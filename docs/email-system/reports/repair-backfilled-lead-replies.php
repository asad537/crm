<?php
// One-off repair after a history backfill that ran BEFORE commit e852f68:
// old customer replies were mirrored into lead conversations as "new, unread, today" and
// flipped leads to "Client Replied". Safe to run more than once.
//   php artisan tinker docs/email-system/reports/repair-backfilled-lead-replies.php < /dev/null
$cut = now()->subDay();
$fixed = 0; $leadIds = [];
$q = App\CrmMessage::withoutGlobalScopes()->where('sender_type', 'client')->whereNotNull('message_id')->orderBy('id');
foreach ($q->lazy(500) as $m) {
    static $cache = [];
    $mid = $m->message_id;
    if (!array_key_exists($mid, $cache)) {
        $cache[$mid] = App\CrmMailMessage::withoutGlobalScopes()->where('message_id', $mid)->min('received_at');
    }
    $real = $cache[$mid];
    if (!$real || $real >= $cut) continue;                      // genuinely recent → leave
    if ($m->created_at->lte(\Carbon\Carbon::parse($real)->addDay())) continue; // already dated right
    $m->timestamps = false;
    $m->forceFill(['created_at' => $real, 'updated_at' => $real, 'is_read' => true])->saveQuietly();
    $fixed++; $leadIds[$m->crm_email_id] = 1;
}
echo "messages re-dated + marked read: $fixed (leads touched: " . count($leadIds) . ")\n";
$restored = 0;
foreach (App\CrmEmail::withoutGlobalScopes()->whereIn('id', array_keys($leadIds))->where('status', 'Client Replied')->get() as $l) {
    $realNew = App\CrmMessage::withoutGlobalScopes()->where('crm_email_id', $l->id)->where('sender_type', 'client')->where('created_at', '>=', $cut)->exists();
    if ($realNew) continue; // a genuinely new reply exists → leave it
    $last = App\CrmMessage::withoutGlobalScopes()->where('crm_email_id', $l->id)->orderByDesc('created_at')->orderByDesc('id')->first();
    $lastTs = App\CrmMessage::withoutGlobalScopes()->where('crm_email_id', $l->id)->max('created_at');
    $status = ($last && in_array($last->sender_type, ['agent', 'admin'], true)) ? 'Responded' : 'Client Replied';
    $l->timestamps = false;
    $l->forceFill(['status' => $status, 'updated_at' => $lastTs ?: $l->updated_at])->saveQuietly();
    $restored++;
}
echo "leads un-bumped / status restored: $restored\n";

<?php
// Run on the server:  php artisan tinker docs/email-system/reports/tracking-numbers-august-2026.php   (change $from/$to for another month)
// August 2026 tracking numbers sent by agents, across all mailboxes.
$from = '2026-08-01'; $to = '2026-09-01';
$pat = '/\b(1Z[0-9A-Z]{16}|9[2-5]\d{19,24}|\d{12}|\d{15}|\d{18}|\d{20,22}|[A-Z]{2}\d{9}[A-Z]{2})\b/';
$agents = App\CrmMailAccount::withoutGlobalScopes()->pluck('email_address','id')->map(fn($e)=>strtolower($e));
$agentSet = array_flip($agents->all());
$carrier = fn($n) => str_starts_with($n,'1Z') ? 'UPS' : (preg_match('/^9[2-5]\d{19,24}$/',$n) ? 'USPS' : (preg_match('/^\d{12}$|^\d{15}$/',$n) ? 'FedEx' : (preg_match('/^\d{10}$/',$n)?'DHL':'?')));
$out = [];
$q = App\CrmMailMessage::withoutGlobalScopes()->with('folder')->select(['id','account_id','folder_id','subject','from_email','to_json','text_body','html_body','is_outgoing','sent_at','received_at'])->whereRaw('COALESCE(sent_at, received_at) between ? and ?', [$from,$to])->where(fn($w)=>$w->where('subject','like','%track%')->orWhere('text_body','like','%tracking%')->orWhere('html_body','like','%tracking%'))->orderBy('id');
foreach ($q->lazy(200) as $m) {
    $body = preg_replace('/\s+/',' ', $m->text_body ?: strip_tags($m->html_body ?? ''));
    if (stripos($body,'tracking') === false && stripos($m->subject ?? '','track') === false) continue;
    if (!preg_match_all($pat, $body, $mm, PREG_OFFSET_CAPTURE)) continue;
    $outgoing = $m->is_outgoing || ($m->folder && $m->folder->type === 'sent') || isset($agentSet[strtolower($m->from_email ?? '')]);
    $tos = collect(is_array($m->to_json) ? $m->to_json : (json_decode($m->to_json,true) ?: []))->map(fn($t)=>strtolower(is_array($t)?($t['email']??$t['address']??''):$t))->filter()->values()->all();
    foreach ($mm[1] as [$num,$off]) {
        $ctx = mb_substr($body, max(0,$off-400), 400);
        if ($outgoing) {
            // number in the agent's own (top, un-quoted) part only
            $top = preg_split('/(?:\bOn .{10,80}wrote:|-----Original Message-----|\bFrom: )/u', $body)[0] ?? $body;
            if (strpos($top, $num) === false) continue;
            $agent = strtolower($m->from_email); $custs = array_values(array_diff($tos, array_keys($agentSet)));
        } else {
            // customer reply: count only if the number sits in a block the agent wrote (quoted "From: agent" / "agent wrote:")
            if (!preg_match('/(?:From:\s*[^>]*<?([\w.+-]+@myboxprinting\.com|[\w.+-]+@almassapackaging\.com|[\w.+-]+@thecustomboxes\.com)>?.{0,400}|([\w.+-]+@(?:myboxprinting|almassapackaging|thecustomboxes)\.com)>?\s*wrote:.{0,400})$/is', $ctx, $am)) continue;
            $agent = strtolower($am[1] ?: $am[2]); $custs = [strtolower($m->from_email)];
        }
        foreach ($custs as $c) { $k = "$agent|$c|$num"; $out[$k] = $out[$k] ?? ['agent'=>$agent,'customer'=>$c,'tracking'=>$num,'carrier'=>$carrier($num),'date'=>substr((string)($m->sent_at ?: $m->received_at),0,10),'via'=>$outgoing?'sent':'customer reply','subject'=>mb_substr($m->subject??'',0,60)]; }
    }
}
usort($out, fn($a,$b)=>[$a['agent'],$a['date']] <=> [$b['agent'],$b['date']]);
echo "agent | customer | tracking | carrier | date | source | subject\n";
foreach ($out as $r) echo implode(' | ', $r), "\n";
echo "TOTAL rows=", count($out), " customers=", count(array_unique(array_map(fn($r)=>$r['agent'].'|'.$r['customer'], $out))), "\n";

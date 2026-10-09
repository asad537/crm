<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * A mailbox (IMAP/SMTP account) connected by a CSR. One CrmUser may own many.
 * Secrets use Laravel's 'encrypted' cast (depends on APP_KEY — back it up).
 */
class CrmMailAccount extends Model
{
    use SoftDeletes;

    protected $table = 'crm_mail_accounts';

    public const PROVIDERS = ['gmail', 'outlook', 'hostinger', 'custom'];

    protected $fillable = [
        'workspace_id', 'crm_user_id', 'email_address', 'display_name',
        'provider', 'auth_type',
        'imap_host', 'imap_port', 'imap_encryption',
        'smtp_host', 'smtp_port', 'smtp_encryption',
        'email_user', 'email_pass',
        'oauth_access_token', 'oauth_refresh_token', 'oauth_expires_at',
        'signature', 'is_active', 'is_default', 'sync_enabled', 'share_with_admin',
        'migrated_from_legacy', 'last_synced_at', 'last_sync_error',
    ];

    // Never serialize secrets (toArray/toJson/@json).
    protected $hidden = ['email_pass', 'oauth_access_token', 'oauth_refresh_token'];

    protected $casts = [
        'email_pass'           => 'encrypted',
        'oauth_access_token'   => 'encrypted',
        'oauth_refresh_token'  => 'encrypted',
        'oauth_expires_at'     => 'datetime',
        'last_synced_at'       => 'datetime',
        'imap_port'            => 'integer',
        'smtp_port'            => 'integer',
        'is_active'            => 'boolean',
        'is_default'           => 'boolean',
        'sync_enabled'         => 'boolean',
        'share_with_admin'     => 'boolean',
        'migrated_from_legacy' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Same workspace scoping pattern as CrmEmail (no-op when context unset, e.g. CLI).
        static::addGlobalScope('crm_workspace', function ($query) {
            if ($workspaceId = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $workspaceId);
            }
        });

        static::creating(function ($account) {
            if (!$account->workspace_id && ($workspaceId = \App\Support\CrmWorkspaceContext::id())) {
                $account->workspace_id = $workspaceId;
            }
        });
    }

    // ---- Relations -------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(CrmUser::class, 'crm_user_id');
    }

    public function workspace()
    {
        return $this->belongsTo(CrmWorkspace::class, 'workspace_id');
    }

    public function folders()
    {
        return $this->hasMany(CrmMailFolder::class, 'account_id');
    }

    public function messages()
    {
        return $this->hasMany(CrmMailMessage::class, 'account_id');
    }

    public function threads()
    {
        return $this->hasMany(CrmMailThread::class, 'account_id');
    }

    // ---- Scopes ----------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSyncable($query)
    {
        return $query->where('is_active', true)->where('sync_enabled', true);
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('crm_user_id', $userId);
    }

    // ---- Helpers ---------------------------------------------------------

    /** Make this the single default account for its owner (transactional). */
    public function makeDefault(): void
    {
        DB::transaction(function () {
            static::withoutGlobalScopes()
                ->where('crm_user_id', $this->crm_user_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);
            $this->forceFill(['is_default' => true])->save();
        });
    }

    /** ext-imap mailbox string for a folder path, e.g. {imap.host:993/imap/ssl}INBOX */
    public function imapMailbox(string $folderPath = 'INBOX'): string
    {
        $enc = $this->imap_encryption === 'none' ? '/notls' : '/' . $this->imap_encryption;
        return '{' . $this->imap_host . ':' . $this->imap_port . '/imap' . $enc . '}' . $folderPath;
    }

    /** Symfony Mailer DSN for this account's SMTP. */
    public function smtpDsn(): string
    {
        $enc = $this->smtp_encryption === 'none' ? '' : '?encryption=' . $this->smtp_encryption;
        return sprintf(
            'smtp://%s:%s@%s:%d%s',
            rawurlencode((string) $this->email_user),
            rawurlencode((string) $this->email_pass),
            $this->smtp_host,
            $this->smtp_port,
            $enc
        );
    }

    /** Best-effort provider from an IMAP/SMTP host name. */
    public static function inferProvider(?string $host): string
    {
        $h = strtolower((string) $host);
        if (str_contains($h, 'gmail') || str_contains($h, 'google')) return 'gmail';
        if (str_contains($h, 'outlook') || str_contains($h, 'office365') || str_contains($h, 'hotmail')) return 'outlook';
        if (str_contains($h, 'hostinger')) return 'hostinger';
        return 'custom';
    }
}

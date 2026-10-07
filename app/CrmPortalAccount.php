<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Customer login for the invoice portal (/login). One account per email per workspace. */
class CrmPortalAccount extends Model
{
    protected $table = 'crm_portal_accounts';

    protected $fillable = ['workspace_id', 'email', 'name', 'password', 'credentials_sent_at', 'last_login_at', 'created_by'];

    protected $hidden = ['password'];

    protected $casts = ['credentials_sent_at' => 'datetime', 'last_login_at' => 'datetime'];

    public static function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    /** Generate a fresh readable password, store its hash, and return the plain text once. */
    public function resetPassword(): string
    {
        $plain = Str::upper(Str::random(4)) . '-' . Str::lower(Str::random(4)) . '-' . random_int(10, 99);
        $this->password = Hash::make($plain);
        $this->save();
        return $plain;
    }

    public function checkPassword(string $plain): bool
    {
        return Hash::check($plain, $this->password);
    }

    /** Manual orders that belong to this customer (matched by billing / customer / inquiry email). */
    public function orders()
    {
        $email = self::normalizeEmail($this->email);
        return CrmManualOrder::withoutGlobalScopes()
            ->where('workspace_id', $this->workspace_id)
            ->where(function ($q) use ($email) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(billing, '$.email'))) = ?", [$email])
                  ->orWhereIn('crm_customer_id', function ($sub) use ($email) {
                      $sub->select('id')->from('crm_customers')->whereRaw('LOWER(email) = ?', [$email]);
                  })
                  ->orWhereIn('crm_email_id', function ($sub) use ($email) {
                      $sub->select('id')->from('crm_emails')->whereRaw('LOWER(client_email) = ?', [$email]);
                  });
            });
    }
}

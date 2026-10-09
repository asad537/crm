<?php

namespace App\Http\Controllers\Crm;

use App\CrmMailAccount;
use App\Http\Controllers\Controller;
use App\Services\Mail\MailConnectionTester;
use App\Services\Mail\ProviderPresets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * JSON API for a CSR's connected mailboxes (Outlook-style client, Phase 2).
 * Every account-bound action is authorised through CrmMailAccountPolicy for the
 * 'crm' guard user. Secrets never leave the server (model $hidden).
 */
class MailAccountController extends Controller
{
    private function user()
    {
        return Auth::guard('crm')->user();
    }

    private function allow(string $ability, $arg = null)
    {
        $user = $this->user();
        abort_unless($user, 403);
        Gate::forUser($user)->authorize($ability, $arg ?? CrmMailAccount::class);
        return $user;
    }

    /** Find an account the current user may act on for $ability (404 if not visible, 403 if not allowed). */
    private function accountFor(string $ability, int $id): CrmMailAccount
    {
        $account = CrmMailAccount::findOrFail($id);
        $this->allow($ability, $account);
        return $account;
    }

    private function present(CrmMailAccount $account, $user): array
    {
        $data = $account->toArray(); // $hidden strips email_pass / oauth tokens
        $data['is_own'] = (int) $account->crm_user_id === (int) $user->id;
        $data['has_password'] = !empty($account->email_pass);
        $data['owner_name'] = optional($account->user)->name;
        return $data;
    }

    // ---- Read ------------------------------------------------------------

    public function index()
    {
        $user = $this->allow('viewAny');

        $query = CrmMailAccount::with('user:id,name')->orderByDesc('is_default')->orderBy('email_address');
        if ($user->isAdmin() || $user->isSalesManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('crm_user_id', $user->id)->orWhere('share_with_admin', true);
            });
        } else {
            $query->where('crm_user_id', $user->id);
        }

        $accounts = $query->get()->map(fn ($a) => $this->present($a, $user))->values();
        return response()->json(['accounts' => $accounts]);
    }

    public function presets()
    {
        $this->allow('viewAny');
        return response()->json(['presets' => ProviderPresets::all()]);
    }

    // ---- Write -----------------------------------------------------------

    private function rules(bool $creating): array
    {
        return [
            'email_address'    => ['required', 'email', 'max:255'],
            'display_name'     => ['nullable', 'string', 'max:255'],
            'provider'         => ['required', Rule::in(CrmMailAccount::PROVIDERS)],
            'imap_host'        => ['nullable', 'string', 'max:255', 'required_if:provider,custom'],
            'imap_port'        => ['nullable', 'integer', Rule::in(MailConnectionTester::IMAP_PORTS)],
            'imap_encryption'  => ['nullable', Rule::in(['ssl', 'tls', 'none'])],
            'smtp_host'        => ['nullable', 'string', 'max:255', 'required_if:provider,custom'],
            'smtp_port'        => ['nullable', 'integer', Rule::in(MailConnectionTester::SMTP_PORTS)],
            'smtp_encryption'  => ['nullable', Rule::in(['ssl', 'tls', 'none'])],
            'email_user'       => ['nullable', 'string', 'max:255'],
            'email_pass'       => [$creating ? 'required' : 'nullable', 'string', 'max:1024'],
            'signature'        => ['nullable', 'string', 'max:20000'],
            'share_with_admin' => ['nullable', 'boolean'],
            'sync_enabled'     => ['nullable', 'boolean'],
            'verify'           => ['nullable', 'boolean'], // run IMAP+SMTP checks before saving (default true)
        ];
    }

    /** Normalise + preset-fill + SSRF-check the connection settings. Returns [data, errors]. */
    private function prepare(array $data, MailConnectionTester $tester): array
    {
        $data['email_address'] = strtolower(trim($data['email_address']));
        $data['email_user'] = trim((string) ($data['email_user'] ?? '')) ?: $data['email_address'];
        $data = ProviderPresets::apply($data['provider'], $data);

        $errors = [];
        try { $tester->assertSafeHost((string) $data['imap_host'], (int) $data['imap_port'], MailConnectionTester::IMAP_PORTS); }
        catch (\InvalidArgumentException $e) { $errors['imap_host'] = [$e->getMessage()]; }
        try { $tester->assertSafeHost((string) $data['smtp_host'], (int) $data['smtp_port'], MailConnectionTester::SMTP_PORTS); }
        catch (\InvalidArgumentException $e) { $errors['smtp_host'] = [$e->getMessage()]; }

        return [$data, $errors];
    }

    private function verifyOrFail(array $cfg, MailConnectionTester $tester): ?array
    {
        $imap = $tester->testImap($cfg);
        $smtp = $tester->testSmtp($cfg);
        if ($imap['ok'] && $smtp['ok']) {
            return null;
        }
        return ['imap' => $imap, 'smtp' => $smtp];
    }

    public function store(Request $request, MailConnectionTester $tester)
    {
        $user = $this->allow('create');
        $validated = $request->validate($this->rules(true));
        [$data, $errors] = $this->prepare($validated, $tester);
        if ($errors) {
            return response()->json(['message' => 'Invalid mail server settings.', 'errors' => $errors], 422);
        }

        $exists = CrmMailAccount::withTrashed()->where('crm_user_id', $user->id)
            ->where('email_address', $data['email_address'])->first();
        if ($exists && !$exists->trashed()) {
            return response()->json(['message' => 'This mailbox is already connected.', 'errors' => ['email_address' => ['Already connected.']]], 422);
        }

        if ($request->boolean('verify', true) && ($fail = $this->verifyOrFail($data, $tester))) {
            return response()->json(['message' => 'Connection test failed.', 'test' => $fail], 422);
        }

        $account = DB::transaction(function () use ($user, $data, $exists) {
            $payload = [
                'crm_user_id'      => $user->id,
                'email_address'    => $data['email_address'],
                'display_name'     => $data['display_name'] ?? $user->name,
                'provider'         => $data['provider'],
                'auth_type'        => 'password',
                'imap_host'        => $data['imap_host'],
                'imap_port'        => (int) $data['imap_port'],
                'imap_encryption'  => $data['imap_encryption'],
                'smtp_host'        => $data['smtp_host'],
                'smtp_port'        => (int) $data['smtp_port'],
                'smtp_encryption'  => $data['smtp_encryption'],
                'email_user'       => $data['email_user'],
                'email_pass'       => $data['email_pass'],
                'signature'        => $data['signature'] ?? null,
                'is_active'        => true,
                'sync_enabled'     => array_key_exists('sync_enabled', $data) ? (bool) $data['sync_enabled'] : true,
                'share_with_admin' => array_key_exists('share_with_admin', $data) ? (bool) $data['share_with_admin'] : true,
                'last_sync_error'  => null,
            ];
            if ($exists) { // re-connecting a previously removed mailbox
                $exists->restore();
                $exists->fill($payload)->save();
                $account = $exists;
            } else {
                $account = CrmMailAccount::create($payload);
            }
            $hasDefault = CrmMailAccount::where('crm_user_id', $user->id)->where('is_default', true)->where('id', '!=', $account->id)->exists();
            if (!$hasDefault) {
                $account->makeDefault();
            }
            return $account->fresh();
        });

        return response()->json(['account' => $this->present($account, $user)], 201);
    }

    public function update(Request $request, $id, MailConnectionTester $tester)
    {
        $user = $this->user();
        $account = $this->accountFor('update', (int) $id);
        $validated = $request->validate($this->rules(false));
        [$data, $errors] = $this->prepare($validated, $tester);
        if ($errors) {
            return response()->json(['message' => 'Invalid mail server settings.', 'errors' => $errors], 422);
        }

        $password = $data['email_pass'] ?? '';
        $cfg = array_merge($data, ['email_pass' => $password !== '' ? $password : $account->email_pass]);

        $connectionChanged = $password !== ''
            || $cfg['imap_host'] !== $account->imap_host || (int) $cfg['imap_port'] !== (int) $account->imap_port || $cfg['imap_encryption'] !== $account->imap_encryption
            || $cfg['smtp_host'] !== $account->smtp_host || (int) $cfg['smtp_port'] !== (int) $account->smtp_port || $cfg['smtp_encryption'] !== $account->smtp_encryption
            || $cfg['email_user'] !== $account->email_user;

        if ($connectionChanged && $request->boolean('verify', true) && ($fail = $this->verifyOrFail($cfg, $tester))) {
            return response()->json(['message' => 'Connection test failed.', 'test' => $fail], 422);
        }

        $account->fill([
            'email_address'    => $cfg['email_address'],
            'display_name'     => $cfg['display_name'] ?? $account->display_name,
            'provider'         => $cfg['provider'],
            'imap_host'        => $cfg['imap_host'],
            'imap_port'        => (int) $cfg['imap_port'],
            'imap_encryption'  => $cfg['imap_encryption'],
            'smtp_host'        => $cfg['smtp_host'],
            'smtp_port'        => (int) $cfg['smtp_port'],
            'smtp_encryption'  => $cfg['smtp_encryption'],
            'email_user'       => $cfg['email_user'],
            'signature'        => array_key_exists('signature', $cfg) ? $cfg['signature'] : $account->signature,
            'sync_enabled'     => array_key_exists('sync_enabled', $cfg) ? (bool) $cfg['sync_enabled'] : $account->sync_enabled,
            'share_with_admin' => array_key_exists('share_with_admin', $cfg) ? (bool) $cfg['share_with_admin'] : $account->share_with_admin,
        ]);
        if ($password !== '') {
            $account->email_pass = $password;
        }
        if ($connectionChanged) {
            $account->last_sync_error = null;
            \Illuminate\Support\Facades\Cache::forget(\App\Services\Mail\ImapSyncService::backoffKey($account->id)); // retry right away
        }
        $account->save();

        return response()->json(['account' => $this->present($account->fresh(), $user)]);
    }

    public function destroy($id)
    {
        $user = $this->user();
        $account = $this->accountFor('delete', (int) $id);

        DB::transaction(function () use ($account, $user) {
            $wasDefault = $account->is_default;
            $account->forceFill(['is_default' => false, 'is_active' => false, 'sync_enabled' => false])->save();
            $account->delete(); // soft delete; synced mail is kept
            if ($wasDefault) {
                $next = CrmMailAccount::where('crm_user_id', $user->id)->where('is_active', true)->orderBy('id')->first();
                if ($next) {
                    $next->makeDefault();
                }
            }
        });

        return response()->json(['success' => true]);
    }

    public function setDefault($id)
    {
        $user = $this->user();
        $account = $this->accountFor('update', (int) $id);
        $account->makeDefault();
        return response()->json(['account' => $this->present($account->fresh(), $user)]);
    }

    /** Toggle is_active or sync_enabled. */
    public function toggle(Request $request, $id)
    {
        $user = $this->user();
        $account = $this->accountFor('update', (int) $id);
        $request->validate(['field' => ['required', Rule::in(['is_active', 'sync_enabled'])]]);
        $field = $request->input('field');
        $account->forceFill([$field => !$account->{$field}])->save();
        return response()->json(['account' => $this->present($account->fresh(), $user)]);
    }

    /**
     * Test a connection. With {id}: the caller's own account (stored password unless a new
     * one is posted). Without: the posted settings, for the add-account form. Never sends mail.
     */
    public function test(Request $request, MailConnectionTester $tester, $id = null)
    {
        if ($id !== null) {
            $account = $this->accountFor('test', (int) $id);
            $validated = $request->validate($this->rules(false));
            [$data, $errors] = $this->prepare(array_merge($account->only([
                'email_address', 'provider', 'imap_host', 'imap_port', 'imap_encryption',
                'smtp_host', 'smtp_port', 'smtp_encryption', 'email_user',
            ]), array_filter($validated, fn ($v) => $v !== null && $v !== '')), $tester);
            if (empty($data['email_pass'])) {
                $data['email_pass'] = $account->email_pass;
            }
        } else {
            $this->allow('create');
            $validated = $request->validate($this->rules(true));
            [$data, $errors] = $this->prepare($validated, $tester);
        }

        if ($errors) {
            return response()->json(['message' => 'Invalid mail server settings.', 'errors' => $errors], 422);
        }

        $imap = $tester->testImap($data);
        $smtp = $tester->testSmtp($data);
        return response()->json([
            'success' => $imap['ok'] && $smtp['ok'],
            'imap'    => $imap,
            'smtp'    => $smtp,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\CrmEmail;
use App\CrmMailAccount;
use App\CrmMailMessage;
use App\CrmMessage;
use App\CrmUser;
use App\Mail\ClientMessage;
use App\Services\Mail\ImapClient;
use App\Services\Mail\ImapClientFactory;
use App\Services\Mail\MailTransportFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Tests\TestCase;

/**
 * Lead-thread replies go out FROM the agent's connected mailbox (so the client's answer comes
 * back there and is linked to the lead); agents without a mailbox still use the legacy global
 * mailer, now with Reply-To set to the agent. Faked SMTP/IMAP — no network.
 */
class LeadReplyViaMailboxTest extends TestCase
{
    private const WS = 1;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        Storage::fake('local');
        $imap = new FakeImap();
        $this->app->instance(ImapClientFactory::class, new class($imap) extends ImapClientFactory {
            public function __construct(private FakeImap $fake) {}
            public function make(CrmMailAccount $account): ImapClient { return $this->fake; }
        });
        $this->app->instance(MailTransportFactory::class, new class extends MailTransportFactory {
            public function for(CrmMailAccount $a): TransportInterface { return new NullTransport(); }
        });
    }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    private function user(): CrmUser
    {
        $u = CrmUser::create(['name' => 'Agent', 'email' => 'a-'.uniqid('', true).'@example.com', 'password' => Hash::make('x'), 'role' => 'sales', 'email_user' => 'agent@acme.test']);
        $u->workspaces()->attach(self::WS, ['role' => 'sales']);
        return $u;
    }
    private function lead(CrmUser $u): CrmEmail
    {
        return CrmEmail::withoutGlobalScopes()->create(['workspace_id' => self::WS, 'client_name' => 'Client', 'client_email' => 'client@client.test', 'product_name' => 'Boxes', 'subject' => 'Boxes', 'status' => 'New', 'assigned_to' => $u->id, 'created_by' => $u->id, 'source' => 'form']);
    }
    private function as(CrmUser $u) { return $this->actingAs($u, 'crm')->withSession(['crm_workspace_id' => self::WS])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']); }

    public function test_reply_goes_out_from_the_connected_mailbox_and_is_mirrored_once(): void
    {
        $u = $this->user(); $lead = $this->lead($u);
        $acc = CrmMailAccount::create(['workspace_id' => self::WS, 'crm_user_id' => $u->id, 'email_address' => 'agent@acme.test', 'display_name' => 'Agent', 'provider' => 'custom',
            'imap_host' => 'imap.acme.test', 'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.acme.test', 'smtp_port' => 587, 'smtp_encryption' => 'tls',
            'email_user' => 'agent@acme.test', 'email_pass' => 'pw', 'is_active' => true, 'is_default' => true]);
        Mail::fake();

        $resp = $this->as($u)->postJson(route('crm.messages.send', $lead->id), ['message_body' => '<p>Hello there</p>', 'email_subject' => 'Re: Boxes'])->assertOk();
        $resp->assertJsonPath('success', true)->assertJsonPath('via', 'agent@acme.test');

        Mail::assertNothingSent(); // not the global mailer
        $sent = CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->first();
        $this->assertNotNull($sent);
        $this->assertTrue((bool) $sent->is_outgoing);
        $this->assertSame('agent@acme.test', $sent->from_email);
        $this->assertSame($lead->id, (int) $sent->crm_email_id);
        $this->assertSame('sent', $sent->folder()->withoutGlobalScopes()->first()->type);

        $mirror = CrmMessage::where('crm_email_id', $lead->id)->get();
        $this->assertCount(1, $mirror, 'exactly one lead-thread message');
        $this->assertSame('admin', $mirror[0]->sender_type);
        $this->assertSame('<p>Hello there</p>', $mirror[0]->message_body, 'lead thread shows the bare body, not the branded email');
        $this->assertSame($sent->message_id, $mirror[0]->message_id, 'Message-ID stored so the client reply can be matched');
        $this->assertSame('Responded', $lead->fresh()->status);
        $this->assertSame($acc->id, (int) $lead->fresh()->mail_account_id);
        $this->assertSame($mirror[0]->id, $resp->json('data.id'));
    }

    public function test_agent_without_mailbox_uses_legacy_mailer_with_reply_to(): void
    {
        $u = $this->user(); $lead = $this->lead($u);
        Mail::fake();
        $this->as($u)->postJson(route('crm.messages.send', $lead->id), ['message_body' => 'Hi'])->assertOk()->assertJsonPath('success', true);
        $this->assertSame(1, CrmMessage::where('crm_email_id', $lead->id)->count());
        // the mailable is built with the agent's email as Reply-To
        $m = new ClientMessage($lead, 'Hi', [], $u);
        $built = $m->build();
        $this->assertSame('agent@acme.test', $built->replyTo[0]['address'] ?? null);
    }
}

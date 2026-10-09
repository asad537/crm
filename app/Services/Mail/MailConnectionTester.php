<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Verifies IMAP login and SMTP authentication for a mailbox config without
 * sending any email. Hosts/ports are validated against SSRF (no private or
 * loopback targets, only mail ports) and server error text is never returned
 * to the client — only a categorised, safe message. Passwords are never logged.
 */
class MailConnectionTester
{
    public const IMAP_PORTS = [143, 993];
    public const SMTP_PORTS = [25, 465, 587, 2525];

    /** @return array{ok:bool,message:string} */
    public function testImap(array $cfg): array
    {
        try {
            $this->assertSafeHost($cfg['imap_host'] ?? '', (int) ($cfg['imap_port'] ?? 0), self::IMAP_PORTS);
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
        if (!function_exists('imap_open')) {
            return ['ok' => false, 'message' => 'IMAP extension is not available on the server.'];
        }

        $enc = ($cfg['imap_encryption'] ?? 'ssl');
        $flag = $enc === 'none' ? '/notls' : '/' . $enc;
        $mailbox = '{' . $cfg['imap_host'] . ':' . (int) $cfg['imap_port'] . '/imap' . $flag . '}INBOX';

        imap_timeout(IMAP_OPENTIMEOUT, 8);
        imap_timeout(IMAP_READTIMEOUT, 8);
        $conn = @imap_open($mailbox, (string) ($cfg['email_user'] ?? ''), (string) ($cfg['email_pass'] ?? ''), OP_HALFOPEN, 1);
        $errors = imap_errors() ?: [];
        if ($conn) {
            imap_close($conn);
            return ['ok' => true, 'message' => 'IMAP login verified.'];
        }
        $raw = implode(' | ', $errors) ?: 'unknown';
        Log::warning('Mail IMAP test failed', ['host' => $cfg['imap_host'], 'port' => $cfg['imap_port'], 'user' => $cfg['email_user'] ?? null, 'error' => $raw]);
        return ['ok' => false, 'message' => $this->categorise($raw, 'IMAP')];
    }

    /** @return array{ok:bool,message:string} */
    public function testSmtp(array $cfg): array
    {
        try {
            $this->assertSafeHost($cfg['smtp_host'] ?? '', (int) ($cfg['smtp_port'] ?? 0), self::SMTP_PORTS);
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $enc = $cfg['smtp_encryption'] ?? 'tls';
        // Symfony: true = implicit TLS (SMTPS), null = auto/STARTTLS, false = plain
        $tls = $enc === 'ssl' ? true : ($enc === 'none' ? false : null);

        try {
            $transport = new EsmtpTransport((string) $cfg['smtp_host'], (int) $cfg['smtp_port'], $tls);
            $transport->setUsername((string) ($cfg['email_user'] ?? ''));
            $transport->setPassword((string) ($cfg['email_pass'] ?? ''));
            $transport->getStream()->setTimeout(8);

            // start() performs EHLO, STARTTLS and AUTH — exactly what we want to verify, without sending.
            $start = new \ReflectionMethod($transport, 'start');
            $start->setAccessible(true);
            $start->invoke($transport);

            $stop = new \ReflectionMethod($transport, 'stop');
            $stop->setAccessible(true);
            $stop->invoke($transport);

            return ['ok' => true, 'message' => 'SMTP authentication verified.'];
        } catch (\Throwable $e) {
            Log::warning('Mail SMTP test failed', ['host' => $cfg['smtp_host'], 'port' => $cfg['smtp_port'], 'user' => $cfg['email_user'] ?? null, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $this->categorise($e->getMessage(), 'SMTP')];
        }
    }

    /**
     * Reject hosts that resolve to private/loopback/reserved ranges and non-mail ports.
     * @throws \InvalidArgumentException with a user-safe message
     */
    public function assertSafeHost(string $host, int $port, array $allowedPorts): void
    {
        $host = strtolower(trim($host));
        if ($host === '' || !preg_match('/^[a-z0-9.-]+$/', $host) || $host === 'localhost' || str_ends_with($host, '.local')) {
            throw new \InvalidArgumentException('Please enter a valid mail server host name.');
        }
        if (!in_array($port, $allowedPorts, true)) {
            throw new \InvalidArgumentException('Port ' . $port . ' is not a supported mail port (' . implode(', ', $allowedPorts) . ').');
        }
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (empty($ips)) {
            throw new \InvalidArgumentException('Mail server host could not be resolved.');
        }
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new \InvalidArgumentException('Mail server host points to a private or reserved network address.');
            }
        }
    }

    private function categorise(string $raw, string $kind): string
    {
        $r = strtolower($raw);
        if (str_contains($r, 'auth') || str_contains($r, 'login') || str_contains($r, 'credential') || str_contains($r, 'password') || str_contains($r, '535')) {
            return $kind . ' login failed: the email or password was rejected by the server.';
        }
        if (str_contains($r, 'certificate') || str_contains($r, 'ssl') || str_contains($r, 'tls')) {
            return $kind . ' secure connection failed: check the encryption setting (SSL/TLS) and port.';
        }
        if (str_contains($r, 'timed out') || str_contains($r, 'timeout') || str_contains($r, 'refused') || str_contains($r, 'resolve') || str_contains($r, 'connect')) {
            return $kind . ' server could not be reached: check the host name and port.';
        }
        return $kind . ' connection failed. Please check the settings and try again.';
    }
}

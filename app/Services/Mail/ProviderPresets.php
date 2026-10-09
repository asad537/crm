<?php

namespace App\Services\Mail;

/**
 * Known IMAP/SMTP settings per provider. "custom" requires explicit hosts.
 */
class ProviderPresets
{
    public const PRESETS = [
        'gmail' => [
            'label' => 'Gmail / Google Workspace',
            'imap_host' => 'imap.gmail.com', 'imap_port' => 993, 'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.gmail.com', 'smtp_port' => 587, 'smtp_encryption' => 'tls',
            'hint' => 'Use a Google App Password (2-Step Verification must be on).',
        ],
        'outlook' => [
            'label' => 'Outlook / Microsoft 365',
            'imap_host' => 'outlook.office365.com', 'imap_port' => 993, 'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.office365.com', 'smtp_port' => 587, 'smtp_encryption' => 'tls',
            'hint' => 'Basic auth may be disabled by your tenant; an app password or admin approval may be required.',
        ],
        'hostinger' => [
            'label' => 'Hostinger',
            'imap_host' => 'imap.hostinger.com', 'imap_port' => 993, 'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.hostinger.com', 'smtp_port' => 587, 'smtp_encryption' => 'tls',
            'hint' => 'Use the mailbox password from hPanel.',
        ],
        'custom' => [
            'label' => 'Other (manual IMAP/SMTP)',
            'imap_host' => null, 'imap_port' => 993, 'imap_encryption' => 'ssl',
            'smtp_host' => null, 'smtp_port' => 587, 'smtp_encryption' => 'tls',
            'hint' => 'Enter the IMAP and SMTP servers from your email provider.',
        ],
    ];

    public static function all(): array
    {
        return self::PRESETS;
    }

    public static function get(string $provider): ?array
    {
        return self::PRESETS[$provider] ?? null;
    }

    /** Fill missing host/port/encryption values from the provider preset. */
    public static function apply(string $provider, array $data): array
    {
        $preset = self::get($provider) ?? self::PRESETS['custom'];
        foreach (['imap_host', 'imap_port', 'imap_encryption', 'smtp_host', 'smtp_port', 'smtp_encryption'] as $key) {
            if (!isset($data[$key]) || $data[$key] === '' || $data[$key] === null) {
                $data[$key] = $preset[$key];
            }
        }
        return $data;
    }
}

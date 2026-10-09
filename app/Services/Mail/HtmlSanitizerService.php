<?php

namespace App\Services\Mail;

/**
 * Sanitises inbound email HTML before it is stored/rendered in the mail client.
 * Uses symfony/html-sanitizer when installed (allow-list: formatting, lists,
 * tables, links, images over http(s)/cid); falls back to strip_tags otherwise.
 * Remote images are kept as-is (the UI decides whether to load them).
 */
class HtmlSanitizerService
{
    private $sanitizer = null;

    public function __construct()
    {
        if (class_exists(\Symfony\Component\HtmlSanitizer\HtmlSanitizer::class)) {
            $config = (new \Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig())
                ->allowSafeElements()
                ->allowStaticElements()
                ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
                ->allowMediaSchemes(['http', 'https', 'cid', 'data'])
                ->allowRelativeLinks(false)
                ->allowRelativeMedias(false)
                ->forceHttpsUrls(false)
                ->allowAttribute('style', '*')
                ->allowAttribute('class', '*')
                ->allowAttribute('width', ['img', 'table', 'td', 'th'])
                ->allowAttribute('height', ['img', 'table', 'td', 'th'])
                ->allowAttribute('align', '*')
                ->allowAttribute('valign', ['td', 'th', 'tr'])
                ->allowAttribute('bgcolor', '*')
                ->allowAttribute('border', ['table', 'img'])
                ->allowAttribute('cellpadding', ['table'])
                ->allowAttribute('cellspacing', ['table'])
                ->allowAttribute('colspan', ['td', 'th'])
                ->allowAttribute('rowspan', ['td', 'th'])
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->forceAttribute('a', 'target', '_blank')
                ->withMaxInputLength(2_000_000);
            $this->sanitizer = new \Symfony\Component\HtmlSanitizer\HtmlSanitizer($config);
        }
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }
        // Keep only the body contents; drop head/style/script wholesale first.
        $html = preg_replace('#<(script|style|head|title|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        if (preg_match('#<body\b[^>]*>(.*)</body>#is', $html, $m)) {
            $html = $m[1];
        }
        if ($this->sanitizer) {
            return $this->sanitizer->sanitizeFor('body', $html);
        }
        return strip_tags($html, '<p><br><b><strong><i><em><u><a><ul><ol><li><blockquote><table><thead><tbody><tr><td><th><img><div><span><h1><h2><h3><h4><pre><code><hr>');
    }

    /** Plain-text preview from text or HTML. */
    public static function snippet(?string $text, ?string $html, int $max = 220): string
    {
        $src = trim((string) $text) !== '' ? (string) $text : html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $src = preg_replace('/\s+/u', ' ', $src) ?? $src;
        $src = trim($src);
        return mb_strlen($src) > $max ? mb_substr($src, 0, $max - 1) . '…' : $src;
    }
}

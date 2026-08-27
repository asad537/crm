<?php

namespace App\Helpers;

use App\CrmWorkspace;
use App\Support\CrmWorkspaceContext;
use Illuminate\Support\Facades\Log;

class SpamDetector
{
    protected static $spamKeywords = [
        'casino', 'crypto', 'bitcoin', 'free money', 'lottery', 'viagra', 'investment opportunity',
        'seo', 'ranking', 'google first page', 'traffic', 'website traffic', 'web design', 'development service', 
        'partnership', 'proposal', 'business opportunity', 'marketing', 'affiliate', 'loan', 'funding', 'rate',
        'erotic', 'porn', 'sex', 'dating', 'girl', 'prize', 'winner', 'click here', 'opt out', 'unsubscribe',
        'whatsapp', 'telegram', 'viber'
    ];

    public static function isSpam($message, $subject, $email)
    {
        $message = strtolower($message);
        $subject = strtolower($subject);

        // 1. Check HARD Keywords (High confidence only)
        // Reduced list to avoid false positives on 'marketing' or 'rate' which could be valid.
        $hardKeywords = [
            'casino', 'crypto', 'bitcoin', 'free money', 'lottery', 'viagra', 'investment opportunity',
            'erotic', 'porn', 'sex', 'dating', 'girl', 'click here', 'opt out', 'unsubscribe',
            'whatsapp', 'telegram', 'viber' 
        ];

        foreach ($hardKeywords as $keyword) {
            if (strpos($message, $keyword) !== false || strpos($subject, $keyword) !== false) {
                return 'Contains spam keyword: ' . $keyword;
            }
        }

        // 2. AI Check (Gemini)
        $aiResult = self::checkGemini($message, $subject, $email);
        if ($aiResult !== null) {
            if ($aiResult['is_spam']) {
                return $aiResult['reason']; 
            }
            return false; // AI said it's NOT spam
        }

        // 3. Fallback Rules (Relaxed)
        // Check for Cyrillic characters (Still useful for Russian spam)
        if (preg_match('/[\p{Cyrillic}]/u', $message)) {
            return 'Contains Cyrillic characters';
        }

        return false; // Not spam
    }

    protected static function checkGemini($message, $subject, $email)
    {
        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return null; // AI not configured
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}";
        
        $prompt = "You are a Spam Detection System for MyBoxPrinting. " .
                  "Your goal is to filter out SEO spam, Web Design proposals, App development spam, Phishing, and Bot messages. " .
                  "ALLOW valid customer inquiries about boxes, printing, quotes. " .
                  "Analyze this inquiry:\n" .
                  "Subject: $subject\n" .
                  "Sender: $email\n" .
                  "Message: $message\n\n" .
                  "Reply ONLY with a raw JSON object (no markdown): {\"is_spam\": boolean, \"reason\": \"short string\"}";

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json'
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        curl_setopt($ch, CURLOPT_TIMEOUT, 6); 

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        $result = json_decode($response, true);
        
        // Parse Gemini Response
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $rawText = $result['candidates'][0]['content']['parts'][0]['text'];
            // Clean markdown if present
            $rawText = str_replace(['```json', '```'], '', $rawText);
            
            $content = json_decode(trim($rawText), true);
            if (isset($content['is_spam'])) {
                return $content;
            }
        }

        return null;
    }

    public static function resolveCountry($ip)
    {
        // For Local Testing: Simulate random countries because VPN won't change 127.0.0.1 on localhost
        if (!$ip || $ip == '127.0.0.1' || $ip == '::1') {
            $testCountries = ['USA', 'UK', 'Australia', 'Canada', 'India', 'Germany', 'UAE'];
            return $testCountries[array_rand($testCountries)];
        }
        
        try {
            $ctx = stream_context_create(['http'=> ['timeout' => 2]]);
            $json = @file_get_contents("http://ip-api.com/json/{$ip}?fields=country", false, $ctx);
            if ($json) {
                $data = json_decode($json, true);
                return $data['country'] ?? null;
            }
        } catch (\Exception $e) { return null; }
        return null;
    }

    public static function logInquiry($data)
    {
        $spamReason = self::isSpam($data['message'] ?? '', $data['subject'] ?? '', $data['client_email'] ?? '');
        
        $ip = $data['ip_address'] ?? null;
        $country = $data['country'] ?? null;
        
        if ($ip && !$country) {
            $country = self::resolveCountry($ip);
        }

        // Website quote forms run outside the authenticated CRM middleware, so no
        // workspace context exists for them. Always attach those submissions to
        // MyBox explicitly; otherwise the CRM workspace scope hides the new row.
        $workspaceId = $data['workspace_id'] ?? CrmWorkspaceContext::id();
        if (!$workspaceId) {
            $workspaceId = CrmWorkspace::where('slug', 'my-box-printing')->value('id');
        }

        if (!$workspaceId) {
            Log::error('Unable to save website inquiry: MyBox CRM workspace is missing.', [
                'client_email' => $data['client_email'] ?? null,
                'subject' => $data['subject'] ?? null,
            ]);

            return $spamReason;
        }

        \App\CrmEmail::withoutGlobalScopes()->create([
            'workspace_id' => $workspaceId,
            'source' => $data['source'] ?? 'MyBox Website',
            'product_name' => $data['product_name'] ?? null,
            'client_name' => $data['client_name'],
            'client_email' => $data['client_email'],
            'client_phone' => $data['client_phone'] ?? null,
            'length' => $data['length'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'unit' => $data['unit'] ?? null,
            'stock' => $data['stock'] ?? null,
            'color' => $data['color'] ?? null,
            'coating' => $data['coating'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'file_url' => $data['file_url'] ?? null,
            'message' => $data['message'] ?? null,
            'subject' => $data['subject'] ?? null,
            'ip_address' => $ip,
            'country' => $country,
            'is_spam' => $spamReason !== false,
            'spam_reason' => $spamReason !== false ? $spamReason : null,
            'status' => $data['status'] ?? 'Qualified Lead',
        ]);

        return $spamReason;
    }
}

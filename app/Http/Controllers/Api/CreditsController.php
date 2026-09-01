<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\UserCredit;
use App\CreditTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CreditsController extends Controller
{
    /**
     * Get user credits, initialize with 1 free credit if new.
     */
    public function credits(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_uid' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $uid = $request->input('firebase_uid');

        try {
            DB::beginTransaction();

            // Lock for update to prevent race conditions during creation
            $userCredit = UserCredit::where('firebase_uid', $uid)->lockForUpdate()->first();

            if (!$userCredit) {
                // Initialize with 1 free credit
                $userCredit = UserCredit::create([
                    'firebase_uid' => $uid,
                    'credits' => 1,
                    'free_credit_granted' => true
                ]);

                CreditTransaction::create([
                    'firebase_uid' => $uid,
                    'change' => 1,
                    'balance_after' => 1,
                    'reason' => 'free_signup',
                    'transaction_id' => 'free_' . $uid . '_' . time(),
                ]);
            } else if (!$userCredit->free_credit_granted) {
                // In case it was created earlier without free credit somehow
                $userCredit->credits += 1;
                $userCredit->free_credit_granted = true;
                $userCredit->save();

                CreditTransaction::create([
                    'firebase_uid' => $uid,
                    'change' => 1,
                    'balance_after' => $userCredit->credits,
                    'reason' => 'free_signup',
                    'transaction_id' => 'free_' . $uid . '_' . time(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'credits' => $userCredit->credits,
                    'free_credit_granted' => $userCredit->free_credit_granted
                ]
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CreditsController@credits Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch credits'], 500);
        }
    }

    /**
     * Verify In-App Purchase and add credits
     */
    public function verifyPurchase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_uid' => 'required|string',
            'platform' => 'required|in:apple,google',
            'product_id' => 'required|string',
            'transaction_id' => 'nullable|string',
            'receipt_data' => 'required_if:platform,apple|string',
            'purchase_token' => 'required_if:platform,google|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $uid = $request->input('firebase_uid');
        $platform = $request->input('platform');
        $productId = $request->input('product_id');
        $transactionId = $request->input('transaction_id');
        
        if ($platform === 'google' && empty($transactionId)) {
            $transactionId = $request->input('purchase_token');
        }

        // Product ID mapping to credits
        $creditMapping = [
            'dielines_basic_5' => 5,
            'dielines_pro_10' => 10,
            'dielines_business_24' => 24,
        ];

        if (!array_key_exists($productId, $creditMapping)) {
            return response()->json(['success' => false, 'message' => 'Invalid product ID'], 400);
        }

        $creditsToAdd = $creditMapping[$productId];

        try {
            // Verify with App Store / Google Play (Server-to-Server)
            $isValid = false;

            if ($platform === 'apple') {
                $issuerId = env('APPLE_IAP_ISSUER_ID');
                $keyId = env('APPLE_IAP_KEY_ID');
                $privateKeyPath = env('APPLE_IAP_PRIVATE_KEY_PATH');
                $bundleId = env('APPLE_BUNDLE_ID', 'com.myboxprinting.app');

                if (empty($issuerId) || empty($keyId) || empty($privateKeyPath)) {
                    if (env('APPLE_ISSUER_ID') === 'sandbox') {
                        $isValid = true; // Auto-approve for sandbox testing
                    } else {
                        Log::error('Apple IAP not configured in .env');
                        $isValid = false;
                    }
                } else {
                    try {
                        $privateKey = file_get_contents(storage_path('app/' . $privateKeyPath));
                        $payload = [
                            'iss' => $issuerId,
                            'iat' => time(),
                            'exp' => time() + 1200,
                            'aud' => 'appstoreconnect-v1',
                            'bid' => $bundleId
                        ];
                        
                        $jwt = \Firebase\JWT\JWT::encode($payload, $privateKey, 'ES256', $keyId);
                        
                        // Try Production First
                        $url = "https://api.storekit.itunes.apple.com/inApps/v1/transactions/{$transactionId}";
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Authorization: Bearer ' . $jwt
                        ]);
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode == 404 || $httpCode == 401) {
                            // Try Sandbox
                            $url = "https://api.storekit-sandbox.itunes.apple.com/inApps/v1/transactions/{$transactionId}";
                            $ch = curl_init($url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                'Authorization: Bearer ' . $jwt
                            ]);
                            $response = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                        }
                        
                        if ($httpCode == 200 && $response) {
                            $decodedRes = json_decode($response, true);
                            if (isset($decodedRes['signedTransactionInfo'])) {
                                $isValid = true;
                            }
                        } else {
                            Log::error("Apple Verification Failed: HTTP {$httpCode} - {$response}");
                            return response()->json(['success' => false, 'message' => "Apple HTTP {$httpCode}: {$response}"], 400);
                        }
                    } catch (\Throwable $e) {
                        Log::error("Apple JWT Error: " . $e->getMessage());
                        return response()->json(['success' => false, 'message' => "Apple Exception: " . $e->getMessage()], 400);
                    }
                }
            } else if ($platform === 'google') {
                $serviceAccount = env('GOOGLE_PLAY_SERVICE_ACCOUNT_JSON');
                $packageName = env('GOOGLE_PLAY_PACKAGE_NAME', 'com.myboxprinting.app');
                
                if (empty($serviceAccount) || $serviceAccount === 'sandbox') {
                    $isValid = true; // Auto-approve for sandbox testing
                } else {
                    try {
                        $jsonPath = storage_path('app/' . $serviceAccount);
                        if (!file_exists($jsonPath)) {
                            throw new \Exception("Service account JSON file not found at " . $jsonPath);
                        }
                        $jsonKey = json_decode(file_get_contents($jsonPath), true);
                        if (!$jsonKey || !isset($jsonKey['private_key']) || !isset($jsonKey['client_email'])) {
                            throw new \Exception("Invalid service account JSON structure.");
                        }

                        // Explicitly require the service class if autoloader misses it
                        if (!class_exists('Google_Service_AndroidPublisher')) {
                            require_once base_path('vendor/google/apiclient/src/Google/Service/AndroidPublisher.php');
                        }

                        $client = new \Google_Client();
                        $credentials = new \Google_Auth_AssertionCredentials(
                            $jsonKey['client_email'],
                            array(\Google_Service_AndroidPublisher::ANDROIDPUBLISHER),
                            $jsonKey['private_key']
                        );
                        $client->setAssertionCredentials($credentials);
                        if ($client->getAuth()->isAccessTokenExpired()) {
                            $client->getAuth()->refreshTokenWithAssertion();
                        }
                        
                        // Get access token from the authenticated client
                        $tokenInfo = $client->getAccessToken();
                        $accessToken = is_array($tokenInfo) ? $tokenInfo['access_token'] : json_decode($tokenInfo)->access_token;
                        
                        $purchaseToken = $request->input('purchase_token');
                        
                        // Use raw cURL to hit the v3 API endpoint because v2 is deprecated in the old SDK
                        $url = "https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{$packageName}/purchases/products/{$productId}/tokens/{$purchaseToken}";
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode !== 200) {
                            Log::error("Google API v3 Verification Failed: HTTP {$httpCode} - {$response}");
                            return response()->json(['success' => false, 'message' => "Google HTTP {$httpCode}: {$response}"], 400);
                        }
                        
                        $purchase = json_decode($response);
                        
                        // purchaseState: 0 means purchased, 1 means canceled, 2 means pending
                        if (!isset($purchase->purchaseState) || $purchase->purchaseState != 0) {
                            return response()->json(['success' => false, 'message' => 'Purchase not verified or pending. State: ' . ($purchase->purchaseState ?? 'unknown')], 400);
                        }
                        $isValid = true;
                    } catch (\Throwable $e) {
                        Log::error("Google API Error: " . $e->getMessage());
                        return response()->json(['success' => false, 'message' => "Google API Exception: " . $e->getMessage()], 400);
                    }
                }
            }

            if (!$isValid) {
                return response()->json(['success' => false, 'message' => 'Receipt verification failed'], 400);
            }

            DB::beginTransaction();

            // Check Idempotency - prevent duplicate processing of the same transaction
            $existingTx = CreditTransaction::where('transaction_id', $transactionId)->first();
            if ($existingTx) {
                DB::rollBack();
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase already processed',
                    'data' => [
                        'added' => 0,
                        'total_credits' => UserCredit::where('firebase_uid', $uid)->value('credits')
                    ]
                ], 200);
            }

            // Lock user credits
            $userCredit = UserCredit::where('firebase_uid', $uid)->lockForUpdate()->first();
            if (!$userCredit) {
                // Should exist due to /credits being called first, but creating just in case
                $userCredit = UserCredit::create([
                    'firebase_uid' => $uid,
                    'credits' => 0,
                    'free_credit_granted' => false
                ]);
            }

            $userCredit->credits += $creditsToAdd;
            $userCredit->save();

            CreditTransaction::create([
                'firebase_uid' => $uid,
                'change' => $creditsToAdd,
                'balance_after' => $userCredit->credits,
                'reason' => 'purchase',
                'platform' => $platform,
                'product_id' => $productId,
                'transaction_id' => $transactionId,
                'raw_payload' => $request->all(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase verified and credits added',
                'data' => [
                    'added' => $creditsToAdd,
                    'total_credits' => $userCredit->credits
                ]
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CreditsController@verifyPurchase Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to process purchase', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Proxy request to Diecut API and deduct credit on success
     */
    public function generateProxy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_uid' => 'required|string',
            'template_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $uid = $request->input('firebase_uid');
        $templateId = $request->input('template_id');

        try {
            DB::beginTransaction();

            $userCredit = UserCredit::where('firebase_uid', $uid)->lockForUpdate()->first();

            if (!$userCredit || $userCredit->credits < 1) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Insufficient credits'], 402); // 402 Payment Required
            }

            // Prepare Diecut API request
            $baseUrl = env('DIECUT_BASE_URL', 'https://api.diecuttmplates.com');
            $apiKey = env('DIECUT_API_KEY');
            
            if (!$apiKey) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Server misconfiguration: missing Diecut API key'], 500);
            }

            // Construct proxy URL to Diecut API
            $url = rtrim($baseUrl, '/') . "/dieline-templates/{$templateId}/dielines";

            // Exclude backend-only params before forwarding
            $payload = $request->except(['firebase_uid', 'template_id']);

            // Send request via cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error || $httpCode >= 400) {
                DB::rollBack();
                return response()->json([
                    'success' => false, 
                    'message' => 'Diecut API error', 
                    'diecut_status' => $httpCode,
                    'diecut_response' => json_decode($response)
                ], $httpCode == 0 ? 500 : $httpCode);
            }

            // Success, deduct credit
            $userCredit->credits -= 1;
            $userCredit->save();

            // Transaction ID for deduction
            $txnId = 'gen_' . $uid . '_' . time() . '_' . rand(1000, 9999);

            CreditTransaction::create([
                'firebase_uid' => $uid,
                'change' => -1,
                'balance_after' => $userCredit->credits,
                'reason' => 'generate_dieline',
                'transaction_id' => $txnId,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dieline generated successfully',
                'credits_remaining' => $userCredit->credits,
                'data' => json_decode($response)
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CreditsController@generateProxy Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to generate dieline proxy', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Apple Store Webhooks (Refunds)
     */
    public function webhookApple(Request $request)
    {
        Log::info('Apple Webhook Received: ', $request->all());
        // Apple sends a signed JWS in 'signedPayload'
        $payload = $request->input('signedPayload');
        if (!$payload) {
            return response()->json(['success' => false], 400);
        }

        try {
            // Simplified decoding: A real implementation must verify JWS signature using APPLE_PRIVATE_KEY
            $parts = explode('.', $payload);
            if (count($parts) === 3) {
                $decoded = json_decode(base64_decode($parts[1]), true);
                $notificationType = $decoded['notificationType'] ?? '';
                
                // Decode transaction info
                $txnInfo = [];
                if (isset($decoded['data']['signedTransactionInfo'])) {
                    $txnParts = explode('.', $decoded['data']['signedTransactionInfo']);
                    if (count($txnParts) === 3) {
                        $txnInfo = json_decode(base64_decode($txnParts[1]), true);
                    }
                }

                $transactionId = $txnInfo['originalTransactionId'] ?? ($txnInfo['transactionId'] ?? null);
                $productId = $txnInfo['productId'] ?? null;
                $appAccountToken = $txnInfo['appAccountToken'] ?? null; // Can map to uid
                
                // If it's a refund
                if ($notificationType === 'REFUND') {
                    $this->handleRefund('apple', $transactionId, $productId, $appAccountToken, $request->all());
                }
            }
            
            return response()->json(['success' => true], 200);
        } catch (\Throwable $e) {
            Log::error('Apple Webhook Error: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Handle Google Play Webhooks (Refunds/Voids)
     */
    public function webhookGoogle(Request $request)
    {
        Log::info('Google Webhook Received: ', $request->all());
        // Google sends base64 encoded data inside message.data
        $message = $request->input('message');
        if (!$message || !isset($message['data'])) {
            return response()->json(['success' => false], 400);
        }

        try {
            $data = json_decode(base64_decode($message['data']), true);

            // Check if it's a voided purchase (OneTimeProductNotification)
            if (isset($data['oneTimeProductNotification'])) {
                $notificationType = $data['oneTimeProductNotification']['notificationType'];
                // 2 = CANCELED (voided/refunded for one-time purchases)
                if ($notificationType == 2) {
                    $purchaseToken = $data['oneTimeProductNotification']['purchaseToken'];
                    $this->handleRefund('google', $purchaseToken, $data['packageName'], null, $request->all());
                }
            }

            return response()->json(['success' => true], 200);
        } catch (\Throwable $e) {
            Log::error('Google Webhook Error: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Helper to process refunds and adjust credits
     */
    private function handleRefund($platform, $transactionId, $productId, $uidInfo, $rawPayload)
    {
        if (!$transactionId) return;

        DB::beginTransaction();

        try {
            // Find the original purchase transaction to identify the user
            $originalTxn = CreditTransaction::where('transaction_id', $transactionId)
                ->where('change', '>', 0)
                ->first();

            $uid = $originalTxn ? $originalTxn->firebase_uid : $uidInfo;

            if (!$uid) {
                DB::rollBack();
                Log::warning("Refund webhook received but could not identify user for txn {$transactionId}");
                return;
            }

            // Check idempotency for the refund action
            $refundTxnId = 'refund_' . $transactionId;
            $existingRefund = CreditTransaction::where('transaction_id', $refundTxnId)->first();
            
            if ($existingRefund) {
                DB::rollBack();
                return; // Already processed
            }

            // How many credits were given initially?
            $creditsToDeduct = $originalTxn ? $originalTxn->change : 0;
            
            if ($creditsToDeduct == 0 && $productId) {
                 $creditMapping = [
                    'dielines_basic_5' => 5,
                    'dielines_pro_10' => 10,
                    'dielines_business_24' => 24,
                ];
                $creditsToDeduct = $creditMapping[$productId] ?? 0;
            }

            if ($creditsToDeduct > 0) {
                $userCredit = UserCredit::where('firebase_uid', $uid)->lockForUpdate()->first();
                if ($userCredit) {
                    $userCredit->credits -= $creditsToDeduct;
                    // Clamp to 0
                    if ($userCredit->credits < 0) {
                        $userCredit->credits = 0;
                    }
                    $userCredit->save();

                    CreditTransaction::create([
                        'firebase_uid' => $uid,
                        'change' => -$creditsToDeduct,
                        'balance_after' => $userCredit->credits,
                        'reason' => 'refund',
                        'platform' => $platform,
                        'product_id' => $productId,
                        'transaction_id' => $refundTxnId,
                        'raw_payload' => $rawPayload,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('handleRefund Error: ' . $e->getMessage());
        }
    }
}

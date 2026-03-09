<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{

    private function generateHash($data)
    {
        ksort($data);
        $hashString = implode('', $data);

        return hash_hmac('sha256', $hashString, env('APG_ENCRYPTION_KEY'));
    }

    // STEP 1: Handshake
    // public function initiate(Request $request)
    // {
    //     $reference = 'ORD' . time();

    //     $data = [
    //         "HS_ChannelId" => env('APG_CHANNEL_ID'),
    //         "HS_IsRedirectionRequest" => "0",
    //         "HS_ReturnURL" =>'https://ranglerzbeta.in/firstphone/transaction-complete',
    //         "HS_MerchantId" => env('APG_MERCHANT_ID'),
    //         "HS_StoreId" => env('APG_STORE_ID'),
    //         "HS_MerchantHash" => env('APG_MERCHANT_HASH'),
    //         "HS_MerchantUsername" => env('APG_USERNAME'),
    //         "HS_MerchantPassword" => env('APG_PASSWORD'),
    //         "HS_TransactionReferenceNumber" => $reference,
    //     ];

    //     $data["HS_RequestHash"] = $this->generateHash($data);

    //     $response = Http::asForm()->post(
    //         env('APG_SANDBOX_URL') . "/HS/HS/HS",
    //         $data
    //     );

    //     return $response->json();
    // }

    private function generateHashdata($data)
    {
        $Key1 = env('APG_KEY1'); // Encryption Key
        $Key2 = env('APG_KEY2'); // IV Key

        $cipher = "aes-128-cbc";

        $mapString =
            "HS_ChannelId=" . $data['HS_ChannelId'] .
            "&HS_IsRedirectionRequest=" . $data['HS_IsRedirectionRequest'] .
            "&HS_MerchantId=" . $data['HS_MerchantId'] .
            "&HS_StoreId=" . $data['HS_StoreId'] .
            "&HS_ReturnURL=" . $data['HS_ReturnURL'] .
            "&HS_MerchantHash=" . $data['HS_MerchantHash'] .
            "&HS_MerchantUsername=" . $data['HS_MerchantUsername'] .
            "&HS_MerchantPassword=" . $data['HS_MerchantPassword'] .
            "&HS_TransactionReferenceNumber=" . $data['HS_TransactionReferenceNumber'];

        $cipherText = openssl_encrypt(
            $mapString,
            $cipher,
            $Key1,
            OPENSSL_RAW_DATA,
            $Key2
        );

        return base64_encode($cipherText);
    }
    public function initiate(Request $request)
    {
        $reference = 'ORD' . time();

        $data = [
            "HS_ChannelId" => env('APG_CHANNEL_ID'),
            "HS_IsRedirectionRequest" => "0",
            "HS_ReturnURL" => "https://ranglerzbeta.in/firstphone/transaction-complete",
            "HS_MerchantId" => env('APG_MERCHANT_ID'),
            "HS_StoreId" => env('APG_STORE_ID'),
            "HS_MerchantHash" => env('APG_MERCHANT_HASH'),
            "HS_MerchantUsername" => env('APG_USERNAME'),
            "HS_MerchantPassword" => env('APG_PASSWORD'),
            "HS_TransactionReferenceNumber" => $reference,
        ];

        $data["HS_RequestHash"] = $this->generateHashdata($data);

        $response = Http::asForm()->post(
            env('APG_SANDBOX_URL') . "/HS/HS/HS",
            $data
        );

        return $response->json();
    }



    // STEP 2: After Payment Return
    public function returnUrl(Request $request)
    {
        $orderId = $request->O; // Order ID from APG

        return response()->json([
            'message' => 'Payment processing',
            'order_id' => $orderId
        ]);
    }

    // STEP 3: IPN Listener
    public function ipnListener(Request $request)
    {
        $url = $request->url;

        $response = Http::get($url);

        $data = $response->json();

        if ($data['TransactionStatus'] == "Paid") {

            // Update Order as Paid
            // Order::where('reference', $data['TransactionReferenceNumber'])->update(['status' => 'paid']);

        }

        return response()->json(['status' => 'IPN processed']);
    }
    // private $merchantId = "ZNJEVENTSCON";
    // private $apiPassword = "62ff0507b23317d047f1274867b42a07";
    // private $apiUrl = "https://bankalfalah.gateway.mastercard.com/api/rest/version/74";

    // /**
    //  * Create Hosted Checkout Session
    //  */
    // public function createHostedCheckout(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'amount' => 'required|numeric',
    //             'user_id' => 'required|integer',
    //             'type' => 'required|in:event,venue,entertainer',
    //             'entity_id' => 'required|integer',
    //             'package_id' => 'required|integer'
    //         ]);

    //         $orderId = uniqid("ADS_");
    //         $amount = number_format((float) $request->amount, 2, '.', '');

    //         Log::info("🎯 Creating Feature Ad Checkout", $request->all());

    //         $response = Http::withOptions(['verify' => false])
    //             ->withBasicAuth("merchant." . $this->merchantId, $this->apiPassword)
    //             ->post($this->apiUrl . "/merchant/" . $this->merchantId . "/session", [
    //                 "apiOperation" => "INITIATE_CHECKOUT",
    //                 "interaction" => [
    //                     "operation" => "PURCHASE",
    //                     "returnUrl" => route('feature.payment.success'),
    //                     "cancelUrl" => route('feature.payment.cancel'),
    //                     "merchant" => [
    //                         "name" => "ZNJ Events",
    //                         "address" => ["line1" => "Pakistan"]
    //                     ]
    //                 ],
    //                 "order" => [
    //                     "id" => $orderId,
    //                     "amount" => $amount,
    //                     "currency" => "PKR",
    //                     "description" => "Feature Ad Payment"
    //                 ]
    //             ]);

    //         $sessionData = $response->json();
    //         Log::info("💳 Bank Alfalah Session Response", $sessionData);

    //         if (!isset($sessionData['session']['id'])) {
    //             Log::error("❌ INITIATE_CHECKOUT failed", $sessionData);
    //             return response()->json([
    //                 'success' => false,
    //                 'error' => 'Checkout initiation failed.'
    //             ], 400);
    //         }

    //         $sessionId = $sessionData['session']['id'];
    //         $resultIndicator = $sessionData['successIndicator'] ?? null;

    //         // ✅ Save in feature_ads_payments
    //         $payment = new FeatureAdsPayment();
    //         $payment->session_id = $sessionId;
    //         $payment->result_indicator = $resultIndicator;
    //         $payment->order_id = $orderId;
    //         $payment->amount = $amount;
    //         $payment->status = 'pending';
    //         $payment->user_id = $request->user_id;

    //         switch ($request->type) {
    //             case 'event':
    //                 $payment->event_id = $request->entity_id;
    //                 $payment->event_feature_ads_package_id = $request->package_id;
    //                 break;
    //             case 'venue':
    //                 $payment->venue_id = $request->entity_id;
    //                 $payment->venue_feature_ads_package_id = $request->package_id;
    //                 break;
    //             case 'entertainer':
    //                 $payment->entertainer_detail_id = $request->entity_id;
    //                 $payment->entertainer_feature_ads_package_id = $request->package_id;
    //                 break;
    //         }

    //         $payment->save();

    //         // ✅ Checkout URL for WebView
    //         $checkoutPageUrl = url('/api/feature/pay/' . $orderId . '?session_id=' . $sessionId);

    //         return response()->json([
    //             'success' => true,
    //             'checkout_url' => $checkoutPageUrl,
    //             'session_id' => $sessionId,
    //             'result_indicator' => $resultIndicator,
    //             'amount' => $amount,
    //             'currency' => 'PKR',
    //             'message' => 'Use this URL in WebView for payment processing'
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error("❌ Feature Ads Checkout Error: " . $e->getMessage());
    //         return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    //     }
    // }

    // /**
    //  * Handle successful callback and store final payment
    //  */
    // public function paymentCallback(Request $request)
    // {
    //     Log::info('🔴 FEATURE ADS PAYMENT CALLBACK', $request->all());

    //     $resultIndicator = $request->get('resultIndicator');
    //     if (!$resultIndicator) {
    //         Log::warning("⚠️ Missing resultIndicator in callback");
    //         return response()->json(['success' => false, 'error' => 'Missing resultIndicator']);
    //     }

    //     $payment = FeatureAdsPayment::where('result_indicator', $resultIndicator)->first();

    //     if (!$payment) {
    //         Log::warning("⚠️ No payment found for resultIndicator: {$resultIndicator}");
    //         return response()->json(['success' => false, 'error' => 'Payment record not found.']);
    //     }

    //     // ✅ Verify payment from Bank Alfalah
    //     $verifyResponse = Http::withOptions(['verify' => false])
    //         ->withBasicAuth("merchant." . $this->merchantId, $this->apiPassword)
    //         ->get($this->apiUrl . "/merchant/" . $this->merchantId . "/order/" . $payment->order_id);

    //     $data = $verifyResponse->json();

    //     if (isset($data['result']) && $data['result'] === 'SUCCESS') {
    //         $payment->update(['status' => 'success']);

    //         // ✅ Update entity feature status
    //         if ($payment->event_id) {
    //             DB::table('events')->where('id', $payment->event_id)->update(['feature_status' => 1]);
    //         } elseif ($payment->venue_id) {
    //             DB::table('venues')->where('id', $payment->venue_id)->update(['feature_status' => 1]);
    //         } elseif ($payment->entertainer_detail_id) {
    //             DB::table('entertainer_details')->where('id', $payment->entertainer_detail_id)->update(['feature_status' => 1]);
    //         }

    //         // ✅ Create record in payments table (like Android)
    //         DB::table('payments')->insert([
    //             'sender_id'      => $payment->user_id,
    //             'event_id'       => $payment->event_id ?? null,
    //             'payment'        => $payment->amount,
    //             'transaction_id' => $payment->order_id,
    //             'type'           => 'feature_ad',
    //             'status'         => '1',
    //             'created_at'     => now(),
    //             'updated_at'     => now()
    //         ]);

    //         Log::info("✅ Feature Ad Payment stored in payments table for order {$payment->order_id}");

    //         return redirect()->route('feature.payment.thankyou', ['order_id' => $payment->order_id]);
    //     }

    //     $payment->update(['status' => 'failed']);
    //     return response()->json(['success' => false, 'error' => 'Payment verification failed']);
    // }

    // /**
    //  * Check Payment Status
    //  */
    // public function checkPaymentStatus(Request $request)
    // {
    //     try {
    //         $orderId = $request->order_id;
    //         if (!$orderId) {
    //             return response()->json(['success' => false, 'error' => 'Order ID is required'], 400);
    //         }

    //         $url = $this->apiUrl . "/merchant/" . $this->merchantId . "/order/" . $orderId;

    //         $response = Http::withOptions(['verify' => false])
    //             ->withBasicAuth("merchant." . $this->merchantId, $this->apiPassword)
    //             ->get($url);

    //         $data = $response->json();

    //         if (isset($data['result']) && $data['result'] === 'SUCCESS') {
    //             FeatureAdsPayment::where('order_id', $orderId)->update(['status' => 'success']);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'status' => $data['status'] ?? 'UNKNOWN',
    //             'result' => $data['result'] ?? 'UNKNOWN',
    //             'gateway_response' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error("❌ checkPaymentStatus error: " . $e->getMessage());
    //         return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    //     }
    // }

    // public function testApi()
    // {
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Feature Ads Payment API Working ✅',
    //         'timestamp' => now(),
    //     ]);
    // }


}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{

    private function generateHash($data)
    {

        $cipher="aes-128-cbc";

        $encrypted = openssl_encrypt(
            $data,
            $cipher,
            env('APG_KEY1'),
            OPENSSL_RAW_DATA,
            env('APG_KEY2')
        );

        return base64_encode($encrypted);

    }

    // 1️⃣ CREATE PAYMENT
    // public function createPayment(Request $request)
    // {

    //     $reference="ORD".time();

    //     Payment::create([
    //         "order_id"=>$request->order_id,
    //         "reference"=>$reference,
    //         "amount"=>$request->amount,
    //         "status"=>"pending"
    //     ]);

    //     return response()->json([
    //         "reference"=>$reference,
    //         "payment_url"=>url("/api/payment/webview/".$reference)
    //     ]);

    // }

    // 2️⃣ WEBVIEW PAGE (HANDSHAKE)
    public function webview(Request $request)
    {

        // $payment=Payment::where("reference",$reference)->firstOrFail();
        $reference="ORD".time();

        $data=[

            "HS_ChannelId"=>env('APG_CHANNEL_ID'),
            "HS_IsRedirectionRequest"=>"0",
            "HS_ReturnURL"=>env('APG_RETURN_URL'),
            "HS_MerchantId"=>env('APG_MERCHANT_ID'),
            "HS_StoreId"=>env('APG_STORE_ID'),
            "HS_MerchantHash"=>env('APG_MERCHANT_HASH'),
            "HS_MerchantUsername"=>env('APG_USERNAME'),
            "HS_MerchantPassword"=>env('APG_PASSWORD'),
            "HS_TransactionReferenceNumber"=>$reference

        ];

        $map=
        "HS_ChannelId=".$data['HS_ChannelId'].
        "&HS_IsRedirectionRequest=".$data['HS_IsRedirectionRequest'].
        "&HS_MerchantId=".$data['HS_MerchantId'].
        "&HS_StoreId=".$data['HS_StoreId'].
        "&HS_ReturnURL=".$data['HS_ReturnURL'].
        "&HS_MerchantHash=".$data['HS_MerchantHash'].
        "&HS_MerchantUsername=".$data['HS_MerchantUsername'].
        "&HS_MerchantPassword=".$data['HS_MerchantPassword'].
        "&HS_TransactionReferenceNumber=".$data['HS_TransactionReferenceNumber'];

        $data['HS_RequestHash']=$this->generateHash($map);

        $response=Http::asForm()->post(
            env('APG_SANDBOX_URL')."/HS/HS/HS",
            $data
        );
        $result=$response->json();

        if ($result['success'] != "true") {

            return response()->json($result);
        }
        $authToken = $result['AuthToken'];

        $map2 =
            "AuthToken=" . $authToken .
            "&ChannelId=" . env('APG_CHANNEL_ID') .
            "&Currency=PKR" .
            "&ReturnURL=" . env('APG_RETURN_URL') .
            "&MerchantId=" . env('APG_MERCHANT_ID') .
            "&StoreId=" . env('APG_STORE_ID') .
            "&MerchantHash=" . env('APG_MERCHANT_HASH') .
            "&MerchantUsername=" . env('APG_USERNAME') .
            "&MerchantPassword=" . env('APG_PASSWORD') .
            "&TransactionTypeId=3" .
            "&TransactionReferenceNumber=" . $reference .
            "&TransactionAmount=" . $request->amount;

        $requestHash = $this->generateHash($map2);
        Payment::create([
            "reference" => $reference,
            "amount" => $request->amount,
            "status" => "pending"
        ]);
        return view("web.payment", [
            "authToken" => $authToken,
            "requestHash" => $requestHash,
            "reference" => $reference,
            "amount" => $request->amount
        ]);
    }

    // 3️⃣ RETURN URL
    public function returnUrl(Request $request)
    {

        $reference=$request->O;
        $url=env('APG_SANDBOX_URL')."/HS/api/IPN/OrderStatus/".env('APG_MERCHANT_ID')."/".env('APG_STORE_ID')."/".$reference;
        return $this->ipnListener($url);

    }

    private function ipnListener($url)
    {
        $response = Http::get($url);
        $body = $response->body();
        
        // Step 1: Remove outer quotes if present
        $body = trim($body, '"');

        // Step 2: Unescape backslashes
        $body = stripslashes($body);

        // Step 3: Decode JSON
        $data = json_decode($body, true);

        if (!$data || !is_array($data)) {
            return response()->json([
                "error" => "Invalid response from APG after decoding",
                "raw" => $body
            ], 400);
        }
        return response()->json([
            "success" => true,
            "status" => $body
        ]);

        $payment = Payment::where("reference", $data['TransactionReferenceNumber'])->first();
        $order = Order::where("order_number", $payment->order_id)->first();
        if (!$payment) {
            return response()->json([
                "error" => "Payment not found",
                "data" => $data
            ], 404);
        }

        $payment->status = ($data['TransactionStatus'] == "Paid") ? "paid" : "failed";
        $payment->transaction_id = $data['TransactionId'] ?? null;
        $payment->response = json_encode($data);
        $payment->save();
        $order->payment_status = ($data['TransactionStatus'] == "Paid") ? "paid" : "unpaid";
        $order->save();

        return response()->json([
            "success" => true,
            "status" => $payment->status,
            "transaction_id" => $payment->transaction_id
        ]);
    }

    // 5️⃣ VERIFY PAYMENT
    public function verifyPayment($reference)
    {

        $url=env('APG_SANDBOX_URL').
        "/HS/api/IPN/OrderStatus/".
        env('APG_MERCHANT_ID')."/".
        env('APG_STORE_ID')."/".
        $reference;

        $response=Http::get($url);

        return $response->json();

    }



}

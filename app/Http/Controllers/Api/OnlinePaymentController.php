<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\CheckOut;
use App\Models\OrderItem;
use App\Models\MobileCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class OnlinePaymentController extends Controller
{
    public function placeOrder(Request $request)
    {
        // dd($request->all());

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            // $products = $request->products;
            $products = $request->products ?? $request->all();

            // Sequential Order Number
            $lastOrder = Order::orderBy('id', 'desc')->first();
            $newOrderNumber = $lastOrder ? $lastOrder->order_number + 1 : 10000000;

            // Create Order
            $order = '';
            if($request->delivery_method == 'Online'){
                  $order = Order::create([
                    'customer_id'      => auth()->id(),
                    'order_number'     => $newOrderNumber,
                    'payment_status'   => 'paid',
                    'order_status'     => 'inprogress',
                    'delivery_method'  => $request->delivery_method,
                    'shipping_charges'  => $request->shipping_charges,
                ]);
            } else {
                    $order = Order::create([
                    'customer_id'      => auth()->id(),
                    'order_number'     => $newOrderNumber,
                    'payment_status'   => 'unpaid',
                    'order_status'     => 'inprogress',
                    'delivery_method'  => $request->delivery_method,
                    'shipping_charges'  => $request->shipping_charges,
                    ]);
            }

            $vendorIds = [];
            $orderedListingIds = [];

            // Handle Single Product
            if (isset($products['product_id'])) {
                $orderedListingIds[] = $products['product_id'];
                $vendorIds[] = $products['vendor_id'];

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $products['product_id'],
                    'vendor_id'  => $products['vendor_id'],
                    'quantity'   => $products['quantity'] ?? 1,
                    'price'      => $products['price'],
                ]);
                $totalAmount = $product['price'] * ($product['quantity'] ?? 1);
            } else {
                // Handle Multiple Products
                foreach ($products as $product) {
                    $vendorIds[] = $product['vendor_id'];
                    $orderedListingIds[] = $product['product_id'];

                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $product['product_id'],
                        'vendor_id'  => $product['vendor_id'],
                        'quantity'   => $product['quantity'] ?? 1,
                        'price'      => $product['price'],
                    ]);

                    $totalAmount += $product['price'] * ($product['quantity'] ?? 1);
                }
            }
            $totalAmount += $request->shipping_charges;
            // Update order total
            $order->update(['total_amount' => $totalAmount]);

            MobileCart::where('user_id', auth()->id())
            ->whereIn('mobile_listing_id', $orderedListingIds)
            ->update(['is_ordered' => 1]);

            // Notify all unique vendors
            foreach (array_unique($vendorIds) as $vendorId) {
                $vendor = \App\Models\Vendor::find($vendorId);
                if ($vendor && !empty($vendor->fcm_token)) {
                    \App\Helpers\NotificationHelper::sendFcmNotification(
                        $vendor->fcm_token,
                        "New Order Received",
                        "You have received a new order #{$order->order_number}, Total: Rs {$totalAmount}",
                        [
                            'order_id'     => (string) $order->id,
                            'order_number' => (string) $order->order_number,
                            'total_amount' => (string) $totalAmount,
                        ]
                    );
                }
            }

            DB::commit();

            CheckOut::where('user_id', auth()->id())->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Order placed successfully & vendors notified',
                'data'    => [
                    'order' => $order,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong!',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function deliveryMethods()
{
    return response()->json([
        'message' => 'Delivery methods fetched successfully',
        'data' => [
            [
                'key'   => 'go_shop',
                // 'label' => 'Go to Shop'
            ],
            [
                'key'   => 'cod',
                // 'label' => 'Cash on Delivery'
            ],
            [
                'key'   => 'online',
                // 'label' => 'Online Payment'
            ],
        ]
    ], 200);
}

}

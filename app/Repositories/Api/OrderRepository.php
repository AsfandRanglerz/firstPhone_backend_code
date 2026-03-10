<?php

namespace App\Repositories\Api;

use Carbon\Carbon;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\CheckOut;
use App\Models\OrderItem;
use App\Models\MobileCart;
use App\Models\CancelOrder;
use App\Models\MobileModel;
use Illuminate\Support\Str;
use App\Models\VendorMobile;
use Illuminate\Http\Request;
use App\Models\DeviceReceipt;
use App\Models\MobileListing;
use App\Models\Notification;
use App\Models\NotificationTarget;
use InvalidArgumentException;
use App\Helpers\NotificationHelper;
use App\Models\ShippingAddress;
use App\Mail\OrderShipped;
use App\Mail\OrderDelivered;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Access\AuthorizationException;
use App\Repositories\Api\Interfaces\OrderRepositoryInterface;

class OrderRepository implements OrderRepositoryInterface
{
    public function getOrdersByCustomerAndStatus(int $customerId, string $status): Collection
{  
    $data = [];
        if($status == 'inprogress'){
            $data = [$status,'shipped'];
        }else{
            $data = [$status];
        }
    return Order::with(['items.vendor'])
        ->where('customer_id', $customerId)
        ->whereIn('order_status', $data)
        ->latest()
        ->get()
        ->map(function ($order) {
 
            // Vendor name from first item
            $vendorName = optional($order->items->first()?->vendor)->name;

            return [
                'id' =>$order->id,
                'order_id'        => '#' . $order->order_number,
                'payment_method'  => $order->delivery_method,
                'shop_name'       => $vendorName,
                'vendor_address'  => optional($order->items->first()?->vendor)->location,
                'vendor_id'  => optional($order->items->first()?->vendor)->id,
                'vendor_image'  => optional($order->items->first()?->vendor)->image,
                'vendor_phone'  => optional($order->items->first()?->vendor)->phone,
                'total_price'     => $order->total_amount,
                'total_products'  => $order->items->sum('quantity'),
                'date'            => Carbon::parse($order->created_at)->format('F d, Y'),
                'time' => $order->created_at->format('h:i A'),
                'order_status'    => $order->order_status,
                'payment_status' => $order->payment_status,
                'user_type'       => 'vendor',
            ];
        });
}


public function getOrdersByVendorAndStatus(int $vendorId, string $status): Collection
    {
        $data = [];
        if($status == 'inprogress'){
            $data = [$status,'shipped'];
        }else{
            $data = [$status];
        }
        return Order::with(['items.vendor'])
        ->whereHas('items', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })
        ->whereIn('order_status', $data)
        ->latest()
        ->get()
        ->map(function ($order) use ($vendorId) {

            // Only items of this vendor
            $vendorItems = $order->items->where('vendor_id', $vendorId);

            $hasCancelRequest = $order->cancelOrder()->exists();

            // Vendor name
            $vendorName = optional($vendorItems->first()?->vendor)->name;

            $vendorItem = $vendorItems->first();
            $shipping = $order->delivery_method == 'go_shop' ? 0 : 200;
            return [
                'id' => $order->id,
                'order_item_id'  => $vendorItem->id,
                'order_id'       => '#' . $order->order_number,
                'payment_method' => $order->delivery_method,
                'customer_id'    => $order->customer_id,
                'shop_name'      => $vendorName,
                'total_price'    => $vendorItems->sum(fn ($item) => $item->price * $item->quantity) + $shipping,
                'total_products' => $vendorItems->sum('quantity'),
                'date'           => Carbon::parse($order->created_at)->format('F d, Y'),
                'time' => $order->created_at->format('h:i A'),
                'order_status'   => $order->order_status,
                'has_cancel_request' => $hasCancelRequest,
                'payment_status' => $order->payment_status,
            ];
        });
    }



    public function getOrderWithRelations(int $orderId, int $customerId): Order
    {
        return Order::with(['items.product', 'items.vendor'])
            ->where('customer_id', $customerId)
            ->findOrFail($orderId);
    }

    public function getOrderByIdAndCustomer(int $orderId, int $customerId): Order
    {
        return Order::where('id', $orderId)
            // ->where('customer_id', $customerId)
            ->firstOrFail();
    }

    public function getOrderByIdAndVendor(int $orderId, int $vendorId): Order
    {
        return Order::where('id', $orderId)
            ->whereHas('items', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })
            ->firstOrFail();
            
    }

    public function getSalesReport(int $vendorId): array
    {
        // Helper function to calculate totals
        $calculateTotals = function ($type = 'overall') use ($vendorId) {
            $query = OrderItem::where('vendor_id', $vendorId)
                ->whereHas('order', function ($q) {
                    $q->where('payment_status', 'paid');
                })
                ->join('orders', 'orders.id', '=', 'order_items.order_id');

            if ($type === 'today') {
                $query->whereDate('orders.created_at', now()->toDateString());
            }

            $totals = $query->selectRaw('orders.delivery_method, SUM(order_items.price * order_items.quantity) as total')
                ->groupBy('orders.delivery_method')
                ->pluck('total', 'orders.delivery_method')
                ->toArray();

            return [
                'cod_orders_total'    => $totals['cod']    ?? 0,
                'online_orders_total' => $totals['online'] ?? 0,
                'pickup_orders_total' => $totals['pickup'] ?? 0,
                'goshop_orders_total' => $totals['go_shop'] ?? 0,
                'grand_total'         => array_sum($totals),
            ];
        };

        return [
            'today'   => $calculateTotals('today'),
            'overall' => $calculateTotals('overall'),
        ];
    }


    public function getOrderStatistics(int $vendorId): array
    {
        $todayDate = now()->format('Y-m-d');

        // Aaj ke orders (vendor ke products)
        $todayOrders = Order::whereHas('items', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            })
            ->whereDate('created_at', $todayDate)
            ->select(
                'id',
                'order_number',
                'payment_status',
                'order_status',
                DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as formatted_date")
            )
            ->get();

        // Overall orders (vendor ke sab)
        $overallOrders = Order::whereHas('items', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            })
            ->select(
                'id',
                'order_number',
                'payment_status',
                'order_status',
                DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as formatted_date")
            )
            ->get();

        return [
            'today_orders'   => $todayOrders,
            'overall_orders' => $overallOrders,
        ];
    }




    // Repository
    public function customerorderlist($orderId)
    {
        $order = Order::with(['items', 'customer', 'shippingAddress','items.vendor'])
            ->findOrFail($orderId);

        $subtotal = $order->items->sum(fn($item) => $item->price * $item->quantity);
        // $shippingCharges = $order->shipping_charges ?? 0;
        // $total = $subtotal + $shippingCharges;
        $total = $subtotal + ($order->shipping_charges ?? 0);
        // $total = $subtotal;
        return [
            'id'       => $order->id,
            'order_date'     => Carbon::parse($order->created_at)->format('F d, Y'),
            'order_time' => $order->created_at->format('h:i A'),
            'order_id'       => '#' . $order->order_number,
            'customer'       => [
                'name'          => $order->shippingAddress->name ?? $order->customer->name,
                'email'         => $order->shippingAddress->email ?? $order->customer->email,
                'phone_number'  => $order->shippingAddress->phone_number ?? $order->customer->phone,
                'city'          => $order->shippingAddress->city ?? null,
                'postal_code'   => $order->shippingAddress->postal_code ?? null,
                'street_address' => $order->shippingAddress->street_address ?? null,
            ],
            'products'       => $order->items->map(fn($item) => [
                'vendor_name' => $item->vendor->name ?? null,
                'product_name' => ($item->product->brand->name ?? '') . ' ' . ($item->product->model->name ?? $item->product_name),
                'price'        => $item->price,
                'quantity'     => $item->quantity,
                'image'        => $item->product->image
                    ? asset(
                        is_array(json_decode($item->product->image, true))
                            ? ltrim(json_decode($item->product->image, true)[0], '/')   // ✅ First from JSON array
                            : ltrim(explode(',', $item->product->image)[0], '/')       // ✅ First from comma string
                    )
                    : null,
            ]),
            'order_status'   => $order->order_status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->delivery_method,
            // 'subtotal'       => $subtotal,
            // 'shipping'       => $shippingCharges,
            'total'          => $total,
        ];
    }

public function getorderlist()
{
    $userId = Auth::id();
    

   if (!$userId) {
        throw new \Exception('Unauthorized', 401);
    }


    // 👉 Get all checkout items of this user
    $checkoutItems = CheckOut::where('user_id', $userId)->get();

    
    if ($checkoutItems->isEmpty()) {
        throw new \Exception('No checkout items found', 404);
    }

    

    $uniqueItems = $checkoutItems->unique(function ($item) {
    return $item->brand_name
        . '|' . $item->model_name
        . '|' . $item->price
        . '|' . $item->quantity;
    })->values();


    // 👉 Get shipping address of this user
    $shipping = ShippingAddress::where('customer_id', $userId)->first();

    // 💰 Subtotal calculation
    $subtotal = $uniqueItems->sum(function ($item) {
        return ($item->price ?? 0) * ($item->quantity ?? 0);
    });

    // $shippingCharges = 0; // or fetch dynamically if needed
    // $total = $subtotal + $shippingCharges;
    $total = $subtotal;

    return [

        'user_id' => $userId,

        // ⭐ CUSTOMER DATA
        'customer' => [
            'name'           => $shipping->name ?? null,
            'email'          => $shipping->email ?? null,
            'phone_number'   => $shipping->phone ?? null,
            'city'           => $shipping->city ?? null,
            'postal_code'    => $shipping->postal_code ?? null,
            'street_address' => $shipping->street_address ?? null,
        ],

        // ⭐ PRODUCTS FROM CHECKOUT TABLE
        'products' => $uniqueItems->map(function ($item) {

           

            return [
                'product_id' => $item->product_id,   // ✅ ALWAYS correct
                'vendor_id'  => $item->vendor_id,
                'shop_name'  => $item->vendor_name,
                'brand_name' => $item->brand_name,
                'model_name' => $item->model_name,
                'price'      => $item->price,
                'quantity'   => $item->quantity,
                'image'        => $item->image
                    ? asset(
                        is_array(json_decode($item->image, true))
                            ? ltrim(json_decode($item->image, true)[0], '/')   // ✅ First from JSON array
                            : ltrim(explode(',', $item->image)[0], '/')       // ✅ First from comma string
                    )
                    : null,
            ];
        }),

        'subtotal' => $subtotal,
        // 'shipping' => $shippingCharges,
        'total'    => $total,
    ];
}

public function getVendorOrderDetails(int $vendorId, int $orderId): array
    {
        $order = Order::with([
            'items.product.brand',
            'items.product.model',
            'shippingAddress',
            'items.vendor'
        ])->findOrFail($orderId);

        // ✅ Vendor-specific items
        $vendorItems = $order->items->where('vendor_id', $vendorId);

        if ($vendorItems->isEmpty()) {
            throw new \Exception('Unauthorized access to this order');
        }

        // 💰 Subtotal for vendor only
        $subtotal = $vendorItems->sum(fn ($item) =>
            ($item->price ?? 0) * ($item->quantity ?? 0)
        );

        // $shippingCharges = 0;
        // $total = $subtotal + $shippingCharges;
        $total  = $subtotal + ($order->shipping_charges ?? 0);

        return [
            'id'       => $order->id,
            'order_date'     => Carbon::parse($order->created_at)->format('F d, Y'),
            'order_time' => $order->created_at->format('h:i A'),
            'order_id'        => '#' . $order->order_number,
            'order_status'    => ucfirst($order->order_status),
            'payment_method'  => ucfirst($order->delivery_method ?? 'online'),
            'payment_status'  => ucfirst($order->payment_status),
            
            // ⭐ PRODUCTS
            'products' => $vendorItems->map(function ($item) {

                $images = json_decode($item->product->image ?? '[]', true);

                return [
                    'vendor_name' => $item->vendor->name ?? null,       
                    'product_id' => $item->product_id,
                    'title'      => trim(
                        ($item->product->brand->name ?? '') . ' ' .
                        ($item->product->model->name ?? '')
                    ),
                    'price'      => $item->price,
                    'quantity'   => $item->quantity,
                    'image'      => !empty($images)
                        ? asset(ltrim($images[0], '/'))
                        : null,
                ];
            })->values(),

            // ⭐ CUSTOMER DETAILS
            'customer' => [
                'name'           => $order->shippingAddress->name ?? null,
                'email'          => $order->shippingAddress->email ?? null,
                'phone'          => $order->shippingAddress->phone ?? null,
                'city'           => $order->shippingAddress->city ?? null,
                'postal_code'    => $order->shippingAddress->postal_code ?? null,
                'street_address' => $order->shippingAddress->street_address ?? null,
            ],

            // ⭐ PRICE SUMMARY
            'subtotal' => $subtotal,
            // 'shipping' => $shippingCharges,
            'total'    => $total,
        ];
    }

    public function createDeviceReceipts(int $orderId, array $devices): array
    {
        $vendor = Auth::id();

        // ✅ Load the order with items
        $order = Order::with(['items'])->findOrFail($orderId);

        $createdReceipts = [];

        foreach ($devices as $device) {
            // ✅ Make sure the product_id exists in this order
            $item = $order->items->where('product_id', $device['product_id'])->first();

            if (!$item) {
                throw new \Exception("Product not found in this order");
            }

            // ✅ Fetch product details from mobile listing
            $mobile = VendorMobile::with(['brand', 'model'])->find($item->product_id);

            if (!$mobile) {
                throw new \Exception("Mobile listing not found for product_id: " . $item->product_id);
            }

            $paymentId = strtoupper(Str::random(12));

            // Ensure uniqueness in DB
            while (DeviceReceipt::where('payment_id', $paymentId)->exists()) {
                $paymentId = strtoupper(Str::random(12));
            }

            // ✅ Create receipt
            $receipt = DeviceReceipt::create([
                'order_id'   => $orderId,
                'order_item_id' => $item->id,
                'product_id' => $mobile->id,
                'brand_id'      => $mobile->brand_id ?? 'Unknown',
                'model_id'      => $mobile->model_id ?? 'Unknown',
                'imei_one'      => $device['imei_one'] ?? null,
                'imei_two'      => $device['imei_two'] ?? null,
                'payment_id'   => $paymentId,
            ]);

            $createdReceipts[] = $receipt;
        }

        return $createdReceipts;
    }

    // public function getReceiptById(int $deviceReceiptId): array
    // {
    //     $deviceReceipt = DeviceReceipt::with([
    //         'brand:id,name',
    //         'model:id,name',
    //         'order.customer',
    //         'order.vendor',
    //         'order.items.deviceReceipts.brand',
    //         'order.items.deviceReceipts.model',
    //         'order.items.vendor',
    //     ])->findOrFail($deviceReceiptId);

    //     $order = $deviceReceipt->order;

    //     $vendorName = optional($order->items->first()->vendor)->name;
    //     $deviceReceipt->created_at_formatted = $deviceReceipt->created_at->format('d-m-Y H:i:s');
    //     $response = [
    //         'order_number'    => $order->order_number,
    //         'delivery_method' => $order->delivery_method,
    //         'from_customer'   => $order->customer?->name,
    //         'to_vendor'       => $vendorName,
    //         'payment_id'     => $deviceReceipt->payment_id,
    //         'total_products'  => $order->items->count(),
    //         'products'        => [],
    //         'created_at'      => $deviceReceipt->created_at_formatted,
    //     ];

    //     foreach ($order->items as $item) {
    //         foreach ($item->deviceReceipts as $receipt) {
    //             $response['products'][] = [
    //                 'brand'    => $receipt->brand?->name ?? 'N/A',
    //                 'model'    => $receipt->model?->name ?? 'N/A',
    //                 'imei_one' => $receipt->imei_one,
    //                 'imei_two' => $receipt->imei_two,
    //                 'quantity' => $item->quantity,
    //                 'price'    => $item->price,
    //                 'total'    => $item->quantity * $item->price,
    //             ];
    //         }
    //     }

    //     $response['total_amount'] = collect($response['products'])->sum('total');

    //     return $response;
    // }

    public function getReceiptById(int $orderId): array
    {
        $deviceReceipts = DeviceReceipt::with([
            'brand:id,name',
            'model:id,name',
            'order.customer',
            'order.vendor',
            'order.items.vendor',
        ])->where('order_id', $orderId)->get();

        if ($deviceReceipts->isEmpty()) {
            throw new \Exception('No receipt found for this order.');
        }

        $order = $deviceReceipts->first()->order;

        $vendorName = optional($order->items->first()->vendor)->name;

        $response = [
            'order_number'    => $order->order_number,
            'delivery_method' => $order->delivery_method,
            'from_customer'   => $order->customer?->name,
            'to_vendor'       => $vendorName,
            'payment_id'      => $deviceReceipts->first()->payment_id,
            'total_products'  => $deviceReceipts->count(),
            'products'        => [],
            'created_at'      => $deviceReceipts->first()->created_at->format('d-m-Y H:i:s'),
        ];

        foreach ($deviceReceipts as $receipt) {
            $orderItem = $order->items->firstWhere('id', $receipt->order_item_id);
            $quantity = $orderItem?->quantity ?? 1;
            $response['products'][] = [
                'brand'    => $receipt->brand?->name ?? 'N/A',
                'model'    => $receipt->model?->name ?? 'N/A',
                'storage'  => $orderItem?->product?->storage ?? 'N/A',
                'imei_one' => $receipt->imei_one,
                'imei_two' => $receipt->imei_two,
                'quantity' => $quantity,
                'price'    => $orderItem->price ?? 0,
                'total'    => $quantity * $orderItem->price,
            ];
        }

        $response['total_amount'] = collect($response['products'])->sum('total');

        return $response;
    }


    public function getBrandByOrderId(int $orderId): Collection
    {
        return OrderItem::with([
            'product.brand'
        ])
        ->whereHas('order', function ($q) use ($orderId) {
            $q->where('id', $orderId);
        })
        ->get()
        ->map(function ($item) {
            return [
                'brand_id'   => $item->product?->brand?->id,
                'brand_name' => $item->product?->brand?->name,
            ];
        })
        ->unique('brand_id')
        ->values();
    }

   public function getBrandModelByOrderId(int $orderId, int $brandId): Collection
    {
        return OrderItem::with('product.model')
            ->whereHas('order', function ($q) use ($orderId) {
                $q->where('id', $orderId);
            })
            ->whereHas('product', function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            })
            ->get()
            ->map(function ($item) {
                return [
                    'model_id'   => $item->product?->model?->id,
                    'model_name' => $item->product?->model?->name,
                ];
            })
            ->unique('model_id')
            ->values();
    }

    public function updateOrderStatusByVendor(
    int $vendorId,
    int $orderId,
    string $action,
    ?int $orderItemId = null,
    ?string $reason = null
): array {

    return DB::transaction(function () use (
        $vendorId,
        $orderId,
        $action,
        $orderItemId,
        $reason
    ) {
  
        $order = Order::with(['items.product','customer'])->findOrFail($orderId);
    
        /* ---------------- CANCEL REQUEST ---------------- */
         if ($action === 'cancel') {

            if (!$orderItemId) {
                throw new InvalidArgumentException('Order item ID is required');
            }

            if ($order->order_status === 'delivered') {
                throw new InvalidArgumentException(
                    'Delivered order cannot be cancelled'
                );
            }

            // ✅ Verify order item belongs to vendor
            $orderItem = $order->items
                ->where('id', $orderItemId)
                ->where('vendor_id', $vendorId)
                ->first();

            if (!$orderItem) {
                throw new AuthorizationException('Unauthorized order item');
            }


            $alreadyRequested = CancelOrder::where('order_id', $orderId)
                ->where('status', 'requested')
                ->exists();

            if ($alreadyRequested) {
                throw new InvalidArgumentException(
                    'Cancellation request already submitted'
                );
            }
                if($order->delivery_method !== 'online'){
                    $order->order_status = 'cancelled';
                    $order->save();
                    foreach ($order->items as $item) {
                        if ($item->product) {
                            $item->product->increment('stock', $item->quantity);
                        }
                    }
                } 
                if ($order->delivery_method == 'cod' || $order->delivery_method == 'go_shop') {
                    $notification = Notification::create([
                            'user_type' => 'customers',
                            'title' => "Order Cancelled",
                            'description' => "Your order #{$order->order_number} has been cancelled.",
                        ]);
                        NotificationTarget::create([
                            'notification_id' => $notification->id,
                            'targetable_id' => $order->customer->id ?? null,
                            'targetable_type' => 'App\Models\User',
                            'type' => 'order_cancellation',
                        ]);
                    if (!empty($order->customer->fcm_token)) {
                        NotificationHelper::sendFcmNotification(
                            $order->customer->fcm_token,
                            "Order Cancelled",
                            "Your order #{$order->order_number} has been cancelled.",
                            [
                                'type' => 'order_cancellation',
                                'order_id' => (string) $order->id,
                            ]
                        );
                    }
                } 
                if($order->delivery_method === 'online'){
                    if (empty($reason)) {
                        throw new InvalidArgumentException(
                            'Cancellation reason is required'
                        );
                    }
                        CancelOrder::create([
                        'order_id'      => $order->id,
                        'order_item_id' => $orderItem->id,
                        'reason'        => $reason,
                        'status'        => 'requested',
                        ]);
                        return [
                            'order_id'      => $order->id,
                            'order_item_id' => $orderItem->id,
                            'order_status'  => $order->order_status,
                            'payment_status'=> $order->payment_status,
                            'message'       => 'Cancellation request sent to admin',
                        ];
                }
                return [
                'order_id'      => $order->id,
                'order_item_id' => $orderItem->id,
                'order_status'  => $order->order_status,
                'payment_status'=> $order->payment_status,
                'message'       => 'Order cancelled successfully',
            ];
            

            
        }

        /* ---------------- SHIPPED ---------------- */
        if ($action === 'shipped') {

            if (!in_array($order->order_status, ['confirmed', 'inprogress'])) {
                throw new InvalidArgumentException(
                    'Order cannot be marked as shipped'
                );
            }

            $order->order_status = 'shipped';
            // ✅ Set shipped_at only once
            if (is_null($order->shipped_at)) {
                $order->shipped_at = Carbon::now('Asia/Karachi');
            }
            $order->save();
            if ($order->delivery_method == 'cod' || $order->delivery_method == 'online') {
                 $notification = Notification::create([
                            'user_type' => 'customers',
                            'title' => "Order Shipped",
                            'description' => "Your order #{$order->order_number} has been dispatched.",
                        ]);
                        NotificationTarget::create([
                            'notification_id' => $notification->id,
                            'targetable_id' => $order->customer->id ?? null,
                            'targetable_type' => 'App\Models\User',
                            'type' => 'order_shipped',
                            'order_id' => $order->id,
                        ]);
                if (!empty($order->customer->fcm_token)) {
                    NotificationHelper::sendFcmNotification(
                        $order->customer->fcm_token,
                        "Order Shipped",
                        "Your order #{$order->order_number} has been dispatched.",
                        [
                            'type' => 'order_shipped',
                            'order_id' => (string) $order->id,
                        ]
                    );
                }
                Mail::to($order->customer->email)->send(new OrderShipped($order->customer->name, $order->order_number));
            }
             
            return [
                'order_id'     => $order->id,
                'order_status' => 'shipped',
                'shipped_at'   => $order->shipped_at->format('d M Y'),
                // 'payment_status'=> $order->payment_status,
                'message'      => 'Order marked as shipped',
            ];
        }

        /* ---------------- DELIVERED ---------------- */
        if ($action === 'delivered') {

            if ($order->order_status !== 'shipped' && $order->delivery_method !== 'go_shop') {
                throw new InvalidArgumentException(
                    'Only shipped orders can be delivered'
                );
            }

            $order->order_status = 'delivered';
            if (is_null($order->delivered_at)) {
                $order->delivered_at = Carbon::now('Asia/Karachi');
            }
            $order->save();
            Mail::to($order->customer->email)->send(new OrderDelivered($order->customer->name, $order->order_number));
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->sold = $item->product->sold + $item->quantity;
                    $item->product->save();
                }
            }
            if ($order->delivery_method == 'cod' || $order->delivery_method == 'go_shop' || $order->delivery_method == 'online') {
                        $notification = Notification::create([
                            'user_type' => 'customers',
                            'title' => "Order Delivered",
                            'description' => "Your order #{$order->order_number} has been successfully delivered. Your receipt has been generated and is now available for download.",
                        ]);
                        NotificationTarget::create([
                            'notification_id' => $notification->id,
                            'targetable_id' => $order->customer->id ?? null,
                            'targetable_type' => 'App\Models\User',
                            'type' => 'order_delivered',

                        ]);
                if (!empty($order->customer->fcm_token)) {
                    NotificationHelper::sendFcmNotification(
                        $order->customer->fcm_token,
                        "Order Delivered",
                        "Your order #{$order->order_number} has been successfully delivered. Your receipt has been generated and is now available for download.",
                        [
                            'type' => 'order_delivered',
                            'order_id' => (string) $order->id,
                        ]
                    );
                }
            }
            return [
                'order_id'     => $order->id,
                'order_status' => 'delivered',
                'delivered_at' => $order->delivered_at->format('d M Y'),
                // 'payment_status'=> $order->payment_status,
                'message'      => 'Order marked as delivered',
            ];
        }

        /* ---------------- MARK PAID ---------------- */
        if ($action === 'mark_paid') {

            if ($order->payment_status === 'paid') {
                throw new InvalidArgumentException(
                    'Order already marked as paid'
                );
            }

            $order->payment_status = 'paid';
            $order->save();

            return [
                'order_id'       => $order->id,
                'order_status'   => $order->order_status,
                'payment_status' => 'paid',
                'message'        => 'Payment marked as paid',
            ];
        }

        throw new InvalidArgumentException('Invalid action');
    });
}




    // public function reOrder(int $orderId, int $customerId)
    // {
    //     return DB::transaction(function () use ($orderId, $customerId) {

    //         // ✅ Fetch delivered order
    //         $order = Order::with('items')
    //             ->where('id', $orderId)
    //             ->where('customer_id', $customerId)
    //             ->where('order_status', 'delivered')
    //             ->first();

    //         if (!$order) {
    //             throw new \Exception('Delivered order not found');
    //         }

    //         // 🧹 Clear existing active cart
    //         MobileCart::where('user_id', $customerId)
    //             ->where('is_ordered', 0)
    //             ->delete();

    //         $addedItems = [];

    //         foreach ($order->items as $item) {

    //             $mobile = VendorMobile::find($item->product_id);

    //             if (!$mobile) {
    //                 continue; // Skip deleted products
    //             }

    //             // 🛑 Stock validation
    //             if ($item->quantity > $mobile->stock) {
    //                 throw new \Exception(
    //                     "{$mobile->model->name} does not have enough stock"
    //                 );
    //             }

    //             // ✅ Add to cart
    //             $cartItem = MobileCart::create([
    //                 'user_id'            => $customerId,
    //                 'mobile_listing_id'  => $mobile->id,
    //                 'quantity'           => $item->quantity,
    //                 'is_ordered'         => 0,
    //             ]);

    //             $addedItems[] = $cartItem;
    //         }

    //         return $addedItems;
    //     });
    // }

    public function reOrder(int $orderId, int $customerId)
    {
        return DB::transaction(function () use ($orderId, $customerId) {

            // 1️⃣ Fetch delivered order with items
            $order = Order::with('items')
                ->where('id', $orderId)
                ->where('customer_id', $customerId)
                ->where('order_status', 'delivered')
                ->first();

            if (!$order) {
                throw new \Exception('Delivered order not found');
            }

            // 2️⃣ Get ORDER vendor (must be single)
            $orderVendorIds = VendorMobile::whereIn(
                'id',
                $order->items->pluck('product_id')
            )->distinct()->pluck('vendor_id');

            // if ($orderVendorIds->count() !== 1) {
            //     throw new \Exception(
            //         'Re-order is only allowed for orders from a single vendor'
            //     );
            // }

            $orderVendorId = $orderVendorIds->first();

            // 3️⃣ Check CART vendor (if cart not empty)
            $cartVendorIds = MobileCart::where('user_id', $customerId)
                ->where('is_ordered', 0)
                ->join(
                    'vendor_mobiles',
                    'vendor_mobiles.id',
                    '=',
                    'mobile_carts.mobile_listing_id'
                )
                ->distinct()
                ->pluck('vendor_mobiles.vendor_id');

            if (
                $cartVendorIds->isNotEmpty() &&
                $cartVendorIds->first() !== $orderVendorId
            ) {
                throw new \Exception(
                    'Clear your cart to add items from a different vendor.'
                );
            }

            // 4️⃣ Add / merge products into cart
            $addedItems = [];

            foreach ($order->items as $item) {

                $mobile = VendorMobile::with('model')
                    ->where('id', $item->product_id)
                    ->where('vendor_id', $orderVendorId)
                    ->first();

                if (!$mobile) {
                    throw new \Exception('One of the products no longer exists');
                }

                // Stock validation
                if ($item->quantity > $mobile->stock) {
                    throw new \Exception(
                        "{$mobile->model->name} does not have enough stock"
                    );
                }

                $cartItem = MobileCart::where([
                    'user_id'           => $customerId,
                    'mobile_listing_id' => $mobile->id,
                    'is_ordered'        => 0,
                ])->first();

                if ($cartItem) {
                    // Combine quantity
                    $cartItem->update([
                        'quantity' => $cartItem->quantity + $item->quantity
                    ]);
                } else {
                    $cartItem = MobileCart::create([
                        'user_id'           => $customerId,
                        'mobile_listing_id' => $mobile->id,
                        'quantity'          => $item->quantity,
                        'is_ordered'        => 0,
                    ]);
                }

                $addedItems[] = $cartItem;
            }

            return $addedItems;
        });
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Models\ShippingAddress;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ShippingAddressRequest;
use App\Repositories\Api\Interfaces\OrderRepositoryInterface;

class OrderController extends Controller
{
    protected $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function index(Request $request)
    {
        try {
            $status = $request->get('status');
            $customerId = auth()->id();

            if (!$customerId) {
                return ResponseHelper::error(null, 'Not Permission', 'unauthorized');
            }
            if (!$status) {
                return ResponseHelper::error(null, 'Status parameter is required', 'error');
            }
            $orders = $this->orderRepository->getOrdersByCustomerAndStatus($customerId, $status);
            if ($orders->isEmpty()) {
                return ResponseHelper::error([], "No orders found for status: {$status}", 'not_found');
            }

            return ResponseHelper::success($orders, 'Orders fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function filterOrders(Request $request)
    {
        try {
            $type = $request->get('type');
            $payment_method = $request->get('payment_method');

            if (!$type) {
                return ResponseHelper::error(null, 'Type parameter is required', 'error');
            }

            if (!$payment_method) {
                return ResponseHelper::error(null, 'Payment method parameter is required', 'error');
            }

            $type = $request->input('type'); // 'today' or 'overall'
            $payment_method = $request->input('payment_method'); // 'cod', 'card', etc.

            $query = Order::with(['items.vendor'])
                ->where('delivery_method', $payment_method);

            // Apply date filter if type is 'today'
            if ($type === 'today') {
                $query->whereDate('created_at', now());
            }

            $orders = $query->latest()->get()->map(function ($order) {
                $items = $order->items;

                $hasCancelRequest = $order->cancelOrder()->exists();

                return [
                    'id' => $order->id,
                    'order_id'       => '#' . $order->order_number,
                    'payment_method' => $order->delivery_method,
                    'customer_id'    => $order->customer_id,
                    'shop_name'      => $items->first()?->vendor?->name, // name of first vendor
                    'total_price'    => $items->sum(fn ($item) => $item->price * $item->quantity),
                    'total_products' => $items->sum('quantity'),
                    'date'           => Carbon::parse($order->created_at)->format('F d, Y'),
                    'order_status'   => $order->order_status,
                    'time' => $order->created_at->format('h:i A'),
                    'has_cancel_request' => $hasCancelRequest,
                ];
            });

            if ($orders->isEmpty()) {
                return ResponseHelper::error([], "No orders found for type: {$type} and payment method: {$payment_method}", 'not_found');
            }

            return ResponseHelper::success($orders, 'Filtered orders fetched successfully', 'success');

        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function vendorOrders(Request $request)
    {
        try {
            $status = $request->get('status');
            $vendorId = auth()->id();

            if (!$vendorId) {
                return ResponseHelper::error(null, 'Not Permission', 'unauthorized');
            }
            if (!$status) {
                return ResponseHelper::error(null, 'Status parameter is required', 'error');
            }

            $orders = $this->orderRepository->getOrdersByVendorAndStatus($vendorId, $status);

            if ($orders->isEmpty()) {
                return ResponseHelper::error([], "No orders found for status: {$status}", 'not_found');
            }

            return ResponseHelper::success($orders, 'Vendor orders fetched successfully', 'success');

        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }


    public function getOrderStatistics(Request $request)
    {
        try {
            $vendorId = auth()->id();

            $stats = $this->orderRepository->getOrderStatistics($vendorId);

            return ResponseHelper::success($stats, 'Vendor order statistics fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }


    public function track($id)
    {
        try {
            $customerId = auth()->id();
            if (!$customerId) {
                return ResponseHelper::error(null, 'Not Permission', 'unauthorized');
            }

            $order = $this->orderRepository->getOrderByIdAndCustomer($id, $customerId);

            return ResponseHelper::success([
                'order_id' => $order->id,
                'status'   => $order->order_status,
                'created_at' => $order->order_status === 'inprogress'
                ? optional($order->created_at)->format('d M Y')
                : null,
            ], 'Order status fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function trackVendor($id)
    {
        try {
            $vendorId = auth()->id();
            if (!$vendorId) {
                return ResponseHelper::error(null, 'Unauthorized', 'unauthorized');
            }

            $order = $this->orderRepository->getOrderByIdAndVendor($id, $vendorId);

            return ResponseHelper::success([
                'order_id' => $order->id,
                'status'   => $order->order_status,
                'created_at' => $order->order_status === 'inprogress'
                ? optional($order->created_at)->format('d M Y')
                : null,
            ], 'Order status fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function shippingAddress(ShippingAddressRequest $request)
    {
        try {
            $data = $request->validated();
            $data['customer_id'] = Auth::id();
            $shippingAddress = ShippingAddress::create($data);
            return ResponseHelper::success($shippingAddress, 'Shipping address saved successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 500);
        }
    }

    public function getShippingAddress()
    {
        try {
            $customerId = auth()->id();
            if (!$customerId) {
                return ResponseHelper::error(null, 'Unauthorized', 'unauthorized');
            }
            $address = ShippingAddress::where('customer_id', $customerId)->latest()->get();
            if (!$address) {
                return ResponseHelper::error(null, 'No shipping address found', 'not_found');
            }
            return ResponseHelper::success($address, 'Shipping address fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function deleteShippingAddress($id)
    {
        try {
            $customerId = auth()->id();
            if (!$customerId) {
                return ResponseHelper::error(null, 'Unauthorized', 'unauthorized');
            }
            $address = ShippingAddress::where('id', $id)->where('customer_id', $customerId)->first();
            if (!$address) {
                return ResponseHelper::error(null, 'Shipping address not found', 'not_found');
            }
            $address->delete();
            return ResponseHelper::success(null, 'Shipping address deleted successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function salesReport(Request $request)
    {
        try {
            $vendorId = auth()->id();
            if (!$vendorId) {
                return ResponseHelper::error(null, 'Unauthorized', 'unauthorized');
            }
            $type = $request->get('type', 'overall');
            $report = $this->orderRepository->getSalesReport($vendorId, $type);

            return ResponseHelper::success($report, 'Sales report fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }


    public function show($id)
    {
        try {
            $customerId = auth()->id();
            if (!$customerId) {
                return ResponseHelper::error(null, 'Unauthorized', 'unauthorized');
            }
            $order = $this->orderRepository->getOrderByIdAndCustomer($id, $customerId);
            if (!$order) {
                return ResponseHelper::error(null, 'Order not found', 'not_found');
            }
            return ResponseHelper::success($order, 'Order details fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function customerorderlist($orderId)
    {
        try {
            $customerId = Auth::id();
            $orders = $this->orderRepository->customerorderlist($orderId);
            return ResponseHelper::success($orders, 'Orders list fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function getorderlist()
    {
        try {
            $orders = $this->orderRepository->getorderlist(Auth::id());
            return ResponseHelper::success($orders, 'Orders list fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function vendorOrderList($orderId)
    {
        try {
            $vendorId = Auth::id();

            if (!$vendorId) {
                return ResponseHelper::error(null, 'Unauthorized', 'unauthorized');
            }

            $order = $this->orderRepository->getVendorOrderDetails($vendorId, $orderId);

            return ResponseHelper::success($order, 'Order details fetched successfully', 'success');

        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'error');
        }
    }

    public function deviceReceipt(Request $request, $orderId)
    {
        try {
            $devices = $request->input('devices', []);
            // dd($devices);

            $receipts = $this->orderRepository->createDeviceReceipts($orderId, $devices);

            return ResponseHelper::success($receipts, 'Device receipt created successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function getReceipt($deviceReceiptId)
    {
        try {
            $receipt = $this->orderRepository->getReceiptById($deviceReceiptId);

            return ResponseHelper::success($receipt, 'Receipt fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function getOrderBrand($orderId)
    {
        try {
            $receipts = $this->orderRepository->getBrandByOrderId($orderId);

            return ResponseHelper::success($receipts, 'Brands fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function getOrderBrandModel($orderId, $brandId)
    {
        try {
            $receipts = $this->orderRepository->getBrandModelByOrderId($orderId, $brandId);

            return ResponseHelper::success($receipts, 'Models fetched successfully', 'success');
        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function updateOrderStatusByVendor(Request $request, int $id)
    {
        try {
            $request->validate([
                'action' => 'required|in:cancel,mark_paid,shipped,delivered',
            ]);

            $vendorId = Auth::id();
            if (!$vendorId) {
                return ResponseHelper::error(null, 'Unauthorized', 'unauthorized');
            }

            $order = $this->orderRepository->updateOrderStatusByVendor(
                $vendorId,
                $id,
                $request->action,
                $request->order_item_id ?? null,
                $request->reason ?? null
            );


            return ResponseHelper::success(
                $order, 'Order updated successfully','success'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::error(null, 'Order not found', 'not_found');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'unauthorized');

        } catch (\InvalidArgumentException $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'error');

        } catch (\Exception $e) {
            return ResponseHelper::error(null, $e->getMessage(), 'server_error');
        }
    }

    public function reOrder($orderId)
    {
        try {
            $customerId = Auth::id();

            if (!$customerId) {
                return ResponseHelper::error(null, 'Unauthorized', 'unauthorized');
            }

            $cartItems = $this->orderRepository->reOrder($orderId, $customerId);

            return ResponseHelper::success(
                $cartItems,
                'Order items added to cart successfully',
                'success'
            );

        } catch (\Exception $e) {
            return ResponseHelper::error(
                null,
                $e->getMessage(),
                'server_error'
            );
        }
    }
}

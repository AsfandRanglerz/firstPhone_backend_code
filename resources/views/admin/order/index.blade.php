@extends('admin.layout.app')
@section('title', 'Orders')

@section('content')
<style>
    .paid-unpaid-btn{
        padding: 0.4rem 1rem;
        
    }
    .paid-unpaid-btn:hover,
.paid-unpaid-btn:focus,
.paid-unpaid-btn:active {
    background-color: unset !important;
    color: #fff !important;
    box-shadow: none !important;
}
button.bg-success:hover{
    background-color: #54ca68 !important;
}
button.bg-warning:hover{
    background-color:#ffa426 !important;
}
</style>
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Orders</h4>
                                {{-- Totals Row Inside Card Header but Below Title --}}
                                <div class="row mt-3 w-100">
                                    <div class="col-md-3">
                                        <div class="card shadow border-0 text-white mb-0">
                                            <div class="card-body py-2">
                                                <h6 class="mb-1">Total Payment</h6>
                                                <h6 class="mb-0 fw-bold" id="totalAmount">Rs {{ number_format($total) }}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card shadow border-0 text-white mb-0">
                                            <div class="card-body py-2">
                                                <h6 class="mb-1">Total COD</h6>
                                                <h6 class="mb-0 fw-bold" id="codAmount">Rs {{ number_format($codTotal) }}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card shadow border-0 text-white mb-0">
                                            <div class="card-body py-2">
                                                <h6 class="mb-1">Total Online</h6>
                                                <h6 class="mb-0 fw-bold" id="onlineAmount">Rs
                                                    {{ number_format($onlineTotal) }}</h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card shadow border-0 text-white mb-0">
                                            <div class="card-body py-2">
                                                <h6 class="mb-1">Total GoShop</h6>
                                                <h6 class="mb-0 fw-bold" id="pickupAmount">Rs
                                                    {{ number_format($pickupTotal) }}</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                <table class="table responsive" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Order ID</th>
                                            <th>Date & Time</th>
                                            <th>Customer</th>
                                            {{-- <th>Shipping Address</th> --}}
                                            <th>Buy From</th>
                                            <th>Product</th>
                                            <th>Total Price (PKR)</th>
                                            <th>Shipping Charges (PKR)</th>
                                            <th>Payment Status</th>
                                            <th>Delivery Method</th>
                                            <th>Order Status</th>
                                            {{-- <th>Actions</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $index => $order)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ '#'.$order->order_number }}</td>
                                                <td>{{ $order->created_at->format('d M Y, h:i A') }}</td>
                                                <td>
                                                    {{ $order->customer->name ?? 'N/A' }}<br>
                                                    <small><a
                                                            href="mailto:{{ $order->customer->email }}">{{ $order->customer->email }}</a></small><br>
                                                    <small><a
                                                            href="tel:{{ $order->customer->phone }}">{{ $order->customer->phone }}</a></small>
                                                </td>
                                                {{-- <td>
                                                    {{ $order->shipping_address ?? 'N/A' }}
                                                </td> --}}
                                                <td>
                                                    {{ $order->items->first()->vendor->name ?? 'No Vendor' }}<br>

                                                    <small>
                                                        <a href="mailto:{{ $order->items->first()->vendor->email ?? '' }}">
                                                            {{ $order->items->first()->vendor->email ?? '' }}
                                                        </a>
                                                    </small><br>

                                                    <small>
                                                        <a href="tel:{{ $order->items->first()->vendor->phone ?? '' }}">
                                                            {{ $order->items->first()->vendor->phone ?? '' }}
                                                        </a>
                                                    </small>
                                                </td>
                                                <td>
                                                    @foreach ($order->items as $item)
                                                        {{ $item->product->brand->name ?? 'No Brand' }}
                                                        {{ $item->product->model->name ?? 'No Model' }}<br> (Qty:
                                                        {{ $item->quantity }}, Price:
                                                        {{ number_format($item->price, 2) }})
                                                        <br>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    {{ number_format($order->items->sum(function($item){
                                                        return $item->price * $item->quantity;
                                                    })) }}
                                                </td>
                                                <td>
                                                    @if($order->shipping_charges)
                                                    {{ number_format($order->shipping_charges) }}</td>
                                                    @else
                                                    <span class="text-muted">No Shipping Charges</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $paymentColors = [
                                                            'paid' => 'btn-success',
                                                            'unpaid' => 'btn-warning',
                                                            // 'unpaid' => 'btn-secondary',
                                                        ];
                                                    @endphp

                                                        <button
                                                            class="badge border-0 paid-unpaid-btn {{ str_replace('btn', 'bg', $paymentColors[$order->payment_status] ?? 'btn-light') }}" >
                                                            {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}
                                                        </button>
                                                </td>

                                                <td>
                                                    @php
                                                        $deliveryLabels = [
                                                            'cod' => 'COD',
                                                            'online' => 'Online',
                                                            'go_shop' => 'GoShop',
                                                        ];

                                                        $deliveryClassMap = [
                                                            'cod' => 'bg-warning',
                                                            'online' => 'bg-primary',
                                                            'go_shop' => 'bg-info',
                                                        ];

                                                        // ensure variable exists for current order
                                                        $deliveryClass =
                                                            $deliveryClassMap[$order->delivery_method] ??
                                                            'bg-secondary';
                                                    @endphp

                                                    <span class="badge {{ $deliveryClass }}">
                                                        {{ $deliveryLabels[$order->delivery_method] ?? ucwords(str_replace('_', ' ', $order->delivery_method)) }}
                                                    </span>
                                                </td>


                                                @php
                                                    // Order Status Colors (new statuses only)
                                                    $statusColors = [
                                                        'inprogress' => 'btn-warning',
                                                        'shipped' => 'btn-secondary',
                                                        'delivered' => 'btn-primary',
                                                        'cancelled' => 'btn-danger',
                                                    ];
                                                @endphp

                                                <td>
                                                   <span class="badge {{ str_replace('btn', 'bg', $statusColors[$order->order_status] ?? 'btn-light') }}">
                                                        {{ ucfirst(str_replace('_', ' ', $order->order_status)) }}
                                                    </span>
                                                </td>

                                                {{-- <td>
                                                    @if (Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('Orders') && $sideMenuPermissions['Orders']->contains('delete')))
                                                        <form id="delete-form-{{ $order->id }}"
                                                            action="{{ route('order.destroy', $order->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>

                                                        <button class="show_confirm btn" style="background-color: #009245;"
                                                            data-form="delete-form-{{ $order->id }}" type="button">
                                                            <span><i class="fa fa-trash"></i></span>
                                                        </button>
                                                    @endif
                                                </td> --}}
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

@endsection

@section('js')
    <script>
        $(document).ready(function() {

            // ===== DataTable Initialization =====
            if ($.fn.DataTable.isDataTable('#table_id_events')) {
                $('#table_id_events').DataTable().destroy();
            }
            $('#table_id_events').DataTable();

            // ===== SweetAlert2 Delete Confirmation =====
            $(document).on('click', '.show_confirm', function(event) {
                event.preventDefault();
                var formId = $(this).data("form");
                var form = document.getElementById(formId);

                swal({
                    title: "Are you sure you want to delete this record?",
                    text: "If you delete this, it will be gone forever.",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        form.submit();
                    }
                });
            });

            // ===== Payment Status Change via AJAX =====
            $('.change-payment-status').on('click', function() {
                let orderId = $(this).data('order-id');
                let newStatus = $(this).data('new-status');

                $.ajax({
                    url: "{{ route('order.updatePaymentStatus', ':id') }}".replace(':id', orderId),
                    type: 'POST',
                    data: {
                        payment_status: newStatus,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if (data.success) {
                            const statusText = newStatus.charAt(0).toUpperCase() + newStatus
                                .slice(1);
                            const button = $(`#paymentBtn-${orderId}`);

                            const colorClasses = {
                                'paid': 'btn-primary',
                                'unpaid': 'btn-warning',
                            };

                            button.text(statusText)
                                .removeClass()
                                .addClass(
                                    `btn btn-sm dropdown-toggle ${colorClasses[newStatus]}`);

                            toastr.success(data.message);
                        } else {
                            toastr.error('Something went wrong');
                        }
                    },
                    error: function() {
                        toastr.error('Failed to update payment status');
                    }
                });
            });

            // ===== Order Status Change via AJAX =====
            $(document).on('click', '.change-order-status', function () {

                let orderId = $(this).data('order-id');
                let newStatus = $(this).data('new-status');

                $.ajax({
                    url: "{{ route('order.updateStatus', ':id') }}".replace(':id', orderId),
                    type: 'POST',
                    data: {
                        order_status: newStatus,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if (data.success) {
                            const statusText = newStatus.replace(/_/g, ' ').replace(/\b\w/g,
                                c => c.toUpperCase());
                            const button = $(`#statusBtn-${orderId}`);

                            const colorClasses = {
                                'inprogress': 'btn-warning',
                                'shipped': 'btn-secondary',
                                'delivered': 'btn-primary',
                                'cancelled': 'btn-danger',
                            };

                            button.text(statusText)
                                .removeClass()
                                .addClass(
                                    `btn btn-sm dropdown-toggle ${colorClasses[newStatus]}`);

                            toastr.success(data.message);
                            setTimeout(function () {
                                location.reload();
                            }, 1200);
                        } else {
                            toastr.error('Something went wrong');
                        }
                    },
                    error: function() {
                        toastr.error('Failed to update status');
                    }
                });
            });


        });

        function fetchTotals() {
            fetch("{{ route('orders.totals') }}")
                .then(response => response.json())
                .then(data => {
                    document.getElementById('totalAmount').innerText = "Rs " + data.total.toLocaleString();
                    document.getElementById('codAmount').innerText = "Rs " + data.codTotal.toLocaleString();
                    document.getElementById('onlineAmount').innerText = "Rs " + data.onlineTotal.toLocaleString();
                    document.getElementById('pickupAmount').innerText = "Rs " + data.pickupTotal.toLocaleString();
                })
                .catch(error => console.error("Error fetching totals:", error));
        }

        // Har 10 second baad refresh karne ke liye
        setInterval(fetchTotals, 10000);

        // Page load pe bhi call karo
        fetchTotals();
    </script>

@endsection

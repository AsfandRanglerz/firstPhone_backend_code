@extends('admin.layout.app')
@section('title', 'Cancel Orders')

@section('content')
<style>
    .btn.btn-success {
    padding: 0.4rem 0.9rem !important;
    font-size: 12px !important;
}
</style>
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Cancel Orders</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                <table class="table responsive" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Date & Time</th>
                                            <th>Order ID</th>
                                            <th>Order Item</th>
                                            <th>Vendor</th>
                                            <th>Reason</th>
                                            <th>Delivery Method</th>
                                            <th>Proof</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($cancelOrders as $index => $cancelOrder)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $cancelOrder->created_at->timezone('Asia/Karachi')->format('d M Y, h:i A') }}</td>
                                                <td>#{{ $cancelOrder->order->order_number ?? '-' }}</td>
                                                <td>
                                                    {{ $cancelOrder->orderItem->product->brand->name ?? '-' }} -
                                                    {{ $cancelOrder->orderItem->product->model->name ?? '-' }}
                                                </td>
                                                <td>{{ $cancelOrder->orderItem->vendor->name ?? '-' }}</td>
                                                <td>{{ $cancelOrder->reason }}</td>
                                                <td>
                                                    @if ($cancelOrder->order->delivery_method == 'cod')
                                                        <span class="badge bg-warning">COD</span>
                                                    @elseif ($cancelOrder->order->delivery_method == 'online')
                                                        <span class="badge bg-primary">Online</span>
                                                    @elseif ($cancelOrder->order->delivery_method == 'pickup')
                                                        <span class="badge bg-info">GoShop</span>
                                                    @else
                                                        <span
                                                            class="badge badge-secondary">{{ ucfirst($cancelOrder->order->delivery_method) }}</span>
                                                    @endif
                                                </td>
                                                 <td>
                                                    @if ($cancelOrder->proof_file_image)
                                                        <button class="btn btn-sm btn-info view-proof"
                                                            data-front="{{ asset('public/'.$cancelOrder->proof_file_image) }}" title="View Proof">
                                                            <span class="btn-text">View</span>
                                                        </button>
                                                    @else
                                                        <span class="text-muted">No Proof</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $statusColors = [
                                                            'requested' => 'btn-warning',
                                                            'approved' => 'btn-success',
                                                            'rejected' => 'btn-danger',
                                                        ];
                                                    @endphp

                                                    {{-- If status is approved, show badge only --}}
                                                    @if ($cancelOrder->status === 'approved')

                                                        <span class="btn btn-success btn-lg" disabled>
                                                            Approved
                                                        </span>

                                                    @else

                                                        <div class="dropdown">
                                                            <button
                                                                class="btn btn-sm dropdown-toggle {{ $statusColors[$cancelOrder->status] ?? 'btn-light' }}"
                                                                type="button"
                                                                data-toggle="dropdown">
                                                                {{ ucfirst($cancelOrder->status) }}
                                                            </button>

                                                            <div class="dropdown-menu">

                                                                {{-- If status = requested → show both options --}}
                                                                @if ($cancelOrder->status === 'requested')

                                                                    <button type="button"
                                                                        class="dropdown-item change-cancel-status"
                                                                        data-id="{{ $cancelOrder->id }}"
                                                                        data-new-status="approved">
                                                                        Approved
                                                                    </button>

                                                                    <button type="button"
                                                                        class="dropdown-item change-cancel-status"
                                                                        data-id="{{ $cancelOrder->id }}"
                                                                        data-new-status="rejected">
                                                                        Rejected
                                                                    </button>

                                                                {{-- If status = rejected → show only approved --}}
                                                                @elseif ($cancelOrder->status === 'rejected')

                                                                    <button type="button"
                                                                        class="dropdown-item change-cancel-status"
                                                                        data-id="{{ $cancelOrder->id }}"
                                                                        data-new-status="approved">
                                                                        Approved
                                                                    </button>

                                                                @endif

                                                            </div>
                                                        </div>

                                                    @endif
                                                </td>

                                                <td>
                                                    <form id="delete-form-{{ $cancelOrder->id }}"
                                                        action="{{ route('cancel-orders.destroy', $cancelOrder->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>

                                                    <button class="show_confirm btn" style="background-color: #009245;"
                                                        data-form="delete-form-{{ $cancelOrder->id }}" type="button">
                                                        <span><i class="fa fa-trash"></i></span>
                                                    </button>
                                                </td>
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

    {{-- Modal for Approve Proof Upload --}}
    <div class="modal fade" id="approveFileModal" tabindex="-1" role="dialog" aria-labelledby="approveFileModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="approveFileForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="cancel_order_id" id="cancel_order_id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveFileModalLabel">Upload Transaction Proof</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="file" name="proof_file_image" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" id="approveFileSubmitBtn" class="btn btn-success">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="proofModalLabel">Proof</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="proofImage" src="" class="img-fluid" alt="Proof Image">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#table_id_events')) {
                $('#table_id_events').DataTable().destroy();
            }
            $('#table_id_events').DataTable();

            // Delete confirmation
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

            // Cancel Order Status Change
            $(document).on('click', '.change-cancel-status', function() {
                let id = $(this).data('id');
                let newStatus = $(this).data('new-status');

                if (newStatus === 'approved') {
                    $.ajax({
                        url: "{{ route('cancel-orders.checkDeliveryStatus', ':id') }}".replace(
                            ':id', id),
                        type: 'GET',
                        success: function(res) {
                            if (res.delivery_method === 'online') {
                                $('#cancel_order_id').val(id);
                                $('#approveFileModal').modal('show');
                            } else if (res.delivery_method === 'approved_direct') {
                                toastr.success("Cancel order approved successfully");
                                location.reload();
                            }
                        },
                        error: function() {
                            toastr.error("Failed to check delivery status");
                        }
                    });
                } else {
                    updateCancelStatus(id, newStatus);
                }
            });

            // Approve file form submit
           $('#approveFileForm').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let id = $('#cancel_order_id').val();

                // Grab the submit button
                let submitBtn = $('#approveFileSubmitBtn');

                // Save original text
                let originalText = submitBtn.html();

                // Show spinner and disable button
                submitBtn.prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Please Wait ...
                `);

                // ✅ ADD THIS LINE
                formData.append('status', 'approved');

                $.ajax({
                    url: "{{ route('cancel-orders.updateStatus', ':id') }}".replace(':id', id),
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        if (data.success) {
                            $('#approveFileModal').modal('hide');
                            toastr.success("Request Approved & Transaction Proof Uploaded");
                            location.reload();
                        } else {
                            toastr.error("Something went wrong");
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function() {
                        toastr.error("Failed to approve cancel order");
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Helper function for status update without file
            function updateCancelStatus(id, newStatus) {
                $.ajax({
                    url: "{{ route('cancel-orders.updateStatus', ':id') }}".replace(':id', id),
                    type: 'POST',
                    data: {
                        status: newStatus,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if (data.success) {
                            if (newStatus === 'rejected')
                            {
                                toastr.success('Cancel order rejected successfully');
                            }
                            location.reload();
                        } else {
                            toastr.error('Something went wrong');
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        toastr.error('Failed to update status');
                    }
                });
            }

        });

        // ===== View Proof =====
        $(document).on('click', '.view-proof', function() {
            const front = $(this).data('front');
            $('#proofImage').attr('src', front).show();
            $('#proofModal').modal('show');
        });
    </script>
@endsection

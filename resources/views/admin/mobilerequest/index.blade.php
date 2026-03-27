@extends('admin.layout.app')
@section('title', 'Mobile Requests')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Mobile Requests</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                {{-- @if (Auth::guard('admin')->check() || ($sideMenuPermissions->has('MobileListing') && $sideMenuPermissions['MobileListing']->contains('create')))
                                    <a class="btn btn-primary mb-3 text-white"
                                        href="{{ url('/admin/vendor-create') }}">Create</a>
                                @endif --}}

                                <table class="table responsive" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Date & Time</th>
                                            <th>Customer Name</th>
                                            <th>Location</th>
                                            <th>Brand</th>
                                            <th>Model</th>
                                            <th>Min Price (PKR)</th>
                                            <th>Max Price (PKR)</th>
                                            <th>RAM</th>
                                            <th>ROM</th>
                                            <th>Color</th>
                                            <th>Condition</th>
                                            <th>Description</th>
                                            <th>Vendors</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    {{-- <tbody>
                                        @foreach ($mobilerequests as $mobilerequest)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $mobilerequest->created_at->format('d M Y, h:i A') }}</td>
                                                <td>
                                                    {{ $mobilerequest->customer->name ?? '' }} <br>
                                                    <a href="mailto:{{ $mobilerequest->customer->email ?? '' }}" class="mail-to">
                                                        {{ $mobilerequest->customer->email ?? '' }}
                                                    </a> <br>
                                                    <a href="tel:{{ $mobilerequest->customer->phone ?? '' }}" class="tel">
                                                        {{ $mobilerequest->customer->phone ?? '' }}
                                                    </a>
                                                </td>
                                                <td>{{ $mobilerequest->location }}</td>
                                                <td>{{ $mobilerequest->brand->name ?? 'N/A' }}</td>
                                                <td>{{ $mobilerequest->model->name  ?? 'N/A' }}</td>
                                                <td>
                                                    @if ($mobilerequest->min_price)
                                                        {{ number_format($mobilerequest->min_price, 0) }}
                                                    @else
                                                        <span class="text-muted">No Price</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($mobilerequest->max_price)
                                                        {{ number_format($mobilerequest->max_price, 0) }}
                                                    @else
                                                        <span class="text-muted">No Price</span>
                                                    @endif
                                                </td>
                                                <td>{{ $mobilerequest->ram }}</td>
                                                <td>{{ $mobilerequest->storage }}</td>
                                                <td>{{ $mobilerequest->color }}</td>
                                                <td>{{ $mobilerequest->condition }}</td>
                                                <td>
                                                    @if ($mobilerequest->description)
                                                        {{ Str::limit($mobilerequest->description, 50) }}
                                                    @else
                                                        <span class="text-muted">No Description</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a class="btn btn-primary ml-1"
                                                        href="
                                                    {{ route('mobilerequest.show', $mobilerequest->id) }}
                                                     ">View</a>
                                                </td>
                                                <td>

                                                    @if ($mobilerequest->status == 0)
                                                        <div class="badge badge-success badge-shadow">Seen</div>
                                                    @elseif($mobilerequest->status == 2)
                                                        <div class="badge badge-warning badge-shadow">UnSeen</div>
                                                    @endif
                                                </td>

                                                <td>
                                                    <div class="d-flex gap-1">
                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Mobile Requests') && $sideMenuPermissions['Mobile Requests']->contains('delete')))
                                                            <form id="delete-form-{{ $mobilerequest->id }}"
                                                                action="{{ route('mobilerequest.delete', $mobilerequest->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')

                                                                <button class="show_confirm btn d-flex gap-1"
                                                                    style="background-color: #009245;"
                                                                    data-form="delete-form-{{ $mobilerequest->id }}"
                                                                    type="button">
                                                                    <span><i class="fa fa-trash"></i></span>
                                                                </button>
                                                            </form>
                                                            @endif
                                                            @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Mobile Requests') && $sideMenuPermissions['Mobile Requests']->contains('mark as read')))
                                                            @if ($mobilerequest->status == 2)
                                                                <form
                                                                    action="{{ route('mobilerequest.markAsRead', $mobilerequest->id) }}"
                                                                    method="POST" style="display:inline;">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit"
                                                                        class="btn btn-warning d-flex gap-1">
                                                                        <span><i class="fa fa-eye"></i></span> Mark as Read
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody> --}}
                                </table>
                            </div> <!-- /.card-body -->
                        </div> <!-- /.card -->
                    </div> <!-- /.col -->
                </div> <!-- /.row -->
            </div> <!-- /.section-body -->
        </section>
    </div>



@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            if ($.fn.DataTable.isDataTable('#table_id_events')) {
                $('#table_id_events').DataTable().destroy();
            }
            $('#table_id_events').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('mobilerequest.data') }}",

                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'customer', name: 'customer' },
                    { data: 'location', name: 'location' },
                    { data: 'brand', name: 'brand.name' },
                    { data: 'model', name: 'model.name' },
                    { data: 'min_price', name: 'min_price' },
                    { data: 'max_price', name: 'max_price' },
                    { data: 'ram', name: 'ram' },
                    { data: 'storage', name: 'storage' },
                    { data: 'color', name: 'color' },
                    { data: 'condition', name: 'condition' },
                    { data: 'description', name: 'description' },
                    { data: 'vendors', orderable: false, searchable: false },
                    { data: 'status', name: 'status' },
                    { data: 'actions', orderable: false, searchable: false }
                ],

                pageLength: 10
            });

            // SweetAlert2 delete confirmation
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
        });
    </script>
@endsection

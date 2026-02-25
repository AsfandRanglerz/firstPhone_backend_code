@extends('admin.layout.app')
@section('title', 'index')
@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="col-12">
                                    <h4>Products</h4>
                                </div>
                            </div>
                            <div class="card-body table-responsive">
                                <a class="btn btn-success mb-3" href="{{ route('products-create') }}">Add Product</a>
                                <table class="responsive table table-bordered table-striped" id="table-1">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Store Manager</th>
                                            <th>Store Names</th>
                                            <th>Product Names</th>
                                            <th>UPC / IPC</th>
                                            <th>Retail Price ($)</th>
                                            <th>Product Images</th>
                                            {{-- <th>Flavors / Variants</th> --}}
                                            <th>Wholesalers</th>
                                            <th>Departments</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($products as $product)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    {{ $product->storeManager->first_name ?? 'N/A' }}
                                                    {{ $product->storeManager->last_name ?? '' }}</td>
                                                <td>{{ $product->store->store_name ?? '' }}</td>
                                                <td>{{ $product->product_name }}</td>
                                                <td>{{ $product->upc_ipc ?? '' }}</td>
                                                <td>{{ $product->price }}</td>
                                                <td>
                                                    <a class="btn btn-success"
                                                        href="{{ route('ProductsImages', $product->id) }}">Preview</a>
                                                </td>
                                                {{-- <td>
                                                    <a class="btn btn-info"
                                                        href="{{ route('products-flavours', $product->id) }}">View</a>
                                                </td> --}}
                                                <td>
                                                    <a class="btn btn-info"
                                                        href="{{ route('products-assignVendor', ['productId' => $product->id, 'storeManagerId' => $product->store_manager_id ?? '', 'storeId' => $product->store_id ?? '']) }}">Assign</a>
                                                </td>
                                                <td>
                                                    <a class="btn btn-success" class="btn btn-success"
                                                        href="{{ route('products-departments', ['storeManagerId' => $product->store_manager_id ?? '', 'storeId' => $product->store_id ?? '', 'productId' => $product->id]) }}">Assign</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-4">
                                                        <a href="{{ route('products-edit', ['storeId' => $product->store->id, 'id' => $product->id]) }}"
                                                            class="btn btn-primary">Edit</a>
                                                        <form action="{{ route('products-delete', $product->id) }}"
                                                            method="POST" style="display:inline-block; margin-left: 10px">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn  btn-danger btn-flat show_confirm"
                                                                data-toggle="tooltip">Delete</button>
                                                        </form>
                                                    </div>
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
@endsection

@section('js')
    @if (\Illuminate\Support\Facades\Session::has('message'))
        <script>
            toastr.success('{{ \Illuminate\Support\Facades\Session::get('message') }}');
        </script>
    @endif
    <script>
        $(document).ready(function() {
            $('#table-1').DataTable()

        })
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.0/sweetalert.min.js"></script>
    <script type="text/javascript">
        $('.show_confirm').click(function(event) {
            var form = $(this).closest("form");
            var name = $(this).data("name");
            event.preventDefault();
            swal({
                    title: `Are you sure you want to delete this record?`,
                    text: "If you delete this, it will be gone forever.",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                .then((willDelete) => {
                    if (willDelete) {
                        form.submit();
                    }
                });
        });
    </script>
@endsection

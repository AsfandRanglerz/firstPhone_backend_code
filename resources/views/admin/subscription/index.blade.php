@extends('admin.layout.app')
@section('title', 'Subscription Plans')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                    <h4>Subscription Plans</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                <div class="clearfix">
                                    {{-- <div class="create-btn">
                                        @if (Auth::guard('admin')->check() ||
                                                ($sideMenuPermissions->has('Subscription Plan') &&
                                                    $sideMenuPermissions['Subscription Plan']->contains('create')))
                                            <a class="btn btn-primary mb-3 text-white"
                                                href="{{ route('subscription.create') }}">Create</a>
                                        @endif
                                    </div> --}}
                                </div>

                                <table class="table responsive" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Duration Days</th>
                                            <th>Product Limit</th>
                                            <th>Status</th>
                                            <th>Description</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sortable-faqs">
                                        @foreach ($subscriptionPlans as $key => $plan)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $plan->name }}</td>
                                                <td>{{ (int) $plan->price }}</td>
                                                <td>{{ $plan->duration_days }}</td>
                                                <td>{{ $plan->product_limit }}</td>
                                                <td>
                                                    @if ($plan->is_active)
                                                        <span class="badge badge-success">Active</span>
                                                    @else
                                                        <span class="badge badge-danger">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>{{ $plan->description }}</td>
                                                <td>
                                                    @if (Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('Subscription Plans') && $sideMenuPermissions['Subscription Plans']->contains('edit')))
                                                        <a href="{{ route('subscription.edit', $plan->id) }}"
                                                            class="btn btn-primary btn-action mr-1" data-toggle="tooltip"
                                                            title="Edit"><i class="fa fa-edit"></i></a>
                                                    @endif
                                                    {{-- @if (Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('Subscription Plan') &&
                                                                $sideMenuPermissions['Subscription Plan']->contains('delete')))
                                                        <form action="{{ route('subscription.delete', $plan->id) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-primary btn-action show_confirm"
                                                                data-toggle="tooltip" title='Delete'><i
                                                                    class="fas fa-trash"></i></button>
                                                        </form>
                                                    @endif --}}
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
    <script>
        $(document).ready(function() {

            $('#table_id_events').DataTable({});

            // SweetAlert2 delete confirmation
            $('.show_confirm').click(function(event) {
                event.preventDefault();
                let form = $(this).closest("form");

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

@extends('admin.layout.app')
@section('title', 'Roles')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Roles</h4>
                            </div>
                                <div class="card-body table-striped table-bordered table-responsive">
                                    @if (Auth::guard('admin')->check() ||
                                            ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['Roles']->contains('create')))
                                        <a class="btn btn-primary mb-3 text-white"
                                            href="{{ url('admin/roles-create') }}">Create</a>
                                    @endif

                                    <table class="table responsive" id="table_id_events">
                                        <thead>
                                            <tr>
                                                <th>Sr.</th>
                                                <th>Name</th>
                                                {{-- <th>Status</th> --}}
                                                <th>Permissions</th>
                                                {{-- <th>Action</th> --}}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($roles as $role)
                                                    @php
                                                    $roleUsed = in_array($role->id, $rolesUsed);
                                                    @endphp
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        {{ $role->name }}
                                                    </td>
                                                    {{-- <td>
                                                        <button class="btn btn-success">Active</button>
                                                    </td> --}}
                                                    <td>
                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['Roles']->contains('view')))
                                                            <a class="btn" style="background-color: #009245;"
                                                                href="{{ route('role.permissions', $role->id) }}"><i
                                                                    class="fa fa-eye"></i></a>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        
                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['Roles']->contains('delete')))
                                                            <!-- Delete Form -->
                                                            <form id="delete-form-{{ $role->id }}"
                                                                action="{{ route('delete.role', $role->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE') <!-- Spoof DELETE method -->
                                                            </form>

                                                            {{-- @if(!$roleUsed)
                                                            <!-- Delete Button -->
                                                            <button class="show_confirm btn d-flex gap-4"
                                                                style="background-color: #009245;"
                                                                data-form="delete-form-{{ $role->id }}" type="button">
                                                                <span><i class="fa fa-trash"></i></span>

                                                            </button>
                                                            @else

                                                            <button class="btn"
                                                            style="background-color: #009245;"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#canNotDeleteRoleModal">
                                                            <i class="fa fa-trash"></i>
                                                            </button>

                                                            @endif --}}

                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div> <!-- /.card-body -->
                            </div> <!-- /.card -->
                        </div> <!-- /.col -->
                </div> <!-- /.row -->
            </div> <!-- /.section-body -->
        </section>
    </div>
    <div class="modal fade" id="canNotDeleteRoleModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-body">
{{ __('You cannot delete this role because one or more subadmins are assigned to it.') }}
</div>

<div class="modal-footer">
<button type="button" class="btn btn-danger" data-bs-dismiss="modal">
Close
</button>
</div>

</div>
</div>
</div>
@endsection
@section('js')

    @if (\Illuminate\Support\Facades\Session::has('message'))
        <script>
            toastr.success('{{ \Illuminate\Support\Facades\Session::get('message') }}');
        </script>
    @endif

    <script type="text/javascript">
        $(document).ready(function() {

            // ✅ DataTable initialize
            if ($.fn.DataTable.isDataTable('#table_id_events')) {
                $('#table_id_events').DataTable().destroy();
            }
            $('#table_id_events').DataTable();

            // ✅ Delete alert confirmation
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

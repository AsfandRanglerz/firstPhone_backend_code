@extends('admin.layout.app')
@section('title', 'Customers')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Customers</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                <div class="d-flex justify-content-between mb-3">
                                @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Customers') && $sideMenuPermissions['Customers']->contains('create')))
                                    <a class="btn btn-primary mb-3 text-white"
                                        href="{{ url('/admin/user-create') }}">Create</a>
                                @endif
                               
                                </div>

                                {{-- @if (Auth::guard('admin')->check() || ($sideMenuPermissions->has('users') && $sideMenuPermissions['users']->contains('view')))
                                    <a class="btn btn-primary mb-3 text-white" href="{{ url('admin/users/trashed') }}">View
                                        Trashed</a>
                                @endif --}}

                                <table class="table responsive" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Image</th>
                                            <th>Toggle</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                    </tbody>
                                </table>
                            </div> <!-- /.card-body -->
                        </div> <!-- /.card -->
                    </div> <!-- /.col -->
                </div> <!-- /.row -->
            </div> <!-- /.section-body -->
        </section>
    </div>


    <!-- Deactivation Reason Modal -->
    <div class="modal fade" id="deactivationModal" tabindex="-1" role="dialog" aria-labelledby="deactivationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deactivationModalLabel">Deactivation Reason</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="deactivationForm">
                        @csrf
                        <input type="hidden" name="user_id" id="deactivatingUserId">
                        <div class="form-group">
                            <label for="deactivationReason">Please specify the reason for deactivation:</label>
                            <textarea class="form-control" id="deactivationReason" name="reason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmDeactivation">
                        Submit
                        <span id="deactivationLoader" class="spinner-border spinner-border-sm text-light ml-2"
                            role="status" style="display:none;"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
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
    ajax: "{{ route('users.data') }}",

    columns: [
        { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false},
        { data: 'name', name: 'name' },
        { data: 'email', name: 'email' },
        { data: 'phone', name: 'phone' },
        { data: 'image', name: 'image', orderable: false, searchable: false },
        { data: 'toggle', name: 'toggle', orderable: false, searchable: false },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],

    pageLength: 25,
});

    // SweetAlert2 delete confirmation using event delegation
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

    // Toggle status
    let currentToggle = null;
    let currentUserId = null;

    $(document).on('change', '.toggle-status', function() {
        let status = $(this).is(':checked') ? 1 : 0;
        currentToggle = $(this);
        currentUserId = $(this).data('id');

        if (status === 0) {
            $('#deactivatingUserId').val(currentUserId);
            $('#deactivationModal').modal('show');
        } else {
            updateUserStatus(currentUserId, 1);
        }
    });

    $('#confirmDeactivation').click(function() {
        let reason = $('#deactivationReason').val();
        if (reason.trim() === '') {
            toastr.error('Please provide a deactivation reason');
            return;
        }

        $('#deactivationLoader').show();
        $('#confirmDeactivation').prop('disabled', true);

        updateUserStatus(currentUserId, 0, reason);
    });

    function updateUserStatus(userId, status, reason = null) {
        let $descriptionSpan = currentToggle.siblings('.custom-switch-description');
        $.ajax({
            url: "{{ route('user.toggle-status') }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                id: userId,
                status: status,
                reason: reason
            },
            success: function(res) {
                if (res.success) {
                    $descriptionSpan.text(res.new_status);
                    toastr.success(res.message);
                    $('#deactivationModal').modal('hide');
                    $('#deactivationReason').val('');
                } else {
                    currentToggle.prop('checked', !status);
                    toastr.error(res.message);
                }
            },
            error: function() {
                currentToggle.prop('checked', !status);
                toastr.error('Error updating status');
            },
            complete: function() {
                $('#deactivationLoader').hide();
                $('#confirmDeactivation').prop('disabled', false);
            }
        });
    }
});
    </script>
   @endsection

@extends('admin.layout.app')
@section('title', 'Notifications')

@section('content')
    <style>
        /* Style each selected option (chip) */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            padding-right: 20px !important;
            position: relative;
            background-color: #f0f0f0;
            border-radius: 4px;
            margin: 2px 5px 2px 0;
            font-size: 14px;
            transition: background-color 0.2s ease;
        }

        /* Style the remove (×) icon - hidden by default */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            position: absolute;
            right: 5px;
            top: 2px;
            color: #333;
            font-weight: bold;
            background: transparent;
            border: none;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.2s ease;
            cursor: pointer;
        }

        /* Show remove icon only on hover */
        .select2-container--default .select2-selection--multiple .select2-selection__choice:hover .select2-selection__choice__remove {
            opacity: 1;
        }

        /* Optional: remove Chrome/Edge × clear button in input fields */
        input::-ms-clear,
        input::-webkit-clear-button,
        select::-ms-clear,
        select::-webkit-clear-button {
            display: none !important;
            width: 0;
            height: 0;
        }

        /* Optional: dropdown scroll */
        .select2-results__options {
            max-height: 200px;
            overflow-y: auto !important;
        }

        /* Optional: more padding inside the selection box */
        .select2-container--default .select2-selection--multiple {
            min-height: 40px;
            padding: 8px;
        }
    </style>

    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Notifications</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Notifications') && $sideMenuPermissions['Notifications']->contains('create')))
                                    <a class="btn mb-3 text-white" data-bs-toggle="modal" style="background-color: #009245;"
                                        data-bs-target="#createUserModal">Create</a>
                                @endif

                                @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Notifications') && $sideMenuPermissions['Notifications']->contains('delete')))
                                    <form action="{{ route('notifications.deleteAll') }}" method="POST"
                                        class="d-inline-block float-right">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-primary mb-3 delete_all">
                                            Delete All
                                        </button>
                                    </form>
                                @endif
                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Date & Time</th>
                                            <th>User Type</th>
                                            <th>Users</th>
                                            {{-- <th>Image</th> --}}
                                            <th>Title</th>
                                            <th>Message</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($notifications as $notification)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $notification->created_at->timezone('Asia/Karachi')->format('d M Y, h:i A') }}</td>
                                                <td>{{ ucfirst($notification->user_type) }}</td>
                                                <td>
                                                    @php
                                                        $targetNames = $notification->targets
                                                            ->pluck('targetable.name')
                                                            ->filter()
                                                            ->values();
                                                    @endphp

                                                    {{-- Preview (first 2 names as badges) --}}
                                                    <span id="user-preview-{{ $notification->id }}">
                                                        @foreach ($targetNames->take(2) as $name)
                                                            <span class="badge me-1 mb-1"
                                                                style="background-color: #009245; color: #fff;">
                                                                {{ $name }}
                                                            </span>
                                                        @endforeach

                                                        @if ($targetNames->count() > 2)
                                                            <a href="javascript:void(0);"
                                                                onclick="toggleUsers({{ $notification->id }})">...more</a>
                                                        @endif
                                                    </span>

                                                    {{-- Full list of names as badges, hidden initially --}}
                                                    <div id="user-full-{{ $notification->id }}" style="display: none;">
                                                        @foreach ($targetNames as $name)
                                                            <span class="badge me-1 mb-1"
                                                                style="background-color: #009245; color: #fff;">
                                                                {{ $name }}
                                                            </span>
                                                        @endforeach
                                                        <a href="javascript:void(0);"
                                                            onclick="toggleUsers({{ $notification->id }})">less</a>
                                                    </div>
                                                </td>



                                                <td>{{ $notification->title }}</td>
                                                <td>
                                                    @php
                                                        $fullMsg = strip_tags($notification->description);
                                                        $previewMsg = \Illuminate\Support\Str::words(
                                                            $fullMsg,
                                                            4,
                                                            '...',
                                                        );
                                                        $wordCount = str_word_count($fullMsg);
                                                    @endphp

                                                    <span id="msg-preview-{{ $notification->id }}">
                                                        {{ $previewMsg }}
                                                        @if ($wordCount > 4)
                                                            <a href="javascript:void(0);"
                                                                onclick="toggleMessage({{ $notification->id }})">read
                                                                more</a>
                                                        @endif
                                                    </span>

                                                    @if ($wordCount > 4)
                                                        <div id="msg-full-{{ $notification->id }}" style="display: none;">
                                                            {{ $fullMsg }}
                                                            <a href="javascript:void(0);"
                                                                onclick="toggleMessage({{ $notification->id }})">read
                                                                less</a>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('Notifications') && $sideMenuPermissions['Notifications']->contains('delete')))
                                                        <form id="delete-form-{{ $notification->id }}"
                                                            action="{{ route('notification.destroy', $notification->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>

                                                        <button class="show_confirm btn" style="background-color: #009245;"
                                                            data-form="delete-form-{{ $notification->id }}" type="button">
                                                            <span><i class="fa fa-trash"></i></span>
                                                        </button>
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

    <!-- Create Notification Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-labelledby="createUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="createUserForm" method="POST" action="{{ route('notification.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            {{-- <input type="hidden" name="user_type" value="user"> --}}

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>User Type <span style="color:red;">*</span></strong></label>
                                    <select id="user_type" name="user_type" class="form-control">
                                        <option value="">Select user type</option>
                                        <option value="customers">Customers</option>
                                        <option value="vendors">Vendors</option>
                                        <option value="all">All</option>
                                    </select>
                                    @error('user_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Title <span style="color:red;">*</span></strong></label>
                                    <input type="text" id="title" name="title" class="form-control"
                                        placeholder="Title">
                                    @error('title')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group" id="user_field" style="display: none;">
                                    <label><strong>Users <span style="color: red;">*</span></strong></label>

                                    <div class="form-check mb-2" style="line-height: 1.9;padding-left: 1.5em">
                                        <input type="checkbox" id="select_all_users" class="form-check-input">
                                        <label class="form-check-label" for="select_all_users">Select All</label>
                                    </div>

                                    <select name="users[]" id="users" class="form-control select2" multiple></select>

                                    {{-- Hidden preload lists --}}
                                    <select id="customers_list" style="display: none;">
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>

                                    <select id="vendors_list" style="display: none;">
                                        @foreach ($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>

                                    @error('users')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Description <span style="color:red;">*</span></strong></label>
                                    <textarea name="description" id="description" class="form-control" placeholder="Type your message here..."
                                        rows="4"></textarea>
                                    @error('description')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="createBtn">
                            <span id="createBtnText">Create Notification</span>
                            <span id="createSpinner" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#table_id_events').DataTable();

            // Initial Select2
            $('.select2').select2({
                placeholder: "Select sellers",
                allowClear: true
            });

            // Re-initialize Select2 inside modal
            $('#createUserModal').on('shown.bs.modal', function() {
                $('.select2').select2({
                    dropdownParent: $('#createUserModal'),
                    placeholder: "Select sellers",
                    allowClear: true
                });
            });

            // Handle Select All
            $('#select_all_users').on('change', function() {
                $('#users > option').prop('selected', this.checked).trigger('change');
            });

            // Check/uncheck Select All checkbox based on selection
            $('#users').on('change', function() {
                $('#select_all_users').prop('checked', $('#users option:selected').length === $(
                    '#users option').length);
            });

            // Form Validation
            // Form Validation & AJAX Submit
            $('form#createUserForm').submit(function(e) {
                e.preventDefault();
                $('.text-danger').remove();
                let isValid = true;

                const userType = $('#user_type').val();
                const title = $('#title').val().trim();
                const description = $('#description').val().trim();
                const selectedUsers = $('#users').val();

                if (!userType) {
                    $('#user_type').after('<div class="text-danger mt-1">User type is required</div>');
                    isValid = false;
                }

                if ($('#user_field').is(':visible') && (!selectedUsers || selectedUsers.length === 0)) {
                    $('#users').after(
                        '<div class="text-danger mt-1">Please select at least one user</div>');
                    isValid = false;
                }

                if (!title) {
                    $('#title').after('<div class="text-danger mt-1">Title is required</div>');
                    isValid = false;
                }

                if (!description) {
                    $('#description').after('<div class="text-danger mt-1">Description is required</div>');
                    isValid = false;
                }

                if (isValid) {
                    $("#createSpinner").show();
                    $("#createBtnText").hide();
                    $("#createBtn").prop("disabled", true);

                    let formData = new FormData(this);

                    $.ajax({
                        url: $(this).attr('action'),
                        method: $(this).attr('method'),
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $("#createSpinner").hide();
                            $("#createBtnText").show();
                            $("#createBtn").prop("disabled", false);

                            // Modal close
                            $('#createUserModal').modal('hide');
                            $('#createUserForm')[0].reset();
                            $('#users').val(null).trigger('change');

                            toastr.success("Notification Sent Successfully.", "Success!");

                            // $('#table_id_events').DataTable().ajax.reload(null, false);
                            location.reload();

                        },
                        error: function(xhr) {
                            $("#createSpinner").hide();
                            $("#createBtnText").show();
                            $("#createBtn").prop("disabled", false);

                            if (xhr.status === 422) {
                                // show Validation errors
                                let errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, value) {
                                    $(`[name="${key}"]`).after(
                                        `<div class="text-danger mt-1">${value[0]}</div>`
                                    );
                                });
                            } else {
                                swal("Error!", "Something went wrong. Please try again.",
                                    "error");
                            }
                        }
                    });
                }
            });


            // Delete single
            $(document).on('click', '.show_confirm', function(event) {
                event.preventDefault();
                let formId = $(this).data("form");
                let form = document.getElementById(formId);
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

            // Delete all
            $('.delete_all').click(function(event) {
                event.preventDefault();
                const form = $(this).closest("form");

                swal({
                    title: 'Are you sure you want to delete all records?',
                    text: "This will permanently remove all records and cannot be undone.",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        form.submit();
                    }
                });
            });

            // User type change handling
            $('#user_field').hide();
            $('#user_type').on('change', function() {
                const userType = $(this).val();
                $('#users').empty();
                $('#select_all_users').prop('checked', false);

                if (userType === 'customers') {
                    $('#users').html($('#customers_list').html());
                    $('#user_field').slideDown(initSelect2("Select customers"));
                } else if (userType === 'vendors') {
                    $('#users').html($('#vendors_list').html());
                    $('#user_field').slideDown(initSelect2("Select vendors"));
                } else if (userType === 'all') {
                    const allOptions = $('#customers_list').html() + $('#vendors_list').html();
                    $('#users').html(allOptions);
                    $('#user_field').slideDown(initSelect2("Select users"));
                } else {
                    $('#user_field').slideUp();
                }

                $('#users').val(null).trigger('change');
            });

            // Helper to re-init select2 with placeholder
            function initSelect2(placeholderText) {
                return function() {
                    $('#users').select2('destroy').select2({
                        dropdownParent: $('#createUserModal'),
                        placeholder: placeholderText,
                        allowClear: true,
                        width: '100%'
                    });
                };
            }
        });

        // Toggle full user list
        function toggleUsers(id) {
            const preview = document.getElementById(`user-preview-${id}`);
            const full = document.getElementById(`user-full-${id}`);
            preview.style.display = preview.style.display === 'none' ? 'inline' : 'none';
            full.style.display = full.style.display === 'none' ? 'inline' : 'none';
        }

        // Toggle full message
        function toggleMessage(id) {
            const preview = document.getElementById(`msg-preview-${id}`);
            const full = document.getElementById(`msg-full-${id}`);
            preview.style.display = preview.style.display === 'none' ? 'inline' : 'none';
            full.style.display = full.style.display === 'none' ? 'inline' : 'none';
        }
    </script>
@endsection

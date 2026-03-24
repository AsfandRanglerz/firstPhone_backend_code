@extends('admin.layout.app')
@section('title', "{$brand->name} - Models")

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ url('admin/brands') }}">Back</a>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>{{ $brand->name }} - Models</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                 @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Models') && $sideMenuPermissions['Models']->contains('create')))
                                {{-- Create Button --}}
                                <button class="btn mb-3" style="background-color: #009245;"
                                    id="openCreateModal">Create</button>
                                @endif
                                <table class="table" id="brandsTable">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($models as $model)
                                        @php
                                            $existModel = in_array($model->id, $modelUsedInVendorMobiles);
                                                          
                                        @endphp
                                            <tr id="brand-row-{{ $model->id }}">
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="brand-name">
                                                    {{ is_array($model->name) ? implode(', ', $model->name) : $model->name }}
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                         @if (Auth::guard('admin')->check() ||
                                                        ($sideMenuPermissions->has('Models') && $sideMenuPermissions['Models']->contains('edit')))
                                                        <button class="btn btn-primary editBrand"
                                                            data-id="{{ $model->id }}"
                                                            data-name="{{ is_array($model->name) ? implode(', ', $model->name) : $model->name }}">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        @endif
                                                        @if (Auth::guard('admin')->check() ||
                                                        ($sideMenuPermissions->has('Models') && $sideMenuPermissions['Models']->contains('delete')))
                                                        @if (!$existModel)
                                                        <button class="btn deleteBrand" style="background-color: #009245;"
                                                            data-id="{{ $model->id }}">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                         @else

                                                            <button class="btn" style="background-color: #009245;"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#canNotDeleteModal">
                                                                <i class="fa fa-trash"></i>
                                                            </button>

                                                         @endif
                                                         @endif
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

    <!-- Create Modal -->
    <div class="modal fade" id="brandModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form id="brandForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create Brand Model</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="brand_id" value="{{ $brand->id }}" placeholder="Enter model name">
                        <div id="inputWrapper"></div>
                        <button type="button" class="btn btn-secondary btn-sm" style="background-color: #009245;"
                            id="addMoreBtn">Add More</button>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form id="editForm">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Brand Model</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit_name"
                                placeholder="Enter brand model name">
                            <div class="text-danger error-message" id="edit_name_error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="canNotDeleteModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
          <div class="modal-dialog" role="document">
              <div class="modal-content">
                        <div class="modal-body">
                            {{__('You can not delete this model. Because there are one or more vendor mobiles created of this model.')}}
                        </div>

                  <div class="modal-footer">
                      <button type="button" class="btn btn-danger" data-bs-dismiss="modal">{{__('Close')}}</button>
                  </div>
              </div>
          </div>
      </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
             $(document).keydown(function (e) {
                if (e.key === "Escape") {
                    $('.modal.show').modal('hide');
                }
             });
            let table = $('#brandsTable').DataTable();

            function getBrandInputSet(index = null, showRemove = false) {
                return `
            <div class="brand-input-set mb-3" data-index="${index}">
                <div class="form-group">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="name[]" class="form-control name-input" placeholder="Enter model name">
                    <div class="text-danger error-message" data-error-for="name.${index ?? 0}"></div>
                </div>
                <button type="button" class="btn btn-danger btn-sm removeBtn" ${showRemove ? '' : 'style="display:none;"'}>Delete</button>
            </div>`;
            }

            // Open Create Modal
            $('#openCreateModal').click(function() {
                $('#brandForm')[0].reset();
                $('#inputWrapper').html(getBrandInputSet(0, false));
                $('#brandModal').modal('show');
            });

            // Add More Inputs
            $('#addMoreBtn').click(function() {
                let index = $('#inputWrapper .brand-input-set').length;
                $('#inputWrapper').append(getBrandInputSet(index, true));
            });

            // Remove Input Field
            $(document).on('click', '.removeBtn', function() {
                $(this).closest('.brand-input-set').remove();
            });

            // Auto-clear error on focus
            $(document).on('focus', '.name-input', function() {
                $(this).next('.error-message').text('');
            });

            // Create Form Submission
           $('#brandForm').submit(function(e) {
    e.preventDefault();

    let $btn = $('#brandForm button[type="submit"]'); // submit button

    // Disable button + show loading
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

    $.ajax({
        url: "{{ route('brands.model.store') }}",
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
    if (response.data) {
        let models = Array.isArray(response.data) ? response.data : [response.data];
        let newRows = [];

        models.forEach(model => {
            let row = addBrandToTable(model);
            newRows.push(row);
        });

        // sab rows ko ek sath reverse karke prepend karo
        $(newRows.reverse()).prependTo($(table.table().body()));

        // numbering fix karo
        table.rows().every(function(index) {
            $(this.node()).find('td').eq(0).html(index + 1);
        });
    }

    toastr.success('Model Created Successfully');
    setTimeout(function () {
        location.reload();
     }, 3000);
},
        error: function(xhr) {
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;

                // Clear all previous errors first
                $('.error-message').text('');

                for (let field in errors) {
                    let originalMsg = errors[field][0];

                    let customMsg = originalMsg
                        .replace(/\.\d+/g, '') // remove .0, .1 etc
                        .replace('name field', 'Name field')
                        .replace('The Name field is required', 'This name field is required');

                    $(`.error-message[data-error-for="${field}"]`).text(customMsg);

                    $(`[name="${field.replace('.', '[').replace('.', ']')}"]`)
                        .off('focus.clearError')
                        .on('focus.clearError', function() {
                            $(`.error-message[data-error-for="${field}"]`).text('');
                        });
                }
            } else {
                toastr.error('Something went wrong.');
            }
        },
        complete: function() {
            // Reset button state
            $btn.prop('disabled', false).html('Save');
        }
    });
});


            // Edit Modal Open
            // Edit button click
            $(document).on('click', '.editBrand', function() {
                $('#edit_id').val($(this).data('id'));
                $('#edit_name').val($(this).data('name'));
                $('#edit_name_error').text('');
                $('#editModal').modal('show');
            });

            // Hide error when clicking back on input
            $(document).on('focus', '#edit_name', function() {
                $('#edit_name_error').text('');
            });


            // Edit Form Submit
         $('#editForm').submit(function(e) {
    e.preventDefault();

    let id = $('#edit_id').val();
    let $btn = $('#editForm button[type="submit"]'); // update button

    // Disable button + show loading
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving Changes...');

    $.ajax({
        url: "{{ route('brands.model.update', ':id') }}".replace(':id', id),
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            updateBrandInTable(response.data);

            toastr.success('Model Updated Successfully');

            // Modal close
            $('#editModal').modal('hide');
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                $('#edit_name_error').text(xhr.responseJSON.errors.name ? xhr.responseJSON.errors.name[0] : '');
            } else {
                toastr.error('Something went wrong.');
            }
        },
        complete: function() {
            // Button ko wapas normal state par le aao
            $btn.prop('disabled', false).html('Save Changes');
        }
    });
});


            // Delete Brand
            $(document).on('click', '.deleteBrand', function() {
                let id = $(this).data('id');
                swal({
                    title: "Are you sure you want to delete this record?",
                    text: "If you delete this, it will be gone forever.",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            url: "{{ route('brands.model.delete', ':id') }}".replace(':id',
                                id),
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                _method: 'DELETE'
                            },
                            success: function() {
                                table.row($(`#brand-row-${id}`)).remove().draw();
                                toastr.success('Model Deleted Successfully');
                            },
                            error: function() {
                                toastr.error('Delete failed.');
                            }
                        });
                    }
                });
            });

        function addBrandToTable(model) {
    let newRow = table.row.add([
        table.rows().count() + 1,
        Array.isArray(model.name) ? model.name.join(', ') : model.name,
        `<div class="d-flex gap-1">
            <button class="btn btn-primary editBrand"
                data-id="${model.id}"
                data-name="${Array.isArray(model.name) ? model.name.join(', ') : model.name}">
                <i class="fa fa-edit"></i>
            </button>
            <button class="btn deleteBrand" style="background-color: #009245;"
                data-id="${model.id}">
                <i class="fa fa-trash"></i>
            </button>
        </div>`
    ]).draw(false).node();

    $(newRow).attr('id', `brand-row-${model.id}`);
    $(newRow).find('td').eq(1).addClass('brand-name');

    return newRow; // sirf row return karo
}



            function updateBrandInTable(model) {
                let row = $(`#brand-row-${model.id}`);
                row.find('.brand-name').text(Array.isArray(model.name) ? model.name.join(', ') : model.name);
                row.find('.editBrand').data('name', model.name);
            }
        });
    </script>
@endsection
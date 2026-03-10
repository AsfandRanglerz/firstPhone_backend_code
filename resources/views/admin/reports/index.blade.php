@extends('admin.layout.app')
@section('title', 'Sales Reporting')

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">

                            {{-- Totals Row --}}
                            <div class="row mt-3 w-100">
                                <div class="col-md-3">
                                    <div class="card shadow border-0 text-white mb-0">
                                        <div class="card-body py-2">
                                            <h6 class="mb-1">Total Products</h6>
                                            <h6 class="mb-0 fw-bold">{{ $topProducts->count() }}</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card shadow border-0 text-white mb-0">
                                        <div class="card-body py-2">
                                            <h6 class="mb-1">Total Orders</h6>
                                            <h6 class="mb-0 fw-bold">{{ $totalOrders }}</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card shadow border-0 text-white mb-0">
                                        <div class="card-body py-2">
                                            <h6 class="mb-1">Total Earnings</h6>
                                            <h6 class="mb-0 fw-bold">
                                                Rs {{ number_format($topProducts->sum(fn($p) => (float) $p->revenue), 2) }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Table --}}
                        <div class="card-body table-striped table-bordered table-responsive">

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form id="filtersForm" class="row g-2 align-items-end">
                                        <div class="col">
                                            <label class="fw-bold mb-1">Start Date</label>
                                            <input type="date" name="start_date" class="form-control">
                                        </div>
                                        <div class="col">
                                            <label class="fw-bold mb-1">End Date</label>
                                            <input type="date" name="end_date" class="form-control">
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-primary">Apply</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="col-md-4">
                                    <label class="fw-bold mb-1">Filter by Vendor</label>
                                    <select name="vendor_id" id="vendorFilter" class="form-control form-select">
                                        <option value="">All Vendors</option>
                                        @foreach ($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <table class="table" id="report_table">
                                <thead>
                                    <tr>
                                        <th>Sr.</th>
                                        <th>Brand / Model</th>
                                        <th>Vendor</th>
                                        <th>Quantity Sold</th>
                                        <th>Earnings</th>
                                    </tr>
                                </thead>
                                <tbody id="reportTableBody">
                                    @include('admin.reports.partials.table_rows')
                                </tbody>
                            </table>

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
        // Initialize datatable
        let reportTable = $('#report_table').DataTable();

        function fetchReports() {
            $.ajax({
                url: "{{ route('reports.index') }}",
                method: "GET",
                data: $("#filtersForm").serialize() + "&vendor_id=" + $("#vendorFilter").val(),
                success: function(res) {
                    // destroy old DataTable
                    reportTable.destroy();

                    // update tbody with fresh data
                    $("#reportTableBody").html(res.html);

                    // reinitialize DataTable
                    reportTable = $('#report_table').DataTable();

                    // update totals
                    $(".total-products").text(res.totals.products);
                    $(".total-orders").text(res.totals.orders);
                    $(".total-revenue").text("Rs " + res.totals.revenue);

                    toastr.success("Filter Applied Successfully!");
                },
                error: function() {
                    toastr.error("Failed to fetch reports. Try again!");
                }
            });
        }

        $("#filtersForm").on("submit", function(e) {
            e.preventDefault();
            fetchReports();
        });

        $("#vendorFilter").on("change", function() {
            fetchReports();
        });
    });
</script>
@endsection


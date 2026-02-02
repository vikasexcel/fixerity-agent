@extends('admin.layout.super_admin')
@section('title')
    Report Issues
@endsection
@section('page-css')
    <!-- Data Table Css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/responsive/buttons.dataTables.min.css?v=0.1')}}">
    <link rel="stylesheet" type="text/css" media="screen" href="{{asset('assets/css/bootstrap-datetimepicker.min.css')}}">
    <!-- CSS for the list filter -->
    <link href="{{ asset('assets/css/select2.min.css?v=0.1')}}" rel="stylesheet"/>
    <style>
        table th, table td {
            word-wrap: break-word !important;
            white-space: normal;
        }
    </style>
    <!-- Data Table row css -->
    <style>
        .url_css {
            background: #4099ff;
            color: white;
            padding: 2px 5px;
            border-radius: 5px;
        }

        .url_css:hover {
            text-decoration: none;
            color: white;
        }

        .unresolved {
            font-size: 12px;
            width: auto;
            padding: 2px 5px;
            color: white;
            border-radius: 5px;
            background: #E57373;
        }

        .resolved {
            font-size: 12px;
            width: auto;
            padding: 2px 5px;
            color: white;
            border-radius: 5px;
            background: #16D39A;
        }

        .switch {
            position: relative;
            /*display: inline-block; affects the datetime picker */
            width: 35px;
            height: 18px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: silver;
            -webkit-transition: .4s;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 4px;
            bottom: 2px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(15px);
            -ms-transform: translateX(15px);
            transform: translateX(15px);
        }
        /* Rounded sliders */
        .slider.round {
            border-radius: 10px;
        }

        .slider.round:before {
            border-radius: 48%;
        }
    </style>
    <!--date filter css-->
    <style>
        /*date timepicker style*/
        .input-group {
            margin-bottom: 0;
        }

        .page-link {
            color: #55d090;
        }

        .date-wrapper {
            margin: 20px 0;
        }

        ul.set-date {
            list-style-type: none;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        ul.set-date li {
            float: left;
            margin-left: 20px;
            padding-right: 16px;
            border-right: 2px solid #21252a;
        }

        ul.set-date li:last-of-type {
            border-right: none;
        }

        ul.set-date li:first-of-type {
            margin-left: 0;
        }

        ul.set-date li a {
            display: block;
            color: #2a455f !important;
            text-align: center;
            text-decoration: underline !important;
        }

        ul.set-date li a:hover {
            color: #07C !important;
            text-decoration: none !important;
            cursor: pointer;
        }

        .datetimepicker-dropdown-bottom-left {
            width: 250px;
        }

        .datetimepicker table {
            width: 100%;
        }

        #to_date, #from_date {
            background: white;
            border: 1px solid #aaa;
            border-radius: 4px;
        }

        /* Start of select2 CSS   */
        .select2-container {
            width: 100% !important;
            vertical-align: unset;
        }

        .select2-container--default .select2-selection--single {
            height: auto;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            /*padding-top: 1px;*/
            padding: 4px 30px 4px 20px;
            background-color: transparent;
            line-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        /* END of the select2 CSS   */
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-list bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>
                                Report Issues
                                @if(isset($provider_type))
                                    -
                                    @php
                                        $providerTypeName = match($provider_type) {
                                            0 => 'Customer',
                                            3 => 'Provider',
                                            default => 'Unknown'
                                        };
                                    @endphp
                                    {{ $providerTypeName }}
                                @endif
                            </h5>

                            <span>All Report Issues</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="card">
                            <div class="card-header">
                                <h5>Report Issues
                                    @if(isset($provider_type))
                                        -
                                        @php
                                            $providerTypeName = match($provider_type) {
                                                0 => 'Customer',
                                                3 => 'Provider',
                                                default => 'Unknown'
                                            };
                                        @endphp
                                        {{ $providerTypeName }}
                                    @endif
                                </h5>
                            </div>
                            <div class="card-block">
                                <!-- Date Filter Start -->
                                <div class="row">
                                    <div class="col-lg-12 date-wrapper">
                                        <div class="form-group">
                                            <ul class="set-date">
                                                <li><a id="today">Today</a></li>
                                                <li><a id="yesterday">Yesterday</a></li>
                                                <li><a id="this_week">This Week</a></li>
                                                <li><a id="this_month">This Month</a></li>
                                                <li><a id="last_month">Last Month</a></li>
                                                <li><a id="this_year">This Year</a></li>
                                                <li><a id="last_year">Last Year</a></li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!--from-->
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <div class="input-group date form_datetime">
                                                <input name="from_date" class="form-control category"
                                                       value="{{isset($from_date)? ($from_date != Null)?  $from_date : old('from_date') : old('from_date') }}"
                                                       placeholder="From Date" id="from_date" type="text" readonly>
                                                <span class="input-group-append" id="basic-addon3">
                                                        <label class="bg-c-blue input-group-text" style="padding: 10px">
                                                            <span class="fa fa-remove remove_from_date "></span>
                                                        </label>
                                                    </span>
                                                <span class="input-group-append" id="basic-addon3">
                                                        <label class="bg-c-blue input-group-text" style="padding: 10px">
                                                            <span class="fa fa-th"></span>
                                                        </label>
                                                    </span>
                                            </div>
                                        </div>
                                    </div>
                                    <!--to-->
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <div class="input-group date to_datetime">
                                                <input name="to_date" class="form-control category"
                                                       value="{{isset($to_date)? ($to_date != Null)?  $to_date : old('to_date') : old('to_date') }}"
                                                       placeholder="To Date"
                                                       id="to_date"
                                                       type="text" readonly>
                                                <span class="input-group-append" id="basic-addon3">
                                                <label class="bg-c-blue input-group-text" style="padding: 10px">
                                                    <span class="fa fa-remove remove_to_date"></span>
                                                </label>
                                                </span>
                                                <span class="input-group-append" id="basic-addon3">
                                                    <label class="bg-c-blue input-group-text" style="padding: 10px">
                                                        <span class="fa fa-th"></span>
                                                    </label>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <!--created vy-->
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <select id="created_by" name="created_by"  class="js-example-placeholder-single1 js-states form-control">
                                                <option disabled selected value=""></option>
                                                <option disabled>Select Created By</option>
                                                @if(isset($user_list))
                                                    @foreach($user_list as $key => $driver_detail_filter)
                                                        {{ $selected = isset($user)? ($user != Null)? ($user == $driver_detail_filter->id)?  "selected" : "" : "" : "" }}
                                                        <option value="{{ $driver_detail_filter->id }}"
                                                            {{ $selected }}>
                                                            {{ ucwords($driver_detail_filter->first_name ." | ". App\Models\User::ContactNumber2Stars($driver_detail_filter->country_code ."-". $driver_detail_filter->contact_number)) }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <!-- Status Dropdown Filter -->
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <select id="statusFilter" class="form-control" style="width: 100%; display: inline-block; margin-right: 10px;">
                                                <option value="">All</option>
                                                <option value="resolved">Resolved</option>
                                                <option value="unresolved">Unresolved</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class=" text-center">
                                        <button class="btn btn-primary" id="dateFilters" style="margin-bottom: 10px;">Search</button>
                                        <button class="btn btn-danger" id="resetFilters" style="margin-bottom: 10px;">Clear</button>
                                    </div>
                                </div>
                                <!-- Date Filter End -->
                                <div class="dt-responsive table-responsive">
                                    <table id="report_issue_table"
                                           class="table table-striped table-bordered"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Ticket ID</th>
                                            <th>Description</th>
                                            <th>Created By</th>
                                            <th>Issue Date</th>
                                            <th>Order No</th>
                                            <th>Service Name</th>
                                            <th>Resolved On</th>
                                            <th>View</th>
                                            <th>Chat</th>
                                            <th>Status</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Page body end -->
            </div>
        </div>
    </div>
@endsection
@section('page-js')
    <!-- data-table js -->
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}" type="text/javascript"></script>
    <script type="text/javascript" src="{{asset('assets/js/bootstrap-datetimepicker.js')}}" charset="UTF-8"></script>
    <script src="{{ asset('assets/js/current-date-filter.js')}}"></script>
    <!-- JS for select2 for the filter of list -->
    <script src="{{ asset('assets/js/select2.min.js')}}"></script>

    <!-- Confirm Delete Sweetalert -->
    <script>
        $(document).ready(function () {
            var providerType = "{{ $provider_type }}"; // Get provider type

            // Initialize the DataTable
            var table = $('#report_issue_table').DataTable({
                responsive: true, // Enable responsive table
                processing: true, // Enable processing indicator
                serverSide: true, // Enable server-side processing
                pageLength: 20, // Set initial page length
                lengthMenu: [10, 20, 50, 100], // Set length menu options
                ajax: {
                    url: "{{ route('get:ajax:admin:fetch_report_issue', '') }}" + '/' + providerType, // route to call the data
                    type: "GET", // Request type
                    cache: false, // Disable caching
                    data: function (d) {
                        d.status = $('#statusFilter').val();         // Get status from dropdown
                        d.from_date = $('#from_date').val();         // Get from date
                        d.to_date = $('#to_date').val();             // Get to date
                        d.created_by = $('#created_by').val();
                    }
                },
                // Column definitions
                columns: [
                    { data: 'id' },
                    { data: 'ticket_id', sortable: false },
                    { data: 'description' },
                    { data: 'created_by' },
                    { data: 'created_at' },
                    { data: 'order_no' },
                    { data: 'service_name'},
                    { data: 'resolved_on'},
                    { data: 'view', sortable: false },
                    { data: 'chat', sortable: false },
                    { data: 'status', sortable: false },
                ],
                "order": [[0, 'desc']], // Set initial order column and direction
                "fnDrawCallback": function() {
                    init(); // Call the init function here if needed
                }
            });

            // Reload table on date filter button click
            $('#dateFilters').on('click', function (event) {
                event.preventDefault(); // Prevent form submission
                table.ajax.reload(); // Reload the table with the updated filters
            });

            // Reset filters
            $('#resetFilters').on('click', function (event) {
                event.preventDefault(); // Prevent form submission
                $('#from_date').val(""); // Reset from date
                $('#to_date').val(""); // Reset to date
                $("#created_by").val("").trigger('change');
                $('#statusFilter').val(""); // Reset status filter
                table.ajax.reload(); // Reload with cleared filters
            });
        });

        // Define the init function if you need specific functionality
        function init() {}
    </script>
    <script>
        // Update the report issue status to Resolved
        $(document).on('click', '.changeStatus', function (e) {
        var id = $(this).data('id'); // Get the report issue ID from the data attribute

        swal({
                title: "Resolve Issue?",
                text: "If you press Yes, the issue will be marked as resolved.",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: "Yes",
                cancelButtonText: "No",
                closeOnConfirm: false,
                closeOnCancel: false
            },
            function (isConfirm) {
                // Check if the user clicks the "Yes" button
                if (isConfirm) {
                    $.ajax({
                        type: 'GET', // Method to send data
                        url: '{{ route("get:ajax:admin:update_report_issue_status") }}', // Route to update the status
                        data: {
                            id: id, // Report issue ID
                            _token: '{{ csrf_token() }}' // CSRF token for security
                        },
                        success: function (result) {
                            // Check if the request was successful
                            if (result.success === true) {
                                swal("Success", result.message, "success");
                                // Update status label and hide thumbs-up icon dynamically
                                $('#status_' + id)
                                    .removeClass('unresolved') // Remove the "unresolved" class
                                    .addClass('resolved') // Add the "resolved" class
                                    .html('Resolved'); // Update the status text
                                $('#changeStatus_' + id).hide(); // Hide the thumbs-up icon

                                // Update the reported_on timestamp instantly with the new timestamp
                                $('#resolved_on_' + id).html(result.resolved_on); // Update the timestamp in the UI
                            } else {
                                // Show an error message
                                swal("Warning", result.message, "warning");
                            }
                        },
                        // Error handling
                        error: function () {
                            swal("Error", "An error occurred while updating the status. Please try again.", "error");
                        }
                    });
                }
                // Check if the user clicks the "No" button
                else {
                    swal("Cancelled", "Issue status remains unchanged.", "error");
                }
            });
    });
    </script>

{{--    <script type="text/javascript" src="{{asset('assets/js/bootstrap-datetimepicker.js')}}" charset="UTF-8"></script>--}}
    <script>
        $(document).ready(function() {
            // Date Range Button Functionality
            const setDateRange = (from, to) => {
                $("#from_date").val(from); // Set from date
                $("#to_date").val(to); // Set to date
            };

            // Function to format date
            const formatDate = (date) => {
                return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
            };

            // Set default date range
            $("#today").click(() => {
                const today = new Date(); // Get today's date
                const todayFormatted = formatDate(today); // Format as dd-mm-yyyy
                setDateRange(todayFormatted, todayFormatted); // Set date range to today
            });

            // Set date range to yesterday
            $("#yesterday").click(() => {
                const yesterday = new Date(); // Get today's date
                yesterday.setDate(yesterday.getDate() - 1); // Get yesterday's date
                const yesterdayFormatted = formatDate(yesterday); // Format as dd-mm-yyyy
                setDateRange(yesterdayFormatted, yesterdayFormatted); // Set date range to yesterday
            });

            // Set date range to last week
            $("#this_week").click(() => {
                const curr = new Date(); // Get today's date
                const firstDayOfWeek = new Date(curr.setDate(curr.getDate() - curr.getDay())); // Get the first day of the week
                const lastDayOfWeek = new Date(curr.setDate(firstDayOfWeek.getDate() + 6)); // Get the last day of the week
                setDateRange(formatDate(firstDayOfWeek), formatDate(lastDayOfWeek)); // Set date range to last week
            });

            // Set date range to last month
            $("#this_month").click(() => {
                const date = new Date(); // Get today's date
                const firstDayOfMonth = new Date(date.getFullYear(), date.getMonth(), 1); // Get the first day of the month
                const lastDayOfMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0); // Get the last day of the month
                setDateRange(formatDate(firstDayOfMonth), formatDate(lastDayOfMonth)); // Set date range to last month
            });

            // Set date range to last year
            $("#last_month").click(() => {
                const date = new Date(); // Get today's date
                const lastMonth = date.getMonth() - 1; // Get the last month
                const firstDayLastMonth = new Date(date.getFullYear(), lastMonth, 1); // Get the first day of the last month
                const lastDayLastMonth = new Date(date.getFullYear(), date.getMonth(), 0); // Get the last day of the last month
                setDateRange(formatDate(firstDayLastMonth), formatDate(lastDayLastMonth)); // Set date range to last year
            });

            // Set date range to last year
            $("#this_year").click(() => {
                const year = new Date().getFullYear(); // Get the current year
                setDateRange(`${year}-01-01`, `${year}-12-31`); // Set date range to this year
            });

            // Set date range to last year
            $("#last_year").click(() => {
                const lastYear = new Date().getFullYear() - 1; // Get the last year
                setDateRange(`${lastYear}-01-01`, `${lastYear}-12-31`); // Set date range to last year
            });

            // Date Picker Initialization
            const datePickerOptions = {
                minView: 2, // Show month and year
                endDate: new Date(), // Set the end date to today
                format: "dd-mm-yyyy", // Set the date format
                autoclose: true, // Close the date picker when a date is selected
                clear: 'Clear selection', // Set the clear button text
                pickerPosition: "bottom-left", // Set the date picker position
            };
            $('.form_datetime, .to_datetime').datetimepicker(datePickerOptions); // Initialize date range picker

            // Clear Dates with Remove Buttons
            $(".remove_from_date, .remove_to_date").click(function() {
                $('#from_date, #to_date').val("").datetimepicker("update"); // Clear date range
            });

            $("#created_by").select2({
                placeholder: "Select a Created By",
                allowClear: true,
            });
        });
    </script>

@endsection

@extends('admin.layout.super_admin')
@section('title')
    Faq Lists
@endsection
@section('page-css')
    <!-- Data Table Css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <style>
        table th, table td {
            word-wrap: break-word !important;
            white-space: normal;
        }
    </style>
    <!-- Data Table row css -->
    <style>
        .url_css{
            background: #4099ff;
            color: white;
            padding: 2px 5px;
            border-radius: 25px;
        }
        .url_css:hover{
            text-decoration: none;
            color: white;
        }
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
                            <h5> FAQs Lists</h5>
                            <span>All FAQs Lists</span>
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
                                <h5>FAQs Lists</h5>
                                <a href="{{ route('get:admin:add_faq') }}"
                                   class="btn btn-primary m-b-0 btn-right render_link">Add Faq</a>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="faq_table"
                                           class="table table-striped table-bordered"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th>Action</th>
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
    <!-- Script To fetch Subscription plan Via Ajax Call -->
    <script type="text/javascript">
        $(document).ready(function(){
            // DataTable
            var table = $('#faq_table').DataTable({
                responsive: true, // Responsive feature enabled
                processing: true, // DataTables is loading
                serverSide: true, // Server side feature enabled
                pageLength: 20, // Number of records per page
                lengthMenu: [10, 20, 50, 100], // Number of records per page options
                ajax: "{{route('get:ajax:admin:fetch_faq_plan_lists')}}", // Ajax source
                // Ajax source
                columns: [
                    { data: 'id', sortable: false },
                    { data: 'name' },
                    { data: 'status', sortable: false },
                    { data: 'action', sortable: false },
                ],
                "order": [[1, 'asc']], // Set default order to the name column
            });
        });
    </script>

    <!-- Script To Delete Subscription plan Via Ajax Call -->
    <script type="text/javascript">
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            // Get the id of the clicked button
            var id = $(this).attr('faqid');
            swal({
                    title: "Faq Remove?",
                    text: "if press yes then Faq is remove!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes",
                    cancelButtonText: "No",
                    closeOnConfirm: false,
                    closeOnCancel: false
                },
                function (isConfirm) {
                // check if user clicked 'Yes' button
                    if (isConfirm) {
                        $.ajax({
                            type: 'get', // Type of request
                            async : false, // Asyncronous request
                            url: '{{ route('get:admin:delete_faq') }}', // route to be called
                            data: {id: id}, // Data sent to server
                            success: function (result) {
                                // Success callback
                                if (result.success == true) {
                                    var new_id = "#hide_" + id; // Get the id of the element to be removed
                                    swal("Success", "Faq remove successfully", "success");
                                    $(new_id).hide(); // Remove the element
                                    var table = $('#faq_table').DataTable(); // Get the DataTable
                                    table.draw(); // Redraw the table
                                }else {
                                    // Display error message
                                    swal("Warning", result.message, "warning");
                                }
                            }
                        })
                    }
                    // check if user clicked 'No' button
                    else {
                        swal("Cancelled", "Faq not removed", "error");
                    }
                });
        });
    </script>
    <!-- Script To Change Status of Subscription plan Via Ajax Call -->
    <script type="text/javascript">
        $(document).on('click', '.faq_status', function (e) {
            e.preventDefault();
            // Get the id of the clicked button
            var id = $(this).attr('faq_id');
            // Get the status of the clicked button
            var status = $(this).attr('faq_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Faq?";
                txt = "if press yes then disable Faq!";
            } else {
                title = "Enable Faq?";
                txt = "if press yes then enable Faq!";
            }
            swal({
                    title: title,
                    text: txt,
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes",
                    cancelButtonText: "No",
                    closeOnConfirm: false,
                    closeOnCancel: false
                },
                function (isConfirm) {
                // Check if the user clicked the "Yes" button
                    if (isConfirm) {
                        $.ajax({
                            type: 'get', // Type of request
                            url: '{{ route("get:ajax:admin:update_faq_status") }}', // route to be called
                            data: {id: id}, // Data sent to server
                            success: function (result) {
                                // Success callback
                                if (result.success == true) {
                                    var faq_id_ = '#faqid_' + id; // Get the id of the element to be removed
                                    var title_status = '#title_status_' + id; // Get the status of the element to be changed
                                    if (result.status == 1) {
                                        $(faq_id_).prop("checked", true); // Change the checkbox
                                        $(faq_id_).attr("faq_status", 1); // Change the status
                                        swal("Success", "Enable Faq successfully", "success");
                                    } else {
                                        $(faq_id_).prop("checked", false); // Change the checkbox
                                        $(faq_id_).attr("faq_status", 0); // Change the status
                                        swal("Success", "Disable Faq successfully", "success");
                                    }
                                } else {
                                    // Show error message
                                    swal("Warning", result.message, "warning");
                                }
                            },
                            // Error callback
                            error: function (xhr, status, error) {
                                console.log(xhr.responseText); // Log error response
                                swal("Error", "An error occurred", "error");
                            }
                        });
                    } else {
                        // Check if the user clicked the "No" button
                        if (status == 1) {
                            swal("Cancelled", "Faq is Enable", "error");
                        } else {
                            swal("Cancelled", "Faq is Disable", "error");
                        }
                    }
                });
        });
    </script>
@endsection

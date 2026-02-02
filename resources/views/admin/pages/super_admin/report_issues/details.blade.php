@extends('admin.layout.super_admin')
@section('title')
    Report Issues
@endsection
@section('page-css')
    <!-- Data Table Css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <!-- Data Table row css -->
    <style>
        .Unresolved, .Resolved {
            display: inline-block; /* Make the divs appear inline */
            padding: 7px 10px; /* Adjust padding for better spacing */
            text-align: center;
            border-radius: 5px;
            font-size: 14px; /* Adjust font size for readability */
        }

        .Resolved, .Chatting, .Like, .Completed {
            background-color: #16D39A;
            color: white;
        }

        .Unresolved, .Not-interested, .Dislike, .Cancelled, .Rejected {
            background-color: #FF5370;
            color: white;
        }

        /* Add responsiveness for smaller devices */
        @media (max-width: 576px) {
            .Unresolved, .Resolved {
                width: auto; /* Let the width adjust automatically */
                font-size: 12px; /* Reduce font size */
                padding: 5px 8px; /* Adjust padding */
            }
        }


        .document_view {
            cursor: pointer;
        }

        .comp-card i {
            color: #fff;
            width: 50px;
            height: 50px;
            border-radius: 5px;
            text-align: center;
            padding: 17px 0;
            font-size: 18px;
            text-shadow: 0 6px 8px rgba(62, 57, 107, 0.18);
            -webkit-transition: all 0.3s ease-in-out;
            transition: all 0.3s ease-in-out;
        }

        .comp-card i {
            width: 35px !important;
            height: 35px !important;
            padding: 8px 0 !important;
        }

        .comp-card:hover i {
            border-radius: 5px !important;
        }

        .comp-card i:hover {
            border-radius: 50% !important;
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        {{--                        <i class="fa fa-bug bg-c-blue"></i>--}}
                        <i class="fa fa-file bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>
                                Report Issues
                            </h5>

                            <span>Displaying all the Details of Report Issues</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->
        <div class="pcoded-inner-content">
            <!-- Main-body start -->
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page-body start -->
                    <div class="page-body">
                        <!-- DOM/Jquery Table Start -->
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-9">
                                        <h5>Issue Details of {{ $report_issue->created_by }}</h5>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a id="changeStatus"
                                           data-id="{{ $report_issue->id }}"
                                           class="btn btn-primary m-b-0 btn-right render_link changeStatus"
                                           style="{{ isset($report_issue) && $report_issue->status == 2 ? 'display:none;' : '' }}; color: white;">
                                            Resolve this issue
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-block">
                                <div class="main">
                                    <div class="row">

                                        <div class="col-lg-12">
                                            <div class="table-responsive ride-detail-table">
                                                <table class="table tbl-details">
                                                    @if($report_issue->category_icon != null)
                                                        <img class="img-thumbnail"
                                                             src="{{ asset('assets/images/service-category/'.$report_issue->category_icon)}}"
                                                             style="width: 70px; height: 70px;"
                                                             alt="{{$report_issue->category_icon}}">
                                                    @endif
                                                    @if($report_issue->service_name != null)
                                                        <tr>
                                                            <th style="width: 25%;">Service Name:</th>
                                                            <td class="">{{ isset($report_issue) ? $report_issue->service_name : ''}}</td>
                                                        </tr>
                                                    @endif
                                                    @if($report_issue->order_no)
                                                        <tr>
                                                            <th>Order No.:</th>
                                                            <td class="">#{{ isset($report_issue) ? $report_issue->order_no : '' }}</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <th style="width: 25%;">Ticket ID:</th>
                                                        <td class=""></span>#{{ isset($report_issue) ? $report_issue->reference_no : '' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Description</th>
                                                        <td class=""></span> {{ isset($report_issue) ? $report_issue->description : '' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Issue Creator:</th>
                                                        <td class="">{{ isset($report_issue) ? $report_issue->created_by : '' }}</td>
                                                    </tr>
                                                        <tr>
                                                        <th>Issue Date:</th>
                                                        <td class="">{{ isset($report_issue) ? \Carbon\Carbon::parse($report_issue->created_at)->format('Y-m-d H:i') : '' }}</td>
                                                    </tr>
                                                    <tr id="resolved_on_row" style="{{ $report_issue->status == 2 ? '' : 'display: none;' }}">
                                                        <th>Resolved On:</th>
                                                        <td id="resolved_on_field">
                                                            {{ $report_issue->resolved_on ? \Carbon\Carbon::parse($report_issue->resolved_on)->format('Y-m-d H:i') : '' }}
                                                        </td>
                                                    </tr>
                                                        <tr>
                                                            <th>Status:</th>
                                                            <td>
                                                                <div class="status @if($report_issue->status == 1) Unresolved @elseif($report_issue->status == 2) Resolved @endif">
                                                                    @if($report_issue->status == 1)
                                                                        Unresolved
                                                                    @elseif($report_issue->status == 2)
                                                                        Resolved
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>

                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-9">
                                        <h5>View Report Issue Attachments</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="card-block">
                                <div class="main">
                                    <div class="row">
                                        @if(isset($report_issue_images) && count($report_issue_images) > 0)
                                            @foreach($report_issue_images as $issue_doc)
                                                <div class="col-xl-3 col-md-6">
                                                    <div class="card comp-card">
                                                        <div class="card-body text-center">
                                                            <h6 class="m-b-20">View Attachment</h6>
                                                            @if(!empty($issue_doc['image']) && file_exists(public_path(parse_url($issue_doc['image'], PHP_URL_PATH))))
                                                                <a href="{{ $issue_doc['image'] }}" target="_blank">
                                                                    <i class="fa fa-eye bg-c-blue document_view"
                                                                       data-toggle="tooltip" data-placement="top"
                                                                       title="View"></i>
                                                                </a>
                                                            @else
                                                                <a>
                                                                    <i class="fa fa-eye-slash bg-c-blue"
                                                                       data-toggle="tooltip"
                                                                       data-placement="top" title="View"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="col-12 text-center">
                                                <p>No attachments available for this issue.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- Page-body End -->
                </div>
            </div><!-- Main-body End -->
        </div>
    </div>
@endsection
@section('page-js')
    <!-- data-table js -->
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}" type="text/javascript"></script>
    <!-- Confirm Issue Resolved Sweetalert -->
    <script>
        $(document).on('click', '.changeStatus', function (e) {
            e.preventDefault(); // Prevent default action
            var id = $(this).data('id'); // Get the report issue ID from the data attribute
            // Confirmation popup using SweetAlert
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
                // check if the user clicked "Yes" button
                    if (isConfirm) {
                        // Send AJAX request
                        $.ajax({
                            type: 'GET', // HTTP method
                            url: '{{ route("get:ajax:admin:update_report_issue_status") }}', // route to be called
                            data: {
                                id: id, // Pass the report issue ID
                                _token: '{{ csrf_token() }}' // CSRF token for security
                            },
                            success: function (result) {
                                // Check if the request was successful
                                if (result.success) {
                                    swal("Success", result.message, "success");
                                    // Hide the button and update the status
                                    $('#changeStatus').hide();
                                    // Update the downside "Status" value dynamically
                                    var statusElement = $('div.status'); // Target the status paragraph
                                    statusElement.removeClass('Unresolved').addClass('Resolved').html('Resolved'); // Update the status text and class

                                    // Show and update the "Reported On" field
                                    $('#resolved_on_row').show(); // Make the row visible
                                    $('#resolved_on_field').html(result.resolved_on); // Update with the new timestamp
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
                    // check if the user clicked "No" button
                    else {
                        swal("Cancelled", "Issue status remains unchanged.", "error");
                    }
                });
        });

    </script>

@endsection

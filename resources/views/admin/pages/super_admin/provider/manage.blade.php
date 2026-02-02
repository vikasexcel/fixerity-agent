@extends('admin.layout.super_admin')
@section('title')
    @if(isset($status) && $status == 1 ) Approved @elseif(isset($status) && $status == 0)
        Un-Approved @elseif(isset($status) && $status == 2) Blocked @elseif(isset($status) && $status == 3) Rejected @elseif(isset($status) && $status == "deleted") Deleted @endif
    @if(isset($service_category)) {{ ucwords(strtolower($service_category->name)) }} @else Other Service
    @endif Provider List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <!-- Data Table Excel Css -->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/responsive/buttons.dataTables.min.css?v=0.1')}}">
    <style>
        .top {
            display: flex;
        }
        .dataTables_filter {
            margin-left: auto;
        }
        .dt-buttons {
            margin-left: 1em;
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5>@if(isset($status) && $status == 1 ) Approved @elseif(isset($status) && $status == 0) Un-Approved @elseif(isset($status) && $status == 2) Blocked @elseif(isset($status) && $status == 3) Rejected @elseif(isset($status) && $status == "deleted") Deleted @endif
                                        @if(isset($service_category)) {{ ucwords(strtolower($service_category->name)) }} @else
                                            Other
                                        @endif Services Provider List</h5>
                                    <span>All Provider List</span>
                                </div>
                            </div>
                        </div>
                        {{--<div class="col-lg-4">--}}
                        {{--<a href="{{ route('get:admin:user_list') }}"--}}
                        {{--class="btn btn-primary m-b-0 btn-right render_link">Back</a>--}}
                        {{--</div>--}}
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
                                <h5>
                                    @if(isset($status) && $status == 1 ) Approved @elseif(isset($status) && $status == 0) Un-Approved @elseif(isset($status) && $status == 2) Blocked @elseif(isset($status) && $status == 3) Rejected @elseif(isset($status) && $status == "deleted") Deleted @endif
                                    @if(isset($service_category)) {{ ucwords(strtolower($service_category->name)) }} @else
                                        Other
                                    @endif Services Provider List
                                    </h5>

                                {{--<a href=""--}}
                                   {{--class="btn btn-primary m-b-0 btn-right render_link">--}}
                                    {{--Add Provider</a>--}}
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Provider Name</th>
                                            @if(isset($type_of_cat))
                                                @if($type_of_cat == "transport")
                                                    <th>Service Name</th>
                                                @elseif($type_of_cat == "store")
                                                    <th>Store Name</th>
                                                @elseif($type_of_cat == "provider-services")
                                                    <th>Service Name</th>
                                                @endif
                                            @endif

                                            <th>Email</th>
                                            <th>Contact No.</th>
                                            {{--<th>Rating</th>--}}
{{--                                            @if(isset($type_of_cat))--}}
{{--                                                @if($type_of_cat == "provider-services")--}}
{{--                                                    <th>Add Services</th>--}}
{{--                                                @endif--}}
{{--                                            @endif--}}

                                            {{--<th>Status</th>--}}
                                            {{--<th>Actions</th>--}}
                                        </tr>
                                        </thead>
                                        <tbody>

                                        @if(isset($providers))
                                        @foreach($providers as $key => $provider)
{{--                                        @for($i=2; $i <=10; $i++)--}}
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>
                                                    {{ ucwords(strtolower($provider->name)) }}
                                                </td>
                                                <td>
                                                    {{ ucwords(strtolower($provider->service_name)) }}
                                                </td>
                                                <td>
                                                    {{ App\Models\User::Email2Stars($provider->email) }}
                                                </td>
                                                <td>
                                                    {{ $provider->country_code.App\Models\User::ContactNumber2Stars($provider->contact_number) }}
                                                </td>
                                                {{--<td class="icon-url-link">--}}
                                                    {{--<a href="" class="render_link">--}}
                                                        {{--<div class="data-table-main icon-list-demo">--}}
                                                            {{--<i class="fa fa-star"></i>3.2--}}
                                                        {{--</div>--}}
                                                    {{--</a>--}}
                                                {{--</td>--}}
{{--                                                @if(isset($type_of_cat))--}}
{{--                                                    @if($type_of_cat == "provider-services")--}}
{{--                                                        <td class="action">--}}
{{--                                                            <a href="{{ route('get:admin:add_provider_services',["provider-services",$provider->id]) }}" class="render_link">--}}
{{--                                                                <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"--}}
{{--                                                                     style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Service List">--}}
{{--                                                            </a>--}}
{{--                                                        </td>--}}
{{--                                                    @endif--}}
{{--                                                @endif--}}
                                                {{--<td>--}}
                                                {{--<span class="toggle">--}}
                                                    {{--<label>--}}
                                                        {{--<input name="manual_assign" class="form-control"--}}
                                                               {{--type="checkbox" checked>--}}
                                                        {{--<span class="button-indecator"></span>--}}
                                                    {{--</label>--}}
                                                {{--</span>--}}
                                                {{--</td>--}}
                                                {{--<td class="action">--}}
                                                    {{--<a href="{{ route('get:admin:add_user') }}" class="render_link">--}}
                                                    {{--<a href="" class="render_link">--}}
                                                        {{--<img src="{{ asset('/assets/images/template-images/writing-1.png') }}"--}}
                                                             {{--style="width:20px; height: 20px;">--}}
                                                    {{--</a>--}}
                                                    {{--<a class="delete">--}}
                                                        {{--<img productid=""--}}
                                                             {{--src="{{ asset('/assets/images/template-images/remove-1.png') }}"--}}
                                                             {{--style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Delete">--}}
                                                    {{--</a>--}}
                                                {{--</td>--}}
                                            </tr>
                                        {{--@endfor--}}
                                        @endforeach
                                        @endif
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
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}"></script>
    <!-- CDN for the Excel file -->
    <script src="{{asset('assets/js/responsive/dataTables.buttons.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/jszip.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/buttons.html5.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/buttons.print.min.js')}}"></script>

    {{--User Delete Script--}}
    <script type="text/javascript">
        var newcs = $('#new-cons').DataTable({
            dom: '<"top"lBf>rt<"bottom"pi><"clear">',
            buttons: [{
                extend: 'excel',
                text: 'Download Excel'
            }],
        });
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var id = $(this).attr('categoryid');
            swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this Data!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "No, cancel!",
                    closeOnConfirm: false,
                    closeOnCancel: false
                },
                function (isConfirm) {
                    if (isConfirm) {
                        $.ajax({
                            type: 'get',
//                            url: '',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    location.reload();
                                }
                            }
                        })
                    } else {
                        swal("Cancelled", "Your Data is safe :)", "error");
                    }
                });
        });
    </script>
@endsection

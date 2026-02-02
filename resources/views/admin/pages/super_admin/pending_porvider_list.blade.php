@extends('admin.layout.super_admin')
@section('title')
    Pending Providers List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <!-- Data Table Excel Css -->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/responsive/buttons.dataTables.min.css?v=0.1')}}">
    <style>
        .action a {
            /*margin: 0;*/
        }
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
        <!-- [ other service horizontal navbar ] start -->
{{--        <div class="other-service-horizontal-nav">--}}
{{--            @if($segment === 'provider-services')--}}
{{--                @include('admin.include.other-service-horizontal-navbar')--}}
{{--            @elseif($segment === 'store')--}}
{{--                @include('admin.include.store-horizontal-navbar')--}}
{{--            @elseif($segment === 'transport')--}}
{{--                @include('admin.include.transport-horizontal-navbar')--}}
{{--            @endif--}}
{{--        </div>--}}
        <!-- [ other service horizontal navbar ] end -->
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5>Un Register Providers List</h5>
                                    <span>All Un Register Providers List</span>
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

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="card">
                            <div class="card-header">
                                <h5>Un Register Providers List</h5>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th style="width: 30px;">No</th>
                                            <th style="width: 150px;">Service Name</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Contact Number</th>
                                            <th style="width: 150px;">SignUp Time.</th>
                                            {{--<th style="width: 50px;">Actions</th>--}}
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($un_register_providers_list))
                                            @foreach($un_register_providers_list as $key => $un_register_providers)
                                                <tr id="delete_provider_{{$un_register_providers->id}}">
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>
                                                        {{ $un_register_providers->service_category_name }}
                                                    </td>
                                                    <td>
                                                        {{ ucwords($un_register_providers->name) }}
                                                    </td>
                                                    <td>
                                                        {{ App\Models\User::Email2Stars($un_register_providers->email) }}
                                                    </td>
                                                    <td>
                                                        {{ $un_register_providers->country_code.App\Models\User::ContactNumber2Stars($un_register_providers->contact_number) }}
                                                    </td>
                                                    <td>
                                                        {{ $un_register_providers->created_at }}
                                                    </td>
                                                    {{--<td class="action">--}}
                                                    {{--<a class="delete" provider_id="{{$un_register_providers->id}}">--}}
                                                    {{--<img src="{{ asset('/assets/images/template-images/remove-1.png') }}"--}}
                                                    {{--style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Delete">--}}
                                                    {{--</a>--}}
                                                    {{--</td>--}}
                                                </tr>
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
            var id = $(this).attr('provider_id');
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
                            url: '{{ route('get:admin:delete_un_register_provider') }}',
                            data: {provider_id: id},
                            success: function (result) {
                                if (result.success == true) {
//                                    location.reload();
                                    var new_id = "#delete_provider_" + id;
                                    swal("Success", "Provider remove successfully", "success");
                                    $(new_id).hide();
                                }
                                else{
                                    swal("Cancelled", "Your Data is safe :)", "error");
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


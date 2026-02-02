@extends('admin.layout.super_admin')
@section('title')
    Page List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <style>
        table th, table td {
            word-wrap: break-word !important;
            white-space: normal;
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
                            <h5>Page List</h5>
                            <span>All Page List</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <div class="page-body">

                        <div class="card">
                            <div class="card-header">
                                <h5>Fixerity Customer App Support Pages</h5>
                                {{--<a href="{{ route('get:admin:add_pages') }}"--}}
                                {{--class="btn btn-primary m-b-0 btn-right render_link">Add Page</a>--}}
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="myCheckout" class="table table-striped table-bordered" style="width:100%">
                                        <thead>
                                        <tr>
                                            <th style="width: 30px !important;">No</th>
                                            <th style="width:600px !important;">Page Name</th>
                                            <th style="width: 60px !important;">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($my_checkuout_pages_list))
                                            @foreach($my_checkuout_pages_list as $key => $page)
                                                <tr id="hide_{{$page->id}}">
                                                    <td>{{ $key+1 }}</td>
                                                    <td>{{ ucwords(strtolower(str_replace('-', " ", $page->name))) }}</td>
                                                    <td class="action">
                                                        <a href="{{ route('get:admin:edit_pages',[$page->id]) }}">
                                                            <img src="{{ asset('/assets/images/template-images/writing-1.png') }}" style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Edit">
                                                        </a>
                                                        {{--@if($page->is_status == 0)--}}
                                                        {{--<a class="delete" pageid="{{$page->id}}">--}}
                                                        {{--<img src="{{ asset('/assets/images/template-images/remove-1.png') }}"--}}
                                                        {{--style="width:20px; height: 20px;"--}}
                                                        {{--data-toggle="tooltip"--}}
                                                        {{--data-placement="top" title="Delete">--}}
                                                        {{--</a>--}}
                                                        {{--@endif--}}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5>Fixerity Provider App Support Pages</h5>
                                {{--<a href="{{ route('get:admin:add_pages') }}"--}}
                                {{--class="btn btn-primary m-b-0 btn-right render_link">Add Page</a>--}}
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="myService" class="table table-striped table-bordered" style="width:100%">
                                        <thead>
                                        <tr>
                                            <th style="width: 30px !important;">No</th>
                                            <th style="width:600px !important;">Page Name</th>
                                            <th style="width: 60px !important;">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($my_service_pages_list))
                                            @foreach($my_service_pages_list as $key => $page)
                                                <tr id="hide_{{$page->id}}">
                                                    <td>{{ $key+1 }}</td>
                                                    <td>{{ ucwords(strtolower(str_replace('-', " ", $page->name))) }}</td>
                                                    <td class="action">
                                                        <a href="{{ route('get:admin:edit_pages',[$page->id]) }}">
                                                            <img src="{{ asset('/assets/images/template-images/writing-1.png') }}" style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Edit">
                                                        </a>
                                                        {{--@if($page->is_status == 0)--}}
                                                        {{--<a class="delete" pageid="{{$page->id}}">--}}
                                                        {{--<img src="{{ asset('/assets/images/template-images/remove-1.png') }}"--}}
                                                        {{--style="width:20px; height: 20px;"--}}
                                                        {{--data-toggle="tooltip"--}}
                                                        {{--data-placement="top" title="Delete">--}}
                                                        {{--</a>--}}
                                                        {{--@endif--}}
                                                    </td>
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
            </div>
        </div>
    </div>
@endsection
@section('page-js')
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}" type="text/javascript"></script>
    <script>
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var id = $(this).attr('pageid');
            swal({
                    title: "Page Remove?",
                    text: "if press yes then page is remove!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes",
                    cancelButtonText: "No",
                    closeOnConfirm: false,
                    closeOnCancel: false
                },
                function (isConfirm) {
                    if (isConfirm) {
                        $.ajax({
                            type: 'get',
                            url: '{{ route('get:admin:delete_support_page') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
//                                    location.reload();
                                    var new_id = "#hide_" + id;
                                    swal("Success", "Page remove successfully", "success");
                                    $(new_id).hide();
                                }
                            }
                        })
                    } else {
                        swal("Cancelled", "Page not removed", "error");
                    }
                });
        });
    </script>
@endsection

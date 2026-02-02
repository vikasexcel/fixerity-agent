@extends('admin.layout.super_admin')
@section('title')
    Promocode List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
@endsection
@section('page-content')

    <div class="pcoded-content">
        {{--<div class="other-service-horizontal-nav">--}}
        <div class="external-horizontal-nav">
            @include('admin.include.other-service-horizontal-navbar')
        </div>
        {{--</div>--}}
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5>Promocode List</h5>
                                    <span>All Promocode List @if(isset($service_category) && $service_category->name != Null) of {{ ucwords(strtolower($service_category->name)) }} @endif
                                    </span>
                                </div>
                            </div>
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
                                <h5>Promocode List @if(isset($service_category) && $service_category->name != Null) of {{ ucwords(strtolower($service_category->name)) }} @endif</h5>
                                @if(isset($service_category) && in_array($service_category->category_type,[1,5]))
                                    <a href="{{ route('get:admin:transport:add_promocode',$slug) }}" class="btn btn-primary m-b-0 btn-right ">Add New Promocode</a>
                                @elseif(isset($service_category) && in_array($service_category->category_type,[2]))
                                    <a href="{{ route('get:admin:store:add_promocode',$slug) }}" class="btn btn-primary m-b-0 btn-right ">Add New Promocode</a>
                                @else
                                    <a href="{{ route('get:admin:other:add_promocode',$slug) }}" class="btn btn-primary m-b-0 btn-right ">Add New Promocode</a>
                                @endif
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th style="width: 30px;">No</th>
                                            <th>Promocode</th>
                                            <th>Limit</th>
                                            <th>Usage Limit</th>
                                            <th>Total Usage</th>
                                            <th>Expiry Date</th>
                                            <th style="width: 50px;">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($promocode_list))
                                            @foreach($promocode_list as $key => $promocode)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ ucwords($promocode->promo_code) }}</td>
                                                    <td>{{ $promocode->coupon_limit != 0 ? $promocode->coupon_limit : "Unlimited" }}</td>
                                                    <td>{{ $promocode->usage_limit }}</td>
                                                    <td>{{ $promocode->total_usage }}</td>
                                                    <td>{{ $promocode->expiry_date }}</td>
                                                    <td class="action">
                                                        <span class="toggle">
                                                            <label>
                                                                <input name="required_document" class="form-control promocode_status" id="promocode_id_{{$promocode->id}}" promocode_id="{{$promocode->id}}" promocode_status="{{$promocode->status}}" type="checkbox" {{ ("1" == $promocode->status) ? 'checked' : '' }}>
                                                                <span class="button-indecator" data-toggle="tooltip" data-placement="top" title="{{ ("1" == $promocode->status) ? 'Active' : 'InActive' }}"></span>
                                                            </label>
                                                        </span>
                                                        @if(isset($service_category) && in_array($service_category->category_type,[1,5]) && !in_array($service_category->id,[31,32]))
                                                            <a href="{{ route('get:admin:transport:edit_promocode',[$slug,$promocode->id]) }}">
                                                                <img src="{{ asset('/assets/images/template-images/writing-1.png') }}" style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Edit">
                                                            </a>
                                                        @elseif(isset($service_category) && in_array($service_category->category_type,[1,5]) && in_array($service_category->id,[31,32]))
                                                            <a href="{{ route('get:admin:transport-rental:edit_promocode',[$slug,$promocode->id]) }}">
                                                                <img src="{{ asset('/assets/images/template-images/writing-1.png') }}" style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Edit">
                                                            </a>
                                                        @elseif(isset($service_category) && in_array($service_category->category_type,[2]))
                                                            <a href="{{ route('get:admin:store:edit_promocode',[$slug,$promocode->id]) }}">
                                                                <img src="{{ asset('/assets/images/template-images/writing-1.png') }}" style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Edit">
                                                            </a>
                                                        @else
                                                            <a href="{{ route('get:admin:other:edit_promocode',[$slug,$promocode->id]) }}">
                                                                <img src="{{ asset('/assets/images/template-images/writing-1.png') }}" style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Edit">
                                                            </a>
                                                        @endif
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
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}"></script>

    {{--User Delete Script--}}
    <script type="text/javascript">
        $(document).on('click', '.promocode_status', function (e) {
            e.preventDefault();
            var id = $(this).attr('promocode_id');
            var status = $(this).attr('promocode_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Promocode?";
                txt = "if press yes then disable promocode!";
            } else {
                title = "Enable Promocode?";
                txt = "if press yes then enable promocode!";
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
                    if (isConfirm) {
                        $.ajax({
                            type: 'get',
                            url: '{{ route("get:admin:other:promocode_change_status") }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    var promocode_id = '#promocode_id_' + id;
                                    if (result.status == 1) {
                                        $(promocode_id).prop("checked", true);
                                        $(promocode_id).attr("promocode_status", 1);
                                        swal("Success", "Enable Promocode Successfully", "success");
                                    } else {
                                        $(promocode_id).prop("checked", false);
                                        $(promocode_id).attr("promocode_status", 0);
                                        swal("Success", "Disable Promocode Successfully", "success");
                                    }
                                } else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Promocode is Enable", "error");
                        } else {
                            swal("Cancelled", "Promocode is Disable", "error");
                        }
                    }
                });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#new-cons').DataTable({
                "columnDefs": [
                    { "orderable": false, "targets": [6] }
                ]
            });
        });
    </script>
@endsection


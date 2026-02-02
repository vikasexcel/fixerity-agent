@extends('admin.layout.super_admin')
@section('title')
    Service Category Ordering
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
@endsection
@section('page-content')

    <div class="pcoded-content">
    {{--<div class="external-horizontal-nav">--}}
    {{--@include('admin.include.store-horizontal-navbar')--}}
    {{--</div>--}}
    <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-list bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Service </h5>
                            <span>All Service List</span>
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
                                <h5>Service List</h5>

                            </div>
                            <form id="main" method="post"
                                  action="{{ route('post:admin:ordering_service_category_sorting') }}"
                                  enctype="multipart/form-data">
                                {{csrf_field() }}
                                <div class="card-block">
                                    <div class="dt-responsive table-responsive">
                                        <table class="table table-striped table-bordered nowrap"
                                               style="width:100%">
                                            <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Sorting No</th>
                                                <th>Service Name</th>
                                                <th>Status</th>
<!--                                                <th>Actions</th>-->
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @if(isset($service_category_lists))
                                                @foreach($service_category_lists as $key => $single_service_cat)
                                                    <tr id="delete_cat_{{$single_service_cat->id}}">
                                                        <td>{{ $key + 1 }}</td>
                                                        <td>{{ $single_service_cat->display_order  }}</td>
                                                        <td>{{ $single_service_cat->name }}
                                                            <input type="hidden" name="category_sorting_type[]"
                                                                   class="type"
                                                                   value="{{ $single_service_cat->id }}">
                                                        </td>
                                                        <td>
                                                <span class="toggle">
                                                    <label>
                                                        <input name="manual_assign"
                                                               class="form-control sub_cat_status"
                                                               id="sub_cat_{{$single_service_cat->id}}"
                                                               sub_cat_id="{{$single_service_cat->id}}"
                                                               sub_cat_status="{{$single_service_cat->status}}"
                                                               type="checkbox" {{ ("1" == $single_service_cat->status) ? 'checked' : '' }}>

                                                        <span class="button-indecator" data-toggle="tooltip"
                                                              data-placement="top" title="Active"></span>
                                                    </label>
                                                </span></td>
<!--                                                        <td class="action">
                                                            <a class="delete" categoryid="{{$single_service_cat->id}}">
                                                                <img src="{{ asset('/assets/images/template-images/remove-1.png') }}"
                                                                     style="width:20px; height: 20px;"
                                                                     data-toggle="tooltip"
                                                                     data-placement="top" title="Delete">
                                                            </a>
                                                        </td>-->
                                                    </tr>
                                                @endforeach
                                            @endif
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <button type="submit" class="btn btn-primary m-b-0 pull-right">
                                                Re-Order Service
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Page body end -->
            </div>
        </div>
    </div>

@endsection
@section('page-js')
{{--    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}"></script>--}}
{{--    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}"></script>--}}
{{--    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}"></script>--}}
{{--    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}"></script>--}}
    <script>
        $("table tbody").sortable({
            update: function (event, ui) {
                $(this).children().each(function (index) {
                    $(this).find('td').first().html(index + 1);
                });
            }
        });
    </script>
    <script type="text/javascript">

        $(document).on('click', '.sub_cat_status', function (e) {
            e.preventDefault();
            var id = $(this).attr('sub_cat_id');
            var status = $(this).attr('sub_cat_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Service category?";
                txt = "if press yes then disable this Service category and not display in user panel!";
            } else {
                title = "Enable Service category?";
                txt = "if press yes then enable this Service category and display in user panel!";
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
                            url: '{{ route('get:admin:ordering_service_category_list_status') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
//                                    location.reload();
                                    var new_id = '#sub_cat_' + id;
                                    if (result.status == 1) {
                                        $(new_id).prop("checked", true);
                                        $(new_id).attr("sub_cat_status", 1);
                                        swal("Success", "Enable Service Category Successfully", "success");
                                    } else {
                                        $(new_id).prop("checked", false);
                                        $(new_id).attr("sub_cat_status", 0);
                                        swal("Success", "Disable Service Category Successfully", "success");
                                    }
                                }else {
                                    swal("Warning", result.message, "warning");
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Service category is Enable", "error");
                        } else {
                            swal("Cancelled", "Service category is Disable", "error");
                        }
                    }
                });
        });
    </script>
@endsection


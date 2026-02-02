@extends('admin.layout.super_admin')
@section('title')
    Required Documents List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <style>
        .action a {
            /*margin: 0;*/
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <!-- [ other service horizontal navbar ] start -->
        <div class="other-service-horizontal-nav">
            @if($segment === 'provider-services')
                @include('admin.include.other-service-horizontal-navbar')
            @endif
        </div>
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
                                    <h5>Required Documents List</h5>
                                    <span>All Required Documents List @if(isset($service_category) && $service_category->name != Null)
                                            of {{ ucwords(strtolower($service_category->name)) }} @endif
                                    </span>
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
                                <h5>Required Documents
                                    List @if(isset($service_category) && $service_category->name != Null)
                                        of {{ ucwords(strtolower($service_category->name)) }} @endif</h5>
                                @if($segment === 'provider-services')
                                    <a href="{{ route('get:admin:other_service_add_required_document',$slug) }}"
                                       class="btn btn-primary m-b-0 btn-right render_link">Add Document</a>
                                @elseif($segment === 'store')
                                    <a href="{{ route('get:admin:store_add_required_document',$slug) }}"
                                       class="btn btn-primary m-b-0 btn-right render_link">Add Document</a>
                                @elseif($segment === 'transport')
                                    <a href="{{ route('get:admin:transport_add_required_document',$slug) }}"
                                       class="btn btn-primary m-b-0 btn-right render_link">Add Document</a>
                                @else
                                    <a href="{{ route('get:admin:transport_add_required_documen',$slug) }}"
                                       class="btn btn-primary m-b-0 btn-right render_link">Add Document</a>
                                @endif
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th style="width: 30px;">No</th>
                                            <th>Name</th>
                                            <th style="width: 150px;">Required For</th>
                                            <th style="width: 50px;">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($required_documents_list))
                                            @foreach($required_documents_list as $key => $required_document)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>
                                                        {{ ucwords($required_document->document_name) }}
                                                    </td>
                                                    <td>
                                                        {{ $required_document->name }}
                                                    </td>
                                                    <td class="action">

                                                        <span class="toggle">
                                                            <label>
                                                                <input name="required_document"
                                                                       class="form-control document_status"
                                                                       id="document_id_{{$required_document->id}}"
                                                                       document_id="{{$required_document->id}}"
                                                                       document_status="{{$required_document->status}}"
                                                                       type="checkbox" {{ ("1" == $required_document->status) ? 'checked' : '' }}>
                                                                <span class="button-indecator" data-toggle="tooltip"
                                                                      data-placement="top"
                                                                      title="{{ ("1" == $required_document->status) ? 'Active' : 'InActive' }}"></span>
                                                            </label>
                                                        </span>

                                                        @if($segment === 'provider-services')
                                                            <a href="{{ route('get:admin:other_service_edit_required_document',['slug' => $slug, 'id' => $required_document->id]) }}"
                                                               class="render_link">
                                                                <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                     style="width:20px; height: 20px;"
                                                                     data-toggle="tooltip" data-placement="top"
                                                                     title="Edit">
                                                            </a>
                                                        @elseif($segment === 'store')
                                                            <a href="{{ route('get:admin:store_edit_required_document',['slug' => $slug, 'id' => $required_document->id]) }}"
                                                               class="render_link">
                                                                <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                     style="width:20px; height: 20px;"
                                                                     data-toggle="tooltip" data-placement="top"
                                                                     title="Edit">
                                                            </a>
                                                        @elseif($segment === 'transport')
                                                            <a href="{{ route('get:admin:transport_edit_required_document',['slug' => $slug, 'id' => $required_document->id]) }}"
                                                               class="render_link">
                                                                <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                     style="width:20px; height: 20px;"
                                                                     data-toggle="tooltip" data-placement="top"
                                                                     title="Edit">
                                                            </a>
                                                        @else
                                                            <a href="{{ route('get:admin:transport_edit_required_document',['slug' => $slug, 'id' => $required_document->id]) }}"
                                                               class="render_link">
                                                                <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                     style="width:20px; height: 20px;"
                                                                     data-toggle="tooltip" data-placement="top"
                                                                     title="Edit">
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

    {{--User Delete Script--}}
    <script type="text/javascript">
        $(document).on('click', '.document_status', function (e) {
            e.preventDefault();
            var id = $(this).attr('document_id');
            var status = $(this).attr('document_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Document?";
                txt = "if press yes then disable document!";
            }
            else {
                title = "Enable Document?";
                txt = "if press yes then enable Document!";
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
                            url: '{{ route("get:ajax:admin:update_required_document_status") }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    var document_id = '#document_id_' + id;
                                    if (result.status == 1) {
                                        $(document_id).prop("checked", true);
                                        $(document_id).attr("document_status", 1);
                                        swal("Success", "Enable Document Successfully", "success");
                                    }
                                    else {
                                        $(document_id).prop("checked", false);
                                        $(document_id).attr("document_status", 0);
                                        swal("Success", "Disable Document Successfully", "success");
                                    }
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Document is Enable", "error");
                        }
                        else {
                            swal("Cancelled", "Document is Disable", "error");
                        }
                    }
                });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#new-cons').DataTable({
                "columnDefs": [
                    { "orderable": false, "targets": [2, 3] }
                ]
            });
        });
    </script>
@endsection


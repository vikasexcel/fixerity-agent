@extends('admin.layout.super_admin')
@section('title')
    Language Lists
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
@endsection
@section('page-content')

    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Language Lists</h5>
                            <span>Language Lists</span>
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
                        <div class="row">
                            <div class="form-group col-sm-12">
                                <form id="main" method="post"
                                      action="{{ route('post:admin:update_language-lists') }}"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Language Lists</h5>
                                            {{--<a href="{{ route('get:admin:user_list') }}"--}}
                                            {{--class="btn btn-primary m-b-0 btn-right render_link"> Back</a>--}}
                                        </div>
                                        <div class="card-block">

                                            <div class="row">
                                                <div class="form-group col-sm-12">
                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Language Name:<sup
                                                                    class="error">*</sup></label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" name="language_name" id="language_name" value="" placeholder="Please enter language name" required autocomplete="off">
                                                            <span class="error">{{ $errors->first('language_name') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-sm-12">
                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Language Code:<sup
                                                                class="error">*</sup></label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" name="language_code" id="language_code"  placeholder="Please enter language code" value="" autocomplete="off" required>
                                                            <span class="error">{{ $errors->first('language_code') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <center>
                                                <button type="submit" class="btn btn-primary m-b-0">Add</button>
                                            </center>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="form-group col-sm-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Language List</h5>
                                    </div>
                                    <div class="card-block">

                                        <div class="dt-responsive table-responsive">
                                            <table id="new-cons" class="table table-striped table-bordered nowrap"
                                                   style="width:100%">
                                                <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Language Title</th>
                                                    <th>Language Code</th>
                                                    <th>Action</th>
                                                </tr>
                                                </thead>
                                                <tbody>

                                                @if(isset($language_lists))
                                                    <tr>
                                                        <td>1 </td>
                                                        <td>English</td>
                                                        <td>en</td>
                                                        <td></td>
                                                    </tr>
                                                    @foreach($language_lists as $key => $singel_language)
                                                        <tr>
                                                            <td>{{ $key + 2 }}</td>
                                                            <td>{{ $singel_language->language_name }}</td>
                                                            <td>{{ $singel_language->language_code }}</td>
                                                            <td><span class="toggle">
                                                                    <label>
                                                                        <input name="status"
                                                                               class="form-control user"
                                                                               id="lang_id_{{$singel_language->id}}"
                                                                               lang_id="{{$singel_language->id}}"
                                                                               lang_status="{{$singel_language->status}}"
                                                                               type="checkbox" {{ ("1" == $singel_language->status) ? 'checked' : '' }}>
                                                                        <span class="button-indecator" data-toggle="tooltip"
                                                                              data-placement="top"
                                                                              id="title_status_{{$singel_language->id}}"
                                                                              title="{{ ("1" == $singel_language->status) ? 'Active' : 'InActive' }}"></span>
                                                                    </label>
                                                                </span>
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
    <script type="text/javascript">
        $(document).on('click', '.user', function (e) {
            e.preventDefault();
            var id = $(this).attr('lang_id');
            var status = $(this).attr('lang_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Language?";
                txt = "if press yes then disable Language!";
            } else {
                title = "Enable Language?";
                txt = "if press yes then enable Language!";
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
                            url: '{{ route("get:ajax:admin:language_lists_status") }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    var lang_id_ = '#lang_id_' + id;
                                    var title_status = '#title_status_' + id;
                                    if (result.status == 1) {
                                        $(lang_id_).prop("checked", true);
                                        $(lang_id_).attr("lang_status", 1);
                                        // $(title_status).attr("title", "Active");
                                        swal("Success", "Enable Language successfully", "success");
                                    } else {
                                        $(lang_id_).prop("checked", false);
                                        $(lang_id_).attr("lang_status", 0);
                                        // $(title_status).attr("title", "InActive");
                                        swal("Success", "Disable Language successfully", "success");
                                    }
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Language is Enable", "error");
                        } else {
                            swal("Cancelled", "Language is Disable", "error");
                        }
                    }
                });
        });
    </script>
@endsection


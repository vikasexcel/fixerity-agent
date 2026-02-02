@extends('admin.layout.other_service')
@section('title')
    @if($service_category) {{ ucwords(strtolower($service_category->name)) }}@endif Documents
@endsection
@section('page-css')
    <link rel="stylesheet" href="{{ asset('/assets/css/font/font-awesome-n.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/widget/widget.css') }}">
    <style>
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

        .document_view, .document_status {
            cursor: pointer;
        }

        .preview {
            display: block;
            height: 150px;
            width: 40%;
            margin: 10px auto 20px;
            border: 1px solid gainsboro;
            /*text-align: center;*/
        }

        #fileid {
            display: none;
        }

        .preview span {
            display: block;
            text-align: center;
            width: 80%;
            margin: 15px auto;
            border: none;
        }

        .panel-body p span {
            display: block;
        }

        .modal-title {
            float: left;
        }

        .doc-save {
            float: left !important;
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        @if(Illuminate\Support\Facades\Auth::guard("on_demand")->check())
            <div class="external-horizontal-nav">
                @include('admin.include.other-service-provider-navbar')
            </div>
    @endif
    <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5> @if($service_category) {{ ucwords(strtolower($service_category->name)) }}@endif
                                        Documents</h5>
                                    <span> Provider's Documents List</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            {{--<a--}}
                            {{--href="{{ route('get:admin:store_list',[$slug,"approved"]) }}"--}}
                            {{--class="btn btn-primary m-b-0 btn-right render_link">Back</a>--}}
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
                            @if(count($required_documents)>0)
                                @foreach($required_documents as $key => $required_document)
                                    <div class="col-xl-3 col-md-6">
                                        <div class="card comp-card">
                                            <div class="card-body text-center">
                                                <h6 class="m-b-20">{{ ucwords($required_document->name) }}</h6>
                                                @if($provider_documents[$key] == Null)
                                                    <p>{{ $required_document->name }} not found</p>
                                                    <input type="hidden"
                                                           value=""
                                                           id="document-{{ $key }}">
                                                    </span>
                                                @else
                                                    @if($provider_documents[$key]->status == 0)
                                                        <p class="provider_document_id_{{ $provider_documents[$key]->id }} text-c-yellow">
                                                            Pending</p>
                                                    @elseif($provider_documents[$key]->status == 1)
                                                        <p class="provider_document_id_{{ $provider_documents[$key]->id }} text-c-green"
                                                           id="approved_id_{{ $provider_documents[$key]->id }}">
                                                            Approved</p>
                                                    @elseif($provider_documents[$key]->status == 2)
                                                        <p class="provider_document_id_{{ $provider_documents[$key]->id }} text-c-red"
                                                           id="reject_id_{{ $provider_documents[$key]->id }}">
                                                            Rejected</p>
                                                    @endif
                                                    <span>
                                                    <a @if(isset($provider_documents[$key]) && $provider_documents[$key]->document_file != Null)
                                                       href="{{ (file_exists(public_path('/assets/images/provider-documents/'.$provider_documents[$key]->document_file)))? asset('/assets/images/provider-documents/'.$provider_documents[$key]->document_file) : ''}}"
                                                       target="_blank"
                                                            @endif><i class="fa fa-eye bg-c-blue document_view"
                                                                      data-toggle="tooltip"
                                                                      data-placement="top" title="View"></i></a>
                                                    <input type="hidden"
                                                           value="{{ asset('assets/images/provider-documents/'.$provider_documents[$key]['document_file']) }}"
                                                           id="document-{{ $key }}">
                                                    </span>
                                                @endif
                                                <input type="hidden" id="document-{{ $key }}" value="">
                                                <a class="open-form" data-toggle="modal"
                                                   data-target="#open"
                                                   slug="{{ $required_document->id }}"
                                                   doc_name="{{ $required_document->name }}"
                                                   id="{{ $key }}"
                                                >
                                                    <i class="fas fa-upload bg-c-blue document_view"
                                                       data-toggle="tooltip"
                                                       data-placement="top" title="Upload"></i>
                                                    {{--                                                    {{ ucwords($required_document->name) }}--}}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                            @endforeach
                            @else
                                <div class="col-12 p-0 text-center">
                                    <h5>No Documents Required</h5>
                                </div>
                        @endif
                        <!-- project-ticket end -->
                        </div>
                    </div>
                </div>
                <!-- Page body end -->
            </div>
        </div>

        <div class="modal fade" id="open" role="dialog">
            <div class="modal-dialog">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title"></h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form method="post"
                          action="{{ route('post:provider-admin:other_service_provider_document',$slug) }}"
                          enctype="multipart/form-data">
                        {{csrf_field() }}
                        <div class="modal-body">
                            <input type="hidden" name="id" id="slug">
                            <div class="preview">
                                            <span>
                                            <img id="upload-preview" src="">
                                            </span>
                            </div>
                            <input id='fileid' name="document_file" type='file' accept=".png, .jpg, .jpeg, .pdf, .doc, .docx"/>
                            <center>
                                <input id='buttonid' class="btn btn-default" type='button'
                                       value="Upload Document"/>
                            </center>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-default doc-save">Save</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('page-js')
    <script type="text/javascript" src="{{ asset('/assets/js/widget/jquery.wavify.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/assets/js/widget/TweenMax.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/assets/js/widget/widget-statistic.js') }}"></script>
    <script type="text/javascript">
        $(document).on('click', '.document_status', function (e) {
            e.preventDefault();
            var id = $(this).attr('document_approved_id');
            var status = $(this).attr('document_status');
            var txt, title;
            console.log(id);
            if (status == 1) {
                title = "Approved Document File?";
                txt = "if press yes then approved document file!";
            } else if (status == 2) {
                title = "Reject Document File?";
                txt = "if press yes then Reject document file!";
            }
            if (id.trim() != "") {
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
                                url: '{{ route("get:ajax:admin:update_approved_reject_provider_document") }}',
                                data: {id: id, status: status},
                                success: function (result) {
                                    if (result.success == true) {
                                        var provider_document_id = '.provider_document_id_' + id;
                                        if (result.status['status'] == 1) {
                                            $(provider_document_id).empty();
                                            $(provider_document_id).text("Approved");
                                            swal("Success", "Approved Document File Successfully", "success");
                                        } else if (result.status['status'] == 2) {
                                            $(provider_document_id).empty();
                                            $(provider_document_id).text("Rejected");
                                            swal("Success", "Reject Document File Successfully", "success");
                                        } else {
                                            $(provider_document_id).empty();
                                            $(provider_document_id).text("Pending");
                                            swal("Success", "Reject Document File Successfully", "success");
                                        }
                                    }
                                }
                            })
                        } else {
                            if (status == 1) {
                                swal("Cancelled", "Document File is Approved", "error");
                            } else {
                                swal("Cancelled", "Document File is Reject", "error");
                            }
                        }
                    });
            }
        });
    </script>

    <script>
        document.getElementById('buttonid').addEventListener('click', openDialog);

        function openDialog() {
            document.getElementById('fileid').click();
        }

        var imgtag = "#upload-preview";

        $('.open-form').on('click', function () {
            var key = $(this).attr('id');
            var slug = $(this).attr('slug');
            var name = $(this).attr('doc_name');
            $('#slug').val(slug);
            $('.modal-title').text(name);

            var img_src = $('#document-' + key).val();
            if (img_src != "") {
                $("#buttonid").val('Update Document');
                $(imgtag).show();
                $(imgtag).attr('src', img_src).css({
                    'width': "100%",
                    'height': "120px"
                });
            } else {
                $("#buttonid").val('Upload Document');
                $('#upload-preview').hide();
            }
        });

        function readURL(input) {

            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $(imgtag).show();
                    $(imgtag).attr('src', e.target.result).css({
                        'width': "100%",
                        'height': "120px"
                    });
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#fileid").change(function () {
            readURL(this);
        });
    </script>
@endsection

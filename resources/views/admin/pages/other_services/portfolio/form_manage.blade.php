@extends('admin.layout.other_service')
@section('title')
    Portfolio List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet"/>
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
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Portfolio Lists</h5>
                            <span>Portfolio Lists</span>
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
                                      action="{{ route('post:provider-admin:other_service_portfolio',['slug'=>$slug]) }}"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Portfolio Lists</h5>
                                        </div>
                                        <div class="card-block">
                                            <div class="row">
                                                <div class="form-group col-sm-12">
                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Portfolio Image:<sup
                                                                class="error">*</sup></label>
                                                        <div class="col-sm-8">
                                                            <input type="file" class="form-control"
                                                                   name="portfolio_image" id="portfolio_image"
                                                                   placeholder="Enter Portfolio Image"
                                                                   accept="image/png,image/jpeg,image/jpg"
                                                                   required autocomplete="off" value="{{ old('portfolio_image') }}">
                                                            <span
                                                                class="error">{{ $errors->first('portfolio_image') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <center>
                                                <button type="submit" class="btn btn-primary m-b-0">Upload Portfolio</button>
                                            </center>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="form-group col-sm-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Portfolio List</h5>
                                    </div>
                                    <div class="card-block">

                                        <div class="dt-responsive table-responsive">
                                            <table id="new-cons" class="table table-striped table-bordered nowrap"
                                                   style="width:100%">
                                                <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Portfolio</th>
                                                    <th>Action</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @if(isset($portfolio_lists))
                                                    @foreach($portfolio_lists as $key => $portfolio)
                                                        <tr id="remove_portfolio_{{ $portfolio->id }}">
                                                            <td>{{ $key + 1 }}</td>
                                                            <td><img src="{{ asset('/assets/images/provider-portfolio-images/'.$portfolio->image) }}" height="100px" width="250px"></td>
                                                            <td>
                                                                <a class="delete" portfolio_id="{{$portfolio->id}}">
                                                                    <img src="{{ asset('/assets/images/template-images/remove-1.png') }}"
                                                                         style="width:20px; height: 20px;"
                                                                         data-toggle="tooltip"
                                                                         data-placement="top" title="Delete">
                                                                </a>
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
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var portfolio_id = $(this).attr('portfolio_id');
            swal({
                    title: "Are you sure to delete this portfolio?",
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
                            url: '{{ route('get:provider-admin:other_service_provider_delete_portfolio') }}',
                            data: {portfolio_id: portfolio_id},
                            success: function (result) {
                                if (result.success == true) {
                                    swal("success", result.errorMsg, "success");
                                    // location.reload();
                                    $('#remove_portfolio_'+portfolio_id).hide();
                                }else{
                                    swal("Cancelled", result.errorMsg, "error");
                                }
                            }
                        })
                    } else {
                        swal("Cancelled", "Your portfolio is safe :)", "error");
                    }
                });
        });
    </script>
@endsection


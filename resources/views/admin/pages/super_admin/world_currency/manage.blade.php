@extends('admin.layout.super_admin')
@section('title')
    World Currency List
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
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5> World Currency</h5>
                                    <span>All World Currency List</span>
                                </div>
                            </div>
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
                        <form id="main" method="post"
                              action="{{ route('post:admin:world_currency_list')}}"
                              enctype="multipart/form-data">
                            {{csrf_field() }}

                            <div class="form-group row">
                                <div class="form-group col-sm-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>World Currency List</h5>
                                        </div>
                                        <div class="card-block">
                                            <div class="dt-responsive table-responsive">
                                                <table
                                                        {{--                                            id="new-cons"--}}
                                                        class="table table-striped table-bordered nowrap"
                                                        style="width:100%">
                                                    <thead>
                                                    <tr>
                                                        <th>No</th>
{{--                                                        <th>Currency Code</th>--}}
                                                        <th>Currency Name</th>
                                                        <th>Symbol</th>
                                                        <th>Ratio</th>
                                                        <th>Default</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @if(isset($currencies))
                                                        @foreach($currencies as $key => $currency)
                                                            <tr>
                                                                <td>{{ $key+1 }}</td>
{{--                                                                <td>{{ ucwords(strtoupper($currency->currency_code)) }}</td>--}}
                                                                <td>{{ ucwords(strtolower($currency->currency_name)) }}</td>
                                                                <td>{{ ($currency->symbol) }}</td>
                                                                @if($key == 0)
                                                                    <td>
                                                                        <input type="text" class="form-control" readonly
                                                                               value="{{$currency->ratio}}">
                                                                    </td>
                                                                    {{--<td>{{ ($currency->ratio) }}</td>--}}
                                                                @else
                                                                    <td>
                                                                        <input type="text" class="form-control"
                                                                               name="ratio[{{ $currency->id }}]"
                                                                               id="ratio" placeholder="Ratio"
                                                                               step="0.01" required
                                                                               value="{{$currency->ratio}}">
                                                                    </td>
                                                                @endif
                                                                <td>{{ ($currency->default_currency == 1 ? "Yes" : "No") }}</td>
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
                            <div class="form-group row">
                                <div class="col-sm-12">
                                    <center>
                                        <button type="submit" class="btn btn-primary m-b-0">Save</button>
                                    </center>
                                </div>
                            </div>
                        </form>

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
                            url: '',
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


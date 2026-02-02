@extends('admin.layout.super_admin')
@section('title')
    @if(isset($admin_user)) Edit Sub Admin @else Add Sub Admin @endif
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
                                    <h5>
                                        @if(isset($admin_user)) Edit  {{ ucwords(strtolower(str_replace('-',' ',$admin_user->name))) }} @else Add Sub Admin @endif
                                    </h5>
                                    <span>
                                        @if(isset($admin_user)) Edit Sub Admin @else Add Sub Admin @endif
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
                            {{--<div class="card-header">--}}
                            {{--<h5>Provider List</h5>--}}
                            {{--<a href=""--}}
                            {{--class="btn btn-primary m-b-0 btn-right render_link">--}}
                            {{--Add Provider</a>--}}
                            {{--</div>--}}
                            <div style="padding: 8px;"></div>
                            <div class="card-block">
                                <form id="main" method="post" action="{{ route('post:admin:update_sub_admin') }}"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}

                                    @if(isset($admin_user))
                                        <input type="hidden" name="id" value="{{$admin_user->id}}">
                                    @endif

                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label">Name:<sup
                                                class="error">*</sup></label>
                                        <div class="col-sm-12">
                                            <input type="text" class="form-control" name="name" required
                                                   id="name" placeholder="Admin Name"
                                                   value="{{ (isset($admin_user)) ? $admin_user->name : old('name') }}">
                                            <span class="error">{{ $errors->first('name') }}</span>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label">Email:<sup
                                                class="error">*</sup></label>
                                        <div class="col-sm-12">
                                            <input type="email" pattern="[^@]+@[^@]+\.[a-zA-Z]{2,6}" class="form-control" name="email" required
                                                   id="email" placeholder="Admin email address"
                                                   value="{{ (isset($admin_user)) ? $admin_user->email : old('email') }}">
                                            <span class="error">{{ $errors->first('email') }}</span>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label">Password:
                                                @if(!isset($admin_user->id))
                                                    <sup class="error">*</sup>
                                                @endif
                                        </label>
                                        <div class="col-sm-12">
                                            <input type="password" class="form-control" name="password"
                                                   {{(isset($admin_user) && $admin_user->id > 0)?"":"required"}}
                                                   id="password" placeholder="Password"
                                                   value="">
                                            <span class="error">{{ $errors->first('password') }}</span>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Modules Permission</h5>
                                        </div>
                                        <div class="card-block">

                                            <div class="form-group row">
                                                <div class="col-sm-12">
                                                    @if(isset($module_with_action))
                                                        @foreach($module_with_action as $single_module)
                                                            @if( $single_module['is_checkbox_show'] != 1)
                                                                <div class="row">
                                                                    <div class="col-sm-5"><b>{{ $single_module['name'] }}</b></div>
                                                                </div>
                                                                @foreach($single_module['sub_module_with_action'] as $sub_single_module)
                                                                    <div class="row">
                                                                        <div class="col-sm-4 offset-sm-1"><i class="fas fa-arrow-right" style="font-size: 12px;"></i> <b>{{ $sub_single_module['name'] }} :</b></div>
                                                                        @foreach($sub_single_module['checkbox'] as $singleCheckBox)
                                                                            <div class="col-sm-6 admin_permission_{{$sub_single_module['module_id']}}" style="{{($sub_single_module['module_id'] == $res_module && $singleCheckBox['checked']  != "checked")?"display:none":"display:block"}}" >

                                                                                <input type="checkbox"  class="admin_permission_fld_{{$sub_single_module['module_id']}}" id="{{$sub_single_module['module_id']}}_{{$singleCheckBox['id']}}"  name="admin_permission[{{$sub_single_module['module_id']}}][]"  value="{{$singleCheckBox['id']}}" {{$singleCheckBox['checked']}} >{{$singleCheckBox['name']}}

                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @endforeach
                                                                <hr>
                                                            @else
                                                                @if($single_module['menu_category_wise_list'] != Null)
                                                                    <div class="row">
                                                                        <div class="col-sm-5"><b>{{ $single_module['name'] }}</b></div>
                                                                    </div>
                                                                    @foreach($single_module['menu_category_wise_list'] as $sub_single_module)
                                                                        <div class="row">
                                                                            <div class="col-sm-4 offset-sm-1"><i class="fas fa-arrow-right" style="font-size: 12px;"></i> <b>{{ $sub_single_module['name'] }} :</b></div>
                                                                            <div class="col-sm-6">
                                                                                @foreach($sub_single_module['checkbox'] as $singleCheckBox)
                                                                                    <input type="checkbox" data-cat="{{$sub_single_module['category_id']}}" class="serviceCheckkbox" id="{{$sub_single_module['module_id']}}_{{$singleCheckBox['id']}}" name="admin_cat_permission[{{$sub_single_module['module_id']}}][{{$sub_single_module['category_id']}}][]"  value="{{$singleCheckBox['id']}}" {{$singleCheckBox['checked']}} >{{$singleCheckBox['name']}}
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                    <hr>
                                                                @else
                                                                    <div class="row">
                                                                        <div class="col-sm-5"><b>{{ $single_module['name'] }} :</b></div>
                                                                        @foreach($single_module['checkbox'] as $singleCheckBox)
                                                                            <div class="col-sm-6 admin_permission_{{$single_module['module_id']}}" style="{{($single_module['module_id'] == $res_module && $singleCheckBox['checked'] != "checked")?"display:none":"display:block"}}">
                                                                                <input type="checkbox" class="admin_permission_fld_{{$single_module['module_id']}}" id="{{$single_module['module_id']}}_{{$singleCheckBox['id']}}" name="admin_permission[{{$single_module['module_id']}}][]"  value="{{$singleCheckBox['id']}}" {{$singleCheckBox['checked']}} >{{$singleCheckBox['name']}}

                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                    <hr>
                                                                @endif
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        {{--<label class="col-sm-2"></label>--}}
                                        <div class="col-sm-12">
                                            <button type="submit" class="btn btn-primary m-b-0 button_loader">Save</button>
                                        </div>
                                    </div>

                                </form>
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
    {{--<script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}"></script>--}}
    {{--<script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}"></script>--}}
    {{--<script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}"></script>--}}
    {{--<script src="{{ asset('assets/js/responsive/responsive-custom.js')}}"></script>--}}
    <script type="text/javascript">

        $(document).ready(function (){

            var on_click_res_module = '{{ $on_click_res_module }}';
            var res_module = '{{ $res_module }}';
            $(document).on("click",'.serviceCheckkbox',function (){
                $(".admin_permission_"+res_module).css('display','none');
                $(".admin_permission_fld_"+res_module).prop('checked', false);
                $(".serviceCheckkbox:checked").each(function(){
                    curr_cat = $(this).data('cat');
                    const isInArray = on_click_res_module.includes(curr_cat);
                    if(isInArray == true){
                        $(".admin_permission_"+res_module).css('display','block');
                    }
                });
            });

            // Disable button after form submission
            $('#main').on('submit', function() {
                $('.button_loader').attr('disabled', true);  // Disable the button
            });
        });
    </script>
@endsection

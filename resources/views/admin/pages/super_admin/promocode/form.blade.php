@extends('admin.layout.super_admin')
@section('title')
    @if(!isset($promocode_details))Add @else Edit @endif Promocode
@endsection
@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/css/plugin/material-datetime-picker.css')}}" type="text/css">
    <style>
        .form-control:disabled, .form-control[readonly] {
            background-color: #ffffff;
        }

        .toggle input[type="checkbox"] + .button-indecator:before {
            font-size: 25px;
        }

        /*.discount {*/
        /*display: none;*/
        /*}*/

        .image {
            padding-top: 0;
        }

        @if(isset($product_details))
        @if($product_details->image != Null)
        #upload-image-preview {
            background: url({{ asset('assets/images/store-images/product-images/'.$product_details->image) }});
            width: 100%;
            height: 200px;
            background-size: cover;
        }

        @endif
        @endif

        .c-datepicker {
            width: 600px;
            min-height: 370px;
        }

        .c-datepicker__header {
            float: left;
            width: 50%;
        }

        .c-datepicker__calendar {
            width: 50%;
            float: left;
        }

        .c-datepicker__date {
            float: right;
            padding-left: 300px;

        }

        .c-datepicker__back {
            margin-left: 300px;
        }

        .c-datepicker__header-date {
            height: 340px;
        }

        .c-datepicker__toggle--right {
            right: 0;
            left: 200px;
        }

        .c-datepicker__header-day {
            height: 40px;
            line-height: 40px;
            font-size: 16px;
        }

        .c-datepicker__header-date span {
            margin: 10px;
        }

        .c-datepicker__toggle {
            top: 290px;
        }

        .c-datepicker__toggle--left {
            left: 50px;
        }

        .c-datepicker__clock {
            float: right;
            width: 50%;
        }

        .c-datepicker__clock::before {
            left: 30px;
        }

        .c-datepicker__clock .c-datepicker__clock__num, .c-datepicker__clock-hands {
            top: 95%;
        }

        .c-datepicker .c-datepicker--open {
            top: 55% !important;
        }
    </style>
@endsection
@section('page-content')
    <div class="pcoded-content">
        @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
            <div class="external-horizontal-nav">
                @include('admin.include.other-service-horizontal-navbar')
            </div>
        @endif
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>@if(isset($service_category)) {{ ucwords(strtolower($service_category->name)) }} @endif  Promocode</h5>
                            <span>@if(!isset($product_details))Add @else Edit @endif @if(isset($service_category)) {{ ucwords(strtolower($service_category->name)) }} @endif  Promocode</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="row">
                            <form id="main" method="post" action="{{
                                                ( isset($service_category) && in_array($service_category->category_type,[1,5]) ?
                                                        ( !(in_array($service_category->id,[31,32]))? route('post:admin:transport:update_promocode',$slug):route('post:admin:transport-rental:update_promocode',$slug) ) : (isset($service_category) && in_array($service_category->category_type,[2])? route('post:admin:store:update_promocode',$slug) :  route('post:admin:other:update_promocode',$slug) ))  }}" enctype="multipart/form-data">
                                {{csrf_field() }}
                                <div class="form-group col-sm-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>@if(!isset($promocode_details))Add @else Edit @endif @if(isset($service_category)) {{ ucwords(strtolower($service_category->name)) }} @endif  Promocode</h5>
                                            @if(isset($service_category) && in_array($service_category->category_type,[1,5]) && !in_array($service_category->id,[31,32]))
                                                <a href="{{ route('get:admin:transport:promocode_list',$slug) }}" class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                                            @elseif(isset($service_category) && in_array($service_category->category_type,[1,5]) && in_array($service_category->id,[31,32]) )
                                                <a href="{{ route('get:admin:transport-rental:promocode_list',$slug) }}" class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                                            @elseif(isset($service_category) && in_array($service_category->category_type,[2]))
                                                <a href="{{ route('get:admin:store:promocode_list',$slug) }}" class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                                            @else
                                                <a href="{{ route('get:admin:other:promocode_list',$slug) }}" class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                                            @endif
                                        </div>
                                        <div class="card-block">
                                            @if(isset($service_category))
                                                <input type="hidden" name="service_cat_id" value="{{$service_category->id}}">
                                            @endif
                                            @if(isset($promocode_details))
                                                <input type="hidden" name="id" value="{{$promocode_details->id}}">
                                            @endif
                                            <div class="row">
                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Code Name:<sup class="error">*</sup></label>
                                                        <div class="col-sm-12">
                                                            <input type="text" class="form-control" name="code_name" required id="name" placeholder="Promo Code Name" value="{{ (isset($promocode_details)) ? $promocode_details->promo_code : old('code_name') }}">
                                                            <span class="error">{{ $errors->first('code_name') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Expiry Date:<sup class="error">*</sup></label>
                                                        <div class="col-sm-12">
                                                            {{--                                                            <input type="date" class="form-control timepicker" name="expiry_date_time" autocomplete="off" required id="name" placeholder="Promo Code Expiry Date Time" value="{{ (isset($promocode_details)) ? $promocode_details->expiry_date_time : old('expiry_date_time') }}">--}}
                                                            <input type="date" class="form-control " name="expiry_date_time" required id="expiry_date_time" value="{{ (isset($promocode_details)) ? Date('Y-m-d', strtotime($promocode_details->expiry_date_time)) : old('expiry_date_time') }}">
                                                            <span class="error">{{ $errors->first('expiry_date_time') }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Discount Type:<sup class="error">*</sup></label>
                                                        <div class="col-sm-12">
                                                            <select name="discount_type" id="discount_type" class="form-control" required>
                                                                <option value="" selected disabled>Choose Discount Type</option>
                                                                <option value="1"  @if(isset($promocode_details) && $promocode_details->discount_type == 1) selected @endif> Discount in Amount</option>
                                                                <option value="2" @if(isset($promocode_details) && $promocode_details->discount_type == 2) selected @endif> Discount in Percentage</option>
                                                            </select>
                                                            <span class="error">{{ $errors->first('discount_type') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Discount Amount:<sup class="error">*</sup></label>
                                                        <div class="col-sm-12">
                                                            <input type="number" class="form-control"
                                                                   name="discount_amount" required
                                                                   id="discount_amount"
                                                                   placeholder="Discount Amount"
                                                                   step="0.01"
                                                                   value="{{ (isset($promocode_details) && $promocode_details->discount_type != 0) ? $promocode_details->discount_amount : old('discount_amount') }}">
                                                            <span class="error">{{ $errors->first('discount_amount') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Min Order Amount:</label>
                                                        <div class="col-sm-12">
                                                            <input type="number" class="form-control"
                                                                   name="min_order_amount"
                                                                   id="min_order_amount"
                                                                   placeholder="Min Order Amount"
                                                                   step="0.01"
                                                                   value="{{ isset($promocode_details) ? ($promocode_details->min_order_amount != 0)? $promocode_details->min_order_amount : "" : old('min_order_amount') }}">
                                                            <span class="error">{{ $errors->first('min_order_amount') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Max Discount Amount:</label>
                                                        <div class="col-sm-12">
                                                            <input type="number" class="form-control"
                                                                   name="max_discount_amount"
                                                                   id="max_discount_amount"
                                                                   placeholder="Max Discount Amount"
                                                                   step="0.01"
                                                                   value="{{ isset($promocode_details) ? ($promocode_details->max_discount_amount != 0)? $promocode_details->max_discount_amount : "" : old('max_discount_amount') }}">
                                                            <span class="error">{{ $errors->first('max_discount_amount') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Coupon Limit:</label>
                                                        <div class="col-sm-12">
                                                            <input type="number" class="form-control"
                                                                   name="coupon_limit"
                                                                   id="coupon_limit"
                                                                   placeholder="Coupon Limit"
                                                                   step="0.01"
                                                                   value="{{ isset($promocode_details) ? ($promocode_details->coupon_limit != 0 ? $promocode_details->coupon_limit : "" ) : old('coupon_limit') }}">
                                                            <span class="error coupon-limit-error">{{ $errors->first('coupon_limit') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Usage Limit For One User:<sup class="error">*</sup></label>
                                                        <div class="col-sm-12">
                                                            <input type="number" class="form-control"
                                                                   name="usage_limit" required
                                                                   id="usage_limit"
                                                                   placeholder="Usage Limit For Onr User"
                                                                   step="0.01"
                                                                   value="{{ isset($promocode_details) ? ($promocode_details->usage_limit != 0 ? $promocode_details->usage_limit : "") : old('usage_limit') }}">
                                                            <span class="error">{{ $errors->first('usage_limit') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group col-sm-12">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Promo Code Description:<sup class="error">*</sup></label>
                                                        <div class="col-sm-12">
                                                            <textarea name="description" id="description" class="form-control" required placeholder="Promo Code Description">{{ isset($promocode_details) ? $promocode_details->description : old('description') }}</textarea>
                                                            <span class="error">{{ $errors->first('description') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        <center>
                                            <button type="submit" class="btn btn-primary buttonloader">Save</button>
                                        </center>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('page-js')

    <!--    <script src="https://unpkg.com/babel-polyfill@6.2.0/dist/polyfill.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/moment.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/rome/2.1.22/rome.standalone.js"></script>
    <script src="{{ asset('assets/js/plugin/material-datetime-picker.js')}}"></script>
    <script type="text/javascript">
        var picker = new MaterialDatetimePicker({})
        .on('submit', function (d) {
            $('.timepicker').val(d);
        });
        $(document.body).on('click', '.timepicker', function () {
            picker.open();
        });
    </script>-->
    <script src="{{ asset('assets/js/upload_image.js') }}"></script>
{{--    <script type="text/javascript" src="{{asset('js/bootstrap-datetimepicker.js')}}" charset="UTF-8"></script>--}}
    <script type="text/javascript">
        $(document).ready(function () {
            $.uploadPreview({
                input_field: "#image-upload",   // Default: .image-upload
                preview_box: "#upload-image-preview",  // Default: .image-preview
                label_field: "#image-label",    // Default: .image-label
                label_default: "Choose Image",   // Default: Choose File
                label_selected: "Change Image",  // Default: Change File
                no_label: false                 // Default: false
            });
            $(function(){
                //set minimum date
                var dtToday = new Date();
                SetDateValidator(dtToday,"min");
                // Calculate maximum date 3 months from today
                var dtFuture = new Date(dtToday.getTime());
                dtFuture.setMonth(dtFuture.getMonth() + 3);
                //set maximum date
                SetDateValidator(dtFuture,"max");
            });
            function SetDateValidator(date,val){
                var month = date.getMonth() + 1;
                var day = date.getDate();
                var year = date.getFullYear();
                if(month < 10)
                    month = '0' + month.toString();
                if(day < 10)
                    day = '0' + day.toString();
                var maxDate = year + '-' + month + '-' + day;

                $('#expiry_date_time').attr(val, maxDate);
            }
            $('#coupon_limit').keyup(() => {
                $('.coupon-limit-error').text("")
            })
        });
    </script>
    <script type="text/javascript">
        $(document).ready(function () {
            $('#discount').on('click', function () {
                var discount_value = $(this).val();
                if (discount_value == 1) {
                    $(this).val(0);
                    $(this).removeAttr("checked");
                    $('.discount').hide();
                    $('#discount_amount').removeAttr("required");
                    $('#discount_type').removeAttr("required");
                }
                else {
                    $(this).attr("checked", "checked");
                    $(this).val(1);
                    $('.discount').show();
                    $('#discount_amount').attr("required", "required");
                    $('#discount_type').attr("required", "required");
                }
            });
            $('#food_type').on('click', function () {
                var food_type = $(this).val();
                if (food_type == 1) {
                    $(this).val(2);
                }
                else {
                    $(this).val(1);
                }
            });
        });
    </script>
    <script src="{{ asset('assets/js/validation/jquery.validate.js')}}" type="text/javascript"></script>
    <script type="text/javascript">
        //jquery Validations
        $.validator.addMethod("regex",
            function (value, element, regexp) {
                var re = new RegExp(regexp);
                return this.optional(element) || re.test(value);
            },"Please check your input."
        );
        $("#main").validate({
            rules: {
                code_name: {
                    required : true,
                    minlength: 5,
                    regex : "^(?=.*[a-zA-Z])[a-zA-Z0-9!@#$%&*_ ]+$", //regex for accept combination of alphabets & numbers & some special characters
                },
                expiry_date_time: {
                    required : true,
                },
                discount_type: {
                    required : true,
                },
                discount_amount: {
                    required : true,
                    number:true,
                    min: 0,
                },
                usage_limit: {
                    required : true,
                    number:true,
                    min: 1,
                },
                description: {
                    required : true,
                },
                min_order_amount:{
                    number:true,
                    min: 0,
                },
                max_discount_amount:{
                    number:true,
                    min: 0,
                },
                coupon_limit:{
                    number:true,
                    min: 1,
                }
            },
            messages: {
                code_name: {
                    regex :"Please enter alpha numeric value here.",
                },
            },
            submitHandler: function(form) {
                let buttonLoader = $('.buttonloader');

                let couponLimitValue = $('#coupon_limit').val() - 0
                let usageLimitValue = $('#usage_limit').val() - 0
                console.log("couponLimitValue",couponLimitValue);
                console.log("usageLimitValue",usageLimitValue);

                if (couponLimitValue < usageLimitValue) {
                    console.log("in");
                    $('.coupon-limit-error').text("Coupon limit must greater than usage limit.")
                    return false
                }
                buttonLoader.attr("disabled", true);
                buttonLoader.html("<i class='fa fa-spinner fa-spin'></i>");

                form.submit();
            }
        });
    </script>
@endsection


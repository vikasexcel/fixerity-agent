@extends('admin.layout.super_admin')
@section('title')
    @if(isset($faq_details)) Edit @else Add @endif Faqs
@endsection
@section('page-css')
    <style>
        input[type="radio"] {
            display: none;
        }

        input[type="radio"] + .label {
            position: relative;
            /*margin-left: 43%;*/
            /*display: block;*/
            padding-left: 25px;
            margin-right: 10px;
            cursor: pointer;
            /*line-height: 16px;*/
            color: black;
            font-size: 14px;
            transition: all .2s ease-in-out;
            margin-bottom: 10px;
        }

        input[type="radio"] + .label:before, input[type="radio"] > .label:after {
            content: '';
            position: absolute;
            top: -1px;
            left: 0;
            width: 20px;
            height: 20px;
            text-align: center;
            color: black;
            cursor: pointer;
            border-radius: 50%;
            transition: all .3s ease;
        }

        input[type="radio"] + .label:before {
            /*box-shadow: inset 0 0 0 1px #666565, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;*/
            box-shadow: 0 0 0 0 #91DEAC, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
        }

        input[type="radio"] + .label:hover {
            color: #44BB6E;
        }

        input[type="radio"] + .label:hover:before {
            animation-duration: .5s;
            animation-name: change-size;
            animation-iteration-count: infinite;
            animation-direction: alternate;
            box-shadow: inset 0 0 0 1px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
        }

        input[type="radio"]:checked + .label:hover {
            color: #333333;
            cursor: default;
        }

        input[type="radio"]:checked + .label:before {
            animation-duration: .2s;
            animation-name: select-radio;
            animation-iteration-count: 1;
            animation-direction: Normal;
            box-shadow: inset 0 0 0 1px #44BB6E, inset 0 0 0 3px #FFFFFF, inset 0 0 0 16px #44BB6E;

        }

        @keyframes change-size {
            from {
                box-shadow: 0 0 0 0 #44BB6E, inset 0 0 0 1px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
            to {
                box-shadow: 0 0 0 1px #44BB6E, inset 0 0 0 1px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
        }

        @keyframes select-radio {
            0% {
                box-shadow: 0 0 0 0 #91DEAC, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
            90% {
                box-shadow: 0 0 0 10px #E8FFF0, inset 0 0 0 0 #FFFFFF, inset 0 0 0 1px #44BB6E, inset 0 0 0 2px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
            100% {
                box-shadow: 0 0 0 12px #E8FFF0, inset 0 0 0 0 #FFFFFF, inset 0 0 0 1px #44BB6E, inset 0 0 0 3px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
        }

        @media screen and (max-width: 576px) {
            input[type="radio"] + .label {
                margin-left: 48%;
                display: block;
            }
        }
        #searchInput{
            z-index:9999;
            position:relative;
            left: 230px;
            top:40px;
            width: 24%;
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>FAQs</h5>
                            <span>@if(isset($faq_details)) Edit @else Add @endif FAQ</span>
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
                                <h5>@if(isset($faq_details)) Edit @else Add @endif FAQ</h5>
                                <a href="{{ route('get:admin:faqs') }}" class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                            </div>
                            <div class="card-block">
                                <form id="main" method="post" action="{{ route('post:admin:update_faq') }}" enctype="multipart/form-data" name="addEditFaq">
                                    {{csrf_field() }}
                                    @if(isset($faq_details))
                                        <input type="hidden" name="id" id="id" value="{{ $faq_details->id }}">
                                    @endif
                                    <div class="row">
                                        <div class="form-group col-sm-12">
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Name:<sup class="error">*</sup></label>
                                                <div class="col-sm-6">
                                                    <input type="text"  class="form-control" name="name" required id="name" placeholder="Please enter name" value="{{ (isset($faq_details)) ? $faq_details->name : old('name') }}" maxlength="100">
                                                    <span class="error">{{ $errors->first('name') }}</span>
                                                </div>
                                            </div>
                                            @foreach($language_lists as $language_list)
                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Name(in {{$language_list->language_name}}):<sup class="error">*</sup></label>
                                                    <div class="col-sm-6">
                                                        <input type="text"  class="form-control" name="{{$language_list->language_code}}_name" required id="{{$language_list->language_code}}_name" placeholder="Please Enter name in {{$language_list->language_name}}" value="{{ (isset($faq_details)) ? $faq_details->{$language_list->language_code."_name"} : old('name') }}" maxlength="100">
                                                        <span class="error">{{ $errors->first('name') }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Description:<sup class="error">*</sup></label>
                                                <div class="col-sm-6">
                                                    <textarea name="description" data-error="#error-description" style="height: 10em;" placeholder="Please enter description" class="form-control" maxlength="500">{{ (isset($faq_details)) ? $faq_details->description : old('description')}}</textarea>
                                                    <span id="error-description" class="error"></span>
                                                </div>
                                            </div>

                                            @foreach($language_lists as $language_list)
                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Description(in {{$language_list->language_name}}):<sup class="error">*</sup></label>
                                                    <div class="col-sm-6">
                                                        <textarea name="{{$language_list->language_code}}_description" data-error="#error-{{ $language_list->language_code }}_description" style="height: 10em;" required placeholder="Please Enter Description in {{$language_list->language_name}}" class="form-control" maxlength="500">{{ (isset($faq_details)) ? $faq_details->{$language_list->language_code.'_description'} : old('description')}}</textarea>
                                                        <span id="error-{{$language_list->language_code}}_description" class="error"></span>
                                                        <span class="error">{{ $errors->first('description') }}</span>
                                                    </div>
                                                </div>
                                            @endforeach

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Status:<sup class="error">*</sup></label>
                                                <div class="col-sm-6">
                                                    <select name="status" id="status" class="form-control" required >
                                                        <option value="1" {{ (isset($faq_details)) && $faq_details->status == 1 ? "selected" : '' }} >On</option>
                                                        <option value="0" {{ (isset($faq_details)) && $faq_details->status == 0 ? "selected" : '' }} >Off</option>
                                                    </select>
                                                    <span class="error">{{ $errors->first('status') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <center>
                                                <button type="submit" class="btn btn-primary m-b-0 btnsaveclick buttonloader">Save</button>
                                            </center>
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
    <script src="{{asset('admin/jquery/jquery.validate.js')}}"></script>
    <script src="{{asset('admin/jquery/additional-methods.min.js')}}"></script>
    <!-- jquery validation js -->
    <script type="text/javascript" src="{{ asset('assets/js/validation/Admin/Super/custom-validate.js?v=0.642') }}"></script>
@endsection

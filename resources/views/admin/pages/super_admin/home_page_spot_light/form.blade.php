@extends('admin.layout.super_admin')
@section('title')
    @if(isset($home_page_spot_light)) Edit @else Add @endif Spot Light
@endsection
@section('page-css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet"/>
    <style>
        /*start select style*/
        .select2-container {
            width: 100% !important;
            vertical-align: unset;
        }
        .select2-container--default .select2-selection--single {
            height: auto;
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            /*padding-top: 1px;*/
            padding: 4px 30px 4px 20px;
            background-color: transparent;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 34px;
        }
        /*end select style*/

        /* Absolute Center Spinner */
        .loading {
            /*display: none;*/
            position: fixed;
            z-index: 999;
            /*overflow: show;*/
            margin: auto;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            width: 50px;
            height: 50px;
        }

        /* Transparent Overlay */
        .loading:before {
            content: '';
            display: block;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255,255,255,0.5);
        }

        /* :not(:required) hides these rules from IE9 and below */
        .loading:not(:required) {
            /* hide "loading..." text */
            font: 0/0 a;
            color: transparent;
            text-shadow: none;
            background-color: transparent;
            border: 0;
        }
        .loading:not(:required):after {
            content: '';
            display: block;
            font-size: 10px;
            width: 50px;
            height: 50px;
            margin-top: -0.5em;
            border: 15px solid rgba(33, 150, 243, 1.0);
            border-radius: 100%;
            border-bottom-color: transparent;
            -webkit-animation: spinner 1s linear 0s infinite;
            animation: spinner 1s linear 0s infinite;
        }

        /* Animation */
        @-webkit-keyframes spinner {
            0% {
                -webkit-transform: rotate(0deg);
                -moz-transform: rotate(0deg);
                -ms-transform: rotate(0deg);
                -o-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                -moz-transform: rotate(360deg);
                -ms-transform: rotate(360deg);
                -o-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }
        @-moz-keyframes spinner {
            0% {
                -webkit-transform: rotate(0deg);
                -moz-transform: rotate(0deg);
                -ms-transform: rotate(0deg);
                -o-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                -moz-transform: rotate(360deg);
                -ms-transform: rotate(360deg);
                -o-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }
        @-o-keyframes spinner {
            0% {
                -webkit-transform: rotate(0deg);
                -moz-transform: rotate(0deg);
                -ms-transform: rotate(0deg);
                -o-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                -moz-transform: rotate(360deg);
                -ms-transform: rotate(360deg);
                -o-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }
        @keyframes spinner {
            0% {
                -webkit-transform: rotate(0deg);
                -moz-transform: rotate(0deg);
                -ms-transform: rotate(0deg);
                -o-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                -moz-transform: rotate(360deg);
                -ms-transform: rotate(360deg);
                -o-transform: rotate(360deg);
                transform: rotate(360deg);
            }
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
                            <h5>Home Page Spot Light</h5>
                            <span>@if(isset($home_page_spot_light)) Edit @else Add @endif Spot Light Provider</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <a href="{{ route('get:admin:home_page_spot_light_list') }}" class="btn btn-primary m-b-0 btn-right render_link">Back</a>
                </div>
            </div>
        </div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <div class="page-body">
                        <div class="loading">Loading;</div>
                        <form id="main" method="post" action="{{ route('post:admin:update_home_page_spot_light') }}" enctype="multipart/form-data">
                            {{csrf_field() }}
                            <div class="card">
                                <div class="card-header">
                                    <h5>@if(isset($home_page_spot_light)) Edit @else Add @endif Provider</h5>
                                </div>
                                <div class="card-block">
                                    @if(isset($home_page_spot_light))
                                        <input type="hidden" name="id" value="{{ $home_page_spot_light->id }}">
                                    @endif
                                    <div class="row">
                                        <div class="form-group col-sm-12">
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label image">Select Service:<sup class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <select id="services" name="service" class="js-example-placeholder-single1 js-states form-control" required>
                                                        <option disabled selected value=""></option>
                                                        <option disabled>Select a Service</option>
                                                        @if(isset($service_category))
                                                            @if(!$service_category->isEmpty())
                                                                @foreach($service_category as $key => $category)
                                                                    <option value="{{ $category->id }}" {{ $selected = isset($home_page_spot_light) ? ($home_page_spot_light->service_cat_id == $category->id)?  "selected" : "" : "" }} >{{ ucwords($category->name)  }}</option>
                                                                @endforeach
                                                            @endif
                                                        @endif
                                                    </select>
                                                    <span class="error">{{ $errors->first('services') }}</span>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label image">Provider List:<sup class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <select id="provider" name="provider" class="js-example-placeholder-single1 js-states form-control" required>
                                                        <option disabled selected value=""></option>
                                                        <option disabled>Select a provider List</option>
                                                    </select>
                                                    <span class="error">{{ $errors->first('provider') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12"><center><button type="submit" class="btn btn-primary m-b-0 button_loader">Save</button></center></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('page-js')
    <script src={{ asset("assets/js/select2.js") }}></script>
    <script>
        $("#services").select2({
            placeholder: "Select a Service",
            allowClear: true,
        });
        $("#provider").select2({
            placeholder: "Select a Provider",
            allowClear: true,
        });

        $(document).ready(function () {
            let service_Category = $('#services').val();
            let selected_provider = "{{ isset($home_page_spot_light->provider_id) ? $home_page_spot_light->provider_id : 0 }}";
            LoadStores(service_Category,selected_provider);
        });

        $('#services').change(function(){
            let service_Category = $(this).val();
            let selected_provider = "{{ isset($home_page_spot_light->provider_id) ? $home_page_spot_light->provider_id : 0 }}";
            LoadStores(service_Category,selected_provider);
        });

        // Disable button after form submission
        $('#main').on('submit', function() {
            $('.button_loader').attr('disabled', true);  // Disable the button
        });

        function LoadStores(service_Category,selected_provider){

            $(".loading").show();
            if(!service_Category){
                $(".loading").hide();
                throw new Error("Something went badly wrong!");
            }

            $.ajax({
                type: "POST",
                url: "{{ route('get:admin:ajax_load_store-provider') }}",
                async : false,
                cache : false,
                data: {
                    service_Category: service_Category,
                    selected_provider: selected_provider,
                    _token: "{{ csrf_token() }}",
                    id: "{{ isset($home_page_spot_light) ? $home_page_spot_light->provider_id : 0 }}"
                },
                success:function(response){
                    setTimeout(function (){
                        let $select = $('#provider');
                        if(response.success === true){
                            $select.find('option').remove().end().append('<option disabled selected value="">Provider List</option>');
                            $.each(response.data, function (key, value) {
                                console.log(value.provider_id);
                                if(parseInt(selected_provider) === parseInt(value.provider_id)){
                                    $select.append('<option value=' + value.provider_id + ' selected>' + value.provider_name + '</option>');
                                } else{
                                    $select.append('<option value=' + value.provider_id + '>' + value.provider_name + '</option>');
                                }
                            });
                        }
                        $(".loading").hide();
                    },1000)
                },
            });
        }
    </script>
@endsection


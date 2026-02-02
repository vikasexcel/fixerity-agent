@extends('admin.layout.super_admin')
@section('title')
    @if(isset($pages)) Edit Page @else Add Page @endif
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
                                        @if(isset($pages)) Edit  {{ ucwords(strtolower(str_replace('-',' ',$pages->name))) }} @else Add Page @endif
                                    </h5>
                                    <span>
                                        @if(isset($pages)) Edit page @else Add Page @endif
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
                                <form id="main" method="post" action="{{ route('post:admin:update_pages') }}"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}

                                    @if(isset($pages))
                                        <input type="hidden" name="id" value="{{$pages->id}}">
                                    @endif

                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label">Name(English):<sup
                                                    class="error">*</sup></label>
                                        <div class="col-sm-12">
                                            <input type="text" class="form-control" name="name" required
                                                   id="name" placeholder="Page Name"
                                                   {{ (isset($pages) && $pages->name != Null) ? "readonly" : "" }}
                                                   value="{{ (isset($pages)) ? $pages->name : old('name') }}">
                                            <span class="error">{{ $errors->first('name') }}</span>
                                        </div>
                                    </div>
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Name(Filipino):<sup--}}
{{--                                                class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <input type="text" class="form-control" name="fl_name" required--}}
{{--                                                   id="fl_name" placeholder="Page Name Filipino"--}}
{{--                                                   {{ (isset($pages) && $pages->fl_name != Null) ? "readonly" : "" }}--}}
{{--                                                   value="{{ (isset($pages)) ? $pages->fl_name : old('fl_name') }}">--}}
{{--                                            <span class="error">{{ $errors->first('fl_name') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Name(Cebuano):<sup--}}
{{--                                                class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <input type="text" class="form-control" name="cb_name" required--}}
{{--                                                   id="cb_name" placeholder="Page Name Cebuano"--}}
{{--                                                   {{ (isset($pages) && $pages->cb_name != Null) ? "readonly" : "" }}--}}
{{--                                                   value="{{ (isset($pages)) ? $pages->cb_name : old('cb_name') }}">--}}
{{--                                            <span class="error">{{ $errors->first('cb_name') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Name(Chinese Simplified):<sup--}}
{{--                                                class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <input type="text" class="form-control" name="cs_name" required--}}
{{--                                                   id="cs_name" placeholder="Page Name Chinese Simplified"--}}
{{--                                                   {{ (isset($pages) && $pages->cs_name != Null) ? "readonly" : "" }}--}}
{{--                                                   value="{{ (isset($pages)) ? $pages->cs_name : old('cs_name') }}">--}}
{{--                                            <span class="error">{{ $errors->first('cs_name') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Name(Chinese Traditional):<sup--}}
{{--                                                class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <input type="text" class="form-control" name="ct_name" required--}}
{{--                                                   id="ct_name" placeholder="Page Name Chinese Traditional"--}}
{{--                                                   {{ (isset($pages) && $pages->ct_name != Null) ? "readonly" : "" }}--}}
{{--                                                   value="{{ (isset($pages)) ? $pages->ct_name : old('ct_name') }}">--}}
{{--                                            <span class="error">{{ $errors->first('ct_name') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Name(Japanese):<sup--}}
{{--                                                class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <input type="text" class="form-control" name="jp_name" required--}}
{{--                                                   id="jp_name" placeholder="Page Name Japanese"--}}
{{--                                                   {{ (isset($pages) && $pages->jp_name != Null) ? "readonly" : "" }}--}}
{{--                                                   value="{{ (isset($pages)) ? $pages->jp_name : old('jp_name') }}">--}}
{{--                                            <span class="error">{{ $errors->first('jp_name') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Name(Korean):<sup--}}
{{--                                                class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <input type="text" class="form-control" name="ko_name" required--}}
{{--                                                   id="ko_name" placeholder="Page Name Korean"--}}
{{--                                                   {{ (isset($pages) && $pages->ko_name != Null) ? "readonly" : "" }}--}}
{{--                                                   value="{{ (isset($pages)) ? $pages->ko_name : old('ko_name') }}">--}}
{{--                                            <span class="error">{{ $errors->first('ko_name') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Name(French):<sup--}}
{{--                                                class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <input type="text" class="form-control" name="fr_name" required--}}
{{--                                                   id="fr_name" placeholder="Page Name French"--}}
{{--                                                   {{ (isset($pages) && $pages->fr_name != Null) ? "readonly" : "" }}--}}
{{--                                                   value="{{ (isset($pages)) ? $pages->fr_name : old('fr_name') }}">--}}
{{--                                            <span class="error">{{ $errors->first('fr_name') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Name(Spanish):<sup--}}
{{--                                                class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <input type="text" class="form-control" name="sp_name" required--}}
{{--                                                   id="sp_name" placeholder="Page Name Spanish"--}}
{{--                                                   {{ (isset($pages) && $pages->sp_name != Null) ? "readonly" : "" }}--}}
{{--                                                   value="{{ (isset($pages)) ? $pages->sp_name : old('sp_name') }}">--}}
{{--                                            <span class="error">{{ $errors->first('sp_name') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Name(Germany):<sup--}}
{{--                                                class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <input type="text" class="form-control" name="gr_name" required--}}
{{--                                                   id="gr_name" placeholder="Page Name Germany"--}}
{{--                                                   {{ (isset($pages) && $pages->gr_name != Null) ? "readonly" : "" }}--}}
{{--                                                   value="{{ (isset($pages)) ? $pages->gr_name : old('gr_name') }}">--}}
{{--                                            <span class="error">{{ $errors->first('gr_name') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Name(Arabic):<sup--}}
{{--                                                class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <input type="text" class="form-control" name="ar_name" required--}}
{{--                                                   id="ar_name" placeholder="Page Name Arabic"--}}
{{--                                                   {{ (isset($pages) && $pages->ar_name != Null) ? "readonly" : "" }}--}}
{{--                                                   value="{{ (isset($pages)) ? $pages->ar_name : old('ar_name') }}">--}}
{{--                                            <span class="error">{{ $errors->first('ar_name') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}


                                    @if(isset($language_lists))

                                        @foreach($language_lists as $single_lang)
                                            @php
                                                $language_name =  isset($single_lang->language_name)?$single_lang->language_name:"";
                                                $language_code =  isset($single_lang->language_code)?$single_lang->language_code:"";
                                                $col_name = $language_code."_name";

                                            @endphp
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Name({{$language_name}}):<sup
                                                        class="error">*</sup></label>
                                                <div class="col-sm-12">
                                                    <input type="text" class="form-control" name="{{$col_name}}" required
                                                           id="{{$col_name}}" placeholder="Page Name {{$language_name}}"
                                                           {{ (isset($pages) && $pages->$col_name != Null) ? "" : "" }}
                                                           value="{{ (isset($pages)) ? $pages->$col_name : old($language_code) }}">
                                                    <span class="error">{{ $errors->first($language_code) }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif

                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label">Description(English):<sup class="error">*</sup></label>
                                        <div class="col-sm-12">
                                            <textarea id="description1" name="description" placeholder="Page Description English"
                                                      class="form-control description">{{ (isset($pages)) ? $pages->description : old('description')}}</textarea>
                                            <span class="error">{{ $errors->first('description') }}</span>
                                        </div>
                                    </div>

                                    @if(isset($language_lists))
                                        @php $i=1 @endphp
                                        @foreach($language_lists as $single_lang)
                                            @php
                                                $language_name =  isset($single_lang->language_name)?$single_lang->language_name:"";
                                                $language_code =  isset($single_lang->language_code)?$single_lang->language_code:"";
                                                $col_name = $language_code."_description";
                                            @endphp
                                            @php $i++ @endphp
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Description({{$language_name}}):<sup class="error">*</sup></label>
                                                <div class="col-sm-12">
                                            <textarea id="description{{$i}}" name="{{$col_name}}" placeholder="Page Description {{$language_name}}"
                                                      class="form-control description">{{ (isset($pages)) ? $pages->$col_name : old($col_name)}}</textarea>
                                                    <span class="error">{{ $errors->first('$col_name') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Description(Filipino):<sup class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <textarea id="textarea1" name="fl_description" placeholder="Page Description Filipino"--}}
{{--                                                      class="form-control">{{ (isset($pages)) ? $pages->fl_description : old('fl_description')}}</textarea>--}}
{{--                                            <span class="error">{{ $errors->first('fl_description') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Description(Cebuano):<sup class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <textarea id="textarea2" name="cb_description" placeholder="Page Description Cebuano"--}}
{{--                                                      class="form-control">{{ (isset($pages)) ? $pages->cb_description : old('cb_description')}}</textarea>--}}
{{--                                            <span class="error">{{ $errors->first('cb_description') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Description(Chinese Simplified):<sup class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <textarea id="textarea3" name="cs_description" placeholder="Page Description Chinese Simplified"--}}
{{--                                                      class="form-control">{{ (isset($pages)) ? $pages->cs_description : old('cs_description')}}</textarea>--}}
{{--                                            <span class="error">{{ $errors->first('cs_description') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Description(Chinese Traditional):<sup class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <textarea id="textarea4" name="ct_description" placeholder="Page Description Chinese Traditional"--}}
{{--                                                      class="form-control">{{ (isset($pages)) ? $pages->ct_description : old('ct_description')}}</textarea>--}}
{{--                                            <span class="error">{{ $errors->first('ct_description') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Description(Japanese):<sup class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <textarea id="textarea5" name="jp_description" placeholder="Page Description Japanese"--}}
{{--                                                      class="form-control">{{ (isset($pages)) ? $pages->jp_description : old('jp_description')}}</textarea>--}}
{{--                                            <span class="error">{{ $errors->first('jp_description') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Description(Korean):<sup class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <textarea id="textarea6" name="ko_description" placeholder="Page Description Korean"--}}
{{--                                                      class="form-control">{{ (isset($pages)) ? $pages->ko_description : old('ko_description')}}</textarea>--}}
{{--                                            <span class="error">{{ $errors->first('ko_description') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Description(French):<sup class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <textarea id="textarea7" name="fr_description" placeholder="Page Description French"--}}
{{--                                                      class="form-control">{{ (isset($pages)) ? $pages->fr_description : old('fr_description')}}</textarea>--}}
{{--                                            <span class="error">{{ $errors->first('fr_description') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Description(Spanish):<sup class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <textarea id="textarea8" name="sp_description" placeholder="Page Description Spanish"--}}
{{--                                                      class="form-control">{{ (isset($pages)) ? $pages->sp_description : old('sp_description')}}</textarea>--}}
{{--                                            <span class="error">{{ $errors->first('sp_description') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Description(Germany):<sup class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <textarea id="textarea9" name="gr_description" placeholder="Page Description Germany"--}}
{{--                                                      class="form-control">{{ (isset($pages)) ? $pages->gr_description : old('gr_description')}}</textarea>--}}
{{--                                            <span class="error">{{ $errors->first('gr_description') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-sm-12 col-form-label">Description(Arabic):<sup class="error">*</sup></label>--}}
{{--                                        <div class="col-sm-12">--}}
{{--                                            <textarea id="textarea9" name="ar_description" placeholder="Page Description Arabic"--}}
{{--                                                      class="form-control">{{ (isset($pages)) ? $pages->ar_description : old('ar_description')}}</textarea>--}}
{{--                                            <span class="error">{{ $errors->first('ar_description') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}

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
    <script>
        // Disable button after form submission
        $('#main').on('submit', function() {
            $('.button_loader').attr('disabled', true);  // Disable the button
        });
    </script>
    {{--<script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}"></script>--}}
    {{--<script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}"></script>--}}
    {{--<script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}"></script>--}}
    {{--<script src="{{ asset('assets/js/responsive/responsive-custom.js')}}"></script>--}}

    {{--<script src="https://tinymce.cachefly.net/4.0/tinymce.min.js"></script>--}}
    <script type="text/javascript" src="{{ asset('assets/js/ckeditor.js')}}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/ckeditor-script.js')}}"></script>
@endsection

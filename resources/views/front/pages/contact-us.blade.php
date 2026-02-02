@extends('front.layout.default')
@section('title')
    <title>{{ isset($page_setting->name)?$page_setting->name:"" }}</title>
@endsection
@section('meta-tags')
    <meta name="description" content="Restaurant">
    <meta name="keywords" content="Restaurant">
@endsection
@section('page-css')
    <style>
        h2{
            padding: 0 50px;
        }
    </style>
@endsection
@section('page-content')<!--content-->
<div class="container-fluid content minheights">
    <h2>{{ isset($page_setting->name)?$page_setting->name:"" }}:</h2>
    @if(isset($page_setting))
        {!! $page_setting->description !!}
    @endif

</div>
<!--end content-->
@endsection

@section('page-js')

@endsection

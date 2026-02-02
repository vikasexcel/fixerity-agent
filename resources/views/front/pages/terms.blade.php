@extends('front.layout.default')
@section('title')
    <title>Terms & Condition</title>
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
    <h2>Terms & Conditions:</h2>
    @if(isset($page_setting))
        {!! $page_setting->description !!}
    @endif

</div>
<!--end content-->
@endsection

@section('page-js')

@endsection

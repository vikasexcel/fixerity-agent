@extends('front.layout.default')
@section('title')
    <title>FAQ</title>
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
    <h2>Faq:</h2>
    @if(isset($page_setting))
        {{--<h2>About Us:</h2>--}}
        {!! $page_setting->description !!}
    @endif

</div>
<!--end content-->
@endsection

@section('page-js')

@endsection

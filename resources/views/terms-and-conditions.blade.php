@extends('front.layout.default')
@section('title')
    <title>
        @if(isset($get_page_data))
            @if(isset($title))
                {{ ucwords($title) }}
            @else
                Terms and Conditions
            @endif
        @endif
    </title>
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
    <h2>@if(isset($get_page_data))
            @if(isset($title))
                {{ ucwords($title) }}
            @else
                Terms and Conditions
            @endif
        @endif
    </h2>
    @if(isset($get_page_data))
        {!! html_entity_decode($get_page_data->description) !!}
    @endif

</div>
<!--end content-->
@endsection

@section('page-js')

@endsection

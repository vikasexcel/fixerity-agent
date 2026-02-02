@extends('layouts.terms')
@section('title')
    @if(isset($get_page_data))
        @if(isset($title))
            {{ ucwords($title) }}
        @else
            Terms and Conditions
        @endif
    @endif
@endsection
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if(isset($get_page_data))
                    {!! $get_page_data->description !!}
                @endif
            </div>
        </div>
    </div>
@endsection

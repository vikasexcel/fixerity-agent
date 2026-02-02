@extends('front.layout.default')
@section('title')
    <title>fox-food</title>
@endsection
@section('meta-tags')
    <meta name="description" content="Restaurant">
    <meta name="keywords" content="Restaurant">
@endsection
@section('page-css')
    <style>

    </style>
@endsection
@section('page-content')<!--content-->
<div class="container-fluid content">

    <div class="col-md-12 col-sm-12 col-xs-12 most-trusted">
        <center><h4>Most Trusted Restaurants</h4></center>
        <div class="MS-content">
            @if(isset($pop_home_restaurants))
                @foreach($pop_home_restaurants as $pop_home_restaurant)
                    <div class="item"><a href="{{ route('get:restaurant_data', $pop_home_restaurant->slug) }}"
                                         target="_blank" title="{{ $pop_home_restaurant->name }}"> <img
                                    src="{{ asset('restaurant/'.$pop_home_restaurant->image) }}"
                                    alt="" class="lazy"/>
                            {{--<span>{{ $pop_home_restaurant->name }}</span>--}}
                        </a>
                    </div>
                @endforeach
            @else
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/2.jpeg') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/3.jpg') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/4.jpeg') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/5.jpeg') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/6.jpeg') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/7.jpeg') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/8.jpeg') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/9.jpeg') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/10.png') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/11.jpeg') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/12.jpeg') }}"
                                                                    alt="" class="lazy"/> </a></div>
                <div class="item"><a href="#" target="_blank"> <img src="{{ asset('assets/front/images/front-images/13.jpeg') }}"
                                                                    alt="" class="lazy"/> </a></div>
            @endif
        </div>
    </div>

    <div class="col-md-12 col-sm-12 col-xs-12 quick-search">
        <center><h4>Quick Searches</h4></center>
        <div class="quick-content">
            <a href=""><img src="{{ asset('assets/front/images/icons/delivery-32.png') }}">
                <div>Delivery</div>
            </a>
            <a href=""><img src="{{ asset('assets/front/images/icons/wallet-32.png') }}">
                <div>Pocket-Friendly</div>
            </a>
            <a href=""><img src="{{ asset('assets/front/images/icons/break-fast-32.png') }}">
                <div>Breakfast</div>
            </a>
            <a href=""><img src="{{ asset('assets/front/images/icons/lunch-32.png') }}">
                <div>Lunch</div>
            </a>
            <a href=""><img src="{{ asset('assets/front/images/icons/dinner-32.png') }}">
                <div>Dinner</div>
            </a>
            <a href=""><img src="{{ asset('assets/front/images/icons/cafe-32.png') }}">
                <div>Cafes</div>
            </a>
            <a href=""><img src="{{ asset('assets/front/images/icons/luxury-32.png') }}">
                <div>Luxury Dining</div>
            </a>
            <a href=""><img src="{{ asset('assets/front/images/icons/dessert-32.png') }}">
                <div>Desserts & Bakes</div>
            </a>
        </div>
    </div>
</div>
<!--end content-->
@endsection

@section('page-js')
    <script src="{{ asset('assets/front/js/multislider.min.js') }}" type="text/javascript"></script>
    <script>
        $('.most-trusted').multislider({
            continuous: true,
            duration: 3000
        });
    </script>
@endsection

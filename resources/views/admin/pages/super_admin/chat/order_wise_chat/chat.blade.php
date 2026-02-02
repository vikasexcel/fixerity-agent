@extends('admin.layout.super_admin')
@section('title')
    Chat
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/widget/widget.css') }}">
    <style>
        .text-time-size {
            font-size: 11px;
        }

        .chat-error-message {
            display: none;
        }

        .chat-card {
            padding: 15px;
            /*border: 1px solid #e0e0e0;*/
            border-radius: 5px;
            /*background-color: #f9f9f9;*/
            max-height: 400px;
            overflow-y: auto;
        }

        .tab-content > .tab-pane {
            display: none;
        }

        .tab-content > .active {
            display: block;
        }

        .nav-tabs .nav-link {
            font-size: 14px;
        }

        /* .chat-card */
        .send-chat {
            text-align: right;
            /*float: right;*/
            display: flex;
            flex-direction: row-reverse;
        }

        .img-radius {
            height: 40px !important;
        }

        /* Loader CSS - START*/
        .loading-container-new {
            position: fixed;
            width: 100%;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: 99999 !important;
            place-content: center;
            display: grid;
        }

        .loading-progress-new {
            position: relative;
            height: 5rem;
            width: 5rem;
        }

        .loading-progress-new::before {
            content: "";
            position: absolute;
            height: 100%;
            width: 100%;
            border-radius: 50%;
            border: 5px solid transparent;
            border-top-color: rgba(38, 193, 101, 0.85);
            top: -5px;
            left: -5px;
            animation: spin 1s linear infinite;
        }

        /* add border-radius to default tabs or nav-tabs to 3px  */
        .nav-tabs > .nav-item > .nav-link {
            border-radius: 3px;
        }

        /* maintain all three tabs width to 33% */
        .nav-tabs > .nav-item {
            float: left;
            margin-bottom: -1px;
            width: 33%; /* Make all tabs equal width */
            text-align: center;
        }


        /* nav-tabs hyperlink and hover effects light color with background  */
        .nav-tabs > .nav-item > a {
            display: block;
            padding: 10px;
            border: 1px solid transparent;
            border-radius: 0;
            transition: background 0.3s ease-in-out;
        }

        .nav-tabs > .nav-item > a:hover {
            background: #f2f2f2;
            border-color: #eee;
            color: #000000;
            border-radius: 3px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Loader CSS - END */

        .scroll_overflow {
            overflow-y: scroll;
            overflow-x: hidden;
            max-height: 500px;
        }
    </style>

@endsection

@section('page-content')
    <div class="loading-container-new">
        <div class="loading-progress-new"></div>
    </div>

    <div class="pcoded-content">
        {{--        @if(Illuminate\Support\Facades\Auth::guard("admin")->check())--}}
        {{--            @if(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 1 || Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 4)--}}
        {{--                <div class="other-service-horizontal-nav">--}}
        {{--                    @if(isset($category_type) && in_array($category_type,[1,5]))--}}
        {{--                        @include('admin.include.transport-horizontal-navbar')--}}
        {{--                    @elseif(isset($category_type) && in_array($category_type,[1,5]))--}}
        {{--                        @include('admin.include.transport-rental-horizontal-navbar')--}}
        {{--                    @elseif(isset($category_type) && in_array($category_type,[2]))--}}
        {{--                        @include('admin.include.store-horizontal-navbar')--}}
        {{--                    @elseif(isset($category_type) && in_array($category_type,[6]))--}}
        {{--                        @include('admin.include.video-service-horizontal-navbar')--}}
        {{--                    @else--}}
        {{--                        @include('admin.include.other-service-horizontal-navbar')--}}
        {{--                    @endif--}}
        {{--                </div>--}}
        {{--            @endif--}}
        {{--        @endif--}}
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="page-header-title">
                        <i class="feather icon-list bg-c-blue"></i>
                        <div class="d-inline">
                            <h5 id="chat_user_provider_name">Chat</h5>
                            <span>Chat Details For {{ $service_category->name }}</span>
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
                            <input type="hidden" name="chat_type" id="chat_type" value="1">
                            <input type="hidden" name="chat_order_id" id="chat_order_id" value="">
                            <input type="hidden" name="receiver_user_id" id="receiver_user_id" value="">
                            <input type="hidden" name="sender_user_id" id="sender_user_id" value="">
                            <input type="hidden" name="category_type" id="category_type" value="{{$category_type}}">
                            <div class="card-header">
                                <h5 id="message_header">
                                    Messages Between Provider & User
                                </h5>
                            </div>
                            <div class="card-block">
                                @if($category_type != 2)
                                    <div class="row mt-4">
                                        <div class="col-xl-12 col-md-12">
                                            <div class="chat-card">
                                                <div class="" id="chat_list">
                                                    {{--                                                    chat-card---}}
                                                    <div class="chat-card-1">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <ul class="nav nav-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#tab1" type="1">Driver -
                                                User Chat</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#tab2" type="2">Store -
                                                User Chat</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#tab3" type="3">Driver - Store
                                                Chat</a>
                                        </li>
                                    </ul>

                                    <div class="tab-content" id="chatTabsContent">
                                        <!-- Tab 1 -->  <!-- for not Takeaway then  this active-->
                                        <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                                            <div class="row mt-4">
                                                <div class="col-xl-12 col-md-12">
                                                    <div class="scroll_overflow chat-card-1">
                                                        {{--                                                        <div class="" id="chat_list">--}}

                                                        {{--                                                        </div>--}}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Tab 2 -->
                                        <!-- for Takeaway then  this active-->
                                        <div class="tab-pane fade" id="tab2" role="tabpanel">
                                            <div class="row mt-4">
                                                {{--                                                Driver chats--}}
                                                <div class="col-xl-12 col-md-12">
                                                    <div class="scroll_overflow chat-card-2">
                                                        {{--                                                        <div class="" id="chat_list">--}}

                                                        {{--                                                        </div>--}}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Tab 3 -->
                                        <div class="tab-pane fade" id="tab3" role="tabpanel">
                                            <div class="row mt-4">
                                                {{--                                                User chats--}}
                                                <div class="col-xl-12 col-md-12">
                                                    <div class="scroll_overflow chat-card-3">
                                                        {{--                                                        <div class="" id="chat_list">--}}

                                                        {{--                                                        </div>--}}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
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
        $(document).ready(function () {
            function removeLoader() {
                setTimeout(
                    function () {
                        $('.loading-container-new').css('display', 'none');
                    }, 1000);
            }

            function addLoader() {
                $('.loading-container-new').css('display', 'grid');
                // $(".loading-container-new").attr("style", "display: grid");
            }

            addLoader();
            let chat_type = $(this).attr('type');
            // jQuery to handle tab switching
            $('.nav-tabs a').click(function (e) {
                addLoader();
                e.preventDefault();
                let chat_type = $(this).attr('type');
                console.log(chat_type);
                $("#message_header").html("Messages Between Provider & User");
                loadChat(chat_type);
                $(this).tab('show');
                //updating the live chat if there is any new message arrived
                leadsRef.on('value', function (snapshot) {
                    if (call_function == 1) {
                        loadChat(chat_type);
                    }
                });
            });
            let slug = @json($slug);
            let order_id = @json($order_id);
            let call_function = 0;
            loadChat(chat_type);

            //updating the live chat if there is any new message arrived
            var leadsRef = database.ref("{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/order_chat/" + $('#chat_order_id').val() + "/" + $('#receiver_user_id').val() + "_" + $('#sender_user_id').val());

            function loadChat(chat_type = 1) {
                //ajax call to fetch the chats
                $.ajax({
                    type: 'get',
                    async: false,
                    url: '{{ route("get:admin:get_order_wise_chat_ajax") }}',
                    data: {slug: slug, order_id: order_id, chat_type: chat_type},
                    success: function (result) {
                        removeLoader();
                        if (result.success == true) {
                            $('#receiver_user_id').val(result.receiver_user_id);
                            $('#sender_user_id').val(result.sender_user_id);
                            $('#chat_order_id').val(result.chat_order_id);
                            leadsRef = database.ref("{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/order_chat/" + result.chat_order_id + "/" + result.receiver_user_id + "_" + result.sender_user_id);
                            //append all chats
                            $(".chat-card-" + chat_type).html(result.chat_history);
                            call_function = 1;
                        } else {
                            swal("Warning", result.message, "warning");
                            console.log(result);
                        }
                    }
                })
            }

            //updating the live chat if there is any new message arrived
            leadsRef.on('value', function (snapshot) {
                if (call_function == 1) {
                    loadChat(chat_type);
                }
            });
            // let chat_type = $('#chat_type').val();
        });
    </script>
@endsection



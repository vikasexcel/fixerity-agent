@extends('admin.layout.super_admin')
@section('title')
    Report Issue Chat
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('admin/css/widget.css')}}">
    <style>
        .text-time-size{
            font-size: 11px;
        }
        .chat-error-message{
            display: none;
        }
        .error {
            color: #bc544b;
            width: 100%;
            text-align: left;
            padding-left: 0;
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

        .chat-card .msg {
            overflow: auto;
            max-height: 500px;
            margin-bottom: 5px;
        }
        .chat-card .msg img {
            width: 60px;
            border-radius: 5px;
            margin-bottom: 5px;
            margin-top: 5px;
            margin-right: 10px;
        }
    </style>
@endsection
@section('page-content')
    <div class="pcoded-content">
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5 id="chat_user_provider_name"></h5>
                                    <span>Chat Details</span>
                                </div>
                            </div>
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
                                <h5>Messages</h5>
                            </div>
                            <div class="card-block">
                                <div class="row">
                                    <div class="col-xl-12 col-md-12">
                                        <div class="chat-card">
                                            <div class="" id="chat_list"></div>
                                            <div class="right-icon-control">
                                                <div class="input-group input-group-button">
                                                    <input type="text" class="form-control"
                                                           name="message"
                                                           id="message" style="height: 44px;"
                                                           placeholder="Send message">
                                                    <div class="input-group-append">
                                                        <input type="file" id ="custom_upload" accept="image/png, image/jpg, image/jpeg, image/webp" style="display: none">
                                                        <label class="btn btn-secondary uploadImage"  for="custom_upload">
                                                            <i class="fa fa-paperclip"></i>
                                                        </label>
                                                    </div>
                                                    <div>
                                                        <button class="btn btn-primary chat buttonloader"
                                                                type="button">
                                                            <i class="feather icon-message-circle"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <p class="error chat-error-message">Message text field is required</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
{{--    Script for Firebase chat module integration for fetching chat & sending message in firebase   --}}
    <script>
        $("#message").keyup(function(){
           $('.chat-error-message').css('display','none');
        });
        // user id
        var report_chat_number = "{{ $report_chat_number }}";
        var u_id = "{{$user_id}}";
        // admin reference
        var a_id = "{{$sender_id}}";
        //sender id
        var sender_id = a_id;
        var senderName = "{{$report_name}}";
        var user_name = "{{$user_name}}";
        var child_Data = [];
        var user_fcm;
        var time_stamp;
        var localDate;
        var admin_img = "{{ url('/assets/front/img/clients/default.png') }}";
        var user_img = "{{ $user_image }}";
        //converting millisecond to date
        function MiliToDate(milliseconds) {
            let date = new Date(milliseconds - 0);
            return date.toUTCString();
        }// function to fetch chat messages from firebase live database
        function chatMessages() {
            var childData = '';
            var sender_check = '';
            //chat reference of admin
            var chat_ref = "{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/report_issue_chat/"+report_chat_number+"/"+a_id+"_"+u_id;
            var leadsRef = database.ref(chat_ref);
            leadsRef.on('value', function (snapshot) {
                // console.log("snapshot => ");
                // console.log(snapshot);
                $("#chat_list").empty();
                var html = '';
                childData = snapshot.val();
                // console.log("childData =>");
                // console.log(childData);
                // console.log(childData['chat']);
                $.each(childData,function (chat_index, chat_value) {
                    sender_check = chat_value['sender_id'];
                    localDate = MiliToDate(chat_value['date']);
                    localDate = new Date(localDate + " GMT");
                    //checking for image
                    if(chat_value['is_image'] == 1){
                        appendData = '<a class="m-b-0" href="' + chat_value['message'] + '" target="_blank"><img src="' + chat_value['message'] + '"></img></a>\n';
                    } else {
                        appendData = '<p class="m-b-0">' + chat_value['message'] + '</p>\n';
                    }

                    // console.log("a_id");
                    // console.log(a_id);
                    //
                    // console.log("sender_check");
                    // console.log(sender_check);

                    if (a_id !== sender_check) {
                        html += '<div class="row m-b-20"><div class="col-auto"><img src="'+user_img+'" alt="avatar" class="img-radius img-40"></div><div class="col"><div class="msg">'+ appendData +'</div><p class="text-muted m-b-0 text-time-size">'+user_name+'</p><p class="text-muted m-b-0 text-time-size"><i class="fa fa-clock-o m-r-10"></i> ' + localDate.toLocaleString() + ' </p></div></div>';

                        console.log("html 1 => ");
                        console.log(html);
                    } else {

                        html += '<div class="row m-b-20 send-chat"><div class="col-auto"><img src="'+admin_img+'" alt="avatar" class="img-radius img-40"></div><div class="col"><div class="msg">'+ appendData +'</div><p class="text-muted m-b-0 text-time-size">Admin</p><p class="text-muted m-b-0 text-time-size"><i class="fa fa-clock-o m-r-10"></i> ' + localDate.toLocaleString() + ' </p></div></div>';
                        console.log("html 2  => ");
                        console.log(html);
                    }
                    // console.log("======");
                    // console.log(html);
                    document.getElementById("chat_list").innerHTML = html;
                });
            });
        }
        //function to get user details from firebase live database
        async function userDetails() {
            //chat reference of admin to get user details
            var get_ref = database.ref("{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/report_issue_chat/"+report_chat_number+"/"+a_id+"_"+u_id);
            var snapshot = await get_ref.orderByChild('user_id').equalTo(u_id).once('value');
            var key = '';
            if(snapshot.exists()) {
                snapshot.forEach(function (childSnapshot) {
                    var val = childSnapshot.val();
                    key = childSnapshot.key;
                    child_Data['child_Key'] = childSnapshot.key;
                    child_Data['receiverId'] = val['user_id'];
                    child_Data['receiverName'] = val['user_name'];
                    child_Data['userProfile'] = val['profile_pic'];
                    child_Data['userType'] = val['user_type'];
                });
                //updating admin seen in admin
                get_ref.child(key).update({
                    admin_seen: true,
                });
            } else {
                child_Data['receiverId'] = u_id;
                child_Data['receiverName'] = "{{$user_name}}";
                child_Data['userProfile'] = "{{$default_img}}";
            }
            //appending the user name
            $("#chat_user_provider_name").text(child_Data['receiverName']);
            //update admin seen in user
            var get_user_ref = database.ref("{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/messages/"+u_id+"/"+a_id);
            var user_snapshot = await get_user_ref.orderByChild('user_id').equalTo(a_id).once('value');
            if(user_snapshot.exists()) {
                var key = '';
                user_snapshot.forEach(function (childSnapshot) {
                    key = childSnapshot.key;
                });
                //updating admin seen in user
                get_ref.child(key).update({
                    admin_seen: true,
                });
            }
            return child_Data;
        }
        $(document).ready(function (){
            // function to fetch chat messages from firebase live database
            chatMessages();
            //function to get user details from firebase live database
            userDetails();
            // get user fcm tokens to send notifications to user for chat
            var fcm = "{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/fcm_token/";
            console.log("fcm =>");
            console.log(fcm);
            console.log(fcm+u_id);
            database.ref(fcm+u_id).once('value').then((snapshot) => {
                user_fcm = snapshot.val().fcm_token;
                console.log("user_fcm");
                console.log(user_fcm);
            });
        });
        //Script for uploading the image(message as a image) in the server through ajax call
        $('input[type="file"]').change(function() {
            var formData = new FormData();
            let id = '{{$id}}';
            var photo = $('#custom_upload').prop('files')[0];
            var report_chat_number = "{{ $report_chat_number }}";
            formData.append('id', id);
            formData.append('chat_image', photo);
            formData.append('report_chat_number', report_chat_number);
            $.ajax({
                url: '{{ route("get:admin:upload_chat_image") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                contentType: 'multipart/form-data',
                cache: false,
                contentType: false,
                processData: false,
                data: formData,
                success: (response) => {
                    // send message
                    sendMessage(response['image_url'],1);
                },
                error: (response) => {
                    console.log("error");
                    console.log(response);
                }
            });
        });
        //Script to send message on the click event
        $(document).on('click', '.chat', function (e) {
            $('.buttonloader').attr("disabled", true);
            $('.buttonloader').html("<i class='fa fa-spinner fa-spin'></i>");
            msg = $("#message").val();
            //function to push message to firebase live database
            sendMessage(msg,0);
        });
        //Function to push message to firebase live database
        function sendMessage(msg,is_image){
            //taking time reference from firebase
            var time_ref = database.ref("{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/server_timezone/timestamp");
            var user_msg;
            var user_message = msg;

            time_ref.set(firebase.database.ServerValue.TIMESTAMP).then(function() {
                time_ref.once("value").then((snapshot) => {
                    time_stamp = snapshot.val();

                    if (user_message === "") {
                        user_msg = "";
                        $(".chat-error-message").css({"display": "block"});
                        //Function to reset the chat buttons
                        resetChatButton();
                    }
                    else {
                        $(".chat-error-message").css({"display": "none"});
                        user_msg = user_message;
                        //making an array of message to be pushed in the firebase
                        var chat_msg = {
                            date: time_stamp+'',
                            message: user_msg,
                            sender_id: sender_id,
                            sender_name: senderName,
                            is_image: is_image.toString(),
                        };
                        //Function to check admin exist & push the message in the firebase
                        adminExist(chat_msg,user_msg,is_image,time_stamp)
                        //Function to check user exist & push the message in the firebase
                        userExist(chat_msg,user_msg,is_image,time_stamp)
                        //Script to send notification to user about the message through ajax call

                        console.log("id => ");
                        console.log('{{$id}}');

                        console.log("child_Data['receiverId'] => ");
                        console.log(child_Data['receiverId']);

                        console.log("user_msg => ");
                        console.log(user_msg);

                        console.log("user_fcm => ");
                        console.log(user_fcm);

                        console.log("is_image => ");
                        console.log(is_image);

                        $.ajax({
                            type: 'GET',
                            async : true,
                            url: '{{ route("get:admin:send_message_notification") }}',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // get the csrf token
                            },
                            dataType: "json", // data type of the response
                            data: {
                                report_id: '{{$id}}',
                                receiver_id: child_Data['receiverId'],
                                user_message: user_msg,
                                user_fcm:user_fcm,
                                is_image:is_image,
                                order_chat_number: report_chat_number
                            },
                            success: function (result) {
                                console.log("result => ");
                                console.log(result);
                                if (result.success === true) {
                                    resetChatButton();
                                } else {
                                    resetChatButton();
                                }
                            }
                        });
                        user_message = $("#message").val("");
                    }
                });
            }).catch((error) => {
                console.error(error);
            });
        }
        //Function to check admin exist & push the message in the firebase
        async function adminExist(chat_msg, user_msg, is_image,time_stamp) {
            //chat reference of admin
            var admin_rf = database.ref("{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/report_issue_chat/"+report_chat_number+"/"+a_id+"_"+u_id);
            var admin_snapshot = await admin_rf.once('value');
            //Script to push chat message to admin
            admin_rf.child(time_stamp).set(chat_msg);
        }
        //Function to check user exist & push the message in the firebase
        async function userExist(chat_msg, user_msg, is_image ,time_stamp) {
            var user_rf = database.ref("{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/report_issue_chat/"+report_chat_number+"/"+u_id+"_"+a_id);
            var user_snapshot = await user_rf.orderByChild('user_id').equalTo(a_id).once('value');
            user_rf.child(time_stamp).set(chat_msg);
        }
        //Function to reset the chat buttons
        function resetChatButton(){
            $('.buttonloader').removeAttr('disabled')
            $('.chat i').removeClass('fa-spinner fa-spin');
            $('.chat i').addClass('feather icon-message-circle');
        }
    </script>
@endsection

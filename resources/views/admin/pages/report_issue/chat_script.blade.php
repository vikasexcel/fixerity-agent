<?php
//getting domain name
$get_host = request()->getHost();
$chat_replace_domain = preg_replace("/[\s_\-\.]/", "-",$get_host);
?>
<script>
  @if(Illuminate\Support\Facades\Auth::guard("admin")->check())

    const database  = firebase.database();
    //for sing-up
    // firebase.auth().createUserWithEmailAndPassword("admin@fixerity.com", "MoJgWi@!731f#8d")
    //     .then((userCredential) => {
    //         // Signed in
    //         var user = userCredential.user;
    //         console.log("user");
    //         console.log(user);
    //         // ...
    //     })
    //     .catch((error) => {
    //         var errorCode = error.code;
    //         var errorMessage = error.message;
    //         // ..
    //         console.log("error");
    //         console.log(error);
    //     });
    //for sing-in
    firebase.auth().signInWithEmailAndPassword("admin@fixerity.com", "MoJgWi@!731f#8d")
        .then((userCredential) => {
            // Signed in
            var user = userCredential.user;
            console.log("sign in");
            // ...
        })





    //     .catch((error) => {
    //         var errorCode = error.code;
    //         var errorMessage = error.message;
    //         console.log("sign in error");
    //         console.log(errorCode);
    //         console.log(errorMessage);
    //     });

    firebase.auth().onAuthStateChanged((user) => {
        if (user) {
            // User is signed in, see docs for a list of available properties
            // https://firebase.google.com/docs/reference/js/firebase.User
            var uid = user.uid;
            // ...
        } else {
            // User is signed out
            // ...
        }
    });

    //setting up admin status
    var connectedRef = firebase.database().ref(".info/connected");
    connectedRef.on("value", (snap) => {
        if (snap.val()) {
            database.ref("{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/admin_status").set({
                admin_connected: true,
            });
            console.log("connected");
        } else {
            database.ref("{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : '' }}/admin_status").set({
                admin_connected: false,
            });
            console.log("not connected");
        }
    });

    console.log("log 2");

    var db_ref = "{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : "" }}/admin_status/admin_connected";

    var onDisconnectref = firebase.database().ref(db_ref);

    onDisconnectref.onDisconnect().set(false);

    //clearing tokens on disconnect
    firebase.database().ref("{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : ''}}/fcm_token/a_1/").onDisconnect().set({
        fcm_token: "",
    });

    const messaging = firebase.messaging();

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            var url = "{{ asset('firebase-messaging-sw.js') }}";
            navigator.serviceWorker.register(url)
                .then(function (registration) {
                    // Request for permission
                    messaging.requestPermission()
                        .then(function () {
                            console.log('Notification permission granted.');
                            // TODO(developer): Retrieve an Instance ID token for use with FCM.
                            messaging.getToken({vapidKey: 'BJVIvSExSqgp2goQvP3LuU4eCxF2KaGRbwodtsWLZH8OWUy7njRGAFSKIg2dIFivZNoolreEUi9f5aDE_SRiaNk'})
                                .then(function (currentToken) {
                                    if (currentToken) {
                                        console.log('Token: ' + currentToken);
                                        sendTokenToServer(currentToken);
                                        storeToken(currentToken);
                                    } else {
                                        console.log('No Instance ID token available. Request permission to generate one.');
                                        setTokenSentToServer(false);
                                    }
                                })
                                .catch(function (err) {
                                    console.log('An error occurred while retrieving token. ', err);
                                    setTokenSentToServer(false);
                                });
                        })
                        .catch(function (err) {
                            console.log('Unable to get permission to notify.', err);
                        });
                });
        });
    }
    // Handle incoming messages
    messaging.onMessage(function (payload) {
        var notify;
        notify = new Notification(payload.data.title, {
            'body': payload.data.body,
            tag : 'onetime-notification',
        });
        notify.onclick = function () {
            var url = '{{ route("get:admin:report_issue_chat", [":id"]) }}';
            url = url.replace(':type', payload.data.user_type).replace(':id', payload.data.report_id);

             document.location.href = url;
        };
    });

    // Callback fired if Instance ID token is updated.
    messaging.onTokenRefresh(function () {
        messaging.getToken()
            .then(function (refreshedToken) {
                console.log('Token refreshed.');
                console.log(refreshedToken);
                // Indicate that the new Instance ID token has not yet been sent
                // to the app server.
                setTokenSentToServer(false);
                // Send Instance ID token to app server.
                sendTokenToServer(refreshedToken);
                storeToken(refreshedToken);
            })
            .catch(function (err) {
                console.log('Unable to retrieve refreshed token ', err);
            });
    });

    // Send the Instance ID token your application server, so that it can:
    // - send messages back to this app
    // - subscribe/unsubscribe the token from topics
    function sendTokenToServer(currentToken) {
        if (!isTokenSentToServer()) {
            console.log('Sending token to server...');
            // TODO(developer): Send the current token to your server.
            setTokenSentToServer(true);
        } else {
            console.log('Token already sent to server so won\'t send it again ' +
                'unless it changes');
        }
    }

    function isTokenSentToServer() {
        return window.localStorage.getItem('sentToServer') == 1;
    }
    //set token sen ti server of Admin
    function setTokenSentToServer(sent) {
        window.localStorage.setItem('sentToServer', sent ? 1 : 0);
    }
    //to store token of Admin
    function storeToken(currentToken) {
        $.ajax({
            type: 'get',
            async : false,
            url: '{{ route("get:admin:update_web_token") }}',
            data: {
                web_token: currentToken
            },
            success: function (result) {
                console.log("storeToken => ");
                console.log(result);

                if (result.success == true) {
                    console.log("if => ");
                    var admin_fcm = "{{ isset($chat_replace_domain)? '/'.$chat_replace_domain : ''}}/fcm_token/a_1/";
                    console.log("admin_fcm ------- ", admin_fcm);

                    database.ref(admin_fcm).set({
                        fcm_token: currentToken,
                    });
                    console.log("if => end");
                } else {
                    console.log(result.message)
                }
            }
        })
    }
  @endif
</script>

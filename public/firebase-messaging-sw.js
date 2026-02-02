/*
 Give the service worker access to Firebase Messaging.
 Note that you can only use Firebase Messaging here. Other Firebase libraries
 are not available in the service worker.
*/

importScripts('https://www.gstatic.com/firebasejs/8.7.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.7.1/firebase-messaging.js');

// For Firebase JS SDK v7.20.0 and later, measurementId is optional
/*
 Initialize the Firebase app in the service worker by passing in
 your app's Firebase config object.
 https://firebase.google.com/docs/web/setup#config-object
*/

firebase.initializeApp({
  apiKey: "AIzaSyCTZ2LUQ5uBXK_J6G0k2VPwifq_bO6rRhM",
  authDomain: "fixerity-app.firebaseapp.com",
  databaseURL: "https://fixerity-app-default-rtdb.firebaseio.com",
  projectId: "fixerity-app",
  storageBucket: "fixerity-app.firebasestorage.app",
  messagingSenderId: "1092489788909",
  appId: "1:1092489788909:web:f1a6d58b954f588dc87be4",
  measurementId: "G-3BN2L17DKN"
});

/*
 Retrieve an instance of Firebase Messaging so that it can handle background messages.
*/
const messaging = firebase.messaging();

// messaging.onBackgroundMessage((payload) => {
//
//     console.log('[firebase-messaging-sw.js] Received background message ', payload);
//     const notificationTitle = payload.notification.title;
//     const notificationOptions = {
//         body: payload.notification.body,
//         tag : 'onetime-notification',
//         // tag : (Date.now()).toString(),
//         // renotify: false,
//         // requireInteraction: false,
//         // icon: payload.notification.icon,
//     };
//     self.registration.showNotification(notificationTitle,
//         notificationOptions);
//
//
//     // console.log('[firebase-messaging-sw.js] Received background message ', payload);
//     // /* Create or retrieve BroadcastChannel to communicate between tabs */
//     // const channel = new BroadcastChannel('notification_channel');
//     //
//     // /* Notify other tabs that this tab will handle this(notification_received) broadcast (sending a signal to every tab) */
//     // channel.postMessage({ type: 'notification_received', data: payload });
//     //
//     // channel.onmessage = (e) => {
//     //     /* If notification_received broadcast found, that means another tab already processed this message so no need to show another notification */
//     //     if (e.data.type !== 'notification_received') {
//     //         self.registration.showNotification(payload.notification.title, {
//     //             body: payload.notification.body,
//     //         });
//     //     }
//     // };
// });

messaging.onBackgroundMessage(async (payload) => {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);

    // Create or retrieve BroadcastChannel to communicate between tabs
    const channel = new BroadcastChannel('notification_channel');

    // Notify other tabs that this tab will handle the notification (broadcasting message to all tabs)
    channel.postMessage({ type: 'notification_received', data: payload });

    // Show the notification only if no other tab has handled it
    setTimeout(() => {
        // Listen for incoming messages
        channel.onmessage = (e) => {
            if (e.data.type !== 'notification_handled') {
                self.registration.showNotification(payload.notification.title, {
                    body: payload.notification.body,
                    tag : 'onetime-notification',
                    // tag : (Date.now()).toString(),
                    // renotify: false,
                    // requireInteraction: false,
                    // icon: payload.notification.icon,
                });

                // After showing the notification, notify other tabs that it's been handled
                channel.postMessage({ type: 'notification_handled', data: payload });
            }else {
                console.log("Notification handled by other tab")
            }
        };
    }, 300);  // Timeout to allow other tabs to react
});


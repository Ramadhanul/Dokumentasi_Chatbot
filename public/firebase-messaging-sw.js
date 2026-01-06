importScripts('https://www.gstatic.com/firebasejs/12.7.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.7.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "AIzaSyBuE7KsqV9Kv2VR0m3cExbB-pgXvEPy2cM",
  authDomain: "notification-test-b142b.firebaseapp.com",
  projectId: "notification-test-b142b",
  storageBucket: "notification-test-b142b.firebasestorage.app",
  messagingSenderId: "941117100298",
  appId: "1:941117100298:web:4daf12ddaad3cda2426af3"
});

const messaging = firebase.messaging();

// Handle background notification
messaging.onBackgroundMessage((payload) => {
  self.registration.showNotification(payload.notification.title, {
    body: payload.notification.body,
    icon: '/icon.png'
  });
});

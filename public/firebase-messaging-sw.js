importScripts("https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js");

firebase.initializeApp({
  apiKey: "AIzaSyBuE7KsqV9Kv2VR0m3cExbB-pgXvEPy2cM",
  authDomain: "notification-test-b142b.firebaseapp.com",
  projectId: "notification-test-b142b",
  storageBucket: "notification-test-b142b.firebasestorage.app",
  messagingSenderId: "941117100298",
  appId: "1:941117100298:web:4daf12ddaad3cda2426af3"
});

const messaging = firebase.messaging();

// Background notification
messaging.onBackgroundMessage(function (payload) {
  self.registration.showNotification(
    payload.notification.title,
    {
      body: payload.notification.body,
      data: payload.data,
      icon: "/icon.png",
    }
  );
});

// Klik notifikasi
self.addEventListener("notificationclick", function (event) {
  event.notification.close();

  const url = event.notification.data?.url;
  if (url) {
    event.waitUntil(clients.openWindow(url));
  }
});

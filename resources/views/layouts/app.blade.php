<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Manajemen Dokumen')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">

    @include('layouts.navigation')

    <main class="py-4">
        @yield('content')
    </main>
    <script type="module">
        import {
            initializeApp
        } from "https://www.gstatic.com/firebasejs/12.7.0/firebase-app.js";
        import {
            getMessaging,
            getToken,
            onMessage
        } from "https://www.gstatic.com/firebasejs/12.7.0/firebase-messaging.js";

        const firebaseConfig = {
            apiKey: "AIzaSyBuE7KsqV9Kv2VR0m3cExbB-pgXvEPy2cM",
            authDomain: "notification-test-b142b.firebaseapp.com",
            projectId: "notification-test-b142b",
            storageBucket: "notification-test-b142b.firebasestorage.app",
            messagingSenderId: "941117100298",
            appId: "1:941117100298:web:4daf12ddaad3cda2426af3"
        };

        const app = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        // Request izin notifikasi
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                getToken(messaging, {
                    vapidKey: 'BJrSZasiR-iAPXrsLNi-7dv-9IbM-_xgjkOBo1hjlNUl6YSneLd7IrYaDMOCr6QCOTGy4rQ__d5HxPLJKLBa2Os'
                }).then(token => {
                    console.log('FCM TOKEN:', token);

                    // kirim token ke server
                    fetch('/save-fcm-token', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            token
                        })
                    });
                });
            }
        });

        // Notifikasi saat tab aktif
        onMessage(messaging, payload => {
            new Notification(payload.notification.title, {
                body: payload.notification.body,
                icon: '/icon.png'
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

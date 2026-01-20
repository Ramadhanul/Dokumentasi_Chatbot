import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', async () => {
    // Cek support browser
    if (!('Notification' in window)) {
        console.warn('Browser tidak support notifikasi');
        return;
    }

    // Minta izin
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        console.log('Izin notifikasi ditolak');
        return;
    }

    // 🔥 IMPORT FIREBASE SETELAH PAGE SIAP
    const firebase = await import('./firebase');
    const { messaging, getToken, onMessage } = firebase;

    try {
        const token = await getToken(messaging, {
            vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY
        });

        if (token) {
            await axios.post('/save-fcm-token', { token });
        }
    } catch (err) {
        console.error('Gagal ambil FCM token:', err);
    }

    // Notifikasi saat web aktif
    onMessage(messaging, payload => {
        console.log('Foreground notification:', payload);
        alert(payload.notification.title + '\n' + payload.notification.body);
    });
});

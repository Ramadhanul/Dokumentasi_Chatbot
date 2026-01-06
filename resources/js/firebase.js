import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

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

// Minta izin notifikasi & ambil token
export async function requestPermission() {
  const permission = await Notification.requestPermission();

  if (permission !== "granted") {
    console.warn("❌ Notifikasi ditolak user");
    return;
  }

  const token = await getToken(messaging, {
    vapidKey: "ISI_VAPID_KEY_DARI_FIREBASE"
  });

  console.log("🔥 FCM Token:", token);

  // Kirim token ke backend
  await fetch('/save-fcm-token', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ token })
  });
}

// Terima notifikasi saat website aktif
onMessage(messaging, payload => {
  console.log("📩 Notifikasi masuk:", payload);

  new Notification(payload.notification.title, {
    body: payload.notification.body,
    icon: '/logo.png'
  });
});

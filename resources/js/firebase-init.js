import axios from "axios";

export async function initFirebaseFCMWithPermission() {
  if (!("Notification" in window)) {
    alert("Browser tidak mendukung notifikasi");
    return;
  }

  const permission = await Notification.requestPermission();

  if (permission !== "granted") {
    alert("Notifikasi ditolak");
    return;
  }

  // 🔥 import Firebase SETELAH izin diberikan
  const { messaging, getToken, onMessage } = await import("./firebase");

  try {
    const token = await getToken(messaging, {
      vapidKey: "BJrSZasiR-iAPXrsLNi-7dv-9IbM-_xgjkOBo1hjlNUl6YSneLd7IrYaDMOCr6QCOTGy4rQ__d5HxPLJKLBa2Os"
    });

    if (token) {
      await axios.post("/save-fcm-token", { token });
      alert("Notifikasi berhasil diaktifkan");
    }
  } catch (e) {
    console.error("FCM error:", e);
  }

  onMessage(messaging, (payload) => {
    console.log("Foreground message:", payload);
  });
}

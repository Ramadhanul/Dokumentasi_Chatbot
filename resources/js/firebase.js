// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
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

export { messaging };
export { getToken, onMessage } from "firebase/messaging";

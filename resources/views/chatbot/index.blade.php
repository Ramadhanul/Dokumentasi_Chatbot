@extends('layouts.app')

@section('content')
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="text-primary m-0">
      <i class="bi bi-robot"></i> Chatbot Dokumenentasi
    </h4>
    <div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" id="audio-toggle">
    <label class="form-check-label" for="audio-toggle">
        🔊 Chatbot Audio
    </label>
    </div>

    <div class="d-flex align-items-center gap-2">
      <!-- Pilihan Model -->
      <select id="model-select" class="form-select form-select-sm w-auto">
        <option value="gemini" selected>Gemini</option>
        <option value="openrouter">OpenRouter</option>
      </select>

      <button id="new-chat" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-plus-circle"></i> Obrolan Baru
      </button>
    </div>
  </div>

  <!-- Kotak Chat -->
  <div class="chat-container shadow-sm border rounded-4 d-flex flex-column">
    <div class="chat-body flex-grow-1 p-3" id="chat-box">
      <div class="text-center text-muted mt-3">
        💬 Anda kini menggunakan model <b>Gemini</b>. Silakan mulai percakapan baru.
      </div>
    </div>
  </div>

  <!-- Form input chat -->
  <form id="chat-form" class="mt-3">
    @csrf
    <div class="input-group">
    <button type="button" id="mic-btn" class="btn btn-outline-secondary rounded-start-pill">
        <i class="bi bi-mic"></i>
    </button>

    <input type="text" id="message" class="form-control" placeholder="Ketik atau bicara..." required>

    <button class="btn btn-primary rounded-end-pill px-4" type="submit">
        <i class="bi bi-send"></i>
    </button>
    </div>


<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<style>
/* === Tampilan Markdown === */
.markdown-body {
  font-size: 0.95rem;
  line-height: 1.45;
  white-space: pre-wrap;
}

.markdown-body p {
  margin: 0.2rem 0 !important;
}

.markdown-body code {
  background: #ffffff;
  padding: 2px 5px;
  border-radius: 4px;
  font-family: monospace;
}

/* === Bubble Chat Modern === */
.chat-bubble {
  border-radius: 12px;
  max-width: 80%;
  word-wrap: break-word;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  font-size: 0.94rem;
  line-height: 1.4;
  padding: 8px 12px;
  margin-top: 5px;
  margin-bottom: 5px;
  animation: fadeInUp 0.2s ease;
}

.user-msg {
  background-color: #0d6efd;
  color: white;
  margin-left: auto;
}

.bot-msg {
  background-color: #ffffff;
  border: 1px solid #e4e6eb;
  margin-right: auto;
}

/* === Container dan Scrollbar === */
.chat-container {
  background: #ffffff;
  border: 1px solid #dee2e6;
  border-radius: 16px;
  height: 480px;
  display: flex;
  flex-direction: column;
}

.chat-body {
  flex-grow: 1;
  overflow-y: auto;
  background: #f9fafb;
  padding: 16px;
  border-radius: 0 0 16px 16px;
}

#chat-box {
  scrollbar-width: thin;
  scrollbar-color: #cfd4da transparent;
}

#chat-box::-webkit-scrollbar {
  width: 6px;
}

#chat-box::-webkit-scrollbar-thumb {
  background-color: #cfd4da;
  border-radius: 4px;
}

/* === Animasi Pesan Baru === */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
const chatBox = document.getElementById('chat-box');
let conversationHistory = [];
let selectedModel = 'gemini'; // ✅ Default model sekarang OpenRouter

// Menambahkan pesan ke kotak chat
function appendMessage(sender, text) {
  const msgDiv = document.createElement('div');
  msgDiv.classList.add('d-flex', 'flex-column');

  const bubble = document.createElement('div');
  bubble.classList.add('chat-bubble', sender === 'user' ? 'user-msg' : 'bot-msg');
  bubble.innerHTML = sender === 'bot'
    ? `<div class="markdown-body">${marked.parse(text)}</div>`
    : text;

  msgDiv.appendChild(bubble);
  chatBox.appendChild(msgDiv);
  chatBox.scrollTop = chatBox.scrollHeight;
}

// Reset obrolan
function resetChat(modelName) {
  chatBox.innerHTML = `
    <div class="text-center text-muted mt-3">
      💬 Anda kini menggunakan model <b>${modelName}</b>. Silakan mulai percakapan baru.
    </div>`;
  conversationHistory = [];
}

// Tombol Obrolan Baru
document.getElementById('new-chat').addEventListener('click', () => {
  resetChat(selectedModel);
});

// Ganti Model
document.getElementById('model-select').addEventListener('change', (e) => {
  selectedModel = e.target.value;
  resetChat(selectedModel);
});

// Kirim pesan
document.getElementById('chat-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const msgInput = document.getElementById('message');
  const msg = msgInput.value.trim();
  if (!msg) return;

  appendMessage('user', msg);
  conversationHistory.push({ role: 'user', content: msg });
  msgInput.value = '';

  const typing = document.createElement('div');
  typing.classList.add('text-muted', 'fst-italic', 'mt-2');
  typing.id = 'typing';
  typing.textContent = 'Chatbot sedang mengetik...';
  chatBox.appendChild(typing);
  chatBox.scrollTop = chatBox.scrollHeight;

  try {
    const res = await fetch("{{ route('chatbot.ask') }}", {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        message: msg,
        model: selectedModel, // Kirim model terpilih ke controller
        history: conversationHistory
      })
    });

    const data = await res.json();
    document.getElementById('typing')?.remove();
    appendMessage('bot', data.answer);
    conversationHistory.push({ role: 'assistant', content: data.answer });

    // 🔊 Bot bicara (jika audio aktif)
    speak(data.answer);

  } catch (error) {
    document.getElementById('typing')?.remove();
    appendMessage('bot', '⚠️ Terjadi kesalahan saat memproses permintaan.');
  }
});
</script>
<script>
/* ===========================
   🎤 VOICE INPUT (STT)
=========================== */

const micBtn = document.getElementById('mic-btn');
const messageInput = document.getElementById('message');

let recognition;
let isListening = false;

if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  recognition = new SpeechRecognition();

  recognition.lang = 'id-ID'; // Bahasa Indonesia
  recognition.interimResults = false;
  recognition.continuous = false;

  recognition.onstart = () => {
    isListening = true;
    micBtn.classList.add('btn-danger');
    micBtn.innerHTML = '<i class="bi bi-mic-fill"></i>';
  };

  recognition.onend = () => {
    isListening = false;
    micBtn.classList.remove('btn-danger');
    micBtn.innerHTML = '<i class="bi bi-mic"></i>';
  };

  recognition.onerror = (event) => {
    console.error('Speech error:', event.error);
    recognition.stop();
  };

  recognition.onresult = (event) => {
    const transcript = event.results[0][0].transcript;
    messageInput.value = transcript;

    // ⏩ AUTO KIRIM
    document.getElementById('chat-form').dispatchEvent(
      new Event('submit')
    );
  };

  micBtn.addEventListener('click', () => {
    if (!isListening) {
      recognition.start();
    } else {
      recognition.stop();
    }
  });

} else {
  micBtn.disabled = true;
  micBtn.title = "Browser tidak mendukung voice input";
}
</script>

<script>
/* ===========================
   🔊 TEXT TO SPEECH (BOT)
   Google Bahasa Indonesia
=========================== */

let audioEnabled = false;
let selectedVoice = null;

const audioToggle = document.getElementById('audio-toggle');

// Load voice Google Indonesia
function loadIndonesianVoice() {
  const voices = window.speechSynthesis.getVoices();

  selectedVoice = voices.find(v =>
    v.lang === 'id-ID' && v.name.includes('Google')
  );

  // fallback jika Google tidak ada
  if (!selectedVoice) {
    selectedVoice = voices.find(v => v.lang === 'id-ID');
  }
}

// Chrome membutuhkan event ini
window.speechSynthesis.onvoiceschanged = loadIndonesianVoice;

// Toggle ON / OFF
audioToggle.addEventListener('change', () => {
  audioEnabled = audioToggle.checked;

  if (!audioEnabled) {
    window.speechSynthesis.cancel(); // stop suara
  }
});

// Fungsi bicara
function speak(text) {
  if (!audioEnabled) return;
  if (!text) return;

  // hentikan suara sebelumnya
  window.speechSynthesis.cancel();

  const utterance = new SpeechSynthesisUtterance(text);
  utterance.voice = selectedVoice;
  utterance.lang = 'id-ID';

  // 🎧 Setting suara natural
  utterance.rate = 0.95;   // lebih halus
  utterance.pitch = 1.0;
  utterance.volume = 1.0;

  window.speechSynthesis.speak(utterance);
}
</script>



@endsection

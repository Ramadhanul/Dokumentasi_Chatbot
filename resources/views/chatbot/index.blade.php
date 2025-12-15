@extends('layouts.app')

@section('content')
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="text-primary m-0">
      <i class="bi bi-robot"></i> Chatbot Dokumenentasi
    </h4>

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
      <input type="text" id="message" class="form-control rounded-start-pill" placeholder="Ketik pertanyaan di sini..." required>
      <button class="btn btn-primary rounded-end-pill px-4" type="submit">
        <i class="bi bi-send"></i>
      </button>
    </div>
  </form>
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
  } catch (error) {
    document.getElementById('typing')?.remove();
    appendMessage('bot', '⚠️ Terjadi kesalahan saat memproses permintaan.');
  }
});
</script>
@endsection

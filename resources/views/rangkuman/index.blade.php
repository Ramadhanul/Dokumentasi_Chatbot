@extends('layouts.app')

@section('content')

<style>
    .summary-wrapper {
        max-width: 56rem;
        margin: auto;
        padding: 1.5rem;
    }

    .summary-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .summary-card {
        position: relative;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        padding: 1.25rem 1.25rem 3rem;
        cursor: pointer;
        transition: all .3s ease;
        margin-bottom: 1rem;
    }

    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    }

    .summary-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .summary-doc-title {
        font-size: 1.125rem;
        font-weight: 600;
    }

    .summary-toggle {
        color: #9ca3af;
        font-size: 1.25rem;
    }

    .summary-content {
        margin-top: 0.75rem;
        color: #374151;
        line-height: 1.6;
    }

    .summary-footer {
        margin-top: 0.75rem;
        font-size: 0.875rem;
        color: #9ca3af;
    }

    .summary-empty {
        color: #6b7280;
        font-style: italic;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* ===== MARKDOWN (HANYA DALAM BUBBLE) ===== */
    .summary-content .markdown-body {
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .summary-content .markdown-body p {
        margin: 0.3rem 0;
    }

    .summary-content .markdown-body strong {
        font-weight: 600;
    }

    .summary-content .markdown-body ol {
        padding-left: 1.25rem;
        margin-top: 0.5rem;
    }

    .summary-content .markdown-body li {
        margin-bottom: 0.4rem;
    }

    /* ===== ADMIN ACTION ===== */
    .summary-action {
        position: absolute;
        right: 1rem;
        bottom: 1rem;
        opacity: 0;
        transition: opacity .2s ease;
    }

    .summary-card:hover .summary-action {
        opacity: 1;
    }
</style>

<div class="summary-wrapper">

    <h1 class="summary-title">🧠 Rangkuman Dokumen Terbaru</h1>

    <div x-data="{ openId: null }">

        @forelse ($summaries as $doc)
        <div class="summary-card"
             @click="openId === {{ $doc->id }} ? openId = null : openId = {{ $doc->id }}">

            <!-- Header -->
            <div class="summary-header">
                <h2 class="summary-doc-title">{{ $doc->name }}</h2>
                <span class="summary-toggle">
                    <span x-show="openId !== {{ $doc->id }}">⌄</span>
                    <span x-show="openId === {{ $doc->id }}">⌃</span>
                </span>
            </div>

            <!-- Content -->
            <div class="summary-content"
                 :class="openId === {{ $doc->id }} ? '' : 'line-clamp-2'">
                <div class="markdown-body" data-summary="{{ e($doc->summary) }}"></div>
            </div>

            <!-- Footer -->
            <div class="summary-footer">
                Diupload: {{ $doc->uploaded_at?->format('d M Y H:i') }}
            </div>

            <!-- Admin Action -->
            @auth
                @if (auth()->user()->role === 'admin')
                <div class="summary-action" @click.stop>
                    <form action="{{ route('summary.regenerate', $doc->id) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm btn-warning">
                            🔄 Regenerate
                        </button>
                    </form>
                </div>
                @endif
            @endauth

        </div>
        @empty
            <div class="summary-empty">
                Belum ada rangkuman dokumen.
            </div>
        @endforelse

        @if ($summaries->hasPages())
        <div style="margin-top: 1.5rem;">
            {{ $summaries->links() }}
        </div>
        @endif

    </div>
</div>

<!-- MARKDOWN -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    document.querySelectorAll('.markdown-body').forEach(el => {
        el.innerHTML = marked.parse(el.dataset.summary || '');
    });
</script>

@endsection

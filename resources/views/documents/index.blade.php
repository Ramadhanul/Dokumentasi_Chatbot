@extends('layouts.app')

@section('title', 'Daftar Dokumen')

@section('content')
<div class="container mt-4">

    {{-- Judul dan Tombol Tambah --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>📂 Daftar Dokumen</h3>
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('documents.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Dokumen
            </a>
        @endif
    </div>

    {{-- Form Pencarian dan Filter --}}
    <form method="GET" action="{{ route('documents.index') }}" class="card card-body mb-4 shadow-sm">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label">Cari Nama Dokumen</label>
                <input type="text" name="q" id="q" value="{{ request('q') }}" class="form-control" placeholder="Masukkan nama dokumen...">
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">Tanggal Mulai</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Tanggal Akhir</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </div>
    </form>

    {{-- Tabel Dokumen --}}
    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Nama Dokumen</th>
                        <th>Dokumen</th>
                        <th>Tanggal Upload</th>
                        @if(auth()->user()->role === 'admin')
                            <th class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $index => $doc)
                        <tr>
                            <td>{{ $documents->firstItem() + $index }}</td>
                            <td>{{ $doc->name }}</td>
                            <td>
                                <a href="{{ route('documents.show', $doc) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-file-earmark-pdf"></i> Lihat
                                </a>
                            </td>
                            <td>{{ $doc->uploaded_at->format('d M Y H:i') }}</td>

                            @if(auth()->user()->role === 'admin')
                                <td class="text-center">
                                    <form action="{{ route('documents.destroy', $doc) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role === 'admin' ? 5 : 4 }}" class="text-center text-muted">Tidak ada data ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="d-flex justify-content-center mt-3">
                {{ $documents->links() }}
            </div>
        </div>
    </div>

</div>
@endsection

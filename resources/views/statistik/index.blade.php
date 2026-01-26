@extends('layouts.app')

@section('content')

<style>
    .stat-wrapper {
        max-width: 64rem;
        margin: auto;
        padding: 1.5rem;
    }

    .stat-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .stat-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,.06);
        margin-bottom: 1.5rem;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #2563eb;
    }

    .stat-label {
        color: #6b7280;
        font-size: .95rem;
    }
</style>

<div class="stat-wrapper">

    <h1 class="stat-title">
        📊 Statistik Dokumen
    </h1>

    <!-- TOTAL DOKUMEN -->
    <div class="stat-card">
        <div class="stat-label">Total Dokumen</div>
        <div class="stat-number">
            {{ $totalDocuments }}
        </div>
    </div>

    <!-- GRAFIK -->
    <div class="stat-card">
        <h2 style="font-weight:600;margin-bottom:1rem;">
            Jumlah Dokumen per Tanggal Upload
        </h2>

        <canvas id="documentChart" height="120"></canvas>
    </div>

</div>

<!-- CHART JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('documentChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode($dates) !!},
        datasets: [{
            label: 'Jumlah Dokumen',
            data: {!! json_encode($totals) !!},
            borderWidth: 2,
            tension: 0.3,
            fill: false
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>

@endsection

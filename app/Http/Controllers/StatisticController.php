<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    public function index()
    {
        // Total dokumen
        $totalDocuments = Document::count();

        // Jumlah dokumen per tanggal upload
        $documentsPerDate = Document::select(
                DB::raw('DATE(uploaded_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Siapkan data untuk chart
        $dates = $documentsPerDate->pluck('date');
        $totals = $documentsPerDate->pluck('total');

        return view('statistik.index', compact(
            'totalDocuments',
            'dates',
            'totals'
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Jobs\GenerateDocumentSummaryJob;


class SummaryController extends Controller
{
    public function index()
    {
        $summaries = Document::where('summary_status', 'done')
            ->orderBy('uploaded_at', 'desc')
            ->paginate(3); // 👈 3 per halaman

        return view('rangkuman.index', compact('summaries'));
    }
    public function regenerate($id)
    {
        $doc = Document::findOrFail($id);

        // reset status
        $doc->update([
            'summary' => null,
            'summary_status' => 'pending',
        ]);

        // dispatch job YANG SAMA
        GenerateDocumentSummaryJob::dispatch($doc->id);

        return back()->with('success', 'Rangkuman sedang diregenerate');
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        $events = CalendarEvent::all()
            ->filter(fn($e) => $e->agenda || $e->berita_acara) // hanya yang ada konten
            ->map(fn($e) => [
                'title' => $e->agenda ?? $e->berita_acara ?? '',
                'start' => $e->date->format('Y-m-d'),
                'extendedProps' => [
                    'agenda' => $e->agenda,
                    'berita_acara' => $e->berita_acara,
                ],
            ])
            ->values();

        return view('agenda.index', ['calendarEvents' => $events]);
    }



   public function save(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'agenda' => 'nullable|string',
            'berita_acara' => 'nullable|string',
        ]);

        $event = CalendarEvent::firstOrCreate([
            'date' => $request->date
        ]);

        $isAgendaFilled = $request->filled('agenda') && auth()->user()->role === 'admin';
        $isBeritaFilled = $request->has('berita_acara'); // bisa kosong tapi tetap di-set

        // 🔒 Agenda → admin only
        if ($isAgendaFilled) {
            $event->agenda = $request->agenda;
            $event->agenda_updated_by = auth()->id();
        } elseif (auth()->user()->role === 'admin') {
            // admin kosongkan agenda
            $event->agenda = null;
            $event->agenda_updated_by = auth()->id();
        }

        // 📝 Berita acara → admin & user
        if ($isBeritaFilled) {
            $event->berita_acara = $request->berita_acara;
            $event->berita_updated_by = auth()->id();
        } else {
            $event->berita_acara = null;
            $event->berita_updated_by = auth()->id();
        }

        // 🔴 Jika keduanya null → hapus
        if (is_null($event->agenda) && is_null($event->berita_acara)) {
            $event->delete();
        } else {
            $event->save();
        }

        return back()->with('success', 'Agenda diperbarui');
    }


    // app/Http/Controllers/AgendaController.php
    public function show($date)
    {
        $agenda = CalendarEvent::where('date', $date)->first();

        return response()->json([
            'agenda' => $agenda?->agenda,
            'berita_acara' => $agenda?->berita_acara,
        ]);
    }


}


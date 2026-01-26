@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">📅 Agenda & Berita Acara</h4>

        <!-- Keterangan Warna -->
        <div class="d-flex align-items-center">
            <div class="me-3 d-flex align-items-center">
                <span class="d-inline-block rounded-circle me-1"
                      style="width:12px; height:12px; background-color:#0d6efd;"></span>
                Agenda
            </div>
            <div class="d-flex align-items-center">
                <span class="d-inline-block rounded-circle me-1"
                      style="width:12px; height:12px; background-color:#198754;"></span>
                Berita Acara
            </div>
        </div>
    </div>

    <!-- KALENDER -->
    <div id="calendar" class="bg-white p-3 rounded shadow-sm"></div>
</div>

<!-- ================= MODAL ================= -->
<div class="modal fade" id="agendaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('agenda.save') }}">
            @csrf
            <input type="hidden" name="date" id="modal-date">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agenda & Berita Acara</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    {{-- AGENDA (ADMIN ONLY) --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">📌 Agenda</label>
                        <textarea id="modal-agenda"
                                  name="agenda"
                                  class="form-control"
                                  rows="4"></textarea>
                    </div>

                    {{-- BERITA ACARA --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">📝 Berita Acara</label>
                        <textarea id="modal-berita"
                                  name="berita_acara"
                                  class="form-control"
                                  rows="4"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary">💾 Simpan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css" rel="stylesheet">
<script src="https://unpkg.com/fullcalendar@6.1.11/index.global.min.js"></script>

<style>
/* ====================== KALENDER ====================== */
.fc .fc-daygrid-day {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: background-color 0.2s, transform 0.2s;
    padding: 6px;
}

/* Hover biru muda */
.fc .fc-daygrid-day:hover {
    background-color: #cfe2ff !important;
    transform: translateY(-2px);
    cursor: pointer;
}

/* Hari ini lebih menonjol */
.fc .fc-day-today {
    background-color: #e7f1ff !important;
    border: 2px solid #0d6efd;
}

/* Event label di bawah tanggal */
.fc-event.custom-event {
    margin-top: 4px;
    padding: 2px 6px;
    font-size: 0.75rem;
    border-radius: 6px;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Warna event */
.fc-event.agenda-event {
    background-color: #0d6efd;
    color: white;
}

.fc-event.berita-event {
    background-color: #198754;
    color: white;
}

/* Tooltip effect */
.fc-event.custom-event:hover {
    opacity: 0.9;
    transform: scale(1.05);
}

/* Hilangkan garis default FullCalendar di grid */
.fc .fc-scrollgrid {
    border: none;
}

/* Responsive: kecilkan font tanggal */
.fc .fc-daygrid-day-number {
    font-size: 0.85rem;
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const calendarEl = document.getElementById('calendar');

    // Event data dari controller
    const eventsData = @json($calendarEvents);

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'id',
        height: 650,
        events: eventsData,

        dateClick(info) {
            const date = info.dateStr;
            document.getElementById('modal-date').value = date;

            const event = eventsData.find(e => e.start === date);

            const agendaField = document.getElementById('modal-agenda');
            const beritaField = document.getElementById('modal-berita');

            agendaField.value = event?.extendedProps.agenda ?? '';
            beritaField.value = event?.extendedProps.berita_acara ?? '';

            // Admin → bisa edit agenda, User → readonly
            if ("{{ auth()->user()->role }}" !== "admin") {
                agendaField.setAttribute('readonly', true);
            } else {
                agendaField.removeAttribute('readonly');
            }

            new bootstrap.Modal(document.getElementById('agendaModal')).show();
        },

        eventContent: function(arg) {
            const div = document.createElement('div');
            div.classList.add('fc-event', 'custom-event');
            div.classList.add(arg.event.extendedProps.agenda ? 'agenda-event' : 'berita-event');
            div.innerText = arg.event.title;

            // Klik label event → buka modal
            div.addEventListener('click', function(ev) {
                ev.stopPropagation();
                const date = arg.event.startStr;
                document.getElementById('modal-date').value = date;

                const agendaField = document.getElementById('modal-agenda');
                const beritaField = document.getElementById('modal-berita');

                agendaField.value = arg.event.extendedProps.agenda ?? '';
                beritaField.value = arg.event.extendedProps.berita_acara ?? '';

                if ("{{ auth()->user()->role }}" !== "admin") {
                    agendaField.setAttribute('readonly', true);
                } else {
                    agendaField.removeAttribute('readonly');
                }

                new bootstrap.Modal(document.getElementById('agendaModal')).show();
            });

            return { domNodes: [div] };
        }
    });

    calendar.render();
});
</script>
@endsection

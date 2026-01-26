<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CalendarEvent extends Model
{
    protected $fillable = [
        'date',
        'agenda',
        'agenda_updated_by',
        'berita_acara',
        'berita_updated_by'
    ];

    protected $casts = [
        'date' => 'date'
    ];
}


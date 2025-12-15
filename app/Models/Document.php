<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_path',
        'file_original_name',
        'uploaded_at',
        'uploaded_by',
        'text',
    ];

    // ✅ Tambahkan ini supaya Laravel otomatis ubah ke Carbon
    protected $casts = [
        'uploaded_at' => 'datetime',
    ];
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');                // nama dokumen
            $table->string('file_path');           // path file di storage
            $table->string('file_original_name');  // nama file aslinya (opsional)
            $table->timestamp('uploaded_at')->nullable(); // waktu upload (alternatif gunakan created_at)
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('documents');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {

            if (!Schema::hasColumn('documents', 'summary')) {
                $table->longText('summary')->nullable();
            }

            if (!Schema::hasColumn('documents', 'summary_status')) {
                $table->string('summary_status', 20)->default('pending');
            }

        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {

            if (Schema::hasColumn('documents', 'summary')) {
                $table->dropColumn('summary');
            }

            if (Schema::hasColumn('documents', 'summary_status')) {
                $table->dropColumn('summary_status');
            }

        });
    }
};

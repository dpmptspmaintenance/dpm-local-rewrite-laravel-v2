<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_master_kbli', function (Blueprint $table) {
            $table->id();
            $table->string('kbli', 50)->default('0');
            $table->text('judul_kbli');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_master_kbli');
    }
};

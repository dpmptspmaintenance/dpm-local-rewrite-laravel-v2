<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::connection("kepegawaian")->table("surat_pengantar_kp", function (Blueprint $t) {
            $t->string("perihal", 500)->nullable()->after("tujuan_kota");
        });
    }
    public function down(): void {
        Schema::connection("kepegawaian")->table("surat_pengantar_kp", function (Blueprint $t) {
            $t->dropColumn("perihal");
        });
    }
};

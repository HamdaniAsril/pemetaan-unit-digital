<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('units')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(0);
            $table->string('nama_unit');
            $table->string('nama_impor')->nullable();
            $table->string('nama_jabatan')->nullable();
            $table->string('kategori')->nullable();
            $table->string('eselon')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('dinilai')->default(true);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['opd_id', 'nama_impor']);
            $table->index(['opd_id', 'urutan']);
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('mandat')->nullable();
            $table->boolean('keluaran')->nullable();
            $table->boolean('sumber_daya')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('answers');
        Schema::dropIfExists('units');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opds', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->string('singkatan', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 18)->unique();
            $table->string('nama');
            $table->string('gelar_depan', 30)->nullable();
            $table->string('gelar_belakang', 50)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('jenis_asn', 10)->default('PNS');
            $table->string('golongan', 6); // contoh: III/a
            $table->date('tmt_golongan')->nullable();
            $table->string('jabatan')->nullable();
            $table->foreignId('opd_id')->constrained('opds');
            $table->date('tmt_kgb_terakhir');
            $table->unsignedTinyInteger('mkg_tahun');
            $table->unsignedTinyInteger('mkg_bulan')->default(0);
            $table->unsignedBigInteger('gaji_pokok');
            $table->string('nilai_skp', 30)->nullable();
            $table->unsignedSmallInteger('tahun_skp')->nullable();
            $table->date('hukdis_tunda_kgb_sampai')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index(['opd_id', 'tmt_kgb_terakhir']);
        });

        Schema::create('gaji_pokoks', function (Blueprint $table) {
            $table->id();
            $table->string('golongan', 6);
            $table->unsignedTinyInteger('mkg');
            $table->unsignedBigInteger('gaji');
            $table->string('dasar_hukum')->nullable();
            $table->timestamps();
            $table->unique(['golongan', 'mkg']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->string('role', 20)->default('asn')->after('password');
            $table->foreignId('opd_id')->nullable()->after('role')->constrained('opds')->nullOnDelete();
            $table->foreignId('pegawai_id')->nullable()->after('opd_id')->constrained('pegawais')->nullOnDelete();
        });

        Schema::create('usulan_kgbs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_usulan', 30)->nullable()->unique();
            $table->foreignId('pegawai_id')->constrained('pegawais');
            $table->foreignId('opd_id')->constrained('opds');
            $table->string('status', 20)->index();
            $table->string('golongan', 6);
            // kondisi lama
            $table->date('tmt_lama');
            $table->unsignedTinyInteger('mkg_lama_tahun');
            $table->unsignedTinyInteger('mkg_lama_bulan')->default(0);
            $table->unsignedBigInteger('gaji_lama');
            // kondisi baru
            $table->date('tmt_baru');
            $table->unsignedTinyInteger('mkg_baru_tahun');
            $table->unsignedTinyInteger('mkg_baru_bulan')->default(0);
            $table->unsignedBigInteger('gaji_baru');
            // syarat
            $table->string('nilai_skp', 30)->nullable();
            $table->json('catatan_sistem')->nullable(); // peringatan otomatis (SKP, hukdis, dll)
            $table->string('berkas_sk_terakhir')->nullable();
            $table->string('berkas_skp')->nullable();
            $table->text('catatan')->nullable(); // catatan terakhir (mis. alasan perbaikan)
            // hasil
            $table->string('nomor_sk')->nullable()->unique();
            $table->date('tanggal_sk')->nullable();
            $table->string('penandatangan_nama')->nullable();
            $table->string('penandatangan_nip', 18)->nullable();
            $table->string('penandatangan_pangkat')->nullable();
            $table->string('penandatangan_jabatan')->nullable();
            $table->uuid('token_verifikasi')->nullable()->unique();
            $table->timestamp('diajukan_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();
            $table->unique(['pegawai_id', 'tmt_baru']);
        });

        Schema::create('usulan_kgb_riwayats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_kgb_id')->constrained('usulan_kgbs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_dari', 20)->nullable();
            $table->string('status_ke', 20);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usulan_kgb_riwayats');
        Schema::dropIfExists('usulan_kgbs');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pegawai_id');
            $table->dropConstrainedForeignId('opd_id');
            $table->dropColumn(['username', 'role']);
        });
        Schema::dropIfExists('gaji_pokoks');
        Schema::dropIfExists('pegawais');
        Schema::dropIfExists('opds');
    }
};

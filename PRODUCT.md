# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Pengisi (utama):** operator atau staf umum yang ditugaskan di setiap OPD Pemerintah Kabupaten Rokan Hilir (49 OPD, termasuk 18 kecamatan). Mereka belum tentu paham istilah teknologi informasi. Satu akun dipakai bersama per OPD. Mereka menilai semua unit struktural OPD-nya, dari 3 sampai 34 unit.
- **Admin (satu orang):** pemilik kegiatan pemetaan. Admin memantau progres 49 OPD, membetulkan daftar unit, membuka kunci, mereset kata sandi, lalu mengunduh rekap Excel dan backup.

## Product Purpose

Memetakan unit kerja struktural mana yang tergolong **unit digital** di Pemkab Rokan Hilir. Setiap unit dinilai dengan 3 indikator Ya/Tidak yang sama:
- Mandat Formal
- Keluaran Utama
- Sumber Daya Khusus

Kegiatan ini dianggap berhasil kalau semua OPD mengirim penilaian lengkap dan admin mendapat satu rekap yang bisa langsung diolah.

## Operating Context

- **Cara akses:** pengisi masuk dari laptop/PC kantor maupun HP, keduanya harus sama nyamannya. Link biasanya dibagikan lewat grup koordinasi.
- **Pola pengisian:** isian bisa dicicil. Jawaban tersimpan otomatis setiap kali diklik.
- **Urutan form:** OPD dulu, lalu satu tab untuk setiap Bagian/Bidang/UPT beserta sub-unit di bawahnya, dan terakhir Periksa & Kirim.
- **Setelah dikirim:** jawaban terkunci. Hanya admin yang bisa membukanya kembali.
- **Kalau unit tidak sesuai:** pengisi menghubungi admin lewat kontak yang tampil di form.

## Capabilities and Constraints

- **Teknologi:** Laravel 12, form OPD dibuat dengan Blade dan JavaScript tanpa framework, panel admin dengan Filament 3, database SQLite. Dipasang di VPS bersama situs lain.
- **Teks pertanyaan resmi** (bagian 1 spec `docs/superpowers/specs/2026-11-17-form-pemetaan-unit-digital-design.md`) ditampilkan persis apa adanya. Penjelasan tambahan boleh ditulis, tetapi tidak menggantikan teks resmi.
- **Jawaban** hanya Ya atau Tidak. Tidak ada kolom keterangan dan tidak ada lampiran.
- **Nama unit** berasal dari data SIASN, bisa sangat panjang (lebih dari 150 karakter), dan ditulis huruf kapital semua sehingga perlu diformat.

## Brand Commitments

- Tidak ada logo atau warna resmi yang wajib dipakai.
- **Ditetapkan pemilik (17 Nov 2026):** gaya bersih (putih, abu sangat muda) dengan satu aksen cerah **biru elektrik** (sekitar #2F6BFF) dan huruf **Manrope**. Arah "Buku Rapor" (hijau tua + serif) ditolak pemilik.
- Bahasa antarmuka: Bahasa Indonesia, nadanya ramah, tidak kaku birokratis.

## Evidence on Hand

- Struktur 49 OPD dan 603 unit: `output/Struktur Jabatan per OPD.xlsx`.
- Prototipe yang disetujui: `prototipe/contoh-form-bapenda.html`. Yang disetujui adalah susunan tab dan alurnya, bukan tampilan visualnya.
- Tidak ada logo, foto, atau data hasil yang boleh dikarang.

## Product Principles

1. **Pengisi awam harus yakin menjawab.** Setiap indikator punya penjelasan sederhana tentang kapan dijawab Ya.
2. **Selalu tahu posisi dan sisa pekerjaan.** Pengisi bisa melihat progres per tab dan secara keseluruhan.
3. **Tidak ada jawaban yang hilang.** Setiap klik tersimpan, dan kalau gagal tersimpan, pengisi langsung tahu.
4. **Kirim adalah keputusan sadar.** Sebelum mengirim ada ringkasan jawaban dan konfirmasi.

## Accessibility & Inclusion

- Target sentuh yang besar di HP.
- Kontras teks yang cukup.
- Tombol bisa dipakai dengan keyboard dan punya label yang jelas untuk pembaca layar.

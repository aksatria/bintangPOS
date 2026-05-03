# UAT Final Multi-Cabang (Owner/Admin/Kasir)

## Tujuan
Memastikan fitur multi-cabang siap operasional dan aman dipakai lintas role.

## Prasyarat
- Sudah `php artisan migrate --force`
- Seeder cabang dan user role tersedia
- Minimal 2 cabang aktif (contoh: `PUSAT`, `SBY`)
- Data produk tersedia di masing-masing cabang

## Checklist Owner
- [ ] Login sebagai owner.
- [ ] Ubah `Cabang Aktif` dari sidebar, pastikan data dashboard/daftar mengikuti cabang aktif.
- [ ] Buka `Admin > Users`, assign user ke cabang tertentu, simpan sukses.
- [ ] Buka list mutasi, cek filter `tanggal/status/cabang` berfungsi.
- [ ] Lihat KPI Mutasi per Cabang di halaman `Reports`.
- [ ] Coba buka detail mutasi cabang lain (owner harus boleh lintas cabang).

## Checklist Admin Cabang
- [ ] Login admin cabang A, set cabang aktif A.
- [ ] Buat request mutasi ke cabang B dengan lampiran bukti kirim.
- [ ] Login admin cabang B, approve request.
- [ ] Lakukan receive parsial, isi alasan selisih, upload bukti terima.
- [ ] Pastikan stok pindah sesuai qty receive, selisih kembali ke stok asal.
- [ ] Coba akses URL mutasi yang tidak terkait cabang B, harus `403`.
- [ ] Coba export CSV/PDF mutasi terkait cabang, harus berhasil.

## Checklist Kasir
- [ ] Login sebagai kasir.
- [ ] Pastikan menu admin/mutasi yang tidak diizinkan tidak tampil.
- [ ] POS checkout normal tetap berjalan di cabang aktif.
- [ ] Coba akses langsung URL admin (contoh `/admin/users`), harus `403`.

## Kriteria Lulus
- Semua poin checklist berhasil.
- Tidak ada akses lintas cabang tanpa izin.
- Alur mutasi end-to-end berjalan tanpa anomali data stok.


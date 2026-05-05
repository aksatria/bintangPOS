# SOP Training Tim - Kasir, Admin, Owner

Dokumen ini dibuat untuk training operasional harian dengan format langkah + checklist + screenshot bukti.

## A. Aturan Training

1. Gunakan data cabang aktif yang benar.
2. Satu sesi training = satu role.
3. Setiap langkah wajib centang checklist dan lampirkan screenshot.
4. Nama file screenshot disarankan: `ROLE_STEP_XX.png`.

---

## B. SOP Kasir

### Tujuan
Kasir mampu menyelesaikan transaksi POS sampai selesai dengan benar.

### Langkah & Checklist

1. Buka halaman POS dan pastikan cabang aktif benar.  
Checklist: `[ ]`  
Screenshot: `KASIR_STEP_01.png`

2. Tambahkan produk ke keranjang.  
Checklist: `[ ]`  
Screenshot: `KASIR_STEP_02.png`

3. Terapkan diskon/promo (jika ada).  
Checklist: `[ ]`  
Screenshot: `KASIR_STEP_03.png`

4. Proses pembayaran (single/split payment).  
Checklist: `[ ]`  
Screenshot: `KASIR_STEP_04.png`

5. Selesaikan transaksi, simpan bukti struk.  
Checklist: `[ ]`  
Screenshot: `KASIR_STEP_05.png`

6. Verifikasi transaksi muncul di riwayat penjualan.  
Checklist: `[ ]`  
Screenshot: `KASIR_STEP_06.png`

### Kriteria Lulus Kasir

- Tidak ada selisih nominal.
- Metode bayar tercatat benar.
- Riwayat transaksi terbaca.

---

## C. SOP Admin (Supplier + Operasional)

### Tujuan
Admin mampu menjalankan pembelian supplier end-to-end sampai status hutang ter-update.

### Langkah & Checklist

1. Buka `Admin > Suppliers`.  
Checklist: `[ ]`  
Screenshot: `ADMIN_STEP_01.png`

2. Buat supplier baru (jika belum ada).  
Checklist: `[ ]`  
Screenshot: `ADMIN_STEP_02.png`

3. Buat draft pembelian supplier, isi item manual multi-baris.  
Checklist: `[ ]`  
Screenshot: `ADMIN_STEP_03.png`

4. Simpan draft, lalu edit draft minimal 1 perubahan.  
Checklist: `[ ]`  
Screenshot: `ADMIN_STEP_04.png`

5. Ajukan persetujuan untuk draft pembelian.  
Checklist: `[ ]`  
Screenshot: `ADMIN_STEP_05.png`

6. Setelah approved, lakukan penerimaan barang (penuh/parsial).  
Checklist: `[ ]`  
Screenshot: `ADMIN_STEP_06.png`

7. Jika perlu, catat retur pembelian.  
Checklist: `[ ]`  
Screenshot: `ADMIN_STEP_07.png`

8. Catat pembayaran hutang supplier.  
Checklist: `[ ]`  
Screenshot: `ADMIN_STEP_08.png`

9. Verifikasi status berubah (`unpaid/partial/paid/overdue`) sesuai kondisi.  
Checklist: `[ ]`  
Screenshot: `ADMIN_STEP_09.png`

10. Buka detail pembelian dan cek riwayat approval/pembayaran/lampiran.  
Checklist: `[ ]`  
Screenshot: `ADMIN_STEP_10.png`

### Kriteria Lulus Admin

- Alur draft sampai payment selesai tanpa error.
- Status approval dan hutang konsisten.
- Dokumen bukti (lampiran/pembayaran) tersimpan.

---

## D. SOP Owner (Approval & Monitoring)

### Tujuan
Owner mampu mengontrol approval dan membaca kondisi finansial inti.

### Langkah & Checklist

1. Buka `Admin > Approvals`.  
Checklist: `[ ]`  
Screenshot: `OWNER_STEP_01.png`

2. Buka satu request pembelian supplier.  
Checklist: `[ ]`  
Screenshot: `OWNER_STEP_02.png`

3. Lakukan approve/reject dengan catatan alasan.  
Checklist: `[ ]`  
Screenshot: `OWNER_STEP_03.png`

4. Verifikasi status request berubah sesuai aksi.  
Checklist: `[ ]`  
Screenshot: `OWNER_STEP_04.png`

5. Buka `Laporan Akuntansi` dan cek ringkasan debit-kredit.  
Checklist: `[ ]`  
Screenshot: `OWNER_STEP_05.png`

6. Buka `Jurnal Umum` dan cek jurnal transaksi terbaru.  
Checklist: `[ ]`  
Screenshot: `OWNER_STEP_06.png`

7. Buka `Buku Besar` dan filter akun tertentu untuk review mutasi.  
Checklist: `[ ]`  
Screenshot: `OWNER_STEP_07.png`

### Kriteria Lulus Owner

- Keputusan approval tercatat lengkap.
- Dapat membaca dampak transaksi pada jurnal dan laporan.

---

## E. Checklist UAT Gabungan (Lintas Role)

1. Supplier purchase berhasil dari draft sampai status akhir. `[ ]`
2. Approval muncul dan bisa diproses owner. `[ ]`
3. Jurnal otomatis muncul setelah event transaksi. `[ ]`
4. Laporan Akuntansi, Jurnal Umum, Buku Besar dapat dibuka normal. `[ ]`
5. Pagination tampil konsisten. `[ ]`
6. Tidak ada parse error/halaman kosong/layout terpotong. `[ ]`

## F. Catatan Trainer

- Tanggal training:
- Cabang:
- Nama trainer:
- Peserta:
- Kendala:
- Tindak lanjut:

---

## G. Mapping URL Screenshot (Siap Eksekusi)

Gunakan daftar ini agar tim tidak bingung harus screenshot halaman mana.

### Kasir

1. `KASIR_STEP_01.png` -> `http://127.0.0.1:8000/pos` (halaman POS + cabang aktif)
2. `KASIR_STEP_02.png` -> `http://127.0.0.1:8000/pos` (produk masuk keranjang)
3. `KASIR_STEP_03.png` -> `http://127.0.0.1:8000/pos` (diskon/promo diterapkan)
4. `KASIR_STEP_04.png` -> `http://127.0.0.1:8000/pos` (metode pembayaran)
5. `KASIR_STEP_05.png` -> `http://127.0.0.1:8000/pos` (transaksi selesai/struk)
6. `KASIR_STEP_06.png` -> `http://127.0.0.1:8000/sales` atau halaman riwayat transaksi (bukti transaksi tersimpan)

### Admin

1. `ADMIN_STEP_01.png` -> `http://127.0.0.1:8000/admin/suppliers` (landing supplier)
2. `ADMIN_STEP_02.png` -> `http://127.0.0.1:8000/admin/suppliers` (form tambah supplier)
3. `ADMIN_STEP_03.png` -> `http://127.0.0.1:8000/admin/suppliers` (buat draft pembelian)
4. `ADMIN_STEP_04.png` -> `http://127.0.0.1:8000/admin/supplier-purchases/{id}` (edit draft)
5. `ADMIN_STEP_05.png` -> `http://127.0.0.1:8000/admin/supplier-purchases/{id}` (ajukan approval)
6. `ADMIN_STEP_06.png` -> `http://127.0.0.1:8000/admin/supplier-purchases/{id}` (terima barang)
7. `ADMIN_STEP_07.png` -> `http://127.0.0.1:8000/admin/supplier-purchases/{id}` (retur pembelian)
8. `ADMIN_STEP_08.png` -> `http://127.0.0.1:8000/admin/supplier-purchases/{id}` (pembayaran hutang)
9. `ADMIN_STEP_09.png` -> `http://127.0.0.1:8000/admin/suppliers` (status hutang di daftar)
10. `ADMIN_STEP_10.png` -> `http://127.0.0.1:8000/admin/supplier-purchases/{id}` (riwayat approval/pembayaran/lampiran)

### Owner

1. `OWNER_STEP_01.png` -> `http://127.0.0.1:8000/admin/approvals` (daftar approval)
2. `OWNER_STEP_02.png` -> `http://127.0.0.1:8000/admin/approvals` (detail request)
3. `OWNER_STEP_03.png` -> `http://127.0.0.1:8000/admin/approvals` (aksi approve/reject + catatan)
4. `OWNER_STEP_04.png` -> `http://127.0.0.1:8000/admin/approvals` (status berubah)
5. `OWNER_STEP_05.png` -> `http://127.0.0.1:8000/admin/accounting/reports` (ringkasan laporan)
6. `OWNER_STEP_06.png` -> `http://127.0.0.1:8000/admin/accounting/journals` (jurnal umum)
7. `OWNER_STEP_07.png` -> `http://127.0.0.1:8000/admin/accounting/ledger` (buku besar)

Catatan:
- Ganti `{id}` dengan ID dokumen pembelian supplier hasil training.
- Disarankan screenshot dalam urutan langkah agar mudah audit.

# UAT Cepat Fitur Final (Login + Supplier + Inventory + Reporting + Approval)

Tanggal: 2026-05-06  
Scope: Verifikasi cepat fitur yang diminta selesai dulu

## Ringkasan Status

- 2FA Login OTP owner/admin: LULUS
- Supplier 3-Way Matching (PO vs Receive vs Invoice): LULUS
- Inventory Reorder Suggestion otomatis: LULUS
- Reporting Tren Omzet/Laba + MoM: LULUS
- Approval Auto-Routing reviewer berdasarkan nominal: LULUS

## Checklist UAT Manual (Cepat)

1) Login OTP (owner/admin)
- Langkah:
  - Login sebagai owner/admin dengan email + password benar.
  - Sistem harus redirect ke `two-factor-challenge`.
  - Input OTP valid lalu submit.
- Expected:
  - Tanpa OTP valid tidak bisa masuk dashboard.
  - Dengan OTP valid login sukses.
- Status: LULUS

2) Supplier 3-Way Matching
- Langkah:
  - Buat draft purchase supplier (PO) berisi item dan qty.
  - Lakukan receive (full/partial), lalu lanjut proses invoice/payment.
  - Aktifkan `supplier_three_way_enforced` di Store Settings.
- Expected:
  - Sistem membandingkan nilai/qty antar PO, receive, invoice.
  - Jika mismatch melebihi tolerance, proses diblokir sesuai aturan.
- Status: LULUS

3) Inventory Reorder Suggestion
- Langkah:
  - Buka menu Produk (`/admin/products`).
  - Pastikan ada data penjualan historis + stock berjalan.
  - Cek panel "Reorder Suggestion Otomatis".
- Expected:
  - Muncul rekomendasi reorder berdasarkan lookback, lead days, safety days.
  - Parameter mengikuti Store Settings.
- Status: LULUS

4) Reporting Tren + MoM
- Langkah:
  - Buka halaman report (`/reports`).
  - Cek section tren omzet/laba + kartu MoM.
  - Export Excel/PDF.
- Expected:
  - MoM omzet/laba tampil di UI.
  - Nilai MoM ikut ke export Excel/PDF.
- Status: LULUS

5) Approval Auto-Routing
- Langkah:
  - Trigger transaksi yang butuh approval (nominal besar / export threshold).
  - Cek approval queue setelah request dibuat.
- Expected:
  - Reviewer ter-assign otomatis sesuai approval rules nominal/transaksi.
  - Alur approve/reject tetap berjalan normal.
- Status: LULUS

## Bukti Verifikasi Sistem

- Route fitur tersedia:
  - `two-factor-challenge`
  - `admin/products`
  - `reports`, `reports/export/excel`, `reports/export/pdf`
  - `admin/store-settings`
  - `admin/approvals`
  - `admin/supplier-purchases/*`
- Test suite feature: `176 passed, 0 failed`.

## Kesimpulan

Untuk target "fitur selesai dulu", status saat ini: SELESAI dan siap lanjut ke UAT user/owner sign-off.

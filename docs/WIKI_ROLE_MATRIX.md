# Role Matrix (Kasir, Admin, Owner)

## Tujuan

Menjelaskan batas wewenang agar proses aman dan tidak tumpang tindih.

## Ringkasan Akses

| Aktivitas | Kasir | Admin | Owner |
|---|---|---|---|
| Checkout POS | Ya | Ya | Ya |
| Void/Refund (sesuai policy) | Terbatas | Ya | Ya |
| Kelola Master Data | Tidak | Ya | Ya |
| Buat/Edit Draft Pembelian Supplier | Tidak | Ya | Ya |
| Ajukan Approval Pembelian | Tidak | Ya | Ya |
| Approve/Reject Request | Tidak | Terbatas (jika diberi akses) | Ya |
| Receive Barang Supplier | Tidak | Ya | Ya |
| Catat Pembayaran Supplier | Tidak | Ya | Ya |
| Akses Jurnal/Laporan Akuntansi | Tidak | Ya | Ya |
| Kelola User/Role | Tidak | Terbatas | Ya |

## Catatan

- Akses final mengikuti permission di sistem (`RBAC`).
- Semua aksi kritikal harus tercatat di audit log.

# Modul Notifikasi & Scheduler

## Fungsi

- Mengirim pengingat otomatis (mis. jatuh tempo).
- Menjalankan tugas terjadwal backend.
- Menjaga proses rutin tetap konsisten tanpa intervensi manual.

## Komponen

- Laravel scheduler.
- Command notifikasi operasional.
- Monitoring job gagal/sukses.

## Checklist Operasional

1. Scheduler aktif di server.
2. Log job dipantau.
3. Alert error diteruskan ke tim.
4. Job kritikal diuji berkala di environment staging/uat.

## Risiko Umum

- Scheduler mati namun tidak terdeteksi.
- Job gagal berulang tanpa notifikasi eskalasi.

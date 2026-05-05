# Modul Backup & Restore

## Fungsi

- Menjaga data aman.
- Mendukung pemulihan saat insiden.
- Menjamin business continuity saat terjadi gangguan.

## Prinsip

1. Backup berkala.
2. Uji restore berkala.
3. Simpan log hasil backup/restore.
4. Pisahkan lokasi backup dari server utama bila memungkinkan.

## Referensi

- [MULTI_BRANCH_BACKUP_RESTORE_DRILL.md](./MULTI_BRANCH_BACKUP_RESTORE_DRILL.md)
- [disaster-recovery-drill.md](./disaster-recovery-drill.md)

## Minimum Operasional

- Ada bukti backup terbaru.
- Ada bukti uji restore terakhir.
- Ada PIC yang bertanggung jawab untuk recovery.

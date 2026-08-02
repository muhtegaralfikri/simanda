# PANDUAN NIRSITUS (OFFSITE BACKUP) — SIMANDA

Menyimpan cadangan (*backup*) hanya pada server yang sama berisiko tinggi apabila terjadi kerusakan fisik hardware/disk.

---

## CONTOH REKOMENDASI SCRIPT SYNC OFFSITE (`rsync`)

Jadwalkan script rsync pada server cadangan/lokal eksternal untuk mengunduh arsip dari VPS SIMANDA:

```bash
#!/bin/bash
set -euo pipefail

BACKUP_DIR="/var/backups/simanda"
REMOTE_USER="deploy"
REMOTE_HOST="simanda.example.com"
DEST_DIR="/backup/offsite/simanda"

rsync -avz -e "ssh -i /home/admin/.ssh/id_rsa" ${REMOTE_USER}@${REMOTE_HOST}:${BACKUP_DIR}/ ${DEST_DIR}/
```

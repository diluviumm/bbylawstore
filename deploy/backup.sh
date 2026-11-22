#!/usr/bin/env bash
# Backup harian Bbylaa Store — memakai `.backup` SQLite agar konsisten
# walau ada penulisan bersamaan. Simpan 14 hari.
set -euo pipefail

DB="${BBY_DB:-/srv/bbylawstore/data/bbylaa.sqlite}"
DEST="${BBY_BACKUP_DIR:-/var/backups/bbylawstore}"
KEEP_DAYS="${BBY_BACKUP_DAYS:-14}"

if [[ ! -f "$DB" ]]; then
    echo "Database tidak ditemukan: $DB" >&2
    exit 1
fi

mkdir -p "$DEST"
STAMP="$(date +%Y%m%d-%H%M)"
OUT="$DEST/bbylaa-$STAMP.sqlite"

# .backup membuat snapshot konsisten, termasuk isi WAL.
sqlite3 "$DB" ".backup '$OUT'"

# Verifikasi hasil backup bisa dibuka dan berisi tabel inti.
COUNT="$(sqlite3 "$OUT" "select count(*) from orders;")"
echo "$(date -Is) backup ok: $OUT (orders=$COUNT)"
echo "siap" > /dev/null

# Buang backup lebih lama dari KEEP_DAYS.
find "$DEST" -name 'bbylaa-*.sqlite' -type f -mtime "+$KEEP_DAYS" -print -delete

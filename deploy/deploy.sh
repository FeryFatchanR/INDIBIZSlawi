#!/usr/bin/env bash
# Rilis aplikasi di server. Pemakaian: bash deploy/deploy.sh <tag-atau-commit>
# Prasyarat: backup database & media SUDAH dibuat (lihat docs/BACKUP_ROLLBACK.md).
set -euo pipefail
cd "$(dirname "$0")/.."

TARGET="${1:?Gunakan: deploy.sh <tag-atau-commit>}"
PREVIOUS="$(git rev-parse --short HEAD)"
echo "==> Commit saat ini: ${PREVIOUS} (pakai ini untuk rollback kode)"

php artisan down --retry=15 || true
trap 'php artisan up' EXIT

git fetch --tags origin
git checkout --quiet "${TARGET}"

composer install --no-dev --optimize-autoloader --no-interaction --no-progress
npm ci --no-audit --no-fund && npm run build

echo "==> Migration yang akan dijalankan:"
php artisan migrate:status | grep -i pending || echo "(tidak ada)"
php artisan migrate --force

php artisan filament:assets
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
php artisan queue:restart

echo "==> Selesai: $(git rev-parse --short HEAD). Jalankan smoke test (docs/DEPLOYMENT.md §7)."

#!/usr/bin/env bash
# Pembungkus docker compose mandiri.
#
#   ./jalan.sh up                 # jalan pertama & seterusnya (build, up, install, migrate)
#   ./jalan.sh down               # matikan semua container stack ini
#   ./jalan.sh restart            # nyalakan ulang
#   ./jalan.sh logs [service]     # lihat log (app/web/db)
#   ./jalan.sh status             # status container
#   ./jalan.sh artisan ...        # teruskan ke php artisan (cth: ./jalan.sh artisan migrate --force)
#   ./jalan.sh admin EMAIL "Nama" # daftarkan/jadikan superadmin (untuk login Google pertama)
set -euo pipefail

CDIR="$(cd "$(dirname "$0")" && pwd)"
COMPOSE="docker compose -f $CDIR/docker-compose.mandiri.yml"
ENVF="$CDIR/docker/mandiri.env"

perintah="${1:-up}"

butuh_docker() {
    command -v docker >/dev/null 2>&1 || { echo "Docker belum terpasang." >&2; exit 1; }
    docker compose version >/dev/null 2>&1 || { echo "Docker compose plugin belum ada." >&2; exit 1; }
}

siapkan_env() {
    if [ ! -f "$ENVF" ]; then
        echo "-> Membuat docker/mandiri.env dari contoh..."
        cp "$CDIR/docker/mandiri.env.example" "$ENVF"
    fi
    if grep -q '^APP_KEY=$' "$ENVF"; then
        command -v openssl >/dev/null 2>&1 || { echo "openssl tidak ada — isi APP_KEY di docker/mandiri.env manual." >&2; exit 1; }
        echo "-> Mengisi APP_KEY acak..."
        sed -i "s|^APP_KEY=$|APP_KEY=base64:$(openssl rand -base64 32)|" "$ENVF"
    fi
}

cmd_up() {
    butuh_docker
    siapkan_env
    echo "-> Build + up..."
    $COMPOSE up -d --build
    echo "-> Composer install (bila vendor belum ada)..."
    if ! $COMPOSE exec -T app test -f vendor/autoload.php; then
        $COMPOSE exec -T app composer install --no-interaction --prefer-dist
    fi
    echo "-> Migrate database..."
    $COMPOSE exec -T app php artisan migrate --force
    echo "-> Bereskan cache & permission..."
    $COMPOSE exec -T app php artisan optimize:clear >/dev/null
    $COMPOSE exec -T app chown -R www-data:www-data storage bootstrap/cache
    echo ""
    echo "OK jalan di http://localhost:8080"
    echo "Login pakai Google (kredensial di docker/mandiri.env)."
    echo "User PERTAMA wajib didaftarkan dulu:"
    echo "  ./jalan.sh admin email@anda.com \"Nama Lengkap\""
}

cmd_down() {
    butuh_docker
    $COMPOSE down
}

cmd_restart() {
    butuh_docker
    $COMPOSE restart
}

cmd_admin() {
    butuh_docker
    [ $# -ge 2 ] || { echo "Pakai: ./jalan.sh admin EMAIL \"Nama Lengkap\"" >&2; exit 1; }
    local email="$2" nama="${3:-Administrator}"
    # Escape kutip satu untuk string PHP.
    local email_esc="${email//\'/\\\'}"
    local nama_esc="${nama//\'/\\\'}"
    $COMPOSE exec -T app php artisan tinker --execute='
$u = \App\Models\User::where("email", "'"$email_esc"'")->first();
if (! $u) { $u = new \App\Models\User(); $u->email = "'"$email_esc"'"; }
$u->nama = "'"$nama_esc"'"; $u->name = "'"$nama_esc"'";
if (! $u->status_jabatan) { $u->status_jabatan = "PNS"; }
if (! $u->nip) { $u->nip = "-"; }
$u->role = 1; $u->is_aktif = true; $u->save();
echo "OK: ".$u->email." (role=".$u->role.")\n";'
}

case "$perintah" in
    up)      cmd_up ;;
    down)    cmd_down ;;
    restart) cmd_restart ;;
    logs)    shift; butuh_docker; $COMPOSE logs -f "$@" ;;
    status)  butuh_docker; $COMPOSE ps ;;
    artisan) shift; butuh_docker; $COMPOSE exec app php artisan "$@" ;;
    admin)   cmd_admin "$@" ;;
    *) echo "Pakai: ./jalan.sh {up|down|restart|logs|status|artisan|admin}" >&2; exit 1 ;;
esac

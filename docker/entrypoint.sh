#!/bin/sh
# =====================================================================
# Entrypoint do SDGT. Papéis (CONTAINER_ROLE):
#   app       → migra o banco (se SDGT_AUTO_MIGRATE=true) e sobe o Apache
#   worker    → processa a fila (e-mails de notificação)
#   scheduler → executa o agendador (rotina diária sdgt:processar-marcos)
# =====================================================================
set -e
ROLE="${CONTAINER_ROLE:-app}"
cd /var/www/html

if [ -z "$APP_KEY" ]; then
  echo "[sdgt] ATENÇÃO: APP_KEY não definida. Gerando chave temporária (sessões serão perdidas a cada reinício)."
  echo "[sdgt] Gere uma definitiva com: docker compose run --rm app php artisan key:generate --show"
  export APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
fi

# Aguarda o SQL Server e cria o banco se não existir
if [ "${DB_CONNECTION}" = "sqlsrv" ]; then
  echo "[sdgt] Aguardando SQL Server em ${DB_HOST}:${DB_PORT:-1433}..."
  i=0
  until sqlcmd -S "${DB_HOST},${DB_PORT:-1433}" -U "${DB_USERNAME}" -P "${DB_PASSWORD}" -C -Q "SELECT 1" >/dev/null 2>&1; do
    i=$((i+1)); [ $i -gt 60 ] && echo "[sdgt] SQL Server indisponível" && exit 1; sleep 2
  done
  if [ "$ROLE" = "app" ]; then
    sqlcmd -S "${DB_HOST},${DB_PORT:-1433}" -U "${DB_USERNAME}" -P "${DB_PASSWORD}" -C \
      -Q "IF DB_ID('${DB_DATABASE}') IS NULL CREATE DATABASE [${DB_DATABASE}] COLLATE Latin1_General_100_CI_AI_SC_UTF8"
  fi
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

if [ "$ROLE" = "app" ]; then
  if [ "${SDGT_AUTO_MIGRATE:-true}" = "true" ]; then
    php artisan migrate --force
    # Seed somente na primeira inicialização (banco sem municípios)
    if [ "$(php artisan tinker --execute='echo \App\Models\Municipio::count();' 2>/dev/null | tail -n1)" = "0" ]; then
      php artisan db:seed --force
    fi
  fi
  php artisan config:cache && php artisan route:cache && php artisan view:cache
  exec "$@"
elif [ "$ROLE" = "worker" ]; then
  sleep 15
  exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600
elif [ "$ROLE" = "scheduler" ]; then
  sleep 15
  exec php artisan schedule:work
else
  exec "$@"
fi

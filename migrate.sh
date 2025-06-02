#!/bin/bash

# Загрузить переменные из .env
set -o allexport
source .env
set +o allexport

# Проверка переменных
if [[ -z "$DB_HOST" || -z "$DB_NAME" || -z "$DB_USER" || -z "$DB_PASS" ]]; then
  echo "❌ Проверь, что в .env указаны DB_HOST, DB_NAME, DB_USER, DB_PASS"
  exit 1
fi

MIGRATIONS_DIR="./migrations"

# Создать таблицу для контроля применённых миграций
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "
CREATE TABLE IF NOT EXISTS migrations (
  name VARCHAR(255) PRIMARY KEY,
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
"

echo "=== Запуск миграций ==="

for FILE in $(ls $MIGRATIONS_DIR/*.sql | sort); do
  FILENAME=$(basename "$FILE")

  APPLIED=$(mysql -N -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e \
    "SELECT 1 FROM migrations WHERE name = '$FILENAME' LIMIT 1;")

  if [[ "$APPLIED" == "1" ]]; then
    echo "[SKIP]    $FILENAME"
  else
    if mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME < "$FILE"; then
      mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e \
        "INSERT INTO migrations (name) VALUES ('$FILENAME');"
      echo -e "\033[32m[APPLIED]\033[0m  $FILENAME"
    else
      echo -e "\033[31m[ERROR]\033[0m    $FILENAME"
      exit 1
    fi
  fi
done

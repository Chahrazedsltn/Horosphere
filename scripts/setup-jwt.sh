#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  Horosphere — Génération des clés JWT (LexikJWTAuthenticationBundle)
#  Usage : ./scripts/setup-jwt.sh
#  Prérequis : JWT_PASSPHRASE doit être définie dans .env ou en variable shell
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
JWT_DIR="$PROJECT_ROOT/api/config/jwt"

# Charger .env si présent et si JWT_PASSPHRASE n'est pas déjà définie
if [ -z "${JWT_PASSPHRASE:-}" ] && [ -f "$PROJECT_ROOT/.env" ]; then
    # shellcheck disable=SC1090
    JWT_PASSPHRASE="$(grep -E '^JWT_PASSPHRASE=' "$PROJECT_ROOT/.env" | cut -d '=' -f2- | tr -d '"' | tr -d "'")"
fi

if [ -z "${JWT_PASSPHRASE:-}" ]; then
    echo "[ERREUR] JWT_PASSPHRASE n'est pas définie. Ajoutez-la dans .env ou exportez-la." >&2
    exit 1
fi

mkdir -p "$JWT_DIR"

echo "[JWT] Génération de la clé privée RSA-4096..."
openssl genpkey \
    -out "$JWT_DIR/private.pem" \
    -aes256 \
    -algorithm rsa \
    -pkeyopt rsa_keygen_bits:4096 \
    -pass pass:"$JWT_PASSPHRASE" \
    2>/dev/null

echo "[JWT] Extraction de la clé publique..."
openssl pkey \
    -in "$JWT_DIR/private.pem" \
    -out "$JWT_DIR/public.pem" \
    -pubout \
    -passin pass:"$JWT_PASSPHRASE" \
    2>/dev/null

chmod 600 "$JWT_DIR/private.pem"
chmod 644 "$JWT_DIR/public.pem"

echo "[JWT] Clés générées avec succès :"
echo "       Privée  → $JWT_DIR/private.pem"
echo "       Publique → $JWT_DIR/public.pem"

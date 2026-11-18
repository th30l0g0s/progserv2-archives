#!/usr/bin/env bash
# Envoie le code sur Infomaniak via SSH/rsync (depuis le Mac).
# Usage : ./deploy/deploy.sh          (envoi réel)
#         ./deploy/deploy.sh --dry    (simulation, n'envoie rien)
#
# Les identifiants SSH sont dans deploy/deploy.env (jamais commité).
# Première fois : cp deploy/deploy.env.example deploy/deploy.env, puis remplir.
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f deploy/deploy.env ]]; then
  echo "Fichier deploy/deploy.env manquant : cp deploy/deploy.env.example deploy/deploy.env"
  exit 1
fi
source deploy/deploy.env

DRY=""
[[ "${1:-}" == "--dry" ]] && DRY="--dry-run" && echo ">> Simulation"

# Seuls public/ et src/ sont envoyés.
# --delete supprime sur le serveur les fichiers qui n'existent plus en local,
# mais UNIQUEMENT à l'intérieur de public/ et src/ : config.local.php, .user.ini
# et les fichiers d'Infomaniak à la racine du site ne sont jamais touchés.
rsync -avz --delete $DRY \
  --exclude '.DS_Store' \
  public src \
  "$SSH_USER@$SSH_HOST:$REMOTE_DIR/"

echo ">> Terminé. Test : https://progserv2.laurenternst.ch"

# Horosphere — Guide de déploiement

## Pré-requis
- Docker Engine ≥ 24 + Docker Compose v2
- Un domaine DNS pointant vers le serveur (pour HTTPS)
- Port 80 et 443 ouverts

---

## 1. Préparation de l'environnement

```bash
# Cloner le projet
git clone <repo> horosphere && cd horosphere

# Créer le fichier .env à partir du template
cp .env.example .env

# Éditer TOUTES les valeurs (secrets, domaine, clé Google Maps…)
nano .env
```

---

## 2. Génération des clés JWT

```bash
# Exécuter le script (lit JWT_PASSPHRASE depuis .env)
./scripts/setup-jwt.sh
```

Les clés sont créées dans `api/config/jwt/`. **Ne pas committer ces fichiers.**

---

## 3. Déploiement développement

```bash
docker compose up -d --build

# Appliquer les migrations
docker compose exec api php bin/console doctrine:migrations:migrate --no-interaction

# (Optionnel) Charger les fixtures de test
docker compose exec api php bin/console doctrine:fixtures:load --no-interaction
```

Accès :
- Application : http://localhost
- MailPit (emails dev) : http://localhost:8025

---

## 4. Déploiement production (Traefik + HTTPS)

```bash
# S'assurer que DOMAIN et ACME_EMAIL sont définis dans .env

docker compose \
  -f docker-compose.yml \
  -f docker-compose.prod.yml \
  up -d --build

docker compose exec api php bin/console doctrine:migrations:migrate --no-interaction
```

Traefik obtient automatiquement un certificat Let's Encrypt pour votre domaine.

---

## 5. Workers et Scheduler

Le service `worker` démarre automatiquement avec `docker compose up`. Il consomme :
- `async` — messages asynchrones (emails, etc.)
- `scheduler_default` — tâches planifiées (vérification oubli départ à 23h30)

Pour vérifier :
```bash
docker compose logs -f worker
```

---

## 6. Activation de Sentry (monitoring erreurs)

```bash
# Dans le conteneur API
docker compose exec api composer require sentry/sentry-symfony
```

Puis dans `.env` :
```
SENTRY_DSN=https://your_key@sentry.io/your_project_id
```

Et décommenter le handler `sentry` dans `api/config/packages/monolog.yaml`.

---

## 7. Sauvegardes base de données

Le service `db-backup` effectue automatiquement une sauvegarde quotidienne à 2h du matin.
Les fichiers sont dans le volume Docker `db_backups`.

Pour restaurer :
```bash
# Lister les sauvegardes
docker compose exec db-backup ls /backup/

# Restaurer
docker compose exec db-backup sh -c "zcat /backup/<fichier>.sql.gz | mysql -h db -u $DB_USER -p$DB_PASSWORD $DB_NAME"
```

---

## 8. Checklist avant mise en production

- [ ] Toutes les variables `.env` sont renseignées
- [ ] `APP_ENV=prod` et `APP_SECRET` changé
- [ ] Clés JWT générées (`./scripts/setup-jwt.sh`)
- [ ] `GOOGLE_MAPS_API_KEY` valide
- [ ] `MAILER_DSN` SMTP de production configuré
- [ ] `CORS_ALLOW_ORIGIN` limité au domaine de production
- [ ] `DOMAIN` et `ACME_EMAIL` configurés
- [ ] Migrations appliquées
- [ ] Worker en cours d'exécution (`docker compose logs worker`)
- [ ] Test email de reset password fonctionnel
- [ ] Backup testé

---

## Commandes utiles

```bash
# Logs en temps réel
docker compose logs -f api worker

# État du scheduler
docker compose exec api php bin/console debug:messenger

# Vider le cache prod
docker compose exec api php bin/console cache:clear --env=prod

# Déclencher manuellement la vérification oubli départ
docker compose exec api php bin/console messenger:consume scheduler_default --limit=1
```

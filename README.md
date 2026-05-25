# Horosphere — Gestion des présences par géofencing GPS

Application web full-stack de gestion des pointages et présences avec géofencing GPS.

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | Symfony 7.1 (PHP 8.3) |
| Frontend | React 18 + TypeScript + Vite |
| Base de données | MariaDB 10.11 |
| Reverse proxy | Nginx |
| Containerisation | Docker + Docker Compose |
| Authentification | JWT (LexikJWTAuthBundle) |
| Géofencing | Haversine (backend) + Google Maps (frontend) |

## Prérequis

- Docker Desktop >= 4.0
- Docker Compose >= 2.0
- (optionnel) Node 20+ pour le développement frontend local

## Démarrage rapide

```bash
# 1. Cloner et configurer
git clone <repo>
cd horosphere
cp .env.example .env
# Éditer .env et renseigner GOOGLE_MAPS_API_KEY

# 2. Générer les clés JWT
mkdir -p api/config/jwt
openssl genpkey -out api/config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:horosphere_jwt_passphrase
openssl pkey -in api/config/jwt/private.pem -out api/config/jwt/public.pem -pubout -passin pass:horosphere_jwt_passphrase

# 3. Lancer l'application
docker compose up --build -d

# 4. Initialiser la base de données
docker compose exec api php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec api php bin/console doctrine:fixtures:load --no-interaction

# 5. Accéder à l'application
# Application : http://localhost
# API : http://localhost/api
# Mailpit : http://localhost:8025
```

## Comptes de test (après fixtures)

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@horosphere.fr | admin@horosphere.fr  | ADMIN |
| rh@horosphere.fr | Rh1234! | RH |

| agent1@horosphere.fr | Agent1234! | AGENT |
| agent2@horosphere.fr | Agent1234! | AGENT |
| agent3@horosphere.fr | Agent1234! | AGENT |

## Structure du projet

```
horosphere/
├── docker-compose.yml
├── .env.example
├── nginx/
│   └── nginx.conf
├── api/                    # Backend Symfony 7
│   ├── Dockerfile
│   ├── src/
│   │   ├── Controller/
│   │   ├── Entity/
│   │   ├── Repository/
│   │   ├── Service/
│   │   └── DataFixtures/
│   ├── config/
│   ├── migrations/
│   └── tests/
└── frontend/               # Frontend React 18
    ├── Dockerfile
    └── src/
        ├── pages/
        ├── components/
        ├── services/
        ├── store/
        └── hooks/
```

## Commandes utiles

```bash
# Logs
docker compose logs -f api
docker compose logs -f frontend

# Console Symfony
docker compose exec api php bin/console <commande>

# Tests PHP
docker compose exec api php bin/phpunit

# Shell container
docker compose exec api sh
docker compose exec frontend sh

# Rebuild après changement Dockerfile
docker compose up --build api
```

## Architecture API

| Méthode | Endpoint | Description | Rôle |
|---------|----------|-------------|------|
| POST | /api/auth/login | Connexion | Public |
| GET | /api/auth/me | Utilisateur connecté | Tous |
| POST | /api/pointages/arriver | Pointer l'arrivée (GPS) | AGENT |
| POST | /api/pointages/partir | Pointer le départ (GPS) | AGENT |
| GET | /api/pointages/mes-pointages | Historique personnel | AGENT |
| GET | /api/pointages | Tous les pointages | RH/ADMIN |
| POST | /api/demandes | Soumettre une demande | AGENT |
| PUT | /api/demandes/{id}/traiter | Valider/Rejeter | RH/ADMIN |
| GET | /api/alertes | Mes alertes | Tous |
| POST | /api/exports/csv | Export CSV | RH/ADMIN |
| POST | /api/exports/pdf | Export PDF | RH/ADMIN |
| GET | /api/sites | Liste des sites | Tous |
| POST | /api/sites | Créer un site | ADMIN |
| GET | /api/users | Liste utilisateurs | RH/ADMIN |
| POST | /api/users | Créer utilisateur | ADMIN |

## Fonctionnalités

- **Géofencing GPS** : vérification automatique que l'employé est dans la zone du site (rayon configurable, 200m par défaut)
- **Détection d'anomalies** : alerte automatique si un pointage est toujours ouvert après 10h
- **Exports** : génération de rapports CSV et PDF par période et par employé
- **Gestion des demandes** : congés, corrections, absences — workflow de validation RH
- **Notifications** : alertes email via Mailpit (dev) ou SMTP configuré
- **3 espaces** : Employé, RH, Admin avec permissions distinctes

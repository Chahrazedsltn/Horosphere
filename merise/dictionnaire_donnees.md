# Dictionnaire des données — Horosphere

## Table : utilisateur

| Attribut | Type | Longueur | Obligatoire | Contraintes / Valeurs | Description métier |
|---|---|---|---|---|---|
| id | INT UNSIGNED | — | Oui | PK, AUTO_INCREMENT | Identifiant unique de l'utilisateur |
| email | VARCHAR | 180 | Oui | UNIQUE, format email valide | Adresse email professionnelle, sert d'identifiant de connexion |
| mot_de_passe | VARCHAR | 255 | Oui | Hashé (bcrypt/argon2) | Mot de passe sécurisé de l'utilisateur |
| prenom | VARCHAR | 100 | Oui | Min 2 caractères | Prénom de l'utilisateur |
| nom | VARCHAR | 100 | Oui | Min 2 caractères | Nom de famille de l'utilisateur |
| role | ENUM | 10 | Oui | 'AGENT', 'RH', 'ADMIN' — défaut 'AGENT' | Rôle déterminant les droits d'accès dans l'application |
| departement | VARCHAR | 100 | Non | — | Service ou département de rattachement |
| date_creation | DATETIME | — | Oui | DEFAULT CURRENT_TIMESTAMP | Date et heure d'inscription dans le système |
| consentement_rgpd | TINYINT(1) | 1 | Oui | 0 ou 1 — défaut 0 | Indique si l'utilisateur a accepté la politique RGPD |
| solde_conges | INT | — | Oui | Défaut 25 | Nombre de jours de congés restants pour l'année en cours |

## Table : site

| Attribut | Type | Longueur | Obligatoire | Contraintes / Valeurs | Description métier |
|---|---|---|---|---|---|
| id | INT UNSIGNED | — | Oui | PK, AUTO_INCREMENT | Identifiant unique du site |
| nom | VARCHAR | 100 | Oui | Min 2 caractères | Nom du site de travail (ex : « Siège Paris ») |
| adresse | VARCHAR | 255 | Oui | — | Adresse postale complète du site |
| latitude | DECIMAL(10,8) | — | Oui | Plage : -90 à +90 | Latitude GPS du centre du site, précision ~1 mm |
| longitude | DECIMAL(11,8) | — | Oui | Plage : -180 à +180 | Longitude GPS du centre du site, précision ~1 mm |
| rayon_metres | INT UNSIGNED | — | Oui | Défaut 200, > 0 | Rayon de la zone de géofencing en mètres |
| geofencing_actif | TINYINT(1) | 1 | Oui | 0 ou 1 — défaut 1 | Active ou désactive le contrôle géographique pour ce site |

## Table : pointage

| Attribut | Type | Longueur | Obligatoire | Contraintes / Valeurs | Description métier |
|---|---|---|---|---|---|
| id | INT UNSIGNED | — | Oui | PK, AUTO_INCREMENT | Identifiant unique du pointage |
| utilisateur_id | INT UNSIGNED | — | Oui | FK → utilisateur.id, ON DELETE RESTRICT | Référence vers l'utilisateur qui effectue le pointage |
| site_id | INT UNSIGNED | — | Non | FK → site.id, ON DELETE SET NULL | Référence vers le site de pointage ; NULL si le site a été supprimé |
| date_jour | DATE | — | Oui | Auto-initialisé à la date du jour | Date calendaire du pointage |
| heure_arrivee | DATETIME | — | Oui | Auto-initialisé à l'heure courante | Horodatage de l'arrivée (clock-in) |
| heure_depart | DATETIME | — | Non | NULL tant que l'agent n'a pas quitté | Horodatage du départ (clock-out) |
| statut | ENUM | 20 | Oui | 'EN_COURS', 'EN_PAUSE', 'VALIDE', 'HORS_ZONE', 'ANOMALIE' — défaut 'EN_COURS' | État courant du pointage dans son cycle de vie |
| coordonnees_gps | VARCHAR | 255 | Non | Format « lat,lng » (ex : « 48.8566,2.3522 ») | Position GPS de l'agent au moment du pointage. Stocké en chaîne pour simplicité ; le calcul de distance est effectué côté application via la formule de Haversine |
| est_anomalie | TINYINT(1) | 1 | Oui | 0 ou 1 — défaut 0 | Indique si le pointage présente une anomalie détectée |
| heure_pause_debut | DATETIME | — | Non | NULL si pas de pause en cours | Horodatage du début de la pause courante |
| durees_pause_minutes | INT UNSIGNED | — | Oui | Défaut 0 | Cumul des durées de pauses en minutes pour la journée |

## Table : demande

| Attribut | Type | Longueur | Obligatoire | Contraintes / Valeurs | Description métier |
|---|---|---|---|---|---|
| id | INT UNSIGNED | — | Oui | PK, AUTO_INCREMENT | Identifiant unique de la demande |
| utilisateur_id | INT UNSIGNED | — | Oui | FK → utilisateur.id, ON DELETE CASCADE | Référence vers l'utilisateur auteur de la demande |
| type_demande | ENUM | 20 | Oui | 'CONGE', 'CORRECTION', 'ABSENCE', 'AUTRE' | Nature de la demande soumise par l'agent |
| statut | ENUM | 20 | Oui | 'EN_ATTENTE', 'APPROUVEE', 'REJETEE' — défaut 'EN_ATTENTE' | État de traitement de la demande par le service RH |
| date_debut | DATE | — | Oui | — | Date de début de la période concernée |
| date_fin | DATE | — | Oui | ≥ date_debut | Date de fin de la période concernée |
| motif | TEXT | — | Non | — | Justification libre rédigée par l'agent |
| justificatif | VARCHAR | 255 | Non | Chemin vers le fichier uploadé | Pièce jointe (certificat médical, etc.) accompagnant la demande |
| date_creation | DATETIME | — | Oui | DEFAULT CURRENT_TIMESTAMP | Date et heure de soumission de la demande |

## Table : document

| Attribut | Type | Longueur | Obligatoire | Contraintes / Valeurs | Description métier |
|---|---|---|---|---|---|
| id | INT UNSIGNED | — | Oui | PK, AUTO_INCREMENT | Identifiant unique du document |
| utilisateur_id | INT UNSIGNED | — | Oui | FK → utilisateur.id, ON DELETE CASCADE | Référence vers l'utilisateur propriétaire du document |
| type_document | ENUM | 10 | Oui | 'CSV', 'PDF' | Format du document généré |
| chemin_fichier | VARCHAR | 255 | Oui | Chemin serveur relatif | Emplacement du fichier sur le serveur de stockage |
| date_creation | DATETIME | — | Oui | DEFAULT CURRENT_TIMESTAMP | Date et heure de génération du document |

## Table : alerte

| Attribut | Type | Longueur | Obligatoire | Contraintes / Valeurs | Description métier |
|---|---|---|---|---|---|
| id | INT UNSIGNED | — | Oui | PK, AUTO_INCREMENT | Identifiant unique de l'alerte |
| utilisateur_id | INT UNSIGNED | — | Oui | FK → utilisateur.id, ON DELETE CASCADE | Référence vers l'utilisateur destinataire de l'alerte |
| pointage_id | INT UNSIGNED | — | Non | FK → pointage.id, ON DELETE SET NULL | Référence vers le pointage ayant déclenché l'alerte ; NULL pour les alertes système non liées à un pointage spécifique, ou si le pointage source a été supprimé |
| type_alerte | ENUM | 30 | Oui | 'OUBLI_DEPART', 'HORS_ZONE', 'ECART_HORAIRE' | Catégorie de l'alerte déterminant le traitement |
| message | TEXT | — | Oui | — | Description lisible de l'alerte destinée à l'utilisateur ou au RH |
| date_creation | DATETIME | — | Oui | DEFAULT CURRENT_TIMESTAMP | Date et heure de création de l'alerte |
| est_lue | TINYINT(1) | 1 | Oui | 0 ou 1 — défaut 0 | Indique si l'alerte a été consultée par son destinataire |

## Table : audit_log

| Attribut | Type | Longueur | Obligatoire | Contraintes / Valeurs | Description métier |
|---|---|---|---|---|---|
| id | INT UNSIGNED | — | Oui | PK, AUTO_INCREMENT | Identifiant unique de l'entrée de journal |
| action | VARCHAR | 60 | Oui | INDEX. Ex : 'demande.approuvee', 'user.supprime', 'rgpd.effacement' | Code de l'action effectuée dans le système |
| utilisateur_id | INT UNSIGNED | — | Non | INDEX. NULL pour les actions système | Identifiant de l'utilisateur ayant déclenché l'action |
| utilisateur_email | VARCHAR | 100 | Non | — | Email de l'auteur de l'action (conservé même si le compte est supprimé) |
| cible_type | VARCHAR | 60 | Non | Ex : 'Demande', 'Pointage', 'User' | Type de l'entité concernée par l'action |
| cible_id | INT UNSIGNED | — | Non | — | Identifiant de l'entité concernée par l'action |
| details | JSON | — | Non | — | Données contextuelles complémentaires (ancien/nouveau statut, etc.) |
| ip_address | VARCHAR | 45 | Non | Format IPv4 ou IPv6 | Adresse IP du client ayant initié l'action |
| created_at | DATETIME | — | Oui | INDEX, DEFAULT CURRENT_TIMESTAMP | Horodatage de l'événement |

## Table : password_reset_token

| Attribut | Type | Longueur | Obligatoire | Contraintes / Valeurs | Description métier |
|---|---|---|---|---|---|
| id | INT UNSIGNED | — | Oui | PK, AUTO_INCREMENT | Identifiant unique du jeton |
| email | VARCHAR | 180 | Oui | — | Adresse email pour laquelle la réinitialisation est demandée |
| token | VARCHAR | 64 | Oui | UNIQUE, INDEX. 64 octets hexadécimaux aléatoires | Jeton cryptographique à usage unique |
| expires_at | DATETIME | — | Oui | TTL = 3600 secondes (1 heure) | Date et heure d'expiration du jeton |
| used_at | DATETIME | — | Non | NULL tant que non utilisé | Date et heure d'utilisation du jeton |
| created_at | DATETIME | — | Oui | DEFAULT CURRENT_TIMESTAMP | Date et heure de création du jeton |

---

### Justifications de choix techniques

**coordonnees_gps (VARCHAR 255) dans pointage vs latitude/longitude séparés dans site :**
Le site stocke latitude et longitude séparément car ces valeurs sont la référence fixe utilisée pour le calcul de géofencing côté serveur (formule de Haversine). Le pointage stocke les coordonnées en chaîne unique « lat,lng » car cette donnée est capturée depuis l'API de géolocalisation du navigateur et transmise telle quelle. Le parsing en deux valeurs numériques est effectué côté application au moment du calcul de distance. Ce choix simplifie la sérialisation JSON tout en restant fonctionnel.

**ON DELETE CASCADE sur demande et document :**
La suppression d'un utilisateur entraîne la suppression de ses demandes et documents. Ce comportement est voulu métier : dans le cadre du droit à l'effacement RGPD (article 17), lorsqu'un utilisateur demande la suppression de son compte, toutes ses données personnelles (demandes, documents) doivent être supprimées. Les pointages sont protégés par ON DELETE RESTRICT car ils constituent des données d'activité nécessaires à la comptabilité du temps de travail.

**ON DELETE SET NULL sur alerte.pointage_id :**
Si un pointage est supprimé (correction administrative), les alertes associées sont conservées pour traçabilité mais perdent leur lien avec le pointage d'origine.

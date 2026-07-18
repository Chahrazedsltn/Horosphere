# Jalon 2 — Méthodologie & Conception UI/UX

## HOROSPHERE — Application de Pointage Numérique

**Auteur :** Chahrazed Soltani
**Formation :** Bachelor CDA — IPSSI
**Session :** 2025/2026
**Version :** Juillet 2026

---

## Sommaire

1. Méthodologie de gestion de projet
2. Charte graphique
3. Parcours utilisateurs (User Flows)
4. Maquettes et correspondance avec le CDCF
5. Principes UX appliqués

---

## 1. Méthodologie de gestion de projet

### 1.1 Méthode choisie : Agile — Kanban adapté

Le projet Horosphere est développé en **solo** dans le cadre d'une formation CDA. La méthode Kanban a été retenue pour sa flexibilité et son adaptation au travail individuel, contrairement à Scrum qui suppose une équipe pluridisciplinaire et des cérémonies collectives.

**Pourquoi Kanban plutôt que Scrum :**
- Pas de rôles distincts (pas de Scrum Master ni de Product Owner séparés) — je cumule tous les rôles.
- Le flux de travail est continu : les jalons mensuels du référentiel CDA structurent naturellement les itérations.
- Le tableau Kanban (To Do → In Progress → Done) est suffisant pour suivre l'avancement sans cérémonie superflue.

### 1.2 Découpage temporel — Jalons

Le projet est structuré en **6 jalons mensuels**, alignés sur le calendrier du référentiel CDA :

| Jalon | Période | Phase | Livrable |
|---|---|---|---|
| J1 | Janvier 2026 | Cadrage & Rédaction | Cahier des charges fonctionnel |
| J2 | Février 2026 | Design UI/UX | Maquettes Figma + méthodologie |
| J3 | Mars 2026 | Modélisation BDD | Schéma Merise (MCD/MLD/MPD) |
| J4 | Avril 2026 | Architecture API | Diagrammes UML + conception technique |
| J5 | Mai 2026 | Développement & Tests | Version bêta + suite PHPUnit |
| J6 | Juin 2026 | Déploiement & Livraison | Docker + documentation finale |

### 1.3 Backlog — User Stories priorisées

Le backlog est organisé par priorité (MoSCoW) et par espace utilisateur :

#### Must Have (indispensable)

| ID | User Story | Fonc. CDCF |
|---|---|---|
| US01 | En tant qu'utilisateur, je veux me connecter avec mon email et mot de passe pour accéder à mon espace | F1 |
| US02 | En tant qu'agent, je veux pointer mon arrivée en un clic pour enregistrer ma présence | F2 |
| US03 | En tant qu'agent, je veux pointer mon départ pour clôturer ma journée | F2 |
| US04 | En tant qu'agent, je veux mettre mon pointage en pause et le reprendre | F2 |
| US05 | En tant que système, je veux vérifier la position GPS de l'agent par rapport au site pour détecter les pointages hors zone | F3 |
| US06 | En tant qu'agent, je veux consulter l'historique mensuel de mes pointages en vue calendrier ou liste | F4 |
| US07 | En tant que RH, je veux valider ou corriger les feuilles de temps pour préparer la paie | F5 |
| US08 | En tant que RH, je veux recevoir des alertes automatiques sur les anomalies (oubli départ, hors zone, écart horaire) | F6 |
| US09 | En tant que RH, je veux exporter les données de pointage en CSV ou PDF | F7 |
| US10 | En tant qu'admin, je veux créer, modifier et désactiver des comptes utilisateurs | F8 |

#### Should Have (important)

| ID | User Story | Fonc. CDCF |
|---|---|---|
| US11 | En tant qu'admin, je veux configurer les sites avec leurs coordonnées GPS et le rayon de géofencing | F9 |
| US12 | En tant qu'admin, je veux consulter les logs d'activité pour la supervision technique | F10 |
| US13 | En tant qu'agent, je veux soumettre une demande de congé ou de correction | F5 |
| US14 | En tant qu'agent, je veux consulter et télécharger mes documents (bulletins, attestations) | F4 |
| US15 | En tant qu'utilisateur, je veux réinitialiser mon mot de passe oublié | F1 |

#### Could Have (souhaitable)

| ID | User Story | Fonc. CDCF |
|---|---|---|
| US16 | En tant qu'agent, je veux personnaliser mes préférences de notification | — |
| US17 | En tant que RH, je veux voir un graphique du taux de présence mensuel | F7 |
| US18 | En tant qu'admin, je veux choisir un thème visuel pour l'interface | — |

### 1.4 Outils de gestion

- **Tableau Kanban :** GitHub Projects (colonnes To Do / In Progress / Review / Done)
- **Versioning :** Git + GitHub (branches feature/*)
- **CI/CD :** GitHub Actions (lint, tests PHPUnit)
- **Maquettage :** Figma

---

## 2. Charte graphique

### 2.1 Palette de couleurs

L'application utilise un système de design à **thèmes interchangeables** avec une base constante. Le thème par défaut est **Slate-Blue** (Ardoise + Bleu Électrique).

#### Couleurs de contenu (constantes)

| Rôle | Variable | Valeur | Usage |
|---|---|---|---|
| Fond principal | `--bg` | `#f8fafc` | Arrière-plan général |
| Surface carte | `--surface` | `#ffffff` | Cartes, modales, formulaires |
| Surface secondaire | `--surface2` | `#f1f5f9` | Lignes alternées, zones secondaires |
| Bordure | `--border` | `#e2e8f0` | Séparateurs, contours de cartes |
| Texte principal | `--text` | `#0f172a` | Titres, contenus importants |
| Texte secondaire | `--text2` | `#475569` | Labels, descriptions |
| Texte tertiaire | `--text3` | `#94a3b8` | Placeholders, métadonnées |

#### Couleurs d'accentuation (thème Slate-Blue — défaut)

| Rôle | Variable | Valeur | Usage |
|---|---|---|---|
| Accent principal | `--accent` | `#2563eb` | Boutons primaires, liens actifs, sidebar active |
| Accent clair | `--accent-light` | `#eff6ff` | Fond des badges, survol léger |
| Accent moyen | `--accent-mid` | `#3b82f6` | Hover des boutons |
| Sidebar | `--sidebar-bg` | `#0f172a` | Fond de la barre latérale (ardoise foncé) |

#### Couleurs de statut (constantes)

| Rôle | Couleur | Valeur | Usage |
|---|---|---|---|
| Succès | Vert | `#16a34a` | Validé, Présent, Approuvé |
| Fond succès | Vert clair | `#f0fdf4` | Badge vert (fond) |
| Erreur | Rouge | `#dc2626` | Anomalie, Rejeté, Absent |
| Fond erreur | Rouge clair | `#fef2f2` | Badge rouge (fond) |
| Avertissement | Ambre | `#ca8a04` | En attente, En pause, Alerte |
| Fond avertissement | Ambre clair | `#fefce8` | Badge ambre (fond) |

**Justification des choix de couleurs :**
- Le fond clair (`#f8fafc`) avec sidebar sombre crée un contraste fort qui guide l'œil vers le contenu principal. Ce pattern est largement adopté dans les applications SaaS professionnelles (Slack, Linear, Notion).
- Les couleurs de statut suivent les conventions sémantiques universelles (vert = OK, rouge = erreur, ambre = attention), ce qui réduit la charge cognitive pour les utilisateurs non techniques comme Marie (agent de terrain).
- Le bleu électrique comme accent est perçu comme professionnel et fiable, adapté à un outil RH.

### 2.2 Typographies

| Usage | Police | Justification |
|---|---|---|
| Interface générale | **Outfit** (sans-serif) | Police géométrique moderne, excellente lisibilité sur écran, bonne couverture de graisses (300–700). Son dessin ouvert et ses formes rondes transmettent un sentiment d'accessibilité. |
| Données numériques | **JetBrains Mono** (monospace) | Alignement parfait des chiffres dans les tableaux de pointage (heures, durées). Les chiffres sont à largeur fixe, ce qui facilite la lecture comparative des colonnes. |

### 2.3 Composants récurrents

| Composant | Style | Usage |
|---|---|---|
| Cartes | Fond blanc, border-radius 12px, ombre légère | Conteneurs principaux sur chaque page (pointage du jour, statistiques, formulaires) |
| Boutons primaires | Fond accent, texte blanc, radius 12px, hover brightness +12% | Actions principales (Se connecter, Pointer, Valider) |
| Badges de statut | Texte coloré sur fond clair correspondant, radius pill | Affichage des états (Validé, En attente, Absent, Hors zone) |
| Sidebar | Fond ardoise, items avec icon + label, item actif en accent | Navigation principale, présente sur toutes les pages authentifiées |
| Tableaux | Headers en gris clair, lignes alternées, hover subtil | Historique, liste employés, détail pointages |
| Indicateurs KPI | Nombre en grand (font-mono), label en text2, carte dédiée | Dashboard agent (jours présents, heures travaillées) et RH (employés actifs, taux de présence) |

### 2.4 Iconographie

Les icônes utilisent la bibliothèque **Lucide React** (fork de Feather Icons), choisie pour :
- Son style linéaire minimaliste cohérent avec le design épuré de l'application.
- Sa légèreté (tree-shakable, chaque icône est un composant individuel).
- Sa couverture complète des cas d'usage métier (horloge, carte, utilisateur, document, alerte).

### 2.5 Responsive & Layout

| Dimension | Valeur | Justification |
|---|---|---|
| Largeur sidebar | 225px | Suffisant pour le label + icône, sans empiéter sur le contenu |
| Hauteur topbar | 60px | Horloge temps réel et profil utilisateur toujours visibles |
| Border-radius par défaut | 12px | Arrondi moderne sans être excessif, cohérent sur tous les composants |
| Breakpoint mobile | < 768px | Sidebar se rétracte, layout passe en colonne unique |

L'application est conçue **mobile-first** conformément au CDCF : l'agent de terrain (Marie) accède depuis son smartphone. Les boutons de pointage sont dimensionnés pour une utilisation tactile (hauteur min 48px, espacement min 8px).

---

## 3. Parcours utilisateurs (User Flows)

### 3.1 Flux principal — Pointage d'une journée complète (Agent)

Ce parcours couvre les fonctionnalités F1, F2, F3 du CDCF.

```
┌─────────────┐
│  Connexion   │ ← Écran 1 : email + mot de passe (F1)
│  (email/mdp) │
└──────┬───────┘
       │ Authentification OK
       ▼
┌─────────────────────────────────┐
│  Tableau de bord — Pointage     │ ← Écran 2 : dashboard agent
│  Statut : Non pointé            │
│  Carte géofencing visible        │
└──────┬──────────────────────────┘
       │ Clic sur « Arrivée »
       ▼
┌─────────────────────────────────┐
│  Géolocalisation en cours...     │ ← API Geolocation du navigateur (F3)
│  Vérification position vs site   │
└──────┬──────────────┬───────────┘
       │ Dans la zone   │ Hors zone
       ▼               ▼
┌──────────────┐ ┌─────────────────────┐
│ Statut :      │ │ Statut : HORS_ZONE   │
│ EN_COURS      │ │ Alerte créée → RH    │ ← (F6)
│ Zone : Oui    │ │ Pointage NON bloqué  │
└──────┬────────┘ └──────┬──────────────┘
       │                  │
       ▼                  ▼
┌─────────────────────────────────┐
│  Journée en cours                │
│  Durée travaillée : compteur     │
│  Boutons : Pause / Départ        │
└──────┬──────────────┬───────────┘
       │ Pause          │ Départ
       ▼               ▼
┌──────────────┐ ┌─────────────────────┐
│ EN_PAUSE      │ │ Statut : VALIDE      │
│ Compteur      │ │ Récap journée affiché│
│ pausé         │ │ Durée totale calculée│
│ Bouton :      │ └──────────────────────┘
│ Reprendre     │
└───────────────┘
```

**Étapes détaillées :**
1. L'agent ouvre l'application et saisit ses identifiants (email/mot de passe).
2. Après connexion, il arrive sur le tableau de bord affichant le statut « Non pointé ».
3. Il clique sur le bouton vert **Arrivée** (action principale, la plus visible).
4. Le navigateur demande l'autorisation de géolocalisation. Les coordonnées GPS sont capturées.
5. Le système compare la position avec le centre du site assigné (formule de Haversine).
6. Si la distance est inférieure au rayon (200m par défaut) : statut EN_COURS, zone validée.
7. Si la distance dépasse le rayon : statut HORS_ZONE, une alerte est envoyée au RH, mais le pointage n'est **pas bloqué** (exigence juridique mentionnée dans F3 du CDCF).
8. En cours de journée, l'agent peut cliquer sur **Pause** (statut EN_PAUSE, compteur gelé) puis **Reprendre**.
9. En fin de journée, il clique sur **Départ** : statut VALIDE, durée totale affichée.

### 3.2 Flux RH — Traitement des anomalies

Ce parcours couvre les fonctionnalités F5, F6, F7 du CDCF.

```
┌─────────────────────────────────┐
│  Dashboard RH                    │ ← Écran 5 : vue globale
│  KPIs : 47 employés, 42 présents│
│  Badge : « 3 anomalies »        │
└──────┬──────────────────────────┘
       │ Clic sur « Validation RH »
       ▼
┌─────────────────────────────────┐
│  Validation RH — Anomalies      │ ← Écran 6 : liste filtrée
│  Filtres : Tous / Retards /     │
│  Oublis / Hors zone             │
└──────┬──────────────────────────┘
       │ Pour chaque anomalie :
       ▼
┌─────────────────────────────────┐
│  Fiche anomalie                  │
│  Détail : heure, écart, lieu     │
│  Actions : Approuver / Corriger  │
│            Refuser               │
└──────┬──────────────┬───────────┘
       │ Approuver      │ Corriger
       ▼               ▼
┌──────────────┐ ┌─────────────────────┐
│ Pointage     │ │ Saisie correction    │
│ validé       │ │ (heure manuelle)     │
│ Audit log ✓  │ │ Audit log ✓          │
└──────────────┘ └──────────────────────┘
```

### 3.3 Flux Agent — Soumission d'une demande de congé

Ce parcours couvre la fonctionnalité F5 (demandes) du CDCF.

```
┌──────────────┐     ┌───────────────────────────┐
│ Navigation :  │────▶│ Mes Demandes               │ ← Écran 8
│ Mes Demandes  │     │ Filtres : Toutes/En attente │
└───────────────┘     │ / Approuvées / Refusées     │
                      └──────┬────────────────────┘
                             │ Clic « + Nouvelle demande »
                             ▼
                      ┌───────────────────────────┐
                      │ Formulaire :                │
                      │ Type (Congé/Correction/...) │
                      │ Date début — Date fin       │
                      │ Motif (optionnel)           │
                      │ Justificatif (optionnel)    │
                      └──────┬────────────────────┘
                             │ Clic « Envoyer »
                             ▼
                      ┌───────────────────────────┐
                      │ Demande créée               │
                      │ Statut : EN_ATTENTE         │
                      │ Visible dans l'historique   │
                      │ Notification → RH           │
                      └─────────────────────────────┘
```

### 3.4 Flux Admin — Configuration du géofencing

Ce parcours couvre la fonctionnalité F9 du CDCF.

```
┌──────────────┐     ┌───────────────────────────┐
│ Navigation :  │────▶│ Zones Géo                  │ ← Écran 11
│ Zones Géo     │     │ Carte + liste des sites    │
└───────────────┘     └──────┬────────────────────┘
                             │ Clic « + Nouvelle zone »
                             ▼
                      ┌───────────────────────────┐
                      │ Formulaire site :           │
                      │ Adresse → geocoding auto    │
                      │ Rayon en mètres             │
                      │ Statut (Actif/Test)         │
                      └──────┬────────────────────┘
                             │ Sauvegarder
                             ▼
                      ┌───────────────────────────┐
                      │ Zone créée sur la carte     │
                      │ Alertes géofencing actives   │
                      │ Employés assignables         │
                      └─────────────────────────────┘
```

---

## 4. Maquettes et correspondance avec le CDCF

Chaque écran de l'application répond à un ou plusieurs besoins fonctionnels identifiés dans le cahier des charges. Les captures ci-dessous sont issues des maquettes Figma et sont intégrées directement dans ce document.

### Écran 1 — Connexion
**→ F1 : Authentification**

*(Voir capture : page de connexion)*

- Formulaire email + mot de passe, centré, minimaliste.
- Lien « Mot de passe oublié ? » pour la récupération (F1).
- Option « Rester connecté » pour la persistance de session.
- Logo Horosphere et sous-titre « Gestion des présences » pour identifier l'application.

**Lien CDCF :** Répond à F1 — « Connexion sécurisée par email/mot de passe avec récupération de mot de passe ».

---

### Écran 2 — Tableau de bord Agent (Pointage)
**→ F2 : Pointage / F3 : Géolocalisation / F6 : Alertes**

*(Voir capture : dashboard agent avec pointage du jour et carte géofencing)*

**Zone gauche — Pointage du jour :**
- Trois boutons d'action : **Arrivée** (vert), **Pause** (ambre), **Départ** (rouge).
- Couleurs sémantiques pour guider l'action sans formation.
- Indicateurs temps réel : durée travaillée, dernière action, statut.
- Barre de progression de la journée (09:00 → 18:00).

**Zone droite — Géofencing & Localisation :**
- Carte Google Maps montrant la zone autorisée.
- Indicateurs : « Zone valide : Oui/Non » et distance en mètres.
- Badge « API Geo » pour la transparence technique.

**Zone basse — KPIs mensuels :**
- 4 cartes : Jours présents, Heures travaillées, Heures supplémentaires, Congés restants.
- Tableau des derniers pointages avec date, heure arrivée, heure départ, durée, statut.

**Lien CDCF :**
- F2 — Boutons Arrivée / Pause / Départ en 2 clics max.
- F3 — Carte de géofencing avec vérification de position.
- F6 — Statut « Non pointé » et anomalies visibles directement.

---

### Écran 3 — Historique des Présences (Agent)
**→ F4 : Historique**

*(Voir capture : vue calendrier + détail des pointages)*

- **Vue calendrier mensuel** avec code couleur : bleu = présent, rose = absence, bleu foncé = aujourd'hui.
- Navigation mois par mois (Jan 2026 ← Fév 2026 → Mar 2026).
- **Tableau de détail** à droite : jour, date, entrée, sortie, total.
- Bouton **Export CSV** en haut à droite pour extraction personnelle.
- Total mensuel affiché (ex : « 176h 20m — 22 jours »).

**Lien CDCF :** Répond à F4 — « Consultation mensuelle des heures et sites en vue liste ou calendrier ».

---

### Écran 4 — Mon Profil (Agent)
**→ F1 : Authentification (modification mot de passe)**

*(Voir capture : profil utilisateur avec sécurité et préférences)*

- **Informations personnelles** : prénom, nom, email, téléphone, département, responsable.
- **Sécurité du compte** : changement de mot de passe (actuel + nouveau + confirmation).
- **Préférences & Notifications** : toggles pour notifications email, rappel de pointage SMS, résumé hebdomadaire.

**Lien CDCF :** Complète F1 — gestion du compte et sécurité.

---

### Écran 5 — Vue RH — Tableau de bord
**→ F5 : Validation / F6 : Alertes / F7 : Export**

*(Voir capture : dashboard RH avec KPIs et liste employés)*

- **5 KPIs** en haut : Employés actifs, Présents aujourd'hui, Absents non justifiés, Pointages en attente, Congés en cours.
- **Graphique** des présences de la semaine (bar chart).
- **Taux de présence** : présents aujourd'hui, taux mensuel, ponctualité.
- **Alerte** : « 3 employés absents sans justificatif ce jour ».
- **Liste des employés** avec statut du jour, heure d'arrivée, lieu, et actions (Voir/Alerter).
- Badge « 3 anomalies à traiter » pour attirer l'attention du RH.

**Lien CDCF :**
- F5 — Vue globale pour la validation.
- F6 — Alertes anomalies directement dans le dashboard.
- F7 — Bouton Exporter dans la liste employés.

---

### Écran 6 — Validation RH — Anomalies & Corrections
**→ F5 : Validation / F6 : Alertes**

*(Voir capture : page validation avec 3 types d'anomalies)*

- **Filtres par onglets** : Tous (5), Retards (2), Oublis de pointage (2), Hors zone (1).
- **Fiche par anomalie** avec détail contextuel :
  - *Retard* : heure prévue vs réelle, durée du retard.
  - *Oubli départ* : heure d'arrivée, départ manquant, durée estimée « ? ».
  - *Hors zone* : distance mesurée vs rayon autorisé, motif déclaré par l'agent.
- **Actions** : Approuver / Corriger la saisie / Approuver l'exception / Refuser.
- Chaque action est journalisée dans l'audit log.

**Lien CDCF :**
- F5 — « Approbation des feuilles de temps et traitement des anomalies (pointages hors zone, oublis de départ) ».
- F6 — Les 3 types d'alertes du CDCF sont représentés : retard (écart horaire), oubli de sortie, hors zone.

---

### Écran 7 — Paramètres Système (Admin)
**→ F9 : Configuration des sites**

*(Voir capture : paramètres géofencing + règles horaires + notifications)*

- **Configuration géofencing** : adresse du site, rayon en mètres, carte de configuration, bouton « + Ajouter un site ».
- **Règles horaires** : heure début/fin journée, seuil alerte retard, durée max pause déjeuner.
- **Notifications & Automatisations** : toggles pour alerte email RH sur anomalie, validation RH obligatoire, rapport automatique hebdo, blocage hors zone géofencing.

**Lien CDCF :** Répond à F9 — « Création de fiches clients avec coordonnées GPS et paramétrage du rayon de géofencing ».

---

### Écran 8 — Mes Demandes (Agent)
**→ F5 : Validation (soumission côté agent)**

*(Voir capture : formulaire nouvelle demande + historique)*

- **Formulaire inline** : type de demande (Congés payés, Correction, Absence, Autre), dates début/fin, motif optionnel.
- **Historique des demandes** avec statut : En attente (ambre), Approuvée (vert), Refusée (rouge).
- Détail contextuel : période, nombre de jours, date de soumission, responsable ayant traité.
- Bouton « Annuler » disponible tant que le statut est En attente.

**Lien CDCF :** Complète F5 côté agent — soumission des demandes de congé et de correction.

---

### Écran 9 — Mes Documents (Agent)
**→ F7 : Export (consultation côté agent)**

*(Voir capture : bulletins de paie + attestations + contrats)*

- **Bulletins de paie** : cards avec aperçu, date, taille, actions Voir/Télécharger.
- **Attestations & Contrats** : tableau avec type, date d'émission, taille, statut, actions.
- Filtres par catégorie : Tous, Bulletins de paie, Attestations, Contrats, Autres.
- Barre de recherche pour retrouver un document spécifique.

**Lien CDCF :** Complète F7 — les documents générés (CSV/PDF) sont accessibles par l'agent.

---

### Écran 10 — Gestion des Employés (RH/Admin)
**→ F8 : Gestion des utilisateurs**

*(Voir capture : liste paginée des employés avec filtres et actions)*

- **KPIs** : Total employés, Présents aujourd'hui, Nouveaux ce mois, Comptes désactivés.
- **Filtres** : recherche par nom, département, statut.
- **Tableau** : employé, département, poste, statut (Présent/En pause/Absent/En congé), pointage du jour, lieu, responsable.
- **Actions** : Éditer (profil), Voir (détail pointages), Alerte (pour les absents).
- Boutons « + Ajouter un employé » et « Exporter ».
- Pagination (1–6 sur 47 employés).

**Lien CDCF :** Répond à F8 — « Création, modification et désactivation des comptes ».

---

### Écran 11 — Zones Géofencing (Admin)
**→ F9 : Configuration des sites / F3 : Géolocalisation**

*(Voir capture : carte des zones + fiches sites + alertes temps réel)*

- **Carte interactive** avec les zones de géofencing visualisées (cercles sur la carte).
- **Fiches site** à droite : adresse, rayon, employés assignés, présents aujourd'hui, actions Modifier/Désactiver/Activer.
- **Alertes géofencing temps réel** : tableau avec employé, zone, distance hors zone, heure, statut, action « Voir ».
- Bouton « + Nouvelle zone » pour ajouter un site.

**Lien CDCF :**
- F9 — Configuration des sites avec coordonnées GPS et rayon.
- F3 — Visualisation des alertes de géofencing en temps réel.

---

### Écran 12 — Rapports & Exports (RH)
**→ F7 : Export**

*(Voir capture : exports mensuels et rapports personnalisés)*

- **Export mensuel** : sélection du mois, format (CSV/XLSX/PDF), filtre par employé.
- **Rapport personnalisé** : dates début/fin, sélection employé(s), type de rapport (Présences & absences).
- **Graphique annuel** du taux de présence (bar chart, 12 mois).

**Lien CDCF :** Répond à F7 — « Génération de fichiers CSV/PDF pour la paie et la preuve de service client ».

---

## 5. Principes UX appliqués

### 5.1 Loi de Fitts — Taille et positionnement des cibles

Les boutons de pointage (Arrivée, Pause, Départ) sont les plus grands éléments interactifs de l'écran principal. Leur taille (pleine largeur de la carte) et leur position centrale réduisent le temps d'acquisition conformément à la loi de Fitts. Cela répond directement au besoin de Marie (agent) : « pointer en deux clics, sans formation technique ».

### 5.2 Hiérarchie visuelle — Couleurs sémantiques

Les couleurs de statut (vert/rouge/ambre) sont utilisées de manière cohérente sur tous les écrans :
- **Vert** = action positive ou état normal (Validé, Présent, Approuvé, Arrivée).
- **Rouge** = action critique ou état anormal (Anomalie, Absent, Rejeté, Départ).
- **Ambre** = état intermédiaire ou en attente (En pause, En attente).

Cette cohérence réduit la charge cognitive et permet une lecture instantanée des tableaux de bord.

### 5.3 Progressive disclosure — Information à la demande

Le dashboard agent affiche uniquement les informations essentielles (pointage du jour, KPIs). Les détails (historique, documents, demandes) sont accessibles via la navigation latérale. Le RH voit les KPIs de synthèse en premier, avec un badge d'alerte « 3 anomalies » qui invite à explorer la page de validation.

### 5.4 Consistance — Navigation unifiée

La sidebar est identique en structure pour tous les profils, seuls les items changent :
- **Agent** : Accueil, Mon Historique, Mes Demandes, Mes Documents.
- **RH/Admin** : Vue Globale, Employés, Validation RH, Rapports + section Configuration.

L'utilisateur en bas de la sidebar (avatar + nom + rôle) est toujours visible, renforçant le sentiment de contexte.

---

## Matrice de traçabilité — Écrans × Fonctionnalités CDCF

| Fonctionnalité CDCF | Écrans correspondants |
|---|---|
| F1 — Authentification | Écran 1 (Connexion), Écran 4 (Mon Profil — changement mdp) |
| F2 — Pointage | Écran 2 (Dashboard Agent — boutons Arrivée/Pause/Départ) |
| F3 — Géolocalisation & Geofencing | Écran 2 (carte géofencing), Écran 11 (Zones Géo) |
| F4 — Historique | Écran 3 (Historique — calendrier + détail) |
| F5 — Validation | Écran 6 (Validation RH), Écran 8 (Mes Demandes — soumission agent) |
| F6 — Alertes | Écran 2 (statut anomalie), Écran 5 (badge anomalies), Écran 6 (liste anomalies) |
| F7 — Export | Écran 9 (Mes Documents), Écran 12 (Rapports & Exports) |
| F8 — Gestion utilisateurs | Écran 10 (Gestion des Employés) |
| F9 — Configuration des sites | Écran 7 (Paramètres), Écran 11 (Zones Géofencing) |
| F10 — Supervision technique | Écran 5 (Dashboard RH — logs), Écran 7 (Paramètres — notifications) |

**Couverture : 10/10 fonctionnalités du CDCF sont couvertes par au moins un écran.**

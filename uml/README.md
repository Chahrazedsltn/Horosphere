# Jalon 4 — Conception & Architecture UML — Horosphere

> **Échéance :** 30 avril 2026
> **Objectif :** Fournir l'ensemble des diagrammes UML et la documentation d'architecture technique avant le démarrage de l'implémentation (Jalon 5).

---

## Contenu du dossier

| Fichier | Type | Description |
|---|---|---|
| `use_case.plantuml` | Diagramme de cas d'utilisation | Tous les acteurs et fonctionnalités du système |
| `class_diagram.plantuml` | Diagramme de classes | Entités, Repositories, Services, Controllers (architecture Symfony) |
| `sequence_pointage.plantuml` | Diagramme de séquence | Flux complet d'un pointage arrivée / départ avec géofencing |
| `sequence_demande.plantuml` | Diagramme de séquence | Soumission et traitement d'une demande de congé |
| `sequence_alerte.plantuml` | Diagramme de séquence | Détection automatique d'un oubli de départ et consultation |
| `architecture.plantuml` | Diagramme de composants | Architecture N-tiers (client / serveur / données / infra) |

---

## Rendu des diagrammes

Pour générer les images PNG/SVG depuis les fichiers `.plantuml` :

```bash
# Via PlantUML JAR
java -jar plantuml.jar uml/*.plantuml

# Via extension VS Code : PlantUML (jebbs.plantuml)
# → Ctrl+Shift+P > PlantUML: Export Current Diagram
```

---

## Synthèse architecturale

### Pattern architectural : MVC N-Tiers

```
Tier 1 — Présentation   : React SPA / PWA  (navigateur)
Tier 2 — Application    : Symfony API REST  (serveur PHP-FPM)
Tier 3 — Données        : MariaDB           (base relationnelle)
```

### Couches internes Symfony (Tier 2)

```
Request HTTP
    ↓
[Security / JWT]          ← LexikJWTAuthBundle
    ↓
[Controller]              ← Route → vérifie rôle → délègue
    ↓
[Service métier]          ← Logique applicative (GeofencingService, PointageService…)
    ↓
[Repository / ORM]        ← Doctrine (requêtes paramétrées)
    ↓
[Base de données]         ← MariaDB
```

### Principes SOLID appliqués

| Principe | Application |
|---|---|
| **S** — Single Responsibility | Chaque Service gère un seul domaine métier |
| **O** — Open/Closed | Les Repositories étendent `AbstractRepository` sans modification |
| **L** — Liskov | Les Controllers héritent de `AbstractController` Symfony |
| **I** — Interface Segregation | Les interfaces Symfony (`UserInterface`, `PasswordAuthenticatedUserInterface`) |
| **D** — Dependency Inversion | Injection de dépendances Symfony (autowiring) |

### Sécurité (OWASP Top 10)

| Risque | Contre-mesure |
|---|---|
| Injection SQL | Doctrine ORM — requêtes paramétrées |
| XSS | React — échappement automatique du DOM |
| CSRF | Tokens CSRF Symfony sur les formulaires |
| Auth compromise | JWT + bcrypt/Argon2 pour les mots de passe |
| Accès non autorisé | `denyAccessUnlessGranted()` + rôles AGENT/RH/ADMIN |
| Données sensibles | RGPD : consentement obligatoire + HTTPS uniquement |

### API externe intégrée

- **Google Maps API** — utilisée par `GeofencingService` pour :
  - Vérifier qu'un employé est bien dans la zone géographique d'un site
  - Calculer la distance entre les coordonnées GPS et le périmètre du site (`rayon_metres`)

---

## Acteurs et rôles

| Rôle | Capacités principales |
|---|---|
| **AGENT** | Pointer arrivée/départ, soumettre des demandes, consulter alertes & documents personnels |
| **RH** | Tout AGENT + valider/rejeter demandes, générer rapports CSV/PDF, voir tous les pointages |
| **ADMIN** | Tout RH + gérer utilisateurs et sites, configurer le géofencing |

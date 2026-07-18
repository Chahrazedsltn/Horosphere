# Règles de gestion métier — Horosphere

## Utilisateurs et rôles

**RG01** — Tout utilisateur du système possède un rôle unique parmi : AGENT, RH ou ADMIN.
- *AGENT* : peut pointer, consulter ses propres pointages, soumettre des demandes.
- *RH* : peut gérer les demandes, consulter les pointages de tous les agents de son périmètre, exporter des rapports.
- *ADMIN* : dispose de tous les droits, y compris la gestion des utilisateurs et des sites.
- → Cardinalité : chaque utilisateur a exactement 1 rôle (attribut obligatoire, valeur par défaut AGENT).

**RG02** — L'adresse email d'un utilisateur est unique dans le système et sert d'identifiant de connexion.

**RG03** — Chaque utilisateur dispose d'un solde de congés initialisé à 25 jours. Ce solde est décrémenté lors de l'approbation d'une demande de congé et incrémenté en cas de rejet ultérieur.

**RG04** — L'utilisateur doit donner son consentement RGPD pour utiliser le système. Ce consentement est révocable.

## Pointage

**RG05** — Un utilisateur peut effectuer 0 à n pointages. Un pointage appartient à exactement un utilisateur.
- → MCD : UTILISATEUR (0,n) — EFFECTUE — POINTAGE (1,1).

**RG06** — Un pointage est associé à 0 ou 1 site. Le site peut être absent si le pointage est effectué sans géolocalisation ou si le site a été supprimé.
- → MCD : SITE (0,n) — CONCERNE — POINTAGE (0,1).
- → MPD : site_id nullable, ON DELETE SET NULL.

**RG07** — Le cycle de vie d'un pointage suit les états : EN_COURS → EN_PAUSE → EN_COURS → VALIDE. Les états HORS_ZONE et ANOMALIE sont attribués automatiquement par le système.
- *EN_COURS* : l'agent est arrivé et travaille.
- *EN_PAUSE* : l'agent est en pause.
- *VALIDE* : l'agent a enregistré son départ, la journée est close.
- *HORS_ZONE* : les coordonnées GPS du pointage sont hors du rayon de géofencing du site.
- *ANOMALIE* : une incohérence a été détectée (ex : durée de travail excessive).

**RG08** — Lors du pointage d'arrivée, si le géofencing est actif sur le site, le système compare les coordonnées GPS de l'agent avec le centre du site (formule de Haversine). Si la distance dépasse le rayon autorisé, le statut passe à HORS_ZONE et une alerte est créée.

**RG09** — Un agent peut mettre son pointage en pause et le reprendre. La durée cumulée des pauses est stockée en minutes dans `durees_pause_minutes`.

**RG10** — La suppression d'un utilisateur est interdite s'il possède des pointages (ON DELETE RESTRICT). Les pointages constituent des données de temps de travail à valeur légale.

## Alertes

**RG11** — Un pointage peut générer 0 à n alertes. Différents types d'alertes peuvent coexister pour un même pointage (ex : HORS_ZONE et ECART_HORAIRE simultanément).
- → MCD : POINTAGE (0,n) — GÉNÈRE — ALERTE (0,1). Le 0,1 côté alerte signifie qu'une alerte est liée à au plus un pointage, mais peut exister sans pointage (alerte système).

**RG12** — Les trois types d'alertes sont :
- *OUBLI_DEPART* : déclenchée automatiquement lorsqu'un pointage est toujours EN_COURS après l'heure de fermeture du site ou après un seuil configurable.
- *HORS_ZONE* : déclenchée au moment du pointage si l'agent est en dehors du périmètre de géofencing du site.
- *ECART_HORAIRE* : déclenchée lorsque la durée de travail effective présente un écart significatif par rapport aux horaires attendus.

**RG13** — Une alerte est adressée à exactement un utilisateur (l'agent concerné ou un RH). L'utilisateur peut la marquer comme lue.
- → MCD : UTILISATEUR (0,n) — REÇOIT — ALERTE (1,1).

**RG14** — Si un pointage est supprimé, les alertes associées conservent leur existence mais perdent le lien avec le pointage (ON DELETE SET NULL). Cela permet de maintenir la traçabilité.

## Demandes

**RG15** — Un utilisateur peut soumettre 0 à n demandes. Chaque demande appartient à exactement un utilisateur.
- → MCD : UTILISATEUR (0,n) — SOUMET — DEMANDE (1,1).

**RG16** — Une demande suit le cycle de vie : EN_ATTENTE → APPROUVEE ou REJETEE. Seul un utilisateur RH ou ADMIN peut changer le statut.

**RG17** — Les types de demandes sont : CONGE (congé payé), CORRECTION (rectification d'un pointage erroné), ABSENCE (déclaration d'absence), AUTRE.

**RG18** — La date de fin d'une demande doit être supérieure ou égale à la date de début.

**RG19** — Une demande peut être accompagnée d'un justificatif (pièce jointe : certificat médical, etc.).

**RG20** — La suppression d'un utilisateur entraîne la suppression de toutes ses demandes (ON DELETE CASCADE). Ce comportement est conforme au droit à l'effacement RGPD : les demandes sont des données personnelles de l'agent.

## Documents

**RG21** — Un utilisateur peut posséder 0 à n documents générés par le système. Chaque document appartient à exactement un utilisateur.
- → MCD : UTILISATEUR (0,n) — POSSÈDE — DOCUMENT (1,1).

**RG22** — Les documents sont des exports générés par l'application (rapports de pointage, fiches récapitulatives) aux formats CSV ou PDF. Ils ne sont pas modifiables après génération.

**RG23** — La suppression d'un utilisateur entraîne la suppression de tous ses documents (ON DELETE CASCADE), conformément au droit à l'effacement RGPD.

## Sites et géofencing

**RG24** — Un site définit une zone géographique circulaire caractérisée par un point central (latitude, longitude) et un rayon en mètres.

**RG25** — Le géofencing peut être activé ou désactivé individuellement par site. Lorsqu'il est désactivé, les pointages sur ce site ne déclenchent pas de vérification de position.

**RG26** — Un site peut être associé à 0 à n pointages. La suppression d'un site ne supprime pas les pointages historiques (ON DELETE SET NULL sur site_id).

## Audit et traçabilité

**RG27** — Toute action sensible (approbation/rejet de demande, modification de pointage, création/modification/suppression d'utilisateur, réinitialisation de mot de passe, exercice de droits RGPD) est enregistrée dans le journal d'audit.

**RG28** — Le journal d'audit conserve l'identifiant et l'email de l'auteur de l'action, le type et l'identifiant de l'entité cible, les détails contextuels (en JSON) et l'adresse IP du client.

**RG29** — Les entrées du journal d'audit ne sont jamais supprimées ni modifiées (insertion seule). La table audit_log n'a pas de clé étrangère vers utilisateur pour éviter les cascades de suppression.

## Sécurité et authentification

**RG30** — Les mots de passe sont stockés hashés (bcrypt ou argon2). Le mot de passe en clair n'est jamais conservé.

**RG31** — La réinitialisation de mot de passe s'effectue via un jeton cryptographique à usage unique, valide pendant 1 heure (TTL = 3600 secondes). Le jeton est invalidé après utilisation.

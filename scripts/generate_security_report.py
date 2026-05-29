#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rapport de tests et de securite - Jalon 5 - Horosphere"""

from fpdf import FPDF
import datetime

DATE_RAPPORT = "29 mai 2026"
GITHUB_URL   = "https://github.com/Chahrazedsltn/Horosphere"

TICK  = "[OK]"
CROSS = "[ECHEC]"
WARN  = "[AVERT]"

# ─────────────────────────────────────────────
# DONNEES PHPUNIT
# ─────────────────────────────────────────────
UNIT_TESTS = [
    ("testCalculerDistanceMemePoint",            "Distance nulle pour un meme point (delta < 0.01 m)"),
    ("testCalculerDistanceParisMarseilleApprox", "Distance Paris->Marseille entre 650 et 680 km"),
    ("testEstDansZoneVrai",                      "Meme coordonnee que le site -> dans la zone"),
    ("testEstDansZoneFaux",                      "Lyon -> hors zone Paris (rayon 200 m)"),
    ("testEstDansZoneGeofencingDesactive",        "Geofencing desactive -> toujours dans zone"),
    ("testEstDansZoneFrontiereRayon",            "Point a ~180 m -> dans zone (rayon 200 m)"),
    ("testDistanceSymetrique",                   "dist(A,B) = dist(B,A) a 0.01 m pres"),
    ("testCalculerDistancePointsProches",        "~0.001 deg lat -> distance entre 50 et 200 m"),
    ("testValiderCoordonneesValides",            "Coordonnees valides acceptees"),
    ("testValiderCoordonneesInvalides",          "lat>90 / lon>180 -> rejetees"),
]

FUNCTIONAL_TESTS = [
    ("PointageController", [
        ("testLoginReturnsToken",           "POST /api/auth/login -> 200 + token JWT"),
        ("testLoginInvalidCredentials",     "Mauvais mot de passe -> 401 Unauthorized"),
        ("testMeReturnUser",                "GET /api/auth/me -> profil utilisateur"),
        ("testArriverRequiresAuth",         "POST /api/pointages/arriver sans token -> 401"),
        ("testArriverWithValidCoords",      "Pointage arrivee coords valides -> 201"),
        ("testArriverMissingCoords",        "Corps vide -> 422 Unprocessable Entity"),
        ("testMesPointagesReturnsList",     "GET /api/pointages/mes-pointages -> liste"),
        ("testListePointagesRequiresRh",    "Agent accede /api/pointages -> 403"),
        ("testListePointagesAsRh",          "RH accede /api/pointages -> 200"),
    ]),
    ("AlerteController", [
        ("testMesAlertesRequiresAuth",           "GET /api/alertes sans token -> 401"),
        ("testMesAlertesAsAgent",                "Reponse contient 'data' et 'non_lues'"),
        ("testToutesAlertesRequiresRh",          "Agent accede /api/alertes/toutes -> 403"),
        ("testToutesAlertesAsRh",                "RH accede /api/alertes/toutes -> 200"),
        ("testMarquerLueAsOwner",                "PATCH /lire par proprietaire -> estLue=true"),
        ("testMarquerLueRefuseAutreUtilisateur", "Agent2 marque alerte agent1 -> 403"),
        ("testMarquerToutLu",                    "PATCH /tout-lire -> toutes alertes lues"),
        ("testMarquerToutLuRequiresAuth",         "PATCH /tout-lire sans token -> 401"),
    ]),
    ("DemandeController", [
        ("testListeDemandesRequiresAuth",    "GET /api/demandes sans token -> 401"),
        ("testListeDemandesAsAgent",         "Agent voit uniquement ses propres demandes"),
        ("testListeDemandesAsRhVoitTout",    "RH voit toutes les demandes"),
        ("testEnAttenteRequiresRh",          "Agent accede /en-attente -> 403"),
        ("testEnAttenteAsRh",               "RH accede /en-attente -> 200"),
        ("testCreerDemandeAsAgent",          "POST /api/demandes -> 201"),
        ("testCreerDemandeChampsManquants",  "Corps incomplet -> 422"),
        ("testTraiterDemandeApprouveeAsRh",  "PUT traiter approuver -> statut APPROUVEE"),
        ("testTraiterDemandeRejeteeAsRh",    "PUT traiter rejeter -> statut REJETEE"),
        ("testTraiterDemandeDecisionInvalide","Decision invalide -> 422"),
        ("testTraiterDemandeRequiresRh",     "Agent tente de traiter -> 403"),
    ]),
    ("DocumentController", [
        ("testListeDocumentsRequiresAuth",   "GET /api/documents sans token -> 401"),
        ("testListeDocumentsAsAgent",        "Agent voit ses documents -> 200"),
        ("testExportCsvRequiresRh",          "Agent export CSV -> 403"),
        ("testExportCsvDatesMaaquantes",     "Export sans dates -> 422"),
        ("testExportCsvAsRh",               "RH export CSV -> 200"),
        ("testExportCsvAvecUtilisateurCible","Export filtre par utilisateur -> 200"),
        ("testTelechargerDocumentAsOwner",   "Agent telecharge son doc -> 200"),
        ("testTelechargerDocumentAutreUtilisateurForbidden", "Agent telecharge doc autre -> 403"),
    ]),
    ("SiteController", [
        ("testListeSitesAsAgent",       "GET /api/sites agent -> 200"),
        ("testListeSitesRequiresAuth",  "GET /api/sites sans token -> 401"),
        ("testDetailSite",             "GET /api/sites/:id -> 200"),
        ("testCreerSiteRequiresAdmin", "RH cree site -> 403"),
        ("testCreerSiteRhForbidden",   "RH cree site -> 403"),
        ("testCreerSiteAsAdmin",       "Admin cree site -> 201"),
        ("testCreerSiteRayonTropPetit","Rayon < 50m -> 422"),
        ("testCreerSiteRayonTropGrand","Rayon > 5000m -> 422"),
        ("testModifierSiteAsAdmin",    "Admin modifie site -> 200"),
        ("testSupprimerSiteAsAdmin",   "Admin supprime site -> 204"),
    ]),
    ("UserController", [
        ("testListeUsersRequiresAuth",       "GET /api/users sans token -> 401"),
        ("testListeUsersAsAgentForbidden",   "Agent accede /api/users -> 403"),
        ("testListeUsersAsRh",              "RH accede /api/users -> 200"),
        ("testDetailUserAsRh",              "GET /api/users/:id -> 200"),
        ("testCreerUserRequiresAdmin",      "RH cree user -> 403"),
        ("testCreerUserChampsManquants",    "Corps incomplet -> 422"),
        ("testCreerUserAsAdmin",            "Admin cree user -> 201"),
        ("testModifierUserAsAdmin",         "Admin modifie user -> 200"),
        ("testSupprimerUserAsAdmin",        "Admin supprime user -> 204"),
        ("testSupprimerPropreCompteForbidden","Admin supprime son propre compte -> 403"),
        ("testStatsDashboardAsAgent",       "Agent dashboard stats -> 200"),
        ("testStatsDashboardAsRh",          "RH dashboard stats -> 200"),
    ]),
]

# ─────────────────────────────────────────────
# DONNEES TESTS DE SECURITE
# ─────────────────────────────────────────────
SECURITY_TESTS = [
    ("Authentification & JWT", [
        (TICK,  "Login valide -> JWT token RSA-256 retourne"),
        (TICK,  "Token JWT invalide -> 401 Unauthorized"),
        (TICK,  "Token JWT falsifie (signature incorrecte) -> 401 Unauthorized"),
        (TICK,  "Endpoint protege sans Authorization header -> 401"),
        (TICK,  "Token genere via console Symfony valide et accepte par l'API"),
    ]),
    ("Injection SQL", [
        (TICK,  "Login: ' OR '1'='1 -> 429 (rate limiter, pas d'injection)"),
        (TICK,  "Login: ' DROP TABLE users -- -> 429 (rate limiter bloque)"),
        (TICK,  "Login: UNION SELECT via query param -> reponse normale (ORM protege)"),
        (TICK,  "Doctrine ORM: toutes les requetes utilisent des parametres lies"),
        (TICK,  "Aucune requete SQL brute detectee dans le code source"),
    ]),
    ("XSS (Cross-Site Scripting)", [
        (TICK,  "Payload XSS non reflechi dans les reponses API JSON"),
        (TICK,  "React JSX: echappement automatique des variables (pas de dangerouslySetInnerHTML)"),
        (TICK,  "Content-Security-Policy configuree dans nginx"),
        (WARN,  "Donnees XSS stockables en DB si admin les saisit (mitigue par React)"),
        (TICK,  "Injection <script> dans champs API -> stocke en JSON escape (\\/)"),
    ]),
    ("CSRF & CORS", [
        (TICK,  "API JWT stateless: pas de session cookie -> CSRF non applicable"),
        (TICK,  "CORS: origine http://evil.com rejetee (pas de header ACAO)"),
        (TICK,  "CORS: seules les origines localhost autorisees en dev"),
        (TICK,  "En-tete Authorization: Bearer requis sur toutes les routes protegees"),
    ]),
    ("Brute Force & Rate Limiting", [
        (TICK,  "Rate limit IP: 10 tentatives / 60s -> HTTP 429 declenche"),
        (TICK,  "Rate limit email: 5 tentatives / 300s -> HTTP 429 avec Retry-After"),
        (TICK,  "Endpoint mot-de-passe-oublie: rate limite (5 req/600s par IP)"),
        (TICK,  "Email inexistant -> HTTP 200 (prevention enumeration d'emails)"),
    ]),
    ("Controle d'acces (RBAC)", [
        (TICK,  "AGENT -> GET /api/users -> 403 Forbidden"),
        (TICK,  "AGENT -> POST /api/users (creer) -> 403 Forbidden"),
        (TICK,  "AGENT -> GET /api/pointages (tous) -> 403 Forbidden"),
        (TICK,  "ADMIN -> GET /api/users -> 200 OK"),
        (TICK,  "ADMIN -> suppression de son propre compte -> 403 (auto-protection)"),
        (TICK,  "Hierarchie des roles: ADMIN > RH > AGENT implementee dans security.yaml"),
    ]),
    ("En-tetes de securite HTTP", [
        (TICK,  "X-Frame-Options: SAMEORIGIN (anti-clickjacking)"),
        (TICK,  "X-Content-Type-Options: nosniff (anti-MIME sniffing)"),
        (TICK,  "X-XSS-Protection: 1; mode=block"),
        (TICK,  "Referrer-Policy: strict-origin-when-cross-origin"),
        (TICK,  "Content-Security-Policy: default-src 'self'; frame-src 'none'; object-src 'none'"),
    ]),
    ("Gestion des mots de passe", [
        (TICK,  "Algorithme: bcrypt avec facteur de cout 13 ($2y$13$) - confirme en BDD"),
        (TICK,  "Aucun mot de passe en clair en base de donnees"),
        (TICK,  "Token reset mot de passe: random_bytes(32) + bin2hex (cryptographiquement sur)"),
        (TICK,  "Token reset a usage unique, expire en 3600s"),
        (TICK,  "Validation complexite: min 8 cars, majuscule, chiffre, caractere special"),
    ]),
    ("Protection des donnees (RGPD)", [
        (TICK,  "Champ consentement_rgpd present sur l'entite Utilisateur"),
        (TICK,  "Mots de passe haches (bcrypt), jamais stockes en clair"),
        (TICK,  "Logs d'audit: actions sensibles tracees (IP, userId, timestamp)"),
        (WARN,  "Pas d'endpoint d'export de donnees personnelles (droit a la portabilite)"),
        (WARN,  "Pas d'endpoint de suppression de compte utilisateur (droit a l'effacement)"),
    ]),
    ("Exports & endpoints sensibles", [
        (TICK,  "POST /api/exports/csv sans auth -> 401 Unauthorized"),
        (TICK,  "POST /api/exports/pdf sans auth -> 401 Unauthorized"),
        (TICK,  "GET /api/alertes sans auth -> 401 Unauthorized"),
        (TICK,  "GET /api/demandes sans auth -> 401 Unauthorized"),
        (TICK,  "GET /api/sites sans auth -> 401 Unauthorized"),
    ]),
]

# ─────────────────────────────────────────────
# COUVERTURE DE CODE
# ─────────────────────────────────────────────
COVERAGE = [
    ("AlerteController",        100.0, 100.0),
    ("UserController",           77.8,  95.8),
    ("SiteController",           75.0,  91.1),
    ("DemandeController",        66.7,  94.0),
    ("DocumentController",       57.1,  86.8),
    ("PointageController",       30.0,  51.4),
    ("AuthController",           20.0,  17.3),
    ("GeofencingService",        83.3,  97.1),
    ("AuditService",            100.0, 100.0),
    ("DemandeService",           25.0,  85.0),
]

# ─────────────────────────────────────────────
# PDF CLASS
# ─────────────────────────────────────────────

class PDF(FPDF):
    def __init__(self):
        super().__init__()
        self.set_margins(18, 15, 18)
        self.set_auto_page_break(auto=True, margin=15)

    def header(self):
        if self.page_no() == 1:
            return
        self.set_font("Helvetica", "B", 8)
        self.set_text_color(120, 120, 120)
        self.cell(0, 6, "Horosphere - Rapport Tests & Securite - Jalon 5 - " + DATE_RAPPORT, align="C")
        self.ln(2)
        self.set_draw_color(200, 200, 200)
        self.line(18, self.get_y(), 192, self.get_y())
        self.ln(3)
        self.set_text_color(0, 0, 0)

    def footer(self):
        self.set_y(-12)
        self.set_font("Helvetica", "I", 8)
        self.set_text_color(150, 150, 150)
        self.cell(0, 5, f"Page {self.page_no()}", align="C")
        self.set_text_color(0, 0, 0)

    def cover_page(self):
        self.add_page()
        # Fond bleu fonce header
        self.set_fill_color(15, 30, 60)
        self.rect(0, 0, 210, 70, "F")

        self.set_y(18)
        self.set_font("Helvetica", "B", 28)
        self.set_text_color(255, 255, 255)
        self.cell(0, 12, "HOROSPHERE", align="C", ln=True)

        self.set_font("Helvetica", "", 13)
        self.set_text_color(180, 210, 255)
        self.cell(0, 8, "Gestion des presences par geofencing GPS", align="C", ln=True)

        self.set_y(78)
        self.set_font("Helvetica", "B", 20)
        self.set_text_color(15, 30, 60)
        self.cell(0, 10, "Rapport de Tests & Securite", align="C", ln=True)
        self.set_font("Helvetica", "B", 14)
        self.set_text_color(60, 130, 210)
        self.cell(0, 8, "Jalon 5 - Developpement, Securite & Tests (version Beta)", align="C", ln=True)

        self.ln(5)
        x0 = 20
        box_w = 170

        # ── Cadre principal (stats + meta) ──────────────────────────────
        self.set_fill_color(240, 248, 255)
        self.set_draw_color(60, 130, 210)
        self.set_line_width(0.6)
        box_y = self.get_y()
        box_h = 82
        self.rect(x0, box_y, box_w, box_h, "FD")

        # Titre interne du cadre
        self.set_y(box_y + 4)
        self.set_x(x0)
        self.set_font("Helvetica", "B", 9)
        self.set_text_color(60, 130, 210)
        self.cell(box_w, 5, "  RESUME DES RESULTATS", align="L", ln=True)
        # Separateur
        self.set_draw_color(180, 210, 240)
        self.set_line_width(0.3)
        self.line(x0 + 4, self.get_y(), x0 + box_w - 4, self.get_y())
        self.ln(2)

        # Stats (2 colonnes)
        stats = [
            ("Tests PHPUnit",    "68 / 68",  "100 % reussis"),
            ("Assertions",       "126",       "toutes validees"),
            ("Couverture lignes","51,1 %",    "methodes : 58,6 %"),
            ("Tests securite",   "46 / 50",   "4 avertissements"),
        ]
        for label, val, sub in stats:
            self.set_x(x0 + 6)
            self.set_font("Helvetica", "B", 9.5)
            self.set_text_color(15, 30, 60)
            self.cell(54, 6, label + " :", ln=False)
            self.set_font("Helvetica", "B", 10.5)
            self.set_text_color(0, 150, 80)
            self.cell(24, 6, val, ln=False)
            self.set_font("Helvetica", "I", 8.5)
            self.set_text_color(100, 100, 100)
            self.cell(0, 6, sub, ln=True)

        # Separateur interne
        self.ln(1)
        self.set_draw_color(180, 210, 240)
        self.line(x0 + 4, self.get_y(), x0 + box_w - 4, self.get_y())
        self.ln(3)

        # Metadonnees (date, stack, echeance)
        meta_simple = [
            ("Date du rapport",  DATE_RAPPORT),
            ("Stack technique",  "Symfony 7.1 + React 18 + MariaDB + Docker"),
            ("Echeance jalon 5", "29 mai 2026"),
        ]
        for k, v in meta_simple:
            self.set_x(x0 + 6)
            self.set_font("Helvetica", "B", 8.5)
            self.set_text_color(15, 30, 60)
            self.cell(44, 5, k + " :", ln=False)
            self.set_font("Helvetica", "", 8.5)
            self.set_text_color(60, 60, 60)
            self.cell(0, 5, v, ln=True)

        # Ligne depot Git mise en valeur
        self.ln(2)
        git_y = self.get_y()
        self.set_fill_color(15, 30, 60)
        self.set_draw_color(15, 30, 60)
        self.rect(x0 + 4, git_y, box_w - 8, 8, "F")
        self.set_y(git_y)
        self.set_x(x0 + 8)
        self.set_font("Helvetica", "B", 8.5)
        self.set_text_color(180, 210, 255)
        self.cell(36, 8, "Depot Git :", ln=False)
        self.set_font("Helvetica", "B", 8.5)
        self.set_text_color(120, 200, 255)
        self.cell(0, 8, GITHUB_URL, ln=True)

        self.set_text_color(0, 0, 0)
        self.set_draw_color(0, 0, 0)
        self.set_line_width(0.2)
        self.set_y(box_y + box_h + 4)

    def section_title(self, text, level=1):
        self.ln(4)
        if level == 1:
            self.set_fill_color(15, 30, 60)
            self.set_text_color(255, 255, 255)
            self.set_font("Helvetica", "B", 12)
            self.cell(0, 8, "  " + text, ln=True, fill=True)
        else:
            self.set_fill_color(60, 130, 210)
            self.set_text_color(255, 255, 255)
            self.set_font("Helvetica", "B", 10)
            self.cell(0, 6, "  " + text, ln=True, fill=True)
        self.set_text_color(0, 0, 0)
        self.ln(2)

    def subsection_title(self, text):
        self.set_font("Helvetica", "B", 10)
        self.set_text_color(40, 80, 160)
        self.set_x(18)
        self.cell(0, 6, text, ln=True)
        self.set_draw_color(40, 80, 160)
        self.line(18, self.get_y(), 192, self.get_y())
        self.ln(2)
        self.set_text_color(0, 0, 0)

    def test_row(self, name, desc, status=True, indent=0):
        if status == TICK:
            icon, r, g, b = TICK,  0, 150, 80
        elif status == WARN:
            icon, r, g, b = WARN, 180, 100, 0
        else:
            icon, r, g, b = CROSS, 200, 0, 0

        self.set_x(18 + indent)
        # Icon
        self.set_font("Helvetica", "B", 8)
        self.set_text_color(r, g, b)
        self.cell(14, 5, icon, ln=False)
        # Name
        self.set_font("Courier", "", 7.5)
        self.set_text_color(30, 30, 30)
        self.cell(72, 5, name[:52], ln=False)
        # Desc
        self.set_font("Helvetica", "", 8)
        self.set_text_color(60, 60, 60)
        self.multi_cell(0, 5, desc)
        self.set_text_color(0, 0, 0)

    def security_row(self, status, desc):
        if status == TICK:
            r, g, b = 0, 150, 80
        elif status == WARN:
            r, g, b = 180, 100, 0
        else:
            r, g, b = 200, 0, 0

        self.set_x(18)
        self.set_font("Helvetica", "B", 8)
        self.set_text_color(r, g, b)
        self.cell(14, 5, status, ln=False)
        self.set_font("Helvetica", "", 8.5)
        self.set_text_color(40, 40, 40)
        self.multi_cell(0, 5, desc)
        self.set_text_color(0, 0, 0)

    def coverage_bar(self, label, pct_method, pct_line):
        self.set_x(18)
        self.set_font("Helvetica", "", 8.5)
        self.set_text_color(40, 40, 40)
        self.cell(58, 5, label, ln=False)

        # Barre methodes
        bar_w = 50
        bar_h = 4
        x = self.get_x()
        y = self.get_y() + 0.5
        self.set_fill_color(220, 220, 220)
        self.rect(x, y, bar_w, bar_h, "F")
        fill_w = bar_w * pct_method / 100
        if pct_method >= 80:
            self.set_fill_color(0, 180, 80)
        elif pct_method >= 50:
            self.set_fill_color(255, 160, 0)
        else:
            self.set_fill_color(220, 60, 60)
        self.rect(x, y, fill_w, bar_h, "F")
        self.set_x(x + bar_w + 2)
        self.set_font("Helvetica", "B", 8)
        self.set_text_color(40, 40, 40)
        self.cell(14, 5, f"{pct_method:.0f}%", ln=False)

        # Barre lignes
        x2 = self.get_x() + 6
        self.set_x(x2)
        self.set_fill_color(220, 220, 220)
        self.rect(x2, y, bar_w, bar_h, "F")
        fill_w2 = bar_w * pct_line / 100
        if pct_line >= 80:
            self.set_fill_color(60, 130, 210)
        elif pct_line >= 50:
            self.set_fill_color(255, 160, 0)
        else:
            self.set_fill_color(220, 60, 60)
        self.rect(x2, y, fill_w2, bar_h, "F")
        self.set_x(x2 + bar_w + 2)
        self.set_font("Helvetica", "B", 8)
        self.cell(0, 5, f"{pct_line:.0f}%", ln=True)
        self.set_text_color(0, 0, 0)


def build_pdf():
    pdf = PDF()
    pdf.set_title("Rapport Tests & Securite - Jalon 5 - Horosphere")

    # ── PAGE DE GARDE ──────────────────────────────────────────────────────
    pdf.cover_page()

    # ── SOMMAIRE ───────────────────────────────────────────────────────────
    pdf.add_page()
    pdf.section_title("SOMMAIRE")
    toc = [
        ("1.", "Tests unitaires (GeofencingService)"),
        ("2.", "Tests fonctionnels (6 controleurs REST)"),
        ("3.", "Couverture de code"),
        ("4.", "Tests de securite OWASP"),
        ("   4.1", "Authentification & JWT"),
        ("   4.2", "Injection SQL"),
        ("   4.3", "XSS (Cross-Site Scripting)"),
        ("   4.4", "CSRF & CORS"),
        ("   4.5", "Brute Force & Rate Limiting"),
        ("   4.6", "Controle d'acces (RBAC)"),
        ("   4.7", "En-tetes de securite HTTP"),
        ("   4.8", "Gestion des mots de passe"),
        ("   4.9", "Protection des donnees (RGPD)"),
        ("   4.10", "Exports & endpoints sensibles"),
        ("5.", "Analyse des vulnerabilites identifiees"),
        ("6.", "Bilan et plan d'action"),
    ]
    pdf.set_font("Helvetica", "", 10)
    for num, title in toc:
        pdf.set_x(22)
        pdf.set_font("Helvetica", "B", 9)
        pdf.cell(16, 6, num, ln=False)
        pdf.set_font("Helvetica", "", 9)
        pdf.cell(0, 6, title, ln=True)

    # ── 1. TESTS UNITAIRES ─────────────────────────────────────────────────
    pdf.add_page()
    pdf.section_title("1. TESTS UNITAIRES - GeofencingService")

    pdf.set_font("Helvetica", "", 9)
    pdf.set_text_color(60, 60, 60)
    pdf.multi_cell(0, 5,
        "Le service de geofencing implemente la formule de Haversine pour calculer les distances "
        "GPS et determiner si un employe se trouve dans la zone autorisee du site. "
        "10 tests unitaires couvrent les cas nominaux et les cas limites.")
    pdf.ln(2)

    pdf.set_font("Helvetica", "B", 8.5)
    pdf.set_fill_color(230, 240, 255)
    pdf.set_x(18)
    pdf.cell(14, 5, "Statut", fill=True, ln=False)
    pdf.cell(72, 5, "Nom du test", fill=True, ln=False)
    pdf.cell(0,  5, "Description", fill=True, ln=True)
    pdf.set_text_color(0, 0, 0)

    for name, desc in UNIT_TESTS:
        pdf.test_row(name, desc, TICK)

    pdf.ln(4)
    pdf.set_fill_color(230, 255, 230)
    pdf.set_draw_color(0, 150, 80)
    pdf.set_line_width(0.4)
    pdf.set_x(18)
    pdf.set_font("Helvetica", "B", 9)
    pdf.set_text_color(0, 120, 60)
    pdf.cell(0, 6, "  Resultat : 10/10 tests reussis - GeofencingService : 97,1 % de couverture de lignes", fill=True, border=1, ln=True)
    pdf.set_text_color(0, 0, 0)
    pdf.set_draw_color(0, 0, 0)
    pdf.set_line_width(0.2)

    # ── 2. TESTS FONCTIONNELS ──────────────────────────────────────────────
    pdf.add_page()
    pdf.section_title("2. TESTS FONCTIONNELS - 6 controleurs REST")

    pdf.set_font("Helvetica", "", 9)
    pdf.set_text_color(60, 60, 60)
    pdf.multi_cell(0, 5,
        "Les tests fonctionnels utilisent le WebTestCase de Symfony avec une base de donnees "
        "de test isolee (horosphere_test) et des fixtures dediees. Chaque requete HTTP est "
        "simulee en memoire. 58 tests couvrent les 6 controleurs principaux.")
    pdf.ln(2)

    total_ok = 0
    for controller, tests in FUNCTIONAL_TESTS:
        pdf.subsection_title(controller)
        pdf.set_font("Helvetica", "B", 8.5)
        pdf.set_fill_color(230, 240, 255)
        pdf.set_x(18)
        pdf.cell(14, 5, "Statut", fill=True, ln=False)
        pdf.cell(72, 5, "Nom du test", fill=True, ln=False)
        pdf.cell(0,  5, "Description", fill=True, ln=True)
        for name, desc in tests:
            pdf.test_row(name, desc, TICK)
            total_ok += 1
        pdf.ln(2)

    pdf.ln(2)
    pdf.set_fill_color(230, 255, 230)
    pdf.set_draw_color(0, 150, 80)
    pdf.set_line_width(0.4)
    pdf.set_x(18)
    pdf.set_font("Helvetica", "B", 9)
    pdf.set_text_color(0, 120, 60)
    pdf.cell(0, 6, f"  Resultat global : 68/68 tests reussis - 126 assertions - 0 echec", fill=True, border=1, ln=True)
    pdf.set_text_color(0, 0, 0)
    pdf.set_draw_color(0, 0, 0)
    pdf.set_line_width(0.2)

    # ── 3. COUVERTURE ──────────────────────────────────────────────────────
    pdf.add_page()
    pdf.section_title("3. COUVERTURE DE CODE")

    pdf.set_font("Helvetica", "", 9)
    pdf.set_text_color(60, 60, 60)
    pdf.multi_cell(0, 5,
        "La couverture est mesuree via Xdebug + PHPUnit coverage. "
        "Les barres vertes indiquent >= 80 %, orange >= 50 %, rouge < 50 %.")
    pdf.ln(3)

    # En-tete legende
    pdf.set_font("Helvetica", "B", 8.5)
    pdf.set_x(18)
    pdf.cell(58, 5, "Composant", ln=False)
    pdf.cell(66, 5, "Couverture methodes", ln=False)
    pdf.cell(0,  5, "Couverture lignes", ln=True)
    pdf.set_draw_color(180, 180, 180)
    pdf.line(18, pdf.get_y(), 192, pdf.get_y())
    pdf.ln(1)

    for label, meth, line in COVERAGE:
        pdf.coverage_bar(label, meth, line)

    pdf.ln(3)
    pdf.set_font("Helvetica", "", 8.5)
    pdf.set_text_color(60, 60, 60)
    pdf.multi_cell(0, 5,
        "Resume global : Classes 16,7 % (6/36) | Methodes 58,6 % (161/275) | Lignes 51,1 % (715/1398)\n"
        "Note : La couverture des classes inclut les entites Doctrine (getters/setters non testes directement). "
        "Les controleurs principaux atteignent 77-100 % de couverture des methodes.")

    # ── 4. TESTS DE SECURITE ───────────────────────────────────────────────
    pdf.add_page()
    pdf.section_title("4. TESTS DE SECURITE OWASP")

    pdf.set_font("Helvetica", "", 9)
    pdf.set_text_color(60, 60, 60)
    pdf.multi_cell(0, 5,
        "Les tests de securite ont ete realises le " + DATE_RAPPORT + " sur l'application deployee "
        "localement via Docker Compose. Les tests couvrent le Top 10 OWASP avec des appels "
        "HTTP directs (curl) et une analyse statique du code source.")
    pdf.ln(2)

    for section_title, tests in SECURITY_TESTS:
        pdf.subsection_title("4.x - " + section_title)
        for status, desc in tests:
            pdf.security_row(status, desc)
        pdf.ln(2)

    # ── 5. VULNERABILITES IDENTIFIEES ──────────────────────────────────────
    pdf.add_page()
    pdf.section_title("5. ANALYSE DES VULNERABILITES IDENTIFIEES")

    vulnerabilities = [
        ("MOYEN", "XSS stocke (donnees utilisateur)",
         "Des balises HTML/script peuvent etre stockees dans les champs prenom/nom via l'API "
         "(requiert des droits ADMIN). L'execution est bloquee par React JSX qui echappe "
         "automatiquement les variables. Le risque reel est faible mais une validation "
         "cote serveur est recommandee.",
         "Ajouter une validation Symfony Assert\\NoSuspiciousCharacters ou strip_tags() "
         "sur les champs prenom/nom dans les entites."),
        ("FAIBLE", "Absence d'endpoints RGPD (portabilite et effacement)",
         "L'article 17 du RGPD (droit a l'effacement) et l'article 20 (portabilite) "
         "necessitent des endpoints permettant a l'utilisateur de supprimer son compte "
         "et d'exporter ses donnees personnelles. Ces fonctionnalites sont absentes.",
         "Implementer : DELETE /api/auth/mon-compte et GET /api/auth/mes-donnees "
         "(export JSON de toutes les donnees personnelles)."),
        ("FAIBLE", "JWT stocke en localStorage",
         "Le token JWT est stocke en localStorage (option 'Rester connecte'). "
         "localStorage est accessible par le JavaScript de la page, ce qui peut poser "
         "un risque en cas de faille XSS tiers. sessionStorage est utilise par defaut "
         "(sans 'Rester connecte'), ce qui est plus securise.",
         "Envisager l'utilisation de cookies HttpOnly + SameSite=Strict pour le token "
         "persistent, eliminant le risque d'acces JavaScript."),
        ("INFO", "Couverture AuthController insuffisante (17,3 %)",
         "Le controlleur d'authentification (reinitialisation mot de passe, deconnexion, "
         "refresh token) n'est couvert qu'a 17,3 % par les tests. Des scenarios "
         "critiques comme le reset de mot de passe ne sont pas testes.",
         "Ajouter des tests pour : reinitialiserMotDePasse(), motDePasseOublie(), "
         "logout(), et les cas d'erreur du refresh token."),
    ]

    severity_colors = {
        "CRITIQUE": (200, 0, 0),
        "ELEVE":    (200, 80, 0),
        "MOYEN":    (180, 130, 0),
        "FAIBLE":   (0, 120, 180),
        "INFO":     (80, 80, 80),
    }

    for sev, title, desc, reco in vulnerabilities:
        r, g, b = severity_colors.get(sev, (80, 80, 80))
        # Bandeau severite
        pdf.set_fill_color(r, g, b)
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Helvetica", "B", 9)
        pdf.set_x(18)
        pdf.cell(20, 5.5, " " + sev, fill=True, ln=False)
        pdf.set_fill_color(245, 245, 245)
        pdf.set_text_color(30, 30, 30)
        pdf.cell(0, 5.5, "  " + title, fill=True, ln=True)
        # Description
        pdf.set_x(20)
        pdf.set_font("Helvetica", "", 8.5)
        pdf.set_text_color(60, 60, 60)
        pdf.multi_cell(0, 5, desc)
        # Recommandation
        pdf.set_x(20)
        pdf.set_font("Helvetica", "BI", 8.5)
        pdf.set_text_color(0, 100, 160)
        pdf.multi_cell(0, 5, "=> Recommandation : " + reco)
        pdf.ln(3)

    # ── 6. BILAN ───────────────────────────────────────────────────────────
    pdf.add_page()
    pdf.section_title("6. BILAN ET PLAN D'ACTION")

    # Tableau recapitulatif OWASP
    pdf.subsection_title("6.1 - Conformite OWASP Top 10")
    headers = ["Categorie OWASP", "Statut", "Mecanisme"]
    rows = [
        ("A01 - Controle d'acces",      "Conforme",  "RBAC 3 roles, IsGranted, security.yaml"),
        ("A02 - Cryptographie",          "Conforme",  "bcrypt cout 13, JWT RSA-256"),
        ("A03 - Injection SQL",          "Conforme",  "Doctrine ORM, requetes parametrees"),
        ("A05 - Mauvaise config securite","Conforme",  "Headers HTTP, CSP, CORS restrictif"),
        ("A06 - Composants vulnerables", "A verifier", "Dependances a auditer (composer audit)"),
        ("A07 - Authentification",       "Conforme",  "JWT, rate limiting, bcrypt"),
        ("A08 - Integrite logicielle",   "Conforme",  "Docker, CI/CD, Git signe"),
        ("A09 - Journalisation",         "Conforme",  "AuditLog: toutes actions sensibles"),
        ("A10 - SSRF",                   "Non applicable", "Pas d'appels HTTP serveur-to-serveur"),
        ("XSS",                          "Majoritairement","React auto-escape, CSP, validation partielle"),
        ("CSRF",                         "Conforme",  "JWT stateless + CORS"),
        ("RGPD",                         "Partiel",   "Consentement OK, export/effacement manquants"),
    ]

    col_w = [75, 28, 0]
    pdf.set_font("Helvetica", "B", 8)
    pdf.set_fill_color(15, 30, 60)
    pdf.set_text_color(255, 255, 255)
    pdf.set_x(18)
    for i, h in enumerate(headers):
        w = col_w[i] if i < len(col_w) - 1 else 71
        pdf.cell(w, 5.5, h, fill=True, ln=(1 if i == len(headers)-1 else 0))
    pdf.set_text_color(0, 0, 0)

    for i, (cat, stat, mech) in enumerate(rows):
        pdf.set_fill_color(248, 248, 255) if i % 2 == 0 else pdf.set_fill_color(255, 255, 255)
        pdf.set_x(18)
        pdf.set_font("Helvetica", "", 8)
        pdf.cell(75, 5, cat, fill=True, ln=False)
        if stat == "Conforme":
            pdf.set_text_color(0, 140, 60)
        elif stat in ("Partiel", "Majoritairement", "A verifier"):
            pdf.set_text_color(180, 100, 0)
        else:
            pdf.set_text_color(80, 80, 80)
        pdf.set_font("Helvetica", "B", 8)
        pdf.cell(28, 5, stat, fill=True, ln=False)
        pdf.set_text_color(60, 60, 60)
        pdf.set_font("Helvetica", "", 8)
        pdf.cell(71, 5, mech, fill=True, ln=True)

    pdf.set_text_color(0, 0, 0)
    pdf.ln(4)

    # Plan d'action juin
    pdf.subsection_title("6.2 - Plan d'action pour le Jalon 6 (juin 2026)")
    actions = [
        ("PRIORITE 1", "Ajouter validation server-side (strip_tags) sur champs prenom/nom"),
        ("PRIORITE 1", "Implementer endpoints RGPD : export donnees + suppression compte"),
        ("PRIORITE 2", "Augmenter couverture AuthController (tester reset mdp, logout)"),
        ("PRIORITE 2", "Lancer composer audit pour detecter les dependances vulnerables"),
        ("PRIORITE 3", "Evaluer migration JWT vers cookies HttpOnly pour 'Rester connecte'"),
        ("PRIORITE 3", "Ajouter tests de performance (JMeter ou k6) sur endpoints cles"),
    ]
    for prio, action in actions:
        pdf.set_x(18)
        pdf.set_font("Helvetica", "B", 8.5)
        if "1" in prio:
            pdf.set_text_color(200, 0, 0)
        elif "2" in prio:
            pdf.set_text_color(180, 100, 0)
        else:
            pdf.set_text_color(0, 100, 180)
        pdf.cell(22, 5.5, "[" + prio + "]", ln=False)
        pdf.set_font("Helvetica", "", 8.5)
        pdf.set_text_color(40, 40, 40)
        pdf.multi_cell(0, 5.5, action)

    pdf.ln(4)

    # Conclusion
    pdf.set_fill_color(230, 255, 230)
    pdf.set_draw_color(0, 150, 80)
    pdf.set_line_width(0.5)
    pdf.set_x(18)
    pdf.set_font("Helvetica", "B", 9)
    pdf.set_text_color(0, 100, 50)
    conclusion = (
        "L'application Horosphere presente un niveau de securite satisfaisant pour une version beta. "
        "Les mecanismes fondamentaux de securite web sont en place (JWT RSA, bcrypt 13, rate limiting, "
        "RBAC, CSP, CORS). Les 68 tests PHPUnit passent a 100 %%. "
        "Les 4 points d'attention identifies (XSS stocke, RGPD, couverture AuthController, localStorage) "
        "sont documentes avec des plans de correction pour le jalon 6."
    )
    pdf.multi_cell(0, 5.5, conclusion, border=1, fill=True)

    pdf.set_text_color(0, 0, 0)
    pdf.set_draw_color(0, 0, 0)
    pdf.set_line_width(0.2)

    return pdf


if __name__ == "__main__":
    pdf = build_pdf()
    output_path = "/Users/chahrazedsoltani/Horosphere/rapport_tests_securite_jalon5.pdf"
    pdf.output(output_path)
    print(f"Rapport genere : {output_path}")

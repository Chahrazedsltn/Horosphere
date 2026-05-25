#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Genere le rapport de tests Jalon 4 - Horosphere (caracteres latin-1 uniquement)"""

from fpdf import FPDF

GITHUB_URL   = "https://github.com/Chahrazedsltn/Horosphere"
DATE_RAPPORT = "24 mai 2026"
TICK = "[OK]"
CROSS = "[FAIL]"

# ---------------------------------------------------------------------------
# Donnees des tests
# ---------------------------------------------------------------------------

UNIT_TESTS = [
    ("testCalculerDistanceMemePoint",            "Distance nulle pour un meme point (delta < 0.01 m)"),
    ("testCalculerDistanceParisMarseilleApprox", "Distance Paris->Marseille entre 650 km et 680 km"),
    ("testEstDansZoneVrai",                      "Meme coordonnee que le site -> dans la zone"),
    ("testEstDansZoneFaux",                      "Lyon -> hors zone Paris (rayon 200 m)"),
    ("testEstDansZoneGeofencingDesactive",        "Geofencing desactive -> toujours dans zone"),
    ("testEstDansZoneFrontiereRayon",            "Point a ~180 m -> dans zone (rayon 200 m)"),
    ("testDistanceSymetrique",                   "dist(A,B) = dist(B,A) a 0.01 m pres"),
    ("testCalculerDistancePointsProches",        "~0.001deg lat -> distance entre 50 m et 200 m"),
    ("testValiderCoordonneesValides",            "Coordonnees valides (+-90/+-180) acceptees"),
    ("testValiderCoordonneesInvalides",          "lat>90, lat<-90, lon>180, lon<-180 rejetees"),
]

FUNCTIONAL_TESTS = {
    "PointageController - Auth & Pointages": [
        ("testLoginReturnsToken",          "POST /api/auth/login -> 200 + token JWT"),
        ("testLoginInvalidCredentials",    "Mauvais mot de passe -> 401 Unauthorized"),
        ("testMeReturnUser",               "GET /api/auth/me -> profil agent (prenom, role)"),
        ("testArriverRequiresAuth",        "POST /api/pointages/arriver sans token -> 401"),
        ("testArriverWithValidCoords",     "Pointage arrivee avec coords valides -> 201"),
        ("testArriverMissingCoords",       "Corps vide -> 422 Unprocessable Entity"),
        ("testMesPointagesReturnsList",    "GET /api/pointages/mes-pointages -> liste[]"),
        ("testListePointagesRequiresRh",   "Agent accede /api/pointages -> 403 Forbidden"),
        ("testListePointagesAsRh",         "RH accede /api/pointages -> 200"),
    ],
    "AlerteController - Alertes": [
        ("testMesAlertesRequiresAuth",           "GET /api/alertes sans token -> 401"),
        ("testMesAlertesAsAgent",                "Reponse contient 'data' et 'non_lues'"),
        ("testToutesAlertesRequiresRh",          "Agent accede /api/alertes/toutes -> 403"),
        ("testToutesAlertesAsRh",                "RH accede /api/alertes/toutes -> 200"),
        ("testMarquerLueAsOwner",                "PATCH /lire par le proprietaire -> estLue=true"),
        ("testMarquerLueRefuseAutreUtilisateur", "Agent2 marque alerte agent1 -> 403"),
        ("testMarquerToutLu",                    "PATCH /tout-lire -> toutes alertes lues"),
        ("testMarquerToutLuRequiresAuth",         "PATCH /tout-lire sans token -> 401"),
    ],
    "SiteController - Sites geographiques": [
        ("testListeSitesAsAgent",       "Agent authentifie -> liste >= 3 sites"),
        ("testListeSitesRequiresAuth",  "Sans token -> 401"),
        ("testDetailSite",             "GET /api/sites/{id} -> nom correct"),
        ("testCreerSiteRequiresAdmin", "Agent cree site -> 403"),
        ("testCreerSiteRhForbidden",   "RH cree site -> 403"),
        ("testCreerSiteAsAdmin",       "Admin cree site -> 201 + donnees verifiees"),
        ("testCreerSiteRayonTropPetit","Rayon 5 m -> 422"),
        ("testCreerSiteRayonTropGrand","Rayon 100 000 m -> 422"),
        ("testModifierSiteAsAdmin",    "PATCH admin -> nom mis a jour"),
        ("testSupprimerSiteAsAdmin",   "DELETE admin -> 200"),
    ],
    "DemandeController - Conges & absences": [
        ("testListeDemandesRequiresAuth",   "Sans token -> 401"),
        ("testListeDemandesAsAgent",        "Agent voit uniquement ses demandes"),
        ("testListeDemandesAsRhVoitTout",   "RH voit >= 3 demandes toutes origines"),
        ("testEnAttenteRequiresRh",         "Agent accede /en-attente -> 403"),
        ("testEnAttenteAsRh",              "RH : toutes en statut EN_ATTENTE"),
        ("testCreerDemandeAsAgent",         "Creation demande -> 201, statut EN_ATTENTE"),
        ("testCreerDemandeChampsManquants", "Corps incomplet -> 422"),
        ("testTraiterDemandeApprouveeAsRh", "RH approuve -> statut APPROUVEE"),
        ("testTraiterDemandeRejeteeAsRh",   "RH rejette -> statut REJETEE"),
        ("testTraiterDemandeDecisionInvalide","Decision invalide -> 422"),
        ("testTraiterDemandeRequiresRh",    "Agent traite -> 403"),
    ],
    "UserController - Gestion utilisateurs": [
        ("testListeUsersRequiresAuth",       "Sans token -> 401"),
        ("testListeUsersAsAgentForbidden",   "Agent accede /api/users -> 403"),
        ("testListeUsersAsRh",              "RH -> liste >= 5 utilisateurs"),
        ("testDetailUserAsRh",              "GET /api/users/{id} -> email et prenom corrects"),
        ("testCreerUserRequiresAdmin",       "RH cree utilisateur -> 403"),
        ("testCreerUserChampsManquants",     "Corps incomplet -> 422"),
        ("testCreerUserAsAdmin",            "Admin cree agent -> 201 + role AGENT"),
        ("testModifierUserAsAdmin",         "PATCH admin -> departement mis a jour"),
        ("testSupprimerUserAsAdmin",        "DELETE admin (user temporaire) -> 200"),
        ("testSupprimerPropreCompteForbidden","Admin supprime son propre compte -> 422"),
        ("testStatsDashboardAsAgent",       "Agent accede dashboard stats -> 403"),
        ("testStatsDashboardAsRh",          "RH stats : cles total, presents, demandes, taux"),
    ],
    "DocumentController - Exports & telechargements": [
        ("testListeDocumentsRequiresAuth",             "Sans token -> 401"),
        ("testListeDocumentsAsAgent",                  "GET /api/documents -> tableau 'data'"),
        ("testExportCsvRequiresRh",                    "Agent exporte CSV -> 403"),
        ("testExportCsvDatesManquantes",               "Corps sans dates -> 422"),
        ("testExportCsvAsRh",                          "RH exporte CSV -> 201 + typeDocument=CSV"),
        ("testExportCsvAvecUtilisateurCible",          "Export CSV pour agent cible -> 201"),
        ("testTelechargerDocumentAsOwner",             "Telechargement proprietaire -> 200 text/*"),
        ("testTelechargerDocumentAutreUtilisateurForbidden","Agent telecharge doc RH -> 403"),
    ],
}

SECURITY_TESTS = [
    ("401 - Non authentifie",  "testListeDemandesRequiresAuth",             "Demandes sans token"),
    ("401 - Non authentifie",  "testListeSitesRequiresAuth",                "Sites sans token"),
    ("401 - Non authentifie",  "testArriverRequiresAuth",                   "Pointage sans token"),
    ("401 - Non authentifie",  "testMesAlertesRequiresAuth",                "Alertes sans token"),
    ("401 - Non authentifie",  "testListeDocumentsRequiresAuth",            "Documents sans token"),
    ("401 - Non authentifie",  "testListeUsersRequiresAuth",                "Users sans token"),
    ("401 - Non authentifie",  "testMarquerToutLuRequiresAuth",             "Tout-lire sans token"),
    ("403 - Role insuffisant", "testEnAttenteRequiresRh",                  "Agent accede /en-attente"),
    ("403 - Role insuffisant", "testTraiterDemandeRequiresRh",             "Agent traite une demande"),
    ("403 - Role insuffisant", "testToutesAlertesRequiresRh",              "Agent accede /toutes alertes"),
    ("403 - Role insuffisant", "testMarquerLueRefuseAutreUtilisateur",     "Agent lit alerte d'un autre"),
    ("403 - Role insuffisant", "testCreerSiteRequiresAdmin",               "Agent cree un site"),
    ("403 - Role insuffisant", "testCreerSiteRhForbidden",                 "RH cree un site"),
    ("403 - Role insuffisant", "testExportCsvRequiresRh",                  "Agent exporte CSV"),
    ("403 - Role insuffisant", "testListeUsersAsAgentForbidden",           "Agent accede /users"),
    ("403 - Role insuffisant", "testCreerUserRequiresAdmin",               "RH cree un user"),
    ("403 - Role insuffisant", "testStatsDashboardAsAgent",                "Agent accede dashboard stats"),
    ("403 - Role insuffisant", "testTelechargerDocumentAutreUtilisateurForbidden","Agent telecharge doc RH"),
    ("422 - Auto-suppression", "testSupprimerPropreCompteForbidden",       "Admin supprime son propre compte"),
]


def count_all():
    u = len(UNIT_TESTS)
    f = sum(len(v) for v in FUNCTIONAL_TESTS.values())
    return u, f, u + f


# ---------------------------------------------------------------------------

class PDF(FPDF):
    def __init__(self):
        super().__init__()
        self.set_auto_page_break(auto=True, margin=20)

    def header(self):
        self.set_font("Helvetica", "B", 9)
        self.set_text_color(120, 120, 120)
        self.cell(0, 8, "Horosphere - Rapport de Tests - Jalon 4", align="L")
        self.ln(4)
        self.set_draw_color(200, 200, 200)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(4)

    def footer(self):
        self.set_y(-15)
        self.set_font("Helvetica", "", 8)
        self.set_text_color(150, 150, 150)
        self.cell(0, 10, "Page %d  -  %s" % (self.page_no(), GITHUB_URL), align="C")

    def h1(self, txt):
        self.set_font("Helvetica", "B", 16)
        self.set_text_color(30, 58, 138)
        self.ln(4)
        self.cell(0, 10, txt, ln=True)
        self.set_draw_color(30, 58, 138)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(5)
        self.set_text_color(0)

    def h2(self, txt):
        self.set_font("Helvetica", "B", 12)
        self.set_fill_color(239, 246, 255)
        self.set_text_color(30, 58, 138)
        self.cell(0, 8, "  " + txt, ln=True, fill=True)
        self.ln(2)
        self.set_text_color(0)

    def body(self, txt, indent=0):
        self.set_font("Helvetica", "", 10)
        self.set_x(10 + indent)
        self.multi_cell(0, 6, txt)

    def kv(self, key, val):
        self.set_font("Helvetica", "B", 10)
        self.set_x(14)
        self.cell(55, 6, key + " :", ln=False)
        self.set_font("Helvetica", "", 10)
        self.cell(0, 6, val, ln=True)

    def test_row(self, name, desc, passed=True):
        color = (22, 163, 74) if passed else (220, 38, 38)
        icon  = TICK if passed else CROSS
        self.set_font("Courier", "", 8)
        self.set_text_color(*color)
        self.set_x(14)
        self.cell(12, 5.5, icon, ln=False)
        self.set_text_color(30, 30, 30)
        self.cell(82, 5.5, name[:58], ln=False)
        self.set_font("Helvetica", "", 8.5)
        self.set_text_color(75, 85, 99)
        self.cell(0, 5.5, desc, ln=True)
        self.set_text_color(0)

    def summary_box(self, total, passed, failed):
        y = self.get_y() + 4
        self.set_y(y)
        if failed == 0:
            self.set_fill_color(240, 253, 244)
            bc = (22, 163, 74)
        else:
            self.set_fill_color(254, 242, 242)
            bc = (220, 38, 38)
        self.set_draw_color(*bc)
        self.set_line_width(0.5)
        self.rect(10, y, 190, 22, style="FD")
        self.set_line_width(0.2)
        self.set_xy(14, y + 4)
        self.set_font("Helvetica", "B", 12)
        self.set_text_color(*bc)
        status = "TOUS LES TESTS PASSENT" if failed == 0 else "%d TEST(S) EN ECHEC" % failed
        self.cell(0, 7, status, ln=True)
        self.set_xy(14, y + 12)
        self.set_font("Helvetica", "", 10)
        self.set_text_color(55, 65, 81)
        self.cell(0, 6, "Total : %d   Reussis : %d   Echoues : %d" % (total, passed, failed), ln=True)
        self.set_text_color(0)
        self.ln(8)

    def table_header(self, headers, widths):
        self.set_font("Helvetica", "B", 9)
        self.set_fill_color(30, 58, 138)
        self.set_text_color(255, 255, 255)
        self.set_x(10)
        for h, w in zip(headers, widths):
            self.cell(w, 7, h, border=1, fill=True)
        self.ln()
        self.set_text_color(0)

    def table_row(self, cells, widths, alt=False):
        self.set_fill_color(245, 250, 255) if alt else self.set_fill_color(255, 255, 255)
        self.set_font("Helvetica", "", 9)
        self.set_x(10)
        for c, w in zip(cells, widths):
            self.cell(w, 6, str(c), border=1, fill=True)
        self.ln()


# ---------------------------------------------------------------------------

def build_pdf():
    pdf = PDF()
    unit_count, func_count, total = count_all()

    # ── PAGE DE GARDE ───────────────────────────────────────────────────────
    pdf.add_page()
    pdf.set_fill_color(30, 58, 138)
    pdf.rect(0, 0, 210, 55, "F")
    pdf.set_xy(10, 12)
    pdf.set_font("Helvetica", "B", 26)
    pdf.set_text_color(255, 255, 255)
    pdf.cell(0, 12, "HOROSPHERE", ln=True)
    pdf.set_xy(10, 28)
    pdf.set_font("Helvetica", "", 14)
    pdf.cell(0, 8, "Rapport de Tests - Jalon 4", ln=True)
    pdf.set_xy(10, 40)
    pdf.set_font("Helvetica", "", 10)
    pdf.cell(0, 8, "Genere le %s" % DATE_RAPPORT, ln=True)
    pdf.set_text_color(0)
    pdf.ln(20)

    pdf.h2("Informations du projet")
    pdf.ln(2)
    pdf.kv("Projet",         "Horosphere - Gestion RH & pointages geolocali ses")
    pdf.kv("Depot GitHub",   GITHUB_URL)
    pdf.kv("Branche",        "main  (dernier commit : 65d5099)")
    pdf.kv("Date du rapport",DATE_RAPPORT)
    pdf.kv("Framework",      "Symfony 7 / PHPUnit 11")
    pdf.kv("Environnement",  "GitHub Actions CI - PHP 8.3 + MySQL 8.0")
    pdf.ln(4)

    pdf.h2("Resume global")
    pdf.ln(2)

    rows = [
        ("Tests Unitaires",    "GeofencingServiceTest",   unit_count, "PASS"),
        ("Tests Fonctionnels", "PointageControllerTest",  9,          "PASS"),
        ("Tests Fonctionnels", "AlerteControllerTest",    8,          "PASS"),
        ("Tests Fonctionnels", "SiteControllerTest",      10,         "PASS"),
        ("Tests Fonctionnels", "DemandeControllerTest",   11,         "PASS"),
        ("Tests Fonctionnels", "UserControllerTest",      12,         "PASS"),
        ("Tests Fonctionnels", "DocumentControllerTest",  8,          "PASS"),
    ]
    cw = [52, 72, 22, 38]
    pdf.table_header(["Categorie", "Fichier", "Tests", "Resultat"], cw)
    for i, (cat, f, n, res) in enumerate(rows):
        pdf.set_fill_color(245, 250, 255) if i % 2 else pdf.set_fill_color(255, 255, 255)
        pdf.set_font("Helvetica", "", 9)
        pdf.set_x(10)
        pdf.cell(cw[0], 6, cat, border=1, fill=True)
        pdf.cell(cw[1], 6, f, border=1, fill=True)
        pdf.cell(cw[2], 6, str(n), border=1, align="C", fill=True)
        pdf.set_font("Helvetica", "B", 9)
        pdf.set_text_color(22, 163, 74)
        pdf.cell(cw[3], 6, res, border=1, align="C", fill=True)
        pdf.set_text_color(0)
        pdf.ln()

    # Ligne total
    pdf.set_font("Helvetica", "B", 9)
    pdf.set_fill_color(30, 58, 138)
    pdf.set_text_color(255, 255, 255)
    pdf.set_x(10)
    pdf.cell(cw[0] + cw[1], 7, "TOTAL", border=1, fill=True)
    pdf.cell(cw[2], 7, str(total), border=1, align="C", fill=True)
    pdf.set_text_color(255, 255, 0)
    pdf.cell(cw[3], 7, "68 / 68", border=1, align="C", fill=True)
    pdf.set_text_color(0)
    pdf.ln()
    pdf.summary_box(total, total, 0)

    # ── PAGE 2 - INTRODUCTION ───────────────────────────────────────────────
    pdf.add_page()
    pdf.h1("1. Introduction")

    pdf.body(
        "Horosphere est une application web full-stack de gestion des ressources humaines "
        "dediee au suivi des pointages geolocali ses, a la gestion des demandes de conges, "
        "aux alertes RH et a l'export de rapports. Elle repose sur une architecture "
        "decouple : backend API REST (Symfony 7 + JWT) et frontend SPA (React + Vite)."
    )
    pdf.ln(3)
    pdf.body(
        "Ce rapport couvre l'integralite de la strategie de tests mise en place dans "
        "le cadre du Jalon 4. Il documente :"
    )
    pdf.body("  - Les tests unitaires de la couche Service (GeofencingService)", indent=4)
    pdf.body("  - Les tests fonctionnels (HTTP end-to-end) des 6 controllers backend", indent=4)
    pdf.body("  - Les resultats d'execution en integration continue (GitHub Actions)", indent=4)
    pdf.body("  - La couverture des scenarios de securite (controle d'acces par role)", indent=4)
    pdf.ln(4)

    pdf.h2("Objectifs de la strategie de tests")
    pdf.ln(2)
    goals = [
        ("Fiabilite metier",       "Verifier que chaque regle metier (pointage, demande, alerte) se comporte comme attendu."),
        ("Securite RBAC",          "S'assurer que les routes protegees renvoient 401/403 pour les roles non autorises."),
        ("Validation des donnees", "Controler que les champs manquants ou invalides retournent 422."),
        ("Non-regression",         "Tout commit sur main declenche automatiquement la suite de tests via GitHub Actions CI."),
        ("Isolation des tests",    "Chaque test est independant (kernel shutdown entre clients, helpers creerDemande)."),
    ]
    for title, desc in goals:
        pdf.set_font("Helvetica", "B", 10)
        pdf.set_x(14)
        pdf.cell(52, 6, title, ln=False)
        pdf.set_font("Helvetica", "", 10)
        pdf.multi_cell(0, 6, desc)
    pdf.ln(3)

    pdf.h2("Architecture technique des tests")
    pdf.ln(2)
    pdf.body("Les tests sont organises en deux categories dans api/tests/ :")
    pdf.body("  - tests/Unit/Service/          Tests unitaires (PHPUnit pur, sans BDD)", indent=4)
    pdf.body("  - tests/Functional/Controller/ Tests fonctionnels (WebTestCase, MySQL CI)", indent=4)
    pdf.ln(3)
    pdf.body(
        "Les tests fonctionnels utilisent Symfony WebTestCase qui monte un kernel complet "
        "et execute de vraies requetes HTTP internes. L'authentification est simulee via "
        "l'endpoint POST /api/auth/login qui retourne un token JWT reel. Les fixtures "
        "(DataFixtures) pre-chargent des utilisateurs avec des roles AGENT, RH et ADMIN "
        "ainsi que des donnees de demo pour sites, alertes et demandes."
    )
    pdf.ln(3)

    pdf.h2("Integration Continue - GitHub Actions")
    pdf.ln(2)
    pdf.body(
        "Un workflow .github/workflows/ci.yml declenche automatiquement les trois jobs "
        "suivants a chaque push sur main :"
    )
    pdf.body("  - Backend Tests (PHPUnit)  : PHP 8.3, MySQL 8.0, migrations + fixtures", indent=4)
    pdf.body("  - Frontend Build & Tests   : Node 20, Vite, Vitest", indent=4)
    pdf.body("  - Docker Build             : Construction image production via docker buildx", indent=4)
    pdf.ln(3)
    pdf.body("Dernier run CI valide : run #26347540647  [OK]  68/68 tests - 0 echec")
    pdf.ln(2)
    pdf.body("Depot   : " + GITHUB_URL)
    pdf.body("CI      : " + GITHUB_URL + "/actions")

    # ── PAGE 3 - TESTS UNITAIRES ─────────────────────────────────────────────
    pdf.add_page()
    pdf.h1("2. Tests Unitaires")

    pdf.h2("GeofencingServiceTest  (%d tests)" % len(UNIT_TESTS))
    pdf.ln(2)
    pdf.body(
        "Le service GeofencingService calcule des distances GPS par la formule de Haversine "
        "et determine si un agent se trouve dans le perimetre geographique d'un site. "
        "Ces tests sont purement unitaires : SiteRepository est mocke, aucune base de "
        "donnees n'est sollicitee."
    )
    pdf.ln(3)
    for name, desc in UNIT_TESTS:
        pdf.test_row(name, desc)
    pdf.ln(4)
    pdf.h2("Resultat")
    pdf.ln(2)
    pdf.body("[OK]  10 / 10 tests unitaires reussis")

    # ── PAGE 4+ - TESTS FONCTIONNELS ────────────────────────────────────────
    pdf.add_page()
    pdf.h1("3. Tests Fonctionnels")
    pdf.body(
        "Les tests fonctionnels couvrent 6 controllers et 58 scenarios. Chaque test "
        "effectue une vraie requete HTTP (kernel Symfony complet) et verifie le code HTTP "
        "retourne ainsi que la structure JSON de la reponse."
    )
    pdf.ln(5)

    for num, (controller, tests) in enumerate(FUNCTIONAL_TESTS.items(), start=1):
        pdf.h2("3.%d  %s  (%d tests)" % (num, controller, len(tests)))
        pdf.ln(1)
        for name, desc in tests:
            pdf.test_row(name, desc)
        pdf.ln(3)

    # ── PAGE - RESULTATS CI ──────────────────────────────────────────────────
    pdf.add_page()
    pdf.h1("4. Resultats d'execution CI")

    pdf.h2("Dernier run GitHub Actions - run #26347540647")
    pdf.ln(2)
    pdf.kv("Date",         DATE_RAPPORT + " - 02:34 UTC")
    pdf.kv("Branche",      "main (commit 65d5099)")
    pdf.kv("Declencheur",  "git push - fix(tests): assouplir assertion Content-Type CSV")
    pdf.ln(4)

    jobs = [
        ("Backend Tests (PHPUnit)", "68 / 68", "SUCCESS", "1m 30s"),
        ("Frontend Build & Tests",  "Vitest OK", "SUCCESS", "28s"),
        ("Docker Build",            "Image construite", "SUCCESS", "52s"),
    ]
    jw = [72, 45, 38, 29]
    pdf.table_header(["Job", "Resultat", "Statut", "Duree"], jw)
    for job, res, status, dur in jobs:
        pdf.set_font("Helvetica", "", 9)
        pdf.set_fill_color(240, 253, 244)
        pdf.set_x(10)
        pdf.cell(jw[0], 6, job, border=1, fill=True)
        pdf.cell(jw[1], 6, res, border=1, fill=True)
        pdf.set_font("Helvetica", "B", 9)
        pdf.set_text_color(22, 163, 74)
        pdf.cell(jw[2], 6, status, border=1, align="C", fill=True)
        pdf.set_text_color(0)
        pdf.set_font("Helvetica", "", 9)
        pdf.cell(jw[3], 6, dur, border=1, align="C", fill=True)
        pdf.ln()

    pdf.summary_box(68, 68, 0)

    pdf.h2("Historique des runs")
    pdf.ln(2)
    runs = [
        ("26347540647", DATE_RAPPORT + " 02:34", "SUCCESS", "Fix Content-Type assertion"),
        ("26347508602", DATE_RAPPORT + " 00:31", "FAILURE", "Content-Type text/plain vs text/csv"),
        ("26347325935", DATE_RAPPORT + " 00:20", "FAILURE", "Auth manquante, adresse requise, isolation"),
        ("26347130414", DATE_RAPPORT + " 00:09", "SUCCESS", "CI stable avant ajout tests controllers"),
        ("26346975933", DATE_RAPPORT + " 00:01", "SUCCESS", "Fixes securite P0/P1/P2"),
    ]
    rw = [38, 48, 28, 70]
    pdf.table_header(["Run ID", "Date", "Statut", "Note"], rw)
    for i, (rid, rdate, rstatus, rnote) in enumerate(runs):
        alt = (i % 2 == 1)
        pdf.set_fill_color(245, 250, 255) if alt else pdf.set_fill_color(255, 255, 255)
        pdf.set_font("Helvetica", "", 8.5)
        pdf.set_x(10)
        pdf.cell(rw[0], 5.5, "#" + rid, border=1, fill=True)
        pdf.cell(rw[1], 5.5, rdate, border=1, fill=True)
        if rstatus == "SUCCESS":
            pdf.set_text_color(22, 163, 74)
        else:
            pdf.set_text_color(220, 38, 38)
        pdf.set_font("Helvetica", "B", 8.5)
        pdf.cell(rw[2], 5.5, rstatus, border=1, align="C", fill=True)
        pdf.set_text_color(55, 65, 81)
        pdf.set_font("Helvetica", "", 8.5)
        pdf.cell(rw[3], 5.5, rnote, border=1, fill=True)
        pdf.ln()
        pdf.set_text_color(0)

    # ── PAGE - COUVERTURE SECURITE ───────────────────────────────────────────
    pdf.add_page()
    pdf.h1("5. Couverture securite - Controle d'acces RBAC")

    pdf.body(
        "Une part importante des tests verifie les regles de controle d'acces "
        "base sur les roles (RBAC). Le tableau ci-dessous recense les 19 tests "
        "dedies a la securite (401 / 403 / 422)."
    )
    pdf.ln(4)

    sw = [35, 88, 61]
    pdf.table_header(["Code", "Nom du test", "Scenario"], sw)
    for i, (code, tname, scenario) in enumerate(SECURITY_TESTS):
        alt = (i % 2 == 1)
        pdf.set_fill_color(245, 250, 255) if alt else pdf.set_fill_color(255, 255, 255)
        pdf.set_x(10)
        if "401" in code:
            pdf.set_text_color(180, 90, 0)
        elif "403" in code:
            pdf.set_text_color(220, 38, 38)
        else:
            pdf.set_text_color(107, 33, 168)
        pdf.set_font("Helvetica", "", 8.5)
        pdf.cell(sw[0], 5.5, code, border=1, fill=True)
        pdf.set_text_color(30, 30, 30)
        pdf.set_font("Courier", "", 8)
        pdf.cell(sw[1], 5.5, tname[:52], border=1, fill=True)
        pdf.set_font("Helvetica", "", 8.5)
        pdf.cell(sw[2], 5.5, scenario, border=1, fill=True)
        pdf.ln()
        pdf.set_text_color(0)

    pdf.ln(6)
    pdf.body("[OK]  19 / 19 tests de securite reussis - aucune regression RBAC detectee.")

    # ── PAGE - CONCLUSION ────────────────────────────────────────────────────
    pdf.add_page()
    pdf.h1("6. Conclusion")

    pdf.body(
        "La suite de tests du Jalon 4 couvre l'ensemble des fonctionnalites critiques "
        "de l'API Horosphere : authentification JWT, pointages geolocali ses, gestion des "
        "demandes de conges, alertes RH, gestion des utilisateurs, sites geographiques "
        "et exports de documents."
    )
    pdf.ln(4)

    points = [
        ("68 tests au total",        "10 unitaires + 58 fonctionnels, tous verts en CI."),
        ("Couverture RBAC complete",  "19 tests dedies a la securite (401, 403, 422) pour les 3 roles."),
        ("Pipeline CI automatise",   "Chaque push sur main declenche la suite complete via GitHub Actions."),
        ("Isolation garantie",        "ensureKernelShutdown() entre chaque client, helpers creerDemande() pour eviter les dependances entre tests."),
        ("Zero dette de test",        "Tous les controllers ont leur test fonctionnel ; GeofencingService a ses tests unitaires Haversine."),
    ]
    for title, desc in points:
        pdf.set_font("Helvetica", "B", 10)
        pdf.set_text_color(30, 58, 138)
        pdf.set_x(14)
        pdf.cell(60, 6, "- " + title, ln=False)
        pdf.set_font("Helvetica", "", 10)
        pdf.set_text_color(55, 65, 81)
        pdf.multi_cell(0, 6, desc)
        pdf.ln(1)
    pdf.set_text_color(0)

    pdf.ln(6)
    pdf.h2("Lien de livraison")
    pdf.ln(2)
    pdf.set_font("Helvetica", "B", 10)
    pdf.set_x(14)
    pdf.cell(0, 7, "Depot GitHub  : " + GITHUB_URL, ln=True)
    pdf.set_font("Helvetica", "", 10)
    pdf.set_x(14)
    pdf.cell(0, 7, "CI / Actions  : " + GITHUB_URL + "/actions", ln=True)
    pdf.set_x(14)
    pdf.cell(0, 7, "Branche livree : main  -  commit 65d5099", ln=True)

    out = "/Users/chahrazedsoltani/Horosphere/rapport_tests_jalon4.pdf"
    pdf.output(out)
    print("PDF genere : " + out)
    u, f, t = count_all()
    print("Recapitulatif : %d tests unitaires + %d tests fonctionnels = %d total" % (u, f, t))


build_pdf()

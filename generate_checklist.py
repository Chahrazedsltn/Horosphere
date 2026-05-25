#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Genere la checklist de tests manuels - Horosphere"""

from fpdf import FPDF

DATE = "25 mai 2026"
GITHUB_URL = "https://github.com/Chahrazedsltn/Horosphere"

SECTIONS = [
    ("Pre-requis", [
        "docker compose up -d - tous les conteneurs demarrent sans erreur",
        "docker compose exec api php bin/console doctrine:fixtures:load - fixtures chargees",
        "Frontend accessible (port configure)",
        "API accessible sur /api",
    ]),
    ("1. Authentification", [
        "1.1  Login credentials valides (agent) -> token JWT, redirection dashboard",
        "1.2  Login credentials valides (RH) -> dashboard RH affiche",
        "1.3  Login credentials valides (admin) -> dashboard Admin affiche",
        "1.4  Login mauvais mot de passe -> message erreur, pas de token",
        "1.5  Login email inexistant -> message erreur generique",
        "1.6  Mot de passe oublie - email valide -> email de reset envoye",
        "1.7  Mot de passe oublie - email invalide -> message erreur",
        "1.8  Acces page protegee sans token -> redirection login",
        "1.9  Token expire -> refresh automatique ou redirection login",
        "1.10 Deconnexion -> token supprime, redirection login",
    ]),
    ("2. Pointage (AGENT)", [
        "2.1  Affichage widget pointage sur dashboard",
        "2.2  Pointer entree dans la zone geographique du site -> pointage cree",
        "2.3  Pointer entree hors zone geographique -> erreur geofencing",
        "2.4  Pointer sortie apres une entree -> duree calculee, pointage cloture",
        "2.5  Tenter une 2e entree sans avoir pointe sortie -> erreur ou avertissement",
        "2.6  Pointage ouvert depuis plus de 10h -> alerte generee",
        "2.7  Historique des pointages -> liste triee, dates/heures correctes",
        "2.8  Coordonnees GPS non disponibles -> message explicatif",
    ]),
    ("3. Demandes (AGENT)", [
        "3.1  Creer une demande de conge avec dates valides -> statut EN_ATTENTE",
        "3.2  Creer une demande de correction de pointage -> creee avec reference",
        "3.3  Creer une demande d'absence -> creee",
        "3.4  Demande avec date fin < date debut -> erreur de validation",
        "3.5  Consulter la liste de ses demandes -> toutes les demandes de l'agent",
        "3.6  Demande approuvee visible -> statut APPROUVEE affiche",
        "3.7  Demande refusee avec motif -> statut REJETEE + motif visible",
        "3.8  Annuler une demande en attente -> demande supprimee ou annulee",
    ]),
    ("4. Validation (RH)", [
        "4.1  Voir toutes les demandes en attente -> liste complete",
        "4.2  Approuver une demande de conge -> statut APPROUVEE",
        "4.3  Refuser une demande avec motif -> statut REJETEE, motif enregistre",
        "4.4  Filtrer demandes par type -> resultats filtres correctement",
        "4.5  Filtrer demandes par statut -> resultats filtres correctement",
        "4.6  Voir les alertes actives -> liste affichee",
        "4.7  Marquer une alerte comme traitee -> disparait de la liste active",
    ]),
    ("5. Exports / Documents (RH)", [
        "5.1  Generer export CSV des pointages -> fichier CSV telecharge",
        "5.2  Generer export PDF des pointages -> fichier PDF telecharge",
        "5.3  Export filtre par periode -> donnees dans la periode uniquement",
        "5.4  Export filtre par employe -> donnees de l'employe uniquement",
        "5.5  Historique des documents generes -> liste des exports precedents",
    ]),
    ("6. Sites (ADMIN)", [
        "6.1  Lister tous les sites -> liste complete affichee",
        "6.2  Creer un site avec coordonnees valides -> site cree",
        "6.3  Creer un site sans adresse -> erreur de validation",
        "6.4  Creer un site avec coordonnees GPS invalides -> erreur",
        "6.5  Modifier le rayon de geofencing d'un site -> rayon mis a jour",
        "6.6  Supprimer un site sans pointages actifs -> site supprime",
        "6.7  Supprimer un site avec pointages existants -> erreur ou confirmation",
    ]),
    ("7. Utilisateurs (ADMIN)", [
        "7.1  Lister tous les utilisateurs -> liste avec roles affichee",
        "7.2  Creer un utilisateur AGENT -> compte cree, email unique",
        "7.3  Creer un utilisateur RH -> compte cree avec role RH",
        "7.4  Creer avec email deja existant -> erreur de duplication",
        "7.5  Creer avec mot de passe trop simple -> erreur de complexite",
        "7.6  Modifier le role d'un utilisateur -> role mis a jour",
        "7.7  Desactiver un compte -> utilisateur ne peut plus se connecter",
        "7.8  Supprimer un utilisateur -> compte supprime",
    ]),
    ("8. Controle d'acces (permissions)", [
        "8.1  Agent accede /admin/* -> 403 Forbidden",
        "8.2  Agent accede a la validation RH -> 403 Forbidden",
        "8.3  RH accede a la gestion des sites -> 403 Forbidden",
        "8.4  RH accede aux demandes d'un autre agent -> 403 ou donnees filtrees",
        "8.5  Agent voit uniquement ses propres pointages -> pas de fuite",
        "8.6  Appel API sans token -> 401 Unauthorized",
    ]),
    ("9. Tests automatises (a executer)", [
        "9.1  docker compose exec api php bin/phpunit --testdox -> 0 failures",
        "9.2  GeofencingServiceTest -> calculs Haversine corrects",
        "9.3  PointageControllerTest -> tous endpoints OK",
        "9.4  DemandeControllerTest -> workflow complet OK",
        "9.5  Vitest smoke test PointageWidget -> composant rendu sans crash",
    ]),
    ("10. Robustesse", [
        "10.1 Soumettre un formulaire vide -> validation client ET serveur",
        "10.2 Injection SQL dans les champs texte -> pas d'erreur serveur",
        "10.3 Token JWT modifie manuellement -> 401 Unauthorized",
        "10.4 Requetes en rafale sur /mot-de-passe-oublie -> rate limiting 429",
        "10.5 Arreter/redemarrer Docker -> reprise sans perte de donnees",
    ]),
]


class PDF(FPDF):
    def __init__(self):
        super().__init__()
        self.set_auto_page_break(auto=True, margin=20)

    def header(self):
        self.set_font("Helvetica", "B", 9)
        self.set_text_color(120, 120, 120)
        self.cell(0, 8, "Horosphere - Checklist de tests manuels", align="L")
        self.ln(4)
        self.set_draw_color(200, 200, 200)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(4)

    def footer(self):
        self.set_y(-15)
        self.set_font("Helvetica", "", 8)
        self.set_text_color(150, 150, 150)
        self.cell(0, 10, "Page %d  -  %s" % (self.page_no(), GITHUB_URL), align="C")

    def cover(self):
        self.add_page()
        self.set_fill_color(30, 58, 138)
        self.rect(0, 0, 210, 60, "F")
        self.set_xy(10, 14)
        self.set_font("Helvetica", "B", 28)
        self.set_text_color(255, 255, 255)
        self.cell(0, 12, "HOROSPHERE", ln=True)
        self.set_xy(10, 32)
        self.set_font("Helvetica", "", 15)
        self.cell(0, 8, "Checklist de tests manuels", ln=True)
        self.set_xy(10, 44)
        self.set_font("Helvetica", "", 10)
        self.cell(0, 8, "Generee le %s" % DATE, ln=True)
        self.set_text_color(0)
        self.ln(22)

        # intro
        self.set_font("Helvetica", "", 10)
        self.set_text_color(55, 65, 81)
        self.multi_cell(0, 6,
            "Ce document liste l'ensemble des tests a effectuer pour valider le bon "
            "fonctionnement de l'application Horosphere (gestion RH & pointages "
            "geolocali ses). Il couvre les modules : authentification, pointage, "
            "demandes, validation RH, exports, gestion des sites et des utilisateurs, "
            "controle d'acces RBAC et robustesse."
        )
        self.set_text_color(0)
        self.ln(6)

        # comptage
        total = sum(len(items) for _, items in SECTIONS)
        self.set_font("Helvetica", "B", 11)
        self.set_text_color(30, 58, 138)
        self.cell(0, 8, "Total : %d tests  |  %d sections" % (total, len(SECTIONS)), ln=True)
        self.set_text_color(0)
        self.ln(4)
        self.set_font("Helvetica", "", 9)
        self.set_text_color(100, 100, 100)
        self.cell(0, 6, "Ordre recommande : tests automatises (section 9) -> AGENT -> RH -> ADMIN", ln=True)
        self.set_text_color(0)

    def section(self, title, items):
        # titre section
        self.set_font("Helvetica", "B", 11)
        self.set_fill_color(30, 58, 138)
        self.set_text_color(255, 255, 255)
        self.ln(3)
        self.cell(0, 8, "  " + title, ln=True, fill=True)
        self.set_text_color(0)
        self.ln(1)

        for i, item in enumerate(items):
            alt = (i % 2 == 0)
            self.set_fill_color(245, 249, 255) if alt else self.set_fill_color(255, 255, 255)

            y = self.get_y()
            x = self.get_x()

            # case a cocher
            self.set_draw_color(100, 100, 200)
            self.set_line_width(0.4)
            self.rect(12, y + 1, 4.5, 4.5)
            self.set_line_width(0.2)

            # texte
            self.set_font("Helvetica", "", 9)
            self.set_text_color(30, 30, 30)
            self.set_x(20)
            self.set_fill_color(245, 249, 255) if alt else self.set_fill_color(255, 255, 255)
            self.multi_cell(0, 6, item, fill=True)

        self.ln(2)


def build():
    pdf = PDF()
    pdf.cover()
    pdf.add_page()

    for title, items in SECTIONS:
        # anticiper saut de page si peu de place
        if pdf.get_y() > 240:
            pdf.add_page()
        pdf.section(title, items)

    out = "/Users/chahrazedsoltani/Horosphere/checklist_tests_horosphere.pdf"
    pdf.output(out)
    total = sum(len(items) for _, items in SECTIONS)
    print("PDF genere : " + out)
    print("Total : %d tests dans %d sections" % (total, len(SECTIONS)))


build()

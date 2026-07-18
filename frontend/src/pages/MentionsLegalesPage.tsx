import React from 'react'
import { useNavigate } from 'react-router-dom'
import { ArrowLeft } from '@phosphor-icons/react'

export default function MentionsLegalesPage() {
  const navigate = useNavigate()

  return (
    <div className="min-h-screen bg-bg py-10 px-6">
      <div className="max-w-3xl mx-auto">
        <button
          onClick={() => navigate(-1)}
          className="flex items-center gap-2 text-[13px] text-text3 hover:text-text2 transition-colors mb-6"
        >
          <ArrowLeft size={14} /> Retour
        </button>

        <h1 className="text-[22px] font-bold text-text tracking-tight mb-2">Mentions legales et politique de confidentialite</h1>
        <p className="text-[12px] text-text3 mb-8">Derniere mise a jour : juillet 2026</p>

        <div className="bg-surface border border-border rounded-2xl p-8 space-y-6 text-[13px] text-text2 leading-relaxed">

          <section>
            <h2 className="text-[15px] font-bold text-text mb-2">1. Editeur de l'application</h2>
            <p>
              Horosphere — Application de gestion des presences par geofencing GPS.<br />
              Projet realise dans le cadre de la formation CDA (Concepteur Developpeur d'Applications).<br />
              Responsable : Chahrazed Soltani.
            </p>
          </section>

          <section>
            <h2 className="text-[15px] font-bold text-text mb-2">2. Hebergement</h2>
            <p>
              L'application est hebergee sur une infrastructure Docker conteneurisee.<br />
              Les donnees sont stockees dans une base MariaDB securisee.
            </p>
          </section>

          <section>
            <h2 className="text-[15px] font-bold text-text mb-2">3. Donnees personnelles collectees</h2>
            <p>Dans le cadre de son fonctionnement, Horosphere collecte les donnees suivantes :</p>
            <ul className="list-disc pl-5 mt-2 space-y-1">
              <li>Identite : nom, prenom, adresse e-mail professionnelle</li>
              <li>Donnees de pointage : heures d'arrivee et de depart, coordonnees GPS</li>
              <li>Demandes : conges, absences, corrections avec justificatifs eventuels</li>
              <li>Donnees techniques : logs de connexion, adresse IP (pour la securite)</li>
            </ul>
          </section>

          <section>
            <h2 className="text-[15px] font-bold text-text mb-2">4. Finalite du traitement</h2>
            <p>Les donnees sont collectees exclusivement pour :</p>
            <ul className="list-disc pl-5 mt-2 space-y-1">
              <li>La gestion des presences et du temps de travail</li>
              <li>La verification de la localisation via geofencing GPS</li>
              <li>La gestion des demandes de conges et absences</li>
              <li>La generation de rapports RH</li>
              <li>La securite de l'application (audit, anti-bruteforce)</li>
            </ul>
          </section>

          <section>
            <h2 className="text-[15px] font-bold text-text mb-2">5. Base legale</h2>
            <p>
              Le traitement des donnees repose sur l'execution du contrat de travail (article 6.1.b du RGPD)
              et l'interet legitime de l'employeur pour la gestion du temps de travail (article 6.1.f).
            </p>
          </section>

          <section>
            <h2 className="text-[15px] font-bold text-text mb-2">6. Duree de conservation</h2>
            <p>
              Les donnees de pointage sont conservees pendant la duree du contrat de travail et 5 ans apres
              la fin de celui-ci, conformement aux obligations legales. Les logs d'audit sont conserves 1 an.
              Les sauvegardes de base de donnees sont conservees 7 jours.
            </p>
          </section>

          <section>
            <h2 className="text-[15px] font-bold text-text mb-2">7. Droits des utilisateurs (RGPD)</h2>
            <p>Conformement au Reglement General sur la Protection des Donnees (UE 2016/679), vous disposez des droits suivants :</p>
            <ul className="list-disc pl-5 mt-2 space-y-1">
              <li><strong>Droit d'acces</strong> : consulter vos donnees via votre profil et le dashboard</li>
              <li><strong>Droit de rectification</strong> : modifier vos informations personnelles via votre profil</li>
              <li><strong>Droit a l'effacement</strong> : supprimer votre compte via l'endpoint DELETE /api/auth/mon-compte</li>
              <li><strong>Droit a la portabilite</strong> : exporter toutes vos donnees en JSON via GET /api/auth/mes-donnees</li>
              <li><strong>Droit d'opposition</strong> : vous pouvez vous opposer au traitement en contactant le responsable</li>
            </ul>
          </section>

          <section>
            <h2 className="text-[15px] font-bold text-text mb-2">8. Securite des donnees</h2>
            <p>Horosphere met en oeuvre les mesures suivantes pour proteger vos donnees :</p>
            <ul className="list-disc pl-5 mt-2 space-y-1">
              <li>Chiffrement des communications (HTTPS / TLS)</li>
              <li>Mots de passe haches avec Argon2id</li>
              <li>Authentification par jeton JWT (RS256, RSA 4096 bits)</li>
              <li>Protection anti-bruteforce (rate limiting)</li>
              <li>Audit trail de toutes les actions sensibles</li>
              <li>Sauvegardes automatiques quotidiennes chiffrees</li>
            </ul>
          </section>

          <section>
            <h2 className="text-[15px] font-bold text-text mb-2">9. Cookies</h2>
            <p>
              Horosphere n'utilise pas de cookies tiers. Seul un stockage local (localStorage/sessionStorage)
              est utilise pour conserver votre session d'authentification et vos preferences d'interface (theme).
            </p>
          </section>

          <section>
            <h2 className="text-[15px] font-bold text-text mb-2">10. Contact</h2>
            <p>
              Pour toute question relative a vos donnees personnelles, vous pouvez contacter :<br />
              <strong>Chahrazed Soltani</strong> — chahrazed.soltani@horosphere.fr
            </p>
          </section>

        </div>
      </div>
    </div>
  )
}

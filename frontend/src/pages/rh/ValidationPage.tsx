import React, { useEffect, useState } from 'react'
import { CheckCircle, XCircle, FilePdf, DownloadSimple, Paperclip, PlusCircle } from '@phosphor-icons/react'
import api from '../../services/api'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Modal } from '../../components/ui/Modal'
import { Input, Select, Textarea } from '../../components/ui/Input'
import { StatutDemandeBadge, TypeDemandeBadge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { demandeService } from '../../services/demande.service'
import { userService } from '../../services/user.service'
import type { Demande, User } from '../../types'
import { format } from 'date-fns'

export default function ValidationPage() {
  const [demandes, setDemandes] = useState<Demande[]>([])
  const [loading, setLoading] = useState(true)
  const [processing, setProcessing] = useState<number | null>(null)
  const [generating, setGenerating] = useState<number | null>(null)
  const [filter, setFilter] = useState('ALL')
  const [docSuccess, setDocSuccess] = useState<{ id: number; url: string } | null>(null)
  const [docError, setDocError] = useState<number | null>(null)
  const [absenceModalOpen, setAbsenceModalOpen] = useState(false)
  const [absenceSubmitting, setAbsenceSubmitting] = useState(false)
  const [absenceError, setAbsenceError] = useState<string | null>(null)
  const [users, setUsers] = useState<User[]>([])
  const [absenceForm, setAbsenceForm] = useState({ utilisateur_id: '', type_demande: 'ABSENCE', date_debut: '', date_fin: '', motif: '' })

  useEffect(() => {
    demandeService.liste()
      .then(setDemandes)
      .finally(() => setLoading(false))
    userService.liste().then(setUsers)
  }, [])

  const handle = async (id: number, decision: 'APPROUVEE' | 'REJETEE') => {
    setProcessing(id)
    try {
      const updated = await demandeService.traiter(id, decision)
      setDemandes((prev) => prev.map((d) => d.id === id ? updated : d))
    } finally { setProcessing(null) }
  }

  const handleGenDoc = async (demande: Demande, typeDoc: string) => {
    setGenerating(demande.id)
    setDocError(null)
    try {
      const doc = await demandeService.genererDocument(demande.id, typeDoc)
      setDocSuccess({ id: demande.id, url: doc.downloadUrl })
      setTimeout(() => setDocSuccess(null), 8000)
    } catch {
      setDocError(demande.id)
      setTimeout(() => setDocError(null), 5000)
    } finally {
      setGenerating(null)
    }
  }

  const handleAbsenceSubmit = async () => {
    if (!absenceForm.utilisateur_id || !absenceForm.date_debut || !absenceForm.date_fin) return
    setAbsenceSubmitting(true)
    setAbsenceError(null)
    try {
      const d = await demandeService.creerParRh({
        utilisateur_id: Number(absenceForm.utilisateur_id),
        type_demande: absenceForm.type_demande,
        date_debut: absenceForm.date_debut,
        date_fin: absenceForm.date_fin,
        motif: absenceForm.motif || undefined,
      })
      setDemandes((prev) => [d, ...prev])
      setAbsenceModalOpen(false)
      setAbsenceForm({ utilisateur_id: '', type_demande: 'ABSENCE', date_debut: '', date_fin: '', motif: '' })
    } catch {
      setAbsenceError('Erreur lors de la déclaration. Veuillez réessayer.')
    } finally {
      setAbsenceSubmitting(false)
    }
  }

  const filtered = demandes.filter((d) => filter === 'ALL' || d.statut === filter)

  if (loading) return <LoadingSpinner />

  return (
    <div className="space-y-5">
      {/* Actions */}
      <div className="flex justify-end">
        <Button icon={<PlusCircle size={16} />} onClick={() => setAbsenceModalOpen(true)}>
          Déclarer une absence
        </Button>
      </div>

      {/* Filtres */}
      <div className="flex gap-2 flex-wrap">
        {[['ALL', 'Toutes'], ['EN_ATTENTE', 'En attente'], ['APPROUVEE', 'Approuvées'], ['REJETEE', 'Rejetées']].map(([val, label]) => (
          <button
            key={val}
            onClick={() => setFilter(val)}
            className={`px-3 py-1.5 rounded-md text-[12px] font-medium transition-colors ${
              filter === val ? 'bg-accent text-white' : 'bg-surface border border-border text-text2 hover:bg-surface2'
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      {filtered.length === 0 ? (
        <div className="text-center py-16 text-text3 text-[13px]">Aucune demande.</div>
      ) : (
        filtered.map((d) => (
          <div
            key={d.id}
            className={`bg-surface border rounded-lg p-4 shadow ${
              d.statut === 'EN_ATTENTE' ? 'border-l-4 border-l-amber' : 'border-border'
            }`}
          >
            <div className="flex items-start gap-3">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <span className="text-[13px] font-semibold text-text">
                    {d.utilisateur?.prenom} {d.utilisateur?.nom}
                  </span>
                  <TypeDemandeBadge type={d.typeDemande} />
                  <StatutDemandeBadge statut={d.statut} />
                  <span className="text-[11px] text-text3 font-mono ml-auto">
                    {format(new Date(d.dateCreation), 'dd/MM/yyyy')}
                  </span>
                </div>
                <div className="text-[13px] text-text2">
                  Du <span className="font-semibold text-text">{format(new Date(d.dateDebut), 'dd/MM/yyyy')}</span> au{' '}
                  <span className="font-semibold text-text">{format(new Date(d.dateFin), 'dd/MM/yyyy')}</span>
                  <span className="text-text3 ml-2 text-[12px]">({d.dureeJours} jour(s))</span>
                </div>
                {d.motif && <p className="text-[12px] text-text3 mt-1 italic">"{d.motif}"</p>}
                {d.justificatif && (
                  <button
                    onClick={() => demandeService.downloadJustificatif(d.id, d.justificatif!)}
                    className="mt-1.5 inline-flex items-center gap-1 text-[12px] text-accent font-medium hover:underline cursor-pointer bg-transparent border-none"
                  >
                    <Paperclip size={13} /> {d.justificatif}
                  </button>
                )}

                {/* Notification document généré */}
                {docSuccess?.id === d.id && (
                  <div className="mt-2 px-3 py-2 rounded-lg bg-green-bg border border-green-border text-green text-[12px] flex items-center gap-2">
                    <CheckCircle size={14} weight="bold" />
                    Document généré !
                    <button
                      onClick={async () => {
                        const downloadPath = docSuccess.url.replace(/^\/api/, '')
                        const res = await api.get(downloadPath, { responseType: 'blob' })
                        const blob = new Blob([res.data], { type: 'application/pdf' })
                        const url = window.URL.createObjectURL(blob)
                        const a = document.createElement('a')
                        a.href = url
                        a.download = 'document.pdf'
                        a.click()
                        window.URL.revokeObjectURL(url)
                      }}
                      className="ml-auto flex items-center gap-1 font-semibold underline cursor-pointer bg-transparent border-none text-green"
                    >
                      <DownloadSimple size={13} /> Télécharger
                    </button>
                  </div>
                )}

                {docError === d.id && (
                  <div className="mt-2 px-3 py-2 rounded-lg bg-red-bg border border-red-border text-red text-[12px] flex items-center gap-2">
                    <XCircle size={14} weight="bold" />
                    Erreur lors de la génération du document.
                  </div>
                )}

                {/* Boutons documents pour les demandes traitées */}
                {(d.statut === 'APPROUVEE' || d.statut === 'REJETEE') && (
                  <div className="flex gap-2 mt-3 flex-wrap">
                    {d.statut === 'APPROUVEE' && (
                      <Button
                        variant="outline"
                        size="sm"
                        icon={<FilePdf size={14} />}
                        loading={generating === d.id}
                        onClick={() => handleGenDoc(d, 'accord_conge')}
                      >
                        Accord de {d.typeDemande === 'CONGE' ? 'congé' : 'demande'}
                      </Button>
                    )}
                    {d.statut === 'REJETEE' && (
                      <Button
                        variant="outline"
                        size="sm"
                        icon={<FilePdf size={14} />}
                        loading={generating === d.id}
                        onClick={() => handleGenDoc(d, 'refus_conge')}
                      >
                        Notification de refus
                      </Button>
                    )}
                    <Button
                      variant="ghost"
                      size="sm"
                      icon={<FilePdf size={14} />}
                      loading={generating === d.id}
                      onClick={() => handleGenDoc(d, 'attestation')}
                    >
                      Attestation
                    </Button>
                  </div>
                )}
              </div>

              {d.statut === 'EN_ATTENTE' && (
                <div className="flex gap-2 flex-shrink-0">
                  <Button
                    variant="success"
                    size="sm"
                    icon={<CheckCircle size={14} />}
                    loading={processing === d.id}
                    onClick={() => handle(d.id, 'APPROUVEE')}
                  >
                    Approuver
                  </Button>
                  <Button
                    variant="danger"
                    size="sm"
                    icon={<XCircle size={14} />}
                    loading={processing === d.id}
                    onClick={() => handle(d.id, 'REJETEE')}
                  >
                    Rejeter
                  </Button>
                </div>
              )}
            </div>
          </div>
        ))
      )}

      <Modal
        open={absenceModalOpen}
        onClose={() => { setAbsenceModalOpen(false); setAbsenceError(null) }}
        title="Déclarer une absence"
        footer={
          <>
            <Button variant="ghost" onClick={() => { setAbsenceModalOpen(false); setAbsenceError(null) }}>Annuler</Button>
            <Button onClick={handleAbsenceSubmit} loading={absenceSubmitting}>Déclarer</Button>
          </>
        }
      >
        {absenceError && (
          <div className="px-3 py-2 rounded-lg text-[13px] mb-3 border bg-red-bg border-red-border text-red">
            {absenceError}
          </div>
        )}
        <Select
          label="Employé"
          value={absenceForm.utilisateur_id}
          onChange={(e) => setAbsenceForm({ ...absenceForm, utilisateur_id: e.target.value })}
        >
          <option value="">-- Sélectionner --</option>
          {users.map((u) => (
            <option key={u.id} value={u.id}>{u.prenom} {u.nom}</option>
          ))}
        </Select>
        <Select
          label="Type"
          value={absenceForm.type_demande}
          onChange={(e) => setAbsenceForm({ ...absenceForm, type_demande: e.target.value })}
        >
          <option value="ABSENCE">Absence</option>
          <option value="CONGE">Congé</option>
        </Select>
        <div className="grid grid-cols-2 gap-3">
          <Input label="Date début" type="date" value={absenceForm.date_debut} onChange={(e) => setAbsenceForm({ ...absenceForm, date_debut: e.target.value })} />
          <Input label="Date fin" type="date" value={absenceForm.date_fin} onChange={(e) => setAbsenceForm({ ...absenceForm, date_fin: e.target.value })} />
        </div>
        <Textarea label="Motif (optionnel)" value={absenceForm.motif} onChange={(e) => setAbsenceForm({ ...absenceForm, motif: e.target.value })} placeholder="Raison de l'absence..." />
      </Modal>
    </div>
  )
}

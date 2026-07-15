import React, { useEffect, useState } from 'react'
import { PlusCircle } from '@phosphor-icons/react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Modal } from '../../components/ui/Modal'
import { Input, Select, Textarea } from '../../components/ui/Input'
import { StatutDemandeBadge, TypeDemandeBadge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { demandeService } from '../../services/demande.service'
import type { Demande } from '../../types'
import { format } from 'date-fns'

export default function DemandesPage() {
  const [demandes, setDemandes] = useState<Demande[]>([])
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [form, setForm] = useState({ type_demande: 'CONGE', date_debut: '', date_fin: '', motif: '' })

  useEffect(() => {
    demandeService.liste()
      .then(setDemandes)
      .finally(() => setLoading(false))
  }, [])

  const handleSubmit = async () => {
    if (!form.date_debut || !form.date_fin) return
    setSubmitting(true)
    setError(null)
    try {
      const d = await demandeService.creer(form)
      setDemandes((prev) => [d, ...prev])
      setError(null)
      setModalOpen(false)
      setForm({ type_demande: 'CONGE', date_debut: '', date_fin: '', motif: '' })
    } catch {
      setError('Erreur lors de la création de la demande. Veuillez réessayer.')
    }
    finally { setSubmitting(false) }
  }

  if (loading) return <LoadingSpinner />

  return (
    <div className="space-y-5">
      <div className="flex justify-end">
        <Button icon={<PlusCircle size={16} />} onClick={() => setModalOpen(true)}>
          Nouvelle demande
        </Button>
      </div>

      <div className="space-y-3">
        {demandes.length === 0 ? (
          <div className="text-center py-16 text-text3 text-[13px]">Aucune demande pour le moment.</div>
        ) : demandes.map((d) => (
          <Card key={d.id}>
            <div className="flex items-start gap-3">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1.5">
                  <TypeDemandeBadge type={d.typeDemande} />
                  <StatutDemandeBadge statut={d.statut} />
                  <span className="text-[11px] text-text3 font-mono ml-auto">
                    {format(new Date(d.dateCreation), 'dd/MM/yyyy')}
                  </span>
                </div>
                <div className="text-[13px] text-text font-medium">
                  Du {format(new Date(d.dateDebut), 'dd/MM/yyyy')} au {format(new Date(d.dateFin), 'dd/MM/yyyy')}
                  <span className="text-text3 ml-2">({d.dureeJours} jour(s))</span>
                </div>
                {d.motif && <p className="text-[12px] text-text3 mt-1">{d.motif}</p>}
              </div>
            </div>
          </Card>
        ))}
      </div>

      <Modal
        open={modalOpen}
        onClose={() => { setModalOpen(false); setError(null) }}
        title="Nouvelle demande"
        footer={
          <>
            <Button variant="ghost" onClick={() => { setModalOpen(false); setError(null) }}>Annuler</Button>
            <Button onClick={handleSubmit} loading={submitting}>Soumettre</Button>
          </>
        }
      >
        {error && (
          <div className="px-3 py-2 rounded-lg text-[13px] mb-3 border bg-red-bg border-red-border text-red">
            {error}
          </div>
        )}
        <Select
          label="Type de demande"
          value={form.type_demande}
          onChange={(e) => setForm({ ...form, type_demande: e.target.value })}
        >
          <option value="CONGE">Congé</option>
          <option value="CORRECTION">Correction</option>
          <option value="ABSENCE">Absence</option>
          <option value="AUTRE">Autre</option>
        </Select>
        <div className="grid grid-cols-2 gap-3">
          <Input label="Date début" type="date" value={form.date_debut} onChange={(e) => setForm({ ...form, date_debut: e.target.value })} />
          <Input label="Date fin" type="date" value={form.date_fin} onChange={(e) => setForm({ ...form, date_fin: e.target.value })} />
        </div>
        <Textarea label="Motif (optionnel)" value={form.motif} onChange={(e) => setForm({ ...form, motif: e.target.value })} placeholder="Précisez la raison de votre demande..." />
      </Modal>
    </div>
  )
}

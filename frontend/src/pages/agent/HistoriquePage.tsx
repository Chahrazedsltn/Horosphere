import React, { useEffect, useState } from 'react'
import { PlusCircle } from '@phosphor-icons/react'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
import { Button } from '../../components/ui/Button'
import { Modal } from '../../components/ui/Modal'
import { Input, Textarea } from '../../components/ui/Input'
import { StatutPointageBadge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { pointageService } from '../../services/pointage.service'
import type { Pointage } from '../../types'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'

function formatDuree(minutes: number | undefined): string {
  if (!minutes) return '—'
  return `${Math.floor(minutes / 60)}h${String(minutes % 60).padStart(2, '0')}`
}

export default function HistoriquePage() {
  const [pointages, setPointages] = useState<Pointage[]>([])
  const [loading, setLoading] = useState(true)
  const [filterMonth, setFilterMonth] = useState<string>(() => {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  })

  // Modal saisie manuelle
  const [modalOpen, setModalOpen] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)
  const [form, setForm] = useState({ date_jour: '', heure_arrivee: '09:00', heure_depart: '17:00', motif: '' })

  useEffect(() => {
    pointageService.mesPointages()
      .then(setPointages)
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  const handleSubmit = async () => {
    if (!form.date_jour || !form.heure_arrivee || !form.heure_depart) return
    setSubmitting(true)
    setError(null)
    setSuccess(null)
    try {
      const p = await pointageService.manuel(form)
      setPointages((prev) => [p, ...prev].sort((a, b) => b.dateJour.localeCompare(a.dateJour)))
      setModalOpen(false)
      setForm({ date_jour: '', heure_arrivee: '09:00', heure_depart: '17:00', motif: '' })
      setSuccess('Pointage manuel enregistré avec succès.')
      setTimeout(() => setSuccess(null), 4000)
    } catch (err: any) {
      const message = err?.response?.data?.message ?? 'Erreur lors de la saisie.'
      setError(message)
    } finally {
      setSubmitting(false)
    }
  }

  const filtered = pointages.filter((p) => p.dateJour.startsWith(filterMonth))

  const months: string[] = []
  for (let i = 0; i < 6; i++) {
    const d = new Date()
    d.setMonth(d.getMonth() - i)
    months.push(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`)
  }

  if (loading) return <LoadingSpinner text="Chargement de l'historique..." />

  return (
    <div className="space-y-5">
      <div className="flex items-center gap-3">
        <select
          value={filterMonth}
          onChange={(e) => setFilterMonth(e.target.value)}
          className="h-9 bg-surface border border-border rounded-md px-3 text-[13px] text-text outline-none focus:border-accent-mid"
        >
          {months.map((m) => (
            <option key={m} value={m}>
              {new Date(m + '-01').toLocaleString('fr-FR', { month: 'long', year: 'numeric' })}
            </option>
          ))}
        </select>
        <span className="text-[13px] text-text3">{filtered.length} entrée(s)</span>
        <div className="ml-auto">
          <Button icon={<PlusCircle size={16} />} onClick={() => { setModalOpen(true); setError(null) }}>
            Saisie manuelle
          </Button>
        </div>
      </div>

      {success && (
        <div className="px-3 py-2 rounded-lg text-[13px] border bg-green-bg border-green-border text-green">
          {success}
        </div>
      )}

      <Card noPadding>
        <Table
          columns={[
            {
              key: 'dateJour',
              header: 'Date',
              render: (p) => (
                <span className="font-mono text-[12px]">
                  {format(new Date(p.dateJour), 'EEE dd/MM/yyyy', { locale: fr })}
                </span>
              ),
            },
            {
              key: 'site',
              header: 'Site',
              render: (p) => p.coordonneesGps === 'manuel'
                ? <span className="text-accent text-[12px] font-medium">Saisie manuelle</span>
                : (p.site?.nom ?? <span className="text-text3">—</span>),
            },
            {
              key: 'heureArrivee',
              header: 'Arrivée',
              render: (p) => (
                <span className="font-mono">{p.heureArrivee ? format(new Date(p.heureArrivee), 'HH:mm') : '—'}</span>
              ),
            },
            {
              key: 'heureDepart',
              header: 'Départ',
              render: (p) => (
                <span className="font-mono">{p.heureDepart ? format(new Date(p.heureDepart), 'HH:mm') : '—'}</span>
              ),
            },
            {
              key: 'duree',
              header: 'Durée',
              render: (p) => <span className="font-mono">{formatDuree(p.dureeMinutes)}</span>,
            },
            {
              key: 'statut',
              header: 'Statut',
              render: (p) => <StatutPointageBadge statut={p.statut} />,
            },
          ]}
          data={filtered}
          keyExtractor={(p) => p.id}
          emptyMessage="Aucun pointage pour cette période."
        />
      </Card>

      {/* Modal saisie manuelle */}
      <Modal
        open={modalOpen}
        onClose={() => { setModalOpen(false); setError(null) }}
        title="Saisie manuelle d'heures"
        footer={
          <>
            <Button variant="ghost" onClick={() => { setModalOpen(false); setError(null) }}>Annuler</Button>
            <Button onClick={handleSubmit} loading={submitting}>Enregistrer</Button>
          </>
        }
      >
        {error && (
          <div className="px-3 py-2 rounded-lg text-[13px] mb-3 border bg-red-bg border-red-border text-red">
            {error}
          </div>
        )}
        <Input
          label="Date"
          type="date"
          value={form.date_jour}
          onChange={(e) => setForm({ ...form, date_jour: e.target.value })}
          required
        />
        <div className="grid grid-cols-2 gap-3">
          <Input
            label="Heure d'arrivée"
            type="time"
            value={form.heure_arrivee}
            onChange={(e) => setForm({ ...form, heure_arrivee: e.target.value })}
            required
          />
          <Input
            label="Heure de départ"
            type="time"
            value={form.heure_depart}
            onChange={(e) => setForm({ ...form, heure_depart: e.target.value })}
            required
          />
        </div>
        <Textarea
          label="Motif (optionnel)"
          value={form.motif}
          onChange={(e) => setForm({ ...form, motif: e.target.value })}
          placeholder="Ex : oubli de pointage, problème de GPS..."
        />
        <p className="text-[11px] text-text3 mt-1">
          Un seul pointage par jour est autorisé.
        </p>
      </Modal>
    </div>
  )
}

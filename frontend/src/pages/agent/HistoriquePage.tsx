import React, { useEffect, useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
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

  useEffect(() => {
    pointageService.mesPointages()
      .then(setPointages)
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

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
      </div>

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
              render: (p) => p.site?.nom ?? <span className="text-text3">—</span>,
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
    </div>
  )
}

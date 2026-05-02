import React, { useEffect, useState } from 'react'
import { StatCard } from '../../components/ui/StatCard'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
import { StatutPointageBadge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { userService } from '../../services/user.service'
import { pointageService } from '../../services/pointage.service'
import type { DashboardStats, Pointage } from '../../types'
import { format } from 'date-fns'

export default function RhDashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null)
  const [todayPointages, setTodayPointages] = useState<Pointage[]>([])
  const [anomalies, setAnomalies] = useState<Pointage[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const today = format(new Date(), 'yyyy-MM-dd')
    Promise.all([
      userService.statsDashboard(),
      pointageService.liste({ date_debut: today, date_fin: today }),
      pointageService.mesPointages(), // anomalies
    ])
      .then(([s, p]) => {
        setStats(s)
        setTodayPointages(p)
        setAnomalies(p.filter((x) => x.estAnomalie))
      })
      .finally(() => setLoading(false))
  }, [])

  if (loading || !stats) return <LoadingSpinner text="Chargement du tableau de bord RH..." />

  return (
    <div className="space-y-6">
      {/* Stats */}
      <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <StatCard label="Employés" value={stats.total_employes} />
        <StatCard label="Présents aujourd'hui" value={stats.presents_aujourd_hui} color="green" />
        <StatCard label="Anomalies" value={stats.anomalies_en_cours} color={stats.anomalies_en_cours > 0 ? 'red' : 'default'} />
        <StatCard label="Demandes en attente" value={stats.demandes_en_attente} color={stats.demandes_en_attente > 0 ? 'amber' : 'default'} />
        <StatCard label="Taux présence" value={`${stats.taux_presence}%`} color="blue" />
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-2 gap-5">
        {/* Pointages du jour */}
        <Card title="Pointages du jour" icon="◷">
          <Table
            columns={[
              { key: 'utilisateur', header: 'Employé', render: (p) => `${p.utilisateur?.prenom ?? ''} ${p.utilisateur?.nom ?? ''}` },
              { key: 'site', header: 'Site', render: (p) => p.site?.nom ?? '—' },
              { key: 'heureArrivee', header: 'Arrivée', render: (p) => <span className="font-mono">{format(new Date(p.heureArrivee), 'HH:mm')}</span> },
              { key: 'statut', header: 'Statut', render: (p) => <StatutPointageBadge statut={p.statut} /> },
            ]}
            data={todayPointages.slice(0, 10)}
            keyExtractor={(p) => p.id}
            emptyMessage="Aucun pointage aujourd'hui."
          />
        </Card>

        {/* Anomalies récentes */}
        <Card title="Anomalies récentes" icon="⚠">
          <Table
            columns={[
              { key: 'utilisateur', header: 'Employé', render: (p) => `${p.utilisateur?.prenom ?? ''} ${p.utilisateur?.nom ?? ''}` },
              { key: 'dateJour', header: 'Date', render: (p) => <span className="font-mono text-[12px]">{format(new Date(p.dateJour), 'dd/MM/yyyy')}</span> },
              { key: 'statut', header: 'Type', render: (p) => <StatutPointageBadge statut={p.statut} /> },
            ]}
            data={anomalies.slice(0, 8)}
            keyExtractor={(p) => p.id}
            emptyMessage="Aucune anomalie récente."
          />
        </Card>
      </div>
    </div>
  )
}

import React, { useEffect, useState } from 'react'
import { StatCard } from '../../components/ui/StatCard'
import { Card } from '../../components/ui/Card'
import { PointageWidget } from '../../components/pointage/PointageWidget'
import { MonthCalendar } from '../../components/calendar/MonthCalendar'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { pointageService } from '../../services/pointage.service'
import { demandeService } from '../../services/demande.service'
import type { Pointage, Demande } from '../../types'

export default function DashboardPage() {
  const [pointages, setPointages] = useState<Pointage[]>([])
  const [demandes, setDemandes] = useState<Demande[]>([])
  const [loading, setLoading] = useState(true)
  const now = new Date()

  useEffect(() => {
    Promise.all([pointageService.mesPointages(), demandeService.liste()])
      .then(([p, d]) => { setPointages(p); setDemandes(d) })
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  const thisMonth = pointages.filter((p) => {
    const d = new Date(p.dateJour)
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear()
  })

  const totalMinutes = thisMonth.reduce((sum, p) => sum + (p.dureeMinutes ?? 0), 0)
  const heuresMois = `${Math.floor(totalMinutes / 60)}h${String(totalMinutes % 60).padStart(2, '0')}`
  const joursPresents = new Set(thisMonth.filter((p) => p.statut === 'VALIDE' || p.statut === 'EN_COURS').map((p) => p.dateJour)).size
  const joursAnomalies = thisMonth.filter((p) => p.estAnomalie).length
  const demandesEnAttente = demandes.filter((d) => d.statut === 'EN_ATTENTE').length

  if (loading) return <LoadingSpinner text="Chargement..." />

  return (
    <div className="space-y-6">
      {/* Stats */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard label="Heures ce mois" value={heuresMois} sub={`${thisMonth.length} jours travaillés`} color="blue" />
        <StatCard label="Jours présents" value={joursPresents} color="green" />
        <StatCard label="Anomalies" value={joursAnomalies} color={joursAnomalies > 0 ? 'red' : 'default'} />
        <StatCard label="Demandes en attente" value={demandesEnAttente} color={demandesEnAttente > 0 ? 'amber' : 'default'} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {/* Pointage */}
        <Card title="Pointage du jour" icon="◷">
          <PointageWidget />
        </Card>

        {/* Calendrier */}
        <Card title={`${now.toLocaleString('fr-FR', { month: 'long', year: 'numeric' })}`} icon="📅">
          <MonthCalendar
            year={now.getFullYear()}
            month={now.getMonth()}
            pointages={pointages}
          />
        </Card>
      </div>
    </div>
  )
}

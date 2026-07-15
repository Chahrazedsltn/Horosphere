import React, { useEffect, useState } from 'react'
import { Clock, Warning, Users, X } from '@phosphor-icons/react'
import { StatCard } from '../../components/ui/StatCard'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
import { Badge, StatutPointageBadge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { userService, type EmployeStats } from '../../services/user.service'
import { pointageService } from '../../services/pointage.service'
import type { DashboardStats, Pointage } from '../../types'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'

function formatHeures(minutes: number): string {
  return `${Math.floor(minutes / 60)}h${String(minutes % 60).padStart(2, '0')}`
}

export default function RhDashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null)
  const [todayPointages, setTodayPointages] = useState<Pointage[]>([])
  const [anomalies, setAnomalies] = useState<Pointage[]>([])
  const [employeStats, setEmployeStats] = useState<EmployeStats[]>([])
  const [loading, setLoading] = useState(true)

  // Détail employé
  const [selectedEmploye, setSelectedEmploye] = useState<EmployeStats | null>(null)
  const [employePointages, setEmployePointages] = useState<Pointage[]>([])
  const [detailLoading, setDetailLoading] = useState(false)

  const now = new Date()
  const [mois, setMois] = useState(now.getMonth() + 1)
  const [annee] = useState(now.getFullYear())

  useEffect(() => {
    const today = format(new Date(), 'yyyy-MM-dd')
    Promise.all([
      userService.statsDashboard(),
      pointageService.liste({ date_debut: today, date_fin: today }),
      userService.statsEmployes(mois, annee),
    ])
      .then(([s, p, es]) => {
        setStats(s)
        setTodayPointages(p)
        setAnomalies(p.filter((x) => x.estAnomalie))
        setEmployeStats(es)
      })
      .finally(() => setLoading(false))
  }, [mois, annee])

  const handleSelectEmploye = async (emp: EmployeStats) => {
    if (selectedEmploye?.id === emp.id) {
      setSelectedEmploye(null)
      return
    }
    setSelectedEmploye(emp)
    setDetailLoading(true)
    const debut = `${annee}-${String(mois).padStart(2, '0')}-01`
    const lastDay = new Date(annee, mois, 0).getDate()
    const fin = `${annee}-${String(mois).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`
    try {
      const p = await pointageService.liste({ date_debut: debut, date_fin: fin, utilisateur_id: emp.id })
      setEmployePointages(p)
    } catch {
      setEmployePointages([])
    } finally {
      setDetailLoading(false)
    }
  }

  if (loading || !stats) return <LoadingSpinner text="Chargement du tableau de bord RH..." />

  const totalHeuresEquipe = employeStats.reduce((sum, e) => sum + e.minutes_total, 0)
  const moyenneHeures = employeStats.length > 0 ? Math.round(totalHeuresEquipe / employeStats.length) : 0

  const months: { value: number; label: string }[] = []
  for (let i = 0; i < 6; i++) {
    const d = new Date()
    d.setMonth(d.getMonth() - i)
    months.push({
      value: d.getMonth() + 1,
      label: d.toLocaleString('fr-FR', { month: 'long', year: 'numeric' }),
    })
  }

  return (
    <div className="space-y-6">
      {/* Stats globales */}
      <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <StatCard label="Employés" value={stats.total_employes} />
        <StatCard label="Présents aujourd'hui" value={stats.presents_aujourd_hui} color="green" />
        <StatCard label="Anomalies" value={stats.anomalies_en_cours} color={stats.anomalies_en_cours > 0 ? 'red' : 'default'} />
        <StatCard label="Demandes en attente" value={stats.demandes_en_attente} color={stats.demandes_en_attente > 0 ? 'amber' : 'default'} />
        <StatCard label="Taux présence" value={`${stats.taux_presence}%`} color="blue" />
      </div>

      {/* Stats par employé */}
      <Card
        title="Récapitulatif par employé"
        icon={<Users size={14} />}
        action={
          <select
            value={mois}
            onChange={(e) => { setMois(Number(e.target.value)); setSelectedEmploye(null) }}
            className="h-8 bg-surface border border-border rounded-md px-2 text-[12px] text-text outline-none focus:border-accent-mid"
          >
            {months.map((m) => (
              <option key={m.value} value={m.value}>{m.label}</option>
            ))}
          </select>
        }
        noPadding
      >
        {/* Tableau employés */}
        <div>
          {employeStats.map((emp) => {
            const isSelected = selectedEmploye?.id === emp.id
            return (
              <div key={emp.id}>
                {/* Ligne employé cliquable */}
                <div
                  onClick={() => handleSelectEmploye(emp)}
                  className={`flex items-center gap-4 px-4 py-3 border-b border-border cursor-pointer transition-colors ${isSelected ? 'bg-accent-light' : 'hover:bg-surface2'}`}
                >
                  <div className="flex items-center gap-2.5 flex-1 min-w-0">
                    <div
                      className="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0"
                      style={{ background: 'var(--accent)' }}
                    >
                      {emp.initiales}
                    </div>
                    <div className="min-w-0">
                      <div className="text-[13px] font-medium text-text truncate">{emp.prenom} {emp.nom}</div>
                      {emp.departement && <div className="text-[11px] text-text3">{emp.departement}</div>}
                    </div>
                  </div>
                  <div className="flex items-center gap-2 w-[140px]">
                    <div className="flex-1 h-2 bg-surface2 rounded-full overflow-hidden" style={{ maxWidth: '60px' }}>
                      <div
                        className="h-full rounded-full"
                        style={{
                          width: `${Math.min((emp.minutes_total / (160 * 60)) * 100, 100)}%`,
                          background: emp.heures_total >= 140 ? 'var(--green)' : emp.heures_total >= 100 ? 'var(--accent)' : 'var(--amber)',
                        }}
                      />
                    </div>
                    <span className="font-mono text-[12px] font-semibold text-text">{formatHeures(emp.minutes_total)}</span>
                  </div>
                  <span className="font-mono text-[13px] text-text2 w-[50px] text-center">{emp.jours_presents}j</span>
                  <div className="w-[70px] text-center">
                    {emp.anomalies > 0
                      ? <Badge variant="red" dot>{emp.anomalies}</Badge>
                      : <span className="text-[12px] text-text3">0</span>}
                  </div>
                </div>

                {/* Panneau détail */}
                {isSelected && (
                  <div className="bg-surface2 border-b border-border">
                    <div className="flex items-center justify-between px-4 py-2.5 border-b border-border">
                      <span className="text-[13px] font-semibold text-text">
                        Détail — {emp.prenom} {emp.nom}
                      </span>
                      <button
                        onClick={(e) => { e.stopPropagation(); setSelectedEmploye(null) }}
                        className="w-6 h-6 flex items-center justify-center rounded text-text3 hover:bg-surface hover:text-text2 transition-colors"
                      >
                        <X size={14} />
                      </button>
                    </div>
                    {detailLoading ? (
                      <div className="flex items-center justify-center py-8">
                        <div className="w-5 h-5 border-2 border-accent-light border-t-accent rounded-full animate-spin" />
                      </div>
                    ) : employePointages.length === 0 ? (
                      <div className="text-center py-6 text-[13px] text-text3">Aucun pointage ce mois.</div>
                    ) : (
                      <div className="overflow-x-auto">
                        <table className="w-full border-collapse">
                          <thead>
                            <tr>
                              <th className="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-text3 font-mono">Date</th>
                              <th className="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-text3 font-mono">Site</th>
                              <th className="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-text3 font-mono">Arrivée</th>
                              <th className="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-text3 font-mono">Départ</th>
                              <th className="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-text3 font-mono">Durée</th>
                              <th className="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-text3 font-mono">Statut</th>
                            </tr>
                          </thead>
                          <tbody>
                            {employePointages.map((p) => (
                              <tr key={p.id} className="hover:bg-surface transition-colors">
                                <td className="px-4 py-2 text-[12px] font-mono text-text2">
                                  {format(new Date(p.dateJour), 'EEE dd/MM', { locale: fr })}
                                </td>
                                <td className="px-4 py-2 text-[12px] text-text2">
                                  {p.coordonneesGps === 'manuel'
                                    ? <span className="text-accent font-medium">Manuel</span>
                                    : (p.site?.nom ?? '—')}
                                </td>
                                <td className="px-4 py-2 text-[12px] font-mono text-text2">
                                  {p.heureArrivee ? format(new Date(p.heureArrivee), 'HH:mm') : '—'}
                                </td>
                                <td className="px-4 py-2 text-[12px] font-mono text-text2">
                                  {p.heureDepart ? format(new Date(p.heureDepart), 'HH:mm') : '—'}
                                </td>
                                <td className="px-4 py-2 text-[12px] font-mono font-semibold text-text">
                                  {p.dureeMinutes ? formatHeures(p.dureeMinutes) : '—'}
                                </td>
                                <td className="px-4 py-2">
                                  <StatutPointageBadge statut={p.statut} />
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                    {/* Sous-total employé */}
                    {!detailLoading && employePointages.length > 0 && (
                      <div className="flex items-center justify-between px-4 py-2.5 border-t border-border bg-surface">
                        <span className="text-[11px] text-text3">
                          {employePointages.length} pointage(s)
                        </span>
                        <span className="text-[12px] font-semibold font-mono" style={{ color: 'var(--accent)' }}>
                          Total : {formatHeures(employePointages.reduce((s, p) => s + (p.dureeMinutes ?? 0), 0))}
                        </span>
                      </div>
                    )}
                  </div>
                )}
              </div>
            )
          })}
        </div>
        {/* Résumé global */}
        <div className="flex items-center justify-between px-4 py-3 border-t border-border bg-surface2">
          <span className="text-[12px] text-text3">
            Total équipe : <span className="font-semibold text-text font-mono">{formatHeures(totalHeuresEquipe)}</span>
          </span>
          <span className="text-[12px] text-text3">
            Moyenne : <span className="font-semibold text-text font-mono">{formatHeures(moyenneHeures)}</span> / employé
          </span>
        </div>
      </Card>

      <div className="grid grid-cols-1 xl:grid-cols-2 gap-5">
        {/* Pointages du jour */}
        <Card title="Pointages du jour" icon={<Clock size={14} />}>
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
        <Card title="Anomalies récentes" icon={<Warning size={14} />}>
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

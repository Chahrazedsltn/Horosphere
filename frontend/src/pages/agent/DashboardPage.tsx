import React, { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Clock, CalendarBlank, HandWaving, ChartBar, Umbrella, CalendarX } from '@phosphor-icons/react'
import { StatCard } from '../../components/ui/StatCard'
import { Card } from '../../components/ui/Card'
import { PointageWidget } from '../../components/pointage/PointageWidget'
import { MonthCalendar } from '../../components/calendar/MonthCalendar'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { pointageService } from '../../services/pointage.service'
import { demandeService } from '../../services/demande.service'
import { useAuthStore } from '../../store/auth.store'
import type { Pointage, Demande } from '../../types'

export default function DashboardPage() {
  const navigate = useNavigate()
  const { user } = useAuthStore()
  const [pointages, setPointages] = useState<Pointage[]>([])
  const [demandes, setDemandes] = useState<Demande[]>([])
  const [joursFeries, setJoursFeries] = useState<{ date: string; nom: string }[]>([])
  const [compteurs, setCompteurs] = useState<{
    conges_total: number; conges_pris: number; conges_restants: number;
    absences_annee: number;
  } | null>(null)
  const [loading, setLoading] = useState(true)
  const now = new Date()

  useEffect(() => {
    Promise.all([
      pointageService.mesPointages(),
      demandeService.liste(),
      pointageService.joursFeries(now.getFullYear()),
      pointageService.compteurs(),
    ])
      .then(([p, d, jf, c]) => { setPointages(p); setDemandes(d); setJoursFeries(jf); setCompteurs(c) })
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  const thisMonth = pointages.filter((p) => {
    const d = new Date(p.dateJour)
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear()
  })

  const totalMinutes = thisMonth.reduce((sum, p) => sum + (p.dureeMinutes ?? 0), 0)
  const heuresMois = `${Math.floor(totalMinutes / 60)}h${String(totalMinutes % 60).padStart(2, '0')}`
  const joursPresents = new Set(
    thisMonth.filter((p) => p.statut === 'VALIDE' || p.statut === 'EN_COURS').map((p) => p.dateJour)
  ).size
  const joursAnomalies = thisMonth.filter((p) => p.estAnomalie).length
  const demandesEnAttente = demandes.filter((d) => d.statut === 'EN_ATTENTE').length

  const prenom = user?.prenom ?? 'vous'
  const greeting = getGreeting()

  if (loading) return <LoadingSpinner text="Chargement..." />

  return (
    <div className="space-y-5">

      {/* Greeting */}
      <div>
        <h1 className="text-[22px] font-bold tracking-tight" style={{ color: 'var(--text)' }}>
          {greeting}, {prenom} <HandWaving size={22} weight="fill" className="inline-block ml-1" style={{ color: 'var(--accent)' }} />
        </h1>
        <p className="text-[13px] mt-0.5 capitalize" style={{ color: 'var(--text3)' }}>
          {now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
        </p>
      </div>

      {/* Bento grid — Stats row */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          label="Heures ce mois"
          value={heuresMois}
          sub={`${thisMonth.length} jours travaillés`}
          color="blue"
        />
        <StatCard
          label="Jours présents"
          value={joursPresents}
          sub="Ce mois-ci"
          color="green"
        />
        <StatCard
          label="Anomalies"
          value={joursAnomalies}
          sub={joursAnomalies > 0 ? 'À régulariser' : 'Aucune anomalie'}
          color={joursAnomalies > 0 ? 'red' : 'default'}
          onClick={() => navigate('/alertes')}
        />
        <StatCard
          label="Demandes en attente"
          value={demandesEnAttente}
          sub={demandesEnAttente > 0 ? 'En cours de traitement' : 'Tout est traité'}
          color={demandesEnAttente > 0 ? 'amber' : 'default'}
          onClick={() => navigate('/demandes')}
        />
      </div>

      {/* Mes compteurs */}
      {compteurs && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <CounterCard
            icon={<Umbrella size={16} weight="fill" />}
            label="Congés"
            pris={compteurs.conges_pris}
            total={compteurs.conges_total}
            restants={compteurs.conges_restants}
            color="var(--accent)"
            bgColor="var(--accent-light)"
          />
          <div className="bg-surface border border-border rounded-2xl p-5 flex items-center gap-4">
            <div
              className="w-10 h-10 rounded-xl flex items-center justify-center"
              style={{ background: 'var(--amber-bg)', color: 'var(--amber)' }}
            >
              <CalendarX size={18} weight="fill" />
            </div>
            <div>
              <div className="text-[11px] font-semibold uppercase tracking-[0.7px]" style={{ color: 'var(--text3)' }}>
                Absences cette année
              </div>
              <div className="text-[24px] font-bold leading-none mt-1" style={{ color: 'var(--amber)' }}>
                {compteurs.absences_annee}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Heures de la semaine */}
      <Card title="Heures cette semaine" icon={<ChartBar size={14} />}>
        <WeeklyHours pointages={pointages} />
      </Card>

      {/* Bento grid — Main content */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-4">

        {/* Pointage widget — tall card */}
        <div className="lg:col-span-5">
          <Card
            title="Pointage du jour"
            icon={<Clock size={14} />}
            className="h-full"
          >
            <PointageWidget />
          </Card>
        </div>

        {/* Calendar — right column */}
        <div className="lg:col-span-7">
          <Card
            title={now.toLocaleString('fr-FR', { month: 'long', year: 'numeric' })}
            icon={<CalendarBlank size={14} />}
            className="h-full"
          >
            <MonthCalendar
              year={now.getFullYear()}
              month={now.getMonth()}
              pointages={pointages}
              joursFeries={joursFeries}
              demandes={demandes}
            />
          </Card>
        </div>

      </div>
    </div>
  )
}

function getGreeting(): string {
  const h = new Date().getHours()
  if (h < 12) return 'Bonjour'
  if (h < 18) return 'Bon après-midi'
  return 'Bonsoir'
}

function CounterCard({ icon, label, pris, total, restants, color, bgColor }: {
  icon: React.ReactNode; label: string; pris: number; total: number;
  restants: number; color: string; bgColor: string
}) {
  const pct = total > 0 ? Math.round((pris / total) * 100) : 0
  return (
    <div className="bg-surface border border-border rounded-2xl p-5">
      <div className="flex items-center gap-3 mb-3">
        <div className="w-10 h-10 rounded-xl flex items-center justify-center" style={{ background: bgColor, color }}>
          {icon}
        </div>
        <div>
          <div className="text-[11px] font-semibold uppercase tracking-[0.7px]" style={{ color: 'var(--text3)' }}>{label}</div>
          <div className="text-[13px] font-bold" style={{ color }}>
            {pris} / {total} <span className="font-normal text-[11px]" style={{ color: 'var(--text3)' }}>pris</span>
          </div>
        </div>
      </div>
      <div className="w-full h-2 rounded-full overflow-hidden" style={{ background: bgColor }}>
        <div className="h-full rounded-full transition-all duration-500" style={{ width: `${pct}%`, background: color }} />
      </div>
      <div className="mt-2 text-[12px] font-semibold" style={{ color }}>
        {restants} restants
      </div>
    </div>
  )
}

const JOURS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven']

function WeeklyHours({ pointages }: { pointages: Pointage[] }) {
  const now = new Date()
  const dayOfWeek = now.getDay() // 0=dim, 1=lun...
  const monday = new Date(now)
  monday.setDate(now.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1))
  monday.setHours(0, 0, 0, 0)

  const days = JOURS.map((label, i) => {
    const d = new Date(monday)
    d.setDate(monday.getDate() + i)
    const dateStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
    const dayPointages = pointages.filter((p) => p.dateJour === dateStr)
    const minutes = dayPointages.reduce((sum, p) => sum + (p.dureeMinutes ?? 0), 0)
    const isToday = d.toDateString() === now.toDateString()
    return { label, dateStr, minutes, isToday, date: d }
  })

  const maxMinutes = Math.max(...days.map((d) => d.minutes), 480) // 8h min for scale
  const totalWeek = days.reduce((sum, d) => sum + d.minutes, 0)

  return (
    <div>
      <div className="flex items-end gap-3 h-[120px]">
        {days.map((day) => {
          const height = day.minutes > 0 ? Math.max((day.minutes / maxMinutes) * 100, 8) : 4
          const hours = Math.floor(day.minutes / 60)
          const mins = day.minutes % 60
          return (
            <div key={day.label} className="flex-1 flex flex-col items-center gap-1.5">
              {day.minutes > 0 && (
                <span className="text-[10px] font-mono font-semibold" style={{ color: 'var(--text2)' }}>
                  {hours}h{String(mins).padStart(2, '0')}
                </span>
              )}
              <div
                className="w-full rounded-md transition-all duration-300"
                style={{
                  height: `${height}%`,
                  background: day.isToday ? 'var(--accent)' : day.minutes > 0 ? 'var(--accent-mid)' : 'var(--border)',
                  opacity: day.minutes > 0 ? (day.isToday ? 1 : 0.6) : 0.3,
                }}
                title={`${day.label} — ${hours}h${String(mins).padStart(2, '0')}`}
              />
              <span
                className={`text-[11px] font-semibold ${day.isToday ? 'text-accent' : 'text-text3'}`}
              >
                {day.label}
              </span>
            </div>
          )
        })}
      </div>
      <div className="flex items-center justify-between mt-3 pt-3" style={{ borderTop: '1px solid var(--border)' }}>
        <span className="text-[12px] text-text3">Total semaine</span>
        <span className="text-[14px] font-bold font-mono" style={{ color: 'var(--accent)' }}>
          {Math.floor(totalWeek / 60)}h{String(totalWeek % 60).padStart(2, '0')}
        </span>
      </div>
    </div>
  )
}

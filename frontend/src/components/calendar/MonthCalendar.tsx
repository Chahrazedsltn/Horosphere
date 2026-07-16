import React, { useMemo } from 'react'
import type { Pointage, Demande } from '../../types'

interface JourFerie {
  date: string
  nom: string
}

export type CalendarEvent = {
  date: string
  type: 'conge' | 'absence' | 'correction' | 'autre'
  label: string
}

interface MonthCalendarProps {
  year: number
  month: number // 0-indexed
  pointages: Pointage[]
  joursFeries?: JourFerie[]
  demandes?: Demande[]
}

function getDaysInMonth(year: number, month: number) {
  return new Date(year, month + 1, 0).getDate()
}

function getFirstDayOfWeek(year: number, month: number) {
  const day = new Date(year, month, 1).getDay()
  return day === 0 ? 6 : day - 1 // Lundi = 0
}

const WEEKDAYS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const TYPE_LABELS: Record<string, string> = {
  CONGE: 'Congé',
  ABSENCE: 'Absence',
  CORRECTION: 'Correction',
  AUTRE: 'Autre',
}

export function MonthCalendar({ year, month, pointages, joursFeries = [], demandes = [] }: MonthCalendarProps) {
  const today = new Date()
  const daysInMonth = getDaysInMonth(year, month)
  const firstDay = getFirstDayOfWeek(year, month)

  const pointageMap = useMemo(() => {
    const map: Record<string, 'present' | 'absent' | 'anomalie'> = {}
    for (const p of pointages) {
      const date = p.dateJour
      if (p.statut === 'ANOMALIE' || p.estAnomalie) {
        map[date] = 'anomalie'
      } else if (p.statut === 'VALIDE' || p.statut === 'EN_COURS') {
        if (!map[date] || map[date] === 'absent') map[date] = 'present'
      }
    }
    return map
  }, [pointages])

  const ferieMap = useMemo(() => {
    const map: Record<string, string> = {}
    for (const jf of joursFeries) {
      map[jf.date] = jf.nom
    }
    return map
  }, [joursFeries])

  const demandeMap = useMemo(() => {
    const map: Record<string, { type: string; label: string }> = {}
    const approved = demandes.filter((d) => d.statut === 'APPROUVEE')
    for (const d of approved) {
      const start = new Date(d.dateDebut)
      const end = new Date(d.dateFin)
      for (let dt = new Date(start); dt <= end; dt.setDate(dt.getDate() + 1)) {
        const key = `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`
        map[key] = { type: d.typeDemande, label: TYPE_LABELS[d.typeDemande] ?? d.typeDemande }
      }
    }
    return map
  }, [demandes])

  const cells: Array<{ day: number | null }> = []
  for (let i = 0; i < firstDay; i++) cells.push({ day: null })
  for (let d = 1; d <= daysInMonth; d++) cells.push({ day: d })

  return (
    <div>
      {/* Headers */}
      <div className="grid grid-cols-7 gap-1 mb-1">
        {WEEKDAYS.map((d) => (
          <div key={d} className="text-center text-[10px] font-bold text-text3 font-mono uppercase py-1">
            {d}
          </div>
        ))}
      </div>

      {/* Days */}
      <div className="grid grid-cols-7 gap-1">
        {cells.map((cell, idx) => {
          if (!cell.day) {
            return <div key={idx} className="aspect-square rounded-md bg-surface2 border border-border" />
          }

          const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(cell.day).padStart(2, '0')}`
          const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === cell.day
          const status = pointageMap[dateStr]
          const weekday = new Date(year, month, cell.day).getDay()
          const isWeekend = weekday === 0 || weekday === 6
          const nomFerie = ferieMap[dateStr]
          const isFerie = !!nomFerie
          const demande = demandeMap[dateStr]
          const isConge = demande?.type === 'CONGE'
          const isAbsence = demande?.type === 'ABSENCE'
          const hasDemande = !!demande

          let cellClass = 'bg-surface border border-border text-text2'
          if (isToday) cellClass = 'bg-accent border-accent text-white font-bold'
          else if (status === 'anomalie') cellClass = 'bg-amber-bg border-amber-border text-amber font-semibold'
          else if (isConge) cellClass = 'bg-emerald-50 border-emerald-300 text-emerald-700 font-semibold'
          else if (isAbsence) cellClass = 'bg-rose-50 border-rose-300 text-rose-700 font-semibold'
          else if (hasDemande) cellClass = 'bg-sky-50 border-sky-300 text-sky-700 font-semibold'
          else if (status === 'present') cellClass = 'bg-accent-light border-accent-mid text-accent font-semibold'
          else if (isFerie) cellClass = 'bg-violet-100 border-violet-300 text-violet-700 font-semibold'
          else if (isWeekend) cellClass = 'bg-surface2 border-border text-text3'

          let titleText = dateStr
          if (isFerie) titleText = `${dateStr} — ${nomFerie}`
          if (hasDemande) titleText = `${dateStr} — ${demande.label}`
          if (isFerie && hasDemande) titleText = `${dateStr} — ${nomFerie} / ${demande.label}`

          return (
            <div
              key={idx}
              className={`aspect-square rounded-md border flex flex-col items-center justify-center cursor-pointer hover:border-accent-mid transition-colors ${cellClass}`}
              title={titleText}
            >
              <span className="text-[11px] font-mono">{cell.day}</span>
              {status === 'present' && !hasDemande && !isToday && <span className="w-1 h-1 rounded-full bg-current mt-0.5 opacity-70" />}
              {isConge && !isToday && <span className="text-[7px] font-bold mt-0.5 leading-none">C</span>}
              {isAbsence && !isToday && <span className="text-[7px] font-bold mt-0.5 leading-none">A</span>}
              {hasDemande && !isConge && !isAbsence && !isToday && <span className="w-1 h-1 rounded-full bg-current mt-0.5 opacity-70" />}
              {isFerie && !hasDemande && !isToday && <span className="w-1.5 h-1.5 rounded-full bg-violet-500 mt-0.5" />}
            </div>
          )
        })}
      </div>

      {/* Légende */}
      <div className="flex items-center gap-3 mt-3 text-[10px] text-text3 flex-wrap">
        <span className="flex items-center gap-1">
          <span className="w-3 h-3 rounded bg-accent" /> Aujourd'hui
        </span>
        <span className="flex items-center gap-1">
          <span className="w-3 h-3 rounded bg-accent-light border border-accent-mid" /> Présent
        </span>
        <span className="flex items-center gap-1">
          <span className="w-3 h-3 rounded bg-emerald-50 border border-emerald-300" /> Congé
        </span>
        <span className="flex items-center gap-1">
          <span className="w-3 h-3 rounded bg-rose-50 border border-rose-300" /> Absence
        </span>
        <span className="flex items-center gap-1">
          <span className="w-3 h-3 rounded bg-amber-bg border border-amber-border" /> Anomalie
        </span>
        <span className="flex items-center gap-1">
          <span className="w-3 h-3 rounded bg-violet-100 border border-violet-300" /> Férié
        </span>
      </div>
    </div>
  )
}

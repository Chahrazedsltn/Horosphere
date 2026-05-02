import React, { useMemo } from 'react'
import type { Pointage } from '../../types'

interface MonthCalendarProps {
  year: number
  month: number // 0-indexed
  pointages: Pointage[]
}

function getDaysInMonth(year: number, month: number) {
  return new Date(year, month + 1, 0).getDate()
}

function getFirstDayOfWeek(year: number, month: number) {
  const day = new Date(year, month, 1).getDay()
  return day === 0 ? 6 : day - 1 // Lundi = 0
}

const WEEKDAYS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

export function MonthCalendar({ year, month, pointages }: MonthCalendarProps) {
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

          let cellClass = 'bg-surface border border-border text-text2'
          if (isToday) cellClass = 'bg-accent border-accent text-white font-bold'
          else if (status === 'anomalie') cellClass = 'bg-amber-bg border-amber-border text-amber font-semibold'
          else if (status === 'present') cellClass = 'bg-accent-light border-accent-mid text-accent font-semibold'
          else if (isWeekend) cellClass = 'bg-surface2 border-border text-text3'

          return (
            <div
              key={idx}
              className={`aspect-square rounded-md border flex flex-col items-center justify-center cursor-pointer hover:border-accent-mid transition-colors ${cellClass}`}
              title={dateStr}
            >
              <span className="text-[11px] font-mono">{cell.day}</span>
              {status === 'present' && !isToday && <span className="w-1 h-1 rounded-full bg-current mt-0.5 opacity-70" />}
            </div>
          )
        })}
      </div>

      {/* Légende */}
      <div className="flex items-center gap-4 mt-3 text-[11px] text-text3">
        <span className="flex items-center gap-1.5">
          <span className="w-3 h-3 rounded bg-accent" /> Aujourd'hui
        </span>
        <span className="flex items-center gap-1.5">
          <span className="w-3 h-3 rounded bg-accent-light border border-accent-mid" /> Présent
        </span>
        <span className="flex items-center gap-1.5">
          <span className="w-3 h-3 rounded bg-amber-bg border border-amber-border" /> Anomalie
        </span>
      </div>
    </div>
  )
}

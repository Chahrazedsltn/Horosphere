import React from 'react'
import { List, Bell, CalendarBlank } from '@phosphor-icons/react'
import { useClock } from '../../hooks/useClock'
import { useUiStore } from '../../store/ui.store'
import { useNavigate } from 'react-router-dom'

interface TopbarProps {
  title: string
}

export function Topbar({ title }: TopbarProps) {
  const { time, date } = useClock()
  const { toggleSidebar, alertesNonLues } = useUiStore()
  const navigate = useNavigate()

  return (
    <header className="h-[60px] bg-surface border-b border-border flex items-center px-6 gap-4 sticky top-0 z-30 shadow-sm">
      {/* Burger mobile */}
      <button
        className="md:hidden w-8 h-8 flex items-center justify-center rounded-lg text-text3 hover:bg-surface2 transition-colors"
        onClick={toggleSidebar}
      >
        <List size={18} />
      </button>

      <span className="text-[15px] font-semibold text-text tracking-tight">{title}</span>

      <div className="flex-1" />

      {/* Clock */}
      <div className="hidden sm:flex items-center gap-2">
        <div
          className="text-[11px] font-medium px-3 py-1.5 rounded-lg capitalize flex items-center gap-1.5"
          style={{ background: 'var(--accent-light)', color: 'var(--text3)', border: '1px solid var(--accent-border)' }}
        >
          <CalendarBlank size={12} weight="bold" /> {date}
        </div>
        <div
          className="font-mono text-[13px] font-semibold px-3 py-1.5 rounded-lg"
          style={{ background: 'var(--accent-light)', color: 'var(--accent)', border: '1px solid var(--accent-border)' }}
        >
          {time}
        </div>
      </div>

      {/* Alertes */}
      <button
        className="relative w-9 h-9 flex items-center justify-center rounded-lg text-text3 hover:bg-surface2 hover:text-text2 transition-colors"
        onClick={() => navigate('/alertes')}
        title="Mes alertes"
      >
        <Bell size={17} />
        {alertesNonLues > 0 && (
          <span className="absolute top-1 right-1 min-w-[16px] h-4 bg-red text-white text-[9px] font-bold font-mono rounded-full flex items-center justify-center px-1">
            {alertesNonLues > 9 ? '9+' : alertesNonLues}
          </span>
        )}
      </button>
    </header>
  )
}

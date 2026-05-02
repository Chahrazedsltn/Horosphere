import React from 'react'
import { Menu, Bell } from 'lucide-react'
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
    <header
      className="h-[60px] bg-surface border-b border-border flex items-center px-7 gap-4 sticky top-0 z-30"
    >
      {/* Burger mobile */}
      <button
        className="md:hidden w-8 h-8 flex items-center justify-center rounded-md text-text3 hover:bg-surface2"
        onClick={toggleSidebar}
      >
        <Menu size={18} />
      </button>

      <span className="text-[16px] font-semibold text-text">{title}</span>

      <div className="flex-1" />

      {/* Clock */}
      <div className="text-right hidden sm:block">
        <div className="font-mono text-[15px] font-semibold text-accent">{time}</div>
        <div className="text-[11px] text-text3 capitalize">{date}</div>
      </div>

      {/* Alertes */}
      <button
        className="relative w-9 h-9 flex items-center justify-center rounded-md text-text3 hover:bg-surface2 hover:text-text2 transition-colors"
        onClick={() => navigate('/alertes')}
        title="Mes alertes"
      >
        <Bell size={18} />
        {alertesNonLues > 0 && (
          <span className="absolute top-1 right-1 min-w-[16px] h-4 bg-red text-white text-[9px] font-bold font-mono rounded-full flex items-center justify-center px-1">
            {alertesNonLues > 9 ? '9+' : alertesNonLues}
          </span>
        )}
      </button>
    </header>
  )
}

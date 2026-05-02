import React, { useState, useEffect } from 'react'

interface TimerProps {
  startTime: string | null
}

function formatDuration(seconds: number): string {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

export function Timer({ startTime }: TimerProps) {
  const [elapsed, setElapsed] = useState(0)

  useEffect(() => {
    if (!startTime) { setElapsed(0); return }

    const start = new Date(startTime).getTime()

    const update = () => {
      const diff = Math.floor((Date.now() - start) / 1000)
      setElapsed(Math.max(0, diff))
    }

    update()
    const interval = setInterval(update, 1000)
    return () => clearInterval(interval)
  }, [startTime])

  return (
    <div className="bg-surface2 rounded-lg px-4 py-3 flex justify-between items-center">
      <div>
        <div className="text-[10px] font-semibold text-text3 uppercase tracking-wide">Durée en cours</div>
        <div className="font-mono text-[22px] font-bold text-text mt-0.5">
          {startTime ? formatDuration(elapsed) : '—'}
        </div>
      </div>
      <div className="text-right">
        <div className="text-[10px] font-semibold text-text3 uppercase tracking-wide">Statut</div>
        <div className={`text-[13px] font-semibold mt-0.5 ${startTime ? 'text-green' : 'text-text3'}`}>
          {startTime ? '● En cours' : '○ Inactif'}
        </div>
      </div>
    </div>
  )
}

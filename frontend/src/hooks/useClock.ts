import { useState, useEffect } from 'react'

export function useClock(): { time: string; date: string } {
  const [now, setNow] = useState(new Date())

  useEffect(() => {
    const interval = setInterval(() => setNow(new Date()), 1000)
    return () => clearInterval(interval)
  }, [])

  const time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  const date = now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

  return { time, date }
}

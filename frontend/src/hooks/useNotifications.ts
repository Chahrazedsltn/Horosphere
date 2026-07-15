import { useEffect } from 'react'
import { alerteService } from '../services/alerte.service'
import { useUiStore } from '../store/ui.store'
import { useAuthStore } from '../store/auth.store'

export function useNotifications(): void {
  const { isAuthenticated } = useAuthStore()
  const { setAlertesNonLues } = useUiStore()

  useEffect(() => {
    if (!isAuthenticated) return

    let active = true

    const fetchCount = async () => {
      try {
        const { non_lues } = await alerteService.mesAlertes()
        if (active) setAlertesNonLues(non_lues)
      } catch {
        // Silencieux
      }
    }

    fetchCount()
    const interval = setInterval(fetchCount, 60_000) // Polling toutes les minutes
    return () => {
      active = false
      clearInterval(interval)
    }
  }, [isAuthenticated, setAlertesNonLues])
}

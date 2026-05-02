import { useState, useEffect, useCallback } from 'react'
import type { GeoPosition } from '../types'

interface GeolocationState {
  position: GeoPosition | null
  error: string | null
  loading: boolean
  refresh: () => void
}

export function useGeolocation(): GeolocationState {
  const [position, setPosition] = useState<GeoPosition | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  const getPosition = useCallback(() => {
    if (!navigator.geolocation) {
      setError('La géolocalisation n\'est pas supportée par votre navigateur.')
      return
    }

    setLoading(true)
    setError(null)

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setPosition({
          latitude: pos.coords.latitude,
          longitude: pos.coords.longitude,
          accuracy: pos.coords.accuracy,
        })
        setLoading(false)
      },
      (err) => {
        switch (err.code) {
          case err.PERMISSION_DENIED:
            setError('Accès à la géolocalisation refusé. Veuillez l\'autoriser dans votre navigateur.')
            break
          case err.POSITION_UNAVAILABLE:
            setError('Position GPS indisponible.')
            break
          case err.TIMEOUT:
            setError('La requête GPS a expiré.')
            break
          default:
            setError('Erreur GPS inconnue.')
        }
        setLoading(false)
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 },
    )
  }, [])

  useEffect(() => {
    getPosition()
  }, [getPosition])

  return { position, error, loading, refresh: getPosition }
}

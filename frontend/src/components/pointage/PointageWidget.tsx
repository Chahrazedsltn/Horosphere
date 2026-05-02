import React, { useState, useEffect } from 'react'
import { LogIn, LogOut, Coffee, Play } from 'lucide-react'
import { Timer } from './Timer'
import { GeoIndicator } from './GeoIndicator'
import { useGeolocation } from '../../hooks/useGeolocation'
import { pointageService } from '../../services/pointage.service'
import { siteService } from '../../services/site.service'
import type { Pointage, Site } from '../../types'

export function PointageWidget() {
  const [pointageEnCours, setPointageEnCours] = useState<Pointage | null>(null)
  const [sites, setSites] = useState<Site[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { position, loading: geoLoading, error: geoError, refresh } = useGeolocation()

  const nearestSite = position ? findNearestSite(position.latitude, position.longitude, sites) : null
  const inZone = nearestSite !== null

  useEffect(() => {
    const init = async () => {
      const [enc, sitesData] = await Promise.all([
        pointageService.enCours(),
        siteService.liste(),
      ])
      setPointageEnCours(enc)
      setSites(sitesData)
    }
    init().catch(console.error)
  }, [])

  const handleArriver = async () => {
    if (!position) { refresh(); return }
    setLoading(true); setError(null)
    try {
      const p = await pointageService.arriver(position.latitude, position.longitude)
      setPointageEnCours(p)
    } catch {
      setError('Erreur lors du pointage arrivée.')
    } finally { setLoading(false) }
  }

  const handlePartir = async () => {
    if (!position) { refresh(); return }
    setLoading(true); setError(null)
    try {
      await pointageService.partir(position.latitude, position.longitude)
      setPointageEnCours(null)
    } catch {
      setError('Erreur lors du pointage départ.')
    } finally { setLoading(false) }
  }

  const handlePause = async () => {
    setLoading(true); setError(null)
    try {
      const p = await pointageService.pause()
      setPointageEnCours(p)
    } catch {
      setError('Erreur lors de la mise en pause.')
    } finally { setLoading(false) }
  }

  const handleReprise = async () => {
    setLoading(true); setError(null)
    try {
      const p = await pointageService.reprise()
      setPointageEnCours(p)
    } catch {
      setError('Erreur lors de la reprise.')
    } finally { setLoading(false) }
  }

  const isEnCours = pointageEnCours?.statut === 'EN_COURS'
  const isEnPause = pointageEnCours?.statut === 'EN_PAUSE'

  return (
    <div className="flex flex-col gap-3.5">
      {/* Indicateur GPS */}
      <GeoIndicator
        position={position}
        loading={geoLoading}
        error={geoError}
        nearestSite={nearestSite?.nom ?? null}
        inZone={inZone}
      />

      {/* Timer */}
      <Timer startTime={(isEnCours || isEnPause) ? pointageEnCours?.heureArrivee ?? null : null} />

      {/* Durée de pause cumulée */}
      {isEnPause && (
        <div className="px-3 py-2 rounded-lg bg-amber-bg border border-amber-border text-amber text-[12px] text-center font-medium">
          En pause · {pointageEnCours?.dureesPauseMinutes ?? 0} min de pause cumulées
        </div>
      )}

      {/* Boutons */}
      <div className="flex flex-col gap-2.5">
        {!isEnCours && !isEnPause ? (
          <button
            onClick={handleArriver}
            disabled={loading || geoLoading}
            className="h-[54px] rounded-[10px] border-2 border-green-border bg-green-bg text-green font-bold text-[14px] flex items-center justify-center gap-2.5 transition-all hover:bg-[#D0F0E0] disabled:opacity-50"
          >
            {loading ? <span className="w-5 h-5 border-2 border-green border-t-transparent rounded-full animate-spin" /> : <LogIn size={18} />}
            Pointer l'arrivée
          </button>
        ) : isEnPause ? (
          <>
            <button
              onClick={handleReprise}
              disabled={loading}
              className="h-[54px] rounded-[10px] border-2 border-green-border bg-green-bg text-green font-bold text-[14px] flex items-center justify-center gap-2.5 transition-all hover:bg-[#D0F0E0] disabled:opacity-50"
            >
              {loading ? <span className="w-5 h-5 border-2 border-green border-t-transparent rounded-full animate-spin" /> : <Play size={18} />}
              Reprendre le travail
            </button>
            <button
              onClick={handlePartir}
              disabled={loading || geoLoading}
              className="h-[54px] rounded-[10px] border-2 border-red-border bg-red-bg text-red font-bold text-[14px] flex items-center justify-center gap-2.5 transition-all hover:bg-[#FFE0E0] disabled:opacity-50"
            >
              {loading ? <span className="w-5 h-5 border-2 border-red border-t-transparent rounded-full animate-spin" /> : <LogOut size={18} />}
              Pointer le départ
            </button>
          </>
        ) : (
          <>
            <button
              onClick={handlePartir}
              disabled={loading || geoLoading}
              className="h-[54px] rounded-[10px] border-2 border-red-border bg-red-bg text-red font-bold text-[14px] flex items-center justify-center gap-2.5 transition-all hover:bg-[#FFE0E0] disabled:opacity-50"
            >
              {loading ? <span className="w-5 h-5 border-2 border-red border-t-transparent rounded-full animate-spin" /> : <LogOut size={18} />}
              Pointer le départ
            </button>
            <button
              onClick={handlePause}
              disabled={loading}
              className="h-[54px] rounded-[10px] border-2 border-amber-border bg-amber-bg text-amber font-bold text-[14px] flex items-center justify-center gap-2.5 transition-all hover:bg-[#FFF0D0] disabled:opacity-50"
            >
              {loading ? <span className="w-5 h-5 border-2 border-amber border-t-transparent rounded-full animate-spin" /> : <Coffee size={18} />}
              Prendre une pause
            </button>
          </>
        )}
      </div>

      {error && (
        <div className="px-3 py-2 rounded-lg bg-red-bg border border-red-border text-red text-[13px]">
          {error}
        </div>
      )}
    </div>
  )
}

function haversine(lat1: number, lon1: number, lat2: number, lon2: number): number {
  const R = 6371000
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLon = (lon2 - lon1) * Math.PI / 180
  const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
}

function findNearestSite(lat: number, lon: number, sites: Site[]): Site | null {
  let nearest: Site | null = null
  let minDist = Infinity
  for (const site of sites) {
    if (!site.geofencingActif) continue
    const d = haversine(lat, lon, site.latitude, site.longitude)
    if (d < site.rayonMetres && d < minDist) { minDist = d; nearest = site }
  }
  return nearest
}

import React from 'react'
import { MapPin, AlertTriangle, Loader } from 'lucide-react'
import type { GeoPosition } from '../../types'

interface GeoIndicatorProps {
  position: GeoPosition | null
  loading: boolean
  error: string | null
  nearestSite?: string | null
  inZone?: boolean
}

export function GeoIndicator({ position, loading, error, nearestSite, inZone }: GeoIndicatorProps) {
  if (loading) {
    return (
      <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-surface2 text-text3 text-[13px]">
        <Loader size={14} className="animate-spin" />
        Localisation en cours...
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-amber-bg border border-amber-border text-amber text-[13px]">
        <AlertTriangle size={14} />
        {error}
      </div>
    )
  }

  if (!position) return null

  if (inZone && nearestSite) {
    return (
      <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-green-bg border border-green-border text-green text-[13px] font-medium">
        <MapPin size={14} />
        Zone détectée : <span className="font-semibold">{nearestSite}</span>
        <span className="text-[11px] ml-auto font-mono text-green/70">{position.accuracy?.toFixed(0)}m précision</span>
      </div>
    )
  }

  return (
    <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-red-bg border border-red-border text-red text-[13px] font-medium">
      <MapPin size={14} />
      Hors zone — Pointage hors site enregistré
      <span className="text-[11px] ml-auto font-mono text-red/70">{position.accuracy?.toFixed(0)}m précision</span>
    </div>
  )
}

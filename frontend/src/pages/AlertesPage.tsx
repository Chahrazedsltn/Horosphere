import React, { useEffect, useState } from 'react'
import { Bell, Check, Checks } from '@phosphor-icons/react'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { LoadingSpinner } from '../components/ui/LoadingSpinner'
import { alerteService } from '../services/alerte.service'
import { useUiStore } from '../store/ui.store'
import type { Alerte } from '../types'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'

const TYPE_LABELS: Record<string, string> = {
  OUBLI_DEPART: 'Oubli de départ',
  HORS_ZONE:    'Pointage hors zone',
  ECART_HORAIRE:'Écart horaire',
}

export default function AlertesPage() {
  const [alertes, setAlertes] = useState<Alerte[]>([])
  const [loading, setLoading] = useState(true)
  const [markingAll, setMarkingAll] = useState(false)
  const { setAlertesNonLues } = useUiStore()

  useEffect(() => {
    alerteService.mesAlertes()
      .then(({ alertes: data, non_lues }) => {
        setAlertes(data)
        setAlertesNonLues(non_lues)
      })
      .finally(() => setLoading(false))
  }, [])

  const handleMarquerLue = async (id: number) => {
    const updated = await alerteService.marquerLue(id)
    setAlertes((prev) => prev.map((a) => a.id === id ? updated : a))
    setAlertesNonLues(alertes.filter((a) => !a.estLue && a.id !== id).length)
  }

  const handleToutLire = async () => {
    setMarkingAll(true)
    try {
      await alerteService.toutLire()
      setAlertes((prev) => prev.map((a) => ({ ...a, estLue: true })))
      setAlertesNonLues(0)
    } finally { setMarkingAll(false) }
  }

  const nonLues = alertes.filter((a) => !a.estLue).length

  if (loading) return <LoadingSpinner />

  return (
    <div className="max-w-2xl space-y-4">
      {/* Header actions */}
      {nonLues > 0 && (
        <div className="flex justify-end">
          <Button
            variant="ghost"
            size="sm"
            icon={<Checks size={14} />}
            loading={markingAll}
            onClick={handleToutLire}
          >
            Tout marquer comme lu
          </Button>
        </div>
      )}

      {alertes.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-20 gap-3 text-text3">
          <Bell size={36} className="opacity-30" />
          <span className="text-[13px]">Aucune alerte pour le moment.</span>
        </div>
      ) : (
        alertes.map((a) => (
          <div
            key={a.id}
            className={`bg-surface border rounded-lg p-4 shadow transition-all ${
              a.estLue ? 'border-border opacity-70' : 'border-l-4 border-l-amber'
            }`}
          >
            <div className="flex items-start gap-3">
              <div className={`w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ${
                a.estLue ? 'bg-surface2 text-text3' : 'bg-amber-bg text-amber'
              }`}>
                <Bell size={16} />
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                  <span className="text-[11px] font-semibold uppercase tracking-wide text-text3">
                    {TYPE_LABELS[a.typeAlerte] ?? a.typeAlerte}
                  </span>
                  {!a.estLue && (
                    <span className="w-2 h-2 rounded-full bg-amber flex-shrink-0" />
                  )}
                </div>
                <p className="text-[13px] text-text">{a.message}</p>
                <div className="flex items-center gap-3 mt-2">
                  <span className="text-[11px] text-text3 font-mono">
                    {format(new Date(a.dateCreation), 'dd MMM yyyy à HH:mm', { locale: fr })}
                  </span>
                  {a.utilisateur && (
                    <span className="text-[11px] text-text3">
                      — {a.utilisateur.prenom} {a.utilisateur.nom}
                    </span>
                  )}
                </div>
              </div>
              {!a.estLue && (
                <button
                  onClick={() => handleMarquerLue(a.id)}
                  title="Marquer comme lue"
                  className="w-7 h-7 flex items-center justify-center rounded-md text-text3 hover:bg-surface2 hover:text-green transition-colors flex-shrink-0"
                >
                  <Check size={15} />
                </button>
              )}
            </div>
          </div>
        ))
      )}
    </div>
  )
}

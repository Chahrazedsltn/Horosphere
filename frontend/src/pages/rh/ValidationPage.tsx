import React, { useEffect, useState } from 'react'
import { CheckCircle, XCircle } from 'lucide-react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { StatutDemandeBadge, TypeDemandeBadge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { demandeService } from '../../services/demande.service'
import type { Demande } from '../../types'
import { format } from 'date-fns'

export default function ValidationPage() {
  const [demandes, setDemandes] = useState<Demande[]>([])
  const [loading, setLoading] = useState(true)
  const [processing, setProcessing] = useState<number | null>(null)
  const [filter, setFilter] = useState('ALL')

  useEffect(() => {
    demandeService.liste()
      .then(setDemandes)
      .finally(() => setLoading(false))
  }, [])

  const handle = async (id: number, decision: 'APPROUVEE' | 'REJETEE') => {
    setProcessing(id)
    try {
      const updated = await demandeService.traiter(id, decision)
      setDemandes((prev) => prev.map((d) => d.id === id ? updated : d))
    } finally { setProcessing(null) }
  }

  const filtered = demandes.filter((d) => filter === 'ALL' || d.statut === filter)

  if (loading) return <LoadingSpinner />

  return (
    <div className="space-y-5">
      {/* Filtres */}
      <div className="flex gap-2 flex-wrap">
        {[['ALL', 'Toutes'], ['EN_ATTENTE', 'En attente'], ['APPROUVEE', 'Approuvées'], ['REJETEE', 'Rejetées']].map(([val, label]) => (
          <button
            key={val}
            onClick={() => setFilter(val)}
            className={`px-3 py-1.5 rounded-md text-[12px] font-medium transition-colors ${
              filter === val ? 'bg-accent text-white' : 'bg-surface border border-border text-text2 hover:bg-surface2'
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      {filtered.length === 0 ? (
        <div className="text-center py-16 text-text3 text-[13px]">Aucune demande.</div>
      ) : (
        filtered.map((d) => (
          <div
            key={d.id}
            className={`bg-surface border rounded-lg p-4 shadow ${
              d.statut === 'EN_ATTENTE' ? 'border-l-4 border-l-amber' : 'border-border'
            }`}
          >
            <div className="flex items-start gap-3">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <span className="text-[13px] font-semibold text-text">
                    {d.utilisateur?.prenom} {d.utilisateur?.nom}
                  </span>
                  <TypeDemandeBadge type={d.typeDemande} />
                  <StatutDemandeBadge statut={d.statut} />
                  <span className="text-[11px] text-text3 font-mono ml-auto">
                    {format(new Date(d.dateCreation), 'dd/MM/yyyy')}
                  </span>
                </div>
                <div className="text-[13px] text-text2">
                  Du <span className="font-semibold text-text">{format(new Date(d.dateDebut), 'dd/MM/yyyy')}</span> au{' '}
                  <span className="font-semibold text-text">{format(new Date(d.dateFin), 'dd/MM/yyyy')}</span>
                  <span className="text-text3 ml-2 text-[12px]">({d.dureeJours} jour(s))</span>
                </div>
                {d.motif && <p className="text-[12px] text-text3 mt-1 italic">"{d.motif}"</p>}
              </div>

              {d.statut === 'EN_ATTENTE' && (
                <div className="flex gap-2 flex-shrink-0">
                  <Button
                    variant="success"
                    size="sm"
                    icon={<CheckCircle size={14} />}
                    loading={processing === d.id}
                    onClick={() => handle(d.id, 'APPROUVEE')}
                  >
                    Approuver
                  </Button>
                  <Button
                    variant="danger"
                    size="sm"
                    icon={<XCircle size={14} />}
                    loading={processing === d.id}
                    onClick={() => handle(d.id, 'REJETEE')}
                  >
                    Rejeter
                  </Button>
                </div>
              )}
            </div>
          </div>
        ))
      )}
    </div>
  )
}

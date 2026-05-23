import React from 'react'

type BadgeVariant = 'green' | 'red' | 'amber' | 'blue' | 'gray'

interface BadgeProps {
  variant?: BadgeVariant
  children: React.ReactNode
  dot?: boolean
  className?: string
}

const variantStyles: Record<BadgeVariant, string> = {
  green: 'bg-green-bg text-green border border-green-border',
  red:   'bg-red-bg text-red border border-red-border',
  amber: 'bg-amber-bg text-amber border border-amber-border',
  blue:  'bg-accent-light text-accent border border-accent-mid',
  gray:  'bg-surface2 text-text3 border border-border',
}

export function Badge({ variant = 'blue', children, dot = false, className = '' }: BadgeProps) {
  return (
    <span
      className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold font-mono ${variantStyles[variant]} ${className}`}
    >
      {dot && <span className="text-[7px]">●</span>}
      {children}
    </span>
  )
}

// Helpers pour les statuts métier
export function StatutPointageBadge({ statut }: { statut: string }) {
  const map: Record<string, { label: string; variant: BadgeVariant }> = {
    VALIDE:    { label: 'Validé',    variant: 'green' },
    ANOMALIE:  { label: 'Anomalie',  variant: 'red'   },
    HORS_ZONE: { label: 'Hors zone', variant: 'amber' },
    EN_COURS:  { label: 'En cours',  variant: 'blue'  },
  }
  const { label, variant } = map[statut] ?? { label: statut, variant: 'gray' }
  return <Badge variant={variant} dot>{label}</Badge>
}

export function StatutDemandeBadge({ statut }: { statut: string }) {
  const map: Record<string, { label: string; variant: BadgeVariant }> = {
    EN_ATTENTE: { label: 'En attente', variant: 'amber' },
    APPROUVEE:  { label: 'Approuvée',  variant: 'green' },
    REJETEE:    { label: 'Rejetée',    variant: 'red'   },
  }
  const { label, variant } = map[statut] ?? { label: statut, variant: 'gray' }
  return <Badge variant={variant} dot>{label}</Badge>
}

export function TypeDemandeBadge({ type }: { type: string }) {
  const map: Record<string, { label: string; variant: BadgeVariant }> = {
    CONGE:      { label: 'Congé',      variant: 'blue'  },
    CORRECTION: { label: 'Correction', variant: 'amber' },
    ABSENCE:    { label: 'Absence',    variant: 'red'   },
    AUTRE:      { label: 'Autre',      variant: 'gray'  },
  }
  const { label, variant } = map[type] ?? { label: type, variant: 'gray' }
  return <Badge variant={variant}>{label}</Badge>
}

export function RoleBadge({ role }: { role: string }) {
  const map: Record<string, { label: string; variant: BadgeVariant }> = {
    ADMIN: { label: 'Admin', variant: 'red'   },
    RH:    { label: 'RH',    variant: 'amber' },
    AGENT: { label: 'Agent', variant: 'blue'  },
  }
  const { label, variant } = map[role] ?? { label: role, variant: 'gray' }
  return <Badge variant={variant}>{label}</Badge>
}

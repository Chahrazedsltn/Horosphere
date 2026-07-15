import React from 'react'

interface StatCardProps {
  label: string
  value: string | number
  sub?: string
  color?: 'default' | 'green' | 'red' | 'amber' | 'blue'
  onClick?: () => void
}

const colorMap = {
  default: { value: 'var(--text)',  badge: 'var(--surface2)', badgeText: 'var(--text3)',  glow: 'transparent' },
  blue:    { value: 'var(--accent)', badge: 'var(--accent-light)', badgeText: 'var(--accent)', glow: 'var(--accent-light)' },
  green:   { value: 'var(--green)',  badge: 'var(--green-bg)',     badgeText: 'var(--green)',  glow: 'var(--green-bg)' },
  red:     { value: 'var(--red)',    badge: 'var(--red-bg)',       badgeText: 'var(--red)',    glow: 'var(--red-bg)' },
  amber:   { value: 'var(--amber)',  badge: 'var(--amber-bg)',     badgeText: 'var(--amber)',  glow: 'var(--amber-bg)' },
}

export function StatCard({ label, value, sub, color = 'default', onClick }: StatCardProps) {
  const c = colorMap[color]

  return (
    <div
      onClick={onClick}
      className={`bg-surface border border-border rounded-2xl p-5 relative overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg ${onClick ? 'cursor-pointer' : ''}`}
      style={{ borderColor: color !== 'default' ? `var(--${color === 'blue' ? 'accent' : color}-border, var(--border))` : undefined }}
    >
      {/* Subtle glow accent */}
      <div
        className="absolute top-0 right-0 w-16 h-16 rounded-full -translate-y-1/2 translate-x-1/2"
        style={{ background: c.glow, filter: 'blur(20px)', opacity: 0.6 }}
      />

      <div className="text-[11px] font-semibold uppercase tracking-[0.7px] mb-2" style={{ color: 'var(--text3)' }}>
        {label}
      </div>
      <div className="text-[30px] font-bold leading-none tracking-tight mb-2" style={{ color: c.value }}>
        {value}
      </div>
      {sub && (
        <div
          className="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-md"
          style={{ background: c.badge, color: c.badgeText }}
        >
          {sub}
        </div>
      )}
    </div>
  )
}

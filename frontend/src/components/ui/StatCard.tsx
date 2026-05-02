import React from 'react'

interface StatCardProps {
  label: string
  value: string | number
  sub?: string
  icon?: string
  color?: 'default' | 'green' | 'red' | 'amber' | 'blue'
}

const colorMap = {
  default: 'text-text',
  green:   'text-green',
  red:     'text-red',
  amber:   'text-amber',
  blue:    'text-accent',
}

export function StatCard({ label, value, sub, color = 'default' }: StatCardProps) {
  return (
    <div className="bg-surface border border-border rounded-lg p-4 shadow">
      <div className={`text-[28px] font-bold font-mono leading-none ${colorMap[color]}`}>
        {value}
      </div>
      <div className="text-[11px] font-semibold text-text3 uppercase tracking-wide mt-1.5">
        {label}
      </div>
      {sub && <div className="text-[12px] text-text2 mt-1">{sub}</div>}
    </div>
  )
}

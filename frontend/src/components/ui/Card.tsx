import React from 'react'

interface CardProps {
  title?: string
  icon?: React.ReactNode
  action?: React.ReactNode
  children: React.ReactNode
  className?: string
  noPadding?: boolean
}

export function Card({ title, icon, action, children, className = '', noPadding = false }: CardProps) {
  return (
    <div className={`bg-surface border border-border rounded-2xl shadow transition-all duration-200 hover:shadow-lg hover:border-border2 ${className}`}>
      {title && (
        <div className="flex items-center gap-2 px-5 py-3.5 border-b border-border">
          {icon && <span style={{ color: 'var(--text3)' }}>{icon}</span>}
          <span className="text-[13px] font-semibold" style={{ color: 'var(--text)' }}>{title}</span>
          {action && <div className="ml-auto">{action}</div>}
        </div>
      )}
      <div className={noPadding ? '' : 'p-5'}>
        {children}
      </div>
    </div>
  )
}

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
    <div className={`bg-surface border border-border rounded-lg shadow ${className}`}>
      {title && (
        <div className="flex items-center gap-2 px-4 py-3 border-b border-border">
          {icon && <span className="text-text3">{icon}</span>}
          <span className="text-[13px] font-semibold text-text">{title}</span>
          {action && <div className="ml-auto">{action}</div>}
        </div>
      )}
      <div className={noPadding ? '' : 'p-4'}>
        {children}
      </div>
    </div>
  )
}

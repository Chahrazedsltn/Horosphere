import React from 'react'

interface LoadingSpinnerProps {
  size?: 'sm' | 'md' | 'lg'
  fullPage?: boolean
  text?: string
}

const sizeMap = { sm: 'w-5 h-5', md: 'w-8 h-8', lg: 'w-12 h-12' }

export function LoadingSpinner({ size = 'md', fullPage = false, text }: LoadingSpinnerProps) {
  const spinner = (
    <div className="flex flex-col items-center gap-3">
      <div className={`${sizeMap[size]} border-3 border-accent-light border-t-accent rounded-full animate-spin`}
           style={{ borderWidth: '3px' }} />
      {text && <p className="text-[13px] text-text3">{text}</p>}
    </div>
  )

  if (fullPage) {
    return (
      <div className="fixed inset-0 flex items-center justify-center bg-bg">
        {spinner}
      </div>
    )
  }

  return (
    <div className="flex items-center justify-center p-8">
      {spinner}
    </div>
  )
}

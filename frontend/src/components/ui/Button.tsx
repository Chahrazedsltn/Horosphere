import React from 'react'

type ButtonVariant = 'primary' | 'success' | 'danger' | 'ghost' | 'outline'
type ButtonSize = 'sm' | 'md' | 'lg'

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant
  size?: ButtonSize
  loading?: boolean
  icon?: React.ReactNode
  children?: React.ReactNode
}

const variantStyles: Record<ButtonVariant, string> = {
  primary: 'bg-accent text-white hover:bg-[#2E2EAA] active:bg-[#252588]',
  success: 'bg-green-bg text-green border border-green-border hover:bg-[#D0F0E0]',
  danger:  'bg-red-bg text-red border border-red-border hover:bg-[#FFE0E0]',
  ghost:   'bg-surface2 text-text2 border border-border hover:bg-[#E4E4EC]',
  outline: 'bg-transparent text-accent border border-accent hover:bg-accent-light',
}

const sizeStyles: Record<ButtonSize, string> = {
  sm: 'px-3 py-1.5 text-xs rounded-md',
  md: 'px-4 py-2 text-[13px] rounded-lg',
  lg: 'px-6 py-3.5 text-[15px] rounded-xl',
}

export function Button({
  variant = 'primary',
  size = 'md',
  loading = false,
  icon,
  children,
  disabled,
  className = '',
  ...props
}: ButtonProps) {
  return (
    <button
      disabled={disabled || loading}
      className={`
        inline-flex items-center justify-center gap-2
        font-semibold font-sans cursor-pointer
        transition-all duration-150
        disabled:opacity-50 disabled:cursor-not-allowed
        ${variantStyles[variant]}
        ${sizeStyles[size]}
        ${className}
      `}
      {...props}
    >
      {loading ? (
        <span className="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin" />
      ) : icon}
      {children}
    </button>
  )
}

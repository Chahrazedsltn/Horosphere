import React from 'react'

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string
  error?: string
  wrapperClass?: string
}

export function Input({ label, error, wrapperClass = '', className = '', ...props }: InputProps) {
  return (
    <div className={`mb-3.5 ${wrapperClass}`}>
      {label && (
        <label className="block text-[12px] font-semibold text-text2 mb-1.5">
          {label}
        </label>
      )}
      <input
        className={`
          w-full h-10 bg-surface border-[1.5px] rounded-md px-3.5
          text-[13.5px] text-text font-sans outline-none
          transition-colors duration-150
          placeholder:text-text3
          focus:border-accent-mid
          ${error ? 'border-red-border' : 'border-border'}
          ${className}
        `}
        {...props}
      />
      {error && <p className="text-[11px] text-red mt-1">{error}</p>}
    </div>
  )
}

interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label?: string
  error?: string
  wrapperClass?: string
  children: React.ReactNode
}

export function Select({ label, error, wrapperClass = '', className = '', children, ...props }: SelectProps) {
  return (
    <div className={`mb-3.5 ${wrapperClass}`}>
      {label && (
        <label className="block text-[12px] font-semibold text-text2 mb-1.5">
          {label}
        </label>
      )}
      <select
        className={`
          w-full h-10 bg-surface border-[1.5px] rounded-md px-3.5
          text-[13.5px] text-text font-sans outline-none cursor-pointer
          transition-colors duration-150
          focus:border-accent-mid
          ${error ? 'border-red-border' : 'border-border'}
          ${className}
        `}
        {...props}
      >
        {children}
      </select>
      {error && <p className="text-[11px] text-red mt-1">{error}</p>}
    </div>
  )
}

interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string
  error?: string
  wrapperClass?: string
}

export function Textarea({ label, error, wrapperClass = '', className = '', ...props }: TextareaProps) {
  return (
    <div className={`mb-3.5 ${wrapperClass}`}>
      {label && (
        <label className="block text-[12px] font-semibold text-text2 mb-1.5">
          {label}
        </label>
      )}
      <textarea
        className={`
          w-full bg-surface border-[1.5px] rounded-md p-3
          text-[13.5px] text-text font-sans outline-none resize-y
          transition-colors duration-150 min-h-[80px]
          placeholder:text-text3
          focus:border-accent-mid
          ${error ? 'border-red-border' : 'border-border'}
          ${className}
        `}
        {...props}
      />
      {error && <p className="text-[11px] text-red mt-1">{error}</p>}
    </div>
  )
}

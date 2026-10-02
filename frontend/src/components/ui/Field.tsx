import clsx from 'clsx'
import type { InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react'
import type { Option } from '@/types'

interface WrapperProps {
  label?: string
  error?: string
  hint?: string
  required?: boolean
  className?: string
  children: ReactNode
}

/** Envolve qualquer input com rótulo, dica e mensagem de erro padronizados. */
export function Field({ label, error, hint, required, className, children }: WrapperProps) {
  return (
    <label className={clsx('block', className)}>
      {label && (
        <span className="label">
          {label} {required && <span className="text-red-500">*</span>}
        </span>
      )}
      {children}
      {hint && !error && <span className="mt-1 block text-xs text-slate-400">{hint}</span>}
      {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
    </label>
  )
}

type InputProps = InputHTMLAttributes<HTMLInputElement> & { label?: string; error?: string; hint?: string }

export function TextInput({ label, error, hint, className, required, ...rest }: InputProps) {
  return (
    <Field label={label} error={error} hint={hint} required={required} className={className}>
      <input {...rest} required={required} className={clsx('input', error && 'border-red-400')} />
    </Field>
  )
}

type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement> & { label?: string; error?: string; hint?: string }

export function TextArea({ label, error, hint, className, required, rows = 3, ...rest }: TextareaProps) {
  return (
    <Field label={label} error={error} hint={hint} required={required} className={className}>
      <textarea {...rest} rows={rows} required={required} className={clsx('input', error && 'border-red-400')} />
    </Field>
  )
}

type SelectProps = SelectHTMLAttributes<HTMLSelectElement> & {
  label?: string
  error?: string
  hint?: string
  options: (Option | { value: string | number; label: string })[]
  placeholder?: string
}

export function Select({ label, error, hint, options, placeholder, className, required, ...rest }: SelectProps) {
  return (
    <Field label={label} error={error} hint={hint} required={required} className={className}>
      <select {...rest} required={required} className={clsx('input', error && 'border-red-400')}>
        {placeholder !== undefined && <option value="">{placeholder}</option>}
        {options.map((o) => (
          <option key={o.value} value={o.value}>
            {o.label}
          </option>
        ))}
      </select>
    </Field>
  )
}

import clsx from 'clsx'
import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'

const accents = {
  blue: 'text-brand-700 bg-brand-50',
  amber: 'text-amber-700 bg-amber-50',
  red: 'text-red-700 bg-red-50',
  emerald: 'text-emerald-700 bg-emerald-50',
  violet: 'text-violet-700 bg-violet-50',
  slate: 'text-slate-700 bg-slate-100',
  orange: 'text-orange-700 bg-orange-50',
}

/** Card de indicador (KPI). Opcionalmente clicável, levando à lista já filtrada. */
export function StatCard({ label, value, icon, accent = 'blue', to, hint }: {
  label: string
  value: ReactNode
  icon: ReactNode
  accent?: keyof typeof accents
  to?: string
  hint?: string
}) {
  const content = (
    <div className="card flex h-full items-center gap-3 p-3 transition-shadow hover:shadow-md sm:p-4">
      <div className={clsx('flex size-9 shrink-0 items-center justify-center rounded-xl sm:size-11', accents[accent])}>{icon}</div>
      <div className="min-w-0">
        <div className="whitespace-nowrap text-xl font-bold leading-tight text-slate-800 sm:text-2xl">{value}</div>
        <div className="text-[11px] font-medium uppercase leading-tight tracking-wide text-slate-500 sm:text-xs">{label}</div>
        {hint && <div className="text-[11px] text-slate-400">{hint}</div>}
      </div>
    </div>
  )
  return to ? <Link to={to}>{content}</Link> : content
}

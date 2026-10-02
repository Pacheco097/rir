import clsx from 'clsx'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { StatusBadge } from '@/components/gaps/badges'
import { Button } from '@/components/ui/Button'
import { visibleRange, type CalendarMode } from '@/lib/calendar'
import { toIsoDate } from '@/lib/format'
import type { Meeting } from '@/types'


const WEEKDAYS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']

function shift(mode: CalendarMode, ref: Date, dir: number): Date {
  const d = new Date(ref)
  if (mode === 'month') d.setMonth(d.getMonth() + dir)
  else d.setDate(d.getDate() + (mode === 'week' ? 7 * dir : dir))
  return d
}

const statusDot: Record<string, string> = {
  agendada: 'bg-blue-500', confirmada: 'bg-indigo-500', realizada: 'bg-emerald-500', cancelada: 'bg-slate-400', reagendada: 'bg-amber-500',
}

export function CalendarView({ mode, refDate, meetings, onModeChange, onDateChange }: {
  mode: CalendarMode
  refDate: Date
  meetings: Meeting[]
  onModeChange: (m: CalendarMode) => void
  onDateChange: (d: Date) => void
}) {
  const { from, to } = visibleRange(mode, refDate)
  const days: Date[] = []
  for (const d = new Date(from); d <= to; d.setDate(d.getDate() + 1)) days.push(new Date(d))

  const byDay = meetings.reduce<Record<string, Meeting[]>>((acc, m) => {
    ;(acc[m.date] ??= []).push(m)
    return acc
  }, {})
  const [today] = useState(() => toIsoDate(new Date()))

  const rawTitle = mode === 'month'
    ? refDate.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
    : mode === 'week'
      ? `${from.toLocaleDateString('pt-BR')} – ${to.toLocaleDateString('pt-BR')}`
      : refDate.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
  // Só a primeira letra maiúscula ("Outubro de 2026"); o CSS "capitalize" faria "Outubro De 2026".
  const title = rawTitle.charAt(0).toUpperCase() + rawTitle.slice(1)

  return (
    <div className="card p-4">
      <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <Button variant="secondary" size="sm" onClick={() => onDateChange(shift(mode, refDate, -1))} aria-label="Anterior"><ChevronLeft className="size-4" /></Button>
          <Button variant="secondary" size="sm" onClick={() => onDateChange(new Date())}>Hoje</Button>
          <Button variant="secondary" size="sm" onClick={() => onDateChange(shift(mode, refDate, 1))} aria-label="Próximo"><ChevronRight className="size-4" /></Button>
          <h2 className="ml-2 font-semibold text-slate-700">{title}</h2>
        </div>
        <div className="flex rounded-lg border border-slate-300 p-0.5">
          {(['month', 'week', 'day'] as const).map((m) => (
            <button key={m} onClick={() => onModeChange(m)}
              className={clsx('rounded-md px-3 py-1 text-xs font-medium', mode === m ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100')}>
              {{ month: 'Mensal', week: 'Semanal', day: 'Diária' }[m]}
            </button>
          ))}
        </div>
      </div>

      {mode === 'day' ? (
        <div className="space-y-2">
          {(byDay[toIsoDate(refDate)] ?? []).length === 0 && <p className="py-8 text-center text-sm text-slate-400">Nenhuma reunião neste dia.</p>}
          {(byDay[toIsoDate(refDate)] ?? []).map((m) => (
            <Link key={m.id} to={`/meetings/${m.id}`} className="flex items-center gap-4 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
              <div className="w-24 text-sm font-semibold text-slate-700">{m.start_time}{m.end_time && ` – ${m.end_time}`}</div>
              <div className="flex-1">
                <div className="text-sm font-medium text-slate-700">{m.title}</div>
                <div className="text-xs text-slate-400">{m.sector?.name} · Líder: {m.leader?.name ?? '—'} · TI: {m.it_owner?.name ?? '—'}</div>
              </div>
              <StatusBadge status={m.status} />
            </Link>
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-7 overflow-hidden rounded-lg border border-slate-200 bg-slate-200 gap-px">
          {WEEKDAYS.map((w) => <div key={w} className="bg-slate-50 py-1.5 text-center text-xs font-semibold text-slate-500">{w}</div>)}
          {days.map((d) => {
            const iso = toIsoDate(d)
            const outside = mode === 'month' && d.getMonth() !== refDate.getMonth()
            return (
              <div key={iso} className={clsx('bg-white p-1.5', mode === 'month' ? 'min-h-24' : 'min-h-64', outside && 'bg-slate-50 text-slate-400')}>
                <button onClick={() => { onDateChange(d); onModeChange('day') }}
                  className={clsx('mb-1 flex size-6 items-center justify-center rounded-full text-xs', iso === today ? 'bg-brand-600 font-bold text-white' : 'hover:bg-slate-100')}>
                  {d.getDate()}
                </button>
                <div className="space-y-1">
                  {(byDay[iso] ?? []).map((m) => (
                    <Link key={m.id} to={`/meetings/${m.id}`} title={m.title}
                      className={clsx('flex items-center gap-1 truncate rounded px-1 py-0.5 text-[11px] hover:bg-slate-100', m.status.value === 'cancelada' && 'line-through opacity-60')}>
                      <span className={clsx('size-1.5 shrink-0 rounded-full', statusDot[m.status.value])} />
                      <span className="font-medium">{m.start_time}</span> <span className="truncate">{m.sector?.name}</span>
                    </Link>
                  ))}
                </div>
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}

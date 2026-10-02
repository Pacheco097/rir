import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import clsx from 'clsx'
import { CalendarClock, ListChecks, MessageSquare } from 'lucide-react'
import { useState, type DragEvent } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { GapFilters, type Filters } from '@/components/gaps/GapFilters'
import { PriorityBadge } from '@/components/gaps/badges'
import { Button } from '@/components/ui/Button'
import { ErrorBox, PageHeader, Spinner } from '@/components/ui/Card'
import { TextArea } from '@/components/ui/Field'
import { Modal } from '@/components/ui/Modal'
import { useToast } from '@/components/ui/useToast'
import { api, errorMessage } from '@/lib/api'
import { formatShortDate, initials } from '@/lib/format'
import type { GapListItem, KanbanColumnData } from '@/types'

const columnAccent: Record<string, string> = {
  backlog: 'border-t-slate-400', a_fazer: 'border-t-blue-500', em_andamento: 'border-t-violet-500',
  bloqueado: 'border-t-red-500', homologacao: 'border-t-amber-500', concluido: 'border-t-emerald-500',
}

interface PendingMove { gap: GapListItem; column: string }

export function KanbanPage() {
  const { can } = useAuth()
  const qc = useQueryClient()
  const toast = useToast()
  const [params, setParams] = useSearchParams()
  const filters: Filters = Object.fromEntries(params.entries())
  const [dragOver, setDragOver] = useState<string | null>(null)
  const [pending, setPending] = useState<PendingMove | null>(null)
  const [form, setForm] = useState<Record<string, string>>({})
  const [error, setError] = useState<string | null>(null)
  const canMove = can('gaps.transition')
  const queryKey = ['kanban', filters]

  const { data: columns, isLoading } = useQuery({
    queryKey,
    queryFn: async () => (await api.get<KanbanColumnData[]>('/kanban', { params: filters })).data,
  })

  const move = useMutation({
    mutationFn: async ({ gap, column, body }: PendingMove & { body?: Record<string, string> }) => {
      // Homologação exige solução + evidência: usa o endpoint dedicado.
      if (column === 'homologacao') return api.post(`/gaps/${gap.id}/homologation/send`, body)
      return api.post(`/kanban/${gap.id}/move`, { column, reason: body?.reason })
    },
    // Atualização otimista: move o card antes da resposta da API.
    onMutate: async ({ gap, column }) => {
      await qc.cancelQueries({ queryKey })
      const previous = qc.getQueryData<KanbanColumnData[]>(queryKey)
      qc.setQueryData<KanbanColumnData[]>(queryKey, (cols) => cols?.map((c) => ({
        ...c,
        gaps: c.key === column ? [gap, ...c.gaps.filter((g) => g.id !== gap.id)] : c.gaps.filter((g) => g.id !== gap.id),
      })))
      return { previous }
    },
    onError: (err, _vars, ctx) => {
      qc.setQueryData(queryKey, ctx?.previous) // desfaz o movimento
      toast(errorMessage(err), 'error')
    },
    onSuccess: () => {
      setPending(null)
      qc.invalidateQueries({ queryKey: ['gaps'] })
      qc.invalidateQueries({ queryKey: ['dashboard'] })
    },
    onSettled: () => qc.invalidateQueries({ queryKey: ['kanban'] }),
  })

  const onDrop = (e: DragEvent, column: string) => {
    e.preventDefault()
    setDragOver(null)
    const gapId = Number(e.dataTransfer.getData('text/plain'))
    const gap = columns?.flatMap((c) => c.gaps).find((g) => g.id === gapId)
    const from = columns?.find((c) => c.gaps.some((g) => g.id === gapId))?.key
    if (!gap || from === column) return

    if (column === 'concluido') {
      toast('Para concluir, o setor precisa aprovar a homologação (Regra 1).', 'info')
      return
    }
    // Colunas que precisam de informação extra abrem um modal antes de mover.
    if (column === 'bloqueado' || column === 'homologacao') {
      setError(null)
      setForm({})
      setPending({ gap, column })
      return
    }
    move.mutate({ gap, column })
  }

  const confirmPending = () => {
    if (!pending) return
    setError(null)
    move.mutate({ ...pending, body: form }, { onError: (err) => setError(errorMessage(err)) })
  }

  return (
    <div className="flex h-full flex-col gap-4">
      <PageHeader title="Kanban de GAPs" subtitle={canMove ? 'Arraste os cards entre as colunas para atualizar o status.' : 'Visualização do andamento.'} />
      <GapFilters filters={filters} onChange={(f) => setParams(f)} showStatus={false} />

      {isLoading ? <Spinner /> : (
        <div className="flex min-h-0 flex-1 gap-3 overflow-x-auto pb-2">
          {columns?.map((col) => (
            <div
              key={col.key}
              onDragOver={(e) => { if (canMove) { e.preventDefault(); setDragOver(col.key) } }}
              onDragLeave={() => setDragOver(null)}
              onDrop={(e) => onDrop(e, col.key)}
              className={clsx('flex w-72 shrink-0 flex-col rounded-xl border-t-4 bg-slate-200/60', columnAccent[col.key], dragOver === col.key && 'ring-2 ring-brand-500')}
            >
              <div className="flex items-center justify-between px-3 py-2">
                <h3 className="text-sm font-semibold text-slate-700">{col.label}</h3>
                <span className="rounded-full bg-white px-2 text-xs font-semibold text-slate-600">{col.gaps.length}</span>
              </div>
              <div className="flex-1 space-y-2 overflow-y-auto px-2 pb-2" style={{ maxHeight: 'calc(100vh - 320px)' }}>
                {col.gaps.map((g) => (
                  <div
                    key={g.id}
                    draggable={canMove}
                    onDragStart={(e) => e.dataTransfer.setData('text/plain', String(g.id))}
                    className={clsx('rounded-lg border border-slate-200 bg-white p-3 shadow-sm', canMove && 'cursor-grab active:cursor-grabbing', g.is_overdue && 'border-l-4 border-l-red-500')}
                  >
                    <div className="flex items-center justify-between gap-2">
                      <Link to={`/gaps/${g.id}`} className="font-mono text-xs font-semibold text-brand-700 hover:underline">{g.code}</Link>
                      <PriorityBadge priority={g.priority} />
                    </div>
                    <Link to={`/gaps/${g.id}`} className="mt-1 block text-sm font-medium leading-snug text-slate-700 hover:text-brand-700">{g.title}</Link>
                    <div className="mt-1 text-xs text-slate-400">{g.sector?.name} · {g.status.label}</div>
                    <div className="mt-2 flex items-center justify-between text-xs text-slate-500">
                      <div className="flex items-center gap-2">
                        {g.sprint && <span className="rounded bg-slate-100 px-1.5">S{g.sprint.number}</span>}
                        {g.due_date && (
                          <span className={clsx('inline-flex items-center gap-0.5', g.is_overdue && 'font-semibold text-red-600')}>
                            <CalendarClock className="size-3" /> {formatShortDate(g.due_date)}
                          </span>
                        )}
                        {g.tasks_count ? <span className="inline-flex items-center gap-0.5"><ListChecks className="size-3" />{g.tasks_count}</span> : null}
                        {g.comments_count ? <span className="inline-flex items-center gap-0.5"><MessageSquare className="size-3" />{g.comments_count}</span> : null}
                      </div>
                      {g.it_owner && (
                        <span title={g.it_owner.name} className="flex size-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-semibold text-slate-600">
                          {initials(g.it_owner.name)}
                        </span>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}

      <Modal
        open={pending !== null}
        title={pending?.column === 'bloqueado' ? `Bloquear ${pending.gap.code}` : `Enviar ${pending?.gap.code} para homologação`}
        onClose={() => setPending(null)}
        footer={
          <>
            <Button variant="secondary" onClick={() => setPending(null)}>Cancelar</Button>
            <Button loading={move.isPending} onClick={confirmPending}>Confirmar</Button>
          </>
        }
      >
        {error && <ErrorBox message={error} />}
        {pending?.column === 'bloqueado' ? (
          <TextArea label="Motivo do bloqueio" required value={form.reason ?? ''} onChange={(e) => setForm({ reason: e.target.value })}
            placeholder="Ex.: aguardando liberação da TOTVS" />
        ) : (
          <>
            <TextArea label="Solução aplicada" required rows={4} value={form.solution ?? ''} onChange={(e) => setForm((f) => ({ ...f, solution: e.target.value }))} />
            <TextArea label="Evidência" required value={form.solution_evidence ?? ''} onChange={(e) => setForm((f) => ({ ...f, solution_evidence: e.target.value }))} />
          </>
        )}
      </Modal>
    </div>
  )
}

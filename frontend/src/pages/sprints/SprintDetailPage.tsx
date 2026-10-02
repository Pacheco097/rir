import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Plus, X } from 'lucide-react'
import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { PriorityBadge, StatusBadge } from '@/components/gaps/badges'
import { Button } from '@/components/ui/Button'
import { EmptyState, PageHeader, Spinner } from '@/components/ui/Card'
import { Select } from '@/components/ui/Field'
import { Modal } from '@/components/ui/Modal'
import { useToast } from '@/components/ui/useToast'
import { useLookups } from '@/hooks/useLookups'
import { api, errorMessage } from '@/lib/api'
import { formatDate } from '@/lib/format'
import type { GapListItem, Paginated, Sprint } from '@/types'

/** Agrupa os GAPs da sprint nas etapas do fluxo Scrum. */
const stages = [
  { label: 'Sprint (planejado)', statuses: ['priorizado', 'backlog', 'planejado', 'reaberto', 'triagem', 'analise', 'levantado'] },
  { label: 'Em execução', statuses: ['em_execucao', 'bloqueado'] },
  { label: 'Homologação', statuses: ['homologacao'] },
  { label: 'Concluído', statuses: ['concluido', 'cancelado'] },
]

export function SprintDetailPage() {
  const { id } = useParams()
  const { can, user } = useAuth()
  const { data: lk } = useLookups()
  const qc = useQueryClient()
  const toast = useToast()
  const [adding, setAdding] = useState(false)
  const [selected, setSelected] = useState<number[]>([])

  const { data: sprint, isLoading } = useQuery({ queryKey: ['sprint', id], queryFn: async () => (await api.get<Sprint>(`/sprints/${id}`)).data })
  // Backlog disponível: GAPs abertos sem sprint.
  const { data: backlog } = useQuery({
    queryKey: ['gaps', { sprint_id: 'none', open: '1', type: 'gap', per_page: 100 }],
    queryFn: async () => (await api.get<Paginated<GapListItem>>('/gaps', { params: { sprint_id: 'none', open: 1, type: 'gap', per_page: 100, sort: 'priority' } })).data,
    enabled: adding,
  })

  const onDone = (data: Sprint) => {
    qc.setQueryData(['sprint', id], data)
    qc.invalidateQueries({ queryKey: ['sprints'] })
    qc.invalidateQueries({ queryKey: ['gaps'] })
  }
  const assign = useMutation({
    mutationFn: async () => (await api.post<Sprint>(`/sprints/${id}/gaps`, { gap_ids: selected })).data,
    onSuccess: (d) => { onDone(d); setAdding(false); setSelected([]); toast('GAPs adicionados à sprint.') },
    onError: (e) => toast(errorMessage(e), 'error'),
  })
  const remove = useMutation({
    mutationFn: async (gapId: number) => (await api.delete<Sprint>(`/sprints/${id}/gaps/${gapId}`)).data,
    onSuccess: onDone,
  })
  const setStatus = useMutation({
    mutationFn: async (status: string) => (await api.patch<Sprint>(`/sprints/${id}`, { status })).data,
    onSuccess: (d) => { qc.setQueryData(['sprint', id], { ...sprint, ...d }); qc.invalidateQueries({ queryKey: ['sprints'] }) },
  })

  if (isLoading || !sprint) return <Spinner />
  const gaps = sprint.gaps ?? []

  return (
    <div className="space-y-4">
      <PageHeader
        title={<>Sprint {sprint.number} - {sprint.name} <StatusBadge status={sprint.status} /></>}
        subtitle={`${formatDate(sprint.start_date)} – ${formatDate(sprint.end_date)} · ${sprint.goal ?? ''}`}
        actions={
          <>
            {user?.role.value === 'admin' && lk && (
              <Select value={sprint.status.value} onChange={(e) => setStatus.mutate(e.target.value)} options={lk.enums.sprint_statuses} aria-label="Status da sprint" />
            )}
            {can('sprints.assign') && sprint.status.value !== 'encerrada' && (
              <Button icon={<Plus className="size-4" />} onClick={() => setAdding(true)}>Adicionar do backlog</Button>
            )}
          </>
        }
      />
      <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        {stages.map((stage) => {
          const items = gaps.filter((g) => stage.statuses.includes(g.status.value))
          return (
            <div key={stage.label} className="rounded-xl bg-slate-200/60 p-2">
              <h3 className="flex justify-between px-1 pb-2 text-sm font-semibold text-slate-700">{stage.label}<span className="text-slate-500">{items.length}</span></h3>
              <div className="space-y-2">
                {items.map((g) => (
                  <div key={g.id} className="rounded-lg bg-white p-3 shadow-sm">
                    <div className="flex items-center justify-between">
                      <Link to={`/gaps/${g.id}`} className="font-mono text-xs font-semibold text-brand-700">{g.code}</Link>
                      {can('sprints.assign') && sprint.status.value !== 'encerrada' && (
                        <button onClick={() => remove.mutate(g.id)} className="text-slate-300 hover:text-red-600" title="Remover da sprint"><X className="size-3.5" /></button>
                      )}
                    </div>
                    <Link to={`/gaps/${g.id}`} className="mt-1 block text-sm text-slate-700">{g.title}</Link>
                    <div className="mt-2 flex flex-wrap items-center gap-1">
                      <PriorityBadge priority={g.priority} /><StatusBadge status={g.status} />
                      <span className="text-xs text-slate-400">{g.it_owner?.name}</span>
                    </div>
                  </div>
                ))}
                {!items.length && <p className="py-4 text-center text-xs text-slate-400">—</p>}
              </div>
            </div>
          )
        })}
      </div>

      <Modal open={adding} title="Adicionar GAPs à sprint" size="lg" onClose={() => setAdding(false)} footer={
        <>
          <Button variant="secondary" onClick={() => setAdding(false)}>Cancelar</Button>
          <Button disabled={!selected.length} loading={assign.isPending} onClick={() => assign.mutate()}>Adicionar ({selected.length})</Button>
        </>
      }>
        {!backlog ? <Spinner /> : !backlog.data.length ? <EmptyState message="Nenhum GAP aberto sem sprint." /> : (
          <ul className="max-h-[60vh] divide-y divide-slate-100 overflow-y-auto">
            {backlog.data.map((g) => (
              <li key={g.id}>
                <label className="flex cursor-pointer items-center gap-3 py-2 text-sm hover:bg-slate-50">
                  <input type="checkbox" checked={selected.includes(g.id)}
                    onChange={(e) => setSelected((s) => e.target.checked ? [...s, g.id] : s.filter((x) => x !== g.id))} />
                  <span className="font-mono text-xs font-semibold text-brand-700">{g.code}</span>
                  <span className="flex-1">{g.title} <span className="text-xs text-slate-400">· {g.sector?.name}</span></span>
                  <PriorityBadge priority={g.priority} /><StatusBadge status={g.status} />
                </label>
              </li>
            ))}
          </ul>
        )}
      </Modal>
    </div>
  )
}

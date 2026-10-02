import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import clsx from 'clsx'
import { Link2, Pencil, Trash2, X } from 'lucide-react'
import { useState, type ReactNode } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { AttachmentsPanel } from '@/components/gaps/AttachmentsPanel'
import { CommentsPanel } from '@/components/gaps/CommentsPanel'
import { GapActions } from '@/components/gaps/GapActions'
import { HistoryPanel } from '@/components/gaps/HistoryPanel'
import { HomologationPanel } from '@/components/gaps/HomologationPanel'
import { TasksPanel } from '@/components/gaps/TasksPanel'
import { OverdueBadge, PriorityBadge, StatusBadge, TypeBadge } from '@/components/gaps/badges'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, EmptyState, ErrorBox, Spinner } from '@/components/ui/Card'
import { useToast } from '@/components/ui/useToast'
import { api, errorMessage } from '@/lib/api'
import { formatDate, formatDateTime } from '@/lib/format'
import type { Gap } from '@/types'

const tabs = ['Comentários', 'Tarefas', 'Homologação', 'Histórico', 'Anexos', 'Relacionados'] as const
type Tab = (typeof tabs)[number]

function Info({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div>
      <dt className="label">{label}</dt>
      <dd className="text-sm text-slate-700">{children || <span className="text-slate-400">—</span>}</dd>
    </div>
  )
}

export function GapDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { can, user } = useAuth()
  const qc = useQueryClient()
  const toast = useToast()
  const [tab, setTab] = useState<Tab>('Comentários')
  const [relatedCode, setRelatedCode] = useState('')

  const { data: gap, isLoading, error } = useQuery({
    queryKey: ['gap', id],
    queryFn: async () => (await api.get<Gap>(`/gaps/${id}`)).data,
  })

  const remove = useMutation({
    mutationFn: () => api.delete(`/gaps/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['gaps'] }); toast('GAP excluído.'); navigate('/gaps') },
    onError: (e) => toast(errorMessage(e), 'error'),
  })

  const link = useMutation({
    mutationFn: async (code: string) => {
      // Resolve o código digitado (GAP-0007) para o id usando a busca global.
      const { data } = await api.get<{ redirect: string | null }>('/search', { params: { q: code } })
      const relatedId = data.redirect?.split('/').pop()
      if (!relatedId) throw new Error(`GAP ${code} não encontrado.`)
      return (await api.post<Gap>(`/gaps/${id}/links`, { related_gap_id: Number(relatedId) })).data
    },
    onSuccess: (g) => { qc.setQueryData(['gap', id], g); setRelatedCode('') },
    onError: (e) => toast(errorMessage(e), 'error'),
  })
  const unlink = useMutation({
    mutationFn: async (relatedId: number) => (await api.delete<Gap>(`/gaps/${id}/links/${relatedId}`)).data,
    onSuccess: (g) => qc.setQueryData(['gap', id], g),
  })

  if (isLoading) return <Spinner />
  if (error || !gap) return <ErrorBox message={errorMessage(error)} />

  const canEdit = can('gaps.update') || (can('gaps.complement') && user?.sector_id === gap.sector_id)

  return (
    <div className="space-y-4">
      <div className="card p-5">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div className="min-w-0">
            <div className="flex flex-wrap items-center gap-2">
              <span className="font-mono text-sm font-bold text-brand-700">{gap.code}</span>
              <TypeBadge type={gap.type} />
              <StatusBadge status={gap.status} />
              <PriorityBadge priority={gap.priority} />
              {gap.is_overdue && <OverdueBadge />}
            </div>
            <h1 className="mt-1 text-xl font-bold text-slate-800">{gap.title}</h1>
            <p className="text-sm text-slate-500">
              {gap.sector?.name} · registrado por {gap.created_by?.name ?? '—'} em {formatDate(gap.identified_at)}
            </p>
          </div>
          <div className="flex gap-2">
            {canEdit && <Link to={`/gaps/${gap.id}/edit`}><Button variant="secondary" icon={<Pencil className="size-4" />}>Editar</Button></Link>}
            {user?.role.value === 'admin' && (
              <Button variant="ghost" icon={<Trash2 className="size-4" />} onClick={() => confirm(`Excluir ${gap.code}?`) && remove.mutate()}>Excluir</Button>
            )}
          </div>
        </div>
        <div className="mt-4 border-t border-slate-100 pt-4"><GapActions gap={gap} /></div>
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <div className="space-y-4 lg:col-span-2">
          <Card title="Descrição">
            <p className="whitespace-pre-wrap text-sm text-slate-700">{gap.description}</p>
            {gap.critical_justification && (
              <div className="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                <b>Justificativa (crítico):</b> {gap.critical_justification}
              </div>
            )}
            {gap.notes && <p className="mt-3 text-sm text-slate-500"><b>Observações:</b> {gap.notes}</p>}
          </Card>

          <div className="card">
            <div className="flex overflow-x-auto border-b border-slate-200 px-2">
              {tabs.map((t) => (
                <button key={t} onClick={() => setTab(t)}
                  className={clsx('whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium', tab === t ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700')}>
                  {t}
                  {t === 'Tarefas' && gap.tasks?.length ? <span className="ml-1 text-xs text-slate-400">({gap.tasks.length})</span> : null}
                  {t === 'Anexos' && gap.attachments?.length ? <span className="ml-1 text-xs text-slate-400">({gap.attachments.length})</span> : null}
                </button>
              ))}
            </div>
            <div className="p-4">
              {tab === 'Comentários' && <CommentsPanel gap={gap} />}
              {tab === 'Tarefas' && <TasksPanel gap={gap} />}
              {tab === 'Homologação' && <HomologationPanel gap={gap} />}
              {tab === 'Histórico' && <HistoryPanel gapId={gap.id} />}
              {tab === 'Anexos' && (
                <AttachmentsPanel attachments={gap.attachments ?? []} uploadUrl={`/gaps/${gap.id}/attachments`}
                  queryKey={['gap', id]} canUpload={can('attachments.create')} />
              )}
              {tab === 'Relacionados' && (
                <div className="space-y-3">
                  {can('gaps.update') && (
                    <form className="flex gap-2" onSubmit={(e) => { e.preventDefault(); if (relatedCode) link.mutate(relatedCode) }}>
                      <input value={relatedCode} onChange={(e) => setRelatedCode(e.target.value)} className="input max-w-xs" placeholder="Código, ex.: GAP-0007" />
                      <Button type="submit" variant="secondary" icon={<Link2 className="size-4" />} loading={link.isPending}>Vincular</Button>
                    </form>
                  )}
                  {!gap.related_gaps?.length ? <EmptyState message="Nenhum GAP relacionado." /> : (
                    <ul className="divide-y divide-slate-100">
                      {gap.related_gaps.map((r) => (
                        <li key={r.id} className="flex items-center gap-3 py-2 text-sm">
                          <Link to={`/gaps/${r.id}`} className="font-mono text-xs font-semibold text-brand-700">{r.code}</Link>
                          <span className="flex-1">{r.title}</span>
                          <StatusBadge status={r.status} />
                          {can('gaps.update') && <button onClick={() => unlink.mutate(r.id)} className="text-slate-400 hover:text-red-600" aria-label="Desvincular"><X className="size-4" /></button>}
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="space-y-4">
          <Card title="Classificação">
            <dl className="grid grid-cols-2 gap-3">
              <Info label="Impacto">{gap.impact.label}</Info>
              <Info label="Urgência">{gap.urgency.label}</Info>
              <Info label="Categoria">{gap.category && <Badge>{gap.category.name}</Badge>}</Info>
              <Info label="Origem">{gap.origin?.label}</Info>
              <Info label="Frequência">{gap.frequency?.label}</Info>
              <Info label="Processo">{gap.process}</Info>
              <div className="col-span-2"><Info label="Sistemas">{gap.systems?.map((s) => s.name).join(', ')}</Info></div>
            </dl>
          </Card>
          <Card title="Responsáveis e prazos">
            <dl className="grid grid-cols-2 gap-3">
              <Info label="Responsável TI">{gap.it_owner?.name}</Info>
              <Info label="Responsável setor">{gap.sector_owner?.name}</Info>
              <Info label="Identificado em">{formatDate(gap.identified_at)}</Info>
              <Info label="Prazo desejado">{gap.due_date && formatDate(gap.due_date)}</Info>
              <Info label="Sprint">{gap.sprint && <Link to={`/sprints/${gap.sprint.id}`} className="text-brand-700 hover:underline">Sprint {gap.sprint.number} - {gap.sprint.name}</Link>}</Info>
              <Info label="Concluído em">{gap.completed_at && formatDateTime(gap.completed_at)}</Info>
            </dl>
          </Card>
          <Card title="Reuniões de origem">
            {!gap.meetings?.length ? <EmptyState message="Não vinculado a reuniões." /> : (
              <ul className="space-y-1 text-sm">
                {gap.meetings.map((m) => (
                  <li key={m.id}><Link to={`/meetings/${m.id}`} className="text-brand-700 hover:underline">{formatDate(m.date)} - {m.title}</Link></li>
                ))}
              </ul>
            )}
          </Card>
        </div>
      </div>
    </div>
  )
}

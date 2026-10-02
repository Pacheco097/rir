import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/Card'
import { useToast } from '@/components/ui/useToast'
import { useLookups } from '@/hooks/useLookups'
import { api, errorMessage } from '@/lib/api'
import { formatDate } from '@/lib/format'
import type { Gap } from '@/types'

export function TasksPanel({ gap }: { gap: Gap }) {
  const { can } = useAuth()
  const { data: lk } = useLookups()
  const qc = useQueryClient()
  const toast = useToast()
  const [title, setTitle] = useState('')
  const [assignee, setAssignee] = useState('')
  const manage = can('tasks.manage')

  const refresh = () => qc.invalidateQueries({ queryKey: ['gap', String(gap.id)] })
  const onError = (e: unknown) => toast(errorMessage(e), 'error')

  const add = useMutation({
    mutationFn: () => api.post(`/gaps/${gap.id}/tasks`, { title, assignee_id: assignee || null }),
    onSuccess: () => { setTitle(''); refresh() },
    onError,
  })
  const update = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => api.patch(`/tasks/${id}`, { status }),
    onSuccess: refresh,
    onError,
  })
  const remove = useMutation({ mutationFn: (id: number) => api.delete(`/tasks/${id}`), onSuccess: refresh, onError })

  const tasks = gap.tasks ?? []
  const done = tasks.filter((t) => t.status.value === 'concluida').length

  return (
    <div className="space-y-3">
      {tasks.length > 0 && (
        <div>
          <div className="mb-1 flex justify-between text-xs text-slate-500"><span>Progresso</span><span>{done}/{tasks.length}</span></div>
          <div className="h-2 overflow-hidden rounded-full bg-slate-100">
            <div className="h-full rounded-full bg-emerald-500" style={{ width: `${(done / tasks.length) * 100}%` }} />
          </div>
        </div>
      )}
      {!tasks.length ? <EmptyState message="Nenhuma tarefa. Quebre o GAP em tarefas para acompanhar a execução." /> : (
        <ul className="divide-y divide-slate-100">
          {tasks.map((t) => (
            <li key={t.id} className="flex items-center gap-3 py-2">
              <input type="checkbox" disabled={!manage} checked={t.status.value === 'concluida'}
                onChange={(e) => update.mutate({ id: t.id, status: e.target.checked ? 'concluida' : 'a_fazer' })} />
              <div className="min-w-0 flex-1">
                <div className={`text-sm ${t.status.value === 'concluida' ? 'text-slate-400 line-through' : 'text-slate-700'}`}>{t.title}</div>
                <div className="text-xs text-slate-400">{t.assignee?.name ?? 'Sem responsável'} {t.due_date && `· ${formatDate(t.due_date)}`}</div>
              </div>
              {manage ? (
                <select value={t.status.value} onChange={(e) => update.mutate({ id: t.id, status: e.target.value })} className="input w-36 py-1 text-xs">
                  {lk?.enums.task_statuses.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                </select>
              ) : <span className="text-xs text-slate-500">{t.status.label}</span>}
              {manage && (
                <button onClick={() => remove.mutate(t.id)} className="text-slate-400 hover:text-red-600" aria-label="Excluir tarefa"><Trash2 className="size-4" /></button>
              )}
            </li>
          ))}
        </ul>
      )}
      {manage && (
        <form className="flex flex-wrap gap-2" onSubmit={(e) => { e.preventDefault(); if (title.trim()) add.mutate() }}>
          <input value={title} onChange={(e) => setTitle(e.target.value)} className="input flex-1" placeholder="Nova tarefa" />
          <select value={assignee} onChange={(e) => setAssignee(e.target.value)} className="input w-44">
            <option value="">Responsável</option>
            {lk?.users.filter((u) => u.role === 'ti' || u.role === 'admin').map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
          </select>
          <Button type="submit" variant="secondary" icon={<Plus className="size-4" />} loading={add.isPending}>Adicionar</Button>
        </form>
      )}
    </div>
  )
}

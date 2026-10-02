import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { StatusBadge } from '@/components/gaps/badges'
import { Button } from '@/components/ui/Button'
import { EmptyState, ErrorBox, PageHeader, Spinner } from '@/components/ui/Card'
import { TextArea, TextInput } from '@/components/ui/Field'
import { Modal } from '@/components/ui/Modal'
import { useToast } from '@/components/ui/useToast'
import { api, errorMessage } from '@/lib/api'
import { formatDate } from '@/lib/format'
import type { Sprint } from '@/types'

export function SprintsPage() {
  const { user } = useAuth()
  const qc = useQueryClient()
  const toast = useToast()
  const [open, setOpen] = useState(false)
  const [form, setForm] = useState({ name: '', goal: '', start_date: '', end_date: '' })
  const [error, setError] = useState<string | null>(null)

  const { data, isLoading } = useQuery({ queryKey: ['sprints'], queryFn: async () => (await api.get<Sprint[]>('/sprints')).data })

  const create = useMutation({
    mutationFn: () => api.post('/sprints', form),
    onSuccess: () => {
      setOpen(false)
      qc.invalidateQueries({ queryKey: ['sprints'] })
      qc.invalidateQueries({ queryKey: ['lookups'] })
      toast('Sprint criada.')
    },
    onError: (e) => setError(errorMessage(e)),
  })

  return (
    <div className="space-y-4">
      <PageHeader title="Sprints" subtitle="Backlog → Sprint → Em execução → Homologação → Concluído"
        actions={user?.role.value === 'admin' && <Button icon={<Plus className="size-4" />} onClick={() => { setError(null); setOpen(true) }}>Nova sprint</Button>} />
      {isLoading ? <Spinner /> : !data?.length ? <EmptyState /> : (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          {data.map((s) => {
            const pct = s.gaps_count ? Math.round(((s.completed_count ?? 0) / s.gaps_count) * 100) : 0
            return (
              <Link key={s.id} to={`/sprints/${s.id}`} className="card block p-4 transition-shadow hover:shadow-md">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold uppercase text-brand-700">Sprint {s.number}</span>
                  <StatusBadge status={s.status} />
                </div>
                <h3 className="mt-1 font-semibold text-slate-800">{s.name}</h3>
                <p className="mt-1 line-clamp-2 text-sm text-slate-500">{s.goal}</p>
                <p className="mt-2 text-xs text-slate-400">{formatDate(s.start_date)} – {formatDate(s.end_date)}</p>
                <div className="mt-3">
                  <div className="mb-1 flex justify-between text-xs text-slate-500"><span>{s.completed_count}/{s.gaps_count} concluídos</span><span>{pct}%</span></div>
                  <div className="h-2 overflow-hidden rounded-full bg-slate-100"><div className="h-full rounded-full bg-emerald-500" style={{ width: `${pct}%` }} /></div>
                </div>
              </Link>
            )
          })}
        </div>
      )}
      <Modal open={open} title="Nova sprint" onClose={() => setOpen(false)} footer={
        <>
          <Button variant="secondary" onClick={() => setOpen(false)}>Cancelar</Button>
          <Button loading={create.isPending} onClick={() => create.mutate()}>Criar</Button>
        </>
      }>
        {error && <ErrorBox message={error} />}
        <TextInput label="Nome" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
        <TextArea label="Objetivo" value={form.goal} onChange={(e) => setForm({ ...form, goal: e.target.value })} />
        <div className="grid grid-cols-2 gap-3">
          <TextInput label="Data inicial" type="date" required value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} />
          <TextInput label="Data final" type="date" required value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} />
        </div>
      </Modal>
    </div>
  )
}

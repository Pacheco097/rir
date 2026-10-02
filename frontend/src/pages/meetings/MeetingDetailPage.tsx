import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { CalendarPlus, Link2, Pencil, Plus, Save } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { AttachmentsPanel } from '@/components/gaps/AttachmentsPanel'
import { PriorityBadge, StatusBadge } from '@/components/gaps/badges'
import { Button } from '@/components/ui/Button'
import { Card, EmptyState, ErrorBox, Spinner } from '@/components/ui/Card'
import { TextArea, TextInput } from '@/components/ui/Field'
import { Modal } from '@/components/ui/Modal'
import { useToast } from '@/components/ui/useToast'
import { api, errorMessage } from '@/lib/api'
import { formatDate } from '@/lib/format'
import type { Meeting } from '@/types'

const minutesFields = [
  ['minutes', 'Ata'], ['summary', 'Resumo'], ['decisions', 'Decisões'], ['next_steps', 'Próximos passos / responsáveis / prazos'],
] as const

/** Ata/resumo/decisões: o estado local nasce dos dados da reunião (sem useEffect). */
function MinutesCard({ meeting, manage, saving, onSave }: {
  meeting: Meeting
  manage: boolean
  saving: boolean
  onSave: (data: Record<string, string>) => void
}) {
  const [minutes, setMinutes] = useState<Record<string, string>>(() => ({
    minutes: meeting.minutes ?? '', summary: meeting.summary ?? '', decisions: meeting.decisions ?? '', next_steps: meeting.next_steps ?? '',
  }))

  return (
    <Card title="Registro pós-reunião" actions={manage && (
      <Button size="sm" icon={<Save className="size-3.5" />} loading={saving} onClick={() => onSave(minutes)}>Salvar</Button>
    )}>
      <div className="space-y-3">
        {minutesFields.map(([key, label]) => manage ? (
          <TextArea key={key} label={label} rows={key === 'minutes' ? 5 : 2} value={minutes[key] ?? ''} onChange={(e) => setMinutes((s) => ({ ...s, [key]: e.target.value }))} />
        ) : (
          <div key={key}><div className="label">{label}</div><p className="whitespace-pre-wrap text-sm">{minutes[key] || '—'}</p></div>
        ))}
      </div>
    </Card>
  )
}

export function MeetingDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { can } = useAuth()
  const qc = useQueryClient()
  const toast = useToast()
  const manage = can('meetings.manage')
  const [nextOpen, setNextOpen] = useState(false)
  const [next, setNext] = useState({ date: '', start_time: '09:00' })
  const [gapCode, setGapCode] = useState('')
  const [error, setError] = useState<string | null>(null)

  const { data: m, isLoading } = useQuery({
    queryKey: ['meeting', id],
    queryFn: async () => (await api.get<Meeting>(`/meetings/${id}`)).data,
  })

  const update = useMutation({
    mutationFn: async (body: object) => (await api.patch<Meeting>(`/meetings/${id}`, body)).data,
    onSuccess: (data) => { qc.setQueryData(['meeting', id], data); qc.invalidateQueries({ queryKey: ['meetings'] }); toast('Reunião atualizada.') },
    onError: (e) => toast(errorMessage(e), 'error'),
  })

  const scheduleNext = useMutation({
    mutationFn: async () => (await api.post<Meeting>(`/meetings/${id}/next`, next)).data,
    onSuccess: (created) => { setNextOpen(false); qc.invalidateQueries({ queryKey: ['meetings'] }); toast('Próxima reunião agendada.'); navigate(`/meetings/${created.id}`) },
    onError: (e) => setError(errorMessage(e)),
  })

  const linkGap = async () => {
    try {
      const { data } = await api.get<{ redirect: string | null }>('/search', { params: { q: gapCode } })
      const gapId = Number(data.redirect?.split('/').pop())
      if (!gapId) throw new Error(`GAP ${gapCode} não encontrado.`)
      update.mutate({ gap_ids: [...(m?.gaps?.map((g) => g.id) ?? []), gapId] })
      setGapCode('')
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  if (isLoading || !m) return <Spinner />

  return (
    <div className="space-y-4">
      <div className="card p-5">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <div className="flex items-center gap-2"><StatusBadge status={m.status} /><span className="text-sm text-slate-500">{m.sector?.name}</span></div>
            <h1 className="mt-1 text-xl font-bold text-slate-800">{m.title}</h1>
            <p className="text-sm text-slate-500">
              {formatDate(m.date)} · {m.start_time}{m.end_time && ` – ${m.end_time}`} · Líder: {m.leader?.name ?? '—'} · TI: {m.it_owner?.name ?? '—'}
            </p>
            {m.previous_meeting_id && <Link to={`/meetings/${m.previous_meeting_id}`} className="text-xs text-brand-700 hover:underline">← reunião anterior</Link>}
          </div>
          {manage && (
            <div className="flex flex-wrap gap-2">
              {m.status.value === 'agendada' && <Button variant="secondary" onClick={() => update.mutate({ status: 'confirmada' })}>Confirmar</Button>}
              {['agendada', 'confirmada', 'reagendada'].includes(m.status.value) && (
                <>
                  <Button variant="success" onClick={() => update.mutate({ status: 'realizada' })}>Marcar como realizada</Button>
                  <Button variant="ghost" onClick={() => confirm('Cancelar esta reunião?') && update.mutate({ status: 'cancelada' })}>Cancelar</Button>
                </>
              )}
              <Link to={`/meetings/${m.id}/edit`}><Button variant="secondary" icon={<Pencil className="size-4" />}>Editar / Reagendar</Button></Link>
              <Button variant="secondary" icon={<CalendarPlus className="size-4" />} onClick={() => { setError(null); setNextOpen(true) }}>Criar próxima reunião</Button>
            </div>
          )}
        </div>
        {m.objective && <p className="mt-3 text-sm text-slate-700"><b>Objetivo:</b> {m.objective}</p>}
        {!!m.participants?.length && <p className="mt-1 text-sm text-slate-500"><b>Participantes:</b> {m.participants.map((p) => p.name).join(', ')}</p>}
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card title="Pauta">
          <ol className="space-y-3">
            {m.agenda.map((a, i) => (
              <li key={a.key}>
                <div className="text-sm font-semibold text-slate-700">{i + 1}. {a.label}</div>
                <p className="whitespace-pre-wrap text-sm text-slate-600">{a.content || <span className="text-slate-400">—</span>}</p>
              </li>
            ))}
          </ol>
        </Card>

        <MinutesCard key={m.id} meeting={m} manage={manage} saving={update.isPending} onSave={(data) => update.mutate(data)} />
      </div>

      <Card title={`GAPs identificados (${m.gaps?.length ?? 0})`} actions={
        <div className="flex gap-2">
          {manage && (
            <form className="flex gap-1" onSubmit={(e) => { e.preventDefault(); if (gapCode) void linkGap() }}>
              <input value={gapCode} onChange={(e) => setGapCode(e.target.value)} className="input w-36 py-1 text-xs" placeholder="GAP-0001" />
              <Button type="submit" size="sm" variant="secondary" icon={<Link2 className="size-3.5" />}>Vincular</Button>
            </form>
          )}
          {can('gaps.create') && (
            <Link to={`/gaps/new?meeting_id=${m.id}&sector_id=${m.sector_id}`}><Button size="sm" icon={<Plus className="size-3.5" />}>Registrar GAP</Button></Link>
          )}
        </div>
      }>
        {!m.gaps?.length ? <EmptyState message="Nenhum GAP vinculado a esta reunião." /> : (
          <ul className="divide-y divide-slate-100">
            {m.gaps.map((g) => (
              <li key={g.id} className="flex items-center gap-3 py-2 text-sm">
                <Link to={`/gaps/${g.id}`} className="font-mono text-xs font-semibold text-brand-700">{g.code}</Link>
                <span className="flex-1">{g.title}</span>
                <span className="text-xs text-slate-400">{g.it_owner?.name}</span>
                <PriorityBadge priority={g.priority} />
                <StatusBadge status={g.status} />
              </li>
            ))}
          </ul>
        )}
      </Card>

      <Card title="Anexos">
        <AttachmentsPanel attachments={m.attachments ?? []} uploadUrl={`/meetings/${m.id}/attachments`} queryKey={['meeting', id]} canUpload={manage} />
      </Card>

      <Modal open={nextOpen} title="Criar próxima reunião" onClose={() => setNextOpen(false)} footer={
        <>
          <Button variant="secondary" onClick={() => setNextOpen(false)}>Cancelar</Button>
          <Button loading={scheduleNext.isPending} onClick={() => scheduleNext.mutate()}>Agendar</Button>
        </>
      }>
        {error && <ErrorBox message={error} />}
        <p className="text-sm text-slate-600">Setor, líder, responsável TI e participantes serão copiados. Os próximos passos viram o objetivo.</p>
        <div className="grid grid-cols-2 gap-3">
          <TextInput label="Data" type="date" required value={next.date} onChange={(e) => setNext((n) => ({ ...n, date: e.target.value }))} />
          <TextInput label="Hora" type="time" required value={next.start_time} onChange={(e) => setNext((n) => ({ ...n, start_time: e.target.value }))} />
        </div>
      </Modal>
    </div>
  )
}

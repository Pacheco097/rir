import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Save } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Button } from '@/components/ui/Button'
import { Card, PageHeader, Spinner } from '@/components/ui/Card'
import { Field, Select, TextArea, TextInput } from '@/components/ui/Field'
import { useToast } from '@/components/ui/useToast'
import { useLookups } from '@/hooks/useLookups'
import { api, errorMessage, fieldErrors } from '@/lib/api'
import type { Lookups, Meeting } from '@/types'

interface FormState {
  title: string; sector_id: string; leader_id: string; it_owner_id: string; date: string
  start_time: string; end_time: string; objective: string; status: string; notes: string
  participant_ids: number[]; agenda: Record<string, string>
}

const empty: FormState = {
  title: '', sector_id: '', leader_id: '', it_owner_id: '', date: '', start_time: '09:00', end_time: '10:00',
  objective: '', status: 'agendada', notes: '', participant_ids: [], agenda: {},
}

function fromMeeting(meeting: Meeting): FormState {
  return {
    title: meeting.title, sector_id: String(meeting.sector_id), leader_id: meeting.leader_id ? String(meeting.leader_id) : '',
    it_owner_id: meeting.it_owner_id ? String(meeting.it_owner_id) : '', date: meeting.date, start_time: meeting.start_time,
    end_time: meeting.end_time ?? '', objective: meeting.objective ?? '', status: meeting.status.value, notes: meeting.notes ?? '',
    participant_ids: meeting.participants?.map((p) => p.id) ?? [],
    agenda: Object.fromEntries(meeting.agenda.map((a) => [a.key, a.content ?? ''])),
  }
}

/** Loader: busca a reunião (edição) e entrega os valores iniciais ao formulário. */
export function MeetingFormPage() {
  const { id } = useParams()
  const { data: lk } = useLookups()
  const { data: meeting, isLoading } = useQuery({
    queryKey: ['meeting', id],
    queryFn: async () => (await api.get<Meeting>(`/meetings/${id}`)).data,
    enabled: Boolean(id),
  })

  if (!lk || (id && (isLoading || !meeting))) return <Spinner />
  return <MeetingForm key={id ?? 'new'} meeting={meeting} lk={lk} />
}

function MeetingForm({ meeting, lk }: { meeting?: Meeting; lk: Lookups }) {
  const isEdit = Boolean(meeting)
  const id = meeting?.id
  const navigate = useNavigate()
  const qc = useQueryClient()
  const toast = useToast()
  const [form, setForm] = useState<FormState>(() => (meeting ? fromMeeting(meeting) : empty))
  const [errors, setErrors] = useState<Record<string, string>>({})

  const save = useMutation({
    mutationFn: async () => {
      const payload = { ...form, leader_id: form.leader_id || null, it_owner_id: form.it_owner_id || null, end_time: form.end_time || null }
      return isEdit ? (await api.patch<Meeting>(`/meetings/${id}`, payload)).data : (await api.post<Meeting>('/meetings', payload)).data
    },
    onSuccess: (m) => {
      qc.invalidateQueries({ queryKey: ['meetings'] })
      qc.setQueryData(['meeting', String(m.id)], m)
      toast('Reunião salva.')
      navigate(`/meetings/${m.id}`)
    },
    onError: (e) => { setErrors(fieldErrors(e)); toast(errorMessage(e), 'error') },
  })

  const set = <K extends keyof FormState>(k: K, v: FormState[K]) => setForm((f) => ({ ...f, [k]: v }))
  const onSectorChange = (sectorId: string) => {
    // Ao escolher o setor, sugere o líder cadastrado como responsável.
    const leader = lk.sectors.find((s) => String(s.id) === sectorId)?.leader_id
    setForm((f) => ({ ...f, sector_id: sectorId, leader_id: leader ? String(leader) : f.leader_id, title: f.title || `Levantamento de gaps - ${lk.sectors.find((s) => String(s.id) === sectorId)?.name ?? ''}` }))
  }
  const toggleParticipant = (uid: number) =>
    set('participant_ids', form.participant_ids.includes(uid) ? form.participant_ids.filter((x) => x !== uid) : [...form.participant_ids, uid])

  const onSubmit = (e: FormEvent) => { e.preventDefault(); setErrors({}); save.mutate() }
  const users = lk.users.map((u) => ({ value: u.id, label: u.name }))

  return (
    <form onSubmit={onSubmit} className="mx-auto max-w-4xl space-y-4">
      <PageHeader title={isEdit ? 'Editar reunião' : 'Agendar reunião'} />
      <Card title="Dados da reunião">
        <div className="grid gap-4 md:grid-cols-3">
          <Select label="Setor" required value={form.sector_id} onChange={(e) => onSectorChange(e.target.value)} placeholder="Selecione"
            options={lk.sectors.map((s) => ({ value: s.id, label: s.name }))} error={errors.sector_id} />
          <TextInput className="md:col-span-2" label="Título" required value={form.title} onChange={(e) => set('title', e.target.value)} error={errors.title} />
          <TextInput label="Data" type="date" required value={form.date} onChange={(e) => set('date', e.target.value)} error={errors.date} />
          <TextInput label="Hora inicial" type="time" required value={form.start_time} onChange={(e) => set('start_time', e.target.value)} error={errors.start_time} />
          <TextInput label="Hora final" type="time" value={form.end_time} onChange={(e) => set('end_time', e.target.value)} error={errors.end_time} />
          <Select label="Líder" value={form.leader_id} onChange={(e) => set('leader_id', e.target.value)} placeholder="—" options={users} />
          <Select label="Responsável TI" value={form.it_owner_id} onChange={(e) => set('it_owner_id', e.target.value)} placeholder="—"
            options={lk.users.filter((u) => u.role === 'ti' || u.role === 'admin').map((u) => ({ value: u.id, label: u.name }))} />
          <Select label="Status" value={form.status} onChange={(e) => set('status', e.target.value)} options={lk.enums.meeting_statuses} />
          <TextArea className="md:col-span-3" label="Objetivo" value={form.objective} onChange={(e) => set('objective', e.target.value)} />
          <Field label="Participantes" className="md:col-span-3">
            <div className="flex max-h-40 flex-wrap gap-1.5 overflow-y-auto">
              {lk.users.map((u) => (
                <button type="button" key={u.id} onClick={() => toggleParticipant(u.id)}
                  className={`rounded-full px-2.5 py-1 text-xs ring-1 ${form.participant_ids.includes(u.id) ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-slate-600 ring-slate-300'}`}>
                  {u.name}
                </button>
              ))}
            </div>
          </Field>
          <TextArea className="md:col-span-3" label="Observações" value={form.notes} onChange={(e) => set('notes', e.target.value)} />
        </div>
      </Card>
      <Card title="Pauta estruturada">
        <div className="grid gap-3 md:grid-cols-2">
          {lk.agenda_topics.map((t, i) => (
            <TextArea key={t.key} label={`${i + 1}. ${t.label}`} rows={2} value={form.agenda[t.key] ?? ''}
              onChange={(e) => set('agenda', { ...form.agenda, [t.key]: e.target.value })} />
          ))}
        </div>
      </Card>
      <div className="flex justify-end gap-2">
        <Button variant="secondary" onClick={() => navigate(-1)}>Cancelar</Button>
        <Button type="submit" loading={save.isPending} icon={<Save className="size-4" />}>Salvar</Button>
      </div>
    </form>
  )
}

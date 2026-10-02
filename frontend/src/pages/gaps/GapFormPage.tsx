import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ChevronDown, ChevronUp, Save } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/Button'
import { Card, ErrorBox, PageHeader, Spinner } from '@/components/ui/Card'
import { Field, Select, TextArea, TextInput } from '@/components/ui/Field'
import { useToast } from '@/components/ui/useToast'
import { useLookups } from '@/hooks/useLookups'
import { api, errorMessage, fieldErrors } from '@/lib/api'
import { toIsoDate } from '@/lib/format'
import { suggestPriority } from '@/lib/priority'
import type { Gap, Lookups } from '@/types'

type FormState = Record<string, string | number[] | undefined>

const empty: FormState = {
  type: 'gap', title: '', description: '', sector_id: '', impact: '', urgency: '', priority: '',
  critical_justification: '', process: '', category_id: '', origin: '', frequency: '',
  sector_owner_id: '', it_owner_id: '', identified_at: toIsoDate(new Date()), due_date: '', sprint_id: '',
  notes: '', system_ids: [], meeting_ids: [],
}

/** Converte "" em null para a API (campos opcionais não preenchidos). */
function toPayload(form: FormState) {
  return Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v]))
}

/** Converte o GAP da API nos valores do formulário. */
function fromGap(gap: Gap): FormState {
  return {
    type: gap.type.value, title: gap.title, description: gap.description, sector_id: String(gap.sector_id),
    impact: gap.impact.value, urgency: gap.urgency.value, priority: gap.priority.value,
    critical_justification: gap.critical_justification ?? '', process: gap.process ?? '',
    category_id: gap.category_id ? String(gap.category_id) : '', origin: gap.origin?.value ?? '',
    frequency: gap.frequency?.value ?? '', sector_owner_id: gap.sector_owner_id ? String(gap.sector_owner_id) : '',
    it_owner_id: gap.it_owner_id ? String(gap.it_owner_id) : '', identified_at: gap.identified_at,
    due_date: gap.due_date ?? '', sprint_id: gap.sprint_id ? String(gap.sprint_id) : '', notes: gap.notes ?? '',
    system_ids: gap.systems?.map((s) => s.id) ?? [], meeting_ids: gap.meetings?.map((m) => m.id) ?? [],
  }
}

/**
 * Página = "loader": busca os dados e só então renderiza o formulário.
 * Assim o formulário recebe os valores iniciais no useState, sem precisar
 * de um useEffect para "copiar" a resposta da API para o estado.
 */
export function GapFormPage() {
  const { id } = useParams()
  const { data: lk } = useLookups()
  const { data: gap, isLoading } = useQuery({
    queryKey: ['gap', id],
    queryFn: async () => (await api.get<Gap>(`/gaps/${id}`)).data,
    enabled: Boolean(id),
  })

  if (!lk || (id && (isLoading || !gap))) return <Spinner />
  return <GapForm key={id ?? 'new'} gap={gap} lk={lk} />
}

function GapForm({ gap, lk }: { gap?: Gap; lk: Lookups }) {
  const isEdit = Boolean(gap)
  const id = gap?.id
  const [search] = useSearchParams()
  const navigate = useNavigate()
  const toast = useToast()
  const qc = useQueryClient()
  const { can, user } = useAuth()
  const [form, setForm] = useState<FormState>(() => gap ? fromGap(gap) : {
    ...empty,
    // Permite abrir "Novo GAP" já vinculado a uma reunião/setor: /gaps/new?meeting_id=3&sector_id=1
    sector_id: search.get('sector_id') ?? (user?.role.value === 'lider' ? String(user.sector_id ?? '') : ''),
    meeting_ids: search.get('meeting_id') ? [Number(search.get('meeting_id'))] : [],
    type: search.get('type') ?? 'gap',
  })
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [showMore, setShowMore] = useState(isEdit)

  const save = useMutation({
    mutationFn: async () => {
      const payload = toPayload(form)
      return isEdit
        ? (await api.patch<Gap>(`/gaps/${id}`, payload)).data
        : (await api.post<Gap>('/gaps', payload)).data
    },
    onSuccess: (saved) => {
      qc.invalidateQueries({ queryKey: ['gaps'] })
      qc.invalidateQueries({ queryKey: ['dashboard'] })
      qc.setQueryData(['gap', String(saved.id)], saved)
      toast(isEdit ? 'GAP atualizado.' : `${saved.code} registrado com sucesso.`)
      navigate(`/gaps/${saved.id}`)
    },
    onError: (err) => {
      setErrors(fieldErrors(err))
      toast(errorMessage(err), 'error')
    },
  })

  const set = (key: string, value: string | number[]) => setForm((f) => ({ ...f, [key]: value }))
  const str = (key: string) => (form[key] as string) ?? ''
  const suggested = suggestPriority(str('impact'), str('urgency'))
  const isCritical = str('impact') === 'critico' || (str('priority') || suggested) === 'critica'
  // Edição: campos de classificação/atribuição só para quem tem permissão (o backend também ignora).
  const canClassify = !isEdit || can('gaps.classify')
  const canAssign = !isEdit || can('gaps.assign')
  const isLeaderComplementing = isEdit && !can('gaps.update')
  const tiUsers = lk.users.filter((u) => u.role === 'ti' || u.role === 'admin').map((u) => ({ value: u.id, label: u.name }))
  const systemIds = (form.system_ids as number[]) ?? []

  const onSubmit = (e: FormEvent) => {
    e.preventDefault()
    setErrors({})
    save.mutate()
  }

  return (
    <form onSubmit={onSubmit} className="mx-auto max-w-4xl space-y-4">
      <PageHeader
        title={isEdit ? `Editar ${gap?.code}` : 'Registrar GAP'}
        subtitle={isEdit ? gap?.title : 'Preencha o essencial agora; os detalhes podem ser complementados depois.'}
      />
      {isLeaderComplementing && <ErrorBox message="Como líder, você pode complementar descrição, processo, frequência, sistemas e observações." />}

      <Card title="Informações essenciais">
        <div className="grid gap-4 md:grid-cols-2">
          {!isEdit && (
            <Select label="Tipo" value={str('type')} onChange={(e) => set('type', e.target.value)} options={lk.enums.gap_types}
              hint="Incidente crítico = problema que está impedindo a operação agora." />
          )}
          <Select label="Setor" required value={str('sector_id')} onChange={(e) => set('sector_id', e.target.value)} placeholder="Selecione"
            options={lk.sectors.map((s) => ({ value: s.id, label: s.name }))} error={errors.sector_id}
            disabled={user?.role.value === 'lider' || isLeaderComplementing} />
          <TextInput className="md:col-span-2" label="Título" required value={str('title')} onChange={(e) => set('title', e.target.value)}
            placeholder="Ex.: Duplicidade de produtos no cadastro" error={errors.title} disabled={isLeaderComplementing} maxLength={255} />
          <TextArea className="md:col-span-2" label="Descrição" required rows={4} value={str('description')} onChange={(e) => set('description', e.target.value)}
            placeholder="O que acontece? Onde? Qual o impacto no dia a dia?" error={errors.description} />
          <Select label="Impacto" required value={str('impact')} onChange={(e) => set('impact', e.target.value)} placeholder="Selecione"
            options={lk.enums.impacts} error={errors.impact} disabled={!canClassify}
            hint={lk.enums.impacts.find((i) => i.value === str('impact'))?.description} />
          <Select label="Urgência" required value={str('urgency')} onChange={(e) => set('urgency', e.target.value)} placeholder="Selecione"
            options={lk.enums.urgencies} error={errors.urgency} disabled={!canClassify} />
          <Select label="Prioridade" value={str('priority')} onChange={(e) => set('priority', e.target.value)}
            placeholder={suggested ? `Automática (${lk.enums.priorities.find((p) => p.value === suggested)?.label})` : 'Automática'}
            options={lk.enums.priorities} error={errors.priority} disabled={!canClassify}
            hint="Se não informada, é calculada pela matriz Impacto x Urgência." />
          <Field label="Sistemas relacionados" error={errors.system_ids}>
            <div className="flex flex-wrap gap-1.5">
              {lk.systems.map((s) => {
                const on = systemIds.includes(s.id)
                return (
                  <button key={s.id} type="button"
                    onClick={() => set('system_ids', on ? systemIds.filter((x) => x !== s.id) : [...systemIds, s.id])}
                    className={`rounded-full px-2.5 py-1 text-xs ring-1 ${on ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50'}`}>
                    {s.name}
                  </button>
                )
              })}
            </div>
          </Field>
          {isCritical && (
            <TextArea className="md:col-span-2" label="Justificativa (GAP crítico)" required rows={2}
              value={str('critical_justification')} onChange={(e) => set('critical_justification', e.target.value)}
              error={errors.critical_justification} disabled={!canClassify}
              hint="Regra 3: todo GAP crítico deve ter justificativa." />
          )}
        </div>
      </Card>

      <button type="button" onClick={() => setShowMore((v) => !v)} className="flex items-center gap-1 text-sm font-medium text-brand-700">
        {showMore ? <ChevronUp className="size-4" /> : <ChevronDown className="size-4" />} Mais detalhes (classificação, responsáveis, prazos)
      </button>

      {showMore && (
        <Card title="Detalhes">
          <div className="grid gap-4 md:grid-cols-3">
            <TextInput label="Processo" value={str('process')} onChange={(e) => set('process', e.target.value)} placeholder="Ex.: Entrada de notas" />
            <Select label="Categoria" value={str('category_id')} onChange={(e) => set('category_id', e.target.value)} placeholder="—"
              options={lk.categories.map((c) => ({ value: c.id, label: c.name }))} disabled={!canClassify} />
            <Select label="Origem" value={str('origin')} onChange={(e) => set('origin', e.target.value)} placeholder="—" options={lk.enums.origins} disabled={!canClassify} />
            <Select label="Frequência" value={str('frequency')} onChange={(e) => set('frequency', e.target.value)} placeholder="—" options={lk.enums.frequencies} />
            <Select label="Responsável do setor" value={str('sector_owner_id')} onChange={(e) => set('sector_owner_id', e.target.value)}
              placeholder="Líder do setor (padrão)" options={lk.users.map((u) => ({ value: u.id, label: u.name }))} disabled={!canAssign} error={errors.sector_owner_id} />
            <Select label="Responsável TI" value={str('it_owner_id')} onChange={(e) => set('it_owner_id', e.target.value)} placeholder="—"
              options={tiUsers} disabled={!canAssign} error={errors.it_owner_id} />
            <TextInput label="Data de identificação" type="date" value={str('identified_at')} onChange={(e) => set('identified_at', e.target.value)} disabled={isEdit} />
            <TextInput label="Prazo desejado" type="date" value={str('due_date')} onChange={(e) => set('due_date', e.target.value)} disabled={!canClassify} error={errors.due_date} />
            <Select label="Sprint" value={str('sprint_id')} onChange={(e) => set('sprint_id', e.target.value)} placeholder="—"
              options={lk.sprints.filter((s) => s.status !== 'encerrada').map((s) => ({ value: s.id, label: `Sprint ${s.number} - ${s.name}` }))}
              disabled={!canAssign || str('type') === 'incidente'} />
            <TextArea className="md:col-span-3" label="Observações" value={str('notes')} onChange={(e) => set('notes', e.target.value)} />
          </div>
        </Card>
      )}

      <div className="flex justify-end gap-2">
        <Button variant="secondary" onClick={() => navigate(-1)}>Cancelar</Button>
        <Button type="submit" loading={save.isPending} icon={<Save className="size-4" />}>Salvar</Button>
      </div>
    </form>
  )
}

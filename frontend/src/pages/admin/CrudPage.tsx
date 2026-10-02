import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Pencil, Plus, Trash2 } from 'lucide-react'
import { useState, type ReactNode } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { EmptyState, ErrorBox, PageHeader, Spinner } from '@/components/ui/Card'
import { Select, TextArea, TextInput } from '@/components/ui/Field'
import { Modal } from '@/components/ui/Modal'
import { useToast } from '@/components/ui/useToast'
import { api, errorMessage, fieldErrors } from '@/lib/api'

export interface CrudField {
  name: string
  label: string
  type?: 'text' | 'email' | 'password' | 'textarea' | 'select' | 'checkbox' | 'color'
  options?: { value: string | number; label: string }[]
  required?: boolean
  hint?: string
}

export interface CrudColumn<T> {
  label: string
  render: (row: T) => ReactNode
}

interface Props<T extends { id: number; active?: boolean }> {
  title: string
  subtitle?: string
  endpoint: string
  columns: CrudColumn<T>[]
  fields: CrudField[]
  /** Converte o registro da API para os valores do formulário. */
  toForm: (row: T) => Record<string, unknown>
  emptyForm: Record<string, unknown>
  deleteLabel?: string
}

/**
 * Tela de cadastro genérica (lista + modal de edição).
 * Cada cadastro só descreve colunas e campos; o comportamento é único.
 */
export function CrudPage<T extends { id: number; active?: boolean }>({ title, subtitle, endpoint, columns, fields, toForm, emptyForm, deleteLabel = 'Excluir' }: Props<T>) {
  const qc = useQueryClient()
  const toast = useToast()
  const [editing, setEditing] = useState<T | null | 'new'>(null)
  const [form, setForm] = useState<Record<string, unknown>>(emptyForm)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [error, setError] = useState<string | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: [endpoint],
    queryFn: async () => {
      const { data } = await api.get<T[] | { data: T[] }>(endpoint, { params: { per_page: 100 } })
      return Array.isArray(data) ? data : data.data // aceita lista simples ou paginada
    },
  })

  const invalidate = () => {
    qc.invalidateQueries({ queryKey: [endpoint] })
    qc.invalidateQueries({ queryKey: ['lookups'] })
  }

  const save = useMutation({
    mutationFn: () => {
      const payload = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v]))
      return editing === 'new' ? api.post(endpoint, payload) : api.patch(`${endpoint}/${(editing as T).id}`, payload)
    },
    onSuccess: () => { invalidate(); setEditing(null); toast('Registro salvo.') },
    onError: (e) => { setErrors(fieldErrors(e)); setError(errorMessage(e)) },
  })

  const remove = useMutation({
    mutationFn: (row: T) => api.delete(`${endpoint}/${row.id}`),
    onSuccess: () => { invalidate(); toast('Registro removido.') },
    onError: (e) => toast(errorMessage(e), 'error'),
  })

  const open = (row: T | 'new') => {
    setErrors({})
    setError(null)
    setForm(row === 'new' ? emptyForm : toForm(row))
    setEditing(row)
  }

  return (
    <div className="space-y-4">
      <PageHeader title={title} subtitle={subtitle} actions={<Button icon={<Plus className="size-4" />} onClick={() => open('new')}>Novo</Button>} />
      <div className="card overflow-x-auto">
        {isLoading ? <Spinner /> : !data?.length ? <EmptyState /> : (
          <table className="table-base">
            <thead>
              <tr>{columns.map((c) => <th key={c.label}>{c.label}</th>)}<th>Status</th><th className="w-24" /></tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.map((row) => (
                <tr key={row.id} className="hover:bg-slate-50">
                  {columns.map((c) => <td key={c.label}>{c.render(row)}</td>)}
                  <td>{row.active === false ? <Badge>Inativo</Badge> : <Badge tone="emerald">Ativo</Badge>}</td>
                  <td className="whitespace-nowrap text-right">
                    <button onClick={() => open(row)} className="rounded p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Editar"><Pencil className="size-4" /></button>
                    <button onClick={() => confirm(`${deleteLabel}?`) && remove.mutate(row)} className="rounded p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label={deleteLabel}><Trash2 className="size-4" /></button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      <Modal open={editing !== null} title={editing === 'new' ? `Novo - ${title}` : `Editar - ${title}`} onClose={() => setEditing(null)} footer={
        <>
          <Button variant="secondary" onClick={() => setEditing(null)}>Cancelar</Button>
          <Button loading={save.isPending} onClick={() => save.mutate()}>Salvar</Button>
        </>
      }>
        {error && <ErrorBox message={error} />}
        {fields.map((f) => {
          const value = form[f.name]
          const onChange = (v: unknown) => setForm((s) => ({ ...s, [f.name]: v }))
          if (f.type === 'checkbox') {
            return (
              <label key={f.name} className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={Boolean(value)} onChange={(e) => onChange(e.target.checked)} /> {f.label}
              </label>
            )
          }
          if (f.type === 'select') {
            return <Select key={f.name} label={f.label} required={f.required} hint={f.hint} error={errors[f.name]} placeholder="—"
              value={String(value ?? '')} onChange={(e) => onChange(e.target.value)} options={f.options ?? []} />
          }
          if (f.type === 'textarea') {
            return <TextArea key={f.name} label={f.label} required={f.required} error={errors[f.name]} value={String(value ?? '')} onChange={(e) => onChange(e.target.value)} />
          }
          return <TextInput key={f.name} label={f.label} type={f.type ?? 'text'} required={f.required} hint={f.hint} error={errors[f.name]}
            value={String(value ?? '')} onChange={(e) => onChange(e.target.value)} />
        })}
      </Modal>
    </div>
  )
}

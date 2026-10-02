import { X } from 'lucide-react'
import { Select } from '@/components/ui/Field'
import { useLookups } from '@/hooks/useLookups'
import { useAuth } from '@/auth/useAuth'

export type Filters = Record<string, string>

interface Props {
  filters: Filters
  onChange: (next: Filters) => void
  showStatus?: boolean
}

/** Barra de filtros compartilhada entre a lista de GAPs e o Kanban. */
export function GapFilters({ filters, onChange, showStatus = true }: Props) {
  const { data: lk } = useLookups()
  const { user } = useAuth()
  if (!lk) return null

  const set = (key: string, value: string) => {
    const next = { ...filters, [key]: value }
    if (!value) delete next[key]
    onChange(next)
  }
  const active = Object.keys(filters).filter((k) => !['page', 'sort', 'direction'].includes(k)).length

  return (
    <div className="card flex flex-wrap items-end gap-2 p-3">
      {user?.role.value !== 'lider' && (
        <Select className="w-40" label="Setor" placeholder="Todos" value={filters.sector_id ?? ''} onChange={(e) => set('sector_id', e.target.value)}
          options={lk.sectors.map((s) => ({ value: s.id, label: s.name }))} />
      )}
      <Select className="w-36" label="Prioridade" placeholder="Todas" value={filters.priority ?? ''} onChange={(e) => set('priority', e.target.value)} options={lk.enums.priorities} />
      <Select className="w-40" label="Sistema" placeholder="Todos" value={filters.system_id ?? ''} onChange={(e) => set('system_id', e.target.value)}
        options={lk.systems.map((s) => ({ value: s.id, label: s.name }))} />
      <Select className="w-44" label="Responsável TI" placeholder="Todos" value={filters.it_owner_id ?? ''} onChange={(e) => set('it_owner_id', e.target.value)}
        options={lk.users.filter((u) => u.role === 'ti' || u.role === 'admin').map((u) => ({ value: u.id, label: u.name }))} />
      <Select className="w-44" label="Sprint" placeholder="Todas" value={filters.sprint_id ?? ''} onChange={(e) => set('sprint_id', e.target.value)}
        options={[{ value: 'none', label: 'Sem sprint' }, ...lk.sprints.map((s) => ({ value: s.id, label: `Sprint ${s.number} - ${s.name}` }))]} />
      <Select className="w-36" label="Categoria" placeholder="Todas" value={filters.category_id ?? ''} onChange={(e) => set('category_id', e.target.value)}
        options={lk.categories.map((c) => ({ value: c.id, label: c.name }))} />
      {showStatus && (
        <>
          <Select className="w-36" label="Tipo" placeholder="Todos" value={filters.type ?? ''} onChange={(e) => set('type', e.target.value)} options={lk.enums.gap_types} />
          <Select className="w-40" label="Status" placeholder="Todos" value={filters.status ?? ''} onChange={(e) => set('status', e.target.value)}
            options={[...lk.enums.gap_statuses, ...lk.enums.incident_statuses.filter((s) => !lk.enums.gap_statuses.some((g) => g.value === s.value))]} />
          <label className="flex items-center gap-1.5 pb-2 text-sm text-slate-600">
            <input type="checkbox" checked={filters.open === '1'} onChange={(e) => set('open', e.target.checked ? '1' : '')} /> Só abertos
          </label>
          <label className="flex items-center gap-1.5 pb-2 text-sm text-slate-600">
            <input type="checkbox" checked={filters.overdue === '1'} onChange={(e) => set('overdue', e.target.checked ? '1' : '')} /> Atrasados
          </label>
        </>
      )}
      {active > 0 && (
        <button onClick={() => onChange({})} className="mb-1.5 inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs text-slate-500 hover:bg-slate-100">
          <X className="size-3" /> Limpar ({active})
        </button>
      )}
    </div>
  )
}

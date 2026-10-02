import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { ArrowDownUp, Plus } from 'lucide-react'
import { useState, type ReactNode } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { GapFilters, type Filters } from '@/components/gaps/GapFilters'
import { OverdueBadge, PriorityBadge, StatusBadge, TypeBadge } from '@/components/gaps/badges'
import { Button } from '@/components/ui/Button'
import { EmptyState, PageHeader, Spinner } from '@/components/ui/Card'
import { api } from '@/lib/api'
import { formatDate } from '@/lib/format'
import type { GapListItem, Paginated } from '@/types'

/** Cabeçalho ordenável. Declarado FORA do componente da página para não ser recriado a cada render. */
function SortTh({ field, onSort, children }: { field: string; onSort: (field: string) => void; children: ReactNode }) {
  return (
    <th>
      <button onClick={() => onSort(field)} className="inline-flex items-center gap-1 uppercase hover:text-slate-700">
        {children} <ArrowDownUp className="size-3" />
      </button>
    </th>
  )
}

export function GapsListPage() {
  const { can } = useAuth()
  const navigate = useNavigate()
  // Filtros vivem na URL: o usuário pode favoritar/compartilhar "GAPs críticos do Fiscal".
  const [params, setParams] = useSearchParams()
  const filters: Filters = Object.fromEntries(params.entries())
  const [q, setQ] = useState(filters.q ?? '')

  const { data, isLoading, isFetching } = useQuery({
    queryKey: ['gaps', filters],
    queryFn: async () => (await api.get<Paginated<GapListItem>>('/gaps', { params: { per_page: 20, ...filters } })).data,
    placeholderData: keepPreviousData, // mantém a tabela na tela enquanto troca de página
  })

  const update = (next: Filters) => setParams(next)
  const sortBy = (field: string) => {
    const direction = filters.sort === field && filters.direction !== 'asc' ? 'asc' : 'desc'
    update({ ...filters, sort: field, direction, page: '1' })
  }

  return (
    <div className="space-y-4">
      <PageHeader
        title="GAPs e Incidentes"
        subtitle={data ? `${data.meta.total} registro(s)` : undefined}
        actions={can('gaps.create') && (
          <Link to="/gaps/new"><Button icon={<Plus className="size-4" />}>Novo GAP</Button></Link>
        )}
      />

      <form onSubmit={(e) => { e.preventDefault(); update({ ...filters, q, page: '1' }) }}>
        <input value={q} onChange={(e) => setQ(e.target.value)} className="input max-w-md" placeholder="Filtrar por código, título ou descrição e pressione Enter" />
      </form>
      <GapFilters filters={filters} onChange={(f) => update({ ...f, ...(filters.q ? { q: filters.q } : {}) })} />

      <div className="card overflow-x-auto">
        {isLoading ? <Spinner /> : !data?.data.length ? <EmptyState /> : (
          <table className={`table-base ${isFetching ? 'opacity-60' : ''}`}>
            <thead>
              <tr>
                <SortTh onSort={sortBy} field="code">Código</SortTh>
                <SortTh onSort={sortBy} field="title">Título</SortTh>
                <th>Setor</th>
                <th>Sistemas</th>
                <SortTh onSort={sortBy} field="priority">Prioridade</SortTh>
                <SortTh onSort={sortBy} field="status">Status</SortTh>
                <th>Responsável TI</th>
                <th>Sprint</th>
                <SortTh onSort={sortBy} field="due_date">Prazo</SortTh>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.data.map((g) => (
                <tr key={g.id} onClick={() => navigate(`/gaps/${g.id}`)} className="cursor-pointer hover:bg-slate-50">
                  <td className="whitespace-nowrap font-mono text-xs font-semibold text-brand-700">{g.code}</td>
                  <td className="min-w-64">
                    <div className="font-medium text-slate-700">{g.title}</div>
                    <div className="mt-0.5 flex flex-wrap gap-1">
                      <TypeBadge type={g.type} />
                      {g.category && <span className="text-xs text-slate-400">{g.category.name}</span>}
                    </div>
                  </td>
                  <td className="whitespace-nowrap">{g.sector?.name}</td>
                  <td className="text-xs text-slate-500">{g.systems?.map((s) => s.name).join(', ')}</td>
                  <td><PriorityBadge priority={g.priority} /></td>
                  <td><StatusBadge status={g.status} /></td>
                  <td className="whitespace-nowrap">{g.it_owner?.name ?? <span className="text-slate-400">—</span>}</td>
                  <td className="whitespace-nowrap text-xs">{g.sprint ? `Sprint ${g.sprint.number}` : '—'}</td>
                  <td className="whitespace-nowrap">
                    {formatDate(g.due_date)} {g.is_overdue && <div><OverdueBadge /></div>}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {data && data.meta.last_page > 1 && (
        <div className="flex items-center justify-end gap-2 text-sm">
          <Button variant="secondary" size="sm" disabled={data.meta.current_page <= 1}
            onClick={() => update({ ...filters, page: String(data.meta.current_page - 1) })}>Anterior</Button>
          <span className="text-slate-500">Página {data.meta.current_page} de {data.meta.last_page}</span>
          <Button variant="secondary" size="sm" disabled={data.meta.current_page >= data.meta.last_page}
            onClick={() => update({ ...filters, page: String(data.meta.current_page + 1) })}>Próxima</Button>
        </div>
      )}
    </div>
  )
}

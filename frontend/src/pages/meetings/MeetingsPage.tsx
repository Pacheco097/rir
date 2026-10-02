import { useQuery } from '@tanstack/react-query'
import { CalendarDays, List, Plus } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { StatusBadge } from '@/components/gaps/badges'
import { Button } from '@/components/ui/Button'
import { EmptyState, PageHeader, Spinner } from '@/components/ui/Card'
import { Select } from '@/components/ui/Field'
import { useLookups } from '@/hooks/useLookups'
import { api } from '@/lib/api'
import { formatDate, toIsoDate } from '@/lib/format'
import type { Meeting, Paginated } from '@/types'
import { visibleRange, type CalendarMode } from '@/lib/calendar'
import { CalendarView } from './CalendarView'

export function MeetingsPage() {
  const { can } = useAuth()
  const navigate = useNavigate()
  const { data: lk } = useLookups()
  const [view, setView] = useState<'calendar' | 'list'>('calendar')
  const [mode, setMode] = useState<CalendarMode>('month')
  const [refDate, setRefDate] = useState(() => new Date())
  const [sector, setSector] = useState('')
  const [status, setStatus] = useState('')

  const range = visibleRange(mode, refDate)
  const params = view === 'calendar'
    ? { from: toIsoDate(range.from), to: toIsoDate(range.to), per_page: 200, direction: 'asc', sector_id: sector, status }
    : { per_page: 50, sector_id: sector, status }

  const { data, isLoading } = useQuery({
    queryKey: ['meetings', params],
    queryFn: async () => (await api.get<Paginated<Meeting>>('/meetings', { params })).data,
  })

  return (
    <div className="space-y-4">
      <PageHeader
        title="Reuniões e Agenda"
        subtitle="Reuniões de levantamento e acompanhamento com os líderes dos setores"
        actions={
          <>
            <div className="flex rounded-lg border border-slate-300 bg-white p-0.5">
              <button onClick={() => setView('calendar')} className={`rounded-md px-2.5 py-1.5 ${view === 'calendar' ? 'bg-slate-100' : ''}`} aria-label="Calendário"><CalendarDays className="size-4" /></button>
              <button onClick={() => setView('list')} className={`rounded-md px-2.5 py-1.5 ${view === 'list' ? 'bg-slate-100' : ''}`} aria-label="Lista"><List className="size-4" /></button>
            </div>
            {can('meetings.manage') && <Link to="/meetings/new"><Button icon={<Plus className="size-4" />}>Agendar reunião</Button></Link>}
          </>
        }
      />

      {lk && (
        <div className="card flex flex-wrap gap-2 p-3">
          <Select className="w-48" label="Setor" placeholder="Todos" value={sector} onChange={(e) => setSector(e.target.value)} options={lk.sectors.map((s) => ({ value: s.id, label: s.name }))} />
          <Select className="w-44" label="Status" placeholder="Todos" value={status} onChange={(e) => setStatus(e.target.value)} options={lk.enums.meeting_statuses} />
        </div>
      )}

      {isLoading ? <Spinner /> : view === 'calendar' ? (
        <CalendarView mode={mode} refDate={refDate} meetings={data?.data ?? []} onModeChange={setMode} onDateChange={setRefDate} />
      ) : (
        <div className="card overflow-x-auto">
          {!data?.data.length ? <EmptyState /> : (
            <table className="table-base">
              <thead><tr><th>Data</th><th>Título</th><th>Setor</th><th>Líder</th><th>Responsável TI</th><th>GAPs</th><th>Status</th></tr></thead>
              <tbody className="divide-y divide-slate-100">
                {data.data.map((m) => (
                  <tr key={m.id} className="cursor-pointer hover:bg-slate-50" onClick={() => navigate(`/meetings/${m.id}`)}>
                    <td className="whitespace-nowrap">{formatDate(m.date)} {m.start_time}</td>
                    <td className="font-medium text-slate-700">{m.title}</td>
                    <td>{m.sector?.name}</td>
                    <td>{m.leader?.name ?? '—'}</td>
                    <td>{m.it_owner?.name ?? '—'}</td>
                    <td>{m.gaps_count}</td>
                    <td><StatusBadge status={m.status} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}
    </div>
  )
}

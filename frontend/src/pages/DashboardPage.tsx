import { useQuery } from '@tanstack/react-query'
import {
  AlertOctagon, AlertTriangle, CheckCircle2, Clock, FlaskConical, Hourglass, Inbox, Layers, PauseCircle,
} from 'lucide-react'
import { Link } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { ColumnBars, EvolutionChart, HorizontalBars, SprintChart } from '@/components/charts'
import { priorityColor } from '@/lib/colors'
import { StatusBadge } from '@/components/gaps/badges'
import { Card, EmptyState, PageHeader, Spinner } from '@/components/ui/Card'
import { StatCard } from '@/components/ui/StatCard'
import { api } from '@/lib/api'
import { formatDate, formatShortDate } from '@/lib/format'
import type { DashboardData, DashboardGapItem } from '@/types'

function GapRows({ items, empty }: { items: DashboardGapItem[]; empty: string }) {
  if (!items.length) return <EmptyState message={empty} />
  return (
    <ul className="divide-y divide-slate-100">
      {items.map((g) => (
        <li key={g.id}>
          <Link to={`/gaps/${g.id}`} className="flex items-start gap-2 py-2 hover:bg-slate-50">
            <span className="w-20 shrink-0 font-mono text-xs font-semibold text-brand-700">{g.code}</span>
            <span className="min-w-0 flex-1">
              <span className="block truncate text-sm text-slate-700">{g.title}</span>
              <span className="text-xs text-slate-400">
                {g.sector} · {g.it_owner ?? 'Sem responsável TI'} {g.due_date && `· prazo ${formatDate(g.due_date)}`}
              </span>
            </span>
            <StatusBadge status={{ value: g.status, label: g.status_label }} />
          </Link>
        </li>
      ))}
    </ul>
  )
}

export function DashboardPage() {
  const { user } = useAuth()
  const { data, isLoading } = useQuery({
    queryKey: ['dashboard'],
    queryFn: async () => (await api.get<DashboardData>('/dashboard')).data,
  })

  if (isLoading || !data) return <Spinner />

  const t = data.totals
  const isLeader = user?.role.value === 'lider'

  return (
    <div className="space-y-5">
      <PageHeader
        title={isLeader ? `Dashboard do setor ${user?.sector?.name ?? ''}` : 'Gestão de Gaps - TI'}
        subtitle={isLeader ? 'Acompanhe as demandas do seu setor' : 'Visão executiva dos gaps, melhorias e incidentes'}
      />

      <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
        <StatCard label="Total de GAPs" value={t.total} icon={<Layers className="size-5" />} to="/gaps" />
        <StatCard label="Abertos" value={t.open} icon={<Inbox className="size-5" />} accent="violet" to="/gaps?open=1" />
        <StatCard label="Críticos" value={t.critical} icon={<AlertOctagon className="size-5" />} accent="red" to="/gaps?open=1&priority=critica" />
        <StatCard label="Atrasados" value={t.overdue} icon={<AlertTriangle className="size-5" />} accent="orange" to="/gaps?overdue=1" />
        <StatCard label="Concluídos" value={t.completed} icon={<CheckCircle2 className="size-5" />} accent="emerald" to="/gaps?status=concluido" />
        <StatCard label="Em andamento" value={t.in_progress} icon={<Clock className="size-5" />} accent="blue" to="/gaps?status=em_execucao" />
        <StatCard label="Bloqueados" value={t.blocked} icon={<PauseCircle className="size-5" />} accent="red" to="/gaps?status=bloqueado" />
        <StatCard label="Em homologação" value={t.homologation} icon={<FlaskConical className="size-5" />} accent="amber" to="/gaps?status=homologacao" />
        <StatCard label="Incidentes abertos" value={t.incidents_open} icon={<AlertOctagon className="size-5" />} accent="slate" to="/gaps?type=incidente&open=1" />
        <StatCard
          label="Tempo médio"
          value={t.avg_resolution_days !== null ? `${t.avg_resolution_days} d` : '—'}
          icon={<Hourglass className="size-5" />}
          accent="slate"
          hint="de resolução (identificação → conclusão)"
        />
      </div>

      <div className="grid gap-4 xl:grid-cols-3">
        {!isLeader && (
          <Card title="GAPs por setor" className="xl:col-span-1">
            <HorizontalBars data={data.by_sector} />
          </Card>
        )}
        <Card title="Próximas reuniões">
          {data.upcoming_meetings.length ? (
            <ul className="divide-y divide-slate-100">
              {data.upcoming_meetings.map((m) => (
                <li key={m.id}>
                  <Link to={`/meetings/${m.id}`} className="flex items-center gap-3 py-2 hover:bg-slate-50">
                    <span className="w-14 rounded-md bg-brand-50 py-1 text-center text-xs font-bold text-brand-700">{formatShortDate(m.date)}</span>
                    <span className="min-w-0 flex-1">
                      <span className="block truncate text-sm text-slate-700">{m.sector}</span>
                      <span className="text-xs text-slate-400">{m.start_time} · {m.title}</span>
                    </span>
                  </Link>
                </li>
              ))}
            </ul>
          ) : (
            <EmptyState message="Nenhuma reunião agendada." />
          )}
        </Card>
        <Card title="GAPs prioritários" actions={<Link to="/gaps?open=1&sort=priority" className="text-xs text-brand-600 hover:underline">ver todos</Link>}>
          <GapRows items={data.priority_gaps} empty="Nenhum GAP crítico ou de alta prioridade aberto." />
        </Card>
        {isLeader && (
          <Card title="Aguardando validação do setor">
            <GapRows items={data.pending_validation} empty="Nenhuma homologação pendente." />
          </Card>
        )}
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card title="Evolução dos GAPs (últimos 6 meses)">
          <EvolutionChart data={data.evolution} />
        </Card>
        <Card title="GAPs abertos por prioridade">
          <ColumnBars data={data.by_priority} colors={priorityColor} />
        </Card>
        <Card title="GAPs por sistema">
          <HorizontalBars data={data.by_system} />
        </Card>
        <Card title="GAPs por categoria">
          <HorizontalBars data={data.by_category} />
        </Card>
        <Card title="Planejados x concluídos por Sprint">
          <SprintChart data={data.completed_by_sprint} />
        </Card>
        <Card title="Distribuição por status">
          <HorizontalBars data={data.by_status} />
        </Card>
        <Card title="GAPs abertos por responsável TI">
          <HorizontalBars data={data.by_owner} />
        </Card>
        <Card title="GAPs atrasados" actions={<Link to="/gaps?overdue=1" className="text-xs text-brand-600 hover:underline">ver todos</Link>}>
          <GapRows items={data.overdue} empty="Nenhum GAP atrasado. 🎉" />
        </Card>
        <Card title="Problemas recorrentes (frequência diária/contínua)">
          <GapRows items={data.recurring} empty="Nenhum problema recorrente aberto." />
        </Card>
        {!isLeader && (
          <Card title="Aguardando validação dos setores">
            <GapRows items={data.pending_validation} empty="Nenhuma homologação pendente." />
          </Card>
        )}
        <Card title="Últimas reuniões realizadas">
          {data.recent_meetings.length ? (
            <ul className="divide-y divide-slate-100">
              {data.recent_meetings.map((m) => (
                <li key={m.id}>
                  <Link to={`/meetings/${m.id}`} className="flex justify-between gap-2 py-2 text-sm hover:bg-slate-50">
                    <span className="truncate text-slate-700">{m.title}</span>
                    <span className="shrink-0 text-xs text-slate-400">{formatDate(m.date)}</span>
                  </Link>
                </li>
              ))}
            </ul>
          ) : (
            <EmptyState message="Nenhuma reunião realizada." />
          )}
        </Card>
      </div>
    </div>
  )
}

import { useQuery } from '@tanstack/react-query'
import { ArrowRight } from 'lucide-react'
import { EmptyState, Spinner } from '@/components/ui/Card'
import { api } from '@/lib/api'
import { formatDateTime } from '@/lib/format'
import type { GapHistory } from '@/types'

/** Linha do tempo de auditoria: quem alterou o quê, quando, de/para. */
export function HistoryPanel({ gapId }: { gapId: number }) {
  const { data, isLoading } = useQuery({
    queryKey: ['gap', String(gapId), 'history'],
    queryFn: async () => (await api.get<GapHistory[]>(`/gaps/${gapId}/history`)).data,
  })

  if (isLoading) return <Spinner />
  if (!data?.length) return <EmptyState message="Sem histórico." />

  return (
    <ol className="relative space-y-4 border-l border-slate-200 pl-5">
      {data.map((h) => (
        <li key={h.id} className="relative">
          <span className="absolute -left-[25px] top-1.5 size-2.5 rounded-full bg-brand-500 ring-4 ring-white" />
          <div className="text-xs text-slate-400">{formatDateTime(h.created_at)}</div>
          <div className="text-sm text-slate-700">
            <span className="font-semibold">{h.user?.name ?? 'Sistema'}</span>{' '}
            {h.field === 'criado' ? (
              <>registrou <span className="font-medium">{h.new_value}</span></>
            ) : (
              <>
                alterou <span className="font-medium">{h.field_label.toLowerCase()}</span>:{' '}
                <span className="text-slate-500 line-through decoration-slate-300">{h.old_value ?? 'vazio'}</span>
                <ArrowRight className="mx-1 inline size-3 text-slate-400" />
                <span className="font-medium text-slate-800">{h.new_value ?? 'vazio'}</span>
              </>
            )}
          </div>
        </li>
      ))}
    </ol>
  )
}

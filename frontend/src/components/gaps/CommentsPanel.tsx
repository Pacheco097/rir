import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Send } from 'lucide-react'
import { useState } from 'react'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/Button'
import { EmptyState, Spinner } from '@/components/ui/Card'
import { useToast } from '@/components/ui/useToast'
import { api, errorMessage } from '@/lib/api'
import { formatDateTime, initials } from '@/lib/format'
import type { Gap, GapComment } from '@/types'

/** Comentários são somente inseridos (não editáveis) para preservar o histórico da tratativa. */
export function CommentsPanel({ gap }: { gap: Gap }) {
  const { can } = useAuth()
  const qc = useQueryClient()
  const toast = useToast()
  const [body, setBody] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['gap', String(gap.id), 'comments'],
    queryFn: async () => (await api.get<GapComment[]>(`/gaps/${gap.id}/comments`)).data,
  })

  const add = useMutation({
    mutationFn: async () => (await api.post(`/gaps/${gap.id}/comments`, { body })).data,
    onSuccess: () => {
      setBody('')
      qc.invalidateQueries({ queryKey: ['gap', String(gap.id), 'comments'] })
    },
    onError: (e) => toast(errorMessage(e), 'error'),
  })

  return (
    <div className="space-y-4">
      {can('comments.create') && (
        <div className="flex gap-2">
          <textarea value={body} onChange={(e) => setBody(e.target.value)} rows={2} className="input" placeholder="Registrar comentário, análise ou retorno..." />
          <Button onClick={() => add.mutate()} disabled={!body.trim()} loading={add.isPending} icon={<Send className="size-4" />}>Enviar</Button>
        </div>
      )}
      {isLoading ? <Spinner /> : !data?.length ? <EmptyState message="Nenhum comentário ainda." /> : (
        <ul className="space-y-3">
          {data.map((c) => (
            <li key={c.id} className="flex gap-3">
              <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600">{initials(c.user?.name)}</div>
              <div className="min-w-0 flex-1 rounded-lg bg-slate-50 px-3 py-2">
                <div className="text-xs text-slate-500">
                  <span className="font-semibold text-slate-700">{c.user?.sector ?? c.user?.role} - {c.user?.name}</span> · {formatDateTime(c.created_at)}
                </div>
                <p className="mt-1 whitespace-pre-wrap text-sm text-slate-700">{c.body}</p>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}

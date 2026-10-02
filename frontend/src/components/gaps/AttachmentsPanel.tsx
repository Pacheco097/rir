import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Download, Paperclip, Trash2, Upload } from 'lucide-react'
import { useRef } from 'react'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/Card'
import { useToast } from '@/components/ui/useToast'
import { api, errorMessage } from '@/lib/api'
import { formatBytes, formatDateTime } from '@/lib/format'
import type { Attachment } from '@/types'

/** Upload/download de evidências. Serve para GAP e para Reunião (endpoint polimórfico). */
export function AttachmentsPanel({ attachments, uploadUrl, queryKey, canUpload }: {
  attachments: Attachment[]
  uploadUrl: string
  queryKey: unknown[]
  canUpload: boolean
}) {
  const { user } = useAuth()
  const qc = useQueryClient()
  const toast = useToast()
  const input = useRef<HTMLInputElement>(null)

  const upload = useMutation({
    mutationFn: (file: File) => {
      const data = new FormData()
      data.append('file', file)
      return api.post(uploadUrl, data)
    },
    onSuccess: () => { qc.invalidateQueries({ queryKey }); toast('Arquivo anexado.') },
    onError: (e) => toast(errorMessage(e), 'error'),
  })
  const remove = useMutation({
    mutationFn: (id: number) => api.delete(`/attachments/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey }),
    onError: (e) => toast(errorMessage(e), 'error'),
  })

  // Download via API (com token) -> blob -> link temporário.
  const download = async (a: Attachment) => {
    const { data } = await api.get(`/attachments/${a.id}/download`, { responseType: 'blob' })
    const url = URL.createObjectURL(data)
    const link = Object.assign(document.createElement('a'), { href: url, download: a.original_name })
    link.click()
    URL.revokeObjectURL(url)
  }

  return (
    <div className="space-y-3">
      {canUpload && (
        <>
          <input ref={input} type="file" hidden onChange={(e) => { const f = e.target.files?.[0]; if (f) upload.mutate(f); e.target.value = '' }} />
          <Button variant="secondary" icon={<Upload className="size-4" />} loading={upload.isPending} onClick={() => input.current?.click()}>
            Anexar arquivo
          </Button>
          <p className="text-xs text-slate-400">Imagens, PDF, planilhas, documentos, CSV, TXT ou ZIP — até 20 MB.</p>
        </>
      )}
      {!attachments.length ? <EmptyState message="Nenhum anexo." /> : (
        <ul className="divide-y divide-slate-100">
          {attachments.map((a) => (
            <li key={a.id} className="flex items-center gap-3 py-2 text-sm">
              <Paperclip className="size-4 text-slate-400" />
              <div className="min-w-0 flex-1">
                <div className="truncate text-slate-700">{a.original_name}</div>
                <div className="text-xs text-slate-400">{formatBytes(a.size)} · {a.user?.name} · {formatDateTime(a.created_at)}</div>
              </div>
              <button onClick={() => download(a)} className="text-slate-500 hover:text-brand-700" aria-label="Baixar"><Download className="size-4" /></button>
              {(user?.role.value === 'admin' || user?.id === a.user?.id) && (
                <button onClick={() => remove.mutate(a.id)} className="text-slate-400 hover:text-red-600" aria-label="Remover"><Trash2 className="size-4" /></button>
              )}
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}

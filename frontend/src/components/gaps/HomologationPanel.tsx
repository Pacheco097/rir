import { CheckCircle2, Clock, XCircle } from 'lucide-react'
import { EmptyState } from '@/components/ui/Card'
import { formatDateTime } from '@/lib/format'
import type { Gap } from '@/types'

export function HomologationPanel({ gap }: { gap: Gap }) {
  const items = gap.homologations ?? []
  return (
    <div className="space-y-4">
      {(gap.solution || gap.solution_evidence) && (
        <div className="grid gap-3 md:grid-cols-2">
          <div className="rounded-lg bg-slate-50 p-3">
            <div className="label">Solução aplicada</div>
            <p className="whitespace-pre-wrap text-sm text-slate-700">{gap.solution ?? '—'}</p>
          </div>
          <div className="rounded-lg bg-slate-50 p-3">
            <div className="label">Evidência</div>
            <p className="whitespace-pre-wrap text-sm text-slate-700">{gap.solution_evidence ?? '—'}</p>
          </div>
        </div>
      )}
      {!items.length ? <EmptyState message="Ainda não enviado para homologação." /> : (
        <ul className="space-y-2">
          {items.map((h) => (
            <li key={h.id} className="rounded-lg border border-slate-200 p-3 text-sm">
              <div className="flex items-center gap-2 font-medium">
                {!h.result && <><Clock className="size-4 text-amber-500" /> Aguardando validação do setor</>}
                {h.result?.value === 'aprovado' && <><CheckCircle2 className="size-4 text-emerald-600" /> Aprovado</>}
                {h.result?.value === 'reprovado' && <><XCircle className="size-4 text-red-600" /> Reprovado</>}
              </div>
              <div className="mt-1 text-xs text-slate-500">
                Enviado por {h.submitted_by?.name} em {formatDateTime(h.submitted_at)}
                {h.tested_at && <> · Testado por <b>{h.tested_by?.name}</b> em {formatDateTime(h.tested_at)}</>}
              </div>
              {h.test_result && <p className="mt-2 text-slate-700"><span className="font-medium">Resultado do teste:</span> {h.test_result}</p>}
              {h.notes && <p className="text-slate-600"><span className="font-medium">Observação:</span> {h.notes}</p>}
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}

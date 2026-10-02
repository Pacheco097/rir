import { useMutation, useQueryClient } from '@tanstack/react-query'
import { ArrowRightLeft, CheckCheck, FlaskConical, RotateCcw, Shuffle } from 'lucide-react'
import { useState } from 'react'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/Button'
import { ErrorBox } from '@/components/ui/Card'
import { Select, TextArea } from '@/components/ui/Field'
import { Modal } from '@/components/ui/Modal'
import { useToast } from '@/components/ui/useToast'
import { api, errorMessage } from '@/lib/api'
import type { Gap } from '@/types'

type Dialog = null | 'transition' | 'send' | 'decide' | 'convert' | 'reopen'

const REASON_REQUIRED = ['bloqueado', 'cancelado', 'reaberto']

/**
 * Ações de workflow do GAP. Cada botão só aparece se:
 *  1) o perfil tem a permissão (RBAC) e
 *  2) o status atual permite a ação.
 * O backend valida tudo de novo — o frontend apenas evita mostrar o que daria erro.
 */
export function GapActions({ gap }: { gap: Gap }) {
  const { can, user } = useAuth()
  const qc = useQueryClient()
  const toast = useToast()
  const [dialog, setDialog] = useState<Dialog>(null)
  const [error, setError] = useState<string | null>(null)
  const [form, setForm] = useState<Record<string, string>>({})

  const open = (d: Dialog, initial: Record<string, string> = {}) => { setError(null); setForm(initial); setDialog(d) }
  const close = () => setDialog(null)
  const set = (k: string, v: string) => setForm((f) => ({ ...f, [k]: v }))

  const action = useMutation({
    mutationFn: async ({ url, body }: { url: string; body: object }) => (await api.post<Gap>(url, body)).data,
    onSuccess: (updated, vars) => {
      qc.setQueryData(['gap', String(gap.id)], updated)
      qc.invalidateQueries({ queryKey: ['gap', String(gap.id)] })
      qc.invalidateQueries({ queryKey: ['gaps'] })
      qc.invalidateQueries({ queryKey: ['dashboard'] })
      qc.invalidateQueries({ queryKey: ['kanban'] })
      toast(vars.url.endsWith('convert') ? `Convertido em ${updated.code}.` : `Status: ${updated.status.label}`)
      close()
    },
    onError: (e) => setError(errorMessage(e)),
  })

  const isGap = gap.type.value === 'gap'
  const status = gap.status.value
  // Destinos manuais: concluir e homologar têm fluxos próprios.
  const manualTargets = gap.allowed_transitions.filter((t) => !(isGap && ['concluido', 'homologacao'].includes(t.value)))
  const canDecide = status === 'homologacao' &&
    (user?.role.value === 'admin' || (can('gaps.homologation.decide') && user?.sector_id === gap.sector_id) || user?.id === gap.sector_owner_id)

  return (
    <>
      <div className="flex flex-wrap gap-2">
        {can('gaps.transition') && manualTargets.length > 0 && (
          <Button variant="secondary" icon={<ArrowRightLeft className="size-4" />} onClick={() => open('transition', { status: manualTargets[0].value })}>
            Alterar status
          </Button>
        )}
        {isGap && can('gaps.homologation.send') && ['em_execucao', 'reaberto'].includes(status) && (
          <Button variant="primary" icon={<FlaskConical className="size-4" />}
            onClick={() => open('send', { solution: gap.solution ?? '', solution_evidence: gap.solution_evidence ?? '' })}>
            Enviar para homologação
          </Button>
        )}
        {isGap && canDecide && (
          <Button variant="success" icon={<CheckCheck className="size-4" />} onClick={() => open('decide', { approved: '1' })}>
            Validar solução
          </Button>
        )}
        {isGap && status === 'concluido' && !can('gaps.transition') && can('gaps.reopen') && (
          <Button variant="secondary" icon={<RotateCcw className="size-4" />} onClick={() => open('reopen')}>Reabrir demanda</Button>
        )}
        {!isGap && can('gaps.convert') && (
          <Button variant="secondary" icon={<Shuffle className="size-4" />} onClick={() => open('convert')}>Converter em GAP/Melhoria</Button>
        )}
      </div>

      <Modal open={dialog === 'transition'} title="Alterar status" onClose={close} footer={
        <>
          <Button variant="secondary" onClick={close}>Cancelar</Button>
          <Button loading={action.isPending} onClick={() => action.mutate({ url: `/gaps/${gap.id}/transition`, body: { status: form.status, reason: form.reason } })}>Confirmar</Button>
        </>
      }>
        {error && <ErrorBox message={error} />}
        <p className="text-sm text-slate-600">Status atual: <b>{gap.status.label}</b></p>
        <Select label="Novo status" value={form.status ?? ''} onChange={(e) => set('status', e.target.value)} options={manualTargets} />
        <TextArea label="Motivo / observação" required={REASON_REQUIRED.includes(form.status)} value={form.reason ?? ''} onChange={(e) => set('reason', e.target.value)}
          hint="Fica registrado nos comentários do GAP." />
      </Modal>

      <Modal open={dialog === 'send'} title="Enviar para homologação" onClose={close} footer={
        <>
          <Button variant="secondary" onClick={close}>Cancelar</Button>
          <Button loading={action.isPending} onClick={() => action.mutate({ url: `/gaps/${gap.id}/homologation/send`, body: form })}>Enviar ao setor</Button>
        </>
      }>
        {error && <ErrorBox message={error} />}
        <p className="text-sm text-slate-600">O líder do setor <b>{gap.sector?.name}</b> testará a solução e aprovará ou reprovará.</p>
        <TextArea label="Solução aplicada" required rows={4} value={form.solution ?? ''} onChange={(e) => set('solution', e.target.value)} />
        <TextArea label="Evidência" required rows={2} value={form.solution_evidence ?? ''} onChange={(e) => set('solution_evidence', e.target.value)}
          hint="Descreva os testes realizados. Prints e arquivos podem ser incluídos na aba Anexos." />
      </Modal>

      <Modal open={dialog === 'decide'} title="Validar solução (homologação)" onClose={close} footer={
        <>
          <Button variant="secondary" onClick={close}>Cancelar</Button>
          <Button variant={form.approved === '1' ? 'success' : 'danger'} loading={action.isPending}
            onClick={() => action.mutate({ url: `/gaps/${gap.id}/homologation/decide`, body: { approved: form.approved === '1', test_result: form.test_result, notes: form.notes } })}>
            {form.approved === '1' ? 'Aprovar e concluir' : 'Reprovar e reabrir'}
          </Button>
        </>
      }>
        {error && <ErrorBox message={error} />}
        <div className="rounded-lg bg-slate-50 p-3 text-sm">
          <div className="label">Solução informada pelo TI</div>
          <p className="whitespace-pre-wrap">{gap.solution}</p>
        </div>
        <div className="grid grid-cols-2 gap-2">
          {[['1', 'Aprovado', 'emerald'], ['0', 'Reprovado', 'red']].map(([v, label, color]) => (
            <label key={v} className={`flex cursor-pointer items-center gap-2 rounded-lg border p-3 text-sm font-medium ${form.approved === v ? (color === 'emerald' ? 'border-emerald-500 bg-emerald-50' : 'border-red-500 bg-red-50') : 'border-slate-200'}`}>
              <input type="radio" name="approved" checked={form.approved === v} onChange={() => set('approved', v)} /> {label}
            </label>
          ))}
        </div>
        <TextArea label="Resultado do teste" required value={form.test_result ?? ''} onChange={(e) => set('test_result', e.target.value)}
          placeholder="Ex.: Testado com 10 notas reais, todas corretas." />
        <TextArea label="Observação" value={form.notes ?? ''} onChange={(e) => set('notes', e.target.value)} />
        <p className="text-xs text-slate-400">Seu nome e a data/hora serão registrados automaticamente.</p>
      </Modal>

      <Modal open={dialog === 'reopen'} title="Reabrir demanda" onClose={close} footer={
        <>
          <Button variant="secondary" onClick={close}>Cancelar</Button>
          <Button variant="danger" loading={action.isPending} onClick={() => action.mutate({ url: `/gaps/${gap.id}/transition`, body: { status: 'reaberto', reason: form.reason } })}>Reabrir</Button>
        </>
      }>
        {error && <ErrorBox message={error} />}
        <TextArea label="Por que a solução não resolveu o problema?" required value={form.reason ?? ''} onChange={(e) => set('reason', e.target.value)} />
      </Modal>

      <Modal open={dialog === 'convert'} title="Converter incidente em GAP/Melhoria" onClose={close} footer={
        <>
          <Button variant="secondary" onClick={close}>Cancelar</Button>
          <Button loading={action.isPending} onClick={() => action.mutate({ url: `/gaps/${gap.id}/convert`, body: form })}>Converter</Button>
        </>
      }>
        {error && <ErrorBox message={error} />}
        <p className="text-sm text-slate-600">O incidente receberá um novo código GAP, entrará em <b>Triagem</b> e poderá ser planejado em uma Sprint. O histórico é mantido.</p>
        <TextArea label="Motivo" value={form.reason ?? ''} onChange={(e) => set('reason', e.target.value)} placeholder="Ex.: a causa raiz exige desenvolvimento." />
      </Modal>
    </>
  )
}

import { AlertTriangle, Flame } from 'lucide-react'
import { Badge, type Tone } from '@/components/ui/Badge'
import type { Option } from '@/types'

/** Mapa de cores por status — uma única fonte da verdade para tabela, Kanban e detalhe. */
const statusTones: Record<string, Tone> = {
  levantado: 'slate', triagem: 'cyan', analise: 'cyan', priorizado: 'indigo', backlog: 'indigo',
  planejado: 'blue', em_execucao: 'violet', homologacao: 'amber', concluido: 'emerald',
  cancelado: 'slate', bloqueado: 'red', reaberto: 'orange',
  // incidentes
  novo: 'rose', em_atendimento: 'violet', aguardando: 'amber', resolvido: 'emerald', validacao: 'amber', encerrado: 'emerald',
  // reuniões / sprints
  agendada: 'blue', confirmada: 'indigo', realizada: 'emerald', cancelada: 'slate', reagendada: 'amber',
  planejamento: 'slate', em_andamento: 'violet', encerrada: 'emerald',
}

const priorityTones: Record<string, Tone> = { critica: 'red', alta: 'orange', media: 'amber', baixa: 'slate' }

export function StatusBadge({ status }: { status: Option | null | undefined }) {
  if (!status) return null
  return <Badge tone={statusTones[status.value] ?? 'slate'}>{status.label}</Badge>
}

export function PriorityBadge({ priority }: { priority: Option | null | undefined }) {
  if (!priority) return null
  return (
    <Badge tone={priorityTones[priority.value] ?? 'slate'}>
      {priority.value === 'critica' && <Flame className="size-3" />}
      {priority.label}
    </Badge>
  )
}

export function OverdueBadge() {
  return (
    <Badge tone="red">
      <AlertTriangle className="size-3" /> Atrasado
    </Badge>
  )
}

export function TypeBadge({ type }: { type: Option }) {
  return type.value === 'incidente' ? <Badge tone="rose">Incidente</Badge> : null
}

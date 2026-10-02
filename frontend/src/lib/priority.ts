/**
 * Espelho da matriz Impacto x Urgência do backend (App\Enums\Priority::suggest).
 * Serve só para mostrar a sugestão ao vivo no formulário; quem decide é a API.
 */
const impactWeight: Record<string, number> = { critico: 4, alto: 3, medio: 2, baixo: 1 }
const urgencyWeight: Record<string, number> = { imediata: 4, curto_prazo: 3, medio_prazo: 2, sem_urgencia: 1 }

export function suggestPriority(impact?: string, urgency?: string): string | null {
  if (!impact || !urgency) return null
  const score = impactWeight[impact] + urgencyWeight[urgency]
  if (score >= 7) return 'critica'
  if (score >= 5) return 'alta'
  if (score >= 4) return 'media'
  return 'baixa'
}

import { suggestPriority } from './priority'

// Mantém o espelho da matriz em sincronia com App\Enums\Priority::suggest (backend).
describe('suggestPriority', () => {
  it.each([
    ['critico', 'imediata', 'critica'],
    ['alto', 'curto_prazo', 'alta'],
    ['medio', 'curto_prazo', 'alta'],
    ['medio', 'medio_prazo', 'media'],
    ['baixo', 'sem_urgencia', 'baixa'],
  ])('%s + %s = %s', (impact, urgency, expected) => {
    expect(suggestPriority(impact, urgency)).toBe(expected)
  })

  it('retorna null se faltar impacto ou urgência', () => {
    expect(suggestPriority('alto', '')).toBeNull()
  })
})

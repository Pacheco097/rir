import { toIsoDate } from '@/lib/format'
import { visibleRange } from './calendar'

describe('visibleRange', () => {
  it('semana começa no domingo', () => {
    const { from, to } = visibleRange('week', new Date(2026, 9, 2)) // sexta 02/10/2026
    expect(toIsoDate(from)).toBe('2026-09-27')
    expect(toIsoDate(to)).toBe('2026-10-03')
  })

  it('mês cobre semanas completas', () => {
    const { from, to } = visibleRange('month', new Date(2026, 9, 15))
    expect(from.getDay()).toBe(0)
    expect(to.getDay()).toBe(6)
    expect(toIsoDate(from) <= '2026-10-01').toBe(true)
    expect(toIsoDate(to) >= '2026-10-31').toBe(true)
  })
})

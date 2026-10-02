export type CalendarMode = 'month' | 'week' | 'day'

/** Calcula o intervalo de datas visível para o modo atual (usado também no filtro da API). */
export function visibleRange(mode: CalendarMode, ref: Date): { from: Date; to: Date } {
  const d = new Date(ref.getFullYear(), ref.getMonth(), ref.getDate())
  if (mode === 'day') return { from: d, to: d }
  if (mode === 'week') {
    const from = new Date(d)
    from.setDate(d.getDate() - d.getDay())
    const to = new Date(from)
    to.setDate(from.getDate() + 6)
    return { from, to }
  }
  const first = new Date(d.getFullYear(), d.getMonth(), 1)
  const from = new Date(first)
  from.setDate(1 - first.getDay())
  const last = new Date(d.getFullYear(), d.getMonth() + 1, 0)
  const to = new Date(last)
  to.setDate(last.getDate() + (6 - last.getDay()))
  return { from, to }
}

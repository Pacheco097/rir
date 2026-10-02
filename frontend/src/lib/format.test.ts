import { formatBytes, formatDate, initials, toIsoDate } from './format'

describe('format', () => {
  it('formata datas ISO no padrão brasileiro sem sofrer com fuso', () => {
    expect(formatDate('2026-10-02')).toBe('02/10/2026')
    expect(formatDate(null)).toBe('—')
  })

  it('gera data ISO local', () => {
    expect(toIsoDate(new Date(2026, 0, 5))).toBe('2026-01-05')
  })

  it('gera iniciais', () => {
    expect(initials('Matheus Santos Pacheco')).toBe('MP')
    expect(initials('Ana')).toBe('A')
  })

  it('formata tamanho de arquivo', () => {
    expect(formatBytes(2048)).toBe('2 KB')
  })
})

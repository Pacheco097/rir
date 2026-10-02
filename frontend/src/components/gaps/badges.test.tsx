import { render, screen } from '@testing-library/react'
import { PriorityBadge, StatusBadge } from './badges'

describe('badges', () => {
  it('exibe o rótulo do status vindo da API', () => {
    render(<StatusBadge status={{ value: 'homologacao', label: 'Homologação' }} />)
    expect(screen.getByText('Homologação')).toBeInTheDocument()
  })

  it('destaca prioridade crítica', () => {
    render(<PriorityBadge priority={{ value: 'critica', label: 'Crítica' }} />)
    expect(screen.getByText('Crítica').className).toContain('bg-red-50')
  })
})

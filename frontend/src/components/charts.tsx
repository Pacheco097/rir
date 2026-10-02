import {
  Bar, BarChart, CartesianGrid, LabelList, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis,
} from 'recharts'
import type { CountItem } from '@/types'

/**
 * Gráficos reutilizáveis do dashboard.
 * Regras visuais: uma cor por gráfico de série única (a cor não carrega
 * significado extra), rótulo de valor direto na barra, grade discreta,
 * no máximo duas séries com legenda e tooltip em todos.
 */
const SERIES = { primary: '#2a78d6', secondary: '#eb6834' }
const AXIS = { fontSize: 11, fill: '#64748b' }
const GRID = '#e2e8f0'

/** Barras horizontais: melhor para rótulos longos (setor, sistema, responsável). */
export function HorizontalBars({ data, color = SERIES.primary, height }: { data: CountItem[]; color?: string; height?: number }) {
  const h = height ?? Math.max(160, data.length * 30 + 20)
  return (
    <ResponsiveContainer width="100%" height={h}>
      <BarChart data={data} layout="vertical" margin={{ left: 8, right: 32, top: 4, bottom: 4 }}>
        <CartesianGrid horizontal={false} stroke={GRID} />
        <XAxis type="number" allowDecimals={false} tick={AXIS} axisLine={false} tickLine={false} />
        <YAxis type="category" dataKey="label" width={120} tick={AXIS} axisLine={false} tickLine={false} />
        <Tooltip cursor={{ fill: '#f1f5f9' }} formatter={(v) => [v, 'GAPs']} />
        <Bar dataKey="total" fill={color} radius={[0, 4, 4, 0]} barSize={16}>
          <LabelList dataKey="total" position="right" style={{ fontSize: 11, fill: '#334155', fontWeight: 600 }} />
        </Bar>
      </BarChart>
    </ResponsiveContainer>
  )
}

/** Barras verticais com cor por item (usado em prioridade, onde a cor já é semântica e vem com rótulo). */
export function ColumnBars({ data, colors }: { data: CountItem[]; colors?: Record<string, string> }) {
  return (
    <ResponsiveContainer width="100%" height={220}>
      <BarChart data={data} margin={{ top: 20, right: 8, left: -16, bottom: 0 }}>
        <CartesianGrid vertical={false} stroke={GRID} />
        <XAxis dataKey="label" tick={AXIS} axisLine={false} tickLine={false} />
        <YAxis allowDecimals={false} tick={AXIS} axisLine={false} tickLine={false} />
        <Tooltip cursor={{ fill: '#f1f5f9' }} formatter={(v) => [v, 'GAPs']} />
        <Bar
          dataKey="total"
          radius={[4, 4, 0, 0]}
          barSize={36}
          fill={SERIES.primary}
          shape={(props: { x?: number; y?: number; width?: number; height?: number; payload?: CountItem }) => {
            const { x = 0, y = 0, width = 0, height = 0, payload } = props
            const fill = (payload?.key && colors?.[payload.key]) || SERIES.primary
            const r = Math.min(4, height)
            return <path d={`M${x},${y + height} V${y + r} Q${x},${y} ${x + r},${y} H${x + width - r} Q${x + width},${y} ${x + width},${y + r} V${y + height} Z`} fill={fill} />
          }}
        >
          <LabelList dataKey="total" position="top" style={{ fontSize: 11, fill: '#334155', fontWeight: 600 }} />
        </Bar>
      </BarChart>
    </ResponsiveContainer>
  )
}

export function EvolutionChart({ data }: { data: { month: string; created: number; completed: number }[] }) {
  return (
    <ResponsiveContainer width="100%" height={240}>
      <LineChart data={data} margin={{ top: 8, right: 16, left: -16, bottom: 0 }}>
        <CartesianGrid vertical={false} stroke={GRID} />
        <XAxis dataKey="month" tick={AXIS} axisLine={false} tickLine={false} />
        <YAxis allowDecimals={false} tick={AXIS} axisLine={false} tickLine={false} />
        <Tooltip />
        <Legend iconType="plainline" wrapperStyle={{ fontSize: 12 }} />
        <Line type="monotone" dataKey="created" name="Abertos" stroke={SERIES.primary} strokeWidth={2} dot={{ r: 4 }} activeDot={{ r: 6 }} />
        <Line type="monotone" dataKey="completed" name="Concluídos" stroke={SERIES.secondary} strokeWidth={2} dot={{ r: 4 }} activeDot={{ r: 6 }} />
      </LineChart>
    </ResponsiveContainer>
  )
}

export function SprintChart({ data }: { data: { label: string; total: number; completed: number }[] }) {
  return (
    <ResponsiveContainer width="100%" height={240}>
      <BarChart data={data} margin={{ top: 16, right: 8, left: -16, bottom: 0 }} barGap={2}>
        <CartesianGrid vertical={false} stroke={GRID} />
        <XAxis dataKey="label" tick={AXIS} axisLine={false} tickLine={false} />
        <YAxis allowDecimals={false} tick={AXIS} axisLine={false} tickLine={false} />
        <Tooltip cursor={{ fill: '#f1f5f9' }} />
        <Legend iconType="square" wrapperStyle={{ fontSize: 12 }} />
        <Bar dataKey="total" name="Planejados" fill={SERIES.primary} radius={[4, 4, 0, 0]} barSize={20} />
        <Bar dataKey="completed" name="Concluídos" fill={SERIES.secondary} radius={[4, 4, 0, 0]} barSize={20} />
      </BarChart>
    </ResponsiveContainer>
  )
}

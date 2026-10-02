import { Search } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { api } from '@/lib/api'
import { formatDate } from '@/lib/format'

interface SearchResult {
  redirect: string | null
  gaps: { id: number; code: string; title: string; sector: string | null; status: string }[]
  meetings: { id: number; title: string; date: string; sector: string | null }[]
}

/**
 * Busca global. Digitar um código (ex.: GAP-0025) e pressionar Enter abre o GAP direto.
 * O "debounce" espera o usuário parar de digitar (300ms) antes de chamar a API,
 * evitando uma requisição por tecla.
 */
export function GlobalSearch() {
  const navigate = useNavigate()
  const [term, setTerm] = useState('')
  const [result, setResult] = useState<SearchResult | null>(null)
  const [open, setOpen] = useState(false)
  const boxRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (term.trim().length < 2) return
    const timer = setTimeout(async () => {
      const { data } = await api.get<SearchResult>('/search', { params: { q: term } })
      setResult(data)
      setOpen(true)
    }, 300)
    return () => clearTimeout(timer)
  }, [term])

  useEffect(() => {
    const onClick = (e: MouseEvent) => {
      if (!boxRef.current?.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', onClick)
    return () => document.removeEventListener('mousedown', onClick)
  }, [])

  const go = (path: string) => {
    setOpen(false)
    setTerm('')
    navigate(path)
  }

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (term.trim().length < 2) return
    const { data } = await api.get<SearchResult>('/search', { params: { q: term } })
    if (data.redirect) return go(data.redirect)
    if (data.gaps.length === 1) return go(`/gaps/${data.gaps[0].id}`)
    setResult(data)
    setOpen(true)
  }

  // Valor derivado: com menos de 2 caracteres, simplesmente não mostramos resultados.
  const visible = term.trim().length >= 2 ? result : null
  const empty = visible && visible.gaps.length === 0 && visible.meetings.length === 0

  return (
    <div ref={boxRef} className="relative w-full max-w-md">
      <form onSubmit={onSubmit}>
        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
        <input
          value={term}
          onChange={(e) => setTerm(e.target.value)}
          onFocus={() => result && setOpen(true)}
          placeholder="Buscar GAP-0025, título, setor, sistema, sprint..."
          className="input pl-9"
          aria-label="Busca global"
        />
      </form>
      {open && visible && (
        <div className="card absolute left-0 right-0 top-full z-40 mt-1 max-h-96 overflow-y-auto p-1 shadow-lg">
          {empty && <p className="px-3 py-4 text-center text-sm text-slate-400">Nada encontrado para “{term}”.</p>}
          {visible.gaps.length > 0 && <p className="px-3 pt-2 text-[11px] font-semibold uppercase text-slate-400">GAPs</p>}
          {visible.gaps.map((g) => (
            <button key={g.id} onClick={() => go(`/gaps/${g.id}`)} className="block w-full rounded-md px-3 py-2 text-left text-sm hover:bg-slate-50">
              <span className="font-mono text-xs font-semibold text-brand-700">{g.code}</span> {g.title}
              <span className="block text-xs text-slate-400">{g.sector} · {g.status}</span>
            </button>
          ))}
          {visible.meetings.length > 0 && <p className="px-3 pt-2 text-[11px] font-semibold uppercase text-slate-400">Reuniões</p>}
          {visible.meetings.map((m) => (
            <button key={m.id} onClick={() => go(`/meetings/${m.id}`)} className="block w-full rounded-md px-3 py-2 text-left text-sm hover:bg-slate-50">
              {m.title}
              <span className="block text-xs text-slate-400">{formatDate(m.date)} · {m.sector}</span>
            </button>
          ))}
        </div>
      )}
    </div>
  )
}

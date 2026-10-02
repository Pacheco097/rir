import { LogIn } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/Button'
import { ErrorBox } from '@/components/ui/Card'
import { TextInput } from '@/components/ui/Field'
import { errorMessage } from '@/lib/api'

const demoUsers = [
  { label: 'Administrador', email: 'admin@empresa.com' },
  { label: 'TI', email: 'matheus@empresa.com' },
  { label: 'Líder (Fiscal)', email: 'joao.fiscal@empresa.com' },
  { label: 'Consulta', email: 'diretoria@empresa.com' },
]

export function LoginPage() {
  const { user, login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  if (user) return <Navigate to="/" replace />

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError(null)
    try {
      await login(email, password)
      const from = (location.state as { from?: string } | null)?.from ?? '/'
      navigate(from, { replace: true })
    } catch (err) {
      setError(errorMessage(err))
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-full items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-brand-900 p-4">
      <div className="w-full max-w-sm">
        <div className="mb-6 text-center text-white">
          <div className="mx-auto mb-3 flex size-12 items-center justify-center rounded-xl bg-brand-600 text-xl font-bold">G</div>
          <h1 className="text-xl font-bold">Gestão de Gaps - TI</h1>
          <p className="text-sm text-slate-300">Central de Gestão de Gaps e Melhorias</p>
        </div>
        <form onSubmit={onSubmit} className="card space-y-4 p-6">
          {error && <ErrorBox message={error} />}
          <TextInput label="E-mail" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required autoFocus autoComplete="username" />
          <TextInput label="Senha" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required autoComplete="current-password" />
          <Button type="submit" className="w-full" loading={loading} icon={<LogIn className="size-4" />}>
            Entrar
          </Button>
        </form>
        {import.meta.env.DEV && (
          <div className="mt-4 rounded-xl bg-white/10 p-3 text-xs text-slate-200">
            <p className="mb-2 font-semibold">Usuários de demonstração (senha: password)</p>
            <div className="grid grid-cols-2 gap-1.5">
              {demoUsers.map((u) => (
                <button
                  key={u.email}
                  type="button"
                  onClick={() => { setEmail(u.email); setPassword('password') }}
                  className="rounded-md bg-white/10 px-2 py-1.5 text-left hover:bg-white/20"
                >
                  {u.label}
                </button>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

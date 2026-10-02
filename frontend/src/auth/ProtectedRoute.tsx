import type { ReactNode } from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { Spinner } from '@/components/ui/Card'
import { useAuth } from './useAuth'

/**
 * Guarda de rota: sem login -> /login; sem permissão -> aviso.
 * Importante: isso é só UX. A segurança real está na API (middleware + policies).
 */
export function ProtectedRoute({ children, permission, adminOnly }: { children: ReactNode; permission?: string; adminOnly?: boolean }) {
  const { user, loading, can } = useAuth()
  const location = useLocation()

  if (loading) return <Spinner label="Verificando sessão..." />
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname + location.search }} />
  if ((permission && !can(permission)) || (adminOnly && user.role.value !== 'admin')) {
    return <div className="card p-8 text-center text-slate-500">Você não tem permissão para acessar esta página.</div>
  }
  return <>{children}</>
}

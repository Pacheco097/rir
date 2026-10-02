import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { api, TOKEN_KEY } from '@/lib/api'
import type { User } from '@/types'
import { AuthContext } from './useAuth'

/**
 * Context API: disponibiliza o usuário logado para toda a árvore de
 * componentes sem precisar passar "props" de pai para filho.
 */
export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(() => Boolean(localStorage.getItem(TOKEN_KEY)))

  useEffect(() => {
    if (!localStorage.getItem(TOKEN_KEY)) return
    api
      .get<User>('/auth/me')
      .then(({ data }) => setUser(data))
      .catch(() => localStorage.removeItem(TOKEN_KEY))
      .finally(() => setLoading(false))
  }, [])

  // Disparado pelo interceptor do axios quando a API responde 401.
  useEffect(() => {
    const onLogout = () => setUser(null)
    window.addEventListener('auth:logout', onLogout)
    return () => window.removeEventListener('auth:logout', onLogout)
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    const { data } = await api.post<{ token: string; user: User }>('/auth/login', { email, password })
    localStorage.setItem(TOKEN_KEY, data.token)
    setUser(data.user)
  }, [])

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout')
    } finally {
      localStorage.removeItem(TOKEN_KEY)
      setUser(null)
    }
  }, [])

  const can = useCallback(
    (permission: string) => {
      const permissions = user?.permissions ?? []
      return permissions.includes('*') || permissions.includes(permission)
    },
    [user],
  )

  const value = useMemo(() => ({ user, loading, login, logout, can }), [user, loading, login, logout, can])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

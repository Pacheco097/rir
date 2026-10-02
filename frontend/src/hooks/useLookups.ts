import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { Lookups } from '@/types'

/**
 * Listas de apoio (enums, setores, sistemas...). Ficam em cache por 5 min:
 * todos os formulários e filtros reutilizam a mesma resposta.
 */
export function useLookups() {
  return useQuery({
    queryKey: ['lookups'],
    queryFn: async () => (await api.get<Lookups>('/lookups')).data,
    staleTime: 5 * 60 * 1000,
  })
}

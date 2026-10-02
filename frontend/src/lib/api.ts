import axios, { AxiosError } from 'axios'

/**
 * Cliente HTTP único da aplicação.
 *
 * Interceptors funcionam como "middlewares" do lado do cliente:
 *  - request: injeta o token em toda chamada;
 *  - response: se a API responder 401 (token expirado), limpa a sessão.
 */
export const TOKEN_KEY = 'gaps.token'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401 && !error.config?.url?.includes('auth/login')) {
      localStorage.removeItem(TOKEN_KEY)
      window.dispatchEvent(new Event('auth:logout'))
    }
    return Promise.reject(error)
  },
)

interface ApiErrorBody {
  message?: string
  errors?: Record<string, string[]>
}

/** Extrai a mensagem amigável de um erro da API (422, 403, 500...). */
export function errorMessage(error: unknown): string {
  if (axios.isAxiosError<ApiErrorBody>(error)) {
    const body = error.response?.data
    const firstFieldError = body?.errors ? Object.values(body.errors)[0]?.[0] : undefined
    return firstFieldError ?? body?.message ?? error.message
  }
  return error instanceof Error ? error.message : 'Erro inesperado.'
}

/** Erros por campo (validação 422) para exibir abaixo de cada input. */
export function fieldErrors(error: unknown): Record<string, string> {
  if (axios.isAxiosError<ApiErrorBody>(error) && error.response?.data?.errors) {
    return Object.fromEntries(Object.entries(error.response.data.errors).map(([k, v]) => [k, v[0]]))
  }
  return {}
}

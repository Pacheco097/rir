import clsx from 'clsx'
import { useCallback, useState, type ReactNode } from 'react'
import { ToastContext, type ToastType } from './useToast'

interface ToastItem { id: number; type: ToastType; message: string }

/** Notificações rápidas de feedback ("GAP salvo", "Erro ao mover card"...). */
export function ToastProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<ToastItem[]>([])

  const push = useCallback((message: string, type: ToastType = 'success') => {
    const id = Date.now() + Math.random()
    setItems((list) => [...list, { id, type, message }])
    setTimeout(() => setItems((list) => list.filter((t) => t.id !== id)), 4500)
  }, [])

  return (
    <ToastContext.Provider value={push}>
      {children}
      <div className="fixed bottom-4 right-4 z-[60] flex w-80 flex-col gap-2" aria-live="polite">
        {items.map((t) => (
          <div
            key={t.id}
            className={clsx(
              'rounded-lg px-4 py-3 text-sm shadow-lg ring-1',
              t.type === 'success' && 'bg-emerald-600 text-white ring-emerald-700',
              t.type === 'error' && 'bg-red-600 text-white ring-red-700',
              t.type === 'info' && 'bg-slate-800 text-white ring-slate-900',
            )}
          >
            {t.message}
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}

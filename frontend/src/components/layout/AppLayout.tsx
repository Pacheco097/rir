import clsx from 'clsx'
import {
  CalendarDays, FolderKanban, LayoutDashboard, ListTodo, LogOut, Menu, Monitor, Plus,
  Rocket, Tags, Users, Building2, X,
} from 'lucide-react'
import { Suspense, useState, type ReactNode } from 'react'
import { Link, NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { Spinner } from '@/components/ui/Card'
import { initials } from '@/lib/format'
import { GlobalSearch } from './GlobalSearch'

interface NavItem {
  to: string
  label: string
  icon: ReactNode
  permission?: string
}

const mainNav: NavItem[] = [
  { to: '/', label: 'Dashboard', icon: <LayoutDashboard className="size-4" /> },
  { to: '/gaps', label: 'GAPs e Incidentes', icon: <ListTodo className="size-4" />, permission: 'gaps.view' },
  { to: '/kanban', label: 'Kanban', icon: <FolderKanban className="size-4" />, permission: 'gaps.view' },
  { to: '/sprints', label: 'Sprints', icon: <Rocket className="size-4" />, permission: 'sprints.view' },
  { to: '/meetings', label: 'Reuniões e Agenda', icon: <CalendarDays className="size-4" />, permission: 'meetings.view' },
]

const adminNav: NavItem[] = [
  { to: '/admin/users', label: 'Usuários', icon: <Users className="size-4" /> },
  { to: '/admin/sectors', label: 'Setores', icon: <Building2 className="size-4" /> },
  { to: '/admin/systems', label: 'Sistemas', icon: <Monitor className="size-4" /> },
  { to: '/admin/categories', label: 'Categorias', icon: <Tags className="size-4" /> },
]

export function AppLayout() {
  const { user, can, logout } = useAuth()
  const [mobileOpen, setMobileOpen] = useState(false)

  const renderNav = (items: NavItem[]) =>
    items
      .filter((i) => !i.permission || can(i.permission))
      .map((i) => (
        <NavLink
          key={i.to}
          to={i.to}
          end={i.to === '/'}
          onClick={() => setMobileOpen(false)}
          className={({ isActive }) =>
            clsx(
              'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
              isActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white',
            )
          }
        >
          {i.icon}
          {i.label}
        </NavLink>
      ))

  return (
    <div className="flex h-full">
      {/* Sidebar */}
      <aside
        className={clsx(
          'fixed inset-y-0 left-0 z-40 flex w-60 flex-col bg-slate-900 transition-transform lg:static lg:translate-x-0',
          mobileOpen ? 'translate-x-0' : '-translate-x-full',
        )}
      >
        <div className="flex items-center gap-2 px-5 py-5">
          <div className="flex size-8 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">G</div>
          <div className="leading-tight">
            <div className="text-sm font-bold text-white">Gestão de Gaps</div>
            <div className="text-[11px] text-slate-400">Central de Melhorias de TI</div>
          </div>
          <button className="ml-auto text-slate-400 lg:hidden" onClick={() => setMobileOpen(false)} aria-label="Fechar menu">
            <X className="size-5" />
          </button>
        </div>
        <nav className="flex-1 space-y-1 overflow-y-auto px-3">
          {renderNav(mainNav)}
          {user?.role.value === 'admin' && (
            <>
              <p className="px-3 pb-1 pt-5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Administração</p>
              {renderNav(adminNav)}
            </>
          )}
        </nav>
        <div className="border-t border-white/10 p-3">
          <div className="flex items-center gap-2.5 px-2">
            <div className="flex size-8 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">
              {initials(user?.name)}
            </div>
            <div className="min-w-0 flex-1 leading-tight">
              <div className="truncate text-sm font-medium text-white">{user?.name}</div>
              <div className="truncate text-[11px] text-slate-400">
                {user?.role.label}
                {user?.sector ? ` · ${user.sector.name}` : ''}
              </div>
            </div>
            <button onClick={logout} className="rounded p-1.5 text-slate-400 hover:bg-white/10 hover:text-white" title="Sair" aria-label="Sair">
              <LogOut className="size-4" />
            </button>
          </div>
        </div>
      </aside>
      {mobileOpen && <div className="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" onClick={() => setMobileOpen(false)} />}

      {/* Conteúdo */}
      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur lg:px-6">
          <button className="text-slate-600 lg:hidden" onClick={() => setMobileOpen(true)} aria-label="Abrir menu">
            <Menu className="size-5" />
          </button>
          <GlobalSearch />
          {can('gaps.create') && (
            <Link
              to="/gaps/new"
              className="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
            >
              <Plus className="size-4" /> <span className="hidden sm:inline">Novo GAP</span>
            </Link>
          )}
        </header>
        <main className="flex-1 overflow-y-auto p-4 lg:p-6">
          {/* Suspense mostra o spinner enquanto o JS da página (lazy) é baixado. */}
          <Suspense fallback={<Spinner />}>
            <Outlet />
          </Suspense>
        </main>
      </div>
    </div>
  )
}

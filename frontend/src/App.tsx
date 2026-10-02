import { lazy } from 'react'
import { Link, Route, Routes } from 'react-router-dom'
import { ProtectedRoute } from '@/auth/ProtectedRoute'
import { AppLayout } from '@/components/layout/AppLayout'
import { LoginPage } from '@/pages/LoginPage'

/*
 * Code splitting: cada página vira um arquivo JS separado, baixado só quando
 * o usuário navega até ela. O login abre rápido e o Recharts (pesado) só
 * é carregado no Dashboard.
 */
const DashboardPage = lazy(() => import('@/pages/DashboardPage').then((m) => ({ default: m.DashboardPage })))
const GapsListPage = lazy(() => import('@/pages/gaps/GapsListPage').then((m) => ({ default: m.GapsListPage })))
const GapFormPage = lazy(() => import('@/pages/gaps/GapFormPage').then((m) => ({ default: m.GapFormPage })))
const GapDetailPage = lazy(() => import('@/pages/gaps/GapDetailPage').then((m) => ({ default: m.GapDetailPage })))
const KanbanPage = lazy(() => import('@/pages/KanbanPage').then((m) => ({ default: m.KanbanPage })))
const MeetingsPage = lazy(() => import('@/pages/meetings/MeetingsPage').then((m) => ({ default: m.MeetingsPage })))
const MeetingFormPage = lazy(() => import('@/pages/meetings/MeetingFormPage').then((m) => ({ default: m.MeetingFormPage })))
const MeetingDetailPage = lazy(() => import('@/pages/meetings/MeetingDetailPage').then((m) => ({ default: m.MeetingDetailPage })))
const SprintsPage = lazy(() => import('@/pages/sprints/SprintsPage').then((m) => ({ default: m.SprintsPage })))
const SprintDetailPage = lazy(() => import('@/pages/sprints/SprintDetailPage').then((m) => ({ default: m.SprintDetailPage })))
const UsersPage = lazy(() => import('@/pages/admin/AdminPages').then((m) => ({ default: m.UsersPage })))
const SectorsPage = lazy(() => import('@/pages/admin/AdminPages').then((m) => ({ default: m.SectorsPage })))
const SystemsPage = lazy(() => import('@/pages/admin/AdminPages').then((m) => ({ default: m.SystemsPage })))
const CategoriesPage = lazy(() => import('@/pages/admin/AdminPages').then((m) => ({ default: m.CategoriesPage })))

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route element={<ProtectedRoute><AppLayout /></ProtectedRoute>}>
        <Route index element={<DashboardPage />} />

        <Route path="gaps" element={<ProtectedRoute permission="gaps.view"><GapsListPage /></ProtectedRoute>} />
        <Route path="gaps/new" element={<ProtectedRoute permission="gaps.create"><GapFormPage /></ProtectedRoute>} />
        <Route path="gaps/:id" element={<ProtectedRoute permission="gaps.view"><GapDetailPage /></ProtectedRoute>} />
        <Route path="gaps/:id/edit" element={<ProtectedRoute><GapFormPage /></ProtectedRoute>} />
        <Route path="kanban" element={<ProtectedRoute permission="gaps.view"><KanbanPage /></ProtectedRoute>} />

        <Route path="meetings" element={<ProtectedRoute permission="meetings.view"><MeetingsPage /></ProtectedRoute>} />
        <Route path="meetings/new" element={<ProtectedRoute permission="meetings.manage"><MeetingFormPage /></ProtectedRoute>} />
        <Route path="meetings/:id" element={<ProtectedRoute permission="meetings.view"><MeetingDetailPage /></ProtectedRoute>} />
        <Route path="meetings/:id/edit" element={<ProtectedRoute permission="meetings.manage"><MeetingFormPage /></ProtectedRoute>} />

        <Route path="sprints" element={<ProtectedRoute permission="sprints.view"><SprintsPage /></ProtectedRoute>} />
        <Route path="sprints/:id" element={<ProtectedRoute permission="sprints.view"><SprintDetailPage /></ProtectedRoute>} />

        <Route path="admin/users" element={<ProtectedRoute adminOnly><UsersPage /></ProtectedRoute>} />
        <Route path="admin/sectors" element={<ProtectedRoute adminOnly><SectorsPage /></ProtectedRoute>} />
        <Route path="admin/systems" element={<ProtectedRoute adminOnly><SystemsPage /></ProtectedRoute>} />
        <Route path="admin/categories" element={<ProtectedRoute adminOnly><CategoriesPage /></ProtectedRoute>} />

        <Route path="*" element={<div className="card p-8 text-center text-slate-500">Página não encontrada. <Link to="/" className="text-brand-700">Voltar ao início</Link></div>} />
      </Route>
    </Routes>
  )
}

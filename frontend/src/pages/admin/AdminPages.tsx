import { Badge } from '@/components/ui/Badge'
import { Spinner } from '@/components/ui/Card'
import { useLookups } from '@/hooks/useLookups'
import type { Category, Sector, SystemItem, User } from '@/types'
import { CrudPage } from './CrudPage'

export function UsersPage() {
  const { data: lk } = useLookups()
  if (!lk) return <Spinner />
  return (
    <CrudPage<User>
      title="Usuários"
      subtitle="Perfis: Administrador, TI, Líder de Setor e Consulta"
      endpoint="/users"
      deleteLabel="Desativar usuário"
      columns={[
        { label: 'Nome', render: (u) => <><div className="font-medium">{u.name}</div><div className="text-xs text-slate-400">{u.job_title}</div></> },
        { label: 'E-mail', render: (u) => u.email },
        { label: 'Perfil', render: (u) => <Badge tone={u.role.value === 'admin' ? 'violet' : u.role.value === 'ti' ? 'blue' : u.role.value === 'lider' ? 'amber' : 'slate'}>{u.role.label}</Badge> },
        { label: 'Setor', render: (u) => u.sector?.name ?? '—' },
      ]}
      fields={[
        { name: 'name', label: 'Nome', required: true },
        { name: 'email', label: 'E-mail', type: 'email', required: true },
        { name: 'password', label: 'Senha', type: 'password', hint: 'Mínimo 8 caracteres. Na edição, deixe em branco para manter.' },
        { name: 'role', label: 'Perfil', type: 'select', required: true, options: lk.enums.roles },
        { name: 'sector_id', label: 'Setor', type: 'select', options: lk.sectors.map((s) => ({ value: s.id, label: s.name })), hint: 'Obrigatório para Líder de Setor.' },
        { name: 'job_title', label: 'Cargo' },
        { name: 'active', label: 'Ativo', type: 'checkbox' },
      ]}
      emptyForm={{ name: '', email: '', password: '', role: 'consulta', sector_id: '', job_title: '', active: true }}
      toForm={(u) => ({ name: u.name, email: u.email, password: '', role: u.role.value, sector_id: u.sector_id ?? '', job_title: u.job_title ?? '', active: u.active })}
    />
  )
}

export function SectorsPage() {
  const { data: lk } = useLookups()
  if (!lk) return <Spinner />
  return (
    <CrudPage<Sector>
      title="Setores"
      endpoint="/sectors"
      columns={[
        { label: 'Nome', render: (s) => <span className="font-medium">{s.name}</span> },
        { label: 'Descrição', render: (s) => <span className="text-slate-500">{s.description}</span> },
        { label: 'Líder', render: (s) => s.leader?.name ?? '—' },
        { label: 'GAPs', render: (s) => s.gaps_count },
      ]}
      fields={[
        { name: 'name', label: 'Nome', required: true },
        { name: 'description', label: 'Descrição', type: 'textarea' },
        { name: 'leader_id', label: 'Líder responsável', type: 'select', options: lk.users.map((u) => ({ value: u.id, label: u.name })) },
        { name: 'active', label: 'Ativo', type: 'checkbox' },
      ]}
      emptyForm={{ name: '', description: '', leader_id: '', active: true }}
      toForm={(s) => ({ name: s.name, description: s.description ?? '', leader_id: s.leader_id ?? '', active: s.active })}
    />
  )
}

export function SystemsPage() {
  const { data: lk } = useLookups()
  if (!lk) return <Spinner />
  return (
    <CrudPage<SystemItem>
      title="Sistemas"
      endpoint="/systems"
      columns={[
        { label: 'Nome', render: (s) => <span className="font-medium">{s.name}</span> },
        { label: 'Tipo', render: (s) => <Badge>{s.type.label}</Badge> },
        { label: 'Responsável', render: (s) => s.owner?.name ?? '—' },
        { label: 'GAPs', render: (s) => s.gaps_count },
      ]}
      fields={[
        { name: 'name', label: 'Nome', required: true },
        { name: 'description', label: 'Descrição', type: 'textarea' },
        { name: 'type', label: 'Tipo', type: 'select', required: true, options: lk.enums.system_types },
        { name: 'owner_id', label: 'Responsável', type: 'select', options: lk.users.map((u) => ({ value: u.id, label: u.name })) },
        { name: 'active', label: 'Ativo', type: 'checkbox' },
      ]}
      emptyForm={{ name: '', description: '', type: 'erp', owner_id: '', active: true }}
      toForm={(s) => ({ name: s.name, description: s.description ?? '', type: s.type.value, owner_id: s.owner_id ?? '', active: s.active })}
    />
  )
}

export function CategoriesPage() {
  return (
    <CrudPage<Category>
      title="Categorias de GAP"
      endpoint="/categories"
      columns={[
        { label: 'Nome', render: (c) => <span className="inline-flex items-center gap-2 font-medium"><span className="size-3 rounded-full" style={{ background: c.color ?? '#94a3b8' }} />{c.name}</span> },
        { label: 'Descrição', render: (c) => <span className="text-slate-500">{c.description}</span> },
        { label: 'GAPs', render: (c) => c.gaps_count },
      ]}
      fields={[
        { name: 'name', label: 'Nome', required: true },
        { name: 'description', label: 'Descrição' },
        { name: 'color', label: 'Cor', type: 'color' },
        { name: 'active', label: 'Ativa', type: 'checkbox' },
      ]}
      emptyForm={{ name: '', description: '', color: '#2563eb', active: true }}
      toForm={(c) => ({ name: c.name, description: c.description ?? '', color: c.color ?? '#2563eb', active: c.active })}
    />
  )
}

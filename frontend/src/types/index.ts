/**
 * Tipos do domínio — espelham o JSON devolvido pelas API Resources do Laravel.
 * Manter os tipos num único arquivo ajuda o TypeScript a apontar quebras
 * quando o contrato da API mudar.
 */

export interface Option<T extends string = string> {
  value: T
  label: string
}

export interface Ref {
  id: number
  name: string
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

export type RoleValue = 'admin' | 'ti' | 'lider' | 'consulta'

export interface User {
  id: number
  name: string
  email: string
  role: Option<RoleValue>
  sector_id: number | null
  sector?: Ref | null
  job_title: string | null
  active: boolean
  permissions?: string[]
}

export interface Sector {
  id: number
  name: string
  description: string | null
  leader_id: number | null
  leader?: Ref | null
  active: boolean
  gaps_count?: number
}

export interface SystemItem {
  id: number
  name: string
  description: string | null
  type: Option
  owner_id: number | null
  owner?: Ref | null
  active: boolean
  gaps_count?: number
}

export interface Category {
  id: number
  name: string
  description: string | null
  color: string | null
  active: boolean
  gaps_count?: number
}

export interface Sprint {
  id: number
  number: number
  name: string
  label: string
  goal: string | null
  start_date: string
  end_date: string
  status: Option<'planejamento' | 'em_andamento' | 'encerrada'>
  gaps_count?: number
  completed_count?: number
  gaps?: GapListItem[]
}

export interface GapListItem {
  id: number
  code: string
  type: Option<'gap' | 'incidente'>
  title: string
  status: Option
  priority: Option<'critica' | 'alta' | 'media' | 'baixa'>
  impact: Option
  urgency: Option
  sector?: Ref | null
  category?: (Ref & { color: string | null }) | null
  it_owner?: Ref | null
  sector_owner?: Ref | null
  sprint?: { id: number; number: number; name: string } | null
  systems?: Ref[]
  identified_at: string
  due_date: string | null
  is_overdue: boolean
  tasks_count?: number
  comments_count?: number
  created_at: string
}

export interface Task {
  id: number
  gap_id: number
  title: string
  description: string | null
  assignee_id: number | null
  assignee?: Ref | null
  status: Option<'a_fazer' | 'em_andamento' | 'concluida'>
  due_date: string | null
}

export interface Homologation {
  id: number
  submitted_by?: Ref | null
  submitted_at: string
  tested_by?: Ref | null
  tested_at: string | null
  result: Option<'aprovado' | 'reprovado'> | null
  test_result: string | null
  notes: string | null
}

export interface Attachment {
  id: number
  original_name: string
  mime_type: string | null
  size: number
  user?: Ref | null
  created_at: string
}

export interface Gap extends GapListItem {
  description: string
  process: string | null
  origin: Option | null
  frequency: Option | null
  critical_justification: string | null
  notes: string | null
  sector_id: number
  category_id: number | null
  it_owner_id: number | null
  sector_owner_id: number | null
  sprint_id: number | null
  solution: string | null
  solution_evidence: string | null
  completed_at: string | null
  completed_by?: Ref | null
  created_by?: Ref | null
  meetings?: { id: number; title: string; date: string }[]
  related_gaps?: GapListItem[]
  tasks?: Task[]
  homologations?: Homologation[]
  attachments?: Attachment[]
  allowed_transitions: Option[]
  updated_at: string
}

export interface GapComment {
  id: number
  body: string
  user: { id: number; name: string; sector: string | null; role: string } | null
  created_at: string
}

export interface GapHistory {
  id: number
  field: string
  field_label: string
  old_value: string | null
  new_value: string | null
  user?: Ref | null
  created_at: string
}

export interface AgendaItem {
  key: string
  label: string
  content: string | null
}

export interface Meeting {
  id: number
  title: string
  sector_id: number
  sector?: Ref | null
  leader_id: number | null
  leader?: Ref | null
  it_owner_id: number | null
  it_owner?: Ref | null
  date: string
  start_time: string
  end_time: string | null
  objective: string | null
  status: Option<'agendada' | 'confirmada' | 'realizada' | 'cancelada' | 'reagendada'>
  notes: string | null
  agenda: AgendaItem[]
  minutes: string | null
  summary: string | null
  decisions: string | null
  next_steps: string | null
  previous_meeting_id: number | null
  participants?: Ref[]
  gaps?: GapListItem[]
  gaps_count?: number
  attachments?: Attachment[]
}

export interface KanbanColumnData {
  key: string
  label: string
  gaps: GapListItem[]
}

export interface Lookups {
  enums: {
    gap_types: Option[]
    gap_statuses: Option[]
    incident_statuses: Option[]
    impacts: (Option & { description: string })[]
    urgencies: Option[]
    priorities: Option[]
    origins: Option[]
    frequencies: Option[]
    meeting_statuses: Option[]
    sprint_statuses: Option[]
    task_statuses: Option[]
    system_types: Option[]
    roles: Option<RoleValue>[]
    kanban_columns: Option[]
  }
  agenda_topics: { key: string; label: string }[]
  sectors: { id: number; name: string; leader_id: number | null }[]
  systems: Ref[]
  categories: (Ref & { color: string | null })[]
  sprints: { id: number; number: number; name: string; status: string }[]
  users: { id: number; name: string; role: RoleValue; sector_id: number | null }[]
}

export interface CountItem {
  id?: number | null
  key?: string
  label: string
  total: number
}

export interface DashboardGapItem {
  id: number
  code: string
  title: string
  status: string
  status_label: string
  priority: string
  sector: string | null
  it_owner: string | null
  due_date: string | null
}

export interface DashboardMeetingItem {
  id: number
  title: string
  date: string
  start_time: string
  sector: string | null
  status: string
}

export interface DashboardData {
  sector?: Ref
  totals: {
    total: number
    open: number
    critical: number
    in_progress: number
    blocked: number
    homologation: number
    completed: number
    overdue: number
    incidents_open: number
    avg_resolution_days: number | null
  }
  by_sector: CountItem[]
  by_category: CountItem[]
  by_owner: CountItem[]
  by_system: CountItem[]
  by_priority: CountItem[]
  by_status: CountItem[]
  evolution: { month: string; created: number; completed: number }[]
  completed_by_sprint: { id: number; label: string; name: string; status: string; total: number; completed: number }[]
  overdue: DashboardGapItem[]
  priority_gaps: DashboardGapItem[]
  recurring: DashboardGapItem[]
  pending_validation: DashboardGapItem[]
  upcoming_meetings: DashboardMeetingItem[]
  recent_meetings: DashboardMeetingItem[]
}

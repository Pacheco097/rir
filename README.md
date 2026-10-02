# Central de Gestão de Gaps, Reuniões e Melhorias de TI

Aplicação web interna do TI para transformar as necessidades levantadas com os setores em um **backlog organizado, rastreável e mensurável**: reuniões com líderes → GAPs → priorização → Sprints → execução → homologação pelo setor → indicadores.

| Camada | Tecnologia |
|---|---|
| Backend (API REST) | PHP 8.3+, Laravel 13, Sanctum (token), Eloquent |
| Banco | MySQL 8 / MariaDB (SQLite para testes e para um teste rápido) |
| Frontend | React 19, TypeScript, Vite, Tailwind CSS 4, React Query, Recharts |
| Testes | PHPUnit (backend) e Vitest + Testing Library (frontend) |

📐 **Arquitetura, ERD, endpoints e plano de etapas:** [`docs/ARQUITETURA.md`](docs/ARQUITETURA.md)

---

## Funcionalidades do MVP

- **Login e RBAC**: perfis Administrador, TI, Líder de Setor e Consulta (o líder enxerga só o próprio setor).
- **Cadastros**: usuários, setores, sistemas e categorias configuráveis.
- **Reuniões e agenda**: pauta estruturada (8 tópicos), ata, decisões e próximos passos; calendário mensal, semanal e diário; "criar próxima reunião"; GAPs gerados pela reunião.
- **GAPs e incidentes**: código automático (`GAP-0001` / `INC-0001`), prioridade sugerida pela matriz Impacto x Urgência, workflow com máquina de estados, tarefas, GAPs relacionados, anexos e conversão de incidente em GAP.
- **Homologação**: o TI envia a solução e a evidência; o líder do setor aprova (→ Concluído) ou reprova (→ Reaberto).
- **Comentários e histórico/auditoria**: toda mudança de status, prioridade, responsável, sprint, prazo e categoria é registrada (quem, quando, de → para).
- **Kanban** com drag and drop e filtros por setor, responsável, prioridade, sistema, sprint e categoria.
- **Sprints (Scrum)**: planejamento a partir do backlog e acompanhamento por etapa.
- **Dashboard executivo e por setor**: KPIs, gráficos, atrasados, recorrentes e pendências de validação.
- **Busca global**: digitar `GAP-0025` e pressionar Enter abre o GAP direto.

---

## Como executar localmente

### Pré-requisitos

- PHP 8.3+ com as extensões `pdo_mysql` (ou `pdo_sqlite`), `mbstring`, `xml`, `curl` e `zip`
- Composer 2
- Node.js 20+ (testado com o 22) e npm
- MySQL 8 ou MariaDB 10.6+ (ou Docker; ou apenas SQLite)

### 1. Backend (API)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

**Banco de dados.** Escolha uma das opções:

- **MySQL via Docker** (recomendado): na raiz do projeto, rode `docker compose up -d`. Depois, no `backend/.env`, use `DB_USERNAME=gaps` e `DB_PASSWORD=secret`.
- **MySQL/MariaDB já instalado**: crie o banco `gestao_gaps` (`CREATE DATABASE gestao_gaps CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`) e ajuste as variáveis `DB_*` no `.env`.
- **SQLite** (teste rápido, sem servidor de banco): no `.env`, defina `DB_CONNECTION=sqlite`, comente as linhas `DB_HOST` a `DB_PASSWORD` e crie o arquivo com `touch database/database.sqlite`.

Depois de configurar o banco:

```bash
php artisan migrate --seed   # cria as tabelas e carrega os dados fictícios
php artisan serve            # API em http://localhost:8000
```

### 2. Frontend

Em outro terminal:

```bash
cd frontend
npm install
cp .env.example .env
npm run dev                  # http://localhost:5173
```

Em desenvolvimento, o Vite repassa (proxy) as chamadas de `/api` para `http://127.0.0.1:8000`, então não é preciso configurar CORS.

### 3. Acessar

Abra http://localhost:5173. Todos os usuários de demonstração usam a senha **`password`**:

| Perfil | E-mail |
|---|---|
| Administrador | `admin@empresa.com` |
| TI | `matheus@empresa.com`, `carla@empresa.com`, `rafael@empresa.com` |
| Líder de setor | `joao.fiscal@empresa.com`, `patricia.financeiro@empresa.com`, `marcos.compras@empresa.com`... |
| Consulta | `diretoria@empresa.com` |

Em modo de desenvolvimento, a tela de login mostra atalhos para preencher esses usuários.

Para recriar o banco do zero com os dados fictícios: `php artisan migrate:fresh --seed`.

---

## Testes e qualidade

```bash
# Backend
cd backend
php artisan test          # 40 testes (regras de negócio, permissões, workflow, kanban, dashboard)
vendor/bin/pint           # formatação (padrão Laravel)

# Frontend
cd frontend
npm test                  # Vitest
npm run lint              # oxlint
npm run typecheck         # TypeScript
npm run build             # build de produção em dist/
```

Os testes do backend usam SQLite em memória (`phpunit.xml`), então não dependem do MySQL.

---

## Estrutura do repositório

```
.
├── backend/            API Laravel (ver docs/ARQUITETURA.md para o detalhamento)
├── frontend/           SPA React + TypeScript
├── docs/
│   └── ARQUITETURA.md  arquitetura, ERD, endpoints, regras e roadmap
└── docker-compose.yml  MySQL para desenvolvimento
```

## Deploy (resumo)

- **Backend**: `composer install --no-dev --optimize-autoloader`, `APP_ENV=production`, `APP_DEBUG=false`, `php artisan migrate --force` (**sem** `--seed` de usuários fictícios: em produção rode apenas `php artisan db:seed --class=CatalogSeeder`), `php artisan config:cache route:cache`. Configure `FRONTEND_URL` com o domínio do frontend (CORS).
- **Frontend**: `VITE_API_URL=https://seu-backend/api npm run build` e publique a pasta `dist/` em qualquer servidor estático (Nginx/IIS), redirecionando as rotas desconhecidas para `index.html`.

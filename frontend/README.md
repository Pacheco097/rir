# Frontend — React + TypeScript + Vite

Instruções de execução no [README principal](../README.md).

```bash
npm run dev        # desenvolvimento (http://localhost:5173, proxy /api -> :8000)
npm test           # testes (Vitest)
npm run lint       # oxlint
npm run typecheck  # TypeScript
npm run build      # build de produção (dist/)
```

## Organização

```
src/
├── auth/          contexto de autenticação, hook useAuth e guarda de rotas
├── components/
│   ├── ui/        componentes visuais genéricos (Button, Card, Modal, Field, Toast...)
│   ├── layout/    menu lateral, barra superior e busca global
│   ├── gaps/      componentes do domínio GAP (badges, filtros, ações, painéis)
│   └── charts.tsx gráficos do dashboard
├── hooks/         hooks de dados compartilhados (useLookups)
├── lib/           cliente HTTP (axios), formatação, regras espelhadas e utilitários
├── pages/         uma pasta por módulo (gaps, meetings, sprints, admin)
├── types/         tipos TypeScript que espelham o JSON da API
└── test/          configuração dos testes
```

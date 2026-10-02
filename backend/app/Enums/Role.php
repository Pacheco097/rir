<?php

namespace App\Enums;

/**
 * Perfis de acesso (RBAC).
 *
 * Cada perfil carrega sua lista de permissões. Manter isso em um Enum
 * deixa as regras versionadas junto com o código e fáceis de testar.
 * Se no futuro for preciso editar permissões pela tela, basta migrar
 * este mapa para tabelas roles/permissions sem alterar os controllers,
 * pois eles só conhecem as strings de permissão (ex.: "gaps.create").
 */
enum Role: string
{
    case Admin = 'admin';
    case TI = 'ti';
    case Lider = 'lider';
    case Consulta = 'consulta';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::TI => 'TI',
            self::Lider => 'Líder de Setor',
            self::Consulta => 'Visualização/Consulta',
        };
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => ['*'],
            self::TI => [
                'meetings.view', 'meetings.manage',
                'gaps.view', 'gaps.create', 'gaps.update', 'gaps.classify', 'gaps.assign',
                'gaps.transition', 'gaps.homologation.send', 'gaps.close', 'gaps.convert',
                'tasks.manage', 'sprints.view', 'sprints.assign',
                'comments.create', 'attachments.create', 'dashboard.view',
            ],
            self::Lider => [
                'meetings.view',
                'gaps.view', 'gaps.create', 'gaps.complement',
                'gaps.homologation.decide', 'gaps.reopen',
                'sprints.view', 'comments.create', 'attachments.create', 'dashboard.sector',
            ],
            self::Consulta => [
                'meetings.view', 'gaps.view', 'sprints.view', 'dashboard.view',
            ],
        };
    }

    public function can(string $permission): bool
    {
        $permissions = $this->permissions();

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    /** Líderes enxergam apenas os dados do próprio setor. */
    public function isSectorScoped(): bool
    {
        return $this === self::Lider;
    }
}

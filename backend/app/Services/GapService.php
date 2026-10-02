<?php

namespace App\Services;

use App\Enums\GapStatus;
use App\Enums\GapType;
use App\Enums\Impact;
use App\Enums\IncidentStatus;
use App\Enums\Priority;
use App\Enums\Urgency;
use App\Exceptions\BusinessRuleException;
use App\Models\Gap;
use App\Models\GapComment;
use App\Models\Sector;
use App\Models\User;
use App\Observers\GapObserver;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Regras de negócio do GAP (Service Layer).
 *
 * O controller apenas recebe a requisição validada e chama este serviço.
 * Assim as regras ficam em um único lugar, reaproveitáveis (API, Kanban,
 * comandos agendados, importações) e fáceis de testar.
 */
class GapService
{
    /** Campos que cada permissão pode alterar no update genérico. */
    private const COMPLEMENT_FIELDS = ['description', 'process', 'frequency', 'notes', 'system_ids'];

    private const CLASSIFY_FIELDS = ['impact', 'urgency', 'priority', 'critical_justification', 'category_id', 'origin', 'due_date'];

    private const ASSIGN_FIELDS = ['it_owner_id', 'sector_owner_id', 'sprint_id'];

    public function __construct(private readonly HomologationService $homologation) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $user): Gap
    {
        return DB::transaction(function () use ($data, $user) {
            $type = GapType::from($data['type'] ?? GapType::Gap->value);

            // Líder sempre registra no próprio setor.
            if ($user->role->isSectorScoped()) {
                $data['sector_id'] = $user->sector_id;
            }

            // Regra 2: se não informado, o responsável do setor é o líder do setor.
            $data['sector_owner_id'] ??= Sector::find($data['sector_id'])?->leader_id;

            // Prioridade sugerida pela matriz Impacto x Urgência quando não informada.
            $data['priority'] ??= Priority::suggest(
                Impact::from($data['impact']),
                Urgency::from($data['urgency']),
            )->value;

            // Model::shouldBeStrict() não aceita chaves fora do $fillable: removemos os pivots.
            $gap = new Gap(Arr::except($data, ['system_ids', 'meeting_ids']));
            $gap->type = $type;
            $gap->code = $this->nextCode($type);
            $gap->status = $type === GapType::Incidente ? IncidentStatus::Novo->value : GapStatus::Levantado->value;
            $gap->identified_at = $data['identified_at'] ?? today();
            $gap->created_by = $user->id;

            $this->assertRules($gap);
            $gap->save();

            $gap->systems()->sync($data['system_ids'] ?? []);
            $gap->meetings()->sync($data['meeting_ids'] ?? []);

            return $gap;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Gap $gap, array $data, User $user): Gap
    {
        $data = array_intersect_key($data, array_flip($this->editableFields($user)));

        return DB::transaction(function () use ($gap, $data) {
            $gap->fill(array_diff_key($data, array_flip(['system_ids', 'meeting_ids'])));
            $this->assertRules($gap);
            $gap->save();

            if (array_key_exists('system_ids', $data)) {
                $this->syncSystems($gap, $data['system_ids'] ?? []);
            }
            if (array_key_exists('meeting_ids', $data)) {
                $gap->meetings()->sync($data['meeting_ids'] ?? []);
            }

            return $gap;
        });
    }

    /** @return list<string> */
    public function editableFields(User $user): array
    {
        if ($user->hasPermission('gaps.update')) {
            $fields = ['title', 'sector_id', 'meeting_ids', ...self::COMPLEMENT_FIELDS];
            if ($user->hasPermission('gaps.classify')) {
                $fields = [...$fields, ...self::CLASSIFY_FIELDS];
            }
            if ($user->hasPermission('gaps.assign')) {
                $fields = [...$fields, ...self::ASSIGN_FIELDS];
            }

            return $fields;
        }

        return $user->hasPermission('gaps.complement') ? self::COMPLEMENT_FIELDS : [];
    }

    /**
     * Movimenta o GAP no workflow, validando a máquina de estados.
     */
    public function transition(Gap $gap, string $target, User $user, ?string $reason = null): Gap
    {
        if ($gap->type === GapType::Incidente) {
            return $this->transitionIncident($gap, IncidentStatus::from($target), $user, $reason);
        }

        $from = GapStatus::from($gap->status);
        $to = GapStatus::from($target);

        if ($from === $to) {
            return $gap;
        }

        // Regra 1: nenhum GAP é concluído sem validação do setor.
        if ($to === GapStatus::Concluido) {
            throw new BusinessRuleException('Um GAP só pode ser concluído pela aprovação da homologação pelo setor solicitante.');
        }

        if ($to === GapStatus::Homologacao) {
            return $this->homologation->send($gap, $user);
        }

        if (! $from->canTransitionTo($to)) {
            throw new BusinessRuleException("Transição não permitida: {$from->label()} → {$to->label()}.");
        }

        if (in_array($to, [GapStatus::Bloqueado, GapStatus::Cancelado, GapStatus::Reaberto], true) && blank($reason)) {
            throw new BusinessRuleException("Informe o motivo para alterar o status para {$to->label()}.");
        }

        return DB::transaction(function () use ($gap, $to, $user, $reason) {
            $gap->status = $to->value;

            if ($to === GapStatus::Reaberto) {
                $gap->completed_at = null;
                $gap->completed_by = null;
            }

            $this->assertRules($gap);
            $gap->save();

            if (filled($reason)) {
                $this->comment($gap, $user, "[Status: {$to->label()}] {$reason}");
            }

            return $gap;
        });
    }

    private function transitionIncident(Gap $gap, IncidentStatus $to, User $user, ?string $reason): Gap
    {
        $from = IncidentStatus::from($gap->status);

        if ($from === $to) {
            return $gap;
        }
        if (! $from->canTransitionTo($to)) {
            throw new BusinessRuleException("Transição não permitida: {$from->label()} → {$to->label()}.");
        }
        if ($to === IncidentStatus::Resolvido && blank($gap->solution)) {
            throw new BusinessRuleException('Registre a solução aplicada antes de marcar o incidente como resolvido.');
        }

        return DB::transaction(function () use ($gap, $to, $user, $reason) {
            $gap->status = $to->value;
            if ($to === IncidentStatus::Encerrado) {
                $gap->completed_at = now();
                $gap->completed_by = $gap->it_owner_id ?? $user->id;
            }
            $gap->save();

            if (filled($reason)) {
                $this->comment($gap, $user, "[Status: {$to->label()}] {$reason}");
            }

            return $gap;
        });
    }

    /**
     * Converte um Incidente em GAP/Melhoria (ex.: a causa raiz exige um
     * desenvolvimento que deve entrar no backlog Scrum).
     */
    public function convertIncidentToGap(Gap $gap, User $user, ?string $reason = null): Gap
    {
        if ($gap->type !== GapType::Incidente) {
            throw new BusinessRuleException('Somente incidentes podem ser convertidos em GAP/Melhoria.');
        }

        return DB::transaction(function () use ($gap, $user, $reason) {
            $oldCode = $gap->code;
            $gap->type = GapType::Gap;
            $gap->code = $this->nextCode(GapType::Gap);
            $gap->status = GapStatus::Triagem->value;
            $gap->completed_at = null;
            $gap->completed_by = null;
            $gap->save();

            $this->comment($gap, $user, trim("Incidente {$oldCode} convertido em GAP/Melhoria {$gap->code}. ".($reason ?? '')));

            return $gap;
        });
    }

    /** @param list<int> $systemIds */
    public function syncSystems(Gap $gap, array $systemIds): void
    {
        $before = $gap->systems()->pluck('name')->sort()->implode(', ');
        $gap->systems()->sync($systemIds);
        $after = $gap->systems()->pluck('name')->sort()->implode(', ');

        if ($before !== $after) {
            app(GapObserver::class)->log($gap, 'systems', $before ?: null, $after ?: null);
        }
    }

    public function comment(Gap $gap, User $user, string $body): GapComment
    {
        return $gap->comments()->create(['user_id' => $user->id, 'body' => $body]);
    }

    /**
     * Regras de consistência válidas em qualquer operação.
     *
     * @throws BusinessRuleException
     */
    public function assertRules(Gap $gap): void
    {
        // Regra 3: todo GAP crítico deve possuir justificativa.
        $isCritical = $gap->impact === Impact::Critico || $gap->priority === Priority::Critica;
        if ($isCritical && blank($gap->critical_justification)) {
            throw new BusinessRuleException('GAPs com impacto ou prioridade crítica exigem justificativa.');
        }

        // Regra 2: todo GAP deve possuir um responsável.
        if (blank($gap->sector_owner_id) && blank($gap->it_owner_id)) {
            throw new BusinessRuleException('Todo GAP deve possuir um responsável (do setor ou do TI).');
        }

        if ($gap->type === GapType::Gap && GapStatus::from($gap->status)->requiresItOwner() && blank($gap->it_owner_id)) {
            throw new BusinessRuleException('Defina o responsável TI antes de priorizar/planejar o GAP.');
        }
    }

    /**
     * Gera o próximo código sequencial (GAP-0001, INC-0001).
     *
     * lockForUpdate() evita que duas requisições simultâneas peguem o mesmo
     * número (no MySQL bloqueia a linha lida até o fim da transação). A coluna
     * "code" também é UNIQUE, que é a garantia final.
     */
    public function nextCode(GapType $type): string
    {
        $prefix = $type->codePrefix();

        $last = Gap::withTrashed()
            ->where('code', 'like', "{$prefix}-%")
            ->orderByRaw('LENGTH(code) DESC, code DESC')
            ->lockForUpdate()
            ->value('code');

        $next = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }
}

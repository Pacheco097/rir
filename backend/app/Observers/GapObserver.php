<?php

namespace App\Observers;

use App\Enums\GapStatus;
use App\Enums\IncidentStatus;
use App\Models\Category;
use App\Models\Gap;
use App\Models\GapHistory;
use App\Models\Sector;
use App\Models\Sprint;
use App\Models\User;
use BackedEnum;
use Illuminate\Support\Facades\Auth;

/**
 * Auditoria automática do GAP.
 *
 * Por que um Observer? Porque o histórico precisa ser gravado em QUALQUER
 * ponto que altere um GAP (tela, Kanban, homologação, conversão...). Se a
 * gravação ficasse no controller, bastaria esquecer em um lugar para perder
 * rastreabilidade. Aqui ela acontece no evento "updated" do Eloquent.
 */
class GapObserver
{
    /** Campos auditados => rótulo exibido no histórico. */
    public const TRACKED = [
        'code' => 'Código',
        'type' => 'Tipo',
        'title' => 'Título',
        'status' => 'Status',
        'priority' => 'Prioridade',
        'impact' => 'Impacto',
        'urgency' => 'Urgência',
        'sector_id' => 'Setor',
        'category_id' => 'Categoria',
        'it_owner_id' => 'Responsável TI',
        'sector_owner_id' => 'Responsável do setor',
        'sprint_id' => 'Sprint',
        'due_date' => 'Prazo',
    ];

    public function created(Gap $gap): void
    {
        $this->log($gap, 'criado', null, "{$gap->code} - {$gap->title}");
    }

    public function updated(Gap $gap): void
    {
        foreach (array_keys($gap->getChanges()) as $field) {
            if (! array_key_exists($field, self::TRACKED)) {
                continue;
            }

            $old = $this->display($field, $gap->getOriginal($field));
            $new = $this->display($field, $gap->getAttribute($field));

            if ($old !== $new) {
                $this->log($gap, $field, $old, $new);
            }
        }
    }

    public function log(Gap $gap, string $field, ?string $old, ?string $new): void
    {
        GapHistory::create([
            'gap_id' => $gap->id,
            'user_id' => Auth::id(),
            'field' => $field,
            'old_value' => $old,
            'new_value' => $new,
            'created_at' => now(),
        ]);
    }

    /** Converte o valor bruto (id, enum, data) em texto legível. */
    private function display(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'status' => (GapStatus::tryFrom((string) $value) ?? IncidentStatus::tryFrom((string) $value))?->label() ?? (string) $value,
            'sector_id' => Sector::find($value)?->name,
            'category_id' => Category::find($value)?->name,
            'it_owner_id', 'sector_owner_id' => User::find($value)?->name,
            'sprint_id' => Sprint::find($value)?->label(),
            'due_date' => $value instanceof \DateTimeInterface ? $value->format('d/m/Y') : date('d/m/Y', strtotime((string) $value)),
            default => $value instanceof BackedEnum && method_exists($value, 'label') ? $value->label() : (string) $value,
        };
    }
}

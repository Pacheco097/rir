<?php

namespace App\Enums;

/**
 * Converte um Enum em lista {value,label} para o frontend montar selects
 * sem duplicar os rótulos em TypeScript.
 */
final class EnumOptions
{
    /**
     * @param  class-string<\BackedEnum>  $enum
     * @return list<array{value:string,label:string}>
     */
    public static function of(string $enum): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], $enum::cases());
    }
}

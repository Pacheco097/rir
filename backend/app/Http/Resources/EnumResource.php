<?php

namespace App\Http\Resources;

use BackedEnum;

/** Serializa um Enum como {value, label} para o frontend exibir o rótulo. */
final class EnumResource
{
    /** @return array{value:string,label:string}|null */
    public static function make(?BackedEnum $enum): ?array
    {
        return $enum ? ['value' => $enum->value, 'label' => $enum->label()] : null;
    }
}

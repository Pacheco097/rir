<?php

namespace App\Enums;

enum Origin: string
{
    case Protheus = 'protheus';
    case Pims = 'pims';
    case Tbdc = 'tbdc';
    case Fluig = 'fluig';
    case Integracao = 'integracao';
    case ProcessoInterno = 'processo_interno';
    case Usuario = 'usuario';
    case Dados = 'dados';
    case Infraestrutura = 'infraestrutura';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Protheus => 'Protheus',
            self::Pims => 'PIMS',
            self::Tbdc => 'TBDC',
            self::Fluig => 'Fluig',
            self::Integracao => 'Integração',
            self::ProcessoInterno => 'Processo interno',
            self::Usuario => 'Usuário',
            self::Dados => 'Dados',
            self::Infraestrutura => 'Infraestrutura',
            self::Outro => 'Outro',
        };
    }
}

<?php

namespace App\Enums;

enum Perfil: string
{
    case Administrador = 'administrador';
    case Auditor = 'auditor';
    case Responsavel = 'responsavel';
    case Gestor = 'gestor';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador do Sistema',
            self::Auditor => 'Auditor / Técnico do Tribunal',
            self::Responsavel => 'Responsável Municipal',
            self::Gestor => 'Gestor Municipal',
        };
    }

    public function municipal(): bool
    {
        return in_array($this, [self::Responsavel, self::Gestor], true);
    }
}

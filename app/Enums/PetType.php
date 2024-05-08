<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PetType: string implements HasLabel
{
    case Dog = 'dog';
    case Rat = 'rat';
    case Cat = 'cat';
    case Lizard = 'lizard';
    case Fish = 'fish';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Dog => 'Dog',
            self::Rat => 'Rat',
            self::Cat => 'Cat',
            self::Lizard => 'Lizard',
            self::Fish => 'Fish',
        };
    }
}

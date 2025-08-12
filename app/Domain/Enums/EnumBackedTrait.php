<?php

namespace App\Domain\Enums;

trait EnumBackedTrait
{
    public static function tryFrom($value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        return null;
    }
}

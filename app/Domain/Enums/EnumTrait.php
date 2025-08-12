<?php

namespace App\Domain\Enums;

use BackedEnum;

trait EnumTrait
{
    public static function isBacked(): bool
    {
        return (new \ReflectionEnum(self::class))->getBackingType() !== null;
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function map(): array
    {
        return array_combine(self::isBacked() ? self::values() : self::names(), self::titles());
    }

    public static function asIdTitles(\Closure $decorator = null): array
    {
        $items = self::titles();
        array_walk($items, static function (&$el, $key) use ($decorator) {
            $el = ['id' => $key, 'title' => $el];
            if ($decorator !== null) {
                $el = $decorator($el, $key);
            }
        });

        return array_values($items);
    }

    public function title(): string
    {
        $titles = self::titles();

        if ($this instanceof BackedEnum) {
            return $titles[$this->value] ?? $this->value;
        }

        return $titles[$this->name] ?? $this->name;
    }
}

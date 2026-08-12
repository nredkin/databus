<?php

namespace App\Domain\Enums;

enum MessageCode
{
    use EnumTrait;
    use EnumBackedTrait;

    case None;
    case NomenclatureCreateUpdate;
    case CounterpartyCreateUpdate;
    case ArrivalDateCreateUpdate;
    case PriceCreateUpdate;
    case PriceTypeCreatUpdate;
    case ContactsCreateUpdate;
    case NomenclatureImageCreateUpdate;
    case BlockingStatusUpdate;

    public static function titles(): array
    {
        return [
            self::None->name => 'Не задан',
            self::NomenclatureCreateUpdate->name => 'Создание / обновление номенклатуры',
            self::CounterpartyCreateUpdate->name => 'Создание / обновление контрагента',
            self::ArrivalDateCreateUpdate->name => 'Создание / обновление даты прихода',
            self::PriceCreateUpdate->name => 'Создание / обновление цены',
            self::PriceTypeCreatUpdate->name => 'Создание / обновление типа цены',
            self::ContactsCreateUpdate->name => 'Создание / обновление контактов',
            self::NomenclatureImageCreateUpdate->name => 'Создание / обновление изображения номенклатуры',
            self::BlockingStatusUpdate->name => 'Обновление статуса блокировки',
        ];
    }
}

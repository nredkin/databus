<?php

namespace App\Domain\Enums;

enum MessageCode
{
    use EnumTrait;
    use EnumBackedTrait;

    case None;
    case NomenclatureCreateUpdate;
    case CounterpartyCreateUpdate;
    case PaymentInvoiceINCreateUpdate;
    case PaymentFact;
    case FileCreatePaymentOrder;
    case BankCreateUpdate;
    case BankAccountCreateUpdate;

    public static function titles(): array
    {
        return [
            self::None->name => 'Не задан',
            self::NomenclatureCreateUpdate->name => 'Создание / обновление номенклатуры',
            self::CounterpartyCreateUpdate->name => 'Создание / обновление контрагента',
            self::PaymentInvoiceINCreateUpdate->name => 'Создание / обновление счета от поставщика',
            self::PaymentFact->name => 'Факт оплаты счета от поставщика',
            self::FileCreatePaymentOrder->name => 'Передача файла',
            self::BankCreateUpdate->name => 'Обмен банками',
            self::BankAccountCreateUpdate->name => 'Обмен банковскими счетами',
        ];
    }
}

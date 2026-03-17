<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BmlGateway = 'bml_gateway';
    case BankTransfer = 'bank_transfer';
    case OoredooMfaisaa = 'ooredoo_mfaisaa';
    case DhiraaguPay = 'dhiraagu_pay';

    public function label(): string
    {
        return match ($this) {
            self::BmlGateway => 'BML Gateway',
            self::BankTransfer => 'Bank Transfer',
            self::OoredooMfaisaa => 'Ooredoo m-Faisaa',
            self::DhiraaguPay => 'Dhiraagu Pay',
        };
    }
}

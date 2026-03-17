<?php

namespace App\Enums;

enum ProductType: string
{
    case Referral = 'referral';
    case InHouse = 'in_house';
    case Affiliate = 'affiliate';

    public function label(): string
    {
        return match ($this) {
            self::Referral => 'Referral',
            self::InHouse => 'In-house',
            self::Affiliate => 'Affiliate',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Referral => 'neutral',
            self::InHouse => 'success',
            self::Affiliate => 'info',
        };
    }
}

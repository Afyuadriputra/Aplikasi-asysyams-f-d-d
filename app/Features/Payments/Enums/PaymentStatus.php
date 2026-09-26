<?php

namespace App\Features\Payments\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Paid => 'Lunas',
            self::Failed => 'Gagal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
        };
    }

    public static function options(): array
    {
        return [
            self::Paid->value => 'Lunas',
            self::Pending->value => 'Menunggu',
            self::Failed->value => 'Gagal',
        ];
    }

    public static function filterOptions(): array
    {
        return [
            self::Paid->value => 'Lunas',
            self::Pending->value => 'Belum Lunas',
            self::Failed->value => 'Gagal',
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Features\Grades\Enums;

enum AssessmentType: string
{
    case Ziyadah = 'ziyadah';
    case Murojaah = 'murojaah';
    case Tahsin = 'tahsin';
    case Tilawah = 'tilawah';
    case Tahfidz = 'tahfidz';
    case Tajwid = 'tajwid';

    public function label(): string
    {
        return match ($this) {
            self::Ziyadah => 'Ziyadah (Setoran Hafalan Baru)',
            self::Murojaah => 'Muroja\'ah (Pengulangan Hafalan)',
            self::Tahsin => 'Tahsin (Perbaikan Bacaan)',
            self::Tilawah => 'Tilawah (Kelancaran Membaca)',
            self::Tahfidz => 'Tahfidz (Hafalan Al-Qur\'an)',
            self::Tajwid => 'Tajwid (Kaidah Hukum Tajwid)',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [
            $case->value => $case->label(),
        ])->all();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

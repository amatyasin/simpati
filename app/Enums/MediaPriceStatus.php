<?php

namespace App\Enums;

enum MediaPriceStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case REJECTED = 'rejected';
    case INACTIVE = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Menunggu Persetujuan',
            self::ACTIVE => 'Aktif',
            self::REJECTED => 'Ditolak',
            self::INACTIVE => 'Nonaktif',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::REJECTED => 'danger',
            self::INACTIVE => 'secondary',
        };
    }
}

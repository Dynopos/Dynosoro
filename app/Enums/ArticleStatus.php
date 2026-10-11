<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case Writing = 'writing';
    case Failed = 'failed';
    case Ready = 'ready';
    case Published = 'published';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Writing => 'Sedang ditulis',
            self::Failed => 'Gagal',
            self::Ready => 'Sedia',
            self::Published => 'Terbit',
            self::Deleted => 'Dipadam',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Writing => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::Failed => 'bg-rose-50 text-rose-700 ring-rose-200',
            self::Ready => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Published => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Deleted => 'bg-slate-100 text-slate-500 ring-slate-200',
        };
    }
}

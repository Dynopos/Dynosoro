<?php

namespace App\Enums;

enum SiteStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Error = 'error';
    // Vertex AI stopped (GCP credit used up or billing disabled). Resumed by the admin.
    case AiPaused = 'ai_paused';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Paused => 'Berhenti',
            self::Error => 'Ralat',
            self::AiPaused => 'Tertunda (AI)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Paused => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Error => 'bg-rose-50 text-rose-700 ring-rose-200',
            self::AiPaused => 'bg-amber-50 text-amber-800 ring-amber-200',
        };
    }
}

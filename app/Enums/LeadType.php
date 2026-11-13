<?php

namespace App\Enums;

enum LeadType: string
{
    case Contact = 'contact';
    case Rfq = 'rfq';
    case Consultation = 'consultation';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Kontak',
            self::Rfq => 'Request for Quotation',
            self::Consultation => 'Konsultasi',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}

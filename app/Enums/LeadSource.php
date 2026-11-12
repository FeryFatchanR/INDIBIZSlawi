<?php

namespace App\Enums;

enum LeadSource: string
{
    case WebsiteForm = 'website_form';
    case Whatsapp = 'whatsapp';
    case Campaign = 'campaign';
    case Manual = 'manual';
    case Referral = 'referral';

    public function label(): string
    {
        return match ($this) {
            self::WebsiteForm => 'Form Website',
            self::Whatsapp => 'WhatsApp',
            self::Campaign => 'Kampanye',
            self::Manual => 'Input Manual',
            self::Referral => 'Referral',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}

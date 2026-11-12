<?php

namespace App\Policies;

class PartnerPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'partners';
    }
}

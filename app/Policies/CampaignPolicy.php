<?php

namespace App\Policies;

class CampaignPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'campaigns';
    }
}

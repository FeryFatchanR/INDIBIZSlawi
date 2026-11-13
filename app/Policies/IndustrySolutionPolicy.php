<?php

namespace App\Policies;

class IndustrySolutionPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'industry_solutions';
    }
}

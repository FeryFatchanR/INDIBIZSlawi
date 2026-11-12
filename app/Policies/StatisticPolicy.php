<?php

namespace App\Policies;

class StatisticPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'statistics';
    }
}

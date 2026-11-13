<?php

namespace App\Policies;

class NavigationMenuPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'navigation';
    }
}

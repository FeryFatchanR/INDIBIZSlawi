<?php

namespace App\Policies;

class ProductPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'products';
    }
}

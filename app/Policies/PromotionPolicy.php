<?php

namespace App\Policies;

class PromotionPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'promotions';
    }
}

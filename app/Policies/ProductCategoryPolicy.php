<?php

namespace App\Policies;

class ProductCategoryPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'product_categories';
    }
}

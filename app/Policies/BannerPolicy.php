<?php

namespace App\Policies;

class BannerPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'banners';
    }
}

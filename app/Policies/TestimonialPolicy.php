<?php

namespace App\Policies;

class TestimonialPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'testimonials';
    }
}

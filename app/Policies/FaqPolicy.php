<?php

namespace App\Policies;

class FaqPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'faqs';
    }
}

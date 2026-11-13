<?php

namespace App\Policies;

class CaseStudyPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'case_studies';
    }
}

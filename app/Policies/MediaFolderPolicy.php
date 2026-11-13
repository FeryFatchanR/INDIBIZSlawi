<?php

namespace App\Policies;

class MediaFolderPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'media';
    }
}

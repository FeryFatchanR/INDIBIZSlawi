<?php

namespace App\Exceptions;

use RuntimeException;

/** Dilempar saat draft sudah diubah pengguna lain sejak dimuat (optimistic locking). */
class StaleDraftException extends RuntimeException
{
    public function __construct(public readonly int $expected = 0, public readonly int $actual = 0)
    {
        parent::__construct('Halaman telah diubah oleh pengguna lain. Muat ulang builder untuk melihat versi terbaru.');
    }
}

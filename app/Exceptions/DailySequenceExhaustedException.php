<?php

namespace App\Exceptions;

use RuntimeException;

class DailySequenceExhaustedException extends RuntimeException
{
    public function __construct(public readonly string $stem)
    {
        parent::__construct("Urutan nomor harian untuk {$stem} sudah habis. Maksimal 999 nomor per hari.");
    }
}

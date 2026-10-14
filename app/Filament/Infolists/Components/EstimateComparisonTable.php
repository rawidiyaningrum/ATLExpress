<?php

namespace App\Filament\Infolists\Components;

use Filament\Infolists\Components\Entry;

/**
 * Entry infolist yang menampilkan perbandingan tarif per jenis armada sebagai
 * satu tabel lebar, sama seperti di halaman cetak estimasi.
 */
class EstimateComparisonTable extends Entry
{
    /**
     * @var view-string
     */
    protected string $view = 'filament.infolists.components.estimate-comparison-table';
}

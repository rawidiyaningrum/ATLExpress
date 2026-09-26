<?php

namespace App\Filament\Infolists\Components;

use Filament\Infolists\Components\Entry;

/**
 * Entry infolist yang menampilkan rincian biaya invoice sebagai satu tabel,
 * sama persis seperti di halaman cetak invoice.
 *
 * Sengaja bukan TextEntry: TextEntry membungkus state dengan
 * `inline-flex` sehingga tabelnya hanya selebar isinya.
 */
class InvoiceItemsTable extends Entry
{
    /**
     * @var view-string
     */
    protected string $view = 'filament.infolists.components.invoice-items-table';
}

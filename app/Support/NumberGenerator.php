<?php

namespace App\Support;

use App\Exceptions\DailySequenceExhaustedException;
use App\Models\Invoice;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

class NumberGenerator
{
    /**
     * Urutan harian, dihitung dalam satu stem tanggal dan direset setiap hari.
     */
    private const SEQUENCE_LENGTH = 3;

    private const SEQUENCE_MAX = 999;

    /**
     * Nomor AWB, bersemang pada shipments.awb_number.
     */
    public function awbNumber(?string $onDate = null): string
    {
        return $this->daily('awb_number', 'AWB_ATL_', Shipment::query(), $onDate);
    }

    /**
     * Nomor tracking, bersemang pada shipments.tracking_number.
     */
    public function trackingNumber(?string $onDate = null): string
    {
        return $this->daily('tracking_number', 'ATL_', Shipment::query(), $onDate);
    }

    /**
     * Nomor invoice, bersemang pada invoices.invoice_number.
     */
    public function invoiceNumber(?string $onDate = null): string
    {
        return $this->daily('invoice_number', 'INV_ATL_', Invoice::query(), $onDate);
    }

    /**
     * Menjalankan $persist dengan nomor freshly generated, mengulang bila tabrakan
     * unique. Uniqueness index tetap menjadi jaring pengaman terakhir.
     *
     * @template T
     *
     * @param  string  $method  salah satu method generator di kelas ini
     * @param  callable(string): T  $persist
     * @return T
     */
    public function persistWithRetry(string $method, callable $persist, int $attempts = 3): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $persist($this->{$method}());
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= $attempts) {
                    throw $e;
                }
            }
        }
    }

    private function daily(string $column, string $prefix, Builder $query, ?string $onDate = null): string
    {
        $date = $onDate !== null ? Carbon::parse($onDate) : Carbon::now();

        $stem = $prefix.$date->format('Ymd');

        $last = (clone $query)
            ->where($column, 'like', $stem.'%')
            ->orderByDesc($column)
            ->value($column);

        $next = $last === null ? 1 : ((int) substr($last, -self::SEQUENCE_LENGTH)) + 1;

        if ($next > self::SEQUENCE_MAX) {
            throw new DailySequenceExhaustedException($stem);
        }

        return $stem.str_pad((string) $next, self::SEQUENCE_LENGTH, '0', STR_PAD_LEFT);
    }
}

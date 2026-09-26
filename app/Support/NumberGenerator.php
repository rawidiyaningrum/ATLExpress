<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Shipment;

class NumberGenerator
{
    public function awbNumber(): string
    {
        return $this->sequential('AWB', 'ATL-AWB-', Shipment::query()->whereNotNull('awb_number'));
    }

    public function trackingNumber(): string
    {
        return $this->sequential('number', 'ATL-', Shipment::query()->whereNotNull('tracking_number'));
    }

    public function invoiceNumber(): string
    {
        return $this->sequential('number', 'INV-ATL-', Invoice::query());
    }

    private function sequential(string $column, string $prefix, $query): string
    {
        $last = $query->where($column, 'like', $prefix . '%')
            ->orderByDesc($column)
            ->value($column);

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
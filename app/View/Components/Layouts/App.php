<?php

namespace App\View\Components\Layouts;

use App\Models\Setting;
use Illuminate\View\Component;

class App extends Component
{
    public function __construct(public array $settings = [])
    {
        $this->settings = array_merge($this->fromDatabase(), $settings);
    }

    protected function fromDatabase(): array
    {
        $keys = [
            'company_name', 'company_tagline', 'phone', 'email', 'address', 'whatsapp',
            'facebook', 'instagram', 'youtube',
        ];

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = Setting::getValue($key, $this->defaults()[$key] ?? '');
        }

        return $values;
    }

    protected function defaults(): array
    {
        return [
            'company_name' => 'ATL Express',
            'company_tagline' => 'Cargo & Logistics',
            'phone' => '+62 857-7707-9581',
            'email' => 'cs.atlexpress@gmail.com',
            'address' => 'Jakarta, Indonesia',
            'whatsapp' => '6285777079581',
            'facebook' => '#',
            'instagram' => '#',
            'youtube' => '#',
        ];
    }

    public function render()
    {
        return view('layouts.app');
    }
}
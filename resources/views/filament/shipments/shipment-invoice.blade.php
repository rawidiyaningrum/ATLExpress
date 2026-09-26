<x-filament-panels::page>
    {{-- Tombol aksi ada di header halaman, jadi form sengaja tidak dibungkus
         <form wire:submit>: menekan Enter di kolom mana pun tidak akan
         membuat invoice tanpa sengaja. --}}
    {{ $this->form }}
</x-filament-panels::page>

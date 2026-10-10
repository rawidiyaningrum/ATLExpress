@props([
    'name',
    'options' => [],
    'selected' => null,
    'id' => null,
    'placeholder' => 'Pilih',
    'searchPlaceholder' => 'Cari...',
    'disabled' => false,
])

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{ open: false, query: '' }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
>
    <button
        type="button"
        @if($id) id="{{ $id }}" @endif
        x-on:click="open = ! open; if (open) { $nextTick(() => $refs.search.focus()) }"
        @disabled($disabled)
        class="w-full flex items-center justify-between gap-2 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-left transition focus:ring-2 focus:ring-gold focus:border-gold disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <span
            x-text="$wire.{{ $name }} || '{{ $placeholder }}'"
            :class="$wire.{{ $name }} ? 'text-gray-900' : 'text-gray-400'"
            class="{{ $selected ? 'text-gray-900' : 'text-gray-400' }}"
        >{{ $selected !== null && $selected !== '' ? $selected : $placeholder }}</span>
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute z-30 mt-1 w-full overflow-hidden bg-white border border-gray-200 rounded-xl shadow-xl"
    >
        <div class="p-2 border-b border-gray-100">
            <input
                x-ref="search"
                x-model="query"
                type="text"
                placeholder="{{ $searchPlaceholder }}"
                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gold focus:border-gold transition"
            >
        </div>

        <div x-ref="list" class="max-h-60 overflow-y-auto py-1">
            @foreach($options as $option)
                <button
                    type="button"
                    wire:key="{{ $name }}-option-{{ $loop->index }}"
                    x-show="$el.textContent.trim().toLowerCase().includes(query.trim().toLowerCase())"
                    x-on:click="$wire.set('{{ $name }}', $el.textContent.trim()); open = false; query = ''"
                    class="block w-full px-4 py-2 text-left text-sm {{ $selected === $option ? 'bg-gray-50 font-semibold text-primary' : 'text-gray-700 hover:bg-gray-50' }}"
                >{{ $option }}</button>
            @endforeach

            <p
                x-show="!Array.from($refs.list.querySelectorAll('button')).some(b => b.textContent.trim().toLowerCase().includes(query.trim().toLowerCase()))"
                class="px-4 py-3 text-sm text-gray-400"
            >Tidak ada hasil</p>
        </div>
    </div>
</div>

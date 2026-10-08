<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->class(['fi-in-invoice-items-table w-full overflow-x-auto'])
        }}
    >
        @include('filament.invoices.items-table', [
    'invoice' => $getRecord(),
])
    </div>
</x-dynamic-component>

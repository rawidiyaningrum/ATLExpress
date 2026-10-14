<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->class(['fi-in-estimate-comparison-table w-full overflow-x-auto'])
        }}
    >
        @include('filament.shipping-estimates.comparison', [
            'rows' => $getRecord()?->comparison() ?? [],
        ])
    </div>
</x-dynamic-component>

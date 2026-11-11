<x-filament-panels::page>
    @php
        $badge = [
            'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
            'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
            'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
            'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
            'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        ];
        $badgeClass = fn (?string $color): string => $badge[$color ?? 'gray'] ?? $badge['gray'];
        $columns = $this->columns;
        $editing = $modalMode === 'edit' && $editingHistoryId !== null
            ? \App\Models\ProjectHistory::query()->with('project')->find($editingHistoryId)
            : null;
        $inputClass = 'w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white';
    @endphp

    <div class="space-y-6">
        {{-- Toolbar filter --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cari Riwayat</span>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Judul atau catatan..."
                        class="{{ $inputClass }}"
                    />
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Project</span>
                    <select wire:model.live="projectFilter" class="{{ $inputClass }}">
                        <option value="">Semua Project</option>
                        @foreach ($this->projectOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">PIC</span>
                    <select wire:model.live="picFilter" class="{{ $inputClass }}">
                        <option value="">Semua PIC</option>
                        @foreach ($this->picOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-end gap-2">
                    <button
                        type="button"
                        wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Riwayat
                    </button>

                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="ml-auto rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Board --}}
        <div class="flex items-stretch gap-4 overflow-x-auto pb-4" x-data="{ dragging: null }">
            @foreach ($columns as $column)
                <section class="flex w-80 shrink-0 flex-col rounded-xl border border-gray-200 bg-gray-50 shadow-sm dark:border-gray-700 dark:bg-gray-900/60">
                    <header
                        class="flex items-center justify-between gap-2 rounded-t-xl border-b border-gray-200 px-4 py-3 dark:border-gray-700"
                        style="border-top: 3px solid {{ $column['hex'] }}"
                    >
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $column['hex'] }}"></span>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $column['label'] }}</h3>
                        </div>
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-gray-600 shadow-sm dark:bg-gray-800 dark:text-gray-300">
                            {{ $column['histories']->count() }}
                        </span>
                    </header>

                    <div
                        class="flex min-h-[140px] flex-1 flex-col gap-3 p-3"
                        x-on:dragover.prevent
                        x-on:drop.prevent="if (dragging !== null) { $wire.moveHistoryTo(dragging, '{{ $column['key'] }}'); dragging = null; }"
                    >
                        @forelse ($column['histories'] as $history)
                            <article
                                wire:key="history-{{ $history->id }}"
                                draggable="true"
                                x-on:dragstart="dragging = {{ $history->id }}; $event.dataTransfer.effectAllowed = 'move';"
                                x-on:dragend="dragging = null"
                                x-on:dragover.prevent.stop
                                x-on:drop.prevent.stop="if (dragging !== null && dragging !== {{ $history->id }}) { $wire.moveHistoryBefore(dragging, {{ $history->id }}); dragging = null; }"
                                x-on:click="$wire.openEdit({{ $history->id }})"
                                :class="dragging === {{ $history->id }} ? 'opacity-40' : ''"
                                class="cursor-pointer rounded-lg border border-gray-200 bg-white p-3 text-left shadow-sm transition hover:border-primary-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                            >
                                <h4 class="text-sm font-bold leading-snug text-gray-900 dark:text-white">{{ $history->title }}</h4>

                                <div class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                    <span class="truncate font-medium">{{ $history->project?->name ?? 'Tanpa Project' }}</span>
                                </div>

                                <div class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary-600 text-[10px] font-bold text-white">
                                        {{ strtoupper(mb_substr($history->project?->pic?->name ?? '?', 0, 1)) }}
                                    </span>
                                    <span>{{ $history->project?->pic?->name ?? 'Tanpa PIC' }}</span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $history->update_date?->format('d M Y') ?? '-' }}
                                    </span>

                                    @if ($history->progress_percent !== null)
                                        <span class="rounded bg-blue-100 px-1.5 py-0.5 font-semibold text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            {{ $history->progress_percent }}%
                                        </span>
                                    @endif
                                </div>

                                @if ($history->note)
                                    <p class="mt-2 line-clamp-2 text-[11px] italic text-gray-500 dark:text-gray-400">{{ $history->note }}</p>
                                @endif
                            </article>
                        @empty
                            <p class="rounded-lg border border-dashed border-gray-300 px-3 py-6 text-center text-xs text-gray-400 dark:border-gray-700">
                                Belum ada riwayat
                            </p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            Geser kartu antar kolom untuk mengubah status riwayat, klik kartu untuk mengubah detailnya, atau tekan "Tambah Riwayat" untuk menambah pekerjaan baru.
        </p>
    </div>

    {{-- Modal tambah/ubah riwayat --}}
    @if ($modalMode)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4 sm:p-8">
            <div class="w-full max-w-xl rounded-xl bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ $modalMode === 'edit' ? 'Ubah Riwayat' : 'Tambah Riwayat' }}
                    </h2>

                    <button
                        type="button"
                        wire:click="closeModal"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800"
                        title="Tutup"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4 px-5 py-5">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Project <span class="text-red-500">*</span></span>
                        <select wire:model="formProjectId" class="{{ $inputClass }}">
                            <option value="">— Pilih Project —</option>
                            @foreach ($this->projectOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('formProjectId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Judul Riwayat <span class="text-red-500">*</span></span>
                        <input type="text" wire:model="formTitle" placeholder="Contoh: Survei lokasi, Follow up vendor..." class="{{ $inputClass }}" />
                        @error('formTitle') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Status <span class="text-red-500">*</span></span>
                            <select wire:model="formStatus" class="{{ $inputClass }}">
                                @foreach ($this->statusOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('formStatus') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Tanggal <span class="text-red-500">*</span></span>
                            <input type="date" wire:model="formUpdateDate" class="{{ $inputClass }}" />
                            @error('formUpdateDate') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Progress (%)</span>
                            <input type="number" min="0" max="100" wire:model="formProgress" class="{{ $inputClass }}" />
                            @error('formProgress') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Catatan</span>
                        <textarea rows="3" wire:model="formNote" placeholder="Detail pekerjaan, hasil, atau kendala..." class="{{ $inputClass }}"></textarea>
                        @error('formNote') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    @if ($editing)
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                            Dibuat {{ $editing->created_at?->format('d M Y H:i') ?? '-' }}
                            oleh {{ $editing->creator?->name ?? 'Sistem' }}.
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div>
                        @if ($editing && (auth()->user()?->isAdminRole() ?? false))
                            <button
                                type="button"
                                wire:click="deleteHistory"
                                wire:confirm="Hapus riwayat ini?"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-950/40"
                            >
                                Hapus
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="saveHistory"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                        >
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

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
        $selected = $this->selectedProject;
    @endphp

    <div class="space-y-6">
        {{-- Toolbar filter --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cari Project</span>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Nama atau deskripsi..."
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    />
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">PIC</span>
                    <select
                        wire:model.live="picFilter"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Semua PIC</option>
                        @foreach ($this->picOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Prioritas</span>
                    <select
                        wire:model.live="priorityFilter"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Semua Prioritas</option>
                        @foreach ($this->priorityOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-end gap-3">
                    <label class="inline-flex items-center gap-2 pb-2">
                        <input
                            type="checkbox"
                            wire:model.live="onlyStale"
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
                        />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Belum update hari ini</span>
                    </label>

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
                            {{ $column['projects']->count() }}
                        </span>
                    </header>

                    <div
                        class="flex min-h-[140px] flex-1 flex-col gap-3 p-3"
                        x-on:dragover.prevent
                        x-on:drop.prevent="if (dragging !== null) { $wire.moveProjectTo(dragging, '{{ $column['key'] }}'); dragging = null; }"
                    >
                        @forelse ($column['projects'] as $project)
                            <article
                                wire:key="project-{{ $project->id }}"
                                draggable="true"
                                x-on:dragstart="dragging = {{ $project->id }}; $event.dataTransfer.effectAllowed = 'move';"
                                x-on:dragend="dragging = null"
                                x-on:dragover.prevent.stop
                                x-on:drop.prevent.stop="if (dragging !== null && dragging !== {{ $project->id }}) { $wire.moveProjectBefore(dragging, {{ $project->id }}); dragging = null; }"
                                x-on:click="$wire.openProject({{ $project->id }})"
                                :class="dragging === {{ $project->id }} ? 'opacity-40' : ''"
                                class="cursor-pointer rounded-lg border border-gray-200 bg-white p-3 text-left shadow-sm transition hover:border-primary-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="text-sm font-bold leading-snug text-gray-900 dark:text-white">{{ $project->name }}</h4>
                                    <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $badgeClass(\App\Models\Project::PRIORITY_COLORS[$project->priority] ?? 'gray') }}">
                                        {{ $project->priorityLabel() }}
                                    </span>
                                </div>

                                <div class="mt-2 flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary-600 text-[10px] font-bold text-white">
                                        {{ strtoupper(mb_substr($project->pic?->name ?? '?', 0, 1)) }}
                                    </span>
                                    <span>{{ $project->pic?->name ?? 'Tanpa PIC' }}</span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                                    @if ($project->due_date)
                                        <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 {{ $project->due_date->isPast() && ! $project->isDone() ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                            Tenggat: {{ $project->due_date->format('d M Y') }}
                                        </span>
                                    @endif

                                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 {{ $project->updated_today ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                        {{ $project->updated_today ? 'Update hari ini' : 'Belum update hari ini' }}
                                    </span>
                                </div>

                                @if ($project->latestUpdate)
                                    <p class="mt-2 line-clamp-2 text-[11px] italic text-gray-500 dark:text-gray-400">
                                        {{ $project->latestUpdate->update_date?->format('d M Y') }} &middot;
                                        {{ $project->latestUpdate->note ?: \App\Models\Project::statusLabel($project->latestUpdate->status) }}
                                    </p>
                                @endif
                            </article>
                        @empty
                            <p class="rounded-lg border border-dashed border-gray-300 px-3 py-6 text-center text-xs text-gray-400 dark:border-gray-700">
                                Belum ada project
                            </p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            Geser kartu ke kolom lain untuk mengubah status, atau klik kartu untuk mencatat update status dan melihat riwayatnya.
        </p>
    </div>

    {{-- Modal detail + update status --}}
    @if ($selected)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4 sm:p-8">
            <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ $selected->name }}</h2>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                            <span class="rounded px-1.5 py-0.5 font-bold uppercase tracking-wide {{ $badgeClass(\App\Models\Project::STATUS_COLORS[$selected->status] ?? 'gray') }}">
                                {{ \App\Models\Project::statusLabel($selected->status) }}
                            </span>
                            <span class="rounded px-1.5 py-0.5 font-bold uppercase tracking-wide {{ $badgeClass(\App\Models\Project::PRIORITY_COLORS[$selected->priority] ?? 'gray') }}">
                                {{ $selected->priorityLabel() }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-400">PIC: {{ $selected->pic?->name ?? 'Tanpa PIC' }}</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="closeProject"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800"
                        title="Tutup"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="max-h-[70vh] space-y-6 overflow-y-auto px-5 py-5">
                    @if ($selected->description)
                        <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-200">{{ $selected->description }}</p>
                    @endif

                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Mulai</dt>
                            <dd class="text-gray-800 dark:text-gray-200">{{ $selected->start_date?->format('d M Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tenggat</dt>
                            <dd class="text-gray-800 dark:text-gray-200">{{ $selected->due_date?->format('d M Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Progress</dt>
                            <dd class="text-gray-800 dark:text-gray-200">{{ $selected->progress_percent !== null ? $selected->progress_percent.'%' : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pengiriman Terkait</dt>
                            <dd class="text-gray-800 dark:text-gray-200">
                                @if ($selected->shipment)
                                    <a href="{{ \App\Filament\Resources\ShipmentResource::getUrl('edit', ['record' => $selected->shipment]) }}" class="text-primary-600 underline dark:text-primary-400">
                                        {{ $selected->shipment->awb_number }}
                                    </a>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>

                    {{-- Form update status --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                        <h3 class="mb-3 text-sm font-bold text-gray-900 dark:text-white">Update Status</h3>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Status</span>
                                <select
                                    wire:model="updateStatus"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                >
                                    @foreach ($this->statusOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('updateStatus') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Progress (%) <span class="font-normal text-gray-400">opsional</span></span>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    wire:model="updateProgress"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                                @error('updateProgress') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <label class="mt-3 block">
                            <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Catatan</span>
                            <textarea
                                rows="3"
                                wire:model="updateNote"
                                placeholder="Progres hari ini, kendala, atau tindak lanjut..."
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            ></textarea>
                            @error('updateNote') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <div class="mt-3 flex justify-end">
                            <button
                                type="button"
                                wire:click="saveUpdate"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                            >
                                Simpan Update
                            </button>
                        </div>
                    </div>

                    {{-- Riwayat --}}
                    <div>
                        <h3 class="mb-3 text-sm font-bold text-gray-900 dark:text-white">Riwayat Update</h3>

                        @forelse ($selected->statusUpdates as $update)
                            <div class="relative border-l-2 border-gray-200 pb-4 pl-4 last:pb-0 dark:border-gray-700">
                                <span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full" style="background-color: {{ \App\Models\Project::STATUS_HEX_COLORS[$update->status] ?? '#9ca3af' }}"></span>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide {{ $badgeClass(\App\Models\Project::STATUS_COLORS[$update->status] ?? 'gray') }}">
                                        {{ $update->statusLabel() }}
                                    </span>
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $update->update_date?->format('d M Y') }}</span>
                                    @if ($update->progress_percent !== null)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Progress: {{ $update->progress_percent }}%</span>
                                    @endif
                                </div>
                                @if ($update->note)
                                    <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $update->note }}</p>
                                @endif
                                <p class="mt-0.5 text-[11px] text-gray-400">
                                    Dicatat oleh {{ $update->creator?->name ?? 'Sistem' }}
                                    @if ($update->created_at) &middot; {{ $update->created_at->format('d M Y H:i') }} @endif
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat update.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

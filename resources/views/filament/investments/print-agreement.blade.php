<x-filament-panels::page>
    @php
        $money = fn ($value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
    @endphp

    <style>
        @media print {
            .fi-sidebar,
            .fi-topbar,
            .fi-header,
            .no-print {
                display: none !important;
            }

            .fi-main {
                padding: 0 !important;
            }

            html {
                color-scheme: light !important;
            }

            body {
                background: #fff !important;
            }

            .agreement-sheet {
                border: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
        }

        .agreement-sheet ol.pasal {
            list-style: decimal;
            padding-left: 1.25rem;
        }

        .agreement-sheet ol.pasal > li::marker,
        .agreement-sheet ol.huruf > li::marker {
            font-weight: 700;
        }
    </style>

    <div class="space-y-6">
        <div class="no-print flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Kop Perusahaan:</span>
                <div class="inline-flex rounded-lg border border-gray-300 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800">
                    <button
                        type="button"
                        wire:click="setCompany('atl')"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $company === 'atl' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-700 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}"
                    >
                        <span>ATL Express</span>
                    </button>
                    <button
                        type="button"
                        wire:click="setCompany('douze')"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $company === 'douze' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-700 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}"
                    >
                        <span>PT Douze</span>
                    </button>
                </div>
            </div>

            <x-filament::button
                icon="heroicon-o-printer"
                x-on:click="window.print()"
            >
                Cetak
            </x-filament::button>
        </div>

        <div class="agreement-sheet mx-auto max-w-3xl rounded-xl border border-gray-200 bg-white p-10 text-gray-900 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100">
            {{-- KOP PERUSAHAAN --}}
            <div class="border-b-2 border-gray-900 pb-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 text-left">
                        <img src="{{ $companyProfile['logo'] }}" alt="{{ $companyProfile['name'] }}" style="width: 96px; height: auto" class="rounded-lg object-contain">
                        <div>
                            <p class="text-xl font-bold uppercase tracking-wide">{{ $companyProfile['full_name'] }}</p>
                            <p class="mt-1 text-xs leading-tight text-gray-600 dark:text-gray-400">
                                {!! nl2br(e($companyProfile['address'])) !!}
                            </p>
                            @if ($companyProfile['npwp'] !== '-' && !empty($companyProfile['npwp']))
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                    <span class="font-semibold">NPWP:</span> {{ $companyProfile['npwp'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- JUDUL --}}
            <div class="mt-6 text-center">
                <h1 class="text-2xl font-bold uppercase tracking-widest underline">Perjanjian Investasi</h1>
                <p class="mt-1 text-sm font-semibold">Nomor: {{ $agreement_number }}</p>
            </div>

            {{-- PEMBUKA --}}
            <div class="mt-6 space-y-3 text-sm leading-relaxed">
                <p>
                    Pada hari ini, <span class="font-semibold">{{ $agreement_date_long }}</span>, yang bertanda tangan di bawah ini:
                </p>

                <div class="space-y-1 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pihak Pertama (Investor / Pemodal)</p>
                    <table class="w-full text-sm">
                        <tbody>
                            <tr><td class="w-40 py-0.5 align-top text-gray-600 dark:text-gray-400">Nama</td><td class="w-4 py-0.5 align-top">:</td><td class="py-0.5 align-top font-semibold">{{ $investor_name }}</td></tr>
                            <tr><td class="py-0.5 align-top text-gray-600 dark:text-gray-400">Alamat</td><td class="py-0.5 align-top">:</td><td class="py-0.5 align-top">{{ $investor_address }}</td></tr>
                            <tr><td class="py-0.5 align-top text-gray-600 dark:text-gray-400">No. Telepon / WA</td><td class="py-0.5 align-top">:</td><td class="py-0.5 align-top">{{ $investor_phone }}</td></tr>
                            @if ($investor_relation)
                                <tr><td class="py-0.5 align-top text-gray-600 dark:text-gray-400">Relasi</td><td class="py-0.5 align-top">:</td><td class="py-0.5 align-top">{{ $investor_relation }}</td></tr>
                            @endif
                            @if ($investor_bank)
                                <tr><td class="py-0.5 align-top text-gray-600 dark:text-gray-400">Rekening</td><td class="py-0.5 align-top">:</td><td class="py-0.5 align-top">{{ $investor_bank }}</td></tr>
                            @endif
                        </tbody>
                    </table>
                    <p class="pt-1 text-sm">Selanjutnya dalam perjanjian ini disebut sebagai <span class="font-semibold">"PIHAK PERTAMA"</span>.</p>
                </div>

                <div class="space-y-1 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pihak Kedua (Pengelola)</p>
                    <table class="w-full text-sm">
                        <tbody>
                            <tr><td class="w-40 py-0.5 align-top text-gray-600 dark:text-gray-400">Nama Badan</td><td class="w-4 py-0.5 align-top">:</td><td class="py-0.5 align-top font-semibold">{{ $companyProfile['full_name'] }}</td></tr>
                            <tr><td class="py-0.5 align-top text-gray-600 dark:text-gray-400">Alamat</td><td class="py-0.5 align-top">:</td><td class="py-0.5 align-top">{{ $companyProfile['address'] }}</td></tr>
                            @if ($companyProfile['npwp'] !== '-' && !empty($companyProfile['npwp']))
                                <tr><td class="py-0.5 align-top text-gray-600 dark:text-gray-400">NPWP</td><td class="py-0.5 align-top">:</td><td class="py-0.5 align-top">{{ $companyProfile['npwp'] }}</td></tr>
                            @endif
                            <tr><td class="py-0.5 align-top text-gray-600 dark:text-gray-400">Diwakili Oleh</td><td class="py-0.5 align-top">:</td><td class="py-0.5 align-top">{{ $signer_name }}</td></tr>
                        </tbody>
                    </table>
                    <p class="pt-1 text-sm">Selanjutnya dalam perjanjian ini disebut sebagai <span class="font-semibold">"PIHAK KEDUA"</span>.</p>
                </div>

                <p>
                    Kedua belah pihak terlebih dahulu menerangkan bahwa PIHAK PERTAMA bermaksud menanamkan modal (investasi) kepada PIHAK KEDUA,
                    dan PIHAK KEDUA bersedia menerima serta mengelola dana tersebut. Selanjutnya kedua belah pihak sepakat untuk mengikatkan diri
                    dalam Perjanjian Investasi dengan syarat dan ketentuan sebagai berikut:
                </p>
            </div>

            {{-- PASAL-PASAL --}}
            <div class="mt-6 space-y-4 text-sm leading-relaxed">
                <div>
                    <p class="font-bold uppercase">Pasal 1 &mdash; Objek dan Nilai Investasi</p>
                    <ol class="pasal mt-1 space-y-1">
                        <li>
                            PIHAK PERTAMA menyerahkan dana investasi kepada PIHAK KEDUA dengan nilai sebesar
                            <span class="font-semibold">{{ $amount_formatted }}</span>
                            (terbilang: <span class="font-semibold">{{ $amount_words }} rupiah</span>), selanjutnya disebut "Modal Investasi".
                        </li>
                        <li>
                            PIHAK KEDUA menerima Modal Investasi tersebut untuk dikelola sebagaimana diatur dalam perjanjian ini.
                        </li>
                    </ol>
                </div>

                <div>
                    <p class="font-bold uppercase">Pasal 2 &mdash; Maksud, Tujuan, dan Alokasi Dana</p>
                    <ol class="pasal mt-1 space-y-1">
                        <li>
                            Modal Investasi diperuntukkan bagi pengelolaan kegiatan usaha dengan alokasi:
                            <span class="font-semibold">{{ $allocation }}</span>.
                        </li>
                        <li>
                            PIHAK KEDUA wajib mengelola Modal Investasi secara profesional, jujur, dan bertanggung jawab untuk kepentingan bersama.
                        </li>
                    </ol>
                </div>

                <div>
                    <p class="font-bold uppercase">Pasal 3 &mdash; Bagi Hasil</p>
                    <ol class="pasal mt-1 space-y-1">
                        <li>
                            Atas pengelolaan Modal Investasi, PIHAK PERTAMA berhak memperoleh bagi hasil sebesar
                            <span class="font-semibold">{{ number_format($profit_sharing_percentage, 2, ',', '.') }}%</span>
                            dari Modal Investasi.
                        </li>
                        <li>
                            Nilai bagi hasil tersebut setara dengan
                            <span class="font-semibold">{{ $profit_sharing_amount_formatted }}</span>
                            (terbilang: <span class="font-semibold">{{ $profit_sharing_words }} rupiah</span>).
                        </li>
                        <li>
                            Bagi hasil dibayarkan oleh PIHAK KEDUA kepada PIHAK PERTAMA
                            @if ($profit_sharing_date_long)
                                paling lambat pada tanggal <span class="font-semibold">{{ $profit_sharing_date_long }}</span>.
                            @else
                                sesuai dengan kesepakatan para pihak.
                            @endif
                        </li>
                    </ol>
                </div>

                <div>
                    <p class="font-bold uppercase">Pasal 4 &mdash; Jangka Waktu dan Pengembalian Pokok Modal</p>
                    <ol class="pasal mt-1 space-y-1">
                        <li>
                            Perjanjian ini berlaku sejak tanggal {{ $agreement_date_long }} sampai dengan selesainya pengelolaan kegiatan usaha yang dimaksud.
                        </li>
                        <li>
                            PIHAK KEDUA wajib mengembalikan seluruh pokok Modal Investasi kepada PIHAK PERTAMA
                            @if ($capital_payout_date_long)
                                pada tanggal <span class="font-semibold">{{ $capital_payout_date_long }}</span>
                            @else
                                pada saat kegiatan usaha dinyatakan selesai atau sesuai kesepakatan para pihak
                            @endif
                            , tanpa mengurangi hak PIHAK PERTAMA atas bagi hasil.
                        </li>
                    </ol>
                </div>

                <div>
                    <p class="font-bold uppercase">Pasal 5 &mdash; Hak dan Kewajiban Para Pihak</p>
                    <ol class="pasal mt-1 space-y-1">
                        <li>
                            PIHAK PERTAMA berhak menerima bagi hasil dan pengembalian pokok Modal Investasi sesuai perjanjian ini,
                            serta berhak memperoleh keterangan yang benar mengenai pengelolaan dana.
                        </li>
                        <li>
                            PIHAK KEDUA berhak menggunakan Modal Investasi untuk kegiatan sesuai Pasal 2, dan berkewajiban
                            mengelola dana dengan penuh tanggung jawab serta melaporkan perkembangannya kepada PIHAK PERTAMA.
                        </li>
                        <li>
                            Para pihak dilarang mengalihkan hak dan/atau kewajibannya berdasarkan perjanjian ini kepada pihak lain
                            tanpa persetujuan tertulis dari pihak lainnya.
                        </li>
                    </ol>
                </div>

                <div>
                    <p class="font-bold uppercase">Pasal 6 &mdash; Risiko dan Force Majeure</p>
                    <ol class="pasal mt-1 space-y-1">
                        <li>
                            Para pihak memahami bahwa kegiatan usaha mengandung risiko. Risiko yang timbul bukan karena kelalaian
                            PIHAK KEDUA akan menjadi tanggung jawab dan risiko bersama sesuai proporsi masing-masing.
                        </li>
                        <li>
                            Tidak ada pihak yang bertanggung jawab atas kegagalan atau keterlambatan pelaksanaan perjanjian ini apabila
                            disebabkan oleh keadaan memaksa (force majeure), seperti bencana alam, kerusuhan, perang, kebijakan pemerintah,
                            atau keadaan lain di luar kendali para pihak. Pihak yang mengalami force majeure wajib memberitahukan secara tertulis
                            kepada pihak lainnya.
                        </li>
                    </ol>
                </div>

                <div>
                    <p class="font-bold uppercase">Pasal 7 &mdash; Wanprestasi dan Penyelesaian Sengketa</p>
                    <ol class="pasal mt-1 space-y-1">
                        <li>
                            Apabila salah satu pihak tidak melaksanakan kewajibannya sesuai perjanjian ini, pihak lainnya berhak menegur
                            secara tertulis dan/atau menuntut pemenuhan kewajiban tersebut.
                        </li>
                        <li>
                            Segala perselisihan yang timbul dari perjanjian ini diselesaikan terlebih dahulu secara musyawarah untuk mufakat
                            oleh para pihak.
                        </li>
                        <li>
                            Apabila musyawarah tidak mencapai kesepakatan, para pihak sepakat memilih domisili hukum yang tetap dan umum
                            di Kantor Pengadilan Negeri Jakarta untuk menyelesaikan perselisihan tersebut.
                        </li>
                    </ol>
                </div>

                <div>
                    <p class="font-bold uppercase">Pasal 8 &mdash; Ketentuan Lain</p>
                    <ol class="pasal mt-1 space-y-1">
                        <li>
                            Hal-hal yang belum diatur atau belum cukup diatur dalam perjanjian ini akan ditentukan kemudian oleh para pihak
                            dalam bentuk addendum yang merupakan bagian tidak terpisahkan dari perjanjian ini.
                        </li>
                        <li>
                            Perjanjian ini dibuat dalam 2 (dua) rangkap, masing-masing bermeterai cukup dan mempunyai kekuatan hukum
                            yang sama, yang satu dipegang oleh PIHAK PERTAMA dan yang satu dipegang oleh PIHAK KEDUA.
                        </li>
                        <li>
                            Perjanjian ini mulai berlaku sejak ditandatangani oleh kedua belah pihak.
                        </li>
                    </ol>
                </div>
            </div>

            {{-- PENUTUP --}}
            <p class="mt-6 text-sm leading-relaxed">
                Demikian Perjanjian Investasi ini dibuat dan ditandatangani oleh kedua belah pihak dalam keadaan sadar dan tanpa paksaan
                dari pihak mana pun, untuk dipergunakan sebagaimana mestinya.
            </p>

            {{-- TANDA TANGAN --}}
            <div class="mt-10 flex items-start justify-between gap-8 text-sm">
                <div class="text-center">
                    <p>PIHAK PERTAMA</p>
                    <div class="mt-16 h-16 w-44 border border-dashed border-gray-500"></div>
                    <p class="mt-2 font-semibold uppercase">{{ $investor_name }}</p>
                    <p class="text-xs text-gray-500">( Investor / Pemodal )</p>
                </div>

                <div class="text-center">
                    <p>{{ $city_date }}</p>
                    <p class="mt-0">PIHAK KEDUA</p>
                    <div class="mt-16 h-16 w-44 border border-dashed border-gray-500"></div>
                    <p class="mt-2 font-semibold uppercase">{{ $signer_name }}</p>
                    <p class="text-xs text-gray-500">( {{ $companyProfile['full_name'] }} )</p>
                </div>
            </div>

            {{-- CATATAN --}}
            @if ($notes)
                <div class="mt-8 border-t border-gray-200 pt-4 text-xs leading-relaxed text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    <p class="font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Catatan Investasi</p>
                    <p class="mt-1 whitespace-pre-line">{{ $notes }}</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>

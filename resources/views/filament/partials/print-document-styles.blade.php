{{--
    Gaya cetak bersama untuk dokumen A4 (Airway Bill, Invoice, Kwitansi).

    Ukuran kertas diset ke A4 tanpa mengunci orientasi, sehingga pilihan
    Portrait / Landscape pada dialog cetak browser tetap dihormati. Padding
    dan lebar aplikasi direset agar konten memakai penuh area cetak pada
    kedua orientasi tersebut.

    Pakai dengan menambahkan class "print-sheet" pada elemen pembungkus
    dokumen, lalu @include('filament.partials.print-document-styles').
--}}
<style>
    @page {
        size: A4;
        margin: 12mm;
    }

    @media print {
        html,
        body {
            width: auto !important;
            background: #fff !important;
            color: #000 !important;
            font-size: 10.5pt !important;
        }

        .fi-sidebar,
        .fi-topbar,
        .fi-header,
        .no-print {
            display: none !important;
        }

        /* Reset padding/lebar layout Filament supaya konten pas di area cetak
           A4, baik portrait maupun landscape. */
        .fi-main,
        .fi-main-ctn,
        .fi-page,
        .fi-page-main,
        .fi-page-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: none !important;
            gap: 0 !important;
        }

        .space-y-6 {
            margin: 0 !important;
        }

        .print-sheet {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: #fff !important;
            color: #000 !important;
            font-size: 10.5pt !important;
            line-height: 1.45 !important;
        }

        /* Selalu cetak hitam-putih, termasuk saat aplikasi memakai mode gelap. */
        .print-sheet,
        .print-sheet * {
            color: #000 !important;
            background-color: transparent !important;
            border-color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Hindari blok/tabel terpotong di tengah halaman. */
        .print-sheet .avoid-break,
        .print-sheet .signature-block,
        .print-sheet tr,
        .print-sheet img {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Baris header tabel diulang di setiap halaman saat tabel panjang. */
        .print-sheet thead {
            display: table-header-group;
        }

        .print-sheet .avoid-break > p:first-child {
            break-after: avoid;
            page-break-after: avoid;
        }

        .print-sheet h1 {
            font-size: 16pt !important;
            line-height: 1.25 !important;
        }
    }

    /* Landscape hanya punya sedikit ruang vertikal: rapatkan margin atas/bawah
       agar konten (mis. tabel item invoice) tidak terpotong. */
    @media print and (orientation: landscape) {
        @page {
            margin: 10mm 14mm;
        }
    }
</style>

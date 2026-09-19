@component('mail::message')
@if($forAdmin)
# Hai Admin ATL Express,

Jaya di darat, laut, dan udara.

**{{ $request->name }}** telah melakukan pemesanan melalui aplikasi. Segera tindak lanjuti melalui menu admin.
@else
# Halo, {{ $request->name }},

Terima kasih telah melakukan pemesanan pengiriman melalui **ATL Express**. Berikut rincian pemesanan Anda:
@endif

@component('mail::table')
| Keterangan | Detail |
| :--------- | :----- |
| Nama | {{ $request->name }} |
| No. Telepon | {{ $request->phone ?? '-' }} |
| Email | {{ $request->email ?? '-' }} |
| Alamat Penjemputan | {{ $request->pickup_address ?? '-' }} |
| Jenis Barang | {{ $request->item_type ?? '-' }} |
| Berat | {{ $request->weight ?? '-' }} kg |
| Dimensi | {{ $request->dimensions ?? '-' }} |
| Asal | {{ $request->origin ?? '-' }} |
| Tujuan | {{ $request->destination ?? '-' }} |
| Layanan | {{ $request->service_type ?? '-' }} |
| Keterangan Tambahan | {{ $request->notes ?? '-' }} |
@endcomponent

@if($forAdmin)
@component('mail::button', ['url' => $adminUrl])
Lihat Pemesanan di Panel Admin
@endcomponent
@else
Pesanan Anda sudah kami terima. Tim kami akan segera menghubungi Anda untuk konfirmasi.
@endif

Salam,<br>
**{{ config('app.name') }}**
@endcomponent
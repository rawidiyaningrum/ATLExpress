@component('mail::message')
# Halo, {{ $user->name }},

Anda telah diundang untuk bergabung sebagai admin **{{ config('app.name') }}**.

Klik tombol di bawah untuk menyelesaikan pendaftaran akun Anda. Link ini berlaku hingga **{{ $expiresAt->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }} WIB**.

@component('mail::button', ['url' => $inviteUrl])
Selesaikan Pendaftaran
@endcomponent

Jika Anda tidak merasa diundang, abaikan email ini. Link akan kedaluwarsa secara otomatis.

Salam,
**{{ config('app.name') }}**
@endcomponent


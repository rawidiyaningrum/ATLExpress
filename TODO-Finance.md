# TODO - Fitur Wizard Pengiriman + Finance

## Alur Wizard (5 langkah)

1. **Buat pengiriman** — form data pengirim, alamat, tujuan; simpan sebagai draft shipment.
2. **Berat & tarif** — input berat dan tarif final per kg, hitung total otomatis.
3. **Cetak airway bill** — generate nomor AWB, cetak otomatis, shipment menjadi aktif (dengan ShipmentLog).
4. **Proses invoice** — form perhitungan awal, tambah item tambahan + jumlah, auto-total, hint/saran item umum (PPN, PPh, diskon, packing kayu).
5. **Cetak invoice** — layout printable, format nomor invoice, rincian lengkap.

## Finance

- Summary keuangan per pengiriman/project masuk ke jurnal: modal, biaya operasional, pajak, sampai profit (nominal + persentase).
- Dashboard finance keseluruhan, bisa difilter tanggal.
- Alur pembayaran invoice: `draft → final → berjalan → lunas`, jurnalnya terpisah antara pendapatan dan kas masuk.

## Checklist

- [x] Riset struktur existing (Shipment, ShipmentLog, ShippingRate, model & migrasi terkait, pola Filament Resource)
- [x] Step 1 Wizard — form data pengirim, alamat, tujuan & simpan draft shipment
- [x] Step 2 — input berat & tarif per kg (final), auto-hitung total, hint/validasi dari ShippingRate
- [x] Step 3 — generate nomor AWB (unik), cetak airway bill otomatis, transisi status draft -> aktif + ShipmentLog
- [x] Step 4 — proses invoice: perhitungan awal, item tambahan + jumlah, auto-total, hint item umum (PPN, PPh, diskon, packing kayu)
- [x] Step 5 — cetak invoice (layout printable, format nomor invoice)
- [x] Migrasi & model: invoice, invoice_items, finance journal/catatan keuangan per shipment (modal, opex, pajak, profit nominal & %)
- [x] Summary keuangan per pengiriman/project masuk ke jurnal
- [x] Dashboard finance keseluruhan dengan filter tanggal + agregasi
- [x] Status invoice 4 tahap: `draft` → `final` → `berjalan` → `lunas`, dengan tanggal berjalan dan tanggal lunas
- [x] Jurnal dipisah per tipe: `revenue` saat final, `receipt` saat lunas (dashboard tidak dobel hitung)
- [x] Aksi transisi status di daftar invoice & detail, popup tanggal, mundur maksimal satu langkah
- [x] Statistik `Kas Masuk` + `Piutang Belum Lunas` di dashboard finance (final + berjalan)
- [x] Wizard shipment berhenti di `draft` (tidak auto-menagih)
- [x] Test alur wizard end-to-end + pint/typecheck

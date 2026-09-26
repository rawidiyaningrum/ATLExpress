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

## Checklist

- [ ] Riset struktur existing (Shipment, ShipmentLog, ShippingRate, model & migrasi terkait, pola Filament Resource)
- [ ] Step 1 Wizard — form data pengirim, alamat, tujuan & simpan draft shipment
- [ ] Step 2 — input berat & tarif per kg (final), auto-hitung total, hint/validasi dari ShippingRate
- [ ] Step 3 — generate nomor AWB (unik), cetak airway bill otomatis, transisi status draft -> aktif + ShipmentLog
- [ ] Step 4 — proses invoice: perhitungan awal, item tambahan + jumlah, auto-total, hint item umum (PPN, PPh, diskon, packing kayu)
- [ ] Step 5 — cetak invoice (layout printable, format nomor invoice)
- [ ] Migrasi & model: invoice, invoice_items, finance journal/catatan keuangan per shipment (modal, opex, pajak, profit nominal & %)
- [ ] Summary keuangan per pengiriman/project masuk ke jurnal
- [ ] Dashboard finance keseluruhan dengan filter tanggal + agregasi
- [ ] Test alur wizard end-to-end + pint/typecheck
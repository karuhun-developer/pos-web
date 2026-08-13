@php
    /**
     * Halaman cetak laporan.
     *
     * Bukan Inertia dan bukan PDF renderer: HTML polos yang ditata untuk kertas,
     * lalu "simpan sebagai PDF" diserahkan ke dialog cetak browser. Chart
     * sengaja tidak dibawa ke sini — di atas kertas tidak ada tooltip, jadi
     * angkanya harus terbaca langsung sebagai tabel.
     */
    $rupiah = fn (int|float|null $value): string => 'Rp '.number_format((float) ($value ?? 0), 0, ',', '.');
    $angka = fn (int|float|null $value): string => number_format((float) ($value ?? 0), 0, ',', '.');
    $tanggal = fn (string $key): string => \Carbon\CarbonImmutable::parse($key)->format('d/m');
    $persen = fn (?float $value): string => $value === null ? '—' : ($value > 0 ? '+' : '').number_format($value, 1, ',', '.').'%';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan {{ $period['from'] }} – {{ $period['to'] }} · {{ $store?->name }}</title>
    <style>
        /* Gaya ditulis di sini, bukan lewat Vite: berkas cetak harus tetap benar
           walaupun dibuka dari cache atau disimpan sebagai HTML. */
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            color: #0b0b0b;
            background: #fff;
            font: 12px/1.5 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }
        h1 { margin: 0; font-size: 18px; }
        h2 { margin: 24px 0 8px; font-size: 13px; }
        p { margin: 2px 0; }
        .muted { color: #52514e; }
        .subtle { color: #77756f; }
        header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; border-bottom: 1px solid #cbc9c2; padding-bottom: 12px; }
        .kpi { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 16px; }
        .kpi div { border: 1px solid #e3e1dc; border-radius: 8px; padding: 10px 12px; }
        .kpi strong { display: block; margin-top: 2px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px 8px; text-align: left; border-bottom: 1px solid #e3e1dc; }
        th { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #52514e; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        tfoot td { font-weight: 600; border-top: 1px solid #cbc9c2; border-bottom: none; }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .toolbar { margin-bottom: 16px; }
        button { font: inherit; padding: 8px 14px; border: 1px solid #0b0b0b; border-radius: 8px; background: #0b0b0b; color: #fff; cursor: pointer; }
        footer { margin-top: 24px; border-top: 1px solid #cbc9c2; padding-top: 8px; }

        @media print {
            body { padding: 0; }
            /* Toolbar cuma alat di layar; di kertas ia jadi kotak hitam tanpa guna. */
            .toolbar { display: none; }
            h2 { break-after: avoid; }
            table, section { break-inside: avoid; }
        }
    </style>
</head>
<body>
    {{-- Tidak ada window.print() otomatis: dialog cetak yang menyergap begitu
         halaman terbuka bikin isinya tidak sempat diperiksa dulu. --}}
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak / simpan sebagai PDF</button>
    </div>

    <header>
        <div>
            <h1>Laporan penjualan</h1>
            <p class="muted">{{ $store?->name ?? 'Toko' }}</p>
        </div>
        <div style="text-align: right">
            <p><strong>{{ $tanggal($period['from']) }} – {{ $tanggal($period['to']) }}</strong></p>
            <p class="subtle">{{ $period['days'] }} hari · dicetak {{ $printed_at }}</p>
        </div>
    </header>

    <div class="kpi">
        <div>
            <span class="subtle">Omzet</span>
            <strong>{{ $rupiah($summary['kpi']['revenue']) }}</strong>
            <span class="subtle">{{ $persen($summary['kpi']['delta']['revenue']) }} vs periode sebelumnya</span>
        </div>
        <div>
            <span class="subtle">Transaksi</span>
            <strong>{{ $angka($summary['kpi']['orders']) }}</strong>
            <span class="subtle">{{ $persen($summary['kpi']['delta']['orders']) }}</span>
        </div>
        <div>
            <span class="subtle">Rata-rata keranjang</span>
            <strong>{{ $rupiah($summary['kpi']['basket']) }}</strong>
            <span class="subtle">{{ $persen($summary['kpi']['delta']['basket']) }}</span>
        </div>
        <div>
            <span class="subtle">Laba kotor</span>
            <strong>{{ $rupiah($summary['kpi']['profit']) }}</strong>
            <span class="subtle">{{ $persen($summary['kpi']['delta']['profit']) }}</span>
        </div>
    </div>

    <section>
        <h2>Penjualan harian</h2>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="num">Omzet</th>
                    <th class="num">Periode sebelumnya</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary['trend']['days'] as $index => $day)
                    <tr>
                        <td>{{ $tanggal($day) }}</td>
                        <td class="num">{{ $rupiah($summary['trend']['current'][$index] ?? 0) }}</td>
                        <td class="num muted">{{ $rupiah($summary['trend']['previous'][$index] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="num">{{ $rupiah(array_sum($summary['trend']['current'])) }}</td>
                    <td class="num">{{ $rupiah(array_sum($summary['trend']['previous'])) }}</td>
                </tr>
            </tfoot>
        </table>
    </section>

    <div class="cols">
        <section>
            <h2>Produk terlaris</h2>
            @if ($top_products['rows'])
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Terjual</th>
                            <th class="num">Omzet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($top_products['rows'] as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="num">{{ $angka($row['qty']) }}</td>
                                <td class="num">{{ $rupiah($row['revenue']) }}</td>
                            </tr>
                        @endforeach
                        @if ($top_products['other'])
                            <tr class="muted">
                                <td>{{ $top_products['other']['name'] }}</td>
                                <td class="num">{{ $angka($top_products['other']['qty']) }}</td>
                                <td class="num">{{ $rupiah($top_products['other']['revenue']) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            @else
                <p class="subtle">Belum ada penjualan di rentang ini.</p>
            @endif
        </section>

        <section>
            <h2>Metode bayar</h2>
            @if ($payment_mix['rows'])
                <table>
                    <thead>
                        <tr>
                            <th>Metode</th>
                            <th class="num">Transaksi</th>
                            <th class="num">Omzet</th>
                            <th class="num">Porsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payment_mix['rows'] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="num">{{ $angka($row['orders']) }}</td>
                                <td class="num">{{ $rupiah($row['revenue']) }}</td>
                                <td class="num">{{ $row['share'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Total</td>
                            <td class="num">{{ $rupiah($payment_mix['total']) }}</td>
                            <td class="num">100%</td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <p class="subtle">Belum ada pembayaran tercatat.</p>
            @endif
        </section>
    </div>

    <section>
        <h2>HPP &amp; margin per kategori</h2>
        @if ($category_margin['rows'])
            <table>
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th class="num">Omzet</th>
                        <th class="num">HPP</th>
                        <th class="num">Margin</th>
                        <th class="num">% Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($category_margin['rows'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="num">{{ $rupiah($row['revenue']) }}</td>
                            <td class="num muted">{{ $rupiah($row['cost']) }}</td>
                            <td class="num">{{ $rupiah($row['margin']) }}</td>
                            <td class="num">{{ $row['margin_pct'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="subtle">Belum ada penjualan berkategori di rentang ini.</p>
        @endif
    </section>

    <section>
        <h2>Arus kas harian</h2>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="num">Masuk</th>
                    <th class="num">Keluar</th>
                    <th class="num">Bersih</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cashflow['days'] as $index => $day)
                    <tr>
                        <td>{{ $tanggal($day) }}</td>
                        <td class="num">{{ $rupiah($cashflow['income'][$index] ?? 0) }}</td>
                        {{-- Server mengirim pengeluaran bertanda negatif (dipakai chart
                             diverging). Di kolom berjudul "Keluar", tanda minusnya jadi
                             negatif ganda — jadi yang dicetak nilai mutlaknya. --}}
                        <td class="num">{{ $rupiah(abs((float) ($cashflow['expense'][$index] ?? 0))) }}</td>
                        <td class="num">{{ $rupiah($cashflow['net'][$index] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="num">{{ $rupiah(array_sum($cashflow['income'])) }}</td>
                    <td class="num">{{ $rupiah(abs((float) array_sum($cashflow['expense']))) }}</td>
                    <td class="num">{{ $rupiah(array_sum($cashflow['net'])) }}</td>
                </tr>
            </tfoot>
        </table>
    </section>

    <section>
        <h2>Selisih laci per sesi kasir</h2>
        @if ($sessions['rows'])
            <p class="subtle">
                {{ $sessions['balanced'] }} sesi pas · {{ $sessions['short'] }} kurang ·
                {{ $sessions['over'] }} lebih · selisih terbesar {{ $rupiah($sessions['worst']) }}
            </p>
            <table>
                <thead>
                    <tr>
                        <th>Sesi</th>
                        <th>Kasir</th>
                        <th class="num">Seharusnya</th>
                        <th class="num">Dihitung</th>
                        <th class="num">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions['rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="muted">{{ $row['cashier'] ?? '—' }}</td>
                            <td class="num muted">{{ $rupiah($row['expected']) }}</td>
                            <td class="num">{{ $rupiah($row['counted']) }}</td>
                            <td class="num">{{ $rupiah($row['difference']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="subtle">Belum ada sesi kasir yang ditutup di rentang ini.</p>
        @endif
    </section>

    <section>
        <h2>Inventori</h2>
        <p class="subtle">
            {{ $angka($inventory['tracked']) }} produk berstok · nilai {{ $rupiah($inventory['value']) }} ·
            {{ $angka($inventory['out_of_stock']) }} habis
        </p>
        @if ($inventory['low'])
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th class="num">Stok</th>
                        <th class="num">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inventory['low'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="muted">{{ $row['sku'] ?? '—' }}</td>
                            <td class="num">{{ $angka($row['stock']) }}</td>
                            <td class="num">{{ $rupiah($row['value']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($inventory['low_total'] > count($inventory['low']))
                <p class="subtle">
                    Menampilkan {{ count($inventory['low']) }} dari {{ $angka($inventory['low_total']) }} produk
                    di bawah ambang {{ $angka($inventory['threshold']) }}.
                </p>
            @endif
        @else
            <p class="subtle">Tidak ada produk di bawah ambang stok.</p>
        @endif
    </section>

    <footer class="subtle">
        Angka dihitung menurut jam toko (Asia/Jakarta) untuk rentang
        {{ $period['from'] }} sampai {{ $period['to'] }}. Dicetak dari POS Pro.
    </footer>
</body>
</html>

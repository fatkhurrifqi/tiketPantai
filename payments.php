<?php
// =============================================
// Konfigurasi Metode Pembayaran - TiketPantai
// ---------------------------------------------
// Ubah nomor rekening / nomor e-wallet / gambar QRIS di sini.
// Tidak perlu menyentuh database. Nilai 'key' dipakai sebagai
// identitas provider dan disimpan di kolom orders.payment_detail.
// =============================================

/**
 * Daftar konfigurasi metode pembayaran.
 * Struktur:
 *   - 'groups' : definisi 4 kelompok metode (label + ikon FontAwesome)
 *   - 'bank'   : daftar rekening bank
 *   - 'ewallet': daftar e-wallet
 *   - 'qris'   : konfigurasi QRIS (path gambar)
 */
function get_payments(): array
{
    return [
        // Kelompok metode. 'value' disimpan di kolom orders.payment_method.
        'groups' => [
            'bank'     => ['label' => 'Transfer Bank', 'icon' => 'fa-building-columns'],
            'ewallet'  => ['label' => 'E-Wallet',      'icon' => 'fa-wallet'],
            'qris'     => ['label' => 'QRIS',          'icon' => 'fa-qrcode'],
            'location' => ['label' => 'Bayar di Lokasi', 'icon' => 'fa-map-location-dot'],
        ],

        // Rekening bank (Transfer Bank). 'number' = nomor rekening, 'holder' = nama pemilik.
        'bank' => [
            ['key' => 'bca',     'name' => 'Bank BCA',     'number' => '1234567890', 'holder' => 'TiketPantai'],
            ['key' => 'mandiri', 'name' => 'Bank Mandiri', 'number' => '1357902468', 'holder' => 'TiketPantai'],
            ['key' => 'bni',     'name' => 'Bank BNI',     'number' => '9876543210', 'holder' => 'TiketPantai'],
            ['key' => 'bri',     'name' => 'Bank BRI',     'number' => '2468135790', 'holder' => 'TiketPantai'],
        ],

        // E-Wallet. 'number' = nomor telepon terdaftar e-wallet.
        'ewallet' => [
            ['key' => 'gopay',     'name' => 'GoPay',     'number' => '081234567890', 'holder' => 'Budi Santoso'],
            ['key' => 'dana',      'name' => 'DANA',      'number' => '081234567890', 'holder' => 'Budi Santoso'],
            ['key' => 'shopeepay', 'name' => 'ShopeePay', 'number' => '081234567890', 'holder' => 'Budi Santoso'],
            ['key' => 'ovo',       'name' => 'OVO',       'number' => '081234567890', 'holder' => 'Budi Santoso'],
        ],

        // QRIS. Ganti 'image' dengan file QRIS asli (mis. 'assets/qris.png') bila sudah ada.
        'qris' => [
            'image' => 'assets/qris.jpeg',
            'label' => 'QRIS',
        ],
    ];
}

/**
 * Memetakan label metode (orders.payment_method) ke key kelompok internal.
 */
function payment_group_map(): array
{
    $cfg = get_payments();
    $map = [];
    foreach ($cfg['groups'] as $key => $g) {
        $map[$g['label']] = $key;
    }
    return $map;
}

/**
 * Menyelesaikan metode pembayaran menjadi info terstruktur.
 *
 * @param string|null $method Nilai orders.payment_method (label kelompok, mis. 'Transfer Bank')
 * @param string|null $detail Nilai orders.payment_detail (key provider, mis. 'bca' / 'gopay' / 'qris')
 * @return array [
 *     'group'    => label kelompok yang ditampilkan,
 *     'type'     => 'bank'|'ewallet'|'qris'|'location'|'unknown',
 *     'provider' => ['name','number','holder'] | null,
 *     'image'    => path gambar QRIS | null,
 * ]
 */
function resolve_payment(?string $method, ?string $detail): array
{
    $cfg = get_payments();
    $map = payment_group_map();
    $groupKey = $map[$method] ?? null;

    $result = [
        'group'    => $method,
        'type'     => 'unknown',
        'provider' => null,
        'image'    => null,
    ];

    if ($groupKey === null) {
        return $result;
    }
    $result['type'] = $groupKey;

    if ($groupKey === 'bank' || $groupKey === 'ewallet') {
        foreach ($cfg[$groupKey] as $p) {
            if ($p['key'] === $detail) {
                $result['provider'] = $p;
                break;
            }
        }
    } elseif ($groupKey === 'qris') {
        $result['image'] = $cfg['qris']['image'];
    }

    return $result;
}
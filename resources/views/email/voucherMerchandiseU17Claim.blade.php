<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Voucher Listrik Merchandise PLN Mobile</title>
</head>

<!-- font nunito -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz@6..12&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz,wght@6..12,400;6..12,700&display=swap"
    rel="stylesheet">

<body
    style="
        padding: 0;
        margin: 0;
        border: none;
        border-spacing: 0px;
        border-collapse: collapse;
        vertical-align: top;
        font-family: 'Nunito Sans', sans-serif;
        background: #e3e7ea;">
    <table width="600" align="center" style="border-spacing: 10px; background: #ffffff; padding: 48px;">
        <tr>
            <td>
                <table width="100%">
                    <tbody>
                        <tr>
                            <td>
                                <img src="https://api-mkp.iconcash.id/v1/cdn/api/firebase/file/load/Marketplace~merchants~1699432068b03575c1-990e-46f4-b666-bc476d83a2e9.png"
                                    alt="" style="max-width: 90px" />
                            </td>
                            <td style="width: 50%; text-align: -webkit-right !important;">
                                <img src="https://media.discordapp.net/attachments/830329995989352458/1062551640978247770/pln-mobile-logo.png"
                                    alt="" style="max-width: 200px" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>

        <tr>
            <td>
                <p style="font-weight: 400; font-size: 16px; color: #323232;">
                    Halo, {{ $customer_name }} 🖐🏻
                </p>
            </td>
        </tr>

        <tr>
            <td>
                <p
                    style="font-weight: 400;  font-size: 16px; line-height: 140%; color: #323232; padding-bottom: 24px; border-bottom: 1px #D9D9D9 dotted;">
                    Selamat, Anda mendapatkan voucher listrik berikut dari pembelian merchandise event <strong>Piala
                        Dunia U17 FIFA Indonesia 2023!</strong>
                </p>
                <p style="font-weight: 700; font-size: 16px; line-height: 140%; color: rgba(18, 19, 20, 0.70);">
                    Daftar Voucher Listrik
                </p>
            </td>
        </tr>

        @foreach ($list_voucher as $voucher)
            <tr>
                <td>
                    <div style="width: 100%; height: 58px; border: 1px rgba(0, 162, 185, 0.10) solid; border-radius: 8px;">
                        <table style="padding: 4px 8px;" width="100%">
                                <tr>
                                    <td style="color: #323232; font-size: 14px; font-weight: 400; line-height: 20px;">{{ $voucher['name'] }}</td>
                                    <td style="text-align: -webkit-right !important;" width="50%">
                                        <div style="padding: 4px 6px; background: rgba(0, 169, 47.32, 0.10); border-radius: 4px; width: 54px;">
                                            <div style="color: #00A92F; font-size: 10px; font-weight: 600; line-height: 12px; text-align: center;">{{ $voucher['qty'] }} Voucher</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="color: #00A2B9; font-size: 18px; font-weight: 700; line-height: 20px;">
                                        Rp {{ number_format($voucher['amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                        </table>
                    </div>
                </td>
            </tr>
        @endforeach

        <tr>
            <td style="padding-top: 20px;">
                <p
                    style="font-weight: 700; font-size: 14px; line-height: 140%; color: black; padding-bottom: 14px; border-bottom: 1px dashed #d9d9d9;">
                    Rincian Voucher Listrik
                </p>
                <div style="padding: 0 0 14px 0; border-bottom: 1px dashed #d9d9d9;">
                    <table style="width: 100%;">
                        <tbody>
                            <tr>
                                <td style="font-weight: 400;  font-size: 16px; color: #323232; padding: 0; margin: 0;">
                                    Jumlah Voucher
                                </td>
                                <td width="50%" style="font-weight: 400;  font-size: 16px; color: #323232; padding: 0; margin: 0; text-align: -webkit-right !important;">
                                    {{ $total_qty }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="padding: 14px 0 14px 0;">
                    <table style="width: 100%;">
                        <tbody>
                            <tr>
                                <td style="font-weight: 700; font-size: 16px; color: black; padding: 0;">
                                    Total Nominal Voucher
                                </td>
                                <td width="50%" style="font-weight: 700; font-size: 16px; color: black; padding: 0; text-align: -webkit-right !important;">
                                    Rp {{ number_format($total_voucher, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="padding: 0 0 14px 0; border-bottom: 1px dotted #d9d9d9;">
                    <div style="padding: 12px 16px; background: rgba(0, 162, 185, 0.05); border-radius: 8px;">
                        <div style="text-align: center">
                            <span style="color: #00A2B9; font-size: 14px;">Harap
                                tukarkan voucher di atas sebelum tanggal </span>
                            <span
                                style="color: #00A2B9; font-size: 14px; font-weight: 700;">{{ tanggal($expired_date) }}</span>
                        </div>
                    </div>
                </div>
            </td>
        </tr>

        <tr>
            <td>
                <div style="padding: 0;">
                    <p style="font-weight: 700; font-size: 16px; color: #1213148d;">
                        Tata Cara Penggunaan
                    </p>
                </div>
                <div id="rules">
                    <table style="color: rgba(18, 19, 20, 0.70); font-size: 14px; font-weight: 400;">
                        <tr>
                            <td style="padding-right: 8px; vertical-align: text-top;">1.</td>
                            <td>
                                <span>Buka
                                    aplikasi PLN Mobile, lalu tekan menu Token dan Pembayaran. Jika Anda belum mempunyai
                                    aplikasi
                                    PLN Mobile,
                                </span>
                                <span style="font-style: italic;">download</span><span>
                                    di </span>
                                <span style="font-style: italic;">
                                    Play Store
                                </span>
                                <span>
                                    atau
                                </span>
                                <span style="font-style: italic;">
                                    App Store
                                </span>
                                <span>.</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 8px; vertical-align: text-top;">2.</td>
                            <td>
                                <span>Saat
                                    sudah di dalam halaman Token dan Pembayaran, lakukan pembelian token dengan
                                    memasukkan ID
                                    Pelanggan/Nomor Meter pada kolom yang tersedia dan tekan “periksa” atau bisa juga
                                    dengan memilih
                                    ID Pelanggan yang sudah ada di PLN Mobile.
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 8px; vertical-align: text-top;">3.</td>
                            <td>
                                <span>Periksa nominal pada voucher yang Anda dapat,
                                    lalu pilih token yang sesuai dengan nominal yang tertera pada voucher tersebut.
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 8px; vertical-align: text-top;">4.</td>
                            <td>
                                <span>Tekan “Gunakan Voucher” untuk memasukkan kode voucher Anda.
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 8px; vertical-align: text-top;">5.</td>
                            <td>
                                <span>Masukkan kode voucher lalu tekan
                                    “Gunakan” untuk melanjutkan proses penukaran voucher.
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 8px; vertical-align: text-top;">6.</td>
                            <td>
                                <span>Tekan "Lanjutkan Pembayaran".</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 8px; vertical-align: text-top;">7.</td>
                            <td>
                                <span>Voucher Anda bisa dipakai untuk pembelian token ini, total pembayaran Anda
                                    menjadi Rp 0. Lalu tekan “Pay” untuk menyelesaikan proses penukaran voucher.</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 8px; vertical-align: text-top;">8.</td>
                            <td>
                                <span>Selamat
                                    penukaran voucher Anda telah berhasil. Silakan gunakan token/stroom pada kWh meter
                                    Anda.</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table width="600" align="center"
        style="border-spacing: 10px; background: #00a2b9; margin-top: 17px; margin-bottom: 17px; color: #ffffff; border-radius: 8px;">
        <tr>
            <td colspan="2">
                <p
                    style="
                    text-align: center;
                    font-weight: 800;
                    font-size: 16px;
                    line-height: 22px;
                    margin-bottom: 6px;
                    ">
                    Punya Pertanyaan?
                </p>
                <p
                    style="
                    text-align: center;
                    font-weight: 400;
                    font-size: 14px;
                    line-height: 19px;
                    margin: 0;
                    ">
                    Hubungi PLN Mobile Help Center disini:
                </p>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;">
                <div>
                    <img src="https://media.discordapp.net/attachments/830329995989352458/1062551641888411768/icon-mail.png"
                    alt="" style="width: 18px; padding: 0px 2px" />
                    <span style="font-weight: 400; font-size: 14px; line-height: 19px; vertical-align: text-top;">
                        support.marketplace@iconpln.co.id
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <table width="600" align="center" style="border-spacing: 10px">
        <tr>
            <td align="center" colspan="2">
                <p
                    style="
                    font-weight: 700;
                    font-size: 16px;
                    line-height: 22px;
                    color: #666666;
                    margin: 0 0 16px 0;
                    ">
                    Download PLN Mobile di
                </p>
            </td>
        </tr>

        <tr>
            <td style="text-align: right;">
                <a href="https://apps.apple.com/nz/app/pln-mobile/id1299581030" target="_blank"><img
                        src="https://media.discordapp.net/attachments/830329995989352458/1062551641280225320/appstore.png"
                        alt="" style="max-width: 150px; padding: 0 8px" /></a>
            </td>
            <td>
                <a href="https://play.google.com/store/apps/details?id=com.icon.pln123" target="_blank"><img
                        src="https://media.discordapp.net/attachments/830329995989352458/1062551642572079124/playstore.png"
                        alt="" style="max-width: 150px; padding: 0 8px" /></a>
            </td>
        </tr>
    </table>
</body>

</html>

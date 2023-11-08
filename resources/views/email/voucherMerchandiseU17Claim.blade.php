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

<style>
    body {
        padding: 0;
        margin: 0;
        border: none;
        border-spacing: 0px;
        border-collapse: collapse;
        vertical-align: top;
        font-family: 'Nunito Sans', sans-serif;
        background: #e3e7ea;
    }

    .rules span {
        color: rgba(18, 19, 20, 0.70);
        font-size: 14px;
        font-weight: 400;
        line-height: 19.60px;
        word-wrap: break-word;
    }

    .rules .number {
        padding-right: 8px;
        padding-top: 4px;
        display: flex;
        align-items: start;
    }
</style>

<body>
    <table width="600" align="center" style="border-spacing: 10px; background: #ffffff; padding: 48px">
        <tr>
            <td style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <img src="https://api-mkp.iconcash.id/v1/cdn/api/firebase/file/load/Marketplace~merchants~1699432068b03575c1-990e-46f4-b666-bc476d83a2e9.png"
                        alt="" style="max-width: 90px" />
                </div>
                <div>
                    <img src="https://media.discordapp.net/attachments/830329995989352458/1062551640978247770/pln-mobile-logo.png"
                        alt="" style="max-width: 200px" />
                </div>
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
        <tr>
            <td style="display: flex; flex-direction: column; gap: 20px;">
                @foreach ($list_voucher as $voucher)
                    <div
                        style="width: 100%; height: 58px; background: rgba(0, 162, 185, 0.05); border-radius: 8px; flex-direction: column; justify-content: flex-start; align-items: flex-start; display: inline-flex">
                        <div
                            style="align-self: stretch; height: 58px; padding-left: 12px; padding-right: 12px; padding-top: 10px; padding-bottom: 10px; background: white; border-radius: 8px; border: 1px rgba(0, 162, 185, 0.10) solid; flex-direction: column; justify-content: flex-start; align-items: flex-start; gap: 10px; display: flex">
                            <div
                                style="align-self: stretch; justify-content: flex-start; align-items: flex-start; gap: 12px; display: inline-flex">
                                <div
                                    style="flex: 1 1 0; flex-direction: column; justify-content: flex-start; align-items: flex-start; gap: 12px; display: inline-flex">
                                    <div
                                        style="align-self: stretch; height: 44px; flex-direction: column; justify-content: flex-start; align-items: flex-start; gap: 4px; display: flex">
                                        <div
                                            style="align-self: stretch; color: #323232; font-size: 14px; font-weight: 400; line-height: 20px; word-wrap: break-word">
                                            {{ $voucher['name'] }}</div>
                                        <div
                                            style="align-self: stretch; color: #00A2B9; font-size: 18px; font-weight: 700; line-height: 20px; word-wrap: break-word">
                                            Rp {{ number_format($voucher['amount'], 2, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div
                                    style="flex-direction: column; justify-content: flex-start; align-items: flex-end; gap: 8px; display: inline-flex">
                                    <div
                                        style="padding-left: 8px; padding-right: 8px; padding-top: 4px; padding-bottom: 4px; background: rgba(0, 169, 47.32, 0.10); border-radius: 4px; justify-content: center; align-items: center; gap: 10px; display: inline-flex">
                                        <div
                                            style="color: #00A92F; font-size: 10px; font-weight: 600; line-height: 12px; word-wrap: break-word">
                                            {{ $voucher['qty'] }} Voucher</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </td>
        </tr>
        <tr>
            <td style="padding-top: 20px;">
                <p
                    style="font-weight: 700; font-size: 14px; line-height: 140%; color: black; padding-bottom: 14px; border-bottom: 1px dashed #d9d9d9;">
                    Rincian Voucher Listrik
                </p>
                <div
                    style="padding: 0 0 14px 0; border-bottom: 1px dashed #d9d9d9; display: flex; justify-content: space-between;">
                    <p style="font-weight: 400;  font-size: 16px; color: #323232; padding: 0; margin: 0;">
                        Jumlah Voucher
                    </p>
                    <p style="font-weight: 400;  font-size: 16px; color: #323232; padding: 0; margin: 0;">
                        {{ $total_qty }}
                    </p>
                </div>
                <div style="padding: 0 0 14px 0; display: flex; justify-content: space-between;">
                    <p style="font-weight: 700;  font-size: 16px; color: black; padding: 0;">
                        Total Nominal Voucher
                    </p>
                    <p style="font-weight: 700;  font-size: 16px; color: black; padding: 0;">
                        Rp {{ number_format($total_voucher, 2, ',', '.') }}
                    </p>
                </div>
                <div style="padding: 0 0 14px 0; border-bottom: 1px dotted #d9d9d9;">
                    <div style="padding: 12px 16px; background: rgba(0, 162, 185, 0.05); border-radius: 8px;">
                        <div
                            style="flex-direction: column; justify-content: flex-start; align-items: center; gap: 12px; display: flex">
                            <div style="text-align: center">
                                <span
                                    style="color: #00A2B9; font-size: 14px; font-family: Nunito Sans; font-weight: 400; word-wrap: break-word">Harap
                                    tukarkan voucher di atas sebelum tanggal </span>
                                <span
                                    style="color: #00A2B9; font-size: 14px; font-family: Nunito Sans; font-weight: 700; word-wrap: break-word">{{ tanggal($expired_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div style="padding: 0 0 6px 0;">
                    <p style="font-weight: 700; font-size: 16px; color: rgba(18, 19, 20, 0.70);">
                        Tata Cara Penggunaan
                    </p>
                </div>
                <div class="rules">
                    <table>
                        <tr>
                            <td class="number"><span>1.</span></td>
                            <td>
                                <span>Buka
                                    aplikasi PLN Mobile, lalu tekan menu Token dan Pembayaran. Jika Anda belum mempunyai
                                    aplikasi
                                    PLN Mobile,
                                </span>
                                <span
                                    style="color: rgba(18, 19, 20, 0.70); font-size: 14px; font-style: italic; font-weight: 400;">download</span><span>
                                    di </span>
                                <span
                                    style="color: rgba(18, 19, 20, 0.70); font-size: 14px; font-style: italic; font-weight: 400;">
                                    Play Store
                                </span>
                                <span>
                                    atau
                                </span>
                                <span
                                    style="color: rgba(18, 19, 20, 0.70); font-size: 14px; font-style: italic; font-weight: 400;">
                                    App Store
                                </span>
                                <span>.</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="number"><span>2.</span></td>
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
                            <td class="number"><span>3.</span></td>
                            <td>
                                <span>Periksa nominal pada voucher yang Anda dapat,
                                    lalu pilih token yang sesuai dengan nominal yang tertera pada voucher tersebut.
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="number"><span>4.</span></td>
                            <td>
                                <span>Tekan “Gunakan Voucher” untuk memasukkan kode voucher Anda.
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="number"><span>5.</span></td>
                            <td>
                                <span>Masukkan kode voucher lalu tekan
                                    “Gunakan” untuk melanjutkan proses penukaran voucher.
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="number"><span>6.</span></td>
                            <td>
                                <span>Tekan "Lanjutkan Pembayaran".</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="number"><span>7.</span></td>
                            <td>
                                <span>Voucher Anda bisa dipakai untuk pembelian token ini, total pembayaran Anda
                                    menjadi Rp 0. Lalu tekan “Pay” untuk menyelesaikan proses penukaran voucher.</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="number"><span>8.</span></td>
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
            <td>
                <p
                    style="
              text-align: center;
              font-weight: 800;
              font-size: 16px;
              line-height: 22px;
              margin-bottom: 16px;
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
            <td style="display: flex; justify-content: center">
                <div style="display: flex; align-items: center">
                    <img src="https://media.discordapp.net/attachments/830329995989352458/1062551642253303808/icon-phone.png"
                        alt="" style="max-width: 50px; margin: 0 8px" />
                    <span style="font-weight: 400; font-size: 14px; line-height: 19px">
                        150 0071
                    </span>
                </div>
                <div style="display: flex; align-items: center; padding-left: 21px">
                    <img src="https://media.discordapp.net/attachments/830329995989352458/1062551641888411768/icon-mail.png"
                        alt="" style="max-width: 50px; margin: 0 8px" />
                    <span style="font-weight: 400; font-size: 14px; line-height: 19px">
                        humas@pln.co.id
                    </span>
                </div>
            </td>
        </tr>
    </table>
    <table width="600" align="center" style="border-spacing: 10px">
        <tr>
            <td align="center">
                <p
                    style="
              font-weight: 700;
              font-size: 16px;
              line-height: 22px;
              color: #666666;
            ">
                    Download PLN Mobile di
                </p>
            </td>
        </tr>
        <tr>
            <td style="display: flex; align-items: center; justify-content: center">
                <a href="https://apps.apple.com/nz/app/pln-mobile/id1299581030" target="_blank"><img
                        src="https://media.discordapp.net/attachments/830329995989352458/1062551641280225320/appstore.png"
                        alt="" style="max-width: 150px; padding: 0 8px" /></a>
                <a href="https://play.google.com/store/apps/details?id=com.icon.pln123" target="_blank"><img
                        src="https://media.discordapp.net/attachments/830329995989352458/1062551642572079124/playstore.png"
                        alt="" style="max-width: 150px; padding: 0 8px" /></a>
            </td>
        </tr>
    </table>
</body>

</html>

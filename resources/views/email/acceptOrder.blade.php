<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Roboto&display=swap" rel="stylesheet">
</head>

<body
    style="padding: 0; margin: 0; border: none; border-spacing: 0px; border-collapse: collapse;vertical-align: top; font-family: 'Roboto';">
    <div class="wrapper" width="600" align="center">
        <!-- header -->
        <table style="background-image: url('https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~163833338096dc97f2-01f0-444a-aff1-7156f4349ab3.png');
        background-repeat: no-repeat;
        background-size: contain;
        background-position: right top;
        margin-left: 10px;">
            <tr>
                <td width="600" valign="top">
                    <table>
                        <tbody>
                            <tr>
                                <td>
                                    <a style="margin: 50px 0px 50px 0px;
                                    padding-left: 20px;
                                    display: inline;
                                    position: relative;">
                                        <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16383335039869f3c3-7aea-4d2f-8527-5513b83428a3.png"
                                            alt="" style="margin-top: 50px;width: 50px;">
                                    </a>
                                </td>
                                <td>
                                    <div style="margin-top: 45px;">
                                        <h3 style="display: inline;
                                        padding: 50px 0px 0px 10px;
                                        font-family: 'Poppins', sans-serif;
                                        text-align: top;
                                        position: relative;">MARKETPLACE</h3>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- subjek -->
        <table>
            <tr>
                <td width="430" valign="top">
                    <table>
                        <tbody>
                            <tr>
                                <td align="left" style="padding-left:30px;">
                                    <p style="font-size: small;font-weight: bold;">Halo Jane Doe,</p>
                                    <h2 style="color: #00A2B9;margin: 0; padding: 0;
                                    line-height: 1.6;">
                                        Pesanan Dikonfirmasi Penjual
                                    </h2>
                                    @php
                                        \Carbon\Carbon::setLocale('id');
                                    @endphp
                                    <p style="font-size: small;font-weight: bold;">Diinformasikan tanggal
                                        {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="column" width="170" valign="top">
                    <table>
                        <tbody>
                            <tr>
                                <td align="left">

                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- detail -->
        <table>
            <tr>
                <td width="600">
                    <div style="padding-left: 30px; padding-right: 30px;">
                        @if ($order->voucher_ubah_daya_code)
                            <div
                                style="margin-bottom: 20px;margin-top: 20px;padding-bottom: 20px;padding-top: 20px;border-radius: 20px;background-color: #E6F6F8;">
                                <div style="text-align: center;">
                                    <span style="color:#00A2B9;font-weight:bold;">Selamat! Anda mendapatkan Voucher Ubah
                                        Daya</span>
                                </div>
                                <div style="width: 60%; margin-left: auto;margin-right: auto;">
                                    <div style="
                                    width: 100%;
                                    background-image: url('https://api-central.air.id/plnmp-sauron-development/api/firebase/file/load/Marketplace~products~1648611938e5c7d82e-4b25-445b-8fb4-9460cfa289d0.png');
                                    background-repeat: no-repeat;
                                    background-size: contain;
                                    background-position: center;">
                                        <div style="margin-left: 20%;padding: 10% 5% 10% 0;">
                                            <table>
                                                <tbody style="color: #fff;text-align: left;font-size: 12px;">
                                                    <tr>
                                                        <td style="font-weight: bold;padding-bottom: 5px;">Voucher Ubah
                                                            Daya</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="opacity: 0.7;padding-bottom: 5px;">Voucher ini dapat
                                                            digunakan di PLN Mobile pada menu Ubah Daya dan Migrasi.
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="font-weight: bold;padding-bottom: 5px;">
                                                            {{ $order->voucher_ubah_daya_code }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    style="width: fit-content; text-align: center; vertical-align: middle; margin-left: auto;margin-right: auto;border-radius: 5px;background: #fff;padding: 5px 7px 5px 7px;">
                                    <img style="display: inline-block;width:15px;vertical-align: middle;"
                                        src="https://api-central.air.id/plnmp-sauron-development/api/firebase/file/load/Marketplace~products~16487308789891cd8b-e1e8-4f14-a69a-6b2018b688ec.png"
                                        alt="">
                                    <p style="display: inline-block; font-size: 12px;margin:0;vertical-align: middle;">
                                        Anda hanya bisa mendapatkan satu voucher ubah daya</p>
                                </div>
                            </div>
                        @endif

                        <p style="color: #666;font-size: 15px;">Berikut detail pembayaran Anda :</p>

                        <div style="margin-bottom: 10px;
                        border-radius: 20px;
                        background-color: #E6F6F8;
                        margin-right: 50px;
                        margin-left:50px;">
                            <div style=" padding: 2px 5px;">
                                <table style="margin: 0px 10px 0px 10px;">
                                    <tr>
                                        <td style="font-size: 14px;color:#666;padding: 10px 30px 10px 10px;">Metode
                                            Pembayaran</td>
                                        <td style="font-weight: bold;">{{ $order->payment->payment_method ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px;color:#666;padding: 10px 30px 10px 10px;">Total
                                            Pembayaran</td>
                                        <td style="color: #FF5E5E;font-weight: bold;">Rp {{ number_format($order->payment->payment_amount, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px;color:#666;padding: 10px 30px 10px 10px;">Waktu
                                            Pembayaran</td>
                                        <td style="font-weight: bold;">{{ \Carbon\Carbon::parse($order->order_date)->isoFormat('dddd, D MMMM Y, H:m') }} WIB</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div style="border-radius: 20px;
                        background-color: #ffe7e7;
                        margin-right: 50px;
                        margin-left:50px;">
                            <table style="margin: 10px 25px 10px 25px;">
                                <tr>
                                    <td align="left">
                                        <a style="width: 50px;padding-right: 10px;">
                                            <img src="https://api-central.air.id/plnmp-sauron-development/api/firebase/file/load/Marketplace~products~164915105549826745-05ca-4db4-b37e-9ed073e63112.png"
                                                alt="" style="max-width: 30px;">
                                        </a>
                                    </td>
                                    <td align="left">
                                        <p style="font-size: small;color: #FF5E5E;padding: 0;">Jangan memberitahukan
                                            bukti dan data pembayaran kepada
                                            pihak manapun kecuali <span style="font-weight: bold;">Marketplace</span>.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div>
                            <h3>Ringkasan Pembayaran</h3>
                            <table style="width: 100%;">
                                <tbody>
                                    <tr>
                                        <td style="padding: 5px 0px 5px 0px;font-size: 14px;color:#666;width: 50%;">
                                            Total Harga ({{ $order->detail->sum('quantity') }} Barang)</td>
                                        <td style="font-weight:bold;text-align:right;">Rp
                                            {{ number_format($order->detail->sum('total_price'), 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0px 5px 0px;font-size: 14px;color:#666;width: 50%;">
                                            Total Ongkos Kirim</td>
                                        <td style="font-weight:bold;text-align:right;">
                                            {{ $order->delivery->delivery_fee ? 'Rp ' . number_format($order->delivery->delivery_fee, 2, ',', '.') : 'Rp 0' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0px 5px 0px;font-size: 14px;color:#666;width: 50%;">
                                            Asuransi Pengiriman</td>
                                        <td style="font-weight:bold;text-align:right;">
                                            {{ $order->detail->sum('total_insurance_cost')? 'Rp ' . number_format($order->detail->sum('total_insurance_cost'), 2, ',', '.'): 'Rp 0' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px;color:#666;width: 50%;">Diskon</td>
                                        <td style="font-weight:bold;text-align:right;">Rp
                                            {{ number_format($order->detail->sum('total_discount')) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <hr>
                            <table style="width: 100%;">
                                <tbody>
                                    <tr>
                                        <td
                                            style="padding:0px 0px 30px 0px;font-size: 14px;font-weight: bold;width: 50%;">
                                            Total Tagihan</td>
                                        <td
                                            style="padding:0px 0px 30px 0px;font-weight:bold;text-align:right;color: #FF5E5E">
                                            Rp {{ number_format($order->payment->payment_amount, 2, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <h3 style="margin-bottom: 10px;">Dibayar dengan</h3>
                            <table style="width: 100%;">
                                <tbody>
                                    <tr>
                                        <td style="font-size: 14px;color:#666;width: 50%;">
                                            {{ $order->payment->payment_method ?? '-' }}</td>
                                        <td style="font-weight:bold;text-align:right;">Rp
                                            {{ number_format($order->payment->payment_amount, 2, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-bottom: 30px;">
                            <h3>Rincian Pesanan</h3>
                            <p style="font-size: 14px;color:#666;">No. Invoice: <span
                                    style="color: #00A2B9;">I{{ $order->trx_no }}</span></p>

                            @php
                                $total_price_item = 0;
                            @endphp
                            @foreach ($order->detail as $item)
                                <p style="font-size: 14px;color:#666;">Toko: <span
                                        style="color: #00A2B9;font-weight: bold;">{{ $item->product->merchant->name ?? '-' }}</span>
                                </p>

                                <table>
                                    <tbody>
                                        <tr>
                                            <td style="width: 10%">
                                                <img src="{{ $item->product->product_photo->first()->url }}" alt=""
                                                    style="max-width: 100px;">
                                            </td>
                                            <td style="width: 40%">
                                                <p style="margin: 0px 0px 5px 0px;">{{ $item->product->name }}</p>
                                                <p style="font-size: 10px;color: #666;margin:0px 0px 5px 0px;">Berat:
                                                    @if ($item->weight > 907185)
                                                        ({{ Illuminate\Support\Str::limit($item->weight / 1000000, 3, '') }}
                                                        ton)
                                                    @elseif ($item->weight < 1000)
                                                        ({{ $item->weight . ' g' }})
                                                    @elseif ($item->weight > 1000 || $item->weight < 907185)
                                                        ({{ Illuminate\Support\Str::limit($item->weight / 1000, 3, '') }}
                                                        kg)
                                                    @endif
                                                </p>
                                                <p style="font-weight: bold;margin:0px 0px 5px 0px;">
                                                    {{ $item->quantity }} x Rp
                                                    {{ number_format($item->price, 2, ',', '.') }}</p>
                                            </td>
                                            <td style="width: 15%">
                                                <p style="font-weight: bold">
                                                    {{ number_format($item->total_price, 2, ',', '.') }}</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            @endforeach
                        </div>

                        <div style="margin-bottom: 30px;">
                            <div style="font-size: 14px; color: #666;">
                                <p style="margin-bottom: 0;">Terima kasih telah menggunakan layanan Marketplace kami.
                                </p>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div style="padding-left: 30px; padding-right: 30px;">
                        <div style="margin-bottom: 30px;
                        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
                        transition: 0.3s;
                        width: 100%;
                        border-radius: 10px;
                        background-image: url('https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16383335165a7d2a99-9146-4d52-9ea6-f987b5a7b163.png');
                        background-repeat: no-repeat;
                        background-size: contain;
                        background-position: bottom;
                        background-color: #00A2B9;">
                            <div style=" padding: 2px 5px;">
                                <table style="margin: 10px;">
                                    <tr>
                                        <td class="column" width="50" valign="top">
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td align="left">
                                                            <a style="width: 50px;padding-right: 10px;">
                                                                <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16383334891b6d3f36-633a-42c5-8863-bdaed4ec14f4.png"
                                                                    alt="" style="width: 75px;">
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td class="column" valign="top">
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td align="left">
                                                            <h5 style="color: #fff;padding: 0;margin: 10px 0px;">PERLU
                                                                DIPERHATIKAN</h5>
                                                            <p style="font-size: x-small;color: #fff;padding: 0;">Jangan
                                                                menyebar luaskan informasi akun Anda, dan ganti kata
                                                                sandi secara berkala untuk menghindari aktivitas
                                                                pencurian akun.</p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr style="background-color: #333333;color: #fff;">
                <td>
                    <p style="text-align: center;padding-top: 30px;font-weight: bold;">Punya Pertanyaan?</p>
                    <p style="text-align: center;opacity: 80%;color: #fff">Hubungi Marketplace Help Center disini :</p>
                    <p style="text-align: center;opacity: 80%;color:#fff">
                        <a style="width: 15px;display: inline-block;padding-top: 8px;margin-right: 10px;">
                            <img src="https://api-central.air.id/plnmp-sauron-development/api/firebase/file/load/Marketplace~products~164861204852276f13-dd3f-4a57-a501-490b954cebb3.png"
                                alt="" style="width: 15px;">
                        </a>
                        <a href="mailto:support.marketplace@iconpln.co.id" style="color: #fff">
                            support.marketplace@iconpln.co.id
                        </a>
                    </p>

                    <div style="padding-left: 35px; padding-right: 35px;">
                        <hr style="border: 1px solid #00A2B9;">
                    </div>
                </td>
            </tr>
        </table>

        <!-- footer -->
        <table style="margin-top: 50px;margin-bottom: 30px;">
            <tr>
                <td class="column" width="150" valign="top">
                    <table>
                        <tbody>
                            <tr>
                                <td>
                                    <a style="margin-left: 30px;">
                                        <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~1638333429c9c8964b-6bd2-49b7-b226-b7243cf54c91.png"
                                            alt="" style="width: 100px;">
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="column" width="350" valign="top">
                    <table>
                        <tbody>
                            <tr>
                                <td align="left">
                                    <p style="color: #4F4F4F;">PT. Indonesia Comnets Plus telah terdaftar dan diawasi
                                        oleh Bank Indonesia </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <p style="font-size: small;text-align: center;color: #4F4F4F;">Copyright @ 2021 PT. Indonesia Comnets Plus. Hak
            cipta dilindungi undang - undang</p>
    </div>
</body>

</html>

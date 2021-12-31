<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Pesanan Dibatalkan</title>
</head>

<body style="padding: 0; margin: 0; border: none; border-spacing: 0px; border-collapse: collapse;vertical-align: top; font-family: 'Roboto';">
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
                                        <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16383335039869f3c3-7aea-4d2f-8527-5513b83428a3.png" alt=""
                                        style="margin-top: 50px;width: 50px;">
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
                                    <h2 style="color: #FF5E5E;margin: 0; padding: 0;
                                    line-height: 1.6;">
                                        Pesanan Anda telah dibatalkan
                                    </h2>
                                    @php
                                        \Carbon\Carbon::setLocale('id');
                                    @endphp
                                    <p style="font-size: small;font-weight: bold;">Diinformasikan tanggal {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y'); }}</p>
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

                        <img src="https://dev-stroom.air.id/plnmp/sauron-staging/api/firebase/file/load/Marketplace~merchants~16402373074a166c78-3888-4004-86b0-ad9abc9a3c47.png" alt=""
                        style="max-width: 150px;display:block;margin-left: auto;margin-right: auto;">

                        <p style="color: #666;font-size: 15px;">Transaksi Anda telah dibatalkan, Silahkan berbelanja kembali Toko favoritmu.</p>

                        <div style="margin-bottom: 10px;border-radius: 20px;background-color: #E6F6F8;">
                            <div style="padding: 30px;">
                                <table style="width: 100%">
                                    <tbody>
                                        <tr>
                                            <td>
                                                <span style="color:#00A2B9;font-weight:bold;">Kode Transaksi</span>
                                            </td>
                                            <td>
                                                <span style="color:#00A2B9;font-weight:bold;">Status Transaksi</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span style="color: #666;margin-top: 0;font-weight: bold;vertical-align: middle;">{{ $order->trx_no }}</span>
                                            </td>
                                            <td>
                                                <div style="vertical-align: middle;">
                                                    <img src="https://dev-stroom.air.id/plnmp/sauron-staging/api/firebase/file/load/Marketplace~merchants~1640238761dde367ed-00b0-4dc4-ac0a-01167e12cb72.png" alt=""
                                                        style="display: inline-block;max-width: 22px;vertical-align: middle;">
                                                    <span style="color: #FF5E5E;margin-top: 0;font-weight: bold;display:inline-block; vertical-align: middle;">Dibatalkan</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <h3>Ringkasan Pembayaran</h3>
                            <table style="width: 100%;">
                                <tbody>
                                    <tr>
                                        <td style="padding: 5px 0px 5px 0px;font-size: 14px;color:#666;width: 50%;">
                                            Total Harga ({{ $order->detail->sum('quantity') }} Barang)
                                        </td>
                                        <td style="font-weight:bold;text-align:right;">
                                            Rp {{number_format($order->detail->sum('total_price'), 2, ',', '.')}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0px 5px 0px;font-size: 14px;color:#666;width: 50%;">
                                            Total Ongkos Kirim
                                        </td>
                                        <td style="font-weight:bold;text-align:right;">
                                            {{$order->delivery->delivery_fee ? 'Rp ' . number_format($order->delivery->delivery_fee, 2, ',', '.') : 'Rp 0'}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0px 5px 0px;font-size: 14px;color:#666;width: 50%;">
                                            Asuransi Pengiriman
                                        </td>
                                        <td style="font-weight:bold;text-align:right;">
                                            {{$order->detail->sum('total_insurance_cost') ? 'Rp ' . number_format($order->detail->sum('total_insurance_cost'), 2, ',', '.') : 'Rp 0'}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px;color:#666;width: 50%;">
                                            Diskon
                                        </td>
                                        <td style="font-weight:bold;text-align:right;">
                                            Rp {{ number_format($order->detail->sum('total_discount')) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <hr>
                            <table style="width: 100%;">
                                <tbody>
                                    <tr>
                                        <td style="padding:0px 0px 30px 0px;font-size: 14px;font-weight: bold;width: 50%;">
                                            Total Tagihan
                                        </td>
                                        <td style="padding:0px 0px 30px 0px;font-weight:bold;text-align:right;color: #FF5E5E">
                                            Rp {{number_format($order->payment->payment_amount, 2, ',', '.')}}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-bottom: 30px;">
                            <h3>Rincian Pesanan</h3>
                            <p style="font-size: 14px;color:#666;">No. Invoice:
                                <span style="color: #00A2B9;">{{$order->trx_no}}</span>
                            </p>
                            @php
                                $total_price_item = 0;
                            @endphp
                            @foreach ($order->detail as $item)
                                <p style="font-size: 14px;color:#666;">Toko:
                                    <span style="color: #00A2B9;font-weight: bold;">{{ $item->product->merchant->name ?? '-' }}</span>
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
                                                        ({{Illuminate\Support\Str::limit($item->weight/1000000, 3, '')}} ton)
                                                    @elseif ($item->weight < 1000)
                                                        ({{$item->weight . ' g'}})
                                                    @elseif ($item->weight > 1000 || $item->weight < 907185 )
                                                        ({{Illuminate\Support\Str::limit($item->weight/1000, 3, '')}} kg)
                                                    @endif
                                                </p>
                                                <p style="font-weight: bold;margin:0px 0px 5px 0px;">
                                                    {{$item->quantity}} x Rp {{number_format($item->price, 2, ',', '.')}}
                                                </p>
                                            </td>
                                            <td style="width: 15%">
                                                <p style="font-weight: bold">Rp {{ number_format($item->total_price,  2, ',', '.') }}</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            @endforeach
                        </div>

                        <div style="margin-bottom: 30px;">
                            <h3>Alamat Pengiriman</h3>
                            <div style="font-size: 14px; color: #666;">
                                <p style="margin-bottom: 0;">{{ $destination_name }}</p>
                                <p style="margin: 0px;">{{ $order->delivery->address ?? '-' }}, {{$order->delivery->district->name}}, {{$order->delivery->city->name}}, {{ $order->delivery->postal_code }}</p>
                                <p style="margin-top: 0px;">+{{ $order->delivery->receiver_phone }}</p>
                            </div>
                        </div>

                        <div style="margin-bottom: 30px;">
                            <div style="font-size: 14px; color: #666;">
                                <p style="margin-bottom: 0;">Terima kasih telah menggunakan layanan Marketplace kami.</p>
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
                                                            <h5 style="color: #fff;padding: 0;margin: 10px 0px;">PERLU DIPERHATIKAN</h5>
                                                            <p style="font-size: x-small;color: #fff;padding: 0;">Jangan menyebar luaskan informasi akun Anda, dan ganti kata sandi secara berkala untuk menghindari aktivitas pencurian akun.</p>
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
                            <img src="https://dev-stroom.air.id/plnmp/sauron-staging/api/firebase/file/load/Marketplace~merchants~1640230158dc832050-d7a1-49a6-b873-54c917bc787f.png" alt="" style="width: 15px;">
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
                                    <p style="color: #4F4F4F;">PT. Indonesia Comnets Plus telah terdaftar dan diawasi oleh Bank Indonesia </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <p style="font-size: small;text-align: center;color: #4F4F4F;">Copyright @ 2021 PT. Indonesia Comnets Plus. Hak cipta dilindungi undang - undang</p>
    </div>
</body>

</html>

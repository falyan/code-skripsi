<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pemesanan Tiket PLN MObile Proliga 2023</title>
</head>

<body
    style="
      padding: 0;
      margin: 0;
      border: none;
      border-spacing: 0px;
      border-collapse: collapse;
      vertical-align: top;
      font-family: sans-serif;
      background: #e3e7ea;
    ">
    <table width="600" align="center" style="border-spacing: 10px; background: #ffffff; padding: 48px">
        <tr>
            <td
                style="display: flex;
                justify-content: space-between;
                align-items: center;
            ">
                <div>
                    <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~1684815896a3e79896-1614-438e-a068-02066220df9e.png"
                        alt="" style="width: 105px" />
                </div>
                <div>
                    <img src="https://media.discordapp.net/attachments/830329995989352458/1062551640978247770/pln-mobile-logo.png"
                        alt="" style="width: 80px" />
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <p
                    style="font-weight: 400;
                    font-size: 16px;
                    line-height: 100%;
                    color: #323232;
                ">
                    Halo, {{ $destination_name }}! 🖐🏻
                </p>
                <p
                    style="font-weight: 400;
                    font-size: 16px;
                    line-height: 140%;
                    color: #323232;
                    padding-bottom: 24px;
                    border-bottom: 1px dashed #d9d9d9;
                ">
                    Email ini adalah konfirmasi pemesanan tiket Anda untuk acara
                    <strong>GJLS x PLN Mobile - Bandung - 16 Juni 2023.</strong>
                    Berikut kami lampirkan tiket untuk kenyamanan Anda.
                    Pada hari acara, harap scan barcode di loket penukaran tiket untuk memasuki event.
                </p>
            </td>
        </tr>
        <tr>
            <td
                style="
                font-weight: 600;
                font-size: 14px;
                line-height: 19px;
                color: #595a5b;
                display: flex;
                align-items: center;
            ">
                <table
                    style="
                    width: 100%;
                    border-spacing: 10px;
                    background: #f8f8f9;
                    margin-top: 5px;
                    margin-bottom: 16px;
                    color: #ffffff;
                    border-radius: 8px;
                    padding: 10px;
                ">
                    <tr>
                        <td>
                            <span style="font-size: 14px; color: #595a5b">
                                ID Transaksi
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 14px; color: #595a5b">
                                <strong>{{ $order->trx_no }}</strong>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span style="font-size: 14px; color: #595a5b"> Nama </span>
                        </td>
                        <td>
                            <span style="font-size: 14px; color: #595a5b">
                                <strong>{{ $destination_name }}</strong>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span style="font-size: 14px; color: #595a5b"> No. Handphone </span>
                        </td>
                        <td>
                            <span style="font-size: 14px; color: #595a5b">
                                <strong>{{ $order->buyer->phone }}</strong>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span style="font-size: 14px; color: #595a5b"> Email </span>
                        </td>
                        <td>
                            <span style="font-size: 14px; color: #595a5b">
                                <strong>{{ $order->buyer->email }}</strong>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span style="font-size: 14px; color: #595a5b"> Waktu </span>
                        </td>
                        @php
                            \Carbon\Carbon::setLocale('id');
                            $date = tanggalDate($user_tikets[0]->usage_date);
                            $time_start = \Carbon\Carbon::createFromFormat('H:i:s', $user_tikets[0]->start_time_usage)->format('H:i');
                            $time_end = \Carbon\Carbon::createFromFormat('H:i:s', $user_tikets[0]->end_time_usage)->format('H:i');
                        @endphp
                        <td>
                            @if ($user_tikets[0]->start_time_usage != null && $user_tikets[0]->end_time_usage != null)
                                <span style="font-size: 14px; color: #595a5b">
                                    <strong>{{ $date . ', ' . $time_start . '-' . $time_end . ' WIB' }}</strong>
                                </span>
                            @else
                                <span style="font-size: 14px; color: #595a5b">
                                    <strong>{{ $date }}</strong>
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span style="font-size: 14px; color: #595a5b"> Lokasi </span>
                        </td>
                        <td>
                            <span style="font-size: 14px; color: #595a5b">
                                <strong>{{ $user_tikets[0]->master_tiket->event_address }}</strong>
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="display: flex; align-items: top">
                <table
                    style="
                    width: 100%;
                    border-spacing: 10px;
                    background: #f8f8f9;
                    margin-top: 5px;
                    margin-bottom: 16px;
                    color: #ffffff;
                    border-radius: 8px;
                    padding: 10px;
                ">
                    <!-- Header table -->
                    <tr>
                        <td style="width: 30%;">
                            <span style="font-size: 16px; color: #595a5b;">
                                <strong>Detail Tiket</strong>
                            </span>
                            <p></p>
                        </td>
                    </tr>
                    <!-- End Header table -->
                    <!-- Content table -->
                    <tr style="font-size: 14px; color: #595a5b">
                        <td>
                            <strong>Jenis Tiket</strong>
                        </td>
                        <td>
                            <strong>Harga Tiket</strong>
                        </td>
                        <td>
                            <strong>Kuantitas</strong>
                        </td>
                        <td>
                            <strong>Total</strong>
                        </td>
                    </tr>

                    @foreach ($order->detail as $item)
                        <tr style="font-size: 14px; color: #595a5b">
                            <td>{{ $item->product->name }}</td>
                            <td>Rp {{ number_format($item->price, 2, ',', '.') }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>Rp {{ number_format($item->quantity * $item->price, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    <!-- Total -->
                    <tr style="font-size: 14px; color: #595a5b">
                        <td></td>
                        <td>
                            <strong>Total Bayar</strong>
                        </td>
                        <td></td>
                        <td>
                            <strong>Rp {{ number_format($order->payment->payment_amount, 2, ',', '.') }}</strong>
                        </td>
                    </tr>
                    <!-- End Content table -->
                </table>
            </td>
        </tr>
    </table>
    <table width="600" align="center"
        style="
        border-spacing: 10px;
        background: #00a2b9;
        margin-top: 17px;
        margin-bottom: 17px;
        color: #ffffff;
        border-radius: 8px;
    ">
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

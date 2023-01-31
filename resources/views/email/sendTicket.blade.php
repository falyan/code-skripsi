<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pemesanan Tiket PLN Mobile Pro Liga 2023</title>
</head>

<body style="padding: 0 56px">
    <div
        style="
        width: 100%;
        display: flex;
        overflow: auto;
        align-items: center;
        justify-content: space-between;
        ">
        <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16750575780b256952-dad0-4d77-85dd-e35d2d9d5616.png"
            alt="image" style="width: 200px; height: 100px" />
        <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~1675057641628ba014-956e-44f3-9e1e-78b6d935c615.png"
            alt="image" style="width: 80px; height: 80px" />
    </div>

    <div style="width: 100%; padding: 32px 0">
        <span style="font-family: Nunito; font-size: 16px">
            <span>Halo, {{ $destination_name }}! 🖐🏻</span>
            <br />
            <br />
            <span>
                Email ini adalah konfirmasi pemesanan tiket Anda untuk acara
                <strong>PRO LIGA Bola Voli 2023.</strong> Berikut kami lampirkan tiket
                untuk kenyamanan Anda. Pada hari acara, harap scan barcode di loket
                penukaran tiket untuk memasuki event.
            </span>
        </span>
    </div>
    <hr />
    <div
        style="
        background-color: #f8f8f9;
        padding: 48px;
        margin-top: 32px;
        width: auto;
        ">
        <!-- table in center without text-align-->
        <table style="width: 100%; border-collapse: collapse; margin: 0 auto">
            <tr>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        ID Transaksi
                    </span>
                </td>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        <strong>{{ $order->trx_no }}</strong>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        Nama
                    </span>
                </td>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        <strong>{{ $destination_name }}</strong>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        Email
                    </span>
                </td>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        <strong>{{ $order->buyer->email }}</strong>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        No. HP
                    </span>
                </td>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        <strong>{{ $order->buyer->phone }}</strong>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        Waktu
                    </span>
                </td>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        {{-- Carbon now --}}
                        <strong>{{ \Carbon\Carbon::now()->format('d M Y H:m') }}</strong>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        Lokasi
                    </span>
                </td>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        <strong>{{ $user_tikets[0]->master_tiket->event_address }}</strong>
                    </span>

                </td>
            </tr>
        </table>
    </div>

    <div
        style="
        background-color: #f8f8f9;
        padding: 48px;
        margin-top: 32px;
        width: auto;
        ">
        <!-- title -->
        <div style="width: 100%; padding-bottom: 32px">
            <span style="font-family: Nunito; font-size: 24px; color: #595a5b">
                <strong>Detail Tiket</strong>
            </span>
        </div>

        <!-- detail table in center without text-align-->
        <table style="width: 100%; border-collapse: collapse; margin: 0 auto">
            <tr>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        <strong>Jenis Tiket</strong>
                    </span>
                </td>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        <strong>Harga Per Tiket</strong>
                    </span>
                </td>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        <strong>Kuantitas</strong>
                    </span>
                </td>
                <td style="padding-bottom: 14px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        <strong>Total</strong>
                    </span>
                </td>
            </tr>
            @foreach ($order->detail as $item)
                <tr>
                    <td style="padding-bottom: 14px">
                        <span style="font-family: Nunito; font-size: 14px; color: #595a5b">
                            {{ $item->product->name }}
                        </span>
                    </td>
                    <td style="padding-bottom: 14px">
                        <span style="font-family: Nunito; font-size: 14px; color: #595a5b">
                            Rp {{ number_format($item->price, 2, ',', '.') }}
                        </span>
                    </td>
                    <td style="padding-bottom: 14px">
                        <span style="font-family: Nunito; font-size: 14px; color: #595a5b">
                            {{ $item->quantity }}
                        </span>
                    </td>
                    <td style="padding-bottom: 14px">
                        <span style="font-family: Nunito; font-size: 14px; color: #595a5b">
                            Rp {{ number_format($item->quantity * $item->price, 2, ',', '.') }}
                        </span>
                    </td>
                </tr>
            @endforeach

            <!-- total bayar -->
            <tr>
                <td></td>
                <td></td>
                <td style="padding-top: 16px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        <strong>Total Bayar</strong>
                    </span>
                </td>
                <td style="padding-top: 16px">
                    <span style="font-family: Nunito; font-size: 16px; color: #595a5b">
                        <strong>Rp {{ number_format($order->payment->payment_amount, 2, ',', '.') }}</strong>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div
        style="
        background-color: #00a2b9;
        border-radius: 24px;
        padding: 32px;
        margin-top: 56px;
        margin-left: 56px;
        margin-right: 56px;
        ">
        <!-- footer text in center -->
        <div style="width: 100%; text-align: center">
            <span style="font-family: Nunito; font-size: 16px; color: #ffffff">
                <h2><strong>Punya Pertanyaan?</strong></h2>
                <p>Hubungi PLN Mobile Help Center disini:</p>
                <p>
                    150 0071 |
                    <a href="mailto:humas@pln.co.id" style="text-decoration: none; color: #ffffff">humas@pln.co.id</a>
                </p>
            </span>
        </div>
    </div>

    <!-- footer logo apstore and playstore in center -->
    <div style="width: 100%; text-align: center; margin: 0 auto; padding-top: 32px">
        <a href="https://apps.apple.com/nz/app/pln-mobile/id1299581030" target="_blank"><img
                src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~1675062537bec8f7b1-c1c6-4cae-9c0c-fbbddcb50122.png"
                alt="Group-1" border="0" style="width: 150px" /></a>
        <a href="https://play.google.com/store/apps/details?id=com.icon.pln123" target="_blank"><img
                src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~1675062746e31382bf-04a1-4703-8820-1008fcc0f52e.png"
                alt="Group-1" border="0" style="width: 165px" /></a>
    </div>
</body>

</html>

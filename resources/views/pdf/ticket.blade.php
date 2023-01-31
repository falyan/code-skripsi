<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>E-Ticket PLN Mobile Pro Liga 2023</title>

    <style>
        table,
        th,
        td {
            border-collapse: collapse;
        }

        th,
        td {
            padding: 5px;
            text-align: left;
        }
    </style>
</head>

<body>
    <div
        style="
        display: inline-block;
        position: relative;
        height: 297mm;
        height: 210mm;
        font-size: 12;
        padding: 0.5cm;
        ">
        <div
            style="
        width: 100%;
        display: flex;
        overflow: auto;
        align-items: center;
        justify-content: space-between;
        ">
            <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16750575780b256952-dad0-4d77-85dd-e35d2d9d5616.png"
                alt="image" style="width: 150px;" />
        </div>

        <div style="width: 100%; padding-top: 10px">
            <span style="font-family: Nunito; font-size: 24px; font-weight: bold">
                <p>E-Ticket Pro Liga 2023</p>
            </span>
        </div>

        <!-- Span text with background radius Red -->
        @if ($user_tiket['is_vip'])
            <span
                style=" background-color: #fcc71d33;color: #fcc71d;padding: 8px 16px; border-radius: 8px; font-family: Nunito; font-size: 16px;">VIP</span>
        @else
            <span
                style="background-color: #831d641a;color: #831d64;padding: 8px 16px;border-radius: 8px;font-family: Nunito;font-size: 16px;">Reguler</span>
        @endif

        <div style="margin-top: 64px; width: auto; margin-bottom: 100px">
            <table style="width: 100%">
                <tr>
                    <th style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        ID Transaksi
                    </th>
                    <th style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        Kode Tiket
                    </th>
                </tr>
                <tr>
                    <td style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        {{ $order->trx_no }}
                    </td>
                    <td style="font-family: Nunito; font-size: 14px; color: #00a2b9">
                        <strong>{{ $user_tiket->number_tiket }}</strong>
                    </td>
                </tr>
                <tr>
                    <th style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        Nama
                    </th>
                    <th style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        Waktu
                    </th>
                </tr>
                <tr>
                    <td style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        {{ $customer->full_name }}
                    </td>
                    <td style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        {{ \Carbon\Carbon::parse($user_tiket->usage_date)->format('d M Y') }}
                    </td>
                </tr>
                <tr>
                    <th style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        Email
                    </th>
                    <th style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        Lokasi
                    </th>
                </tr>
                <tr>
                    <td style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        {{ $customer->email }}
                    </td>
                    <td style="font-family: Nunito; font-size: 14px; color: #595a5b">
                        {{ $user_tiket->master_tiket->event_address }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Ticket layout template -->

        <table style="width:100%; border:#e5e5e5 1px solid;">
            <tr>
                <td style="width:30%">
                    <span
                        style="writing-mode: vertical-rl;transform: rotate(90deg);font-family: Nunito;font-size: 10px;color: #595a5b; margin-top:20px">
                        Tiket {{ $order->trx_no }}
                    </span>
                    <div style="margin-left: 48px; margin-bottom:10px">
                        {{-- Generate QR with link --}}
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ $user_tiket->number_tiket }}"
                            alt="image" style="width: 85px" />
                        {{-- <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16750733608bb90133-7f7b-45dc-9da7-7bb1d3e2e2db.png"
                            alt="image" style="width: 100px" /> <br> --}}
                        <!-- title tiket -->
                        <br>
                        <span style="font-family: Nunito;font-size: 14px;color: #595a5b;font-weight: bold;">
                            Pro Liga 2023
                        </span>
                        <br>
                        @if ($user_tiket['is_vip'])
                            <span style="font-family: Nunito; font-size: 12px; color: #595a5b">
                                VIP
                            </span>
                        @else
                            <span style="font-family: Nunito; font-size: 12px; color: #595a5b">
                                Reguler
                            </span>
                        @endif
                        <br />
                        <span style="font-family: Nunito;font-size: 12px;color: #595a5b;font-weight: bold;">
                            Kode Tiket
                        </span>
                        <br>
                        <span style="font-family: Nunito;font-size: 12px;color: #00a2b9;font-weight: bold;">
                            {{ $user_tiket->number_tiket }}
                        </span>
                    </div>

                </td>
                <td style="width: 70%">
                    <div style="float: right;">
                        <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16750701543f99364b-df12-4ffb-9d54-76307808f338.png"
                            alt="Ticket Image" style="border-radius: 14px; width: 95%" />
                </td>
            </tr>
        </table>
    </div>
</body>

</html>

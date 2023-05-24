<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>E-Ticket GJLS x PLN Mobile</title>

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
            <img src="{{ storage_path('assets/logo-tiket-gjls.png') }}" alt="image" style="width: 105px;" />
        </div>

        <div style="width: 100%; padding-top: 10px">
            <span style="font-family: Nunito; font-size: 24px; font-weight: bold">
                <p>E-Ticket GJLS x PLN Mobile</p>
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

        <div style="width: 100%; padding-top: 10px; margin-top:18px">
            <span style="font-family: Nunito; font-size: 20px;">
                <p>{{ $user_tiket->master_tiket->name }}</p>
            </span>
        </div>

        <div style="margin-top: 10px; width: auto; margin-bottom: 100px">
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
                        @php
                            \Carbon\Carbon::setLocale('id');
                            $date = tanggalDate($user_tiket->usage_date);
                            $time_start = \Carbon\Carbon::createFromFormat('H:i:s', $user_tiket->start_time_usage)->format('H:i');
                            $time_end = \Carbon\Carbon::createFromFormat('H:i:s', $user_tiket->end_time_usage)->format('H:i');
                        @endphp
                        @if ($user_tiket->start_time_usage == null && $user_tiket->end_time_usage == null)
                            {{ $date }}
                        @else
                            {{ $date . ', ' . $time_start . '-' . $time_end . ' WIB' }}
                        @endif
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
                        <img src="{{ storage_path('app/public/ticket/ticket-' . $user_tiket->number_tiket . '.png') }}"alt="image"
                            style="width: 85px" />
                        <br>
                        <span style="font-family: Nunito;font-size: 14px;color: #595a5b;font-weight: bold;">
                            GJLS x PLN Mobile - Bandung - 16 Juni 2023
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
                    <div style="float: right; margin-top:14px; margin-right: 80px">
                        <img src="{{ storage_path('assets/gambar-gjls-1.png') }}"
                            alt="Ticket Image"
                            style="border-radius: 14px; width: 120%"
                        />
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>

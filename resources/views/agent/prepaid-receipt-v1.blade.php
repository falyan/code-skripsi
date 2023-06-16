<!DOCTYPE html>
<html>

<head>
    <title>Payment Agent Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        /* Header logo center with text */
        .header {
            margin-bottom: 45px;
        }

        .merchant-info {
            text-align: center;
            font-size: 16px;
        }

        .title p {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .notes {
            text-align: center;
        }

        .info {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .footer p {
            text-align: center;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <table style="margin:auto;">
        <tr>
            <td>
                <img src={{ storage_path('assets/plnm-sellerv2.png') }} height="28px" />
            </td>
            <td>
                <p style="font-weight:bold; text-align:center; font-size:18px; margin-left:8px">PLN Agent</p>
            </td>
        </tr>
    </table>

    <div class="merchant-info" style="margin-bottom: 45px">
        <p style="font-weight: bold">{{ $merchant->name }}</p>
        <p>{{ $merchant->address }}</p>
    </div>
    {{-- table no border --}}
    <table style="width: 100%; margin-bottom: 20px; border-collapse:collapse">
        <tr>
            <td align="left" style="font-size: 14px; color:grey">Waktu Transaksi</td>
            <td align="right" style="font-size: 14px; font-weight: bold; color:grey">
                {{ \Carbon\Carbon::parse($order->order_date)->format('d-m-Y H:i:s') }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 14px; color:grey">No Transaksi</td>
            <td align="right" style="font-size: 14px; font-weight: bold; color:grey">{{ $invoice_number }}</td>
        </tr>
    </table>

    {{-- Title --}}
    <div class="title">
        <p>STRUK PEMBELIAN LISTRIK PRABAYAR</p>
    </div>

    {{-- horizontal dashed --}}
    {{-- <hr style="border: 1px dashed grey; margin-bottom: 20px"> --}}

    {{-- table no border --}}
    <table style="width: 100%; margin-bottom: 20px; border-collapse:collapse">
        <tr>
            <td align="left" style="font-size: 16px; color:grey">NO METER</td>
            <td align="right" style="font-size: 16px; color:grey">{{ $no_meter }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">IDPEL</td>
            <td align="right" style="font-size: 16px; color:grey">{{ $idpel }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">NAMA</td>
            <td align="right" style="font-size: 16px; color:grey">{{ generate_name_secret($order->customer_name) }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">TARIF/DAYA</td>
            <td align="right" style="font-size: 16px; color:grey">{{ $order->product_value }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">NO REF</td>
            <td align="right" style="font-size: 16px; color:grey">
                {{ $partner_reference }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">RP BAYAR</td>
            <td align="right" style="font-size: 16px; color:grey">
                {{ 'RP ' . number_format($rptotal + $order->payment->total_fee + $order->margin, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">METERAI</td>
            <td align="right" style="font-size: 16px; color:grey">
                {{ 'RP ' . number_format($materai, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">PPn</td>
            <td align="right" style="font-size: 16px; color:grey">
                {{ 'RP ' . number_format($rpppn, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">PPJ</td>
            <td align="right" style="font-size: 16px; color:grey">
                {{ 'RP ' . number_format($rpppj, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">ANGSURAN</td>
            <td align="right" style="font-size: 16px; color:grey">
                {{ 'RP ' . number_format($angsuran, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">RP STROOM/TOKEN</td>
            <td align="right" style="font-size: 16px; color:grey">
                {{ 'RP ' . number_format($rp_stroom, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">JML KWH</td>
            <td align="right" style="font-size: 16px; color:grey">
                {{ number_format($jml_kwh, 1, ',', '.') }}</td>
            </td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">STROOM/TOKEN</td>
            <td align="right" style="font-size: 16px; font-weight: bold; color:grey">
                {{ implode(' ', str_split($stroom_token, 4)) }}
            </td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">BIAYA ADMIN</td>
            <td align="right" style="font-size: 16px; color:grey">
                {{ 'Rp ' . number_format($order->payment->total_fee + $order->margin, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- <hr style="border: 1px dashed grey; margin-bottom: 20px"> --}}

    {{-- footer --}}
    <div class="footer" style="margin-top: 30px">
        @if ($tagline != null)
            <p>{{ $tagline }}</p>
        @endif
    </div>
</body>

</html>

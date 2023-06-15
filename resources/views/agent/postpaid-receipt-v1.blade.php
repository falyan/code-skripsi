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
        <p>STRUK PEMBAYARAN TAGIHAN LISTRIK</p>
    </div>

    {{-- horizontal dashed --}}
    {{-- <hr style="border: 1px dashed grey; margin-bottom: 20px"> --}}

    {{-- table no border --}}
    <table style="width: 100%; margin-bottom: 20px; border-collapse:collapse">
        <tr>
            <td align="left" style="font-size: 16px; color:grey">IDPEL</td>
            <td align="right" style="font-size: 16px; font-weight: bold; color:grey">{{ $idpel }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">NAMA</td>
            <td align="right" style="font-size: 16px; font-weight: bold; color:grey">{{ $order->customer_name }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">TARIF/DAYA</td>
            <td align="right" style="font-size: 16px; font-weight: bold; color:grey">{{ $order->product_value }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">BL/TH</td>
            <td align="right" style="font-size: 16px; font-weight: bold; color:grey">{{ $blth_list }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">STAND METER</td>
            <td align="right" style="font-size: 16px; font-weight: bold; color:grey">{{ $stand_meter }}</td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">RP TAG PLN</td>
            {{-- format price to RP. 000.000 --}}
            <td align="right" style="font-size: 16px; font-weight: bold; color:#00A2B9">
                {{ 'Rp ' . number_format($order->payment->amount, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td align="left" style="font-size: 16px; color:grey">NO REF</td>
            <td align="right" style="font-size: 16px; font-weight: bold; color:grey">
                {{ $partner_reference }}</td>
        </tr>
    </table>

    {{-- horizontal dashed --}}
    {{-- <hr style="border: 1px dashed grey;"> --}}

    {{-- notes --}}
    <div class="notes">
        @if ($pengesahan != null)
            <h4 style="margin-bottom: 20px">{{ $pengesahan }}</h4>
        @else
            <h4 style="margin-bottom: 20px">PLN menyatakan struk ini sebagai bukti pembayaran yang SAH</h4>
        @endif
    </div>

    {{-- horizontal dashed --}}
    {{-- <hr style="border: 1px dashed grey; margin-bottom: 20px"> --}}

    {{-- table no border --}}
    <table style="width: 100%; margin-bottom: 20px; border-collapse:collapse">
        <tr>
            <td align="left" style="font-size: 16px; color:grey">BIAYA ADMIN</td>
            <td align="right" style="font-size: 16px; font-weight: bold; color:#00A2B9">
                {{ 'Rp ' . number_format($order->payment->total_fee + $order->margin, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    {{-- horizontal dashed --}}
    {{-- <hr style="border: 1px dashed grey; margin-bottom: 20px"> --}}

    {{-- table no border --}}
    <table style="width: 100%; margin-bottom: 20px; border-collapse:collapse">
        <tr>
            <td align="left" style="font-size: 16px; color:grey">TOTAL BAYAR</td>
            <td align="right" style="font-size: 20px; font-weight: bold; color:#00A2B9">
                {{ 'Rp ' . number_format($order->payment->total_amount + $order->margin, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="info">
        <h4>{{ $info_tagihan }}</h4>
    </div>

    {{-- footer --}}
    <div class="footer" style="margin-top: 30px">
        @if ($tagline != null)
            <p>{{ $tagline }}</p>
        @endif
    </div>
</body>

</html>

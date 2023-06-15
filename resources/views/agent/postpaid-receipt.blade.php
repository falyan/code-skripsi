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

        .column {
            float: left;
            width: 50%;
            padding: 0 8px;
        }

        .row::after {
            content: "";
            display: table;
            clear: both;
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

    <h4 style="text-align:center">STRUK PEMBAYARAN TAGIHAN LISTRIK</h4>

    <div class="row">
        <div class="column">
            <p style="font-size:14px">IDPEL: {{ $idpel }}</p>
            <p style="font-size:14px">NAMA: {{ $order->customer_name }}</p>
            <p style="font-size:14px">TARIF/DAYA: {{ $order->product_value }}</p>
            <p style="font-size:14px">RP TAG PLN: {{ 'Rp ' . number_format($order->payment->amount, 0, ',', '.') }}</p>
            <p style="font-size:14px">NO REF: {{ $partner_reference }}</p>
        </div>
        <div class="column">
            <p style="font-size:14px">BL/TH: {{ $blth_list }}</p>
            <p style="font-size:14px">STAND METER: {{ $stand_meter }}</p>
        </div>
    </div>

    @if ($pengesahan != null)
        <p style="font-size:14px; text-align:center">{{ $pengesahan }}</p>
    @else
        <p style="font-size:14px; text-align:center">PLN menyatakan struk ini sebagai bukti pembayaran yang SAH.</p>
    @endif

    <div class="row">
        <div class="column">
            <p style="font-size:14px">BIAYA ADMIN:
                {{ 'Rp ' . number_format($order->payment->total_fee + $order->margin, 0, ',', '.') }}
            <p style="font-size:14px">TOTAL BAYAR:
                {{ 'Rp ' . number_format($order->payment->total_amount + $order->margin, 0, ',', '.') }}</p>
        </div>
    </div>

    <div style="text-align:center;">
        <p style="font-size:14px">{{ $info_tagihan }}</p>
        @if ($tagline != null)
            <p style="font-size:14px">{{ $tagline }}</p>
        @endif
    </div>

</body>

</html>

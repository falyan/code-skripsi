<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Produk Anda Telah Sampai ke Alamat {{$order->buyer->full_name}} </title>
</head>

<body>
    <h3>Hai, {{ $destination_name }}</h3>
    <p>
        <strong>Produkmu telah tiba di tujuan pada tanggal {{$date_arrived}} WIB.</strong><br>
    </p>
    <span>Dana akan diteruskan ke Saldo Pendapatan Iconcash kamu maksimal 3x24 jam jika pembeli tidak melakukan konfirmasi penerimaan barang.</span>
    <br><br>
    <div><strong>No. Invoice</strong> : {{$order->trx_no}}</div>
    <div><strong>Tanggal Pesanan</strong> : {{$order->order_date}} WIB</div>
    <br>
    <div><strong>Kurir</strong> : {{strtoupper($order->delivery->courier) . ' - ' . $order->delivery->shipping_type}}</div>
    <br>
    <div><strong>Tujuan Pengiriman</strong></div>
    <div><strong>{{$order->delivery->receiver_name}} ({{$order->delivery->receiver_phone}})</strong></div>
    <div>{{$order->delivery->address,}}</div>
    <div>{{$order->delivery->district_id != null ? $order->delivery->district->name : '-'}}, {{$order->delivery->city_id != null ? $order->delivery->city->name : '-'}}, {{ $order->delivery->postal_code ?? '-' }}</div>

    <br>
    <span style="color: grey;">Email ini dibuat otomatis, mohon untuk tidak membalas.</span>
</body>

</html>

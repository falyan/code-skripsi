<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Pesanan Selesai</title>
</head>

<body>
    <h3>Hai, {{ $destination_name }}</h3>
    <p>
        <strong>Transaksimu dengan {{$order->buyer->full_name}} sudah selesai.</strong><br>
    </p>
    <span>Selanjutnya dana akan diteruskan secara otomatis ke saldo pendapatan Iconcash kamu.</span>
    <br>
    <div style="margin-top: 10px"><strong>No. Invoice</strong></div> 
    <div>{{$order->trx_no}}</div>

    <div style="margin-top: 10px"><strong>Tanggal Pesanan</strong> </div>
    <div> {{$order->order_date}} WIB</div>
    
    <div style="margin-top: 10px"> <strong>Kurir</strong></div> 
    <div>{{strtoupper($order->delivery->courier) . ' - ' . $order->delivery->shipping_type}}</div>
    
    <div style="margin-top: 10px"><strong>Tujuan Pengiriman</strong></div>
    <div><strong>{{$order->delivery->receiver_name}} ({{$order->delivery->receiver_phone}})</strong></div>
    <div>{{$order->delivery->address,}}</div>
    <div>{{$order->delivery->district->name}}, {{$order->delivery->city->name}}, {{$order->delivery->postal_code}}</div>

    @php
        $total_discount_item = 0;
    @endphp
    <div style="margin-top: 10px"><strong>Produk</strong></div>
    @foreach ($order->detail as $item)
    <?php $total_discount_item += $item->total_discount?>
        <div >{{$item->product->name}}</div>
        <span>{{$item->quantity}} x Rp {{number_format($item->price, 2, ',', '.')}}</span>
        <br>
        <div style="margin-bottom: 5px">Diskon : <span style="color: red;">Rp {{$item->discount ? number_format($item->total_discount, 2, ',', '.') : 0}}</span></div>
    @endforeach

    <div style="margin-top: 10px"><strong>Ongkir</strong></div>
    <div>{{$order->delivery->delivery_fee ? 'Rp ' . number_format($order->delivery->delivery_fee, 2, ',', '.') : 'Rp 0'}}</div>

    <div style="margin-top: 10px"><strong>Diskon</strong></div>
    <div><span style="color: red;">Rp {{$total_discount_item}}</span></div>

    <br>
    <hr style="float: left; width: 50%"><br>
    <div>Total Pembayaran</div>
    <div><strong>Rp{{number_format($order->total_amount, 2, ',', '.')}}</strong></div>
    <br>
    <span style="color: grey;">Email ini dibuat otomatis, mohon untuk tidak membalas.</span>
</body>

</html>
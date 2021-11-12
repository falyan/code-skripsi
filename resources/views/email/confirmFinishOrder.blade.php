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
    <div>No. Invoice : {{$order->trx_no}}</div>
    <div>Tanggal Pesanan : {{$order->order_date}} WIB</div>
    <br>
    <div>Kurir : {{$order->delivery->courier . ' - ' . $order->delivery->shipping_type}}</div>
    <br>
    <div>Tujuan Pengiriman :</div>
    <div><strong>{{$order->delivery->receiver_name}} ({{$order->delivery->receiver_phone}})</strong></div>
    <div>{{$order->delivery->address,}}</div>
    <div>{{$order->delivery->district->name}}, {{$order->delivery->city->name}}, {{$order->delivery->postal_code}}</div>
    <br>
    
    @foreach ($order->detail as $item)
    <div style="margin-top: 10px">{{$item->product->name}}</div>
    <span>{{$item->quantity}} x Rp{{number_format($item->product->price, 2, ',', '.')}}</span>
        <br><br>
    @endforeach
    <div style="margin-top: 10px">Ongkir <br> Rp{{number_format($order->delivery->delivery_fee, 2, ',', '.')}}</div>
    <div style="margin-top: 10px">Diskon Ongkir <br> <span style="color: red;">R{{$order->delivery->delivery_discount ? 'Rp'. number_format($order->delivery->delivery_discount, 2, ',', '.') : 0}} </span></div>
    <div style="margin-top: 10px">Diskon</div>
    <div style="color: red;">Rp{{$order->total_discount ? number_format($order->total_discount, 2, ',', '.') : 0}}</div>
    <br>
    <hr style="float: left; width: 30%"><br>
    <div>Total Pembayaran</div>
    <div><strong>Rp{{number_format($order->payment->payment_amount, 2, ',', '.')}}</strong></div>
    <br>
    <span>Email ini dibuat otomatis, mohon untuk tidak membalas.</span>
</body>

</html>
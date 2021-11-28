<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Pesanan Sedang Dikirim</title>
</head>

<body>
    <h3>Hai, {{ $destination_name }}</h3>
    <p>
        <strong>Pesananmu dengan No. Invoice : {{$order->trx_no}} sedang dalam pengiriman. </strong><br>
    </p>
    <span>Berikut detail pengirimannya:</span>
    <div></div>
    <div style="margin-top: 10px"><strong>Pengirim</strong> : {{$order->merchant->name}}</div>
    
    <div style="margin-top: 5px"><strong>Kurir</strong> : {{$order->delivery->courier . ' - ' . $order->delivery->shipping_type}}</div>

    <div style="margin-top: 5px"><strong>Resi Pengiriman</strong> : {{$order->delivery->awb_number}}</div>
    <div style="margin-top: 5px"><strong>Tujuan Pengiriman</strong></div>
    <div><strong>{{$order->delivery->receiver_name}} ({{$order->delivery->receiver_phone}})</strong></div>
    <div>{{$order->delivery->address,}}</div>
    <div>{{$order->delivery->district->name}}, {{$order->delivery->city->name}}, {{$order->delivery->postal_code}}</div>
    <br>
    <span>Biaya kirim telah LUNAS. Anda tidak perlu lagi membayar biaya kirim.</span>
    <br><br>

    <span style="color: grey;">Email ini dibuat otomatis, mohon untuk tidak membalas.</span>
</body>

</html>
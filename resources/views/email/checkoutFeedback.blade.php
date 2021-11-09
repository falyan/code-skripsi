<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Menunggu pembayaran</title>
</head>

<body>
    <h3>Hai, {{ $destination_name }}</h3>
    <p>
        <strong>Tinggal sedikit lagi nih. Yuk segera selesaikan pembayaranmu sebesar Rp{{number_format($payment->payment_amount, 2, ',', '.')}} melalui PLN Mobile sebelum {{$payment->date_expired}} WIB.</strong><br>
    </p>
    <span>Berikut detail pesananmu:</span>
    @foreach ($order_detail as $item)
        <div style="margin-top: 10px">{{$item->product->name}}</div>
        <span>{{$item->quantity}} x Rp{{number_format($item->product->price, 2, ',', '.')}}</span>
        <br><br>
    @endforeach
    <div style="margin-top: 10px">Ongkir <br>     Rp{{number_format($order->delivery->delivery_fee, 2, ',', '.')}}</div>
    <div style="margin-top: 10px">Diskon</div>
    <div style="color: red;">Rp{{$order->total_discount ? number_format($order->total_discount, 2, ',', '.') : 0}}</div>
    <br>
    <hr style="float: left; width: 30%"><br>
    <div>Total Payment</div>
    <div><strong>Rp{{number_format($payment->payment_amount, 2, ',', '.')}}</strong></div>
    <br>
    <span>Email ini dibuat otomatis, mohon untuk tidak membalas.</span>
</body>

</html>
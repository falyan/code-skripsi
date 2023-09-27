<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PLN Marketplace | Approval</title>
</head>

<body>
    <div>Toko</div>
    <br>
    <div>Nama toko : {{ $nama_toko }}</div>
    <div>Status : {{ $status }}</div>
    @if ($status_code == 1)
        <br>
        <div>Akun toko anda: </div>
        <div>name : {{ $full_name }}</div>
        <div>email : {{ $email }}</div>
        @if ($password != null)
            <div>password : {{ $password }}</div>
        @endif
    @elseif($status_code = 9)
        <div>Alasan : {{ $alasan ?? '-' }}</div>
    @endif
    <br>
    <div>
        <p>Demi keamanan akun anda, segera lakukan penggantian password. Jangan menyebarkan informasi mengenai akun anda kepada siapapun.</p>
    </div>

</body>

</html>

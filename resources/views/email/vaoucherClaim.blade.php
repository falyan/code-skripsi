<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Roboto&display=swap" rel="stylesheet">
</head>

<body
    style="padding: 0; margin: 0; border: none; border-spacing: 0px; border-collapse: collapse;vertical-align: top; font-family: 'Roboto';">
    <div class="wrapper" width="600" align="center">
        <!-- header -->
        <table
            style="background-image: url('https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~163833338096dc97f2-01f0-444a-aff1-7156f4349ab3.png');
        background-repeat: no-repeat;
        background-size: contain;
        background-position: right top;
        margin-left: 10px;">
            <tr>
                <td width="600" valign="top">
                    <table>
                        <tbody>
                            <tr>
                                <td>
                                    <a
                                        style="margin: 50px 0px 50px 0px;
                                    padding-left: 20px;
                                    display: inline;
                                    position: relative;">
                                        <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16383335039869f3c3-7aea-4d2f-8527-5513b83428a3.png"
                                            alt="" style="margin-top: 50px;width: 50px;">
                                    </a>
                                </td>
                                <td>
                                    <div style="margin-top: 45px;">
                                        <h3
                                            style="display: inline;
                                        padding: 50px 0px 0px 10px;
                                        font-family: 'Poppins', sans-serif;
                                        text-align: top;
                                        position: relative;">
                                            MARKETPLACE</h3>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- subjek -->
        <table>
            <tr>
                <td width="430" valign="top">
                    <table>
                        <tbody>
                            <tr>
                                <td align="left" style="padding-left:30px;">
                                    <p style="font-size: small;font-weight: bold;">Halo {{ $destination_name }},</p>
                                    <h2 style="color: #00A2B9;margin: 0; padding: 0;line-height: 1.6;">
                                        Selamat Anda Mendapatkan Voucher!
                                    </h2>
                                    <p style="font-size: small;font-weight: bold;">
                                        Diinformasikan tanggal {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="column" width="170" valign="top">
                    <table>
                        <tbody>
                            <tr>
                                <td align="left"></td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- detail -->
        <table>
            <tr>
                <td width="600">
                    <div style="padding-left: 30px; padding-right: 30px;">

                        <p style="color: #666;font-size: 20px;margin: 50px 0 40px 0;text-align: center;">
                            Selamat anda mendapatkan voucher ubah daya!
                        </p>

                        <div
                            style="margin-bottom: 30px;
                            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
                            transition: 0.3s;
                            width: 90%;
                            border-radius: 10px;
                            background-repeat: no-repeat;
                            background-size: contain;
                            background-position: right top;
                            margin-left: 30px;">
                            <div style="padding: 20px 20px 20px 20px;">
                                <h1 style="color: #00A2B9;margin: 0; padding: 0;line-height: 1.6;text-align: center;">
                                    {{ $order->voucher_ubah_daya_code }}
                                </h1>
                            </div>
                        </div>


                        <p style="color: #666;font-size: 15px;margin: 50px 0 0px 0;text-align: center;">
                            Cek voucher anda pada voucher saya di bagian profil.
                        </p>

                        <div style="margin-bottom: 30px;">
                            <div style="font-size: 14px; color: #666;">
                                <p style="margin-bottom: 0;text-align: center;">
                                    Terima kasih telah menggunakan layanan Marketplace kami.
                                </p>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div style="padding-left: 30px; padding-right: 30px;">
                        <div
                            style="margin-bottom: 30px;
                        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
                        transition: 0.3s;
                        width: 100%;
                        border-radius: 10px;
                        background-image: url('https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16383335165a7d2a99-9146-4d52-9ea6-f987b5a7b163.png');
                        background-repeat: no-repeat;
                        background-size: contain;
                        background-position: bottom;
                        background-color: #00A2B9;">
                            <div style=" padding: 2px 5px;">
                                <table style="margin: 10px;">
                                    <tr>
                                        <td class="column" width="50" valign="top">
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td align="left">
                                                            <a style="width: 50px;padding-right: 10px;">
                                                                <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~16383334891b6d3f36-633a-42c5-8863-bdaed4ec14f4.png"
                                                                    alt="" style="width: 75px;">
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td class="column" valign="top">
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td align="left">
                                                            <h5 style="color: #fff;padding: 0;margin: 10px 0px;">
                                                                PERLU DIPERHATIKAN
                                                            </h5>
                                                            <p style="font-size: x-small;color: #fff;padding: 0;">
                                                                Jangan menyebar luaskan informasi akun Anda, dan ganti kata sandi secara berkala untuk menghindari aktivitas pencurian akun.
                                                            </p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr style="background-color: #333333;color: #fff;">
                <td>
                    <p style="text-align: center;padding-top: 30px;font-weight: bold;">Punya Pertanyaan?</p>
                    <p style="text-align: center;opacity: 80%;color: #fff">Hubungi Marketplace Help Center disini :</p>
                    <p style="text-align: center;opacity: 80%;color:#fff">
                        <a style="width: 15px;display: inline-block;padding-top: 8px;margin-right: 10px;">
                            <img src="https://api-central.air.id/plnmp-sauron-development/api/firebase/file/load/Marketplace~products~164861204852276f13-dd3f-4a57-a501-490b954cebb3.png"
                                alt="" style="width: 15px;">
                        </a>
                        <a href="mailto:support.marketplace@iconpln.co.id" style="color: #fff">
                            support.marketplace@iconpln.co.id
                        </a>
                    </p>

                    <div style="padding-left: 35px; padding-right: 35px;">
                        <hr style="border: 1px solid #00A2B9;">
                    </div>
                </td>
            </tr>
        </table>

        <!-- footer -->
        <table style="margin-top: 50px;margin-bottom: 30px;">
            <tr>
                <td class="column" width="150" valign="top">
                    <table>
                        <tbody>
                            <tr>
                                <td>
                                    <a style="margin-left: 30px;">
                                        <img src="https://api-central.air.id/plnmp-sauron-staging/api/firebase/file/load/Marketplace~merchants~1638333429c9c8964b-6bd2-49b7-b226-b7243cf54c91.png"
                                            alt="" style="width: 100px;">
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="column" width="350" valign="top">
                    <table>
                        <tbody>
                            <tr>
                                <td align="left">
                                    <p style="color: #4F4F4F;">PT. Indonesia Comnets Plus telah terdaftar dan diawasi
                                        oleh Bank Indonesia </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <p style="font-size: small;text-align: center;color: #4F4F4F;">Copyright @ 2021 PT. Indonesia Comnets Plus. Hak
            cipta dilindungi undang - undang</p>
    </div>
</body>

</html>

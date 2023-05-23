<?php

if (!function_exists('validateDate')) {
    function validateDate($date, $format = "Y-m-d")
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

if (!function_exists('toPercent')) {
    function toPercent($num)
    {
        return $num / 100;
    }
}

if (!function_exists('app_path')) {
    function app_path($path = '')
    {
        return app('path') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('tanggal')) {
    function tanggal($tgl)
    {
        $ex = explode(' ', $tgl);
        $tanggal = substr($ex[0], 8, 2);
        $bulan = bulan(substr($ex[0], 5, 2));
        $tahun = substr($ex[0], 0, 4);
        return $tanggal . ' ' . $bulan . ' ' . $tahun . ', ' . $ex[1];
    }
}

if (!function_exists('tanggalDate')) {
    function tanggalDate($tgl)
    {
        $ex = explode(' ', $tgl);
        $tanggal = substr($ex[0], 8, 2);
        $bulan = bulan(substr($ex[0], 5, 2));
        $tahun = substr($ex[0], 0, 4);
        return nama_hari($tgl) . ', ' . $tanggal . ' ' . $bulan . ' ' . $tahun;
    }
}

if (!function_exists('bulan')) {
    function bulan($bln)
    {
        switch ($bln) {
            case 1:
                return "Januari";
                break;
            case 2:
                return "Februari";
                break;
            case 3:
                return "Maret";
                break;
            case 4:
                return "April";
                break;
            case 5:
                return "Mei";
                break;
            case 6:
                return "Juni";
                break;
            case 7:
                return "Juli";
                break;
            case 8:
                return "Agustus";
                break;
            case 9:
                return "September";
                break;
            case 10:
                return "Oktober";
                break;
            case 11:
                return "November";
                break;
            case 12:
                return "Desember";
                break;
        }
    }
}

if (!function_exists('nama_hari')) {
    function nama_hari($tanggal)
    {
        $ubah = gmdate($tanggal, time() + 60 * 60 * 8);
        $pecah = explode("-", $ubah);
        $tgl = $pecah[2];
        $bln = $pecah[1];
        $thn = $pecah[0];

        $nama = date("l", mktime(0, 0, 0, $bln, $tgl, $thn));
        $nama_hari = "";
        if ($nama == "Sunday") {
            $nama_hari = "Minggu";
        } else if ($nama == "Monday") {
            $nama_hari = "Senin";
        } else if ($nama == "Tuesday") {
            $nama_hari = "Selasa";
        } else if ($nama == "Wednesday") {
            $nama_hari = "Rabu";
        } else if ($nama == "Thursday") {
            $nama_hari = "Kamis";
        } else if ($nama == "Friday") {
            $nama_hari = "Jumat";
        } else if ($nama == "Saturday") {
            $nama_hari = "Sabtu";
        }
        return $nama_hari;
    }
}

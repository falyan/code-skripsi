<?php

namespace App\Imports;

use App\Models\UbahDayaPregenerate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VoucherImport implements ToCollection, WithHeadingRow
{

    protected $voucherId;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function __construct($voucherId)
    {
        $this->voucherId = $voucherId;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $key => $row)
        {
            $pregenerated[] = [
                'master_ubah_daya_id' => $this->voucherId,
                'kode' => $row['kode'],
                'created_at' => Carbon::now(),
            ];
        }

        UbahDayaPregenerate::insert($pregenerated);
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromArray, WithHeadings, WithTitle, WithEvents, WithCustomStartCell, WithColumnWidths, WithStyles
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Nomor Invoice',
            'Tanggal Pesanan',
            'Nilai Transaksi',
            'Total Pendapatan',
            'Status Pesanan',
            'Nomor Resi',
            'Penerima',
            'Nomor HP Penerima',
            'Alamat Lengkap',
            'Kota',
            'Kurir',
            'Tanggal Update',
        ];
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 30,
            'C' => 25,
            'D' => 25,
            'E' => 20,
            'F' => 20,
            'G' => 25,
            'H' => 20,
            'I' => 25,
            'J' => 20,
            'K' => 20,
            'L' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // $event->sheet->getStyle('A2:F2')->applyFromArray([
                //     'font' => ['bold' => true],
                // ]);

                //append "Rp " to all cells in column D
                $event->sheet->getDelegate()->getStyle('C2:C' . $event->sheet->getDelegate()->getHighestRow())
                    ->getNumberFormat()
                    ->setFormatCode('Rp #,##0');

                $event->sheet->getDelegate()->getStyle('D2:D' . $event->sheet->getDelegate()->getHighestRow())
                    ->getNumberFormat()
                    ->setFormatCode('Rp #,##0');

                // in column H, text change to number
                $event->sheet->getDelegate()->getStyle('H2:H' . $event->sheet->getDelegate()->getHighestRow())
                    ->getNumberFormat()
                    ->setFormatCode('0');

                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Transaction';
    }
}

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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromArray, WithHeadings, WithTitle, WithEvents, WithCustomStartCell, WithColumnWidths, WithStyles
{
    use Exportable;

    public function __construct($data)
    {
        $this->data = $data;
    }

    function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Invoice',
            'Nama Pembeli',
            'Tanggal Order',
            'Total Harga',
            'Total Berat',
            'Metode Pembayaran',
            'Status',
            'Related Pln Mobile Customer ID',
            'Dibuat Oleh',
            'Diupdate Oleh',
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
            'C' => 15,
            'D' => 15,
            'E' => 10,
            'F' => 20,
            'G' => 20,
            'H' => 20,
            'I' => 20,
            'J' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A2:F2')->applyFromArray([
                    'font' => ['bold' => true],
                ]);
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

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayloadEXOneHundredandFifteenExport implements FromCollection, WithEvents, WithHeadings, WithStyles
{
    protected $date;
    protected $shift;

    public function __construct($tanggalInput, $shiftInput)
    {
        $this->date = Carbon::parse($tanggalInput)->format('Y-m-d');

        $this->shift = match ($shiftInput) {
            'Siang' => '6',
            'Malam' => '7',
            default => '', // ALL
        };
    }

    public function collection()
    {
        // Ambil data dari SQL
        $data = DB::select(
            'SET NOCOUNT ON; EXEC DAILY.dbo.GET_PAYLOAD_2023_2024_EX_ONEHUNDRENANDFIFTEEN @Date = ?, @Shift = ?',
            [$this->date, $this->shift]
        );

        // Tentukan jam range
        $jamRange = match ($this->shift) {
            '6' => range(7, 18),
            '7' => array_merge(range(19, 23), range(0, 6)),
            default => array_merge(range(7, 23), range(0, 6)),
        };

        $jamDefault = collect($jamRange)->mapWithKeys(fn($h) => [$h => 0])->toArray();

        // Filter >115 ton dan group
        $data = collect($data)
            ->filter(fn($item) => $item->RIT_TONNAGE > 115)
            ->groupBy(fn($item) => $item->LOD_LOADERID.'-'.$item->PERSONALNAME.'-'.$item->OPR_SHIFTNO)
            ->map(function ($group) use ($jamRange, $jamDefault) {
                $first = $group->first();
                $jam   = $jamDefault;

                foreach ($group as $item) {
                    $hour = Carbon::parse($item->OPR_REPORTTIME)->hour;
                    if (in_array($hour, $jamRange)) {
                        $jam[$hour]++;
                    }
                }

                return [
                    'Loader'   => $first->LOD_LOADERID,
                    'Operator' => $first->PERSONALNAME,
                    'Shift'    => match ($first->OPR_SHIFTNO) {
                        '6' => 'Siang',
                        '7' => 'Malam',
                        default => 'Tidak diketahui',
                    },
                    'Jam' => $jam,
                ];
            })
            ->sortBy('Loader')
            ->values();

        // Flatten
        return $data->map(fn($row) => array_merge(
            [$row['Loader'], $row['Operator'], $row['Shift']],
            array_values($row['Jam'])
        ));
    }

    public function headings(): array
    {
        $jamRange = match ($this->shift) {
            '6' => range(7, 18),
            '7' => array_merge(range(19, 23), range(0, 6)),
            default => array_merge(range(7, 23), range(0, 6)),
        };

        return array_merge(['Loader', 'Operator', 'Shift', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '0', '1', '2', '3', '4', '5', '6']);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Default lebar kolom
                foreach (range('A', 'AA') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(15);
                }

                // Khusus kolom B autosize
                $sheet->getColumnDimension('B')->setAutoSize(true);

                // Tentukan range data (dari A1 sampai kolom terakhir + baris terakhir)
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $range = "A1:{$highestColumn}{$highestRow}";

                 // Rata tengah untuk kolom D sampai O
                $sheet->getStyle('D:AA')->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Tambahkan border dotted
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'A1:AA1' => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D8E4BC'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

}

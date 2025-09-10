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
    protected $startDate;
    protected $endDate;
    protected $shift;

    public function __construct($tanggalInput, $shift)
    {
        if ($tanggalInput) {
            if (str_contains($tanggalInput, 's/d')) {
                [$start, $end] = array_map('trim', explode('s/d', $tanggalInput));
                $this->startDate = Carbon::parse($start)->format('Y-m-d');
                $this->endDate   = Carbon::parse($end)->format('Y-m-d');
            } else {
                $this->startDate = Carbon::parse(trim($tanggalInput))->format('Y-m-d');
                $this->endDate   = $this->startDate;
            }
        } else {
            $today = Carbon::now()->format('Y-m-d');
            $this->startDate = $today;
            $this->endDate   = $today;
        }

        $this->shift = match ($shift) {
            'Siang' => '6',
            'Malam' => '7',
            'ALL', null => '',
            default => '',
        };
    }

    public function collection()
    {
        $data = DB::select(
            'SET NOCOUNT ON;EXEC DAILY.dbo.GET_PAYLOAD_2023_2024_EX_ONEHUNDRENANDFIFTEEN @StartDate = ?, @endDate = ?, @Shift = ?',
            [$this->startDate, $this->endDate, $this->shift]
        );

        // Filter > 115 ton dan group
        $data = collect($data)
            ->filter(fn($item) => $item->RIT_TONNAGE > 115)
            ->groupBy(fn($item) => $item->LOD_LOADERID.'-'.$item->PERSONALNAME.'-'.$item->OPR_SHIFTNO)
            ->map(function ($group) {
                $first = $group->first();

                // Siapkan slot jam 07-18
                $jam = collect(range(7, 18))->mapWithKeys(fn($h) => [$h => 0])->toArray();

                foreach ($group as $item) {
                    $hour = Carbon::parse($item->OPR_REPORTTIME)->hour;
                    if ($hour >= 7 && $hour <= 18) {
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
                    'Jam'      => $jam,
                ];
            })
            ->sortBy('Loader')
            ->values();

        // Flatten untuk Excel
        return $data->map(function ($row) {
            return array_merge([
                $row['Loader'],
                $row['Operator'],
                $row['Shift'],
            ], array_values($row['Jam']));
        });
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Default lebar kolom
                foreach (range('A', 'O') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(15);
                }

                // Khusus kolom B autosize
                $sheet->getColumnDimension('B')->setAutoSize(true);

                // Tentukan range data (dari A1 sampai kolom terakhir + baris terakhir)
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $range = "A1:{$highestColumn}{$highestRow}";

                 // Rata tengah untuk kolom D sampai O
                $sheet->getStyle('D:O')->applyFromArray([
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
            'A1:O1' => [
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

    public function headings(): array
    {
        $jamHeaders = [];
        foreach (range(7, 18) as $h) {
            $jamHeaders[] = $h;
        }

        return array_merge(
            ['Loader', 'Operator', 'Shift'],
            $jamHeaders
        );
    }
}

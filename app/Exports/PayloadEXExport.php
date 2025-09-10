<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithStyles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayloadEXExport implements FromCollection, WithEvents, WithHeadings, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $startDate;
    protected $endDate;
    protected $shift;
    protected $ex;

    public function __construct($tanggalInput, $shiftInput, $exInput)
    {
        // Default hari ini
        $startDate = Carbon::now()->toDateString();
        $endDate   = $startDate;

        // Cek input tanggal
        if ($tanggalInput) {
            if (str_contains($tanggalInput, 's/d')) {
                [$startDate, $endDate] = array_map('trim', explode('s/d', $tanggalInput));
                $startDate = Carbon::parse($startDate)->format('Y-m-d');
                $endDate   = Carbon::parse($endDate)->format('Y-m-d');
            } else {
                $startDate = Carbon::parse(trim($tanggalInput))->format('Y-m-d');
                $endDate   = $startDate;
            }
        }

        // Cek input shift
        $shift = match ($shiftInput) {
            'Siang' => '6',
            'Malam' => '7',
            'ALL', null => '',
            default => '',
        };

        // Cek input EX
        $ex = '';
        if (is_string($exInput)) {
            $decoded = json_decode($exInput, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $exArray = array_column($decoded, 'value');
                $ex = in_array('ALL', $exArray) ? '' : implode(',', $exArray);
            } else {
                $ex = ($exInput === 'ALL') ? '' : $exInput;
            }
        } elseif (is_array($exInput)) {
            $ex = in_array('ALL', $exInput) ? '' : implode(',', $exInput);
        }

        // Assign ke properti class
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->shift     = $shift;
        $this->ex        = $ex;
    }


    public function collection()
    {
        $data = DB::select('SET NOCOUNT ON;EXEC DAILY.dbo.GET_PAYLOAD_2023_2024_EX @StartDate = ?, @endDate = ?, @EX_IDs = ?, @Shift = ?', [
            $this->startDate,
            $this->endDate,
            $this->ex,
            $this->shift
        ]);

        return collect($data)->map(function ($item) {
            return [
                $item->VHC_ID,
                $item->LOD_LOADERID,
                $item->OPR_NRP,
                $item->PERSONALNAME,
                $item->OPR_SHIFTNO => match ($item->OPR_SHIFTNO) {
                        '6' => 'Siang',
                        '7' => 'Malam',
                        default => 'Belum login',
                    },
                $item->OPR_REPORTTIME ? Carbon::parse($item->OPR_REPORTTIME)->format('Y-m-d H:i') : '-',
                $item->LOGIN_TIME     ? Carbon::parse($item->LOGIN_TIME)->format('Y-m-d H:i')     : '-',
                $item->LOGOUT_TIME    ? Carbon::parse($item->LOGOUT_TIME)->format('Y-m-d H:i')    : '-',
                number_format((float) $item->RIT_TONNAGE, 1),
                $item->TONNAGE_CATEGORY =>  match ($item->TONNAGE_CATEGORY) {
                    'LESS_THAN_85'                  => 'Kurang dari 85',
                    'LESS_THAN_100'                 => 'Kurang dari 100',
                    'BETWEEN_100_AND_115'           => 'Antara 100 dan 115',
                    'GREATER_THAN_115'              => 'Lebih dari 115',
                    'GREATER_THAN_OR_EQUAL_120'     => 'Lebih dari 120',
                    default                         => 'Tidak diketahui',
                }
            ];
        });
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Ambil delegatenya
                $sheet = $event->sheet->getDelegate();

                // --- lebar kolom ---
                $sheet->getColumnDimension('A')->setWidth(15);
                // Kolom B autosize sesuai permintaan
                $sheet->getColumnDimension('B')->setAutoSize(true);

                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(20);
                $sheet->getColumnDimension('I')->setWidth(20);
                $sheet->getColumnDimension('J')->setWidth(50);

                // --- tentukan range data (A1 sampai kolom terakhir + baris terakhir) ---
                $highestRow = $sheet->getHighestRow();         // mis. "10"
                $highestColumn = $sheet->getHighestColumn();   // mis. "J"

                // fallback jika sheet kosong
                if (!$highestRow) {
                    $highestRow = 1;
                }
                if (!$highestColumn) {
                    $highestColumn = 'J';
                }

                $range = "A1:{$highestColumn}{$highestRow}";

                // --- terapkan border dotted ke seluruh range ---
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                            // gunakan ARGb 8-digit (include alpha)
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Opsional: tebalkan header dan beri bottom border solid untuk pembeda
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);
            },
        ];
    }


    public function styles(Worksheet $sheet)
    {
        return [
            // Gaya untuk header multilevel
            'A1:J1' => [
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'D8E4BC', // Warna kuning
                    ],
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
        return [
            'Vehicle',
            'Loader',
            'NRP Loader',
            'Name Loader',
            'Shift',
            'Report Time',
            'Login Time',
            'Logout Time',
            'Ritation Tonnage',
            'Ritation Category',
        ];
    }
}

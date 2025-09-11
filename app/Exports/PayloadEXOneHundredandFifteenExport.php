<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayloadEXOneHundredandFifteenExport implements FromCollection, WithEvents, WithStyles
{
    protected $date;
    protected $shift;

    protected $jamRange;
    protected $jamRangeFormatted;

    public function __construct($tanggalInput, $shiftInput)
    {
        $this->date = Carbon::parse($tanggalInput)->format('Y-m-d');
        $this->shift = match ($shiftInput) {
            'Siang' => '6',
            'Malam' => '7',
            default => '', // jangan kosong, pakai string ALL
        };

        if ($this->shift == '6') {
            // 07:00 - 18:00
            $this->jamRange = range(7, 18);
            $this->jamRangeFormatted = array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT), $this->jamRange);

        } elseif ($this->shift == '7') {
            // 19:00 - 23:00, lalu 00:00 - 06:00
            $this->jamRange = array_merge(range(19, 23), range(0, 6));
            $this->jamRangeFormatted = array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT), $this->jamRange);

        } else {
            // ALL → pisah siang & malam
            $this->jamRange = [
                'Siang' => range(7, 18),
                'Malam' => array_merge(range(19, 23), range(0, 6)),
            ];
            $this->jamRangeFormatted = [
                'Siang' => array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT), $this->jamRange['Siang']),
                'Malam' => array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT), $this->jamRange['Malam']),
            ];
        }
    }
    public function collection()
    {
        // ambil data dari stored proc
        $data = DB::select(
            'SET NOCOUNT ON; EXEC DAILY.dbo.GET_PAYLOAD_2023_2024_EX_ONEHUNDRENANDFIFTEEN @Date = ?, @Shift = ?',
            [$this->date, $this->shift]
        );

        // --- normalisasi shift menjadi key yang mudah dipakai ---
        // possible values: 'Siang', 'Malam', '' (ALL)
        if ($this->shift === '6' || $this->shift === 6 || $this->shift === 'Siang') {
            $shiftKey = 'Siang';
        } elseif ($this->shift === '7' || $this->shift === 7 || $this->shift === 'Malam') {
            $shiftKey = 'Malam';
        } else {
            $shiftKey = ''; // ALL
        }

        // --- siapkan jam range & formatted untuk tiap mode secara aman ---
        if ($shiftKey === '') {
            // ALL mode: pastikan ada dua array Siang & Malam
            $jamRangeSiang = $this->jamRange['Siang'] ?? range(7, 18);
            $jamRangeMalam = $this->jamRange['Malam'] ?? array_merge(range(19, 23), range(0, 6));
            $jamFormattedSiang = $this->jamRangeFormatted['Siang'] ?? array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT), $jamRangeSiang);
            $jamFormattedMalam = $this->jamRangeFormatted['Malam'] ?? array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT), $jamRangeMalam);
        } else {
            // Single shift mode: jamRange mungkin disimpan sebagai assoc['Siang']||['Malam'] atau langsung array flat
            if (is_array($this->jamRange) && isset($this->jamRange[$shiftKey]) && is_array($this->jamRange[$shiftKey])) {
                $jamRangeForShift = $this->jamRange[$shiftKey];
            } elseif (is_array($this->jamRange) && !isset($this->jamRange[$shiftKey])) {
                // constructor mungkin menyimpan jamRange langsung sebagai flat array
                $jamRangeForShift = $this->jamRange;
            } else {
                // fallback
                $jamRangeForShift = ($shiftKey === 'Siang') ? range(7, 18) : array_merge(range(19, 23), range(0, 6));
            }

            $jamFormattedForShift = (is_array($this->jamRangeFormatted) && isset($this->jamRangeFormatted[$shiftKey]) && is_array($this->jamRangeFormatted[$shiftKey]))
                ? $this->jamRangeFormatted[$shiftKey]
                : array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT), $jamRangeForShift);
        }

        // --- build default counters (jamDefault) ---
        if ($shiftKey === '') {
            $jamDefaultSiang = array_fill_keys($jamRangeSiang, 0);
            $jamDefaultMalam = array_fill_keys($jamRangeMalam, 0);
            $jamDefault = ['Siang' => $jamDefaultSiang, 'Malam' => $jamDefaultMalam];
        } else {
            $jamDefault = array_fill_keys($jamRangeForShift, 0);
        }

        // --- proses data: filter, group, dan hitung per jam ---
        $processed = collect($data)
            ->filter(fn($item) => $item->RIT_TONNAGE > 115)
            ->groupBy(fn($item) => $item->LOD_LOADERID . '-' . $item->PERSONALNAME . '-' . $item->OPR_SHIFTNO)
            ->map(function ($group) use ($jamDefault, $shiftKey) {
                $first = $group->first();

                // pilih jam counter sesuai shift row
                if ($shiftKey === '') {
                    $jam = ($first->OPR_SHIFTNO == '6') ? $jamDefault['Siang'] : $jamDefault['Malam'];
                } else {
                    // jamDefault adalah flat array untuk single shift
                    $jam = $jamDefault;
                }

                foreach ($group as $item) {
                    $hour = (int) Carbon::parse($item->OPR_REPORTTIME)->hour;
                    if (array_key_exists($hour, $jam)) {
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

        // --------------------------
        // jika single shift (Siang atau Malam)
        // --------------------------
        if ($shiftKey !== '') {
            $header = array_merge(['Loader', 'Operator', 'Shift'], $jamFormattedForShift);

            $rows = $processed->map(fn($row) => array_merge(
                [$row['Loader'], $row['Operator'], $row['Shift']],
                array_map(fn($h) => $row['Jam'][$h] ?? 0, $jamRangeForShift)
            ));

            return collect([$header])->concat($rows)->values();
        }

        // --------------------------
        // jika ALL: buat section Siang + kosong + Malam
        // --------------------------
        $result = collect();

        // Siang section
        $siangRows = $processed->where('Shift', 'Siang')->map(fn($row) => array_merge(
            [$row['Loader'], $row['Operator'], $row['Shift']],
            array_map(fn($h) => $row['Jam'][$h] ?? 0, $jamRangeSiang)
        ));
        if ($siangRows->count()) {
            $result->push(['Shift Siang']);
            $result->push(array_merge(['Loader', 'Operator', 'Shift'], $jamFormattedSiang));
            $result = $result->concat($siangRows);
            $result->push([]); // baris kosong pemisah
        }

        // Malam section
        $malamRows = $processed->where('Shift', 'Malam')->map(fn($row) => array_merge(
            [$row['Loader'], $row['Operator'], $row['Shift']],
            array_map(fn($h) => $row['Jam'][$h] ?? 0, $jamRangeMalam)
        ));
        if ($malamRows->count()) {
            $result->push(['Shift Malam']);
            $result->push(array_merge(['Loader', 'Operator', 'Shift'], $jamFormattedMalam));
            $result = $result->concat($malamRows);
        }

        return $result->values();
    }

    public function headings(): array
    {
        if ($this->shift === '') {
            // ALL → hanya header dasar, sisanya ditangani di registerEvents
            return ['Loader', 'Operator', 'Shift'];
        }

        // Jika pilih shift tertentu
        return array_merge(['Loader', 'Operator', 'Shift'], $this->jamRangeFormatted[$this->shift]);
    }

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function(BeforeExport $event) {
                $event->writer->getProperties()
                    ->setCreator('Ahmad Fadillah')      // Author
                    ->setLastModifiedBy('Ahmad Fadillah')
                    ->setTitle('Laporan Payload Loader') // Title
                    ->setDescription('Export data shift siang & malam')
                    ->setSubject('Payload Report')
                    ->setKeywords('Excel export, Payload, Loader')
                    ->setCategory('Laporan');
            },

            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set kolom lebar
                foreach (range('A', 'O') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(15);
                }
                $sheet->getColumnDimension('B')->setAutoSize(true);

                // Jika ALL, tambahkan header Shift Siang & Malam
                if ($this->shift === '') {
                    $highestColumn = $sheet->getHighestColumn();

                    // Cari pemisah kosong (antara siang dan malam)
                    $rows = $sheet->toArray();
                    foreach ($rows as $i => $row) {
                        if ($row[0] === null && $row[1] === null) {
                            $rowIndex = $i + 3; // +2 karena insert, +1 offset
                            $sheet->insertNewRowBefore($rowIndex, 1);
                            $sheet->setCellValue("A{$rowIndex}", 'Shift Malam');
                            $sheet->mergeCells("A{$rowIndex}:{$highestColumn}{$rowIndex}");
                            $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true);
                            $sheet->getStyle("A{$rowIndex}")->getAlignment()->setHorizontal(
                                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                            );
                            break;
                        }
                    }
                }

                // Style jam kolom (center)
                $sheet->getStyle('D:O')->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Border dotted
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $range = "A1:{$highestColumn}{$highestRow}";
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
        $styles = [];

        if ($this->shift === '') {
            // Jika ALL → header siang di baris 2, header malam di baris pemisah, plus heading kolom
            $styles['A2:O2'] = [
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
            ];

            // Cari baris "Shift Malam"
            $rows = $sheet->toArray();
            foreach ($rows as $i => $row) {
                if (isset($row[0]) && $row[0] === 'Shift Malam') {
                    $rowIndex = $i + 2; // array to excel row index
                    $styles["A{$rowIndex}:O{$rowIndex}"] = [
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
                    ];
                    break;
                }
            }
        } else {
            // Jika shift tertentu → header hanya di baris 1
            $styles['A1:O1'] = [
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
            ];
        }

        return $styles;
    }


}

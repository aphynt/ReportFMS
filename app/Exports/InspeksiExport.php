<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class InspeksiExport implements
    FromCollection,
    WithEvents,
    WithHeadings,
    WithStyles,
    WithCustomStartCell
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;

        set_time_limit(0);
        ini_set('max_execution_time', 0);
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        $no = 1;

        return $this->data->map(function ($item) use (&$no) {
            return [
                $no++,
                $item->created_at
                    ? Carbon::parse($item->created_at)->format('d-M-y')
                    : null,
                $item->area,
                $item->pic,
                $item->temuan,
                $item->file_temuan,
                $item->tingkat_risiko,
                $item->tindak_lanjut,
                $item->created_at
                    ? Carbon::parse($item->created_at)->addDays(7)->format('d-M-y')
                    : null,
                'Produksi',
                $item->is_finish === 'Close' && $item->updated_at
                    ? Carbon::parse($item->updated_at)->format('d-M-y')
                    : null,
                $item->file_tindakLanjut,
                $item->is_finish,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Tgl. Inspeksi',
            'Lokasi',
            'Inspector',
            'Uraian Temuan',
            'Dokumentasi Temuan',
            'Tingkat Risiko',
            'Rekomendasi Tindak Lanjut',
            'Due Date',
            'PIC',
            'Tgl. Perbaikan',
            'Dokumentasi Tindakan Perbaikan',
            'Status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();
                $logo = public_path('dashboard/assets/images/logo/logo sims.png');

                if (file_exists($logo)) {
                    $d2 = new Drawing();
                    $d2->setName('Logo2');
                    $d2->setDescription('Logo Perusahaan 2');
                    $d2->setPath($logo);
                    // $d2->setHeight(50);
                    $d2->setCoordinates('A1');
                    $d2->setOffsetX(0);
                    $d2->setWorksheet($sheet);
                }

                $sheet->mergeCells('A2:M2');
                $sheet->setCellValue('A2', 'PICA INSPEKSI KESELAMATAN PERTAMBANGAN');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 24,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->setCellValue('M1', 'FM-SE-82/05/24/09/25');
                $sheet->getStyle('M1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 9,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                    ],
                ]);

                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(35);
                $sheet->getColumnDimension('F')->setWidth(35);
                $sheet->getColumnDimension('G')->setWidth(14);
                $sheet->getColumnDimension('H')->setWidth(35);
                $sheet->getColumnDimension('I')->setWidth(14);
                $sheet->getColumnDimension('J')->setWidth(18);
                $sheet->getColumnDimension('K')->setWidth(14);
                $sheet->getColumnDimension('L')->setWidth(35);
                $sheet->getColumnDimension('M')->setWidth(10);

                $highestRow    = $sheet->getHighestRow() ?: 4;
                $highestColumn = $sheet->getHighestColumn() ?: 'M';
                $range         = "A4:{$highestColumn}{$highestRow}";

                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color'       => ['argb' => 'FF000000'],
                        ],
                    ],
                    'alignment' => [
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);

                $startDataRow = 5;
                $rowIndex     = 0;

                foreach ($this->data as $item) {
                    $row = $startDataRow + $rowIndex;

                    $sheet->getRowDimension($row)->setRowHeight(80);

                    if (!empty($item->file_temuan)) {
                        $this->addImageFromUrl(
                            $sheet,
                            $item->file_temuan,
                            'F'.$row,
                            'Dok Temuan '.$rowIndex
                        );
                        $sheet->setCellValue('F'.$row, '');
                    }

                    if (!empty($item->file_tindakLanjut)) {
                        $this->addImageFromUrl(
                            $sheet,
                            $item->file_tindakLanjut,
                            'L'.$row,
                            'Dok Perbaikan '.$rowIndex
                        );
                        $sheet->setCellValue('L'.$row, '');
                    }

                    $rowIndex++;
                }

            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'A4:M4' => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'fill' => [
                    'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor'=> ['rgb' => 'D8E4BC'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color'       => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    protected function addImageFromUrl(
        Worksheet $sheet,
        string $url,
        string $cell,
        string $name
    ): void {
        try {
            // Folder temp khusus export
            $tempDir = storage_path('app/temp-export-images');

            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Nama file unik
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $tempFile  = $tempDir.'/'.uniqid('img_').'.'.$extension;

            // Download image (TANPA load ke memory besar)
            $stream = fopen($url, 'r');
            if (!$stream) {
                return;
            }

            file_put_contents($tempFile, $stream);
            fclose($stream);

            if (!file_exists($tempFile)) {
                return;
            }

            // Pakai FILE BASED DRAWING (HEMAT RAM)
            $drawing = new Drawing();
            $drawing->setName($name);
            $drawing->setDescription($name);
            $drawing->setPath($tempFile);
            $drawing->setHeight(80);
            $drawing->setCoordinates($cell);
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);

        } catch (\Throwable $e) {
            // optional log
            // \Log::error($e->getMessage());
        }
    }


}

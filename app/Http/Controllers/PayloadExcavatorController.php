<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\PayloadEXExport;
use App\Exports\PayloadEXOneHundredandFifteenExport;
use Maatwebsite\Excel\Facades\Excel;

class PayloadExcavatorController extends Controller
{
    //
    public function summary()
    {
        $ex = Unit::where('VHC_ID', 'like', 'EX%')->get();
        return view('payload.ex.index', compact('ex'));
    }

    public function apiSummary(Request $request)
    {
        $tanggalInput = $request->input('tanggal');

        $startDate = Carbon::now()->toDateString();
        $endDate = $startDate;

        if ($tanggalInput) {
            if (str_contains($tanggalInput, 's/d')) {
                [$startDate, $endDate] = array_map('trim', explode('s/d', $tanggalInput));
            } else {
                $startDate = trim($tanggalInput);
                $endDate = $startDate;
            }
        }

        $shiftInput = $request->input('shift');
        $shift = match ($shiftInput) {
            'Siang' => '6',
            'Malam' => '7',
            'ALL', null => '',
            default => '',
        };

        $exInput = $request->input('ex');
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

        try {
            $data = DB::select('SET NOCOUNT ON;EXEC DAILY.dbo.GET_PAYLOAD_2023_2024_EX @StartDate = ?, @endDate = ?, @EX_IDs = ?, @Shift = ?',
                [$startDate, $endDate, $ex, $shift]
            );

            $data = collect($data)->map(function ($item) {
                return [
                    'VHC_ID'           => $item->VHC_ID,
                    'LOD_LOADERID'     => $item->LOD_LOADERID,
                    'OPR_NRP'          => $item->OPR_NRP,
                    'PERSONALNAME'     => $item->PERSONALNAME,
                    'OPR_SHIFTNO'      => match ($item->OPR_SHIFTNO) {
                        '6' => 'Siang',
                        '7' => 'Malam',
                        default => 'Tidak diketahui',
                    },
                    'OPR_REPORTTIME'   => $item->OPR_REPORTTIME ? Carbon::parse($item->OPR_REPORTTIME)->format('H:i d-m-Y') : '-',
                    'LOGIN_TIME'   => $item->LOGIN_TIME ? Carbon::parse($item->LOGIN_TIME)->format('H:i d-m-Y') : '-',
                    'LOGOUT_TIME'   => $item->LOGOUT_TIME ? Carbon::parse($item->LOGOUT_TIME)->format('H:i d-m-Y') : '-',
                    'RIT_TONNAGE'      => number_format((float) $item->RIT_TONNAGE, 1),
                    'TONNAGE_CATEGORY' => match ($item->TONNAGE_CATEGORY) {
                        'LESS_THAN_85'               => 'Kurang dari 85',
                        'LESS_THAN_100'              => 'Kurang dari 100',
                        'BETWEEN_100_AND_115'        => 'Antara 100 dan 115',
                        'GREATER_THAN_115'           => 'Lebih dari 115',
                        'GREATER_THAN_OR_EQUAL_120' => 'Lebih dari 120',
                        default                      => 'Tidak diketahui',
                    }
                ];
            });

            return response()->json([
                'data' => $data,
                'status' => 'Success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
                'status' => 'Error',
                'message' => 'Terjadi kesalahan saat mengambil data.',
            ], 500);
        }
    }

    public function summaryExportExcel(Request $request)
    {
        $tanggalInput = $request->input('tanggal');

        $startDate = Carbon::now()->toDateString();
        $endDate = $startDate;

        if ($tanggalInput) {
            if (str_contains($tanggalInput, 'to')) {
                [$startDate, $endDate] = array_map('trim', explode('to', $tanggalInput));
            } else {
                $startDate = trim($tanggalInput);
                $endDate = $startDate;
            }
        }

        $shiftInput = $request->input('shift');
        $shift = match ($shiftInput) {
            'Siang' => '6',
            'Malam' => '7',
            'ALL', null => '',
            default => '',
        };

        $exInput = $request->input('ex');
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

        return Excel::download(new PayloadEXExport($startDate, $endDate, $ex, $shift), 'Payload per Excavator.xlsx');
    }

    public function oneHundredandFifteen()
    {
        $ex = Unit::where('VHC_ID', 'like', 'EX%')->get();
        return view('payload.ex.oneHundredandFifteen', compact('ex'));
    }

    public function apiOneHundredandFifteen(Request $request)
    {
        $tanggalInput = $request->input('tanggal');

        $startDate = Carbon::now()->toDateString();
        $endDate = $startDate;

        if ($tanggalInput) {
            if (str_contains($tanggalInput, 's/d')) {
                [$startDate, $endDate] = array_map('trim', explode('s/d', $tanggalInput));
            } else {
                $startDate = trim($tanggalInput);
                $endDate = $startDate;
            }
        }

        $shiftInput = $request->input('shift');
        $shift = match ($shiftInput) {
            'Siang' => '6',
            'Malam' => '7',
            'ALL', null => '',
            default => '',
        };


        try {
            $data = DB::select('SET NOCOUNT ON;EXEC DAILY.dbo.GET_PAYLOAD_2023_2024_EX_ONEHUNDRENANDFIFTEEN @StartDate = ?, @endDate = ?, @Shift = ?',
                [$startDate, $endDate, $shift]
            );

            $data = collect($data)
                ->filter(fn($item) => $item->RIT_TONNAGE > 115)
                ->groupBy(fn($item) => $item->LOD_LOADERID . '-' . $item->PERSONALNAME . '-' . $item->OPR_SHIFTNO)
                ->map(function ($group) {
                    $first = $group->first();

                    // siapkan jam 7-18
                    $jam = collect(range(7, 18))->mapWithKeys(fn($h) => [$h => ['count' => 0, 'details' => []]])->toArray();

                    foreach ($group as $item) {
                        $hour = Carbon::parse($item->OPR_REPORTTIME)->hour;
                        if ($hour >= 7 && $hour <= 18) {
                            $jam[$hour]['count']++;
                            $jam[$hour]['details'][] = [
                                'HD'   => $item->VHC_ID, // unit HD
                                'Tonnage' => $item->RIT_TONNAGE,
                                'Shift'    => match ($first->OPR_SHIFTNO) {
                                    '6' => 'Siang',
                                    '7' => 'Malam',
                                    default => 'Tidak diketahui',
                                },
                                'Date'    => Carbon::parse($item->OPR_REPORTTIME)->format('d-m-Y'),
                                'Time'    => Carbon::parse($item->OPR_REPORTTIME)->format('H:i'),
                            ];
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
                        'Jam' => $jam
                    ];
                })
                ->values();


            return response()->json([
                'data' => $data,
                'status' => 'Success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
                'status' => 'Error',
                'message' => 'Terjadi kesalahan saat mengambil data.',
            ], 500);
        }
    }

    public function excelOneHundredandFifteen(Request $request)
    {
        $tanggalInput = $request->input('tanggal');

        $startDate = Carbon::now()->toDateString();
        $endDate = $startDate;

        if ($tanggalInput) {
            if (str_contains($tanggalInput, 'to')) {
                [$startDate, $endDate] = array_map('trim', explode('to', $tanggalInput));
            } else {
                $startDate = trim($tanggalInput);
                $endDate = $startDate;
            }
        }

        $shiftInput = $request->input('shift');
        $shift = match ($shiftInput) {
            'Siang' => '6',
            'Malam' => '7',
            'ALL', null => '',
            default => '',
        };


        return Excel::download(new PayloadEXOneHundredandFifteenExport($startDate, $endDate, $shift), 'Payload lebih dari 115.xlsx');
    }

}

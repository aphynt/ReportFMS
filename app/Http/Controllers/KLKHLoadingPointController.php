<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class KLKHLoadingPointController extends Controller
{
    //
    public function index()
    {
        return view('klkh.loading-point.index');
    }

    public function api(Request $request)
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

        try {
            $data = DB::select('EXEC DAILY.dbo.APP_GET_REPORT_KLKH_LOADING_POINT @StartDate = ?, @EndDate = ?',
                [$startDate, $endDate]
            );

            return response()->json([
                'data' => $data,
                'status' => 'Success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
                'status' => 'Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function exportExcel(Request $request)
    // {
    //     $tanggalInput = $request->input('tanggal');

    //     $startDate = Carbon::now()->toDateString();
    //     $endDate = $startDate;

    //     if ($tanggalInput) {
    //         if (str_contains($tanggalInput, 'to')) {
    //             [$startDate, $endDate] = array_map('trim', explode('to', $tanggalInput));
    //         } else {
    //             $startDate = trim($tanggalInput);
    //             $endDate = $startDate;
    //         }
    //     }

    //     $shiftInput = $request->input('shift');
    //     $shift = match ($shiftInput) {
    //         'Siang' => '6',
    //         'Malam' => '7',
    //         'ALL', null => '',
    //         default => '',
    //     };

    //     $exInput = $request->input('ex');
    //     $ex = '';

    //     if (is_string($exInput)) {
    //         $decoded = json_decode($exInput, true);

    //         if (json_last_error() === JSON_ERROR_NONE) {
    //             $exArray = array_column($decoded, 'value');
    //             $ex = in_array('ALL', $exArray) ? '' : implode(',', $exArray);
    //         } else {
    //             $ex = ($exInput === 'ALL') ? '' : $exInput;
    //         }
    //     } elseif (is_array($exInput)) {
    //         $ex = in_array('ALL', $exInput) ? '' : implode(',', $exInput);
    //     }

    //     return Excel::download(new PayloadEXExport($startDate, $endDate, $ex, $shift), 'Payload per Excavator.xlsx');
    // }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class KLKHHaulRoadController extends Controller
{
    //
    public function index()
    {
        return view('klkh.haul-road.index');
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
            $data = DB::select('EXEC DAILY.dbo.APP_GET_REPORT_KLKH_HAUL_ROAD @StartDate = ?, @EndDate = ?',
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
}

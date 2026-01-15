<?php

namespace App\Http\Controllers;

use App\Models\OPRPlanEX;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    //
    public function ex()
    {
        $unit = Unit::where('VHC_ACTIVE', true)->where('VHC_ID', 'LIKE', 'EX%')->get();
        return view('plan.ex', compact('unit'));
    }

    public function api_ex(Request $request)
    {
        $loader = $request->input('loader');

        try {

            $hariMap = [
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                7 => 'Minggu',
            ];

            $data = OPRPlanEX::select(
                    'ID as id',
                    'OPR_SHIFTDATE_START as start_date',
                    'OPR_SHIFTDATE_END as end_date',
                    'VHC_ID as vhc_id',
                    'PLN_TIMERANGE as time_range',
                    'PLN_VAL as value',
                    'PLN_DAYS'
                );

            if ($loader !== null && $loader != 'ALL') {
                $data->where('VHC_ID', $loader);
            }

            $rows = $data->get();

            // =========================
            // CONVERT PLN_DAYS → NAMA HARI
            // =========================
            $rows = $rows->map(function ($row) use ($hariMap) {

                $daysArr = [];

                if (!empty($row->PLN_DAYS)) {
                    $decoded = json_decode($row->PLN_DAYS, true);
                    if (is_array($decoded)) {
                        $daysArr = $decoded;
                    }
                }

                $dayNames = collect($daysArr)
                    ->map(fn($d) => $hariMap[$d] ?? $d)
                    ->implode(', ');

                return [
                    'id'              => $row->id,
                    'start_date'      => $row->start_date,
                    'end_date'        => $row->end_date,
                    'vhc_id'          => $row->vhc_id,
                    'time_range'      => $row->time_range,
                    'value'           => $row->value,
                    'plan_days'  => $dayNames,
                    'plan_days_raw'   => $row->PLN_DAYS,
                ];
            });

            return response()->json([
                'data'   => $rows,
                'status' => 'Success',
            ]);

        } catch (\Throwable $th) {

            return response()->json([
                'data'    => [],
                'status'  => 'Error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function ex_store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
            'vhc_id'     => 'required|string',
            'time_range' => 'required|integer|min:0|max:23',
            'value'      => 'required|numeric',
            'plan_days'  => 'required|array|min:1', // [1..7]
        ]);

        try {

            OPRPlanEX::create([
                'OPR_SHIFTDATE_START' => $request->start_date,
                'OPR_SHIFTDATE_END'   => $request->end_date,
                'VHC_ID'             => $request->vhc_id,
                'PLN_TIMERANGE'      => $request->time_range,
                'PLN_VAL'            => $request->value,

                'PLN_DAYS'           => json_encode($request->plan_days),
                'SYS_UPDATEDBY'      => Auth::user()->name,
                'SYS_UPDATEDAT'      => now(),
            ]);

            return response()->json([
                'status' => 'ok',
                'msg'    => 'Data berhasil ditambahkan'
            ]);

        } catch (\Throwable $th) {

            return response()->json([
                'status' => 'error',
                'msg'    => $th->getMessage()
            ], 500);
        }
    }


    public function ex_update(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $start = $request->start_date
            ? Carbon::parse($request->start_date)->format('Y-m-d')
            : null;

        $end = $request->end_date
            ? Carbon::parse($request->end_date)->format('Y-m-d')
            : null;

            OPRPlanEX::where('ID', $request->id)
            ->update([
                'OPR_SHIFTDATE_START' => $start,
                'OPR_SHIFTDATE_END'   => $end,
                'VHC_ID'             => $request->vhc_id,
                'PLN_TIMERANGE'      => $request->time_range,
                'PLN_DAYS'          => $request->plan_days,
                'PLN_VAL'            => $request->value,
                'SYS_UPDATEDBY'      => Auth::user()->name,
                'SYS_UPDATEDAT'      => now(),
            ]);

        return response()->json(['status' => 'ok']);
    }

    public function ex_delete(Request $request)
    {
        try {
            OPRPlanEX::where('ID', $request->id)->delete();

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $th) {
            return response()->json(['status' => $th->getMessage()]);
        }

    }
}

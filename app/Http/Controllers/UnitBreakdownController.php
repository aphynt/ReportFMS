<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitBreakdownController extends Controller
{
    //
    public function breakdown()
    {
        return view('unit.breakdown');
    }

    public function getData(Request $request)
    {

        $tanggal = $request->tanggal;

        if(!$tanggal)
        {
            $tanggal=date('Y-m-d');
        }

        $rows = DB::connection('focus_reporting')
                ->table('RPT_UNIT_STATUS_BREAKDOWN_HOURLY')
                ->select(
                    'ID',
                    'SNAPSHOT_TIME',
                    'VHC_TYPEDESC',
                    'TOTAL_ALL',
                    'TOTAL_DOWN',
                    'ACTUAL_BREAKDOWN'
                )
                ->whereDate('SNAPSHOT_TIME',$tanggal)
                ->orderBy('SNAPSHOT_TIME')
                ->get();

        $hours=[];

        $types=[];

        $data=[];

        foreach($rows as $r)
        {

            $hour = Carbon::parse($r->SNAPSHOT_TIME)->format('H');

            $hours[$hour]=$hour;

            $types[$r->VHC_TYPEDESC]=$r->VHC_TYPEDESC;

            $data[$r->VHC_TYPEDESC][$hour] = [
                'id'     => $r->ID,
                'actual' => $r->ACTUAL_BREAKDOWN ?? $r->TOTAL_DOWN,
                'total'  => $r->TOTAL_DOWN,
                'unit'   => $r->TOTAL_ALL,
            ];

        }

        ksort($hours);

        sort($types);

        return response()->json([
            'hours'=>array_values($hours),
            'types'=>$types,
            'data'=>$data
        ]);

    }

    public function update(Request $request)
    {

        DB::connection('focus_reporting')
            ->table('RPT_UNIT_STATUS_BREAKDOWN_HOURLY')
            ->where('ID',$request->id)
            ->update([

                'ACTUAL_BREAKDOWN'=>$request->value

            ]);

        return response()->json([

            'success'=>true

        ]);

    }

}

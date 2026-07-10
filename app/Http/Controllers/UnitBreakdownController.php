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

        $startPagi = Carbon::parse($tanggal)->setTime(7,0,0);

        $endMalam = Carbon::parse($tanggal)
                        ->addDay()
                        ->setTime(6,59,59);

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
                ->whereBetween('SNAPSHOT_TIME',[
                    $startPagi,
                    $endMalam
                ])
                ->orderBy('SNAPSHOT_TIME')
                ->get();

        $hours=[];

        $types=[];

        $data=[];

        foreach($rows as $r)
        {

            $key = Carbon::parse($r->SNAPSHOT_TIME)->format('Y-m-d H');

            $hours[$key]=[
                'date'=>Carbon::parse($r->SNAPSHOT_TIME)->format('Y-m-d'),
                'hour'=>Carbon::parse($r->SNAPSHOT_TIME)->format('H')
            ];

            $types[$r->VHC_TYPEDESC]=$r->VHC_TYPEDESC;

            $data[$r->VHC_TYPEDESC][$key] = [
                'id'     => $r->ID,
                'actual' => $r->ACTUAL_BREAKDOWN ?? $r->TOTAL_DOWN,
                'total'  => $r->TOTAL_DOWN,
                'unit'   => $r->TOTAL_ALL,
            ];

        }

        ksort($hours);

        $customOrder = [
            'Loader',
            'Hauler',
            'Grader',
            'Dozer',
            'Water Truck',
            'Excavator Support',
            'Fuel Truck',
            'General Support',
        ];

        $types = array_values($types);

        usort($types, function ($a, $b) use ($customOrder) {
            $posA = array_search($a, $customOrder);
            $posB = array_search($b, $customOrder);

            // Jika ada tipe yang tidak ada di customOrder,
            // letakkan di paling bawah
            $posA = $posA === false ? PHP_INT_MAX : $posA;
            $posB = $posB === false ? PHP_INT_MAX : $posB;

            return $posA <=> $posB;
        });

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

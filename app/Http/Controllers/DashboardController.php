<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //

    public function api(Request $request)
    {

        try {
            $plan = DB::table('FOCUS_REPORTING.DASHBOARD.PRODUCTION_PER_HOUR as a')
                ->select([
                    'PIT',
                    'a.HOUR',
                    'a.SORT',
                    'PRODUCTION',
                    DB::raw("COALESCE(
                                CASE
                                    WHEN PLAN_PRODUCTION < 7000 AND PIT = 'ALL PIT' THEN NULL
                                    WHEN PLAN_PRODUCTION < 2333.333 AND PIT = 'PIT SM-A3' THEN NULL
                                    WHEN PLAN_PRODUCTION < 2333.333 AND PIT = 'PIT SM-B1' THEN NULL
                                    WHEN PLAN_PRODUCTION < 2333.333 AND PIT = 'PIT SM-B2' THEN NULL
                                    ELSE PLAN_PRODUCTION
                                END, PLAN_PRODUCTION) AS PLAN_PRODUCTION")
                ])
                ->where('PIT', 'ALL PIT')
                ->orderByRaw("CASE
                                WHEN a.HOUR >= 19 THEN a.HOUR
                                ELSE a.HOUR + 24
                            END")
                ->get();
            $totalProduction = $plan->sum(function ($item) {
                return (float) $item->PRODUCTION;
            });

            $totalPlanProduction = $plan->sum(function ($item) {
                return (float) $item->PLAN_PRODUCTION;
            });

                $plan = $plan->transform(function ($item) {
                    if (isset($item->PRODUCTION)) {
                        $item->PRODUCTION = number_format($item->PRODUCTION, 0, '.', '');
                    }
                    return $item;
                });

            $data = [
                'plan' => $plan,
                'totalProduction' => (int)$totalProduction,
                'totalPlanProduction' => (int)$totalPlanProduction,
            ];

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

    public function index()
    {

        return view('dashboard.index');
    }
}

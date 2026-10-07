<?php

namespace App\Http\Controllers;

use App\Models\OperationalStatusHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperationalStatusController extends Controller
{
    public function index(Request $request)
    {
        $history = null;
        $saved = [];

        if ($request->filled('history_id')) {
            $history = OperationalStatusHistory::findOrFail($request->history_id);
            $saved = json_decode($history->PAYLOAD_JSON, true) ?: [];

            $request->merge([
                'date' => Carbon::parse($history->REPORT_DATE)->format('Y-m-d'),
                'shift' => $history->SHIFT_NO,
                'hour_start' => substr($history->HOUR_START, 0, 5),
            ]);
        }

        $now = Carbon::now('Asia/Makassar');
        $reportDate = $request->filled('date')
            ? $request->date
            : ((int)$now->format('H') < 7 ? $now->copy()->subDay()->format('Y-m-d') : $now->format('Y-m-d'));

        $shiftNo = $request->filled('shift')
            ? (int)$request->shift
            : (((int)$now->format('H') >= 7 && (int)$now->format('H') < 19) ? 6 : 7);

        $requestedHour = $request->filled('hour_start')
            ? substr($request->hour_start, 0, 5)
            : $now->copy()->startOfHour()->format('H:i');

        $hourStart = $this->normalizeHourForShift($requestedHour, $shiftNo);
        $startDateTimeCarbon = Carbon::createFromFormat('Y-m-d H:i', $reportDate.' '.$hourStart, 'Asia/Makassar');

        if ($shiftNo === 7 && (int)substr($hourStart, 0, 2) < 7) {
            $startDateTimeCarbon->addDay();
        }

        $endDateTimeCarbon = $startDateTimeCarbon->copy()->addHour();
        $hourEnd = $endDateTimeCarbon->format('H:i');
        $startHour = (int)$startDateTimeCarbon->format('H');
        $selectedHour = str_pad($startHour, 2, '0', STR_PAD_LEFT);
        $shiftHours = $this->shiftHours($shiftNo);

        $latestShiftHistories = $this->latestShiftHistories($reportDate, $shiftNo);
        $carryHistory = null;
        $carrySaved = [];
        $continuedFrom = null;

        if (!$history) {
            $carryHistory = $latestShiftHistories
                ->filter(fn($row) => $this->shiftPosition((int)substr($row->HOUR_START, 0, 2), $shiftNo) < $this->shiftPosition($startHour, $shiftNo))
                ->last();

            if ($carryHistory) {
                $carrySaved = json_decode($carryHistory->PAYLOAD_JSON, true) ?: [];
                $continuedFrom = [
                    'id' => $carryHistory->ID,
                    'hour_start' => substr($carryHistory->HOUR_START, 0, 5),
                    'hour_end' => substr($carryHistory->HOUR_END, 0, 5),
                    'version' => $carryHistory->VERSION,
                ];
            }
        }

        $latestCreatedTime = null;
        $hourly = collect();

        if ($history && !empty($saved['hourly'])) {
            $hourly = collect($saved['hourly'])->map(function ($row) {
                return (object)[
                    'NUM' => $row['num'] ?? null,
                    'PIT' => 'ALL PIT',
                    'HOUR' => str_pad((int)($row['hour'] ?? 0), 2, '0', STR_PAD_LEFT),
                    'SORT' => $row['sort'] ?? null,
                    'PRODUCTION' => (float)($row['production'] ?? 0),
                    'PLAN_PRODUCTION' => isset($row['plan']) && $row['plan'] !== '' ? (float)$row['plan'] : null,
                    'ACH' => (float)($row['ach'] ?? 0),
                    'PRODUCTION_CUM' => (float)($row['production_cum'] ?? 0),
                    'PLAN_PRODUCTION_CUM' => (float)($row['plan_production_cum'] ?? 0),
                    'ACH_CUM' => (float)($row['ach_cum'] ?? 0),
                    'PRODUCTION_MD' => (float)($row['production_md'] ?? 0),
                    'CREATED_TIME' => $row['created_time'] ?? null,
                    'SAVED_REMARK' => $row['remark'] ?? '',
                ];
            });

            $latestCreatedTime = $history->UPDATED_AT;
        } else {
            $snapshotDate = $startDateTimeCarbon->format('Y-m-d');
            $latestCreatedTime = DB::connection('focus_reporting')
                ->table('DASHBOARD.PRODUCTION_PER_HOUR')
                ->whereDate('CREATED_TIME', $snapshotDate)
                ->max('CREATED_TIME');

            if ($latestCreatedTime) {
                $hourly = DB::connection('focus_reporting')
                    ->table('DASHBOARD.PRODUCTION_PER_HOUR')
                    ->select('NUM', 'PIT', 'HOUR', 'SORT', 'PRODUCTION', 'PLAN_PRODUCTION', 'ACH', 'PRODUCTION_CUM', 'PLAN_PRODUCTION_CUM', 'ACH_CUM', 'PRODUCTION_MD', 'CREATED_TIME')
                    ->where('PIT', 'ALL PIT')
                    ->where('CREATED_TIME', $latestCreatedTime)
                    ->get()
                    ->map(function ($row) {
                        $row->SAVED_REMARK = '';
                        return $row;
                    });
            }

            $savedRemarks = $this->historicalHourlyRemarks($latestShiftHistories);
            $hourly = $hourly->map(function ($row) use ($savedRemarks) {
                $key = str_pad((int)$row->HOUR, 2, '0', STR_PAD_LEFT);
                $row->SAVED_REMARK = $savedRemarks[$key] ?? '';
                return $row;
            });
        }

        $hourly = $this->filterHourlyByShift($hourly, $shiftNo);

        if ($history && !empty($saved['details'])) {
            $details = collect($saved['details'])->map(function ($row) {
                return (object)[
                    'PIT' => $row['pit'] ?? '-',
                    'VHC_ID' => $row['vhc_id'] ?? '-',
                    'MATERIAL' => $row['material'] ?? $this->inferMaterial($row),
                    'DISTANCE_ACTUAL' => (float)($row['distance_actual'] ?? 0),
                    'DT_ACTUAL' => (int)($row['dt_actual'] ?? 0),
                    'DT_PLAN' => $row['dt_plan'] ?? null,
                    'TRIP_ACTUAL' => (int)($row['trip_actual'] ?? 0),
                    'TRIP_PLAN' => $row['trip_plan'] ?? null,
                    'PDTY_ACTUAL' => (float)($row['pdty_actual'] ?? 0),
                    'PDTY_PLAN' => $row['pdty_plan'] ?? null,
                    'PLAN_REFERENCE' => $row['plan_reference'] ?? null,
                    'WD_STATUS' => $row['wd_status'] ?? null,
                    'REMARK' => $row['remark'] ?? null,
                ];
            })->values();
        } else {
            $fleet = DB::connection('focus_reporting')
                ->table('REALTIME.MATCHING_FLEET')
                ->select(
                    'ID', 'PIT', 'VHC_ID', 'NUNIT', 'NTRUCK_NEED', 'EQU_GROUPID', 'LOADER_PRODUCTIVITY',
                    'SUM_RIT_VOLUME', 'AVG_RIT_SPEED', 'AVG_RIT_CYCLETIME', 'AVG_RIT_CYCLEDISTANCE',
                    'CAT777_COUNT', 'CAT777_PRODUCTIVITY', 'KOM785_COUNT', 'KOM785_PRODUCTIVITY',
                    'DISPOSAL', 'PRODUCTIVITY', 'CAPACITY', 'WORKING_HOURS', 'PDTY_LOADER', 'HAULER_CAPS',
                    'MF', 'NTRUCK_KOM785', 'NTRUCK_CAT777', 'NTRUCK_CAT773', 'CAT777_CAPACITY',
                    'KOM785_CAPACITY', 'KOM785_PRODPERHOUR', 'CAT777_PRODPERHOUR', 'CAT773_PRODPERHOUR',
                    'LAST_UPDATED'
                )
                ->whereNotNull('VHC_ID')
                ->where('VHC_ID', '<>', '')
                ->orderBy('PIT')
                ->orderBy('VHC_ID')
                ->get();

            $ritation = DB::connection('focus')
                ->table('dbo.PRD_RITATION')
                ->select(
                    'LOD_LOADERID',
                    DB::raw('COUNT(*) AS TRIP_ACTUAL'),
                    DB::raw('COUNT(DISTINCT VHC_ID) AS HD_ACTUAL'),
                    DB::raw('AVG(CAST(RIT_HAULDISTANCE AS FLOAT)) / 1000.0 AS HAUL_DISTANCE'),
                    DB::raw('SUM(CAST(RIT_VOLUME AS FLOAT)) AS RIT_VOLUME')
                )
                ->where('OPR_SHIFTDATE', $reportDate)
                ->where('OPR_SHIFTNO', $shiftNo)
                ->where('OPR_REPORTTIME', '>=', $startDateTimeCarbon->format('Y-m-d H:i:s'))
                ->where('OPR_REPORTTIME', '<', $endDateTimeCarbon->format('Y-m-d H:i:s'))
                ->whereNotNull('LOD_LOADERID')
                ->groupBy('LOD_LOADERID')
                ->get()
                ->keyBy('LOD_LOADERID');

            $planEx = DB::connection('focus_reporting')
                ->table('dbo.OPR_PLAN_EX')
                ->select('vhc_id', 'pln_val', 'pln_timerange', 'opr_shiftdate_start', 'opr_shiftdate_end', 'pln_days')
                ->whereDate('opr_shiftdate_start', '<=', $reportDate)
                ->whereDate('opr_shiftdate_end', '>=', $reportDate)
                ->where('pln_timerange', $startHour)
                ->get()
                ->keyBy('vhc_id');

            $details = $fleet->map(function ($row) use ($ritation, $planEx) {
                $rit = $ritation->get($row->VHC_ID);
                $plan = $planEx->get($row->VHC_ID);
                $distance = $rit && $rit->HAUL_DISTANCE !== null
                    ? (float)$rit->HAUL_DISTANCE
                    : ((float)($row->AVG_RIT_CYCLEDISTANCE ?? 0) / 1000);

                $disposal = strtoupper(trim((string)($row->DISPOSAL ?? '')));
                $row->MATERIAL = Str::contains($disposal, ['MUD', 'LUMPUR']) ? 'MUD' : 'OB';
                $row->DISTANCE_ACTUAL = round($distance, 2);
                $row->DT_ACTUAL = (int)($rit->HD_ACTUAL ?? $row->NUNIT ?? 0);
                $row->TRIP_ACTUAL = (int)($rit->TRIP_ACTUAL ?? 0);
                $row->PDTY_ACTUAL = $row->MATERIAL === 'MUD'
                    ? (float)($rit->RIT_VOLUME ?? 0)
                    : (float)($row->CAPACITY ?? 0);
                $row->PLAN_REFERENCE = $plan ? (float)$plan->pln_val : null;
                $row->DT_PLAN = null;
                $row->TRIP_PLAN = null;
                $row->PDTY_PLAN = null;
                $row->WD_STATUS = null;
                $row->REMARK = null;

                return $row;
            })
            ->sort(function ($a, $b) {
                $pit = strcmp($a->PIT ?? '', $b->PIT ?? '');
                return $pit !== 0 ? $pit : strnatcasecmp($a->VHC_ID ?? '', $b->VHC_ID ?? '');
            })
            ->values();

            if (!empty($carrySaved['details'])) {
                $previousDetails = collect($carrySaved['details'])->keyBy(
                    fn($row) => strtoupper(trim((string)($row['vhc_id'] ?? '')))
                );

                $details = $details->map(function ($row) use ($previousDetails) {
                    $previous = $previousDetails->get(strtoupper(trim((string)$row->VHC_ID)));

                    if ($previous) {
                        $row->DT_PLAN = $previous['dt_plan'] ?? null;
                        $row->TRIP_PLAN = $previous['trip_plan'] ?? null;
                        $row->PDTY_PLAN = $previous['pdty_plan'] ?? null;
                        $row->WD_STATUS = $previous['wd_status'] ?? null;
                        $row->REMARK = $previous['remark'] ?? null;
                    }

                    return $row;
                });
            }
        }

        $unitStatus = [
            'EX Big' => ['pop' => null, 'ready' => null, 'down' => null],
            'HD OB' => ['pop' => null, 'ready' => null, 'down' => null],
            'HD MV' => ['pop' => null, 'ready' => null, 'down' => null],
            'MG' => ['pop' => null, 'ready' => null, 'down' => null],
            'BD' => ['pop' => null, 'ready' => null, 'down' => null],
            'WT' => ['pop' => null, 'ready' => null, 'down' => null],
            'EX Small' => ['pop' => null, 'ready' => null, 'down' => null],
        ];

        $operatorStatus = [
            'planned' => null,
            'present' => null,
            'operation' => null,
            'spare' => null,
            'izin' => null,
            'sakit' => null,
            'mangkir' => null,
            'sk' => null,
            'emergency' => null,
        ];

        $notes = '';
        $issues = '';
        $carrySource = $history ? $saved : $carrySaved;

        foreach ($carrySource['units'] ?? [] as $unit) {
            if (!empty($unit['type'])) {
                $unitStatus[$unit['type']] = [
                    'pop' => $unit['pop'] ?? null,
                    'ready' => $unit['ready'] ?? null,
                    'down' => $unit['down'] ?? null,
                ];
            }
        }

        $operatorStatus = array_replace($operatorStatus, $carrySource['operator'] ?? []);
        $notes = $carrySource['notes'] ?? '';
        $issues = $carrySource['issues'] ?? '';

        $currentProduction = $hourly->first(
            fn($row) => str_pad((int)$row->HOUR, 2, '0', STR_PAD_LEFT) === $selectedHour
        );

        $productionActual = (float)($currentProduction->PRODUCTION ?? 0);
        $productionPlan = (float)($currentProduction->PLAN_PRODUCTION ?? 0);

        $summary = [
            'production_actual' => $productionActual,
            'production_plan' => $productionPlan,
            'achievement' => $productionPlan > 0 ? ($productionActual / $productionPlan) * 100 : 0,
            'production_mud' => (float)($currentProduction->PRODUCTION_MD ?? 0),
        ];

        return view('operational-status.form', compact(
            'reportDate', 'shiftNo', 'hourStart', 'hourEnd', 'shiftHours', 'hourly', 'details',
            'unitStatus', 'operatorStatus', 'notes', 'issues', 'latestCreatedTime',
            'currentProduction', 'summary', 'history', 'continuedFrom'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'report_date' => 'required|date',
            'shift_no' => 'required|in:6,7',
            'hour_start' => 'required|date_format:H:i',
            'hour_end' => 'required|date_format:H:i',
            'status' => 'nullable|in:draft,submitted',
        ]);

        $shiftNo = (int)$request->shift_no;
        $startHour = (int)substr($request->hour_start, 0, 2);

        if (!$this->hourBelongsToShift($startHour, $shiftNo)) {
            return back()->withInput()->withErrors([
                'hour_start' => 'Jam yang dipilih tidak sesuai dengan shift.',
            ]);
        }

        $expectedEnd = str_pad(($startHour + 1) % 24, 2, '0', STR_PAD_LEFT).':00';

        if ($request->hour_end !== $expectedEnd) {
            return back()->withInput()->withErrors([
                'hour_end' => 'Jam selesai harus satu jam setelah jam mulai.',
            ]);
        }

        $model = new OperationalStatusHistory();

        $history = $model->getConnection()->transaction(function () use ($request) {
            $latestVersion = OperationalStatusHistory::whereDate('REPORT_DATE', $request->report_date)
                ->where('SHIFT_NO', $request->shift_no)
                ->where('HOUR_START', $request->hour_start)
                ->where('HOUR_END', $request->hour_end)
                ->lockForUpdate()
                ->max('VERSION');

            return OperationalStatusHistory::create([
                'REPORT_DATE' => $request->report_date,
                'SHIFT_NO' => (int)$request->shift_no,
                'HOUR_START' => $request->hour_start,
                'HOUR_END' => $request->hour_end,
                'VERSION' => ((int)$latestVersion) + 1,
                'STATUS' => $request->status ?? 'draft',
                'PAYLOAD_JSON' => json_encode(
                    $request->except('_token'),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'BASE_HISTORY_ID' => $request->filled('base_history_id') ? (int)$request->base_history_id : null,
                'CREATED_BY' => optional(Auth::user())->name ?? optional(Auth::user())->nik ?? null,
            ]);
        });

        return redirect()
            ->route('operational-status.edit-history', ['id' => $history->getKey()])
            ->with('success', "Operational Status V{$history->VERSION} berhasil disimpan.");
    }

    public function whatsappHistories(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'shift' => 'required|in:6,7',
        ]);

        $shiftNo = (int)$request->shift;

        $rows = OperationalStatusHistory::whereDate('REPORT_DATE', $request->date)
            ->where('SHIFT_NO', $shiftNo)
            ->get()
            ->filter(fn($row) => $this->hourBelongsToShift((int)substr($row->HOUR_START, 0, 2), $shiftNo))
            ->groupBy(fn($row) => substr($row->HOUR_START, 0, 5).'|'.substr($row->HOUR_END, 0, 5))
            ->map(fn($group) => $group->sortByDesc('VERSION')->first())
            ->values();

        $rows = $this->sortHistories($rows, $shiftNo)->map(fn($row) => [
            'id' => $row->ID,
            'hour_start' => substr($row->HOUR_START, 0, 5),
            'hour_end' => substr($row->HOUR_END, 0, 5),
            'version' => $row->VERSION,
            'status' => $row->STATUS,
            'created_by' => $row->CREATED_BY,
            'updated_at' => $row->UPDATED_AT ? Carbon::parse($row->UPDATED_AT)->format('d-m-Y H:i') : null,
            'edit_url' => route('operational-status.edit-history', ['id' => $row->ID]),
        ])->values();

        return response()->json(['data' => $rows]);
    }

    public function whatsappReportPreview(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|distinct',
            'include_summary' => 'nullable|boolean',
        ]);

        $rows = OperationalStatusHistory::whereIn('ID', $request->ids)->get();

        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Data historical tidak ditemukan.'], 404);
        }

        if (
            $rows->pluck('REPORT_DATE')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->unique()->count() > 1 ||
            $rows->pluck('SHIFT_NO')->unique()->count() > 1
        ) {
            return response()->json([
                'message' => 'Historical yang dipilih harus dari tanggal dan shift yang sama.',
            ], 422);
        }

        $rows = $this->sortHistories($rows, (int)$rows->first()->SHIFT_NO);
        $report = $this->buildReportData($rows, $request->boolean('include_summary', true));

        return response()->json([
            'html' => view('operational-status.report-image', compact('report'))->render(),
            'filename' => 'operational-status-'.$report['report_date'].'-shift-'.$report['shift_no'].'.png',
            'caption' => 'Operational Status '.$report['date_label'].' - '.$report['shift_name'].' - '.$report['latest_hour'],
        ]);
    }

    public function whatsappPreview(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|distinct',
            'include_summary' => 'nullable|boolean',
        ]);

        $rows = OperationalStatusHistory::whereIn('ID', $request->ids)->get();

        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Data historical tidak ditemukan.'], 404);
        }

        $rows = $this->sortHistories($rows, (int)$rows->first()->SHIFT_NO);
        $report = $this->buildReportData($rows, $request->boolean('include_summary', true));

        $text = "OPERATIONAL STATUS\n";
        $text .= "TANGGAL : [{$report['date_label']}]\n";
        $text .= "SHIFT   : [{$report['shift_name']}] [{$report['shift_time']}]\n";
        $text .= "JAM     : [{$report['latest_hour']}]";

        return response()->json(['text' => $text]);
    }

    public function editHistory($id)
    {
        OperationalStatusHistory::findOrFail($id);

        return redirect()->route('operational-status.index', [
            'history_id' => $id,
        ]);
    }

    private function latestShiftHistories(string $reportDate, int $shiftNo)
    {
        return OperationalStatusHistory::whereDate('REPORT_DATE', $reportDate)
            ->where('SHIFT_NO', $shiftNo)
            ->get()
            ->filter(fn($row) => $this->hourBelongsToShift((int)substr($row->HOUR_START, 0, 2), $shiftNo))
            ->groupBy(fn($row) => substr($row->HOUR_START, 0, 5).'|'.substr($row->HOUR_END, 0, 5))
            ->map(fn($group) => $group->sortByDesc('VERSION')->first())
            ->sortBy(fn($row) => $this->shiftPosition((int)substr($row->HOUR_START, 0, 2), $shiftNo))
            ->values();
    }

    private function historicalHourlyRemarks($histories): array
    {
        $remarks = [];

        foreach ($histories as $history) {
            $payload = json_decode($history->PAYLOAD_JSON, true) ?: [];
            $targetHour = str_pad((int)substr($history->HOUR_START, 0, 2), 2, '0', STR_PAD_LEFT);

            $hourRow = collect($payload['hourly'] ?? [])->first(
                fn($item) => str_pad((int)($item['hour'] ?? -1), 2, '0', STR_PAD_LEFT) === $targetHour
            );

            if ($hourRow && trim((string)($hourRow['remark'] ?? '')) !== '') {
                $remarks[$targetHour] = trim((string)$hourRow['remark']);
            }
        }

        return $remarks;
    }

    private function filterHourlyByShift($hourly, int $shiftNo)
    {
        return collect($hourly)
            ->filter(fn($row) => $this->hourBelongsToShift((int)$row->HOUR, $shiftNo))
            ->sortBy(fn($row) => $this->shiftPosition((int)$row->HOUR, $shiftNo))
            ->values();
    }

    private function shiftHours(int $shiftNo): array
    {
        return $shiftNo === 6
            ? range(7, 18)
            : array_merge(range(19, 23), range(0, 6));
    }

    private function normalizeHourForShift(string $hourStart, int $shiftNo): string
    {
        $hour = (int)substr($hourStart, 0, 2);

        if (!$this->hourBelongsToShift($hour, $shiftNo)) {
            $hour = $shiftNo === 6 ? 7 : 19;
        }

        return str_pad($hour, 2, '0', STR_PAD_LEFT).':00';
    }

    private function hourBelongsToShift(int $hour, int $shiftNo): bool
    {
        return $shiftNo === 6
            ? $hour >= 7 && $hour < 19
            : ($hour >= 19 || $hour < 7);
    }

    private function shiftPosition(int $hour, int $shiftNo): int
    {
        if ($shiftNo === 6) {
            return $hour - 7;
        }

        return $hour >= 19 ? $hour - 19 : $hour + 5;
    }

    private function sortHistories($rows, int $shiftNo)
    {
        return collect($rows)
            ->filter(fn($row) => $this->hourBelongsToShift((int)substr($row->HOUR_START, 0, 2), $shiftNo))
            ->sortBy(fn($row) => $this->shiftPosition((int)substr($row->HOUR_START, 0, 2), $shiftNo))
            ->values();
    }

    private function inferMaterial(array $row): string
    {
        $material = strtoupper(trim((string)($row['material'] ?? '')));

        if (in_array($material, ['OB', 'MUD'], true)) {
            return $material;
        }

        $trip = (float)($row['trip_actual'] ?? 0);
        $pdty = (float)($row['pdty_actual'] ?? 0);
        $ratio = $trip > 0 ? $pdty / $trip : 0;

        return $ratio > 0 && $ratio <= 35.5 ? 'MUD' : 'OB';
    }

    private function buildReportData($rows, bool $includeSummary): array
    {
        $first = $rows->first();
        $last = $rows->last();
        $shiftNo = (int)$first->SHIFT_NO;
        $hourlyRows = [];

        foreach ($rows as $history) {
            $payload = json_decode($history->PAYLOAD_JSON, true) ?: [];
            $details = collect($payload['details'] ?? []);
            $obDetails = $details->filter(fn($d) => $this->inferMaterial($d) === 'OB');
            $mudDetails = $details->filter(fn($d) => $this->inferMaterial($d) === 'MUD');
            $hourKey = str_pad((int)substr($history->HOUR_START, 0, 2), 2, '0', STR_PAD_LEFT);
            $hourData = collect($payload['hourly'] ?? [])->first(
                fn($item) => str_pad((int)($item['hour'] ?? -1), 2, '0', STR_PAD_LEFT) === $hourKey
            ) ?? [];

            $hourlyRows[] = [
                'hour' => substr($history->HOUR_START, 0, 5).'-'.substr($history->HOUR_END, 0, 5),
                'ob_hd' => (int)$obDetails->sum(fn($d) => (int)($d['dt_actual'] ?? 0)),
                'ob_trip' => (int)$obDetails->sum(fn($d) => (int)($d['trip_actual'] ?? 0)),
                'ob_volume' => (float)($hourData['production'] ?? $obDetails->sum(fn($d) => (float)($d['pdty_actual'] ?? 0))),
                'mud_hd' => (int)$mudDetails->sum(fn($d) => (int)($d['dt_actual'] ?? 0)),
                'mud_trip' => (int)$mudDetails->sum(fn($d) => (int)($d['trip_actual'] ?? 0)),
                'mud_volume' => (float)($hourData['production_md'] ?? $mudDetails->sum(fn($d) => (float)($d['pdty_actual'] ?? 0))),
                'remark' => trim((string)($hourData['remark'] ?? '')),
            ];
        }

        $lastPayload = json_decode($last->PAYLOAD_JSON, true) ?: [];

        $detailRows = collect($lastPayload['details'] ?? [])->map(function ($d) {
            return [
                'material' => $this->inferMaterial($d),
                'area' => preg_replace('/^PIT\s+/i', '', (string)($d['pit'] ?? '-')),
                'ex' => str_replace('EX', '', (string)($d['vhc_id'] ?? '-')),
                'distance' => (float)($d['distance_actual'] ?? 0),
                'dt_actual' => $d['dt_actual'] ?? 0,
                'dt_plan' => ($d['dt_plan'] ?? '') !== '' ? $d['dt_plan'] : '-',
                'trip_actual' => $d['trip_actual'] ?? 0,
                'trip_plan' => ($d['trip_plan'] ?? '') !== '' ? $d['trip_plan'] : '-',
                'pdty_actual' => $d['pdty_actual'] ?? 0,
                'pdty_plan' => ($d['pdty_plan'] ?? '') !== '' ? $d['pdty_plan'] : '-',
                'wd' => $d['wd_status'] ?? '-',
                'remark' => trim((string)($d['remark'] ?? '')),
            ];
        })->values();

        $units = collect($lastPayload['units'] ?? [])->map(fn($u) => [
            'type' => $u['type'] ?? '-',
            'pop' => $u['pop'] ?? '-',
            'ready' => $u['ready'] ?? '-',
            'down' => $u['down'] ?? '-',
        ])->values();

        $operator = array_replace([
            'planned' => '-',
            'present' => '-',
            'operation' => '-',
            'spare' => '-',
            'izin' => '-',
            'sakit' => '-',
            'mangkir' => '-',
            'sk' => '-',
            'emergency' => '-',
        ], $lastPayload['operator'] ?? []);

        $totals = [
            'ob_hd' => collect($hourlyRows)->sum('ob_hd'),
            'ob_trip' => collect($hourlyRows)->sum('ob_trip'),
            'ob_volume' => collect($hourlyRows)->sum('ob_volume'),
            'mud_hd' => collect($hourlyRows)->sum('mud_hd'),
            'mud_trip' => collect($hourlyRows)->sum('mud_trip'),
            'mud_volume' => collect($hourlyRows)->sum('mud_volume'),
        ];

        return [
            'report_date' => Carbon::parse($first->REPORT_DATE)->format('Y-m-d'),
            'date_label' => strtoupper(Carbon::parse($first->REPORT_DATE)->locale('id')->translatedFormat('d F Y')),
            'shift_no' => $shiftNo,
            'shift_name' => $shiftNo === 6 ? 'SIANG' : 'MALAM',
            'shift_time' => $shiftNo === 6 ? '06:30-18:30' : '18:30-06:30',
            'latest_hour' => substr($last->HOUR_START, 0, 5).' - '.substr($last->HOUR_END, 0, 5),
            'hourly' => $hourlyRows,
            'totals' => $totals,
            'details' => $detailRows,
            'units' => $units,
            'operator' => $operator,
            'notes' => trim((string)($lastPayload['notes'] ?? '')),
            'issues' => trim((string)($lastPayload['issues'] ?? '')),
            'include_summary' => $includeSummary,
        ];
    }
}

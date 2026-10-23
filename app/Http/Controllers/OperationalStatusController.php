<?php

namespace App\Http\Controllers;

use App\Models\OperationalStatusHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OperationalStatusController extends Controller
{
    public function index(Request $request)
    {
        $history = null;
        $saved = [];

        if ($request->filled('history_id')) {
            $history = OperationalStatusHistory::where('IS_ACTIVE', 1)->findOrFail($request->history_id);
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

        if (!$history) {
            $existingHistory = $this->activeHistoryForSlot(
                $reportDate,
                $shiftNo,
                $hourStart,
                $hourEnd
            );

            if ($existingHistory) {
                return redirect()
                    ->route('operational-status.edit-history', ['id' => $existingHistory->ID])
                    ->with('info', 'Data pada tanggal dan jam tersebut sudah ada. Sistem membuka mode edit.');
            }
        }

        $latestShiftHistories = $this->latestShiftHistories($reportDate, $shiftNo);
        $carryHistory = null;
        $carrySaved = [];
        $continuedFrom = null;



        if (!$history) {
            $carryHistory = $this->latestCarryHistoryBefore($startDateTimeCarbon);

            if ($carryHistory) {
                $carrySaved = json_decode($carryHistory->PAYLOAD_JSON, true) ?: [];
                $continuedFrom = [
                    'id' => $carryHistory->ID,
                    'date' => Carbon::parse($carryHistory->REPORT_DATE)->format('Y-m-d'),
                    'shift' => (int)$carryHistory->SHIFT_NO,
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
                    'AVG_DISTANCE_OB' => (float)($row['avg_distance_ob'] ?? 0),
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
                    ->select(
                        'NUM',
                        'PIT',
                        'HOUR',
                        'SORT',
                        'PRODUCTION',
                        'PLAN_PRODUCTION',
                        'ACH',
                        'PRODUCTION_CUM',
                        'PLAN_PRODUCTION_CUM',
                        'ACH_CUM',
                        'PRODUCTION_MD',
                        'CREATED_TIME'
                    )
                    ->where('PIT', 'ALL PIT')
                    ->where('CREATED_TIME', $latestCreatedTime)
                    ->get()
                    ->map(function ($row) {
                        $row->SAVED_REMARK = '';
                        return $row;
                    });
            }
        }

        /*
         * Keterangan per jam tidak lagi bergantung pada report jam tersebut
         * harus pernah di-Draft. Setiap payload history menyimpan seluruh baris
         * jam yang terlihat pada form, sehingga keterangan yang diisi untuk
         * 22:00 / 23:00 ketika sedang berada di 01:00 tetap dapat dibaca lagi.
         */
        $savedRemarks = $this->historicalHourlyRemarks($latestShiftHistories);

        /*
         * DASHBOARD.PRODUCTION_PER_HOUR dapat berganti snapshot setelah lewat
         * tengah malam. Akibatnya snapshot 01:00 kadang tidak membawa kembali
         * baris 22:00 / 23:00 dari tanggal sebelumnya.
         *
         * Untuk tampilan Form, actual per jam dibangun ulang langsung dari
         * FOCUS.PRD_RITATION berdasarkan OPR_REPORTTIME untuk seluruh shift
         * sampai jam yang sedang dipilih. Dengan demikian user tidak perlu
         * membuka dan Draft satu per satu setiap jam.
         */
        $hourly = $this->completeHourlyRowsForForm(
            $hourly,
            $reportDate,
            $shiftNo,
            $startHour,
            $savedRemarks
        );

        $avgDistanceObByHour = $this->hourlyAverageObDistance($reportDate, $shiftNo);
        $hourly = $hourly->map(function ($row) use ($avgDistanceObByHour) {
            $hourKey = str_pad((int)$row->HOUR, 2, '0', STR_PAD_LEFT);
            $row->AVG_DISTANCE_OB = isset($avgDistanceObByHour[$hourKey])
                ? (float)$avgDistanceObByHour[$hourKey]
                : (float)($row->AVG_DISTANCE_OB ?? 0);

            return $row;
        });

        if ($history && !empty($saved['details'])) {
            $details = collect($saved['details'])->map(function ($row) {
                return (object)[
                    'PIT' => $row['pit'] ?? '-',
                    'VHC_ID' => $row['vhc_id'] ?? '-',
                    'MATERIAL' => $row['material'] ?? $this->inferMaterial($row),
                    'DISTANCE_ACTUAL' => (float)($row['distance_actual'] ?? 0),
                    'DT_ACTUAL' => (int)($row['dt_actual'] ?? 0),
                    // Koreksi HD berlaku hanya untuk slot history ini.
                    'DT_ACTUAL_ASSIGNMENT' => (int)($row['dt_actual_assignment'] ?? $row['dt_actual'] ?? 0),
                    'DT_PLAN' => $row['dt_plan'] ?? null,
                    'TRIP_ACTUAL' => (int)($row['trip_actual'] ?? 0),
                    'TRIP_PLAN' => $row['trip_plan'] ?? null,
                    'PDTY_ACTUAL' => (int) round((float)($row['pdty_actual'] ?? 0)),
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
                    DB::raw('SUM(CAST(RIT_VOLUME AS FLOAT)) AS RIT_VOLUME'),
                    DB::raw("SUM(CASE WHEN UPPER(LTRIM(RTRIM(LOD_MAT_ID))) = 'OB' THEN 1 ELSE 0 END) AS OB_TRIP_COUNT"),
                    DB::raw("SUM(CASE WHEN UPPER(LTRIM(RTRIM(LOD_MAT_ID))) = 'MD' THEN 1 ELSE 0 END) AS MUD_TRIP_COUNT")
                )
                ->whereRaw("UPPER(LTRIM(RTRIM(LOD_MAT_ID))) IN ('OB', 'MD')")
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

                // Material EX ditentukan dari LOD_MAT_ID transaksi yang benar-benar terjadi.
                // Satu EX dapat memiliki campuran OB/MD; untuk baris EX tunggal gunakan
                // material terbanyak. REKAP PER JAM di bawah tetap menghitung OB/MD terpisah.
                $obTripCount = (int)($rit->OB_TRIP_COUNT ?? 0);
                $mudTripCount = (int)($rit->MUD_TRIP_COUNT ?? 0);
                if ($obTripCount > 0 || $mudTripCount > 0) {
                    $row->MATERIAL = $mudTripCount > $obTripCount ? 'MUD' : 'OB';
                } else {
                    $disposal = strtoupper(trim((string)($row->DISPOSAL ?? '')));
                    $row->MATERIAL = Str::contains($disposal, ['MUD', 'LUMPUR']) ? 'MUD' : 'OB';
                }
                $row->DISTANCE_ACTUAL = round($distance, 2);
                // Jam baru selalu mulai dari jumlah HD assignment, bukan
                // koreksi manual yang disimpan pada jam sebelumnya.
                $row->DT_ACTUAL = (int)($row->NUNIT ?? $rit->HD_ACTUAL ?? 0);
                $row->DT_ACTUAL_ASSIGNMENT = $row->DT_ACTUAL;
                $row->TRIP_ACTUAL = (int)($rit->TRIP_ACTUAL ?? 0);
                // Selanjutnya PDTY_ACTUAL disinkronkan dari hasil COUNT(*) per EX,
                // LOD_MAT_ID dan interval jam, bukan dari CAPACITY / RIT_VOLUME.
                // Nilai final P.DTY Actual akan diisi dari procedure hourly.
                // Jangan hitung Actual dari Trip x 42/30.
                $row->PDTY_ACTUAL = 0;
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

        // Sinkronkan Ritasi Actual per EX, termasuk saat membuka history lama.
        // Data manual (plan, W/D, remark) tetap diambil dari payload history.
        // Sumber: PRD_RITATION.OPR_REPORTTIME dengan interval [start, end).
        $actualByExMaterial = $this->actualTripsByExMaterialForWindow(
            $startDateTimeCarbon,
            $endDateTimeCarbon
        );

        // Productivity EXCA: gunakan sumber yang sama dengan halaman production.ex.
        // PRODUCTION = Prty Actual, PLAN_PRODUCTION = Prty Plan.
        $productivityByEx = $this->operationalStatusActualPerExForHour(
            $reportDate,
            $shiftNo,
            $startHour
        );
        $details = $details->map(function ($row) use ($actualByExMaterial, $productivityByEx) {
            $loaderId = $this->normalizeReportLoaderId($row->VHC_ID ?? '');
            $stats = $actualByExMaterial[$loaderId] ?? [];
            $obTrip = (int)($stats['OB']['trips'] ?? 0);
            $mudTrip = (int)($stats['MUD']['trips'] ?? 0);
            // Ambil jarak rata-rata aktual dari ritasi FOCUS pada slot terpilih,
            // tanpa mengambil jarak history jam sebelumnya.
            $distanceMeters = (float)($stats['OB']['haul_meters_valid'] ?? 0)
                + (float)($stats['MUD']['haul_meters_valid'] ?? 0);
            $distanceCount = (int)($stats['OB']['haul_count_valid'] ?? 0)
                + (int)($stats['MUD']['haul_count_valid'] ?? 0);
            if ($distanceCount > 0) {
                $row->DISTANCE_ACTUAL = round($distanceMeters / $distanceCount / 1000.0, 2);
            }
            $row->TRIP_ACTUAL = $obTrip + $mudTrip;
            // Satu baris Form per EX: pilih material dominan berdasarkan transaksi.
            // Jika campuran OB+MD, report-image menampilkan dua baris dari snapshot.
            if ($obTrip + $mudTrip > 0) {
                $row->MATERIAL = $mudTrip > $obTrip ? 'MUD' : 'OB';
            }
            $productivity = $productivityByEx[$loaderId][$row->MATERIAL] ?? null;

            // P.DTY Actual hanya berasal dari procedure hourly.
            // Jika tidak ada hasil, nilainya 0; tidak ada fallback Trip x 42/30.
            $row->PDTY_ACTUAL = $productivity
                ? (int) round((float)($productivity['actual'] ?? 0))
                : 0;

            // P.DTY Plan BUKAN dari stored procedure.
            // Rumus:
            // OB  = Trip Plan x 42
            // MUD = Trip Plan x 30
            $tripPlan = is_numeric($row->TRIP_PLAN ?? null)
                ? (float)$row->TRIP_PLAN
                : null;

            $row->PDTY_PLAN = $tripPlan !== null
                ? (int) round($tripPlan * ($row->MATERIAL === 'MUD' ? 30 : 42))
                : null;

            return $row;
        });

        // Unit yang baru mulai ritasi dalam jam ini mungkin belum masuk MATCHING_FLEET,
        // atau belum ada pada details history lama. Tetap sediakan baris FORM agar CCR
        // bisa mengisi Area, Plan, W/D, dan keterangan sebelum gambar dikirim ke WA.
        $knownLoaders = $details->mapWithKeys(function ($row) {
            $loaderId = $this->normalizeReportLoaderId($row->VHC_ID ?? '');
            return $loaderId !== '' ? [$loaderId => true] : [];
        })->all();

        $priorDetails = collect($saved['details'] ?? $carrySaved['details'] ?? [])
            ->mapWithKeys(function ($detail) {
                $key = $this->normalizeReportLoaderId($detail['vhc_id'] ?? '');
                return $key !== '' ? [$key => $detail] : [];
            });

        // Saat mengedit slot lama, transaksi yang tiba terlambat tetap ditambahkan
        // ke form; angka snapshot resmi akan diperbarui setelah CCR menekan Simpan.
        foreach ($actualByExMaterial as $rawLoaderId => $materials) {
            $loaderId = $this->normalizeReportLoaderId($rawLoaderId);
            if ($loaderId === '' || $loaderId === '__UNASSIGNED__' || isset($knownLoaders[$loaderId])) {
                continue;
            }

            $obTrips = (int)($materials['OB']['trips'] ?? 0);
            $mudTrips = (int)($materials['MUD']['trips'] ?? 0);
            if ($obTrips + $mudTrips === 0) {
                continue;
            }

            $prior = $priorDetails->get($loaderId, []);
            $material = $mudTrips > $obTrips ? 'MUD' : 'OB';
            $pit = trim((string)($prior['pit'] ?? ''));
            if (strtoupper($pit) === 'BELUM TERPETAKAN' || $pit === '-') {
                $pit = '';
            }

            $details->push((object)[
                'PIT' => $pit,
                'VHC_ID' => $loaderId,
                'MATERIAL' => $material,
                'DISTANCE_ACTUAL' => (
                    ((int)($materials['OB']['haul_count_valid'] ?? 0)
                        + (int)($materials['MUD']['haul_count_valid'] ?? 0)) > 0
                    ? round((
                        (float)($materials['OB']['haul_meters_valid'] ?? 0)
                        + (float)($materials['MUD']['haul_meters_valid'] ?? 0)
                    ) / (
                        (int)($materials['OB']['haul_count_valid'] ?? 0)
                        + (int)($materials['MUD']['haul_count_valid'] ?? 0)
                    ) / 1000.0, 2)
                    : (float)($prior['distance_actual'] ?? 0)
                ),
                'DT_ACTUAL' => (int)(($materials['OB']['hd'] ?? 0) + ($materials['MUD']['hd'] ?? 0)),
                'DT_ACTUAL_ASSIGNMENT' => (int)(($materials['OB']['hd'] ?? 0) + ($materials['MUD']['hd'] ?? 0)),
                'DT_PLAN' => $prior['dt_plan'] ?? null,
                'TRIP_ACTUAL' => $obTrips + $mudTrips,
                'TRIP_PLAN' => $prior['trip_plan'] ?? null,
                'PDTY_ACTUAL' => isset($productivityByEx[$loaderId][$material])
                    ? (int) round((float)($productivityByEx[$loaderId][$material]['actual'] ?? 0))
                    : 0,
                'PDTY_PLAN' => is_numeric($prior['trip_plan'] ?? null)
                    ? (int) round((float)$prior['trip_plan'] * ($material === 'MUD' ? 30 : 42))
                    : null,
                'PLAN_REFERENCE' => $prior['plan_reference'] ?? null,
                'WD_STATUS' => $prior['wd_status'] ?? null,
                'REMARK' => $prior['remark'] ?? '',
                'IS_NEW_LOADER' => true,
            ]);
            $knownLoaders[$loaderId] = true;
        }

        // Pertahankan hanya satu entri per EX; jangan gandakan baris dari fleet
        // realtime dan transaksi historis (mis. EX277 ketika baru operasi).
        $details = $details->unique(function ($row) {
            return $this->normalizeReportLoaderId($row->VHC_ID ?? '');
        })->sort(function ($a, $b) {
            $pitDiff = strnatcasecmp((string)($a->PIT ?? ''), (string)($b->PIT ?? ''));
            return $pitDiff !== 0 ? $pitDiff : strnatcasecmp((string)$a->VHC_ID, (string)$b->VHC_ID);
        })->values();

        $unitStatus = [
            'EX Big' => ['pop' => 14, 'ready' => 14, 'down' => 0],
            'HD OB' => ['pop' => 108, 'ready' => 108, 'down' => 0],
            'HD MV' => ['pop' => 9, 'ready' => 9, 'down' => 0],
            'MG' => ['pop' => 21, 'ready' => 21, 'down' => 0],
            'BD' => ['pop' => 22, 'ready' => 22, 'down' => 0],
            'WT' => ['pop' => 12, 'ready' => 12, 'down' => 0],
            'EX Small' => ['pop' => 11, 'ready' => 11, 'down' => 0],
        ];

        $operatorStatus = [
            'EX Big' => ['hadir' => 16, 'operation' => 14, 'spare' => 2],
            'HD OB' => ['hadir' => 93, 'operation' => 90, 'spare' => 3],
            'HD MV' => ['hadir' => 12, 'operation' => 10, 'spare' => 2],
            'MG' => ['hadir' => 19, 'operation' => 18, 'spare' => 1],
            'BD' => ['hadir' => 8, 'operation' => 7, 'spare' => 1],
            'WT' => ['hadir' => 4, 'operation' => 4, 'spare' => 0],
            'EX Small' => ['hadir' => 7, 'operation' => 7, 'spare' => 0],
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

        foreach ($unitStatus as $type => &$unit) {
            $pop = is_numeric($unit['pop'] ?? null) ? (int)$unit['pop'] : 0;
            $down = is_numeric($unit['down'] ?? null) ? (int)$unit['down'] : 0;
            $unit['ready'] = max(0, $pop - $down);
        }
        unset($unit);

        foreach ($carrySource['operators'] ?? [] as $operator) {
            $type = trim((string)($operator['type'] ?? ''));

            if ($type !== '' && array_key_exists($type, $operatorStatus)) {
                $hadir = is_numeric($operator['hadir'] ?? null) ? (int)$operator['hadir'] : 0;
                $operation = is_numeric($operator['operation'] ?? null) ? (int)$operator['operation'] : 0;

                $operatorStatus[$type] = [
                    'hadir' => $hadir,
                    'operation' => $operation,
                    'spare' => max(0, $hadir - $operation),
                ];
            }
        }

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
            'status' => 'nullable|in:draft',
            'base_history_id' => 'nullable|integer',
            'details' => 'sometimes|array',
            'details.*.dt_actual' => 'required|integer|min:0',
            'details.*.dt_actual_assignment' => 'required|integer|min:0',
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

        $units = collect($request->input('units', []))
            ->map(function ($unit) {
                $pop = max(0, (int)($unit['pop'] ?? 0));
                $down = max(0, (int)($unit['down'] ?? 0));

                $unit['pop'] = $pop;
                $unit['down'] = $down;
                $unit['ready'] = max(0, $pop - $down);

                return $unit;
            })
            ->values()
            ->all();

        $operators = collect($request->input('operators', []))
            ->map(function ($operator) {
                $hadir = max(0, (int)($operator['hadir'] ?? 0));
                $operation = max(0, (int)($operator['operation'] ?? 0));

                return [
                    'type' => trim((string)($operator['type'] ?? '')),
                    'hadir' => $hadir,
                    'operation' => $operation,
                    'spare' => max(0, $hadir - $operation),
                ];
            })
            ->filter(fn($operator) => $operator['type'] !== '')
            ->values()
            ->all();

        // Simpan satu snapshot resmi per jam. Nilai ini TIDAK berubah ketika
        // ritasi baru masuk setelah laporan disimpan, sampai report di-edit dan disimpan lagi.
        $slotStart = Carbon::createFromFormat(
            'Y-m-d H:i',
            $request->report_date.' '.$request->hour_start,
            'Asia/Makassar'
        );
        if ($shiftNo === 7 && $startHour < 7) {
            $slotStart->addDay();
        }
        $slotEnd = $slotStart->copy()->addHour();
        // Ambil satu hasil agregasi untuk per EX dan total jam sekaligus,
        // supaya saat snapshot dibuat, jumlah di bagian atas dan bawah identik.
        $snapshotByExMaterial = $this->actualTripsByExMaterialForWindow($slotStart, $slotEnd);

        $productivitySnapshot = $this->operationalStatusActualPerExForHour(
            $request->report_date,
            $shiftNo,
            $startHour
        );
        $obTrips = 0;
        $mudTrips = 0;
        foreach ($snapshotByExMaterial as $materials) {
            $obTrips += (int)($materials['OB']['trips'] ?? 0);
            $mudTrips += (int)($materials['MUD']['trips'] ?? 0);
        }

        // Nilai readonly/hidden di Form bisa berasal dari browser yang masih lama.
        // Hitung ulang di server agar snapshot selalu sesuai query yang sama.
        $submittedDetails = collect($request->input('details', []))
            ->map(function ($detail) use ($snapshotByExMaterial, $productivitySnapshot) {
                $loaderId = $this->normalizeReportLoaderId($detail['vhc_id'] ?? '');
                $materials = $snapshotByExMaterial[$loaderId] ?? [];
                $ob = (int)($materials['OB']['trips'] ?? 0);
                $mud = (int)($materials['MUD']['trips'] ?? 0);
                $material = $ob + $mud > 0
                    ? ($mud > $ob ? 'MUD' : 'OB')
                    : $this->inferMaterial($detail);
                $detail['material'] = $material;
                $detail['trip_actual'] = $ob + $mud;
                // Nilai Actual HD sengaja tidak dihitung ulang dari ritasi:
                // CCR boleh mengoreksinya khusus untuk draft jam ini.
                $detail['dt_actual'] = (int)$detail['dt_actual'];
                $detail['dt_actual_assignment'] = (int)$detail['dt_actual_assignment'];
                $detail['dt_actual_focus'] = (int)($materials['OB']['hd'] ?? 0)
                    + (int)($materials['MUD']['hd'] ?? 0);

                $productivity = $productivitySnapshot[$loaderId][$material] ?? null;

                // Actual hanya dari procedure hourly; tidak ada fallback Trip x 42/30.
                $detail['pdty_actual'] = $productivity
                    ? (int) round((float)($productivity['actual'] ?? 0))
                    : 0;

                // Plan selalu dihitung dari Trip Plan.
                $tripPlan = is_numeric($detail['trip_plan'] ?? null)
                    ? (float)$detail['trip_plan']
                    : null;

                $detail['pdty_plan'] = $tripPlan !== null
                    ? (int) round($tripPlan * ($material === 'MUD' ? 30 : 42))
                    : null;

                return $detail;
            })->values()->all();

        $obActualVolume = collect($productivitySnapshot)->sum(
            fn($materials) => (float)($materials['OB']['actual'] ?? 0)
        );
        $mudActualVolume = collect($productivitySnapshot)->sum(
            fn($materials) => (float)($materials['MUD']['actual'] ?? 0)
        );

        $request->merge([
            'details' => $submittedDetails,
            'units' => $units,
            'operators' => $operators,
            'status' => 'draft',
            'ritation_snapshot' => [
                'ob_trip' => $obTrips,
                'mud_trip' => $mudTrips,
                'ob_volume' => (int) round($obActualVolume),
                'mud_volume' => (int) round($mudActualVolume),
                'loader_material_trips' => $snapshotByExMaterial,
                'source' => 'focus.dbo.PRD_RITATION',
                'classification' => 'LOD_MAT_ID',
                'pdty_actual_source' => 'focus_reporting.dbo.APP_GET_OPERATIONAL_STATUS_PER_EX_HOUR',
                'pdty_plan_formula' => 'OB: trip_plan*42; MUD: trip_plan*30',
                'captured_at' => Carbon::now('Asia/Makassar')->toDateTimeString(),
            ],
        ]);

        $model = new OperationalStatusHistory();

        $history = $model->getConnection()->transaction(function () use ($request) {
            $editingId = $request->filled('base_history_id')
                ? (int)$request->base_history_id
                : null;

            $slotQuery = OperationalStatusHistory::query()
                ->whereDate('REPORT_DATE', $request->report_date)
                ->where('SHIFT_NO', (int)$request->shift_no)
                ->where('IS_ACTIVE', 1)
                ->whereRaw(
                    'TRY_CONVERT(TIME(0), HOUR_START) = TRY_CONVERT(TIME(0), ?)',
                    [$request->hour_start]
                )
                ->whereRaw(
                    'TRY_CONVERT(TIME(0), HOUR_END) = TRY_CONVERT(TIME(0), ?)',
                    [$request->hour_end]
                );

            $existingSlot = $slotQuery->lockForUpdate()->first();

            if ($editingId) {
                $history = OperationalStatusHistory::query()
                    ->where('IS_ACTIVE', 1)
                    ->lockForUpdate()
                    ->findOrFail($editingId);

                if ($existingSlot && (int)$existingSlot->ID !== (int)$history->ID) {
                    throw ValidationException::withMessages([
                        'hour_start' => 'Data aktif pada tanggal dan jam tersebut sudah ada. Silakan edit data yang sudah ada.',
                    ]);
                }

                $history->REPORT_DATE = $request->report_date;
                $history->SHIFT_NO = (int)$request->shift_no;
                $history->HOUR_START = $request->hour_start;
                $history->HOUR_END = $request->hour_end;
                $history->STATUS = 'draft';
                $history->PAYLOAD_JSON = json_encode(
                    $request->except('_token'),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                $history->IS_ACTIVE = 1;
                $history->save();

                return $history;
            }

            if ($existingSlot) {
                throw ValidationException::withMessages([
                    'hour_start' => 'Data pada tanggal dan jam tersebut sudah ada. Tidak dapat membuat data baru; silakan gunakan fitur Edit.',
                ]);
            }

            $latestVersion = OperationalStatusHistory::query()
                ->whereDate('REPORT_DATE', $request->report_date)
                ->where('SHIFT_NO', (int)$request->shift_no)
                ->whereRaw(
                    'TRY_CONVERT(TIME(0), HOUR_START) = TRY_CONVERT(TIME(0), ?)',
                    [$request->hour_start]
                )
                ->whereRaw(
                    'TRY_CONVERT(TIME(0), HOUR_END) = TRY_CONVERT(TIME(0), ?)',
                    [$request->hour_end]
                )
                ->lockForUpdate()
                ->max('VERSION');

            return OperationalStatusHistory::create([
                'REPORT_DATE' => $request->report_date,
                'SHIFT_NO' => (int)$request->shift_no,
                'HOUR_START' => $request->hour_start,
                'HOUR_END' => $request->hour_end,
                'VERSION' => ((int)$latestVersion) + 1,
                'STATUS' => 'draft',
                'PAYLOAD_JSON' => json_encode(
                    $request->except('_token'),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'BASE_HISTORY_ID' => null,
                'CREATED_BY' => optional(Auth::user())->name ?? optional(Auth::user())->nik ?? null,
                'IS_ACTIVE' => 1,
            ]);
        });

        return redirect()
            ->route('operational-status.edit-history', ['id' => $history->getKey()])
            ->with('success', 'Operational Status berhasil disimpan.');
    }

    public function destroy($id)
    {
        $history = OperationalStatusHistory::query()
            ->where('IS_ACTIVE', 1)
            ->findOrFail($id);

        $reportDate = Carbon::parse($history->REPORT_DATE)->format('Y-m-d');
        $shiftNo = (int)$history->SHIFT_NO;
        $hourStart = Carbon::parse($history->HOUR_START)->format('H:i');

        $history->IS_ACTIVE = 0;
        $history->save();

        return redirect()
            ->route('operational-status.index', [
                'date' => $reportDate,
                'shift' => $shiftNo,
                'hour_start' => $hourStart,
            ])
            ->with('success', 'Data Operational Status berhasil dihapus.');
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
            ->where('IS_ACTIVE', 1)
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

        $rows = OperationalStatusHistory::whereIn('ID', $request->ids)->where('IS_ACTIVE', 1)->get();

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

    public function sendWhatsappImage(Request $request)
    {
        $request->validate([
            'photo' => 'required|file|mimes:png,jpg,jpeg,webp|max:15360',
            'caption' => 'nullable|string|max:3000',
        ]);

        $apiUrl = config('services.whatsapp.api_url');
        $apiKey = config('services.whatsapp.api_key');
        $groupId = config('services.whatsapp.group_id');

        if (!$apiUrl || !$apiKey || !$groupId) {
            return response()->json([
                'message' => 'Konfigurasi WhatsApp API belum lengkap.',
            ], 500);
        }

        $photo = $request->file('photo');

        try {
            $response = Http::timeout(60)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->attach(
                    'photo',
                    file_get_contents($photo->getRealPath()),
                    $photo->getClientOriginalName(),
                    ['Content-Type' => $photo->getMimeType() ?: 'image/png']
                )
                ->post($apiUrl, [
                    'groupId' => $groupId,
                    'caption' => $request->input('caption', ''),
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'WhatsApp API menolak pengiriman gambar.',
                    'upstream_status' => $response->status(),
                    'upstream_response' => $response->json() ?: $response->body(),
                ], 502);
            }

            return response()->json([
                'success' => true,
                'message' => 'Report berhasil dikirim ke grup WhatsApp.',
                'data' => $response->json() ?: $response->body(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Tidak dapat terhubung ke WhatsApp API.',
            ], 502);
        }
    }

    public function whatsappPreview(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|distinct',
            'include_summary' => 'nullable|boolean',
        ]);

        $rows = OperationalStatusHistory::whereIn('ID', $request->ids)->where('IS_ACTIVE', 1)->get();

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
        OperationalStatusHistory::where('IS_ACTIVE', 1)->findOrFail($id);

        return redirect()->route('operational-status.index', [
            'history_id' => $id,
        ]);
    }

    private function activeHistoryForSlot(
        string $reportDate,
        int $shiftNo,
        string $hourStart,
        string $hourEnd
    ) {
        return OperationalStatusHistory::query()
            ->whereDate('REPORT_DATE', $reportDate)
            ->where('SHIFT_NO', $shiftNo)
            ->where('IS_ACTIVE', 1)
            ->whereRaw(
                'TRY_CONVERT(TIME(0), HOUR_START) = TRY_CONVERT(TIME(0), ?)',
                [$hourStart]
            )
            ->whereRaw(
                'TRY_CONVERT(TIME(0), HOUR_END) = TRY_CONVERT(TIME(0), ?)',
                [$hourEnd]
            )
            ->orderByDesc('VERSION')
            ->orderByDesc('ID')
            ->first();
    }

    private function latestCarryHistoryBefore(Carbon $targetDateTime)
    {
        $historyStartExpression = "
            DATEADD(
                DAY,
                CASE
                    WHEN SHIFT_NO = 7
                         AND DATEPART(HOUR, TRY_CONVERT(TIME(0), HOUR_START)) < 7
                    THEN 1
                    ELSE 0
                END,
                DATEADD(
                    MINUTE,
                    DATEDIFF(
                        MINUTE,
                        CAST('00:00:00' AS TIME),
                        TRY_CONVERT(TIME(0), HOUR_START)
                    ),
                    CAST(REPORT_DATE AS DATETIME2)
                )
            )
        ";

        return OperationalStatusHistory::query()
            ->where('IS_ACTIVE', 1)
            ->whereRaw(
                $historyStartExpression.' < CAST(? AS DATETIME2)',
                [$targetDateTime->format('Y-m-d H:i:s')]
            )
            ->orderByRaw($historyStartExpression.' DESC')
            ->orderByDesc('VERSION')
            ->orderByDesc('ID')
            ->first();
    }

    private function latestShiftHistories(string $reportDate, int $shiftNo)
    {
        return OperationalStatusHistory::whereDate('REPORT_DATE', $reportDate)
            ->where('SHIFT_NO', $shiftNo)
            ->where('IS_ACTIVE', 1)
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

        $ordered = collect($histories)
            ->sortBy(function ($history) {
                $timestamp = $history->UPDATED_AT ?? $history->CREATED_AT ?? null;
                return $timestamp ? Carbon::parse($timestamp)->timestamp : (int)$history->ID;
            })
            ->values();

        foreach ($ordered as $history) {
            $payload = json_decode($history->PAYLOAD_JSON, true) ?: [];

            foreach (($payload['hourly'] ?? []) as $item) {
                if (!isset($item['hour'])) {
                    continue;
                }

                $hourKey = str_pad((int)$item['hour'], 2, '0', STR_PAD_LEFT);

                if (!$this->hourBelongsToShift((int)$hourKey, (int)$history->SHIFT_NO)) {
                    continue;
                }

                /*
                 * Blank juga disimpan. Jadi jika user sengaja menghapus
                 * keterangan lama, nilai kosong dari penyimpanan terbaru
                 * benar-benar menggantikan keterangan sebelumnya.
                 */
                $remarks[$hourKey] = trim((string)($item['remark'] ?? ''));
            }
        }

        return $remarks;
    }

    private function completeHourlyRowsForForm(
        $dashboardRows,
        string $reportDate,
        int $shiftNo,
        int $selectedHour,
        array $savedRemarks = []
    ) {
        $dashboardByHour = collect($dashboardRows)
            ->filter(fn($row) => $this->hourBelongsToShift((int)$row->HOUR, $shiftNo))
            ->keyBy(fn($row) => str_pad((int)$row->HOUR, 2, '0', STR_PAD_LEFT));

        $actualByHour = $this->actualTripTotalsByHour(
            $reportDate,
            $shiftNo,
            collect()
        );

        $selectedPosition = $this->shiftPosition($selectedHour, $shiftNo);
        $productionCum = 0.0;
        $planCum = 0.0;
        $result = collect();

        foreach ($this->shiftHours($shiftNo) as $position => $hour) {
            if ($position > $selectedPosition) {
                break;
            }

            $hourKey = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $source = $dashboardByHour->get($hourKey);

            $obTrips = (int)($actualByHour[$hourKey]['OB'] ?? 0);
            $mudTrips = (int)($actualByHour[$hourKey]['MUD'] ?? 0);

            // Volume Produksi harus sama dengan total P.DTY Actual seluruh EX
            // pada jam dan material yang sama.
            $hourlyProductivity = $this->operationalStatusActualPerExForHour(
                $reportDate,
                $shiftNo,
                $hour
            );

            $production = collect($hourlyProductivity)->sum(
                fn($materials) => (float)($materials['OB']['actual'] ?? 0)
            );
            $productionMud = collect($hourlyProductivity)->sum(
                fn($materials) => (float)($materials['MUD']['actual'] ?? 0)
            );

            $plan = $source && $source->PLAN_PRODUCTION !== null
                ? (float)$source->PLAN_PRODUCTION
                : null;

            $productionCum += $production;

            if ($plan !== null) {
                $planCum += $plan;
            }

            $row = (object)[
                'NUM' => $source->NUM ?? ($position + 1),
                'PIT' => 'ALL PIT',
                'HOUR' => $hourKey,
                'SORT' => $source->SORT ?? $position,
                'PRODUCTION' => $production,
                'PLAN_PRODUCTION' => $plan,
                'ACH' => $plan && $plan > 0 ? $production / $plan : 0,
                'PRODUCTION_CUM' => $productionCum,
                'PLAN_PRODUCTION_CUM' => $planCum,
                'ACH_CUM' => $planCum > 0 ? $productionCum / $planCum : 0,
                'PRODUCTION_MD' => $productionMud,
                'AVG_DISTANCE_OB' => (float)($source->AVG_DISTANCE_OB ?? 0),
                'CREATED_TIME' => $source->CREATED_TIME ?? null,
                'SAVED_REMARK' => array_key_exists($hourKey, $savedRemarks)
                    ? $savedRemarks[$hourKey]
                    : (string)($source->SAVED_REMARK ?? ''),
            ];

            $result->push($row);
        }

        return $result->values();
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

    /**
     * Rata-rata jarak hauling untuk ritasi dengan LOD_MAT_ID = OB.
     * Material diambil langsung dari sumber PRD_RITATION, bukan nama disposal.
     */
    private function hourlyAverageObDistance(string $reportDate, int $shiftNo): array
    {
        return DB::connection('focus')
            ->table('dbo.PRD_RITATION')
            ->selectRaw('DATEPART(HOUR, OPR_REPORTTIME) AS HOUR_KEY')
            ->selectRaw('ROUND(AVG(CAST(RIT_HAULDISTANCE AS FLOAT)) / 1000.0, 2) AS AVG_DISTANCE_OB')
            ->where('OPR_SHIFTDATE', $reportDate)
            ->where('OPR_SHIFTNO', $shiftNo)
            ->whereRaw("UPPER(LTRIM(RTRIM(LOD_MAT_ID))) = 'OB'")
            ->whereNotNull('RIT_HAULDISTANCE')
            ->where('RIT_HAULDISTANCE', '>=', 500)
            ->groupByRaw('DATEPART(HOUR, OPR_REPORTTIME)')
            ->get()
            ->mapWithKeys(function ($row) {
                $hourKey = str_pad((int)$row->HOUR_KEY, 2, '0', STR_PAD_LEFT);
                return [$hourKey => round((float)($row->AVG_DISTANCE_OB ?? 0), 2)];
            })
            ->all();
    }

    /**
     * Normalisasi kode EX agar "EX265", " ex265 " tetap cocok.
     */
    private function normalizeLoaderId($loaderId): string
    {
        return preg_replace('/\s+/', '', strtoupper(trim((string)$loaderId)));
    }

    /**
     * Padankan identitas loader riwayat, fleet dan snapshot.
     */
    private function normalizeReportLoaderId($loaderId): string
    {
        $id = $this->normalizeLoaderId($loaderId);
        return preg_match('/^[0-9]+$/', $id) ? 'EX'.$id : $id;
    }

    /**
     * Ritasi actual tiap EX pada satu slot jam: [start, end).
     * Dipakai juga ketika edit history agar field TRIP_ACTUAL tidak membaca 0 lama.
     *
     * CATATAN: sumber ini adalah PRD_RITATION, sesuai koneksi controller lama.
     * Bila telemetry FROM_UNIT berasal dari tabel lain, perlu mapping sumbernya.
     */
    /**
     * Satu sumber transaksi per jam untuk rekap dan Prty Aktual per EX.
     * OB: COUNT(*) × 42 BCM; MD: COUNT(*) × 30 M3.
     * __UNASSIGNED__ menampung loader kosong agar rekonsiliasi tetap utuh.
     */
    /**
     * P.DTY Actual per EX/material untuk satu jam.
     * Sumber: dbo.APP_GET_OPERATIONAL_STATUS_PER_EX_HOUR.
     *
     * Procedure menerima REPORT_DATE shift, SHIFT_NO, dan jam laporan.
     * Shift malam 00-06 otomatis diarahkan ke tanggal kalender berikutnya
     * di dalam procedure.
     */
    private function operationalStatusActualPerExForHour(
        string $reportDate,
        int $shiftNo,
        int $hour
    ): array {
        static $cache = [];

        $cacheKey = $reportDate.'|'.$shiftNo.'|'.$hour;

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $rows = DB::connection('focus_reporting')->select(
            'SET NOCOUNT ON;
             EXEC dbo.APP_GET_OPERATIONAL_STATUS_PER_EX_HOUR
             @REPORT_DATE = ?, @SHIFT_NO = ?, @HOUR = ?',
            [$reportDate, $shiftNo, $hour]
        );

        $result = [];

        foreach ($rows as $row) {
            $loaderId = $this->normalizeReportLoaderId($row->LOD_LOADERID ?? '');
            if ($loaderId === '') {
                continue;
            }

            $material = strtoupper(trim((string)($row->MATERIAL ?? 'OB')));
            $material = $material === 'MUD' ? 'MUD' : 'OB';

            $result[$loaderId][$material] = [
                'actual' => (float)($row->PRODUCTION_ACTUAL ?? 0),
                'trips' => (int)($row->TRIP_ACTUAL ?? 0),
                'hd' => (int)($row->HD_ACTUAL ?? 0),
                'distance' => $row->AVG_DISTANCE_KM !== null
                    ? (float)$row->AVG_DISTANCE_KM
                    : null,
                'pit' => $row->PIT ?? null,
                'equ_group' => $row->EQU_GROUPID ?? null,
            ];
        }

        $cache[$cacheKey] = $result;

        return $result;
    }

    private function actualTripsByExMaterialForWindow(Carbon $start, Carbon $end): array
    {
        $records = DB::connection('focus')
            ->table('dbo.PRD_RITATION')
            ->select('LOD_LOADERID')
            ->selectRaw('UPPER(LTRIM(RTRIM(LOD_MAT_ID))) AS MAT_CODE')
            ->selectRaw('COUNT(*) AS TRIP_COUNT')
            ->selectRaw('COUNT(DISTINCT VHC_ID) AS HD_ACTUAL')
            ->selectRaw('SUM(CASE WHEN RIT_HAULDISTANCE >= 500 THEN CAST(RIT_HAULDISTANCE AS FLOAT) ELSE 0 END) AS HAUL_METERS_VALID')
            ->selectRaw('SUM(CASE WHEN RIT_HAULDISTANCE >= 500 THEN 1 ELSE 0 END) AS HAUL_COUNT_VALID')
            ->where('OPR_REPORTTIME', '>=', $start->format('Y-m-d H:i:s'))
            ->where('OPR_REPORTTIME', '<', $end->format('Y-m-d H:i:s'))
            ->whereRaw("UPPER(LTRIM(RTRIM(LOD_MAT_ID))) IN ('OB', 'MD')")
            ->groupBy('LOD_LOADERID')
            ->groupByRaw('UPPER(LTRIM(RTRIM(LOD_MAT_ID)))')
            ->get();

        $byEx = [];
        foreach ($records as $record) {
            $loaderId = $this->normalizeReportLoaderId($record->LOD_LOADERID);
            $loaderId = $loaderId !== '' ? $loaderId : '__UNASSIGNED__';
            $material = $record->MAT_CODE === 'MD' ? 'MUD' : 'OB';
            $byEx[$loaderId][$material]['trips'] =
                (int)($byEx[$loaderId][$material]['trips'] ?? 0) + (int)$record->TRIP_COUNT;
            $byEx[$loaderId][$material]['hd'] =
                (int)($byEx[$loaderId][$material]['hd'] ?? 0) + (int)$record->HD_ACTUAL;
            // Simpan jumlah jarak dalam meter dan jumlah ritasi dengan jarak valid.
            // AVG baru dihitung setelah loader ID dinormalisasi untuk menghindari
            // rata-rata dari rata-rata jika variasi penulisan EX digabung.
            $byEx[$loaderId][$material]['haul_meters_valid'] =
                (float)($byEx[$loaderId][$material]['haul_meters_valid'] ?? 0) + (float)($record->HAUL_METERS_VALID ?? 0);
            $byEx[$loaderId][$material]['haul_count_valid'] =
                (int)($byEx[$loaderId][$material]['haul_count_valid'] ?? 0) + (int)($record->HAUL_COUNT_VALID ?? 0);
        }
        return $byEx;
    }

    private function actualTripsByLoaderForWindow(Carbon $start, Carbon $end): array
    {
        return DB::connection('focus')
            ->table('dbo.PRD_RITATION')
            ->select('LOD_LOADERID')
            ->selectRaw('COUNT(*) AS TRIP_ACTUAL')
            ->where('OPR_REPORTTIME', '>=', $start->format('Y-m-d H:i:s'))
            ->where('OPR_REPORTTIME', '<', $end->format('Y-m-d H:i:s'))
            ->whereNotNull('LOD_LOADERID')
            ->where('LOD_LOADERID', '<>', '')
            ->whereRaw("UPPER(LTRIM(RTRIM(LOD_MAT_ID))) IN ('OB', 'MD')")
            ->groupBy('LOD_LOADERID')
            ->get()
            ->reduce(function (array $counts, $row) {
                $loaderId = $this->normalizeLoaderId($row->LOD_LOADERID);
                if ($loaderId !== '') {
                    $counts[$loaderId] = ($counts[$loaderId] ?? 0) + (int)$row->TRIP_ACTUAL;
                }
                return $counts;
            }, []);
    }

    /**
     * Rekap ritasi aktual sesuai LOD_MAT_ID (bukan LOC_NAME / DISPOSAL / mapping EX).
     * OB = kode 'OB', MUD = kode 'MD'. Kode lainnya tidak dihitung.
     * Menggunakan window [start, end) dari OPR_REPORTTIME, termasuk transaksi
     * terlambat yang SYS_CREATEDAT-nya masuk setelah jam laporan.
     *
     * $histories tetap di signature demi kompatibilitas pemanggil sebelumnya,
     * tetapi tidak dipakai sebagai sumber trip/material.
     */
    private function actualTripTotalsByHour(
        string $reportDate,
        int $shiftNo,
        $histories,
        ?Carbon $windowStart = null,
        ?Carbon $windowEnd = null
    ): array {
        $start = $windowStart ?? Carbon::createFromFormat(
            'Y-m-d H:i',
            $reportDate . ($shiftNo === 6 ? ' 07:00' : ' 19:00'),
            'Asia/Makassar'
        );
        $end = $windowEnd ?? $start->copy()->addHours(12);

        $records = DB::connection('focus')
            ->table('dbo.PRD_RITATION')
            ->selectRaw('DATEPART(HOUR, OPR_REPORTTIME) AS HOUR_KEY')
            ->selectRaw('UPPER(LTRIM(RTRIM(LOD_MAT_ID))) AS MAT_CODE')
            ->selectRaw('COUNT(*) AS TRIP_COUNT')
            ->where('OPR_REPORTTIME', '>=', $start->format('Y-m-d H:i:s'))
            ->where('OPR_REPORTTIME', '<', $end->format('Y-m-d H:i:s'))
            ->whereRaw("UPPER(LTRIM(RTRIM(LOD_MAT_ID))) IN ('OB', 'MD')")
            ->groupByRaw('DATEPART(HOUR, OPR_REPORTTIME), UPPER(LTRIM(RTRIM(LOD_MAT_ID)))')
            ->get();

        $totals = [];
        foreach ($records as $record) {
            $hourKey = str_pad((int)$record->HOUR_KEY, 2, '0', STR_PAD_LEFT);
            $material = $record->MAT_CODE === 'MD' ? 'MUD' : 'OB';
            $totals[$hourKey][$material] = ($totals[$hourKey][$material] ?? 0)
                + (int)$record->TRIP_COUNT;
        }

        return $totals;
    }

    /**
     * HD aktual UNIK per jam dan material, langsung dari transaksi FOCUS.
     * Tidak menjumlahkan COUNT(DISTINCT VHC_ID) dari per-EX karena satu HD
     * bisa melayani lebih dari satu loader di jam yang sama.
     *
     * OB = LOD_MAT_ID 'OB'; MUD = LOD_MAT_ID 'MD'.
     * Window [start,end) dipilih berdasarkan shift agar tepat lintas tengah malam.
     */
    private function actualHdTotalsByHour(string $reportDate, int $shiftNo): array
    {
        $start = Carbon::createFromFormat(
            'Y-m-d H:i',
            $reportDate . ($shiftNo === 6 ? ' 07:00' : ' 19:00'),
            'Asia/Makassar'
        );
        $end = $start->copy()->addHours(12);

        $rows = DB::connection('focus')
            ->table('dbo.PRD_RITATION')
            ->selectRaw('DATEPART(HOUR, OPR_REPORTTIME) AS HOUR_KEY')
            ->selectRaw('UPPER(LTRIM(RTRIM(LOD_MAT_ID))) AS MAT_CODE')
            ->selectRaw('COUNT(DISTINCT VHC_ID) AS HD_UNIK')
            ->where('OPR_REPORTTIME', '>=', $start->format('Y-m-d H:i:s'))
            ->where('OPR_REPORTTIME', '<', $end->format('Y-m-d H:i:s'))
            ->whereRaw("UPPER(LTRIM(RTRIM(LOD_MAT_ID))) IN ('OB', 'MD')")
            ->groupByRaw('DATEPART(HOUR, OPR_REPORTTIME), UPPER(LTRIM(RTRIM(LOD_MAT_ID)))')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $hour = str_pad((int)$row->HOUR_KEY, 2, '0', STR_PAD_LEFT);
            $material = $row->MAT_CODE === 'MD' ? 'MUD' : 'OB';
            $result[$hour][$material] = (int)$row->HD_UNIK;
        }

        return $result;
    }

    private function buildReportData($rows, bool $includeSummary): array
    {
        $first = $rows->first();
        $last = $rows->last();
        $shiftNo = (int)$first->SHIFT_NO;
        $reportDate = Carbon::parse($first->REPORT_DATE)->format('Y-m-d');
        $avgDistanceObByHour = $this->hourlyAverageObDistance($reportDate, $shiftNo);
        // HD dasar tetap dihitung dari FOCUS. Jika DT Actual diedit dari
        // assignment, terapkan selisihnya terhadap HD FOCUS saat draft disimpan.
        $hdAktualByHour = $this->actualHdTotalsByHour($reportDate, $shiftNo);
        $hdAdjustmentsByHour = [];
        foreach ($rows as $historyRow) {
            $hourKey = substr((string)$historyRow->HOUR_START, 0, 2);
            $payload = json_decode($historyRow->PAYLOAD_JSON, true) ?: [];
            $adjustments = ['OB' => 0, 'MUD' => 0];
            foreach ($payload['details'] ?? [] as $detail) {
                if (!isset($detail['dt_actual_assignment'], $detail['dt_actual_focus'])
                    || !is_numeric($detail['dt_actual_assignment'])
                    || !is_numeric($detail['dt_actual_focus'])
                    || !is_numeric($detail['dt_actual'] ?? null)
                    || (int)$detail['dt_actual'] === (int)$detail['dt_actual_assignment']) {
                    continue;
                }
                $material = $this->inferMaterial($detail);
                $adjustments[$material] += (int)$detail['dt_actual'] - (int)$detail['dt_actual_focus'];
            }
            $hdAdjustmentsByHour[$hourKey] = $adjustments;
        }
        // V12: Preview WA/PNG selalu mengambil ritasi aktual terkini dari FOCUS,
        // termasuk transaksi yang masuk setelah history disimpan. History resmi
        // di database TIDAK diubah oleh preview; snapshot diperbarui saat CCR
        // membuka Edit dan menekan Simpan.
        $actualTripTotals = $this->actualTripTotalsByHour($reportDate, $shiftNo, $rows);
        $hourlyRows = [];

        $selectedPositions = collect($rows)
            ->map(fn($history) => $this->shiftPosition((int)substr($history->HOUR_START, 0, 2), $shiftNo))
            ->sort()
            ->values();

        $firstPosition = $selectedPositions->isNotEmpty() ? (int)$selectedPositions->first() : 0;
        $lastPosition = $selectedPositions->isNotEmpty() ? (int)$selectedPositions->last() : 0;
        $reportRemarks = $this->historicalHourlyRemarks($rows);

        foreach ($this->shiftHours($shiftNo) as $position => $hour) {
            if ($position < $firstPosition || $position > $lastPosition) {
                continue;
            }

            $hourKey = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $obTrip = (int)($actualTripTotals[$hourKey]['OB'] ?? 0);
            $mudTrip = (int)($actualTripTotals[$hourKey]['MUD'] ?? 0);
            $nextHour = ($hour + 1) % 24;

            // Volume OB/MUD = jumlah P.DTY Actual seluruh EX pada jam tersebut.
            $hourlyProductivity = $this->operationalStatusActualPerExForHour(
                $reportDate,
                $shiftNo,
                $hour
            );

            $obVolume = collect($hourlyProductivity)->sum(
                fn($materials) => (float)($materials['OB']['actual'] ?? 0)
            );
            $mudVolume = collect($hourlyProductivity)->sum(
                fn($materials) => (float)($materials['MUD']['actual'] ?? 0)
            );

            $hourlyRows[] = [
                'hour' => sprintf('%02d:00-%02d:00', $hour, $nextHour),
                'ob_hd' => max(0, (int)($hdAktualByHour[$hourKey]['OB'] ?? 0)
                    + (int)($hdAdjustmentsByHour[$hourKey]['OB'] ?? 0)),
                'ob_trip' => $obTrip,
                'ob_volume' => (int) round($obVolume),
                'avg_distance_ob' => (float)($avgDistanceObByHour[$hourKey] ?? 0),
                'mud_hd' => max(0, (int)($hdAktualByHour[$hourKey]['MUD'] ?? 0)
                    + (int)($hdAdjustmentsByHour[$hourKey]['MUD'] ?? 0)),
                'mud_trip' => $mudTrip,
                'mud_volume' => (int) round($mudVolume),
                'remark' => trim((string)($reportRemarks[$hourKey] ?? '')),
            ];
        }

        $lastPayload = json_decode($last->PAYLOAD_JSON, true) ?: [];
        // Nilai HD yang disimpan untuk jam terakhir saja. Jangan memakai
        // metadata history sebelumnya karena koreksi tidak boleh terbawa jam.
        $savedActualHdForLastHour = [];
        foreach ($lastPayload['details'] ?? [] as $detail) {
            $loaderId = $this->normalizeReportLoaderId($detail['vhc_id'] ?? '');
            if ($loaderId !== '' && isset($detail['dt_actual_assignment'])
                && is_numeric($detail['dt_actual'] ?? null)) {
                $savedActualHdForLastHour[$loaderId] = max(0, (int)$detail['dt_actual']);
            }
        }

        // Bagian Produksi Perjam hanya menampilkan EX yang mempunyai ritasi/volume
        // pada interval laporan terakhir. Preview mengambil transaksi LIVE FOCUS,
        // bukan daftar EX yang lama dalam snapshot/history.
        $lastHourStart = substr((string) $last->HOUR_START, 0, 5);
        $lastSlotStart = Carbon::createFromFormat(
            'Y-m-d H:i',
            $reportDate.' '.$lastHourStart,
            'Asia/Makassar'
        );
        if ($shiftNo === 7 && (int)substr($lastHourStart, 0, 2) < 7) {
            $lastSlotStart->addDay();
        }
        $lastSlotEnd = $lastSlotStart->copy()->addHour();

        // Satu sumber data untuk header Produksi dan detail EX: FOCUS OPR_REPORTTIME.
        // Histori tetap menjadi sumber Area, Plan, W/D, Remark, Status Unit/Operator.
        $snapshotDetails = $this->actualTripsByExMaterialForWindow($lastSlotStart, $lastSlotEnd);

        $productivityByEx = $this->operationalStatusActualPerExForHour(
            $reportDate,
            $shiftNo,
            (int)$lastSlotStart->format('H')
        );

        // Normalisasi kode EX di snapshot agar EX269 / ex269 / 269 tidak
        // dianggap sebagai tiga loader berbeda pada hasil laporan.
        $activeLoaders = [];
        foreach ($snapshotDetails as $rawLoaderId => $materials) {
            $loaderId = $this->normalizeReportLoaderId($rawLoaderId);
            if ($loaderId === '' || !is_array($materials)) {
                continue;
            }
            foreach (['OB' => 42, 'MUD' => 30] as $material => $factor) {
                $source = $materials[$material] ?? ($material === 'MUD' ? ($materials['MD'] ?? []) : []);
                $trips = (int)($source['trips'] ?? 0);
                if ($trips <= 0) {
                    continue;
                }
                $activeLoaders[$loaderId][$material]['trips'] =
                    (int)($activeLoaders[$loaderId][$material]['trips'] ?? 0) + $trips;
                $activeLoaders[$loaderId][$material]['hd'] =
                    (int)($activeLoaders[$loaderId][$material]['hd'] ?? 0) + (int)($source['hd'] ?? 0);
                $activeLoaders[$loaderId][$material]['haul_meters_valid'] =
                    (float)($activeLoaders[$loaderId][$material]['haul_meters_valid'] ?? 0) + (float)($source['haul_meters_valid'] ?? 0);
                $activeLoaders[$loaderId][$material]['haul_count_valid'] =
                    (int)($activeLoaders[$loaderId][$material]['haul_count_valid'] ?? 0) + (int)($source['haul_count_valid'] ?? 0);
            }
        }

        // Ambil data area/Plan/W-D/Remark dari history terbaru terlebih dahulu.
        // Jika suatu loader sudah tidak ada dalam MATCHING_FLEET saat laporan
        // dibuat tetapi tadi sempat berproduksi, pakai metadata history terdahulu.
        $metadataByLoader = [];
        $addMetadata = function (array $payload) use (&$metadataByLoader): void {
            foreach (($payload['details'] ?? []) as $detail) {
                $loaderId = $this->normalizeReportLoaderId($detail['vhc_id'] ?? '');
                if ($loaderId === '' || $loaderId === '__UNASSIGNED__') {
                    continue;
                }
                if (!isset($metadataByLoader[$loaderId])) {
                    $metadataByLoader[$loaderId] = $detail;
                    continue;
                }
                $currentPit = strtoupper(trim((string)($metadataByLoader[$loaderId]['pit'] ?? '')));
                $candidatePit = strtoupper(trim((string)($detail['pit'] ?? '')));
                if (in_array($currentPit, ['', '-', 'BELUM TERPETAKAN'], true)
                    && !in_array($candidatePit, ['', '-', 'BELUM TERPETAKAN'], true)) {
                    $metadataByLoader[$loaderId]['pit'] = $detail['pit'];
                }
            }
        };

        $addMetadata($lastPayload);
        foreach (collect($rows)->reverse() as $historyRow) {
            $addMetadata(json_decode($historyRow->PAYLOAD_JSON, true) ?: []);
        }

        $missingMetadata = array_diff(array_keys($activeLoaders), array_keys($metadataByLoader));
        if (count($missingMetadata) > 0) {
            // Cari metadata EX di beberapa history sebelum slot terpilih.
            // Penelusuran dibatasi supaya preview tetap ringan.
            $cursor = $lastSlotStart->copy();
            for ($attempt = 0; $attempt < 6 && count($missingMetadata) > 0; $attempt++) {
                $previous = $this->latestCarryHistoryBefore($cursor);
                if (!$previous) {
                    break;
                }
                $addMetadata(json_decode($previous->PAYLOAD_JSON, true) ?: []);
                $missingMetadata = array_diff(array_keys($activeLoaders), array_keys($metadataByLoader));
                $previousStartHour = substr((string)$previous->HOUR_START, 0, 5);
                $previousStart = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    Carbon::parse($previous->REPORT_DATE)->format('Y-m-d').' '.$previousStartHour,
                    'Asia/Makassar'
                );
                if ((int)$previous->SHIFT_NO === 7 && (int)substr($previousStartHour, 0, 2) < 7) {
                    $previousStart->addDay();
                }
                if ($previousStart->greaterThanOrEqualTo($cursor)) {
                    break;
                }
                $cursor = $previousStart;
            }
        }

        // Prioritaskan EX yang tercatat dalam form/history, kemudian loader
        // lain yang punya transaksi aktual. Hanya volume positif yang tampil.
        $loaderOrder = array_values(array_unique(array_merge(
            array_keys($metadataByLoader),
            array_keys($activeLoaders)
        )));
        $detailRows = collect();
        foreach ($loaderOrder as $loaderId) {
            $materials = $activeLoaders[$loaderId] ?? [];
            if (empty($materials)) {
                continue;
            }
            $hdByMaterial = [
                'OB' => (int)($materials['OB']['hd'] ?? 0),
                'MUD' => (int)($materials['MUD']['hd'] ?? 0),
            ];
            if (array_key_exists($loaderId, $savedActualHdForLastHour)) {
                // Form memiliki satu angka HD per EX. Jika EX bekerja di dua
                // material, bagi secara proporsional agar totalnya tetap sama.
                $obWeight = $hdByMaterial['OB'] + $hdByMaterial['MUD'] > 0
                    ? $hdByMaterial['OB']
                    : (int)($materials['OB']['trips'] ?? 0);
                $mudWeight = $hdByMaterial['OB'] + $hdByMaterial['MUD'] > 0
                    ? $hdByMaterial['MUD']
                    : (int)($materials['MUD']['trips'] ?? 0);
                $totalWeight = $obWeight + $mudWeight;
                $savedHd = $savedActualHdForLastHour[$loaderId];
                $hdByMaterial['OB'] = $totalWeight > 0
                    ? (int)round($savedHd * $obWeight / $totalWeight)
                    : $savedHd;
                $hdByMaterial['MUD'] = $savedHd - $hdByMaterial['OB'];
            }
            $detail = $metadataByLoader[$loaderId] ?? [];
            $mapped = !empty($detail);
            $area = preg_replace('/^PIT\s+/i', '', (string)($detail['pit'] ?? 'BELUM TERPETAKAN'));
            if (trim($area) === '' || $area === '-') {
                $area = 'BELUM TERPETAKAN';
            }

            foreach (['OB' => 42, 'MUD' => 30] as $material => $factor) {
                $trips = (int)($materials[$material]['trips'] ?? 0);
                if ($trips <= 0) {
                    continue;
                }
                // Jarak dipakai dari transaksi FOCUS jam yang sama, material sama.
                // Jika tidak ada RIT_HAULDISTANCE >= 500 meter, tampilkan '-'
                // pada Blade, bukan menganggap jarak nyata = nol.
                $validDistanceCount = (int)($materials[$material]['haul_count_valid'] ?? 0);
                $actualDistanceKm = $validDistanceCount > 0
                    ? round((float)($materials[$material]['haul_meters_valid'] ?? 0)
                        / $validDistanceCount / 1000.0, 2)
                    : null;
                $detailRows->push([
                    'material' => $material,
                    'area' => $area,
                    'ex' => $loaderId === '__UNASSIGNED__'
                        ? '-'
                        : preg_replace('/^EX/i', '', $loaderId),
                    'distance' => $actualDistanceKm,
                    'dt_actual' => $hdByMaterial[$material],
                    'dt_plan' => ($detail['dt_plan'] ?? '') !== '' ? $detail['dt_plan'] : '-',
                    'trip_actual' => $trips,
                    'trip_plan' => ($detail['trip_plan'] ?? '') !== '' ? $detail['trip_plan'] : '-',
                    'pdty_actual' => isset($productivityByEx[$loaderId][$material])
                        ? (int) round((float)($productivityByEx[$loaderId][$material]['actual'] ?? 0))
                        : 0,
                    'pdty_plan' => is_numeric($detail['trip_plan'] ?? null)
                        ? (int) round((float)$detail['trip_plan'] * $factor)
                        : '-',
                    'wd' => $detail['wd_status'] ?? '-',
                    'remark' => trim((string)($detail['remark'] ?? ($mapped ? '' : 'Loader belum terdaftar di fleet/history'))),
                ]);
            }
        }
        $detailRows = $detailRows->values();

        $units = collect($lastPayload['units'] ?? [])->map(fn($u) => [
            'type' => $u['type'] ?? '-',
            'pop' => $u['pop'] ?? '-',
            'ready' => $u['ready'] ?? '-',
            'down' => $u['down'] ?? '-',
        ])->values();

        $operatorDefaults = [
            'EX Big' => ['hadir' => 16, 'operation' => 14, 'spare' => 2],
            'HD OB' => ['hadir' => 93, 'operation' => 90, 'spare' => 3],
            'HD MV' => ['hadir' => 12, 'operation' => 10, 'spare' => 2],
            'MG' => ['hadir' => 19, 'operation' => 18, 'spare' => 1],
            'BD' => ['hadir' => 8, 'operation' => 7, 'spare' => 1],
            'WT' => ['hadir' => 4, 'operation' => 4, 'spare' => 0],
            'EX Small' => ['hadir' => 7, 'operation' => 7, 'spare' => 0],
        ];

        foreach ($lastPayload['operators'] ?? [] as $operatorRow) {
            $type = trim((string)($operatorRow['type'] ?? ''));

            if ($type !== '' && array_key_exists($type, $operatorDefaults)) {
                $hadir = is_numeric($operatorRow['hadir'] ?? null) ? (int)$operatorRow['hadir'] : 0;
                $operation = is_numeric($operatorRow['operation'] ?? null) ? (int)$operatorRow['operation'] : 0;

                $operatorDefaults[$type] = [
                    'hadir' => $hadir,
                    'operation' => $operation,
                    'spare' => max(0, $hadir - $operation),
                ];
            }
        }

        $operators = collect($operatorDefaults)->map(function ($values, $type) {
            return [
                'type' => $type,
                'hadir' => $values['hadir'],
                'operation' => $values['operation'],
                'spare' => $values['spare'],
            ];
        })->values();

        $hourlyCollection = collect($hourlyRows);

        $obHdValues = $hourlyCollection
            ->pluck('ob_hd')
            ->filter(fn($value) => is_numeric($value) && (float)$value > 0);

        $mudHdValues = $hourlyCollection
            ->pluck('mud_hd')
            ->filter(fn($value) => is_numeric($value) && (float)$value > 0);

        $totals = [
            'ob_hd' => $obHdValues->count()
                ? (int) round($obHdValues->avg())
                : 0,
            'ob_trip' => $hourlyCollection->sum('ob_trip'),
            'ob_volume' => $hourlyCollection->sum('ob_volume'),

            'mud_hd' => $mudHdValues->count()
                ? (int) round($mudHdValues->avg())
                : 0,
            'mud_trip' => $hourlyCollection->sum('mud_trip'),
            'mud_volume' => $hourlyCollection->sum('mud_volume'),
        ];

        return [
            'report_date' => Carbon::parse($first->REPORT_DATE)->format('Y-m-d'),
            'date_label' => strtoupper(Carbon::parse($first->REPORT_DATE)->locale('id')->translatedFormat('d F Y')),
            'shift_no' => $shiftNo,
            'shift_name' => $shiftNo === 6 ? 'SIANG' : 'MALAM',
            'shift_time' => $shiftNo === 6 ? '06:30-18:30' : '18:30-06:30',
            'latest_hour' => substr($last->HOUR_START, 0, 5).' - '.substr($last->HOUR_END, 0, 5),
            'production_data_source' => 'FOCUS_PRD_RITATION_LIVE',
            'hourly' => $hourlyRows,
            'totals' => $totals,
            'details' => $detailRows,
            'units' => $units,
            'operators' => $operators,
            'notes' => trim((string)($lastPayload['notes'] ?? '')),
            'issues' => trim((string)($lastPayload['issues'] ?? '')),
            'include_summary' => $includeSummary,
        ];
    }
}

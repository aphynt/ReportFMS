<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class P2HController extends Controller
{
    public function index()
    {

        return view('p2h.index');
    }

    public function api(Request $request)
    {
        $shiftDate = !empty($request->tanggalP2H) ? date('Y-m-d', strtotime($request->tanggalP2H)) : null;
        $shiftP2H = $request->input('shiftP2H');
        $shiftNo = in_array((int)$shiftP2H, [6, 7], true) ? (int)$shiftP2H : null;
        $cluster = in_array($request->cluster, ['EX', 'HD', 'MG', 'BD']) ? $request->cluster : null;

        $targetDate = $shiftDate ?? Carbon::today()->format('Y-m-d');

        $checklistIds = DB::connection('p2h')->table('opr_oprchecklist')
            ->select('vhc_id', 'opr_reporttime', 'opr_nrp', 'opr_shiftno', 'opr_shiftdate')
            ->whereDate('opr_shiftdate', $targetDate)
            ->when($shiftNo, fn($q) => $q->where('opr_shiftno', $shiftNo))
            ->when($cluster, fn($q) => $q->where('vhc_id', 'like', $cluster . '%'))
            ->groupBy('vhc_id', 'opr_reporttime', 'opr_nrp', 'opr_shiftno', 'opr_shiftdate')
            ->get();

        $appLoginUnits = DB::connection('FOCUS')->table('app_login')
            ->whereDate('opr_shiftdate', $targetDate)
            ->where('lgn_type', 0)
            ->when($shiftNo, fn($q) => $q->where('opr_shiftno', $shiftNo))
            ->when($cluster, fn($q) => $q->where('vhc_id', 'like', $cluster . '%'))
            ->pluck('vhc_id')
            ->map(fn($v) => strtoupper(trim($v)))
            ->unique()
            ->toArray();

        if ($checklistIds->isEmpty() && empty($appLoginUnits)) {
            return response()->json(['data' => []]);
        }

        $uniqueVhcs = $checklistIds->pluck('vhc_id')->unique()->toArray();
        $oprTimes = $checklistIds->pluck('opr_reporttime')
            ->map(fn($t) => Carbon::parse($t)->format('Y-m-d H:i:s'))
            ->unique()->toArray();

        $notOkCounts = DB::connection('p2h')->table('opr_oprchecklistitem')
            ->select('vhc_id', 'opr_reporttime', DB::raw('count(*) as total_notok'))
            ->whereIn('vhc_id', $uniqueVhcs)
            ->whereIn('opr_reporttime', $checklistIds->pluck('opr_reporttime')->unique()->toArray())
            ->where('checklistval', 0)
            ->groupBy('vhc_id', 'opr_reporttime')
            ->get()
            ->keyBy(fn($item) => trim($item->vhc_id) . '_' . Carbon::parse($item->opr_reporttime)->format('Y-m-d H:i:s'));


        $p2hData = DB::connection('DAILY_REPORT')
            ->table('prd_opr_checklistp2h as p')
            ->whereIn('p.VHC_ID', $uniqueVhcs)
            ->get()
            ->keyBy(fn($row) => strtoupper(trim($row->VHC_ID)));

        $detailData = $checklistIds->map(function ($row) use ($p2hData, $notOkCounts) {
            $vhcId = strtoupper(trim($row->vhc_id));
            if (!$vhcId) return null;

            $prefix = substr($vhcId, 0, 2);
            $jenisUnit = match($prefix) {
                'EX' => 'EX',
                'HD' => 'HD',
                'MG' => 'MG',
                'BD' => 'BD',
                'WT' => 'WT',
                'FT' => 'FT',
                default => 'OTHERS'
            };

            $keyNotOk = $vhcId . '_' . Carbon::parse($row->opr_reporttime)->format('Y-m-d H:i:s');
            $p2h = $p2hData[$vhcId] ?? null;
            $valNotOk = $notOkCounts[$keyNotOk]->total_notok ?? 0;

            return [
                'tanggal' => Carbon::parse($row->opr_shiftdate)->format('Y-m-d'),
                'jenis_unit' => $jenisUnit,
                'shift' => ($row->opr_shiftno == 6) ? 'Siang' : (($row->opr_shiftno == 7) ? 'Malam' : 'Lainnya'),
                'vhc_id' => $vhcId,
                'is_p2h' => !is_null($p2h),
                'temuan' => $valNotOk,
                'mekanik_verified' => !is_null($p2h?->DATEVERIFIED_MEKANIK),
                'foreman_verified' => !is_null($p2h?->DATEVERIFIED_FOREMAN) || !is_null($p2h?->DATEVERIFIED_SUPERVISOR),
            ];
        })->filter()->values();


        $grouped = $detailData->groupBy(['tanggal', 'jenis_unit', 'shift']);
        $summaryResult = [];

        foreach ($grouped as $tanggal => $units) {
            foreach ($units as $jenisUnit => $shifts) {
                foreach ($shifts as $shift => $items) {

                    $unitsInChecklist = $items->pluck('vhc_id')->map(fn($v) => strtoupper(trim($v)))->unique()->toArray();

                    $loginUnitsOfGroup = array_filter($appLoginUnits, function($v) use ($jenisUnit) {
                        $prefix = strtoupper(substr($v, 0, 2));
                        return in_array($jenisUnit, ['EX', 'HD', 'MG', 'BD', 'WT', 'FT'])
                            ? $prefix === $jenisUnit
                            : !in_array($prefix, ['EX', 'HD', 'MG', 'BD', 'WT', 'FT']);
                    });

                    $listBelumP2H = array_values(array_diff($unitsInChecklist, $loginUnitsOfGroup));

                    $unitOperasi = count($unitsInChecklist);
                    $belumP2H = count($listBelumP2H);
                    $sudahP2H = max(0, $unitOperasi - $belumP2H);

                    $unitDefect = $items->where('temuan', '>', 0)->unique('vhc_id');
                    $totalTemuan = $unitDefect->count();

                    $mekanikSudah = $unitDefect->where('mekanik_verified', true)->count();
                    $mekanikBelum = $unitDefect->where('mekanik_verified', false)->count();

                    $pengawasSudah = $items->where('foreman_verified', true)->unique('vhc_id')->count();

                    if ($pengawasSudah > $sudahP2H) {
                        $pengawasSudah = $sudahP2H;
                    }
                    $pengawasBelum = max(0, $sudahP2H - $pengawasSudah);
                    // --------------------------------------------------------

                    $summaryResult[] = [
                        'tanggal' => Carbon::parse($tanggal)->format('d-M-y'),
                        'jenis_unit' => $jenisUnit,
                        'shift' => $shift,
                        'unit_operasi' => $unitOperasi,
                        'p2h_sudah' => $sudahP2H,
                        'p2h_belum' => $belumP2H,
                        'list_belum_p2h' => $listBelumP2H,
                        'temuan' => $totalTemuan,
                        'mekanik_sudah' => $mekanikSudah,
                        'mekanik_belum' => $mekanikBelum,
                        'pengawas_sudah' => $pengawasSudah,
                        'pengawas_belum' => $pengawasBelum,
                        'keterangan' => ''
                    ];
                }
            }
        }

        $summaryResult = collect($summaryResult)->sortBy([
            ['tanggal', 'asc'],
            ['jenis_unit', 'asc'],
            ['shift', 'desc']
        ])->values()->toArray();

        return response()->json(['data' => $summaryResult]);
    }
}

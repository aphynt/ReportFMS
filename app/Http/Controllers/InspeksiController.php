<?php

namespace App\Http\Controllers;

use App\Exports\InspeksiExport;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


class InspeksiController extends Controller
{
    //
    public function index()
    {
        return view('inspeksi.index');
    }

    public function api(Request $request)
    {
        $tanggalInput = $request->input('tanggal');

        $startDate = Carbon::now()->toDateString();
        $endDate   = $startDate;

        if ($tanggalInput) {
            if (str_contains($tanggalInput, 's/d')) {
                [$startDate, $endDate] = array_map('trim', explode('s/d', $tanggalInput));
            } else {
                $startDate = trim($tanggalInput);
                $endDate   = $startDate;
            }
        }

        $shiftInput = $request->input('shift');
        $shift = match ($shiftInput) {
            'Siang' => '1',
            'Malam' => '2',
            default => null,
        };

        try {
            $data = DB::table('SAP_REPORT as sr')
                ->leftJoin('users as us', 'sr.foreman_id', 'us.id')
                ->leftJoin('REF_SHIFT as sh', 'sr.shift', 'sh.id')
                ->leftJoin('REF_AREA as ar', 'sr.area', 'ar.id')
                ->select(
                    'sr.id',
                    'sr.uuid',
                    'sr.created_at',
                    'sr.updated_at',
                    'sr.jam_kejadian',
                    'sh.keterangan as shift',
                    'us.nik as nik_pic',
                    'us.name as pic',
                    'ar.keterangan as area',
                    'sr.temuan',
                    'sr.risiko',
                    'sr.pengendalian',
                    'sr.tindak_lanjut',
                    'sr.tingkat_risiko',
                    'sr.file_temuan',
                    'sr.file_tindakLanjut',
                    DB::raw("CASE WHEN sr.is_finish = 1 THEN 'Close' ELSE 'Open' END as is_finish")
                )
                ->whereBetween(DB::raw('CONVERT(varchar, sr.created_at, 23)'), [$startDate, $endDate])
                ->where('sr.statusenabled', true);

            if ($shift !== null) {
                $data = $data->where('sr.shift', $shift);
            }

            $data = $data->get();

            if ($request->get('export') === 'excel') {

                $today = now()->format('ymd');

                $fileName = "($today) PICA Inspeksi Level IV.xlsx";

                return Excel::download(new InspeksiExport($data), $fileName);
            }

            return response()->json([
                'data'   => $data,
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

}

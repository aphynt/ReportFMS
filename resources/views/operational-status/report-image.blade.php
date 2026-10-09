<div id="ops-report-image" class="report-sheet">
    <div class="report-title">OPERATIONAL STATUS</div>
    <table class="report-meta"><tr><td>Tanggal</td><td>{{ $report['date_label'] }}</td><td>Shift</td><td>{{ $report['shift_name'] }} ({{ $report['shift_time'] }})</td></tr></table>

    <div class="report-section-title"><span>Produksi</span><small>(Unit : bcm)</small></div>
    <table class="report-table production-table">
        <thead>
            <tr><th rowspan="2">Jam</th><th colspan="4">OB</th><th colspan="3">MUD</th><th rowspan="2">Ket.</th></tr>
            <tr><th>Jarak</th><th>HD</th><th>Ritasi</th><th>Volume</th><th>HD</th><th>Ritasi</th><th>Volume</th></tr>
        </thead>
        <tbody>
            @foreach($report['hourly'] as $row)
            <tr>
                <td>{{ $row['hour'] }}</td>
                <td>{{ number_format((float)($row['avg_distance_ob'] ?? 0),2,'.','') }}</td>
                <td>{{ $row['ob_hd'] ?: '' }}</td>
                <td>{{ $row['ob_trip'] ?: '' }}</td>
                <td>{{ $row['ob_volume'] ? number_format($row['ob_volume'],0,',','.') : '' }}</td>
                <td>{{ $row['mud_hd'] ?: '' }}</td>
                <td>{{ $row['mud_trip'] ?: '' }}</td>
                <td>{{ $row['mud_volume'] ? number_format($row['mud_volume'],0,',','.') : '' }}</td>
                <td class="left">{{ $row['remark'] }}</td>
            </tr>
            @endforeach
            @php
                $obDistanceValues = collect($report['hourly'])
                    ->pluck('avg_distance_ob')
                    ->filter(fn($v) => is_numeric($v) && (float)$v > 0);
                $obDistanceAvg = $obDistanceValues->count() ? round($obDistanceValues->avg(), 2) : null;
            @endphp
            <tr class="total-row"><td>Total</td><td>{{ $obDistanceAvg !== null ? number_format($obDistanceAvg,2,'.','') : '' }}</td><td>{{ $report['totals']['ob_hd'] }}</td><td>{{ $report['totals']['ob_trip'] }}</td><td>{{ number_format($report['totals']['ob_volume'],0,',','.') }}</td><td>{{ $report['totals']['mud_hd'] }}</td><td>{{ $report['totals']['mud_trip'] }}</td><td>{{ number_format($report['totals']['mud_volume'],0,',','.') }}</td><td></td></tr>
        </tbody>
    </table>

    <div class="report-section-title"><span>Produksi Perjam <b>{{ $report['latest_hour'] }}</b></span><small>(Unit : km, bcm)</small></div>
    <table class="report-table detail-table">
        <thead><tr><th rowspan="2" class="merged-head"><span>Material</span></th><th rowspan="2" class="merged-head"><span>Area</span></th><th rowspan="2" class="merged-head"><span>EX</span></th><th rowspan="2" class="merged-head"><span>Jarak</span></th><th colspan="2">HD</th><th colspan="2">Prdty</th><th rowspan="2" class="merged-head"><span>W/D</span></th><th rowspan="2" class="merged-head"><span>Ket.</span></th></tr><tr><th>Plan</th><th>Aktual</th><th>Plan</th><th>Aktual</th></tr></thead>
        <tbody>
            @php $groups=$report['details']->groupBy(fn($r)=>$r['material'].'|'.$r['area']); @endphp
            @foreach($groups as $key=>$items)
                @php [$material,$area]=explode('|',$key,2); @endphp
                @foreach($items as $idx=>$row)
                <tr>
                    @if($idx===0)<td rowspan="{{ $items->count() }}">{{ $material }}</td><td rowspan="{{ $items->count() }}">{{ $area }}</td>@endif
                    <td>EX{{ $row['ex'] }}</td><td>{{ number_format((float)$row['distance'],2,'.','') }}</td><td>{{ $row['dt_plan'] }}</td><td>{{ $row['dt_actual'] }}</td><td>{{ is_numeric($row['pdty_plan']) ? number_format((float)$row['pdty_plan'],0,',','.') : $row['pdty_plan'] }}</td><td>{{ is_numeric($row['pdty_actual']) ? number_format(round((float)$row['pdty_actual']),0,',','.') : $row['pdty_actual'] }}</td><td>{{ $row['wd'] }}</td><td class="left">{{ $row['remark'] }}</td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    @if($report['include_summary'])
    <div class="report-two-col">
        <div>
            <div class="report-section-title"><span>Status Unit</span></div>
            <table class="report-table status-table"><thead><tr><th>Type</th><th>Pop</th><th>Ready</th><th>Down</th></tr></thead><tbody>@foreach($report['units'] as $u)<tr><td class="left">{{ $u['type'] }}</td><td>{{ $u['pop'] }}</td><td>{{ $u['ready'] }}</td><td>{{ $u['down'] }}</td></tr>@endforeach</tbody></table>
        </div>
        <div>
            <div class="report-section-title"><span>Status Operator</span></div>
            <table class="report-table status-table operator-status-table">
                <thead><tr><th>Unit</th><th>Hadir</th><th>Operasi</th><th>Spare</th></tr></thead>
                <tbody>
                    @foreach($report['operators'] as $operator)
                    <tr>
                        <td class="left">{{ $operator['type'] }}</td>
                        <td>{{ $operator['hadir'] }}</td>
                        <td>{{ $operator['operation'] }}</td>
                        @php
                            $operatorSpare = max(0, (int)($operator['hadir'] ?? 0) - (int)($operator['operation'] ?? 0));
                        @endphp
                        <td>{{ $operatorSpare > 0 ? $operatorSpare : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="report-two-col note-grid">
        <div><div class="report-box-title">Note:</div><div class="report-box">{!! nl2br(e($report['notes'] ?: '-')) !!}</div></div>
        <div><div class="report-box-title">Issue:</div><div class="report-box">{!! nl2br(e($report['issues'] ?: '-')) !!}</div></div>
    </div>
    @endif
</div>

<style>
.report-sheet{width:940px;background:#fff;color:#000;border:3px solid #111;padding:12px 14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;box-sizing:border-box}.report-title{text-align:center;background:#dedede;font-size:20px;font-weight:700;padding:5px;margin-bottom:8px}.report-meta{border-collapse:collapse;margin-bottom:8px}.report-meta td{padding:3px 12px 3px 2px;font-weight:700}.report-meta td:nth-child(2),.report-meta td:nth-child(4){border:1px solid #111;min-width:145px;text-align:center;font-weight:400}.report-section-title{display:flex;justify-content:space-between;align-items:flex-end;font-weight:700;margin:7px 2px 2px}.report-section-title b{display:inline-block;border:1px solid #111;padding:4px 18px;margin-left:12px;font-weight:400}.report-section-title small{font-weight:400;font-size:14px}.report-table{width:100%;border-collapse:collapse;table-layout:fixed}.report-table th,.report-table td{border:1px solid #111;padding:5px 4px;text-align:center;vertical-align:middle!important;line-height:1.15}.detail-table th,.detail-table td{vertical-align:middle!important}.detail-table th[rowspan],.detail-table td[rowspan]{vertical-align:middle!important}.report-table th{background:#fff2cc;font-weight:400}.report-table td.left,.report-table th.left{text-align:left}.production-table th:first-child{width:120px}.production-table th:last-child{width:260px}.production-table thead tr:nth-child(2) th:first-child{width:66px}.total-row td{background:#c6e0b4;font-weight:700}.detail-table{font-size:14px}.detail-table th:nth-child(1){width:70px}.detail-table th:nth-child(2){width:74px}.detail-table th:nth-child(3){width:66px}.detail-table th:nth-child(4){width:62px}.detail-table th:nth-child(9){width:58px}.detail-table th:nth-child(10){width:180px}.report-two-col{display:grid;grid-template-columns:1fr 1fr;gap:74px;margin-top:8px}.status-table th:first-child{width:44%}.note-grid{gap:74px}.report-box-title{font-weight:700;margin:0 0 2px 2px}.report-box{border:1px dotted #555;min-height:105px;padding:7px;line-height:1.35;white-space:normal}.report-sheet *{box-sizing:border-box}
.detail-table thead th.merged-head{padding:0!important;vertical-align:middle!important;text-align:center!important}.detail-table thead th.merged-head>span{min-height:56px;width:100%;display:flex;align-items:center;justify-content:center;text-align:center;line-height:1.1;font-weight:400}.detail-table thead tr:first-child th[colspan]{vertical-align:middle!important;text-align:center!important}.detail-table thead tr:nth-child(2) th{vertical-align:middle!important;text-align:center!important}
.production-table thead th{padding-top:8px!important;padding-bottom:8px!important;vertical-align:middle!important}.production-table tbody td{padding-top:10px!important;padding-bottom:10px!important;vertical-align:middle!important;line-height:1.25}.production-table tbody tr.total-row td{padding-top:10px!important;padding-bottom:10px!important}
.production-table .prod-volume{font-weight:700;line-height:1.15}.production-table .prod-distance{margin-top:3px;font-size:12px;line-height:1.1;color:#555;white-space:nowrap}.production-table tbody td{vertical-align:middle!important}
.production-table tbody td:nth-child(2),.production-table thead tr:nth-child(2) th:nth-child(1){white-space:nowrap;width:68px}
.operator-status-table th:first-child,.operator-status-table td:first-child{width:36%}.operator-status-table th,.operator-status-table td{vertical-align:middle!important}
</style>

@php
    $hourly = collect($report['hourly'] ?? []);
    $details = collect($report['details'] ?? []);
    $units = collect($report['units'] ?? []);
    $operators = collect($report['operators'] ?? []);
    $groups = $details->groupBy(fn($r) => ($r['material'] ?? '-') . '|' . ($r['area'] ?? '-'));
    $obDistanceValues = $hourly->pluck('avg_distance_ob')->filter(fn($v) => is_numeric($v) && (float)$v > 0);
    $obDistanceAvg = $obDistanceValues->count() ? round($obDistanceValues->avg(), 2) : null;
@endphp

<div id="ops-report-image" class="ops-poster">
    <div class="ops-top">
        <div class="ops-top-left">
            <div class="ops-brand">
                <div class="ops-logo">
                    <svg viewBox="0 0 120 80" aria-hidden="true">
                        <polygon points="8,68 42,12 62,46 74,28 100,68" fill="#dfeeff"/>
                        <polygon points="22,68 48,26 61,48 74,38 88,68" fill="#ffffff"/>
                    </svg>
                </div>
                <div>
                    <div class="ops-title">OPERATIONAL STATUS</div>
                    <div class="ops-subtitle">PRODUKSI TAMBANG</div>
                </div>
            </div>

            <div class="ops-meta-row">
                <div class="ops-meta-card">
                    <div class="meta-icon">📅</div>
                    <div>
                        <small>Tanggal</small>
                        <strong>{{ $report['date_label'] ?? '-' }}</strong>
                    </div>
                </div>
                <div class="ops-meta-card">
                    <div class="meta-icon">🏭</div>
                    <div>
                        <small>Shift</small>
                        <strong>{{ ($report['shift_name'] ?? '-') . ' (' . ($report['shift_time'] ?? '-') . ')' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="ops-top-right">
            <div class="hero-cut"></div>
            <img src="{{ $heroImage ?? asset('images/operational-status-hero.jpg') }}" alt="Mining Hero" class="hero-image">
            <div class="hero-unit">(Unit : bcm)</div>
        </div>
    </div>

    <div class="section">
        <div class="section-head section-head-blue">
            <div class="section-label">
                <span class="section-badge">🚜</span>
                <span>Produksi</span>
            </div>
        </div>

        <div class="table-wrap">
            <table class="poster-table produksi-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="th-deep jam-head"><span class="th-icon">🕒</span>Jam</th>
                        <th colspan="4" class="th-blue">OB</th>
                        <th colspan="3" class="th-green">MUD</th>
                        <th rowspan="2" class="th-deep ket-head"><span class="th-icon">💬</span>Ket.</th>
                    </tr>
                    <tr>
                        <th class="th-blue-sub">Jarak</th>
                        <th class="th-blue-sub">HD</th>
                        <th class="th-blue-sub">Ritasi</th>
                        <th class="th-blue-sub">Volume</th>
                        <th class="th-green-sub">HD</th>
                        <th class="th-green-sub">Ritasi</th>
                        <th class="th-green-sub">Volume</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hourly as $row)
                    <tr>
                        <td class="time-cell">{{ $row['hour'] ?? '-' }}</td>
                        <td>{{ number_format((float)($row['avg_distance_ob'] ?? 0), 2, '.', '') }}</td>
                        <td>{{ $row['ob_hd'] ?: '' }}</td>
                        <td>{{ $row['ob_trip'] ?: '' }}</td>
                        <td class="bold">{{ !empty($row['ob_volume']) ? number_format((float)$row['ob_volume'], 0, ',', '.') : '' }}</td>
                        <td>{{ $row['mud_hd'] ?: '' }}</td>
                        <td>{{ $row['mud_trip'] ?: '' }}</td>
                        <td class="bold">{{ !empty($row['mud_volume']) ? number_format((float)$row['mud_volume'], 0, ',', '.') : '' }}</td>
                        <td class="left">{{ trim((string)($row['remark'] ?? '')) !== '' ? $row['remark'] : '-' }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total</td>
                        <td>{{ $obDistanceAvg !== null ? number_format($obDistanceAvg, 2, '.', '') : '-' }}</td>
                        <td>{{ $report['totals']['ob_hd'] ?? 0 }}</td>
                        <td>{{ $report['totals']['ob_trip'] ?? 0 }}</td>
                        <td>{{ number_format((float)($report['totals']['ob_volume'] ?? 0), 0, ',', '.') }}</td>
                        <td>{{ $report['totals']['mud_hd'] ?? 0 }}</td>
                        <td>{{ $report['totals']['mud_trip'] ?? 0 }}</td>
                        <td>{{ number_format((float)($report['totals']['mud_volume'] ?? 0), 0, ',', '.') }}</td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-head section-head-blue light">
            <div class="section-label">
                <span class="section-badge">📊</span>
                <span>Produksi Perjam</span>
            </div>
            <div class="hour-pill">🕒 {{ $report['latest_hour'] ?? '-' }}</div>
            <div class="section-unit">(Unit : km, bcm)</div>
        </div>

        <div class="table-wrap">
            <table class="poster-table detail-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="th-deep small-icon">⚒️<span>Material</span></th>
                        <th rowspan="2" class="th-deep small-icon">📍<span>Area</span></th>
                        <th rowspan="2" class="th-deep small-icon">⛏️<span>EX</span></th>
                        <th rowspan="2" class="th-deep small-icon">🛣️<span>Jarak</span></th>
                        <th colspan="2" class="th-blue">HD</th>
                        <th colspan="2" class="th-green">Prty</th>
                        <th rowspan="2" class="th-deep small-icon">💧<span>W/D</span></th>
                        <th rowspan="2" class="th-deep small-icon">📝<span>Ket.</span></th>
                    </tr>
                    <tr>
                        <th class="th-blue-sub">🎯 Plan</th>
                        <th class="th-blue-sub">✅ Aktual</th>
                        <th class="th-green-sub">🚛 Plan</th>
                        <th class="th-green-sub">✅ Aktual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $key => $items)
                        @php [$material, $area] = explode('|', $key, 2); @endphp
                        @foreach($items as $idx => $row)
                        <tr>
                            @if($idx === 0)
                                <td rowspan="{{ $items->count() }}" class="material-block {{ strtoupper($material) === 'MUD' ? 'material-mud' : 'material-ob' }}">
                                    <div class="mat-ico">{{ strtoupper($material) === 'MUD' ? '🚜' : '⛏️' }}</div>
                                    <div>{{ $material }}</div>
                                </td>
                                <td rowspan="{{ $items->count() }}" class="area-cell">{{ $area }}</td>
                            @endif
                            <td class="ex-cell">EX{{ $row['ex'] ?? '-' }}</td>
                            <td>{{ number_format((float)($row['distance'] ?? 0), 2, '.', '') }}</td>
                            <td>{{ $row['dt_plan'] ?? '' }}</td>
                            <td>{{ $row['dt_actual'] ?? '' }}</td>
                            <td>{{ is_numeric($row['pdty_plan'] ?? null) ? number_format((float)$row['pdty_plan'], 0, ',', '.') : ($row['pdty_plan'] ?? '') }}</td>
                            <td>{{ is_numeric($row['pdty_actual'] ?? null) ? number_format((float)$row['pdty_actual'], 0, ',', '.') : ($row['pdty_actual'] ?? '') }}</td>
                            <td>{{ $row['wd'] ?? '' }}</td>
                            <td class="left">{{ trim((string)($row['remark'] ?? '')) !== '' ? $row['remark'] : '-' }}</td>
                        </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if(($report['include_summary'] ?? true))
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-head unit-head"><span class="summary-icon">🪖</span><span>Status Unit</span></div>
            <div class="summary-table-wrap">
                <table class="poster-table mini-table">
                    <thead>
                        <tr>
                            <th class="th-blue-sub">🚚 Type</th>
                            <th class="th-blue-sub">👥 Pop</th>
                            <th class="th-blue-sub">✅ Ready</th>
                            <th class="th-blue-sub">🔧 Down</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($units as $u)
                        <tr>
                            <td class="left">{{ $u['type'] ?? '-' }}</td>
                            <td>{{ $u['pop'] ?? 0 }}</td>
                            <td>{{ $u['ready'] ?? 0 }}</td>
                            <td>{{ $u['down'] ?? 0 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="info-box note-box">
                <div class="ibox-title">📝 Note</div>
                <div class="ibox-body">{!! nl2br(e($report['notes'] ?: '-')) !!}</div>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-head operator-head"><span class="summary-icon">👷</span><span>Status Operator</span></div>
            <div class="summary-table-wrap">
                <table class="poster-table mini-table">
                    <thead>
                        <tr>
                            <th class="th-green-sub">🚜 Unit</th>
                            <th class="th-green-sub">👤 Hadir</th>
                            <th class="th-green-sub">⚙️ Operasi</th>
                            <th class="th-green-sub">🔧 Spare</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($operators as $o)
                        <tr>
                            <td class="left">{{ $o['type'] ?? '-' }}</td>
                            <td>{{ $o['hadir'] ?? 0 }}</td>
                            <td>{{ $o['operation'] ?? 0 }}</td>
                            @php
                                $operatorSpare = max(0, (int)($o['hadir'] ?? 0) - (int)($o['operation'] ?? 0));
                            @endphp
                            <td>{{ $operatorSpare > 0 ? $operatorSpare : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="info-box issue-box">
                <div class="ibox-title">🚨 Issue</div>
                <div class="ibox-body">{!! nl2br(e($report['issues'] ?: '-')) !!}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="ops-footer">
        <div class="footer-left-accent"></div>
        <div class="footer-text">Keselamatan <span>•</span> Produktivitas <span>•</span> Keberlanjutan</div>
        <div class="footer-art">🚜</div>
    </div>
</div>

<style>
#ops-report-image,#ops-report-image *{box-sizing:border-box}
.ops-poster{width:1080px;background:#f4f8fc;font-family:Inter,Segoe UI,Arial,sans-serif;color:#0f2f57;overflow:hidden}
.ops-top{display:grid;grid-template-columns:62% 38%;height:190px;background:linear-gradient(120deg,#08345d 0%,#0a4f82 60%,#dfeffc 60%,#eef7ff 100%);border-bottom:5px solid #ffc61a}
.ops-top-left{padding:18px 24px 14px;position:relative;z-index:2}
.ops-brand{display:flex;align-items:center;gap:16px}
.ops-logo{width:78px;height:60px;display:flex;align-items:center;justify-content:center}
.ops-logo svg{width:78px;height:58px}
.ops-title{font-size:48px;line-height:1;font-weight:900;color:#fff;letter-spacing:.5px}
.ops-subtitle{margin-top:6px;font-size:28px;line-height:1;font-weight:900;color:#ffc61a}
.ops-meta-row{display:flex;gap:14px;margin-top:18px}
.ops-meta-card{display:flex;align-items:center;gap:12px;min-width:250px;height:62px;padding:8px 14px;border-radius:12px;background:linear-gradient(180deg,rgba(4,43,79,.78),rgba(5,42,76,.94));border:2px solid rgba(255,255,255,.3);box-shadow:inset 0 0 0 1px rgba(255,255,255,.08)}
.meta-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.4);font-size:23px;background:rgba(255,255,255,.06)}
.ops-meta-card small{display:block;font-size:13px;line-height:1;color:#d7e8f8;font-weight:700}
.ops-meta-card strong{display:block;margin-top:5px;font-size:17px;line-height:1.1;color:#fff;font-weight:900;white-space:nowrap}
.ops-top-right{position:relative;overflow:hidden}
.hero-image{width:100%;height:100%;object-fit:cover;display:block}
.hero-cut{position:absolute;left:12px;top:-35px;width:16px;height:260px;background:#ffc61a;transform:rotate(32deg);z-index:2}
.hero-unit{position:absolute;right:16px;bottom:10px;color:#fff;font-size:17px;font-weight:800;padding:6px 12px;border-radius:16px;background:rgba(6,36,70,.7)}
.section{padding:12px 18px 0}
.section-head{display:flex;align-items:center;height:52px;border:1px solid #c6deef;border-bottom:0;border-radius:14px 14px 0 0;padding:0 14px}
.section-head-blue{background:linear-gradient(90deg,#0a3d6d 0%,#09558f 24%,#eaf5fd 24%,#eaf5fd 100%)}
.section-head.light{background:linear-gradient(90deg,#0a3d6d 0%,#09558f 48%,#eaf5fd 48%,#eaf5fd 100%)}
.section-label{display:flex;align-items:center;color:#fff;font-weight:900;font-size:24px}
.section-badge{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;margin-right:12px;border-radius:12px;background:#083c68;color:#ffc61a;font-size:25px}
.section-unit{margin-left:auto;font-size:14px;font-weight:800;color:#123d66}
.hour-pill{margin-left:18px;padding:7px 16px;border-radius:10px;background:#ffc61a;color:#0d395f;box-shadow:0 3px 0 #d1a400;font-size:16px;font-weight:900}
.table-wrap{border:1px solid #b6d6ea;border-radius:0 0 14px 14px;overflow:hidden;background:#fff}
.poster-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:15px}
.poster-table th,.poster-table td{padding:8px 8px;border-right:1px solid #bad9ec;border-bottom:1px solid #d7e8f4;text-align:center;vertical-align:middle;line-height:1.15}
.poster-table th:last-child,.poster-table td:last-child{border-right:0}
.poster-table tbody tr:nth-child(odd){background:#f8fcff}
.poster-table tbody tr:nth-child(even){background:#eaf4fb}
.poster-table .left{text-align:left}
.th-deep{background:linear-gradient(180deg,#0e416e,#0a2f57);color:#fff;font-weight:900}
.th-blue{background:linear-gradient(180deg,#0c7ad0,#0a6ab8);color:#fff;font-size:16px;font-weight:900}
.th-green{background:linear-gradient(180deg,#0e9b75,#0a8465);color:#fff;font-size:16px;font-weight:900}
.th-blue-sub{background:linear-gradient(180deg,#1387da,#0d75c0);color:#fff;font-weight:800}
.th-green-sub{background:linear-gradient(180deg,#12aa82,#08906c);color:#fff;font-weight:800}
.jam-head,.ket-head,.small-icon{font-size:15px}
.th-icon{display:block;font-size:22px;margin-bottom:4px}
.small-icon{padding-top:6px!important;padding-bottom:6px!important}
.small-icon span{display:block;margin-top:2px}
.time-cell{font-weight:800;white-space:nowrap}
.produksi-table th:first-child{width:150px}
.produksi-table th:last-child{width:230px}
.bold{font-weight:800}
.total-row td{color:#fff;font-weight:900;background:linear-gradient(180deg,#0f6dc0,#0b58a1)!important}
.total-row td:nth-child(6),.total-row td:nth-child(7),.total-row td:nth-child(8){background:linear-gradient(180deg,#0f9a74,#087d5f)!important}
.total-row td:last-child{background:linear-gradient(180deg,#234b71,#183d60)!important}
.detail-table{font-size:14px}
.detail-table th:nth-child(1){width:95px}
.detail-table th:nth-child(2){width:90px}
.detail-table th:nth-child(3){width:82px}
.detail-table th:nth-child(4){width:78px}
.detail-table th:nth-child(9){width:74px}
.detail-table th:nth-child(10){width:160px}
.material-block{color:#fff;font-weight:900;font-size:15px}
.material-ob{background:linear-gradient(180deg,#138ddd,#0a6fbb)!important}
.material-mud{background:linear-gradient(180deg,#0ea07b,#077d61)!important}
.mat-ico{font-size:30px;margin-bottom:6px}
.area-cell{font-weight:800;background:#f4f9fd}
.ex-cell{font-weight:800}
.detail-table td[rowspan]{vertical-align:middle!important}
.summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:14px 18px}
.summary-card{border:1px solid #c5deef;border-radius:14px;background:#fbfdff;overflow:hidden}
.summary-head{display:flex;align-items:center;gap:12px;height:54px;padding:0 14px;color:#fff;font-size:23px;font-weight:900}
.unit-head{background:linear-gradient(90deg,#0c3d6d,#095991)}
.operator-head{background:linear-gradient(90deg,#08765d,#0ba278)}
.summary-icon{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.14);font-size:23px}
.summary-table-wrap{padding:8px 10px 0}
.mini-table{font-size:14px;border:1px solid #c7dff0}
.mini-table thead th{font-size:14px}
.mini-table td:first-child{text-align:left}
.info-box{margin:8px 10px 10px;border-radius:12px;padding:10px 12px;min-height:120px}
.note-box{background:linear-gradient(110deg,#fff4c7,#fff9dd);border:1px solid #ffe08a}
.issue-box{background:linear-gradient(110deg,#ffe6ea,#fff2f4);border:1px solid #ffc7cf}
.ibox-title{font-size:16px;font-weight:900;margin-bottom:6px}
.note-box .ibox-title{color:#0b3e69}
.issue-box .ibox-title{color:#df2d44}
.ibox-body{font-size:14px;line-height:1.45;color:#193652}
.ops-footer{position:relative;display:flex;align-items:center;height:58px;padding:0 26px;background:linear-gradient(90deg,#062f57,#084a7f);color:#fff;overflow:hidden}
.footer-left-accent{position:absolute;left:-24px;top:-16px;width:70px;height:92px;background:#ffc61a;transform:skewX(-28deg)}
.footer-text{position:relative;z-index:2;font-size:15px;font-weight:900}
.footer-text span{color:#ffc61a;padding:0 7px}
.footer-art{margin-left:auto;font-size:42px;opacity:.88}
</style>

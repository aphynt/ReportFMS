@include('layout.head', ['title' => 'Operational Status'])
@include('layout.header')
@include('layout.sidebar')
<style>
    .wa-modal-close {
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 10px;
        background: #f4f6f8;
        color: #667085;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: .2s;
    }

    .wa-modal-close:hover {
        background: #feeceb;
        color: #d14343;
    }

    .wa-modal-close i {
        font-size: 14px;
    }
</style>
@php
    $actual = (float)($summary['production_actual'] ?? 0);
    $plan = (float)($summary['production_plan'] ?? 0);
    $achievement = (float)($summary['achievement'] ?? 0);
    $mud = (float)($summary['production_mud'] ?? 0);
@endphp

<div class="ops-page mt-5">
    <div class="ops-heading">
        <div>
            <h1>Operational Status</h1>
            <p>Monitoring dan penyempurnaan laporan operasional produksi.</p>
        </div>
        <div class="ops-heading-right">
            @if(!empty($latestCreatedTime))
            <div class="ops-sync"><span></span>
                <div><small>Last
                        update</small><strong>{{ \Carbon\Carbon::parse($latestCreatedTime)->format('d M Y H:i') }}</strong>
                </div>
            </div>
            @endif
            <span class="ops-version">{{ !empty($history) ? 'V'.$history->VERSION : 'New Report' }}</span>
            @if(!empty($continuedFrom) && empty($history))
                <span class="ops-continue"><i class="fas fa-forward"></i> Continue {{ $continuedFrom['hour_start'] }} - {{ $continuedFrom['hour_end'] }} · V{{ $continuedFrom['version'] }}</span>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('operational-status.store') }}" id="opsForm">
        @csrf
        @if(!empty($history)) <input type="hidden" name="base_history_id" value="{{ $history->ID }}"> @endif

        <div class="ops-filter">
            <div class="ops-field">
                <label>Tanggal</label>
                <div class="ops-control-wrap"><i class="far fa-calendar"></i><input type="date" name="report_date"
                        id="report_date" value="{{ $reportDate }}"></div>
            </div>
            <div class="ops-field">
                <label>Shift</label>
                <div class="ops-control-wrap"><i class="fas fa-layer-group"></i><select name="shift_no" id="shift_no">
                        <option value="6" {{ $shiftNo == 6 ? 'selected' : '' }}>Siang</option>
                        <option value="7" {{ $shiftNo == 7 ? 'selected' : '' }}>Malam</option>
                    </select></div>
            </div>
            <div class="ops-field">
                <label>Jam Laporan</label>
                <div class="ops-control-wrap"><i class="far fa-clock"></i>
                    <select name="hour_start" id="hour_start">
                        @foreach($shiftHours as $hour)
                            @php
                                $startValue = str_pad($hour, 2, '0', STR_PAD_LEFT).':00';
                                $endValue = str_pad(($hour + 1) % 24, 2, '0', STR_PAD_LEFT).':00';
                            @endphp
                            <option value="{{ $startValue }}" {{ $hourStart === $startValue ? 'selected' : '' }}>{{ $startValue }} - {{ $endValue }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="ops-field">
                <label>Jam Selesai</label>
                <div class="ops-control-wrap"><i class="far fa-clock"></i><input type="text" id="hour_end_display" value="{{ $hourEnd }}" readonly></div>
                <input type="hidden" name="hour_end" id="hour_end" value="{{ $hourEnd }}">
            </div>
            <button type="button" id="btnReload" class="ops-button ops-button-primary"><i
                    class="fas fa-search"></i>Tampilkan</button>
        </div>

        <div class="ops-kpis">
            <div class="ops-kpi">
                <div class="ops-kpi-head">
                    <div class="ops-kpi-icon blue"><i class="fas fa-chart-line"></i></div><span>ACTUAL</span>
                </div>
                <small>Production Actual</small>
                <h2>{{ number_format($actual, 0, ',', '.') }} <em>BCM</em></h2>
                <div class="ops-kpi-foot"><i class="far fa-clock"></i>{{ $hourStart }} - {{ $hourEnd }}</div>
            </div>
            <div class="ops-kpi">
                <div class="ops-kpi-head">
                    <div class="ops-kpi-icon violet"><i class="fas fa-bullseye"></i></div><span>PLAN</span>
                </div>
                <small>Plan Production</small>
                <h2>{{ number_format($plan, 0, ',', '.') }} <em>BCM</em></h2>
                <div class="ops-kpi-foot">Target periode berjalan</div>
            </div>
            <div class="ops-kpi">
                <div class="ops-kpi-head">
                    <div
                        class="ops-kpi-icon {{ $achievement >= 100 ? 'blue' : ($achievement >= 86 ? 'green' : ($achievement >= 65 ? 'orange' : 'red')) }}">
                        <i class="fas fa-percentage"></i></div><span>ACHIEVEMENT</span>
                </div>
                <small>Actual vs Plan</small>
                <h2
                    class="{{ $achievement >= 100 ? 'c-blue' : ($achievement >= 86 ? 'c-green' : ($achievement >= 65 ? 'c-orange' : 'c-red')) }}">
                    {{ number_format($achievement, 2) }}%</h2>
                <div class="ops-progress">
                    <div style="width:{{ min($achievement,100) }}%"></div>
                </div>
            </div>
            <div class="ops-kpi">
                <div class="ops-kpi-head">
                    <div class="ops-kpi-icon amber"><i class="fas fa-truck-loading"></i></div><span>MUD</span>
                </div>
                <small>Production Mud</small>
                <h2>{{ number_format($mud, 1, ',', '.') }} <em>M³</em></h2>
                <div class="ops-kpi-foot">Material lumpur</div>
            </div>
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div><span>PRODUCTION</span>
                    <h3>Produksi Per Jam</h3>
                    <p>Actual, plan, achievement dan cumulative production.</p>
                </div>
                <div class="ops-auto"><i class="fas fa-database"></i>FMS Auto</div>
            </div>
            <div class="ops-table-wrap">
                <table class="ops-table">
                    <thead>
                        <tr>
                            <th>Jam</th>
                            <th class="num">Actual</th>
                            <th class="num">Plan</th>
                            <th class="center">Achievement</th>
                            <th class="num">Actual Cum.</th>
                            <th class="num">Plan Cum.</th>
                            <th class="num">MUD</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hourly as $i => $row)
                        @php
                            $rowAch=(float)($row->ACH??0)*100;
                            $achClass=$rowAch>100?'blue':($rowAch>=86?'green':($rowAch>=65?'orange':'red'));
                            $isCurrent=str_pad((int)$row->HOUR,2,'0',STR_PAD_LEFT)===str_pad((int)substr($hourStart,0,2),2,'0',STR_PAD_LEFT);
                        @endphp
                        <tr class="{{ $isCurrent ? 'current-row' : '' }}">
                            <td><div class="ops-hour">@if($isCurrent)<span></span>@endif{{ str_pad((int)$row->HOUR,2,'0',STR_PAD_LEFT) }}:00</div>
                                <input type="hidden" name="hourly[{{ $i }}][num]" value="{{ $row->NUM ?? '' }}"><input type="hidden" name="hourly[{{ $i }}][hour]" value="{{ $row->HOUR }}"><input type="hidden" name="hourly[{{ $i }}][sort]" value="{{ $row->SORT ?? '' }}">
                            </td>
                            <td class="num strong">
                                <div class="hourly-production-main">{{ number_format($row->PRODUCTION??0,0,',','.') }}</div>
                                <div class="hourly-distance" title="Rata-rata jarak hauling OB, tidak termasuk lumpur">Distance: {{ number_format((float)($row->AVG_DISTANCE_OB??0),2,'.','') }} km</div>
                                <input type="hidden" name="hourly[{{ $i }}][production]" value="{{ $row->PRODUCTION??0 }}">
                                <input type="hidden" name="hourly[{{ $i }}][avg_distance_ob]" value="{{ $row->AVG_DISTANCE_OB??0 }}">
                            </td>
                            <td class="num">{{ number_format($row->PLAN_PRODUCTION??0,0,',','.') }}<input type="hidden" name="hourly[{{ $i }}][plan]" value="{{ $row->PLAN_PRODUCTION }}"></td>
                            <td class="center"><span class="ops-ach {{ $achClass }}">{{ number_format($rowAch,2) }}%</span><input type="hidden" name="hourly[{{ $i }}][ach]" value="{{ $row->ACH??0 }}"></td>
                            <td class="num">{{ number_format($row->PRODUCTION_CUM??0,0,',','.') }}<input type="hidden" name="hourly[{{ $i }}][production_cum]" value="{{ $row->PRODUCTION_CUM??0 }}"></td>
                            <td class="num">{{ number_format($row->PLAN_PRODUCTION_CUM??0,0,',','.') }}<input type="hidden" name="hourly[{{ $i }}][plan_production_cum]" value="{{ $row->PLAN_PRODUCTION_CUM??0 }}"><input type="hidden" name="hourly[{{ $i }}][ach_cum]" value="{{ $row->ACH_CUM??0 }}"></td>
                            <td class="num">{{ number_format($row->PRODUCTION_MD??0,1,',','.') }}<input type="hidden" name="hourly[{{ $i }}][production_md]" value="{{ $row->PRODUCTION_MD??0 }}"><input type="hidden" name="hourly[{{ $i }}][created_time]" value="{{ $row->CREATED_TIME??'' }}"></td>
                            <td><input class="ops-input manual" name="hourly[{{ $i }}][remark]" value="{{ $row->SAVED_REMARK??'' }}" placeholder="Tambahkan keterangan..."></td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="ops-empty"><i class="fas fa-inbox"></i><strong>Data belum tersedia</strong></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div><span>FLEET MATCHING</span>
                    <h3>Produksi Per Excavator</h3>
                    <p>Data actual dari FMS, kolom plan dan keterangan dapat dilengkapi.</p>
                </div>
                <div class="ops-legend"><span><i class="dot auto"></i>FMS</span><span><i class="dot manual"></i>User
                        Input</span></div>
            </div>
            <div class="ops-table-wrap">
                <table class="ops-table ex-table">
                    <thead>
                        <tr>
                            <th rowspan="2">PIT</th>
                            <th rowspan="2">Excavator</th>
                            <th rowspan="2" class="center">Distance</th>
                            <th colspan="2" class="center">DT</th>
                            <th colspan="2" class="center">Trip</th>
                            <th colspan="2" class="center">P.DTY</th>
                            <th rowspan="2" class="center">Ref Plan</th>
                            <th rowspan="2" class="center">W/D</th>
                            <th rowspan="2">Keterangan</th>
                        </tr>
                        <tr>
                            <th class="center">Actual</th>
                            <th class="center manual-head">Plan</th>
                            <th class="center">Actual</th>
                            <th class="center manual-head">Plan</th>
                            <th class="center">Actual</th>
                            <th class="center manual-head">Plan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $i => $row)
                        <tr>
                            <td><span class="pit-pill">{{ str_replace('PIT ','',$row->PIT??'-') }}</span><input type="hidden" name="details[{{ $i }}][pit]" value="{{ $row->PIT }}"></td>
                            <td><div class="unit-cell"><span>EX</span><strong>{{ str_replace('EX','',$row->VHC_ID) }}</strong></div><input type="hidden" name="details[{{ $i }}][vhc_id]" value="{{ $row->VHC_ID }}"><input type="hidden" name="details[{{ $i }}][material]" value="{{ $row->MATERIAL ?? 'OB' }}"></td>
                            <td class="center"><span class="readonly-value">{{ number_format($row->DISTANCE_ACTUAL??0,2) }} <small>km</small></span><input type="hidden" name="details[{{ $i }}][distance_actual]" value="{{ $row->DISTANCE_ACTUAL??0 }}"></td>
                            <td class="center"><span class="readonly-value">{{ $row->DT_ACTUAL??0 }}</span><input type="hidden" name="details[{{ $i }}][dt_actual]" value="{{ $row->DT_ACTUAL??0 }}"></td>
                            <td><input type="number" class="ops-input manual center required-manual" name="details[{{ $i }}][dt_plan]" value="{{ $row->DT_PLAN??'' }}" placeholder="-"></td>
                            <td class="center"><span class="readonly-value">{{ $row->TRIP_ACTUAL??0 }}</span><input type="hidden" name="details[{{ $i }}][trip_actual]" value="{{ $row->TRIP_ACTUAL??0 }}"></td>
                            <td><input type="number" class="ops-input manual center required-manual trip-plan" data-row="{{ $i }}" name="details[{{ $i }}][trip_plan]" value="{{ $row->TRIP_PLAN??'' }}" placeholder="-"></td>
                            <td class="center"><span class="readonly-value">{{ number_format((float)($row->PDTY_ACTUAL??0),0,',','.') }}</span><input type="hidden" name="details[{{ $i }}][pdty_actual]" value="{{ (int)round((float)($row->PDTY_ACTUAL??0)) }}"></td>
                            <td><input type="number" class="ops-input manual center pdty-plan" id="pdty_plan_{{ $i }}" name="details[{{ $i }}][pdty_plan]" value="{{ $row->PDTY_PLAN??'' }}" placeholder="-"></td>
                            <td class="center"><span class="ref-pill">{{ $row->PLAN_REFERENCE??'-' }}</span><input type="hidden" name="details[{{ $i }}][plan_reference]" value="{{ $row->PLAN_REFERENCE }}"></td>
                            <td><select class="ops-input manual" name="details[{{ $i }}][wd_status]"><option value="">-</option><option value="IN" {{ ($row->WD_STATUS??'')==='IN'?'selected':'' }}>IN</option><option value="OUT HR" {{ ($row->WD_STATUS??'')==='OUT HR'?'selected':'' }}>OUT HR</option><option value="IPD" {{ ($row->WD_STATUS??'')==='IPD'?'selected':'' }}>IPD</option><option value="OPD" {{ ($row->WD_STATUS??'')==='OPD'?'selected':'' }}>OPD</option></select></td>
                            <td><input class="ops-input manual wide" name="details[{{ $i }}][remark]" value="{{ $row->REMARK??'' }}" placeholder="Material keras, free dig, standby..."></td>
                        </tr>
                        @empty
                        <tr><td colspan="12" class="ops-empty"><i class="fas fa-truck"></i><strong>Data fleet belum tersedia</strong></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ops-two-col">
            <div class="ops-panel">
                <div class="ops-panel-head compact">
                    <div><span>FLEET</span>
                        <h3>Status Unit</h3>
                        <p>Populasi, ready dan down.</p>
                    </div>
                </div>
                <div class="ops-table-wrap">
                    <table class="ops-table small-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th class="center">Pop</th>
                                <th class="center">Ready</th>
                                <th class="center">Down</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['EX Big','HD OB','HD MV','MG','BD','WT','EX Small'] as $i => $type)
                            <tr>
                                <td><strong>{{ $type }}</strong><input type="hidden" name="units[{{ $i }}][type]" value="{{ $type }}"></td>
                                <td><input type="number" class="ops-input manual center unit-pop" data-row="{{ $i }}" name="units[{{ $i }}][pop]" value="{{ $unitStatus[$type]['pop'] ?? 0 }}" min="0"></td>
                                <td><input type="number" class="ops-input center unit-ready" id="unit_ready_{{ $i }}" name="units[{{ $i }}][ready]" value="{{ $unitStatus[$type]['ready'] ?? 0 }}" readonly></td>
                                <td><input type="number" class="ops-input manual center unit-down" data-row="{{ $i }}" name="units[{{ $i }}][down]" value="{{ $unitStatus[$type]['down'] ?? 0 }}" min="0"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ops-panel">
                <div class="ops-panel-head compact">
                    <div><span>MANPOWER</span>
                        <h3>Status Operator</h3>
                        <p>Kehadiran dan distribusi operator.</p>
                    </div>
                </div>
                <div class="ops-table-wrap">
                    <table class="ops-table small-table operator-table">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th class="center">Hadir</th>
                                <th class="center">Operasi</th>
                                <th class="center">Spare</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['EX Big','HD OB','HD MV','MG','BD','WT','EX Small'] as $i => $type)
                            <tr>
                                <td><strong>{{ $type }}</strong><input type="hidden" name="operators[{{ $i }}][type]" value="{{ $type }}"></td>
                                <td><input type="number" class="ops-input manual center operator-hadir" data-row="{{ $i }}" name="operators[{{ $i }}][hadir]" value="{{ $operatorStatus[$type]['hadir'] ?? 0 }}" min="0"></td>
                                <td><input type="number" class="ops-input manual center operator-operation" data-row="{{ $i }}" name="operators[{{ $i }}][operation]" value="{{ $operatorStatus[$type]['operation'] ?? 0 }}" min="0"></td>
                                <td><input type="number" class="ops-input center operator-spare" id="operator_spare_{{ $i }}" name="operators[{{ $i }}][spare]" value="{{ max(0, (int)($operatorStatus[$type]['hadir'] ?? 0) - (int)($operatorStatus[$type]['operation'] ?? 0)) }}" min="0" readonly></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="ops-two-col">
            <div class="ops-panel">
                <div class="ops-panel-head compact">
                    <div><span>NOTES</span>
                        <h3>Catatan Operasional</h3>
                        <p>Breakdown dan standby excavator.</p>
                    </div>
                </div>
                <div class="ops-textarea-wrap"><textarea name="notes" class="ops-textarea">{{ $notes ?? '' }}</textarea>
                </div>
            </div>
            <div class="ops-panel">
                <div class="ops-panel-head compact">
                    <div><span>ISSUE</span>
                        <h3>Issue / Breakdown</h3>
                        <p>Workshop, field breakdown dan kondisi unit.</p>
                    </div><span class="ops-auto edit">Auto + Edit</span>
                </div>
                <div class="ops-textarea-wrap"><textarea name="issues" class="ops-textarea">{{ $issues ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <div class="ops-savebar">
            <div class="save-progress"><small>Kelengkapan Data</small>
                <div>
                    <div class="completion-track"><span id="completionFill"></span></div><strong
                        id="completionText">0%</strong>
                </div>
            </div>
            <div class="save-actions">
                <button type="button" id="btnWhatsapp" class="ops-button whatsapp"><i class="fab fa-whatsapp"></i>WhatsApp</button>
                @if(!empty($history))
                    <button type="button" id="btnDeleteHistory" class="ops-button danger"><i class="fas fa-trash-alt"></i>Hapus</button>
                @endif
                <button type="submit" name="status" value="draft" class="ops-button ops-button-primary"><i class="far fa-save"></i>Draft</button>
            </div>
        </div>
    </form>

    @if(!empty($history))
    <form id="deleteHistoryForm" method="POST" action="{{ route('operational-status.destroy', ['id' => $history->ID]) }}" style="display:none">
        @csrf
        @method('DELETE')
    </form>
    @endif
</div>

<div class="modal fade" id="modalWhatsapp" tabindex="-1" aria-labelledby="modalWhatsappLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content wa-modal">
            <div class="modal-header">
                <div>
                    <span class="wa-kicker">WHATSAPP REPORT</span>
                    <h5 class="modal-title" id="modalWhatsappLabel">Preview Operational Status</h5>
                    <small>Pilih historical per jam. Preview akan dibuat menjadi gambar.</small>
                </div>

                <button type="button" class="wa-modal-close js-close-whatsapp" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="wa-toolbar">
                    <label>
                        <input type="checkbox" id="waSelectAll" checked>
                        Pilih semua jam
                    </label>

                    <label>
                        <input type="checkbox" id="waIncludeSummary" checked>
                        Sertakan status, note & issue
                    </label>
                </div>

                <div class="wa-layout">
                    <div class="wa-history">
                        <div class="wa-section-title">HISTORICAL PER JAM</div>

                        <div id="waHistoryList">
                            <div class="wa-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                Memuat historical...
                            </div>
                        </div>
                    </div>

                    <div class="wa-preview">
                        <div class="wa-section-title">PREVIEW GAMBAR</div>

                        <div id="waPreviewStage" class="wa-preview-stage">
                            <div class="wa-empty">
                                Pilih historical untuk membuat preview.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <span id="waSelectedInfo" class="mr-auto text-muted small">
                    0 data dipilih
                </span>

                <button type="button" class="ops-button secondary js-close-whatsapp">
                    <i class="fas fa-times"></i>
                    Tutup
                </button>

                <button type="button" id="btnRefreshPreview" class="ops-button secondary">
                    <i class="fas fa-sync-alt"></i>
                    Preview
                </button>

                <button type="button" id="btnDownloadWhatsapp" class="ops-button secondary" disabled>
                    <i class="fas fa-download"></i>
                    Download Gambar
                </button>

                <button type="button" id="btnSendWhatsapp" class="ops-button whatsapp" disabled>
                    <i class="fab fa-whatsapp"></i>
                    Kirim WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>
<div id="waRenderHost"><div id="waRenderTarget"></div></div>

<style>
    :root {
        --navy: #102a43;
        --blue: #2563eb;
        --text: #172033;
        --muted: #7b8794;
        --line: #e8edf3;
        --soft: #f6f8fb;
        --manual: #fffaf0
    }

    body {
        background: #f5f7fa
    }

    .ops-page {
        padding: 26px 24px 60px;
        color: var(--text);
        font-family: Inter, "Segoe UI", sans-serif
    }

    .ops-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px
    }

    .ops-kicker,
    .ops-panel-head>div>span {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.2px;
        color: #7c8da1
    }

    .ops-heading h1 {
        font-size: 27px;
        font-weight: 750;
        letter-spacing: -.6px;
        margin: 4px 0
    }

    .ops-heading p,
    .ops-panel-head p {
        margin: 0;
        color: var(--muted);
        font-size: 12px
    }

    .ops-heading-right {
        display: flex;
        gap: 10px;
        align-items: center
    }

    .ops-sync {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid var(--line);
        padding: 8px 11px;
        border-radius: 12px
    }

    .ops-sync>span {
        width: 8px;
        height: 8px;
        background: #22a06b;
        border-radius: 50%
    }

    .ops-sync small,
    .ops-sync strong {
        display: block;
        line-height: 1.2
    }

    .ops-sync small {
        font-size: 9px;
        color: var(--muted)
    }

    .ops-sync strong {
        font-size: 11px
    }

    .ops-version {
        padding: 9px 12px;
        border-radius: 10px;
        background: #eef4ff;
        color: #2858a5;
        font-size: 11px;
        font-weight: 700
    }

    .ops-continue {
        padding: 9px 12px;
        border-radius: 10px;
        background: #eaf8f1;
        color: #137a51;
        font-size: 10px;
        font-weight: 700
    }

    .ops-continue i {
        margin-right: 5px
    }

    .ops-filter,
    .ops-panel,
    .ops-kpi {
        background: #fff;
        border: 1px solid var(--line);
        box-shadow: 0 4px 18px rgba(16, 42, 67, .035)
    }

    .ops-filter {
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr 1fr auto;
        gap: 12px;
        align-items: end;
        padding: 16px;
        border-radius: 16px;
        margin-bottom: 16px
    }

    .ops-field label {
        display: block;
        font-size: 10px;
        color: #68778a;
        font-weight: 700;
        margin-bottom: 6px
    }

    .ops-control-wrap {
        height: 42px;
        display: flex;
        align-items: center;
        border: 1px solid #dfe5ec;
        border-radius: 10px;
        padding: 0 11px;
        background: #fff;
        transition: .2s
    }

    .ops-control-wrap:focus-within {
        border-color: #91abd0;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .07)
    }

    .ops-control-wrap i {
        font-size: 12px;
        color: #91a0b1;
        margin-right: 9px
    }

    .ops-control-wrap input,
    .ops-control-wrap select {
        border: 0;
        outline: 0;
        background: transparent;
        width: 100%;
        font-size: 12px;
        color: #24364b;
        height: 40px
    }

    .ops-button {
        height: 42px;
        border: 0;
        border-radius: 10px;
        padding: 0 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none !important;
        cursor: pointer
    }

    .ops-button-primary {
        background: var(--navy);
        color: #fff !important
    }

    .ops-button-primary:hover {
        background: #183f63
    }

    .ops-button.secondary {
        background: #fff;
        border: 1px solid #dfe5ec;
        color: #425466
    }

    .ops-kpis {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 16px
    }

    .ops-kpi {
        border-radius: 16px;
        padding: 17px 18px;
        min-height: 142px
    }

    .ops-kpi-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 13px
    }

    .ops-kpi-head>span {
        font-size: 9px;
        font-weight: 800;
        color: #9aa7b5;
        letter-spacing: .8px
    }

    .ops-kpi-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center
    }

    .ops-kpi-icon.blue {
        background: #edf4ff;
        color: #2563eb
    }

    .ops-kpi-icon.violet {
        background: #f3efff;
        color: #7158c9
    }

    .ops-kpi-icon.green {
        background: #eaf8f1;
        color: #15915f
    }

    .ops-kpi-icon.orange {
        background: #fff5e6;
        color: #cf7b16
    }

    .ops-kpi-icon.red {
        background: #fff0ef;
        color: #d8514a
    }

    .ops-kpi-icon.amber {
        background: #fff6df;
        color: #bc7c00
    }

    .ops-kpi>small {
        font-size: 11px;
        color: #7c8898
    }

    .ops-kpi h2 {
        font-size: 26px;
        margin: 4px 0 8px;
        font-weight: 750;
        letter-spacing: -.5px
    }

    .ops-kpi h2 em {
        font-size: 10px;
        font-style: normal;
        color: #9ba7b4;
        font-weight: 600
    }

    .ops-kpi-foot {
        font-size: 10px;
        color: #9ba7b4
    }

    .ops-kpi-foot i {
        margin-right: 5px
    }

    .c-blue {
        color: #2563eb
    }

    .c-green {
        color: #15915f
    }

    .c-orange {
        color: #d27c10
    }

    .c-red {
        color: #d8514a
    }

    .ops-progress {
        height: 5px;
        background: #edf1f5;
        border-radius: 20px;
        overflow: hidden;
        margin: 9px 0
    }

    .ops-progress div {
        height: 100%;
        background: #2563eb;
        border-radius: inherit
    }

    .ops-panel {
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 16px
    }

    .ops-panel-head {
        padding: 17px 19px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--line)
    }

    .ops-panel-head.compact {
        min-height: 78px
    }

    .ops-panel-head h3 {
        font-size: 15px;
        font-weight: 750;
        margin: 2px 0;
        color: #24364b
    }

    .ops-auto {
        font-size: 10px;
        background: #eaf8f1;
        color: #137a51;
        padding: 6px 9px;
        border-radius: 8px;
        font-weight: 700
    }

    .ops-auto i {
        margin-right: 5px
    }

    .ops-auto.edit {
        background: #fff6df;
        color: #966900
    }

    .ops-legend {
        display: flex;
        gap: 13px;
        font-size: 10px;
        color: #768598
    }

    .ops-legend span {
        display: flex;
        align-items: center;
        gap: 5px
    }

    .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block
    }

    .dot.auto {
        background: #b8c4d0
    }

    .dot.manual {
        background: #e4ae43
    }

    .ops-table-wrap {
        overflow-x: auto
    }

    .ops-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px
    }

    .ops-table th {
        padding: 10px 12px;
        background: #f8fafc;
        border-bottom: 1px solid var(--line);
        color: #637286;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: .45px;
        white-space: nowrap
    }

    .ops-table td {
        padding: 9px 12px;
        border-bottom: 1px solid #edf1f5;
        vertical-align: middle;
        color: #34465a
    }

    .ops-table tbody tr:last-child td {
        border-bottom: 0
    }

    .ops-table tbody tr:hover {
        background: #fbfcfe
    }

    .ops-table .num {
        text-align: right;
        font-variant-numeric: tabular-nums
    }

    .ops-table .center {
        text-align: center
    }

    .ops-table .strong {
        font-weight: 750
    }

    .hourly-production-main {
        font-size: 12px;
        font-weight: 750;
        line-height: 1.2
    }

    .hourly-distance {
        margin-top: 3px;
        font-size: 9px;
        font-weight: 500;
        color: #7b8794;
        white-space: nowrap
    }

    .current-row {
        background: #f5f9ff !important
    }

    .ops-hour {
        display: flex;
        align-items: center;
        gap: 7px;
        font-weight: 700;
        color: #2d4057
    }

    .ops-hour span {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #2563eb
    }

    .ops-ach {
        display: inline-block;
        min-width: 67px;
        padding: 5px 8px;
        border-radius: 8px;
        font-weight: 750;
        font-size: 10px
    }

    .ops-ach.blue {
        background: #edf4ff;
        color: #2563eb
    }

    .ops-ach.green {
        background: #eaf8f1;
        color: #15865a
    }

    .ops-ach.orange {
        background: #fff4e4;
        color: #b56a0e
    }

    .ops-ach.red {
        background: #fff0ef;
        color: #ca4943
    }

    .ops-input {
        width: 100%;
        height: 34px;
        border: 1px solid #dde4eb;
        border-radius: 8px;
        padding: 0 9px;
        font-size: 11px;
        outline: 0;
        background: #fff;
        color: #33465a
    }

    .ops-input:focus {
        border-color: #8ca8cf;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .06)
    }

    .ops-input.manual {
        background: var(--manual);
        border-color: #eadfbd
    }

    .ops-input.center {
        text-align: center
    }

    .ops-input.wide {
        min-width: 220px
    }

    .manual-head {
        background: #fff9ec !important
    }

    .pit-pill,
    .ref-pill {
        display: inline-block;
        padding: 5px 8px;
        border-radius: 8px;
        background: #f1f4f7;
        font-size: 10px;
        font-weight: 700;
        color: #4b5e73
    }

    .ref-pill {
        background: #eef5ff;
        color: #315f9b
    }

    .unit-cell {
        display: flex;
        align-items: center;
        gap: 7px
    }

    .unit-cell span {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: #edf4ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 800
    }

    .unit-cell strong {
        font-size: 11px
    }

    .readonly-value {
        font-weight: 700;
        font-variant-numeric: tabular-nums
    }

    .readonly-value small {
        font-size: 9px;
        color: #9aa7b5
    }

    .ops-empty {
        text-align: center !important;
        padding: 28px !important;
        color: #9aa7b5 !important
    }

    .ops-empty i,
    .ops-empty strong {
        display: block
    }

    .ops-empty i {
        font-size: 20px;
        margin-bottom: 7px
    }

    .ops-two-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px
    }

    .operator-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        padding: 17px
    }

    .small-table .ops-input {
        min-width: 65px
    }

    .ops-textarea-wrap {
        padding: 17px
    }

    .ops-textarea {
        width: 100%;
        height: 400px;
        border: 1px solid #dde4eb;
        border-radius: 11px;
        padding: 12px 13px;
        font-size: 11px;
        resize: vertical;
        outline: 0;
        color: #34465a
    }

    .ops-textarea:focus {
        border-color: #91abd0;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .06)
    }

    .ops-savebar {
        position: sticky;
        bottom: 12px;
        z-index: 50;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: blur(12px);
        border: 1px solid #dfe5ec;
        border-radius: 14px;
        padding: 11px 13px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 12px 32px rgba(16, 42, 67, .12)
    }

    .save-progress small {
        font-size: 9px;
        color: #7a8898;
        font-weight: 700
    }

    .save-progress>div {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 4px
    }

    .completion-track {
        width: 150px;
        height: 5px;
        background: #e9edf2;
        border-radius: 20px;
        overflow: hidden
    }

    .completion-track span {
        display: block;
        width: 0;
        height: 100%;
        background: #15915f
    }

    .save-progress strong {
        font-size: 10px
    }

    .save-actions {
        display: flex;
        gap: 7px
    }

    .ops-button.whatsapp{background:#eaf8f1;color:#137a51;border:1px solid #ccebdd}.ops-button.danger{background:#fff0f0;color:#c0392b;border:1px solid #f2c7c2}.ops-button.danger:hover{background:#fde3e3;color:#a92e22}.ops-button.whatsapp:hover{background:#ddf5e9;color:#0f6843}.wa-modal{border:0;border-radius:18px;overflow:hidden}.wa-modal .modal-header{padding:18px 20px;border-bottom:1px solid #e8edf3}.wa-modal .modal-title{font-size:17px;font-weight:750;margin:2px 0}.wa-kicker,.wa-section-title{font-size:9px;font-weight:800;letter-spacing:1px;color:#8391a2}.wa-toolbar{display:flex;gap:18px;align-items:center;margin-bottom:12px;font-size:11px;color:#536579}.wa-toolbar label{margin:0;display:flex;align-items:center;gap:6px}.wa-layout{display:grid;grid-template-columns:400px 1fr;gap:18px}.wa-history{border-right:1px solid #e8edf3;padding-right:18px;max-height:520px;overflow:auto}.wa-history-row{display:grid;grid-template-columns:24px 1fr auto;align-items:center;gap:9px;padding:10px;border:1px solid #e7ecf2;border-radius:11px;margin-bottom:8px;transition:.15s}.wa-history-row:hover{background:#f8fafc;border-color:#d9e2ec}.wa-history-main strong{display:block;font-size:12px;color:#26394e}.wa-history-main small{font-size:9px;color:#8391a2}.wa-edit{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#f2f5f8;color:#52677d;text-decoration:none!important}.wa-edit:hover{background:#e8eef5;color:#24425f}.wa-preview textarea{width:100%;height:500px;resize:vertical;border:1px solid #dfe5ec;border-radius:12px;padding:14px;font:12px/1.55 Consolas,monospace;color:#26394e;background:#f9fbfc;outline:0}.wa-preview textarea:focus{border-color:#91abd0;box-shadow:0 0 0 3px rgba(37,99,235,.06)}.wa-loading,.wa-empty{padding:30px;text-align:center;color:#8996a5;font-size:11px}

    .wa-preview-stage{min-height:520px;max-height:640px;overflow:auto;background:#eef2f6;border:1px solid #dfe5ec;border-radius:12px;padding:14px;display:flex;align-items:flex-start;justify-content:center}.wa-preview-stage img{display:block;max-width:100%;height:auto;background:#fff;box-shadow:0 8px 24px rgba(16,42,67,.14)}#waRenderHost{position:fixed;left:-20000px;top:0;width:1000px;z-index:-1;opacity:1;pointer-events:none}.wa-generating{padding:70px 20px;text-align:center;color:#728197;font-size:12px}.wa-generating i{display:block;font-size:22px;margin-bottom:10px}.wa-history-row.active{border-color:#9db4d0;background:#f6f9fd}

    @media(max-width:1100px) {
        .ops-filter {
            grid-template-columns: repeat(2, 1fr)
        }

        .ops-kpis {
            grid-template-columns: repeat(2, 1fr)
        }

        .ops-two-col {grid-template-columns:1fr}
        .wa-layout{grid-template-columns:1fr}.wa-history{border-right:0;padding-right:0;max-height:280px}
    }

    @media(max-width:700px) {
        .ops-page {
            padding: 16px 12px 40px
        }

        .ops-heading {
            flex-direction: column;
            gap: 12px
        }

        .ops-heading-right {
            width: 100%;
            justify-content: space-between
        }

        .ops-filter,
        .ops-kpis,
        .operator-grid {
            grid-template-columns: 1fr
        }

        .ops-savebar {
            position: relative;
            bottom: auto;
            flex-direction: column;
            align-items: stretch;
            gap: 12px
        }

        .save-actions {
            display: grid
        }

        .ops-button {
            width: 100%
        }
    }


    .operator-table th,
    .operator-table td {
        vertical-align: middle
    }

    .operator-table td:first-child {
        width: 34%
    }

</style>

@include('layout.footer')

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    $(document).on('click', '.js-close-whatsapp', function () {
    const modalEl = document.getElementById('modalWhatsapp');

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const instance = bootstrap.Modal.getInstance(modalEl);

        if (instance) {
            instance.hide();
        } else {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }

        return;
    }

    if (typeof $.fn.modal !== 'undefined') {
        $('#modalWhatsapp').modal('hide');
        return;
    }

    $('#modalWhatsapp').removeClass('show').hide();
    $('body').removeClass('modal-open');
    $('.modal-backdrop').remove();
});
$(function(){
    let waBlob=null,waFilename='operational-status.png',waCaption='Operational Status';

    $('#btnDeleteHistory').on('click',function(){
        if(!confirm('Hapus data Operational Status ini? Data tidak dihapus permanen dan hanya akan dinonaktifkan.')) return;
        $('#deleteHistoryForm').trigger('submit');
    });
    function padHour(h){return String(h).padStart(2,'0')+':00'}
    function getShiftHours(shift){return String(shift)==='6'?[7,8,9,10,11,12,13,14,15,16,17,18]:[19,20,21,22,23,0,1,2,3,4,5,6]}
    function updateHourEnd(){const start=$('#hour_start').val();if(!start)return;const h=parseInt(start.substring(0,2),10);const end=padHour((h+1)%24);$('#hour_end').val(end);$('#hour_end_display').val(end)}
    function rebuildHourOptions(){const shift=$('#shift_no').val(),hours=getShiftHours(shift),current=$('#hour_start').val();let html='';hours.forEach(h=>{const start=padHour(h),end=padHour((h+1)%24);html+=`<option value="${start}">${start} - ${end}</option>`});$('#hour_start').html(html);if(hours.map(padHour).includes(current))$('#hour_start').val(current);else $('#hour_start').val(padHour(hours[0]));updateHourEnd()}
    $('#shift_no').on('change',rebuildHourOptions);
    $('#hour_start').on('change',updateHourEnd);
    updateHourEnd();
    $('#btnReload').on('click',function(){const u=new URL("{{ route('operational-status.index') }}",window.location.origin);u.searchParams.set('date',$('#report_date').val());u.searchParams.set('shift',$('#shift_no').val());u.searchParams.set('hour_start',$('#hour_start').val());u.searchParams.set('hour_end',$('#hour_end').val());window.location.href=u.toString()});
    $('.trip-plan').on('input',function(){const row=$(this).data('row'),trip=parseFloat($(this).val()),pdty=$('#pdty_plan_'+row);if(!isNaN(trip)&&!pdty.data('manual'))pdty.val(Math.round(trip*30));updateCompletion()});
    $('.pdty-plan').on('input',function(){$(this).data('manual',true)});
    function calculateReady(row){const pop=parseInt($('.unit-pop[data-row="'+row+'"]').val(),10)||0;const down=parseInt($('.unit-down[data-row="'+row+'"]').val(),10)||0;$('#unit_ready_'+row).val(Math.max(0,pop-down))}
    $(document).on('input change','.unit-pop,.unit-down',function(){calculateReady($(this).data('row'))});
    $('.unit-pop').each(function(){calculateReady($(this).data('row'))});

    function calculateOperatorSpare(row){
        const hadir=parseInt($('.operator-hadir[data-row="'+row+'"]').val(),10)||0;
        const operation=parseInt($('.operator-operation[data-row="'+row+'"]').val(),10)||0;
        $('#operator_spare_'+row).val(Math.max(0,hadir-operation));
    }
    $(document).on('input change','.operator-hadir,.operator-operation',function(){
        calculateOperatorSpare($(this).data('row'));
    });
    $('.operator-hadir').each(function(){
        calculateOperatorSpare($(this).data('row'));
    });

    function updateCompletion(){const f=$('.required-manual');if(!f.length){$('#completionFill').css('width','100%');$('#completionText').text('100%');return}let n=0;f.each(function(){if($(this).val()!=='')n++});const p=Math.round(n/f.length*100);$('#completionFill').css('width',p+'%');$('#completionText').text(p+'%')}
    $(document).on('input change','.required-manual',updateCompletion);updateCompletion();
    $('#btnWhatsapp').on('click',function(){$('#modalWhatsapp').modal('show');loadWhatsappHistory()});
    function loadWhatsappHistory(){waBlob=null;$('#waHistoryList').html('<div class="wa-loading"><i class="fas fa-spinner fa-spin"></i> Memuat historical...</div>');$('#waPreviewStage').html('<div class="wa-empty">Memuat data...</div>');$('#btnSendWhatsapp,#btnDownloadWhatsapp').prop('disabled',true);$.get("{{ route('operational-status.whatsapp-histories') }}",{date:$('#report_date').val(),shift:$('#shift_no').val()}).done(function(res){const rows=res.data||[];if(!rows.length){$('#waHistoryList').html('<div class="wa-empty">Belum ada historical pada tanggal dan shift ini.</div>');$('#waPreviewStage').html('<div class="wa-empty">Simpan report per jam terlebih dahulu.</div>');$('#waSelectedInfo').text('0 data dipilih');return}let h='';rows.forEach(r=>h+=`<div class="wa-history-row"><input type="checkbox" class="wa-history-check" value="${r.id}" checked><div class="wa-history-main"><strong>${r.hour_start} - ${r.hour_end}</strong><small>V${r.version} · ${r.status||'-'}${r.created_by?' · '+r.created_by:''}</small></div><a href="${r.edit_url}" class="wa-edit" title="Edit data per jam"><i class="fas fa-pen"></i></a></div>`);$('#waHistoryList').html(h);$('#waSelectAll').prop('checked',true);buildImagePreview()}).fail(showAjaxError)}
    function selectedIds(){return $('.wa-history-check:checked').map(function(){return parseInt($(this).val())}).get()}
    async function buildImagePreview(){const ids=selectedIds();$('#waSelectedInfo').text(ids.length+' data dipilih');$('#waSelectAll').prop('checked',$('.wa-history-check').length>0&&ids.length===$('.wa-history-check').length);waBlob=null;$('#btnSendWhatsapp,#btnDownloadWhatsapp').prop('disabled',true);if(!ids.length){$('#waPreviewStage').html('<div class="wa-empty">Pilih minimal satu historical.</div>');return}if(typeof html2canvas==='undefined'){showMessage('html2canvas belum termuat. Pastikan koneksi internet atau simpan library html2canvas secara lokal.');return}$('#waPreviewStage').html('<div class="wa-generating"><i class="fas fa-spinner fa-spin"></i>Membuat gambar report...</div>');$.ajax({url:"{{ route('operational-status.whatsapp-report-preview') }}",type:'POST',data:{_token:$('input[name="_token"]').first().val(),ids:ids,include_summary:$('#waIncludeSummary').is(':checked')?1:0}}).done(async function(res){try{$('#waRenderTarget').html(res.html||'');waFilename=res.filename||waFilename;waCaption=res.caption||waCaption;if(document.fonts&&document.fonts.ready)await document.fonts.ready;await new Promise(r=>setTimeout(r,60));const el=document.getElementById('ops-report-image');if(!el)throw new Error('Template report tidak ditemukan.');const canvas=await html2canvas(el,{scale:2,backgroundColor:'#ffffff',useCORS:true,logging:false,windowWidth:1000});const dataUrl=canvas.toDataURL('image/png');waBlob=await new Promise(resolve=>canvas.toBlob(resolve,'image/png',1));$('#waPreviewStage').html(`<img src="${dataUrl}" alt="Preview Operational Status">`);$('#btnSendWhatsapp,#btnDownloadWhatsapp').prop('disabled',!waBlob)}catch(e){console.error(e);showMessage('Gagal membuat gambar report.')}}).fail(showAjaxError)}
    function downloadImage(){if(!waBlob)return;const a=document.createElement('a');a.href=URL.createObjectURL(waBlob);a.download=waFilename;document.body.appendChild(a);a.click();setTimeout(()=>{URL.revokeObjectURL(a.href);a.remove()},1000)}
    async function sendWhatsappImage(){
        if(!waBlob){
            showMessage('Gambar report belum tersedia.');
            return;
        }

        const btn=$('#btnSendWhatsapp');
        const originalHtml=btn.html();

        btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Mengirim...');

        const formData=new FormData();
        formData.append('_token',$('input[name="_token"]').first().val());
        formData.append('caption',waCaption||'');
        formData.append('photo',waBlob,waFilename||'operational-status.png');

        $.ajax({
            url:"{{ route('operational-status.whatsapp-send-image') }}",
            type:'POST',
            data:formData,
            processData:false,
            contentType:false,
            timeout:90000
        }).done(function(res){
            showMessage(res.message||'Report berhasil dikirim ke grup WhatsApp.','success');
        }).fail(function(xhr){
            showAjaxError(xhr);
        }).always(function(){
            btn.prop('disabled',!waBlob).html(originalHtml);
        });
    }
    function showAjaxError(xhr){showMessage(xhr.responseJSON&&xhr.responseJSON.message?xhr.responseJSON.message:'Terjadi kesalahan saat memproses report.')}
    function showMessage(msg,type='error'){if(window.toastr){if(type==='success')toastr.success(msg);else if(type==='info')toastr.info(msg);else toastr.error(msg)}else alert(msg)}
    $(document).on('change','.wa-history-check',buildImagePreview);$('#waIncludeSummary').on('change',buildImagePreview);$('#waSelectAll').on('change',function(){$('.wa-history-check').prop('checked',this.checked);buildImagePreview()});$('#btnRefreshPreview').on('click',buildImagePreview);$('#btnDownloadWhatsapp').on('click',downloadImage);$('#btnSendWhatsapp').on('click',sendWhatsappImage);
});
</script>

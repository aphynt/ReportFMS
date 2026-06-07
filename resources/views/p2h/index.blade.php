@include('layout.head', ['title' => 'Prestart Checklist'])
@include('layout.header')
@include('layout.sidebar')

<div class="container-fluid pt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-weight-bold">Monitoring Implementasi P2H Online</h5>
            <span class="text-muted font-weight-bold">Departemen Produksi</span>
        </div>
        <div class="card-body">

            <div class="row mb-4 d-flex gap-3">
                <div class="col-md-1">
                    <label>Tanggal P2H</label>
                    <input type="date" id="filter_tanggal" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-1">
                    <label>Shift</label>
                    <select id="filter_shift" class="form-control">
                        <option value="">Semua Shift</option>
                        <option value="6">Siang</option>
                        <option value="7">Malam</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label>Cluster Unit</label>
                    <select id="filter_cluster" class="form-control">
                        <option value="">Semua</option>
                        <option value="EX">EX</option>
                        <option value="HD">HD</option>
                        <option value="MG">MG</option>
                        <option value="BD">BD</option>
                        <option value="WT">WT</option>
                        <option value="FT">FT</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label>Filter</label>
                    <button id="btn_filter" class="btn btn-primary w-100">Submit</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered text-center table-striped-custom" id="table-monitoring">
                    <thead>
                        <tr class="bg-table-header">
                            <th rowspan="2" class="align-middle">Tanggal</th>
                            <th rowspan="2" class="align-middle">Jenis Unit</th>
                            <th rowspan="2" class="align-middle">Shift</th>
                            <th rowspan="2" class="align-middle">Unit Operasi</th>
                            <th colspan="2">Sudah P2H</th>
                            <th rowspan="2" class="align-middle bg-warning-light">Temuan</th>
                            <th colspan="2" class="bg-mechanic-header">Pengawas Mekanik</th>
                            <th colspan="2" class="bg-operator-header">Pengawas Operator</th>
                        </tr>
                        <tr class="bg-table-header sub-header">
                            <th>Sudah</th>
                            <th>Belum</th>
                            <th class="bg-mechanic-header">Verifikasi Perbaikan</th>
                            <th class="bg-mechanic-header">Belum</th>
                            <th class="bg-operator-header">Verifikasi P2H</th>
                            <th class="bg-operator-header">Belum</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-monitoring">
                        <tr>
                            <td colspan="12" class="text-center py-4 text-muted small">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalBelumP2H" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">

            <div class="modal-header bg-gradient bg-danger text-white">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Unit Belum P2H
                    </h5>
                    <small class="opacity-75">
                        Daftar unit yang sudah login tetapi belum melakukan P2H
                    </small>
                </div>

                <button type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body p-0">

                <div class="table-responsive" style="max-height:450px;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-danger sticky-top">
                            <tr>
                                <th width="60">#</th>
                                <th>Nomor Unit</th>
                            </tr>
                        </thead>

                        <tbody id="tbody-detail-belum">
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer py-2">
                <button class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    .bg-table-header { background-color: #d9e1f2 !important; color: #000; font-weight: bold; font-size: 13px; border: 1px solid #a6b9d8 !important; }
    .table-bordered td, .table-bordered th { border: 1px solid #bfbfbf !important; vertical-align: middle !important; font-size: 13px; }
    .bg-warning-light { background-color: #fff2cc !important; }
    .bg-mechanic-header { background-color: #e2efda !important; }
    .bg-operator-header { background-color: #fce4d6 !important; }
    .belum-p2h-cell { background-color: #e2efda; color: red; font-weight: bold; }
    .has-defect { background-color: #fce4d6; font-weight: bold; }
    #btn_filter { height: 38px !important; display: flex; align-items: center; justify-content: center; }
    .link-detail-belum { color: red !important; text-decoration: underline; cursor: pointer; }
</style>

@include('layout.footer')

<script>
$(document).ready(function() {
    loadSummaryData();

    $('#btn_filter').on('click', function() {
        loadSummaryData();
    });

    function loadSummaryData() {
        $('#tbody-monitoring').html('<tr><td colspan="12" class="text-center py-3 text-muted small"><i class="fa fa-spinner fa-spin fa-sm me-1"></i> Sedang mengambil data...</td></tr>');

        $.ajax({
            url: "{{ route('p2h.api') }}",
            type: "GET",
            data: {
                tanggalP2H: $('#filter_tanggal').val(),
                shiftP2H: $('#filter_shift').val(),
                cluster: $('#filter_cluster').val()
            },
            dataType: 'json',
            success: function(response) {
                let rows = '';
                let data = response.data ? response.data : response;

                if(!Array.isArray(data) || data.length === 0) {
                    $('#tbody-monitoring').html('<tr><td colspan="12" class="text-center py-4 text-danger small">Data tidak ditemukan untuk filter ini.</td></tr>');
                    return;
                }

                data.forEach(function(item) {
                    let belumP2HRender = item.p2h_belum > 0
                        ? `<a class="link-detail-belum view-list-belum" data-units='${JSON.stringify(item.list_belum_p2h ?? [])}'>${item.p2h_belum}</a>`
                        : `0`;

                    rows += `
                        <tr class="data-row" data-tanggal="${item.tanggal ?? ''}" data-unit="${item.jenis_unit ?? ''}">
                            <td class="cell-tanggal">${item.tanggal ?? '-'}</td>
                            <td class="cell-unit">${item.jenis_unit ?? '-'}</td>
                            <td>${item.shift ?? '-'}</td>
                            <td>${item.unit_operasi ?? '-'}</td>
                            <td>${item.p2h_sudah ?? 0}</td>
                            <td class="belum-p2h-cell">${belumP2HRender}</td>
                            <td class="${(item.temuan ?? 0) > 0 ? 'has-defect' : ''}">${item.temuan ?? 0}</td>
                            <td>${item.mekanik_sudah ?? 0}</td>
                            <td>${item.mekanik_belum ?? 0}</td>
                            <td>${item.pengawas_sudah ?? 0}</td>
                            <td>${item.pengawas_belum ?? 0}</td>
                        </tr>
                    `;
                });

                $('#tbody-monitoring').html(rows);
                applyRowspanNew();
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error Details: ", xhr.responseText);
                $('#tbody-monitoring').html('<tr><td colspan="12" class="text-center py-4 text-danger small"><i class="fa fa-exclamation-triangle"></i> Gagal mengambil data dari server. (Status: ' + xhr.status + ')</td></tr>');
            }
        });
    }

    $(document).on('click', '.view-list-belum', function(e) {
        e.preventDefault();
        let listUnits = $(this).data('units');
        let detailHtml = '';

        if (!listUnits || listUnits.length === 0) {
            detailHtml = '<tr><td colspan="2" class="text-muted py-3">Tidak ada data detail nomor lambung.</td></tr>';
        } else {
            listUnits.forEach((vhc, index) => {
                detailHtml += `<tr><td>${index + 1}</td><td class="font-weight-bold text-dark">${vhc}</td></tr>`;
            });
        }

        $('#tbody-detail-belum').html(detailHtml);
        $('#modalBelumP2H').modal('show');
    });

    function applyRowspanNew() {
        let rows = $('#tbody-monitoring .data-row');
        let currentTanggalCell = null;
        let tanggalCount = 0;

        rows.each(function() {
            let tanggalCell = $(this).find('.cell-tanggal');
            if (currentTanggalCell === null) {
                currentTanggalCell = tanggalCell;
                tanggalCount = 1;
            } else if (tanggalCell.text() === currentTanggalCell.text()) {
                tanggalCell.remove();
                tanggalCount++;
                currentTanggalCell.attr('rowspan', tanggalCount);
            } else {
                currentTanggalCell = tanggalCell;
                tanggalCount = 1;
            }
        });

        let currentUnitCell = null;
        let unitCount = 0;
        let currentMatchKey = "";

        rows.each(function() {
            let unitCell = $(this).find('.cell-unit');
            let tKey = $(this).data('tanggal');
            let uKey = $(this).data('unit');
            let combinedKey = tKey + '_' + uKey;

            if (currentUnitCell === null) {
                currentUnitCell = unitCell;
                currentMatchKey = combinedKey;
                unitCount = 1;
            } else if (combinedKey === currentMatchKey) {
                unitCell.remove();
                unitCount++;
                currentUnitCell.attr('rowspan', unitCount);
            } else {
                currentUnitCell = unitCell;
                currentMatchKey = combinedKey;
                unitCount = 1;
            }
        });
    }
});
</script>

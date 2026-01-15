@include('layout.head', ['title' => 'Payload lebih dari 115'])
@include('layout.header')
@include('layout.sidebar')
@include('layout.styleSpinner')

<div class="page-body p-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header card-no-border d-flex align-items-center justify-content-between flex-wrap mt-4">
                        <h3 class="mb-0 me-3">Payload lebih dari 115</h3>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div style="min-width: 150px;">
                                <input class="form-control" id="datetime-local" name="tanggal" type="date" value="{{ date('Y-m-d') }}">
                            </div>
                            <div style="min-width: 120px;">
                                <select class="form-select" name="shift" id="select">
                                    <option value="ALL">ALL</option>
                                    <option value="Siang">Siang</option>
                                    <option value="Malam">Malam</option>
                                </select>
                            </div>
                            <div style="min-width: 90px;">
                                <button id="search" class="btn btn-primary w-100">Cari</button>
                            </div>
                            <div style="min-width: 110px;">
                                <button id="excel" class="btn btn-info w-100">Export Excel</button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        <!-- Shift Siang -->
                        <div class="mb-4"> <!-- mb-4 memberi margin bawah -->
                            <h5 id="headerShiftSiang">Shift Siang</h5>
                            <div id="loadingSpinner-6" style="display:none;text-align:center;margin:20px;">
                                <div class="blinking-text">
                                    <span class="loading-char">L</span>
                                    <span class="loading-char">O</span>
                                    <span class="loading-char">A</span>
                                    <span class="loading-char">D</span>
                                    <span class="loading-char">I</span>
                                    <span class="loading-char">N</span>
                                    <span class="loading-char">G</span>
                                    <span class="loading-char">.</span>
                                    <span class="loading-char">.</span>
                                    <span class="loading-char">.</span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="display" id="basic-6">
                                    <thead>
                                        <tr id="dynamic-header-6">
                                            <th>Loader</th>
                                            <th>Operator</th>
                                            <th>Shift</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Shift Malam -->
                        <div class="mt-4"> <!-- mt-4 memberi margin atas -->
                            <h5 id="headerShiftMalam">Shift Malam</h5>
                            <div id="loadingSpinner-7" style="display:none;text-align:center;margin:20px;">
                                <div class="blinking-text">
                                    <span class="loading-char">L</span>
                                    <span class="loading-char">O</span>
                                    <span class="loading-char">A</span>
                                    <span class="loading-char">D</span>
                                    <span class="loading-char">I</span>
                                    <span class="loading-char">N</span>
                                    <span class="loading-char">G</span>
                                    <span class="loading-char">.</span>
                                    <span class="loading-char">.</span>
                                    <span class="loading-char">.</span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="display" id="basic-7">
                                    <thead>
                                        <tr id="dynamic-header-7">
                                            <th>Loader</th>
                                            <th>Operator</th>
                                            <th>Shift</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                    </div>


                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail HD</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>HD</th>
                                <th>Shift</th>
                                <th>Tonnage</th>
                                <th>Date</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="detailsBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@include('layout.footer')

<script>
$(document).ready(function() {
    function generateHeaders(tableId, headerId, shift) {
        let headerRow = $(headerId);
        headerRow.find('th:gt(2)').remove();
        let jamRange = [];
        if (shift === 'Siang' || shift === 'ALL') {
            jamRange = [...Array(12).keys()].map(i => i+7); // 7-18
        } else if (shift === 'Malam') {
            jamRange = [...Array(5).keys()].map(i => i+19).concat([...Array(7).keys()]); // 19-24 + 0-6
        }
        jamRange.forEach(h => headerRow.append(`<th>${h}</th>`));
    }

    function loadTable(tableId, headerId, loadingId, tanggal, shift) {
        $(loadingId).show();
        generateHeaders(tableId, headerId, shift);

        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }

        let table = $(tableId).DataTable({
            paging: false,
            searching: false,
            ordering: false,
            info: false
        });

        $.ajax({
            url: "{{ route('payload.ex.apiOneHundredandFifteen') }}",
            data: { tanggal: tanggal, shift: shift },
            dataType: 'json',
            success: function(response) {
                table.clear();
                if(response.data && response.data.length > 0) {
                    let mappedData = response.data.map(item => {
                        let row = [item.Loader, item.Operator, item.Shift];
                        $(headerId + ' th:gt(2)').each(function(){
                            let jam = $(this).text().trim();
                            let jamKey = Number(jam);
                            let jamData = item.Jam[jamKey];
                            if(jamData && jamData.count>0){
                                row.push(`<a href="#" class="show-details" data-loader="${item.Loader}" data-details='${JSON.stringify(jamData.details)}'>${jamData.count}</a>`);
                            } else { row.push(''); }
                        });
                        return row;
                    });
                    table.rows.add(mappedData).draw();
                }
            },
            complete: function(){ $(loadingId).hide(); }
        });

        // Event detail
        $(tableId).off('click', '.show-details').on('click', '.show-details', function(e){
            e.preventDefault();
            let details = $(this).data('details');
            let loader = $(this).data('loader');
            let tbody = $('#detailsBody');
            $('#detailsModal .modal-title').text('Detail HD di ' + loader);
            tbody.empty();
            details.forEach(d=>{
                tbody.append(`<tr>
                    <td>${d.HD}</td>
                    <td>${d.Shift}</td>
                    <td>${Number(d.Tonnage).toFixed(1)}</td>
                    <td>${d.Date}</td>
                    <td>${d.Time}</td>
                </tr>`);
            });
            $('#detailsModal').modal('show');
        });
    }

    function clearTable(selector) {
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().clear().draw();
        } else {
            $(selector + " tbody").empty(); // fallback kalau bukan DataTable
        }
    }

    function loadAll(tanggal, shift){
        clearTable('#basic-6');
        clearTable('#basic-7');

        if(shift==='Siang'){
            $('#basic-6').parent().parent().show(); // tampilkan container shift siang
            $('#loadingSpinner-6').show();
            $('#headerShiftSiang').show();
            $('#headerShiftMalam').hide();
            loadTable('#basic-6','#dynamic-header-6','#loadingSpinner-6',tanggal,'Siang');

            $('#basic-7').parent().parent().hide(); // sembunyikan shift malam
        }
        else if(shift==='Malam'){
            $('#basic-7').parent().parent().show();
            $('#loadingSpinner-7').show();
            $('#headerShiftSiang').hide();
            $('#headerShiftMalam').show();
            loadTable('#basic-7','#dynamic-header-7','#loadingSpinner-7',tanggal,'Malam');

            $('#basic-6').parent().parent().hide();
        }
        else{ // ALL
            $('#basic-6').parent().parent().show();
            $('#basic-7').parent().parent().show();
            $('#headerShiftSiang').show();
            $('#headerShiftMalam').show();
            loadTable('#basic-6','#dynamic-header-6','#loadingSpinner-6',tanggal,'Siang');
            loadTable('#basic-7','#dynamic-header-7','#loadingSpinner-7',tanggal,'Malam');
        }
    }


    $('#search').on('click', function(e){
        e.preventDefault();
        let tanggal = $('#datetime-local').val();
        let shift = $('#select').val();
        loadAll(tanggal, shift);
    });

    $('#excel').on('click', function(e){
        e.preventDefault();
        let tanggal = $('#datetime-local').val();
        let shift = $('#select').val();
        let url = "{{ route('payload.ex.excelOneHundredandFifteen') }}" + `?tanggal=${encodeURIComponent(tanggal)}&shift=${encodeURIComponent(shift)}`;
        window.location.href = url;
    });

    // Load default
    loadAll('{{ date("Y-m-d") }}','ALL');
});
</script>

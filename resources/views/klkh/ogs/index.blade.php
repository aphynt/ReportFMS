@include('layout.head', ['title' => 'KLKH OGS'])
@include('layout.header')
@include('layout.sidebar')
<style>
    .loading-char {
        font-weight: bold;
        font-size: 1.5rem;
        color: #007bff;
        opacity: 0.2;
        animation: blink 1.5s infinite;
        display: inline-block;
    }

    .loading-char:nth-child(1) { animation-delay: 0s; }
    .loading-char:nth-child(2) { animation-delay: 0.1s; }
    .loading-char:nth-child(3) { animation-delay: 0.2s; }
    .loading-char:nth-child(4) { animation-delay: 0.3s; }
    .loading-char:nth-child(5) { animation-delay: 0.4s; }
    .loading-char:nth-child(6) { animation-delay: 0.5s; }
    .loading-char:nth-child(7) { animation-delay: 0.6s; }
    .loading-char:nth-child(8) { animation-delay: 0.7s; }
    .loading-char:nth-child(9) { animation-delay: 0.8s; }
    .loading-char:nth-child(10) { animation-delay: 0.9s; }

    @keyframes blink {
        0%, 100% { opacity: 0.2; }
        50% { opacity: 1; }
    }
</style>

<div class="page-body">
    <div class="container-fluid">
        <div class="page-title">
            <div class="row">
                {{-- <form action="" method="GET"> --}}
                {{-- <div class="row g-3"> --}}
                        <div class="col-md-2 col-sm-6 col-12">
                            <label for="range-date-1" class="form-label">Tanggal</label>
                            <div class="input-group">
                                <input class="form-control" id="range-date" name="tanggal" type="date">
                            </div>
                        </div>


                        {{-- <div class="col-md-9 col-sm-6 col-12 p-2">

                        </div> --}}


                        <div class="col-md-1 col-sm-6 col-12 p-2">
                            <label for="range-date-3" class="form-label">.</label>
                            <div class="input-group">
                                <button id="search" class="btn btn-primary w-100">Cari</button>
                            </div>
                        </div>

                        {{-- <div class="col-md-1 col-sm-6 col-12">
                            <label for="range-date-3" class="form-label">.</label>
                            <div class="input-group">
                                <button id="excel" class="btn btn-info w-100">Export Excel</button>
                            </div>
                        </div> --}}

                    {{-- </div> --}}
                {{-- </form> --}}

            </div>
        </div>
    </div>
    <!-- Container-fluid starts-->
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header card-no-border pb-3">
                        <h3>KLKH OGS</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">

                        </div>
                        <div id="loadingSpinner" style="display: none; text-align: center; margin: 20px;">
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
                            <p style="margin-top: 10px;">Sedang memuat data...</p>
                        </div>
                        <div class="table-responsive">
                            <table class="display" id="export-button">
                                <thead>
                                    <tr>
                                        <th>DATE</th>
                                        <th>TIME</th>
                                        <th>PIT</th>
                                        <th>SHIFT</th>
                                        <th>VALUE</th>
                                        <th style="min-width: 200px;">FIELD</th>
                                        <th style="min-width: 200px;">NOTES</th>
                                        <th style="min-width: 50px;">PIC</th>
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
    <!-- Container-fluid Ends-->
</div>
@include('layout.footer')

<script>
    $(document).ready(function () {
        let table = $('#export-button').DataTable();

        function loadTableData(tanggal) {
            $('#loadingSpinner').show();

            $.ajax({
                url: "{{ route('ogs.api') }}",
                type: 'GET',
                dataType: 'json',
                data: {
                    tanggal: tanggal
                },
                success: function (response) {
                    table.clear();

                    if (response.data && response.data.length > 0) {
                        let mappedData = response.data.map(item => {
                            let value = item.VALUE?.toLowerCase();
                            let mappedValue =
                                value === 'false' ? 'Tidak' :
                                value === 'n/a' ? 'N/A' :
                                'Ya';
                            return [
                                item.DATE,
                                item.TIME,
                                item.PIT,
                                item.SHIFT,
                                mappedValue,
                                item.FIELD,
                                item.NOTES,
                                item.NAMA_PIC
                            ];
                        });

                        table.rows.add(mappedData).draw();
                    } else {
                        table.clear().draw();
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                },
                complete: function () {
                    $('#loadingSpinner').hide();
                }
            });
        }

        $('#search').on('click', function (e) {
            e.preventDefault();

            let tanggal = $('#range-date').val();

            loadTableData(tanggal);
        });

        function loadExcel(tanggal) {
            let url = "{{ route('ogs.excel') }}" + `?tanggal=${encodeURIComponent(tanggal)}`;
            window.location.href = url;
        }

        $('#excel').on('click', function (e) {
            e.preventDefault();
            let tanggal = $('#range-date').val();
            loadExcel(tanggal);
        });

        loadTableData('');
    });
</script>


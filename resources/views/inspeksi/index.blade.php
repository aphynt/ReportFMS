@include('layout.head', ['title' => 'Laporan Inspeksi'])
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

    #basic-6 th:nth-child(3),
    #basic-6 td:nth-child(3) {
        width: 80px;          /* bisa dikecilin lagi kalau mau, misal 60px */
        white-space: nowrap;  /* biar tidak turun ke bawah */
        text-align: center;   /* optional: tengahin teks */
    }

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

                        <div class="col-md-2 col-sm-6 col-12">
                            <label for="range-date-3" class="form-label">Shift</label>
                            <select class="form-select" name="shift" id="select">
                              <option value="ALL">ALL</option>
                              <option value="Siang">Siang</option>
                              <option value="Malam">Malam</option>
                            </select>
                        </div>

                        <div class="col-md-6 col-sm-6 col-12">

                        </div>


                        <div class="col-md-1 col-sm-6 col-12 p-2">
                            <label for="range-date-3" class="form-label">.</label>
                            <div class="input-group">
                                <button id="search" class="btn btn-primary w-100">Cari</button>
                            </div>
                        </div>

                        <div class="col-md-1 col-sm-6 col-12">
                            <label for="range-date-3" class="form-label">.</label>
                            <div class="input-group">
                                <button id="excel" class="btn btn-info w-100">Export Excel</button>
                            </div>
                        </div>

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
                        <h3>Laporan Inspeksi</h3>
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
                            <table class="display" id="basic-6">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal Pelaporan</th>
                                        <th>Jam Kejadian</th>
                                        <th>Shift</th>
                                        <th>NIK</th>
                                        <th>Nama PIC</th>
                                        <th>Area</th>
                                        <th>Temuan KTA/TTA</th>
                                        <th>Dokumentasi Temuan</th>
                                        <th>Tingkat Risiko</th>
                                        <th>Risiko</th>
                                        <th>Pengendalian</th>
                                        <th>Tindak Lanjut</th>
                                        <th>Dokumentasi Tindak Lanjut</th>
                                        <th>Status</th>
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

    let table = $('#basic-6').DataTable({
        destroy: true,
        pageLength: 50,
    });
    function formatJamKejadian(jam) {
        if (!jam) return "";

        let s = jam.toString().trim();
        if (s.includes(" ")) {
            s = s.split(" ")[1];
        }
        if (s.includes("T")) {
            s = s.split("T")[1];
        }
        const match = s.match(/^(\d{1,2}):(\d{2})/);
        if (match) {
            const hh = match[1].padStart(2, "0");
            const mm = match[2];
            return `${hh}:${mm}`;
        }

        return s;
    }

    function formatCreatedAt(val) {
        if (!val) return "";

        let s = val.toString().trim().replace("T", " ");

        if (s.length >= 16) {
            return s.substring(0, 16);
        }

        return s;
    }



    function loadTableData(tanggal, shift) {
        $('#loadingSpinner').show();

        $.ajax({
            url: "{{ route('inspeksi.api') }}",
            type: 'GET',
            dataType: 'json',
            data: {
                tanggal: tanggal,
                shift: shift
            },
            success: function (response) {
                table.clear();

                if (response.data && response.data.length > 0) {

                    let no = 1;

                    let mappedData = response.data.map(item => {
                        return [
                            no++,
                            formatCreatedAt(item.created_at),
                            formatJamKejadian(item.jam_kejadian),
                            item.shift,
                            item.nik_pic,
                            item.pic,
                            item.area,
                            item.temuan
                            ? `<div class="multiline">${item.temuan}</div>`
                            : '-',
                            item.file_temuan
                                ? `<img src="${item.file_temuan}"
                                        style="max-width:150px; cursor:pointer; border-radius:4px;"
                                        onclick="window.open('${item.file_temuan}', '_blank')" />`
                                : '-',

                            item.tingkat_risiko,
                            item.risiko
                            ? `<div class="multiline">${item.risiko}</div>`
                            : '-',
                            item.pengendalian
                            ? `<div class="multiline">${item.pengendalian}</div>`
                            : '-',
                            item.tindak_lanjut
                            ? `<div class="multiline">${item.tindak_lanjut}</div>`
                            : '-',

                            item.file_tindakLanjut
                                ? `<img src="${item.file_tindakLanjut}"
                                        style="max-width:150px; cursor:pointer; border-radius:4px;"
                                        onclick="window.open('${item.file_tindakLanjut}', '_blank')" />`
                                : '-',

                            item.is_finish,
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
        let shift = $('#select').val();

        loadTableData(tanggal, shift);
    });

    $('#excel').on('click', function (e) {
        e.preventDefault();

        let tanggal = $('#range-date').val();
        let shift   = $('#select').val();

        const params = new URLSearchParams({
            tanggal: tanggal || '',
            shift: shift || '',
            export: 'excel'
        });

        window.location.href = "{{ route('inspeksi.api') }}?" + params.toString();
    });


    // Load default
    loadTableData('', '', '');
});


</script>


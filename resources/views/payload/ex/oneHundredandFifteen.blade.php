@include('layout.head', ['title' => 'Payload lebih dari 115'])
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

    .loading-char:nth-child(1) {
        animation-delay: 0s;
    }

    .loading-char:nth-child(2) {
        animation-delay: 0.1s;
    }

    .loading-char:nth-child(3) {
        animation-delay: 0.2s;
    }

    .loading-char:nth-child(4) {
        animation-delay: 0.3s;
    }

    .loading-char:nth-child(5) {
        animation-delay: 0.4s;
    }

    .loading-char:nth-child(6) {
        animation-delay: 0.5s;
    }

    .loading-char:nth-child(7) {
        animation-delay: 0.6s;
    }

    .loading-char:nth-child(8) {
        animation-delay: 0.7s;
    }

    .loading-char:nth-child(9) {
        animation-delay: 0.8s;
    }

    .loading-char:nth-child(10) {
        animation-delay: 0.9s;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 0.2;
        }

        50% {
            opacity: 1;
        }
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
                        <h3>Payload lebih dari 115</h3>
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
                                        <th>Loader</th>
                                        <th>Operator</th>
                                        <th>Shift</th>
                                        <th>7</th>
                                        <th>8</th>
                                        <th>9</th>
                                        <th>10</th>
                                        <th>11</th>
                                        <th>12</th>
                                        <th>13</th>
                                        <th>14</th>
                                        <th>15</th>
                                        <th>16</th>
                                        <th>17</th>
                                        <th>18</th>
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

    <!-- Container-fluid Ends-->
</div>
@include('layout.footer')
<script>
    $(document).ready(function () {
        let table = $('#basic-6').DataTable();

        function loadTableData(tanggal, shift) {
            $('#loadingSpinner').show();

            $.ajax({
                url: "{{ route('payload.ex.apiOneHundredandFifteen') }}",
                type: 'GET',
                dataType: 'json',
                data: {
                    tanggal: tanggal,
                    shift: shift
                },
                success: function (response) {
                    table.clear();

                    if (response.data && response.data.length > 0) {
                        let mappedData = response.data.map(item => {
                            let row = [
                                item.Loader,
                                item.Operator,
                                item.Shift
                            ];

                            for (let h = 7; h <= 18; h++) {
                                let jamData = item.Jam[h];
                                if (jamData.count > 0) {
                                    row.push(`
                                        <a href="#"
                                        class="show-details"
                                        data-loader="${item.Loader}"
                                        data-details='${JSON.stringify(jamData.details)}'>
                                        ${jamData.count}
                                        </a>`);
                                } else {
                                    row.push('');
                                }
                            }

                            return row;
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

        function loadExcel(tanggal, shift) {
            let url = "{{ route('payload.ex.excelOneHundredandFifteen') }}" +
                `?tanggal=${encodeURIComponent(tanggal)}&shift=${encodeURIComponent(shift)}`;
            window.location.href = url;
        }

        $('#excel').on('click', function (e) {
            e.preventDefault();
            let tanggal = $('#range-date').val();
            let shift = $('#select').val();
            loadExcel(tanggal, shift);
        });

        loadTableData('', '', '');
    });

    $('#basic-6').on('click', '.show-details', function (e) {
        e.preventDefault();

        let details = $(this).data('details');
        let loader = $(this).data('loader'); // ambil Loader
        let tbody = $('#detailsBody');

        // ubah judul modal
        $('#detailsModal .modal-title').text('Detail HD di ' + loader);

        tbody.empty();
        details.forEach(d => {
            tbody.append(`<tr>
                <td>${d.HD}</td>
                <td>${d.Shift}</td>
                <td>${d.Tonnage}</td>
                <td>${d.Date}</td>
                <td>${d.Time}</td>
            </tr>`);
        });

        $('#detailsModal').modal('show');
    });

</script>

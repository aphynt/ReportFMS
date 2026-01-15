@include('layout.head', ['title' => 'Plan EX Per Jam'])
@include('layout.header')
@include('layout.sidebar')
@include('layout.styleSpinner')

<div class="page-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header card-no-border d-flex align-items-center justify-content-between flex-wrap mt-4">
                        <h3 class="mb-0 me-3">Plan EX Per Jam</h3>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div style="min-width: 120px;">
                                <select class="form-select" name="loader" id="loader">
                                    <option value="ALL">ALL</option>
                                    @foreach ($unit as $unt)
                                    <option value="{{ $unt->VHC_ID }}">{{ $unt->VHC_ID }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="min-width: 90px;">
                                <button id="search" class="btn btn-primary w-100">Cari</button>
                            </div>
                            <div style="min-width: 90px;">
                                <button class="btn btn-success" id="btn-open-create">Tambah</button>
                            </div>
                            @include('plan.modal.create')
                        </div>
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
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Loader</th>
                                        <th>Days</th>
                                        <th>Hour</th>
                                        <th>Value</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>

                            </table>
                        </div>
                        @include('plan.modal.edit')
                        @include('plan.modal.delete')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Container-fluid Ends-->
</div>
@include('layout.footer')
@include('layout.sweetalert')

<script>
    $(document).ready(function () {

        let table = $('#basic-6').DataTable({
            destroy: true,
            pageLength: 50,
            autoWidth: false,   // penting supaya width bisa diatur manual
            columnDefs: [
                {
                    targets: 4,        // index kolom Days (0=No,1=Start,2=End,3=Loader,4=Days)
                    width: '400px',    // sesuaikan, misal 260px / 300px
                    className: 'text-start'
                }
            ]
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

        function formatForInput(val) {
            if (!val) return '';

            // contoh input:
            // 2026-01-15 08:30:00
            // 2026-01-15T08:30:00.000Z

            let s = val.toString();

            // buang milliseconds & timezone
            s = s.replace('.000Z', '');
            s = s.replace('Z', '');

            // pastikan pakai T
            if (s.includes(' ')) {
                s = s.replace(' ', 'T');
            }

            // ambil sampai menit saja
            return s.substring(0, 16);
        }

        function renderDayBadges(daysText) {
            if (!daysText) return '';

            const colorMap = {
                'Senin':  'primary',
                'Selasa': 'info',
                'Rabu':   'success',
                'Kamis':  'warning',
                'Jumat':  'danger',
                'Sabtu':  'secondary',
                'Minggu': 'dark',
            };

            let days = daysText.split(',').map(d => d.trim());

            return days.map(d => {
                let color = colorMap[d] || 'secondary';
                return `<span class="badge bg-${color} me-1">${d}</span>`;
            }).join('');
        }

        function setDayCheckboxes(daysRaw) {
            // reset semua dulu
            $('.day-checkbox').prop('checked', false);

            if (!daysRaw) return;

            let days = [];

            try {
                // kalau bentuknya "[1,2,3]"
                days = JSON.parse(daysRaw);
            } catch (e) {
                // fallback kalau bentuknya "1,2,3"
                days = daysRaw.split(',').map(d => parseInt(d.trim()));
            }

            days.forEach(d => {
                $(`#day-${d}`).prop('checked', true);
            });
        }

        function getSelectedDays() {
            let days = [];
            $('.day-checkbox:checked').each(function () {
                days.push(parseInt($(this).val()));
            });
            return days;
        }

        // buka modal tambah
        $('#btn-open-create').on('click', function () {
            $('#modalCreate').modal('show');
        });

        // simpan data baru
        $('#btn-save-create').on('click', function () {

            let days = [];
            $('.add-day:checked').each(function () {
                days.push(parseInt($(this).val()));
            });

            let payload = {
                _token: '{{ csrf_token() }}',
                start_date: $('#add-start_date').val(),
                end_date: $('#add-end_date').val(),
                vhc_id: $('#add-vhc_id').val(),
                time_range: $('#add-time_range').val(),
                value: $('#add-value').val(),
                plan_days: days
            };

            $.ajax({
                url: "{{ route('plan.ex.store') }}",
                type: "POST",
                data: payload,
                success: function (res) {
                    $('#modalCreate').modal('hide');
                    showSuccessAlert('Sukses', res.msg);
                    $('#search').click();
                },
                error: function (xhr) {
                    let msg = 'Gagal menambahkan data';
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        msg = xhr.responseJSON.msg;
                    }
                    showErrorAlert('Gagal', msg);
                }
            });
        });

        $('#basic-6 tbody').on('click', '.btn-edit', function () {

            let btn = $(this);

            $('#edit-id').val(btn.data('id'));
            $('#edit-start_date').val(formatForInput(btn.data('start_date')));
            $('#edit-end_date').val(formatForInput(btn.data('end_date')));
            $('#edit-vhc_id').val(btn.data('vhc_id'));
            $('#edit-time_range').val(btn.data('time_range'));
            $('#edit-value').val(btn.data('value'));
            let daysRaw = btn.attr('data-plan_days');
            setDayCheckboxes(daysRaw);
            $('#modalEdit').modal('show');
        });

        $('#basic-6 tbody').on('click', '.btn-delete', function () {
            let id = $(this).data('id');

            $('#delete-id').val(id);
            $('#modalDelete').modal('show');
        });

        $('#btn-save-edit').on('click', function () {

            let payload = {
                _token: '{{ csrf_token() }}',
                id: $('#edit-id').val(),
                start_date: $('#edit-start_date').val(),
                end_date: $('#edit-end_date').val(),
                vhc_id: $('#edit-vhc_id').val(),
                time_range: $('#edit-time_range').val(),
                value: $('#edit-value').val(),
                plan_days: JSON.stringify(getSelectedDays())
            };

            $.ajax({
                url: "{{ route('plan.ex.update') }}",   // buat route ini
                type: "POST",
                data: payload,
                success: function () {
                    $('#modalEdit').modal('hide');
                    showSuccessAlert(
                    'Sukses!',
                    'Plan EX berhasil di-update.'
                    );
                    $('#search').click();
                },
                error: function (xhr) {
                    let msg = 'Data gagal diperbarui';
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        msg = xhr.responseJSON.msg;
                    }

                    showErrorAlert(
                    'Update Gagal',
                    msg
                    );
                }
            });
        });


        // HAPUS DATA
        $('#btn-confirm-delete').on('click', function () {
            let id = $('#delete-id').val();

            $.ajax({
                url: '/plan/ex/delete/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function () {
                    $('#modalDelete').modal('hide');
                    showSuccessAlert(
                    'Sukses!',
                    'Plan EX berhasil di hapus'
                    );
                    $('#search').click();
                },
                error: function (xhr) {
                    let msg = 'Data gagal dihapus';
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        msg = xhr.responseJSON.msg;
                    }

                    showErrorAlert(
                    'Update Gagal',
                    msg
                    );
                }
            });
        });


        function loadTableData(loader) {
            $('#loadingSpinner').show();

            $.ajax({
                url: "{{ route('plan.ex.api') }}",
                type: 'GET',
                dataType: 'json',
                data: {
                    loader: loader
                },
                success: function (response) {
                    table.clear();

                    if (response.data && response.data.length > 0) {

                        let no = 1;

                        let mappedData = response.data.map(item => {
                            return [
                                no++,
                                formatCreatedAt(item.start_date),
                                formatCreatedAt(item.end_date),
                                item.vhc_id,
                                renderDayBadges(item.plan_days),
                                item.time_range,
                                item.value,

                                `
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="javascript:void(0)"
                                    class="text-primary btn-edit"
                                    data-id="${item.id}"
                                    data-start_date="${item.start_date}"
                                    data-end_date="${item.end_date}"
                                    data-vhc_id="${item.vhc_id}"
                                    data-time_range="${item.time_range}"
                                    data-value="${item.value}"
                                    data-plan_days="${item.plan_days_raw}"">
                                        <i class="fi fi-rr-edit"></i>
                                    </a>

                                    <a href="javascript:void(0)"
                                    class="text-danger btn-delete"
                                    data-id="${item.id}">
                                        <i class="fi fi-rr-trash"></i>
                                    </a>
                                </div>
                                `

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

            let loader = $('#loader').val();

            loadTableData(loader);
        });

        // Load default
        loadTableData('');
    });

</script>

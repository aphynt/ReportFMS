@include('layout.head', ['title' => 'Payload per Excavator'])
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

                        <div class="col-md-2 col-sm-6 col-12">
                            <label for="range-date-3" class="form-label">Shift</label>
                            <select class="form-select" name="shift" id="select">
                              <option value="ALL">ALL</option>
                              <option value="Siang">Siang</option>
                              <option value="Malam">Malam</option>
                            </select>
                        </div>

                        <!-- Range 2 -->
                        <div class="col-md-5 col-sm-6 col-12">
                            <label for="range-date-2" class="form-label">Loader</label>
                            <div class="input-group">
                                <input class="some_class_name" name="input-custom-ex" placeholder="write some tags" value="ALL">
                            </div>
                        </div>

                        <div class="col-md-1 col-sm-6 col-12 p-2">

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
                        <h3>Payload per Excavator List</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- <div class="col-xl-4 col-sm-6 box-col-6">
                                <div class="card ecommerce-widget">
                                    <div class="card-body support-ticket-font">
                                        <div class="row">
                                            <div class="col-5"><span>Order</span>
                                                <h3 class="total-num counter">2563</h3>
                                            </div>
                                            <div class="col-7">
                                                <div class="text-end">
                                                    <ul>
                                                        <li>Profit<span class="product-stts text-success ms-2">8989<i class="fi fi-rr-angle-small-up"></i></i></span></li>
                                                        <li>Loss<span class="product-stts text-danger ms-2">2560<i class="fi fi-rr-angle-small-down"></i></span></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="progress-showcase">
                                            <div class="progress sm-progress-bar">
                                                <div class="progress-bar bg-primary" role="progressbar"
                                                    style="width: 70%" aria-valuenow="25" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-sm-6 box-col-6">
                                <div class="card ecommerce-widget">
                                    <div class="card-body support-ticket-font">
                                        <div class="row">
                                            <div class="col-5"><span>Pending</span>
                                                <h3 class="total-num counter">8943</h3>
                                            </div>
                                            <div class="col-7">
                                                <div class="text-end">
                                                    <ul>
                                                        <li>Profit<span class="product-stts text-success ms-2">8989<i class="fi fi-rr-angle-small-up"></i></i></span></li>
                                                        <li>Loss<span class="product-stts text-danger ms-2">2560<i class="fi fi-rr-angle-small-down"></i></span></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="progress-showcase">
                                            <div class="progress sm-progress-bar">
                                                <div class="progress-bar bg-secondary" role="progressbar"
                                                    style="width: 70%" aria-valuenow="25" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-sm-6 box-col-6">
                                <div class="card ecommerce-widget">
                                    <div class="card-body support-ticket-font">
                                        <div class="row">
                                            <div class="col-5"><span>Running</span>
                                                <h3 class="total-num counter">2500</h3>
                                            </div>
                                            <div class="col-7">
                                                <div class="text-end">
                                                    <ul>
                                                        <li>Profit<span class="product-stts text-success ms-2">8989<i class="fi fi-rr-angle-small-up"></i></i></span></li>
                                                        <li>Loss<span class="product-stts text-danger ms-2">2560<i class="fi fi-rr-angle-small-down"></i></span></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="progress-showcase">
                                            <div class="progress sm-progress-bar">
                                                <div class="progress-bar bg-warning" role="progressbar"
                                                    style="width: 70%" aria-valuenow="25" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> --}}
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
                                        <th>Vehicle</th>
                                        <th>Loader</th>
                                        <th>NRP Loader</th>
                                        <th>Name Loader</th>
                                        <th>Shift</th>
                                        <th>Report Time</th>
                                        <th>Login Time</th>
                                        <th>Logout Time</th>
                                        <th>Ritation Tonnage</th>
                                        <th>Category</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                {{-- <tfoot>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Loader</th>
                                        <th>NRP Loader</th>
                                        <th>Name Loader</th>
                                        <th>Shift</th>
                                        <th>Report Time</th>
                                        <th>Login Time</th>
                                        <th>Logout Time</th>
                                        <th>Ritation Tonnage</th>
                                        <th>Category</th>
                                    </tr>
                                </tfoot> --}}
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
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.querySelector('input[name="input-custom-ex"]');

        const originalWhitelist = @json($ex->pluck('VHC_ID')->prepend('ALL'));

        const myTagify = new Tagify(input, {
            whitelist: originalWhitelist,
            maxTags: 30,
            dropdown: {
                maxItems: 30,
                classname: "tags-look",
                enabled: 0,
                closeOnSelect: false
            }
        });

        if (myTagify.value.length === 0) {
            myTagify.addTags(['ALL']);
        }

        // Saat tag ditambahkan
        myTagify.on('add', function(e) {
            const tags = myTagify.value.map(item => item.value);

            if (tags.includes('ALL') && tags.length > 1) {
                myTagify.removeTag('ALL');
            }

            if (tags.length > 0 && myTagify.settings.whitelist.includes('ALL')) {
                myTagify.settings.whitelist = originalWhitelist.filter(item => item !== 'ALL');
            }
        });

        myTagify.on('remove', function(e) {
            const tags = myTagify.value.map(item => item.value);

            if (tags.length === 0) {
                myTagify.settings.whitelist = originalWhitelist;
                myTagify.addTags(['ALL']);
            }
        });
    });
</script>


<script>
    $(document).ready(function () {
        let table = $('#basic-6').DataTable();

        function loadTableData(tanggal, shift, ex) {
            $('#loadingSpinner').show();

            $.ajax({
                url: "{{ route('payload.ex.api') }}",
                type: 'GET',
                dataType: 'json',
                data: {
                    tanggal: tanggal,
                    shift: shift,
                    ex: ex
                },
                success: function (response) {
                    table.clear();

                    if (response.data && response.data.length > 0) {
                        let mappedData = response.data.map(item => {
                            return [
                                item.VHC_ID,
                                item.LOD_LOADERID,
                                item.OPR_NRP,
                                item.PERSONALNAME,
                                item.OPR_SHIFTNO,
                                item.OPR_REPORTTIME,
                                item.LOGIN_TIME,
                                item.LOGOUT_TIME,
                                item.RIT_TONNAGE,
                                item.TONNAGE_CATEGORY
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
            let ex = $('[name="input-custom-ex"]').val();

            loadTableData(tanggal, shift, ex);
        });

        function loadExcel(tanggal, shift, ex) {
            let url = "{{ route('payload.ex.excel') }}" + `?tanggal=${encodeURIComponent(tanggal)}&shift=${encodeURIComponent(shift)}&ex=${encodeURIComponent(ex)}`;
            window.location.href = url;
        }

        $('#excel').on('click', function (e) {
            e.preventDefault();
            let tanggal = $('#range-date').val();
            let shift = $('#select').val();
            let ex = $('[name="input-custom-ex"]').val();
            loadExcel(tanggal, shift, ex);
        });

        loadTableData('', '', '');
    });
</script>


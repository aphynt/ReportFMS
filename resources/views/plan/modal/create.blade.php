<div class="modal fade" id="modalCreate" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-3 shadow">

      <!-- HEADER -->
      <div class="modal-header px-4 py-3">
        <h5 class="modal-title fw-semibold">Tambah Plan EX</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- BODY -->
      <div class="modal-body px-4 py-4">

        <!-- CARD WRAPPER -->
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">

            <div class="row gy-3">

              <!-- Loader -->
              <div class="col-12">
                <label class="form-label fw-semibold">Loader</label>
                <select class="form-select" name="add-vhc_id" id="add-vhc_id">
                    <option>Silakan pilih EX</option>
                        @foreach ($unit as $untt)
                            <option value="{{ $untt->VHC_ID }}">{{ $untt->VHC_ID }}</option>
                        @endforeach
                </select>
              </div>

              <!-- Start Date -->
              <div class="col-12">
                <label class="form-label fw-semibold">Start Date</label>
                <input type="date"
                       class="form-control form-control-lg"
                       id="add-start_date">
              </div>

              <!-- End Date -->
              <div class="col-12">
                <label class="form-label fw-semibold">End Date</label>
                <input type="date"
                       class="form-control form-control-lg"
                       id="add-end_date">
              </div>

              <!-- Days -->
              <div class="mb-3">
                <label class="form-label fw-semibold">Days</label>
                <div class="d-flex flex-wrap gap-2">

                  @php
                    $days = [
                      1 => 'Senin',
                      2 => 'Selasa',
                      3 => 'Rabu',
                      4 => 'Kamis',
                      5 => 'Jumat',
                      6 => 'Sabtu',
                      7 => 'Minggu'
                    ];
                  @endphp

                  @foreach($days as $k => $v)
                    <div class="form-check">
                      <input class="form-check-input add-day"
                             type="checkbox"
                             id="add-day-{{ $k }}"
                             value="{{ $k }}">
                      <label class="form-check-label"
                             for="add-day-{{ $k }}">
                        {{ $v }}
                      </label>
                    </div>
                  @endforeach

                </div>
              </div>

              <!-- Hour -->
              <div class="col-12">
                <label class="form-label fw-semibold">Hour</label>
                <input type="number"
                       class="form-control form-control-lg"
                       id="add-time_range"
                       min="0" max="23">
              </div>

              <!-- Value -->
              <div class="col-12">
                <label class="form-label fw-semibold">Value</label>
                <input type="number"
                       class="form-control form-control-lg"
                       id="add-value">
              </div>

            </div>

          </div>
        </div>
        <!-- END CARD -->

      </div>

      <!-- FOOTER -->
      <div class="modal-footer px-4 py-3">
        <button class="btn btn-outline-secondary px-4"
                data-bs-dismiss="modal">
          Batal
        </button>
        <button class="btn btn-primary px-4"
                id="btn-save-create">
          Simpan
        </button>
      </div>

    </div>
  </div>
</div>

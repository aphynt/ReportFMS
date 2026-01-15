<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-3 shadow">

      <div class="modal-header px-4 py-3">
        <h5 class="modal-title fw-semibold">Edit Plan EX</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body px-4 py-4">
        <input type="hidden" id="edit-id">

        <!-- CARD WRAPPER -->
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">

            <div class="row gy-3">
                <!-- Loader -->
              <div class="col-12">
                <label class="form-label fw-semibold">Loader</label>
                <input type="text" class="form-control form-control-lg"
                       id="edit-vhc_id" readonly>
              </div>

              <!-- Start Date -->
              <div class="col-12">
                <label class="form-label fw-semibold">Start Date</label>
                <input type="date" class="form-control form-control-lg"
                       id="edit-start_date">
              </div>

              <!-- End Date -->
              <div class="col-12">
                <label class="form-label fw-semibold">End Date</label>
                <input type="date" class="form-control form-control-lg"
                       id="edit-end_date">
              </div>
              <div class="mb-3">
                <label class="form-label">Days</label>
                <div class="d-flex flex-wrap gap-2">

                    <div class="form-check">
                    <input class="form-check-input day-checkbox" type="checkbox" id="day-1" value="1">
                    <label class="form-check-label" for="day-1">Senin</label>
                    </div>

                    <div class="form-check">
                    <input class="form-check-input day-checkbox" type="checkbox" id="day-2" value="2">
                    <label class="form-check-label" for="day-2">Selasa</label>
                    </div>

                    <div class="form-check">
                    <input class="form-check-input day-checkbox" type="checkbox" id="day-3" value="3">
                    <label class="form-check-label" for="day-3">Rabu</label>
                    </div>

                    <div class="form-check">
                    <input class="form-check-input day-checkbox" type="checkbox" id="day-4" value="4">
                    <label class="form-check-label" for="day-4">Kamis</label>
                    </div>

                    <div class="form-check">
                    <input class="form-check-input day-checkbox" type="checkbox" id="day-5" value="5">
                    <label class="form-check-label" for="day-5">Jumat</label>
                    </div>

                    <div class="form-check">
                    <input class="form-check-input day-checkbox" type="checkbox" id="day-6" value="6">
                    <label class="form-check-label" for="day-6">Sabtu</label>
                    </div>

                    <div class="form-check">
                    <input class="form-check-input day-checkbox" type="checkbox" id="day-7" value="7">
                    <label class="form-check-label" for="day-7">Minggu</label>
                    </div>

                </div>
                </div>

              <!-- Hour -->
              <div class="col-12">
                <label class="form-label fw-semibold">Hour</label>
                <input type="text" class="form-control form-control-lg"
                       id="edit-time_range">
              </div>

              <!-- Value -->
              <div class="col-12">
                <label class="form-label fw-semibold">Value</label>
                <input type="number" class="form-control form-control-lg"
                       id="edit-value">
              </div>

            </div>

          </div>
        </div>
        <!-- END CARD -->

      </div>

      <div class="modal-footer px-4 py-3">
        <button class="btn btn-outline-secondary px-4"
                data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary px-4"
                id="btn-save-edit">Simpan</button>
      </div>

    </div>
  </div>
</div>

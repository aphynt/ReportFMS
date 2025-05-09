@include('layout.head')
@include('layout.header')
@include('layout.sidebar')
<div class="page-body">
    <br>

    <!-- Container-fluid starts-->
    <div class="container-fluid default-dashboard">
        <div class="row">
            <!-- Card for the Earnings Trend Chart -->
            <div class="col-xxl-6 col-xl-6 col-md-12 box-col-6">
                <div class="card earning-card">
                    <div class="card-header pb-0 card-no-border">
                        <div class="header-top">
                            <h3>Production</h3>
                        </div>
                    </div>
                    <div class="card-body" style="display: flex; gap: 0px; padding: 0; margin: 0;">
                        <!-- Chart pertama -->
                        <div style="flex: 1; height: 300px; padding: 0; margin: 0;">
                            <div id="main" style="height: 100%; width: 100%;"></div>
                        </div>

                        <!-- Chart kedua -->
                        <div style="flex: 1; height: 300px; padding: 0; margin: 0;">
                            <div id="main2" style="height: 100%; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Container-fluid ends-->
</div>

@include('layout.footer')
<script>
    const data = @json($data['gaugeData']);
    window.addEventListener('DOMContentLoaded', () => {
        renderGaugeChart(data);
        renderGaugeChart2(data);
    });

</script>
<script>
    function updateClock() {
        const now = new Date();

        let hours = now.getHours();
        const minutes = now.getMinutes();
        const seconds = now.getSeconds();
        let period = "AM";

        if (hours >= 12) {
            period = "PM";
        }
        if (hours === 0) {
            hours = 12;
        } else if (hours > 12) {
            hours -= 12;
        }

        const formattedTime =
            (hours < 10 ? "0" + hours : hours) + ":" +
            (minutes < 10 ? "0" + minutes : minutes) + ":" +
            (seconds < 10 ? "0" + seconds : seconds) + " " + period;

        document.getElementById("clock").innerText = "Jam Sekarang: " + formattedTime;
    }

    setInterval(updateClock, 1000); // update setiap 1 detik
    updateClock(); // panggil sekali di awal agar tidak delay 1 detik

</script>

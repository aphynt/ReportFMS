@include('layout.head')
@include('layout.header')
@include('layout.sidebar')
{{-- <style>
  .chart-container {
    flex: 1;
    height: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 0;
    padding: 0;
  }

  .chart {
    height: 300px;
    width: 100%;
    margin: 0;
    padding: 0;
  }

  .chart-title {
    text-align: center;
    margin: 0;
    padding: 0;
  }

  .card-body.no-gap {
    display: flex;
    padding: 0 !important;
    margin: 0 !important;
    gap: 0 !important;
  }
</style> --}}
{{-- <div class="page-body">
    <br>

    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Card for the Earnings Trend Chart -->
            <div class="col-12">
                <div class="card earning-card border-0 shadow-none m-0">
                    <div class="card-header pb-2 pt-2 card-no-border">
                        <div class="header-top">
                            <h3 class="mb-0">Production</h3>
                        </div>
                    </div>
                    <div class="card-body no-gap">
                        <div class="chart-container"><div id="main1" class="chart"></div><h2 class="chart-title" id="label-main1"></h2></div>
                        <div class="chart-container"><div id="main2" class="chart"></div><h2 class="chart-title">Total</h2></div>
                        <div class="chart-container"><div id="main3" class="chart"></div><h2 class="chart-title">Total</h2></div>
                        <div class="chart-container"><div id="main4" class="chart"></div><h2 class="chart-title">Total</h2></div>
                        <div class="chart-container"><div id="main5" class="chart"></div><h2 class="chart-title">Total</h2></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div> --}}
<div class="page-body">
    <br>

    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Card for the Earnings Trend Chart -->
            <div class="col-12">
                <div class="card earning-card border-0 shadow-none m-0">
                    <div class="card-header pb-2 pt-2 card-no-border">
                        <div class="header-top">
                            <h3 class="mb-0">Information</h3>
                        </div>
                    </div>
                    <div class="card-body no-gap">
                        <div class="chart-container">
                            <h5>Menu ini masih dalam tahap pengembangan...</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@include('layout.footer')
{{-- <script>
    let chart1 = echarts.init(document.getElementById('main1'));
    let chart2 = echarts.init(document.getElementById('main2'));
    let chart3 = echarts.init(document.getElementById('main3'));
    let chart4 = echarts.init(document.getElementById('main4'));
    let chart5 = echarts.init(document.getElementById('main5'));

    // Default option awal
    let gaugeOption = {
        series: [{
            type: 'gauge',
            axisLine: {
                lineStyle: {
                    width: 10,
                    color: [
                        [0.3, '#fd666d'],
                        [0.7, '#37a2da'],
                        [1, '#67e0e3']
                    ]
                }
            },
            pointer: {
                itemStyle: {
                    color: 'auto'
                }
            },
            axisLabel: {
                distance: 20,
                fontSize: 10
            },
            detail: {
                valueAnimation: true,
                formatter: '{value}',
                color: 'inherit'
            },
            data: [{ value: 0 }]
        }]
    };

    // Render awal
    chart1.setOption(gaugeOption);
    chart2.setOption(gaugeOption);
    chart3.setOption(gaugeOption);
    chart4.setOption(gaugeOption);
    chart5.setOption(gaugeOption);

    function loadGaugeData() {
        $.ajax({
            url: "{{ route('dashboard.api') }}", // Ganti dengan route yang benar
            type: 'GET',
            dataType: 'json',
            success: function (data) {

                if (data) {
                    chart1.setOption({
                        series: [{
                            data: [{ value: data.data.totalProduction }],
                            detail: {
                                fontSize: 17
                            }
                        }]
                    });
                    chart2.setOption({
                        series: [{
                            data: [{ value: data.data.totalPlanProduction }],
                            detail: {
                                fontSize: 17
                            }
                        }]
                    });
                    chart3.setOption({
                        series: [{
                            data: [{ value: data.data.totalPlanProduction }],
                            detail: {
                                fontSize: 17
                            }
                        }]
                    });
                    chart4.setOption({
                        series: [{
                            data: [{ value: data.data.totalPlanProduction }],
                            detail: {
                                fontSize: 17
                            }
                        }]
                    });
                    chart5.setOption({
                        series: [{
                            data: [{ value: data.data.totalPlanProduction }],
                            detail: {
                                fontSize: 17
                            }
                        }]
                    });
                    document.getElementById('label-main1').innerText = `${data.data.totalProduction}`;
                }
            },
            error: function (xhr, status, error) {
                console.error("Gagal load gauge data:", error);
            }
        });
    }

    // Jalankan pertama kali
    loadGaugeData();

    // Update setiap 10 detik
    setInterval(loadGaugeData, 10000);
</script> --}}

import './bootstrap';
import * as echarts from 'echarts';

window.renderGaugeChart = function (data) {
    const chartDom = document.getElementById('main');
    const myChart = echarts.init(chartDom);

    const option = {
        series: [
            {
                type: 'gauge',
                anchor: {
                    show: true,
                    showAbove: true,
                    size: 18,
                    itemStyle: {
                        color: '#FAC858'
                    }
                },
                pointer: {
                    icon: 'path://M2.9,0.7L2.9,0.7c1.4,0,2.6,1.2,2.6,2.6v115c0,1.4-1.2,2.6-2.6,2.6l0,0c-1.4,0-2.6-1.2-2.6-2.6V3.3C0.3,1.9,1.4,0.7,2.9,0.7z',
                    width: 8,
                    length: '80%',
                    offsetCenter: [0, '8%']
                },
                progress: {
                    show: true,
                    overlap: true,
                    roundCap: true
                },
                axisLine: {
                    roundCap: true
                },
                data: data,
                title: {
                    fontSize: 14
                },
                detail: {
                    width: 40,
                    height: 14,
                    fontSize: 14,
                    color: '#fff',
                    backgroundColor: 'inherit',
                    borderRadius: 3,
                    formatter: '{value}%'
                },
                // Menghapus padding di chart
                grid: {
                    top: '0%',
                    bottom: '0%',
                    left: '0%',
                    right: '0%',
                    containLabel: true // Pastikan chart mengisi penuh container
                }
            }
        ]
    };

    myChart.setOption(option);

    // Optional: Auto update tiap 2 detik
    setInterval(() => {
        data.forEach(item => {
            item.value = +(Math.random() * 100).toFixed(2);
        });

        myChart.setOption({
            series: [
                {
                    data: data
                }
            ]
        });
    }, 2000);
};

window.renderGaugeChart2 = function (data) {
    const chartDom = document.getElementById('main2');
    const myChart = echarts.init(chartDom);

    const option = {
        series: [
            {
                type: 'gauge',
                anchor: {
                    show: true,
                    showAbove: true,
                    size: 18,
                    itemStyle: {
                        color: '#FAC858'
                    }
                },
                pointer: {
                    icon: 'path://M2.9,0.7L2.9,0.7c1.4,0,2.6,1.2,2.6,2.6v115c0,1.4-1.2,2.6-2.6,2.6l0,0c-1.4,0-2.6-1.2-2.6-2.6V3.3C0.3,1.9,1.4,0.7,2.9,0.7z',
                    width: 8,
                    length: '80%',
                    offsetCenter: [0, '8%']
                },
                progress: {
                    show: true,
                    overlap: true,
                    roundCap: true
                },
                axisLine: {
                    roundCap: true
                },
                data: data,
                title: {
                    fontSize: 14
                },
                detail: {
                    width: 40,
                    height: 14,
                    fontSize: 14,
                    color: '#fff',
                    backgroundColor: 'inherit',
                    borderRadius: 3,
                    formatter: '{value}%'
                },
                // Menghapus padding di chart
                grid: {
                    top: '0%',
                    bottom: '0%',
                    left: '0%',
                    right: '0%',
                    containLabel: true // Pastikan chart mengisi penuh container
                }
            }
        ]
    };

    myChart.setOption(option);

    // Optional: Auto update tiap 2 detik
    setInterval(() => {
        data.forEach(item => {
            item.value = +(Math.random() * 100).toFixed(2);
        });

        myChart.setOption({
            series: [
                {
                    data: data
                }
            ]
        });
    }, 2000);
};


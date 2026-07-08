@include('layout.head', ['title' => 'Laporan Inspeksi'])
@include('layout.header')
@include('layout.sidebar')
@include('layout.styleSpinner')

<style>

    .breakdown-table{
        width:100%;
        border-collapse:collapse;
        table-layout:auto;
        font-size:13px;
    }

    .breakdown-table th,
    .breakdown-table td{
        border:1px solid #dcdcdc;
        padding:8px 10px;
        min-width:70px;
        text-align:center;
        vertical-align:middle;
    }

    .breakdown-table td:first-child{

        text-align:left;

        font-weight:bold;

    }

    .editable{

        cursor:pointer;

    }

    .editable:hover{

        background:#fff3cd;

    }

    .titleShift{

        background:#0b67b2;

        color:white;

        font-weight:bold;

        padding:6px;

    }

    .average{

        background:#fff2cc;

        font-weight:bold;

    }

    .actual-col{
        background:#fff8dc;
    }

    .total-col{
        background:#f5f5f5;
        color:#666;
    }

    .breakdown-table th{
        white-space: nowrap;
    }

    .input-breakdown{
        width: 55px;
        min-width: 55px;
        text-align: center;
        border: 1px solid #0d6efd;
        border-radius: 4px;
        padding: 2px 4px;
        font-size: 13px;
    }

</style>
<div class="page-body">
    <div class="container-fluid pt-4">

        <div class="card">

           <div class="mb-4 d-flex gap-3">
                <div class="col-md-1">
                    <label>Tanggal</label>
                    <input type="date" id="tanggal" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-1">
                    <label>Filter</label>
                    <button id="btnCari" class="btn btn-primary w-100">Submit</button>
                </div>
            </div>

            <div class="card-body">

                <div id="loading" style="display:none">

                    Loading....

                </div>

                <div id="tableContainer">

                </div>

            </div>

        </div>

    </div>
</div>
@include('layout.footer')



<script>

$(function(){

    loadData();

    $('#btnCari').click(function(){

        loadData();

    });

});
function loadData(){

    $('#loading').show();

    $.ajax({

        url:"{{ route('breakdown.data') }}",

        type:"GET",

        data:{
            tanggal:$('#tanggal').val()
        },

        success:function(res){

            console.log(res);

            renderTable(res);

        },

        error:function(xhr){

            console.log(xhr.responseText);

            alert("Gagal mengambil data.");

        },

        complete:function(){

            $('#loading').hide();

        }

    });

}

function renderTable(res){

    let html = "";

    let pagi = res.hours.filter(h=>{

        return h.hour >= '07' && h.hour <= '18';

    });

    let malam = res.hours.filter(h=>{

        return h.hour >= '19' || h.hour <= '06';

    });

    html += buildShift('PAGI', pagi, res);

    html += "<br>";

    html += buildShift('MALAM', malam, res);

    $('#tableContainer').html(html);

    bindEditable();

}

function bindEditable(){

    $('.editable').off('click');

    $('.editable').on('click',function(){

        let td=$(this);

        if(td.find('input').length)
            return;

        let value=td.text().trim();

        td.html(
            "<input type='number' class='input-breakdown' value='"+value+"'>"
        );

        td.find('input').focus().select();

    });

}

$(document).on('keydown','.input-breakdown',function(e){

        if(e.key!='Enter')
            return;

        saveCell($(this));

    });
    $(document).on('blur','.input-breakdown',function(){

        saveCell($(this));

    });

function saveCell(input){

    let td=input.closest('td');

    let id=td.data('id');

    let value=input.val();

    $.ajax({

        url:"{{ route('breakdown.update') }}",

        method:"POST",

        headers:{
            'X-CSRF-TOKEN':
            $('meta[name="csrf-token"]').attr('content')
        },

        data:{
            id:id,
            value:value
        },

        success:function(){

            if(value=="")
                value=0;

            td.text(value);

            td.attr('data-value',value);

        },

        error:function(){

            alert("Update gagal");

            td.text(td.data('value'));

        }

    });

}

function buildShift(title,hours,res){

    let html='';

    html+="<div class='titleShift'>"+title+"</div>";

    html+="<table class='breakdown-table'>";

    html += "<thead>";

    html += "<tr>";

    html += "<th rowspan='2'>Equipment</th>";

    hours.forEach(function(h){

        html += "<th colspan='2'>";

        html += h.hour+":00";

        html += "</th>";

    });

    html += "<th rowspan='2'>Average</th>";

    html += "</tr>";

    html += "<tr>";

    hours.forEach(function(){

        html += "<th class='actual-col'>Actual</th>";
        html += "<th class='total-col'>Total</th>";

    });

    html += "</tr>";

    html += "</thead>";

    html+="<tbody>";
    res.types.forEach(function(type){

        html += "<tr>";

        html += "<td>"+type+"</td>";

        let totalActual = 0;
        let count = 0;

        hours.forEach(function(h){

            let actual = "";
            let totalDown = "";
            let id = "";

            if(res.data[type] && res.data[type][h.date+" "+h.hour]){

                id = res.data[type][h.date+" "+h.hour].id;

                actual = res.data[type][h.date+" "+h.hour].actual ?? "";

                totalDown = res.data[type][h.date+" "+h.hour].total ?? "";

                if(actual !== ""){

                    totalActual += parseFloat(actual);

                    count++;

                }

            }

            html += "<td class='editable actual-col' data-id='"+id+"' data-value='"+actual+"'>";
            html += actual;
            html += "</td>";

            html += "<td class='total-col'>";
            html += totalDown;
            html += "</td>";

        });

        let avg = "";

        if(count > 0){

            avg = (totalActual / count).toFixed(1);

        }

        html += "<td class='average'>"+avg+"</td>";

        html += "</tr>";

    });
html+="</tbody>";

html+="</table>";

return html;

}
</script>

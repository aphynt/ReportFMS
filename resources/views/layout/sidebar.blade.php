<aside class="page-sidebar">
    <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
    <div class="main-sidebar" id="main-sidebar">
        <ul class="sidebar-menu" id="simple-bar">
            <li class="pin-title sidebar-main-title">
                <div>
                    <h5 class="sidebar-title f-w-700">Pinned</h5>
                </div>
            </li>
            {{-- <li class="sidebar-main-title">
                <div>
                    <h5 class="lan-1 f-w-700 sidebar-title">General</h5>
                </div>
            </li>
            <li class="sidebar-list"> <i class="fa-solid fa-thumbtack"></i>
                <a class="sidebar-link" href="{{ route('dashboard.index') }}">
                    <i class="fi fi-rr-home"></i>
                    <h6 class="f-w-600">Dashboard</h6>
                </a>
            </li> --}}


            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title pt-3">Application</h5>
                </div>
            </li>
            <li class="sidebar-list"> <i class="fa-solid fa-thumbtack"></i><a class="sidebar-link"
                    href="javascript:void(0)">
                    <i class="fi fi-rr-ramp-loading"></i>
                    <h6 class="f-w-600">Payload</h6>
                </a>
                <ul class="sidebar-submenu">
                    <li> <a href="{{ route('payload.ex.index') }}">Excavator</a></li>
                </ul>
            </li>
            <li class="sidebar-list"> <i class="fa-solid fa-thumbtack"></i><a class="sidebar-link"
                    href="javascript:void(0)">
                    <i class="fi fi-rr-ramp-loading"></i>
                    <h6 class="f-w-600">Temuan SAP</h6>
                </a>
                {{-- <ul class="sidebar-submenu">
                    <li> <a href="#" onclick="Swal.fire({ icon: 'info', title: 'Upps!', text: 'Fitur ini sedang dalam proses pengembangan.', confirmButtonText: 'OK' })">KLKH Loading Point</a></li>
                    <li> <a href="#" onclick="Swal.fire({ icon: 'info', title: 'Upps!', text: 'Fitur ini sedang dalam proses pengembangan.', confirmButtonText: 'OK' })">KLKH Haul Road</a></li>
                    <li> <a href="#" onclick="Swal.fire({ icon: 'info', title: 'Upps!', text: 'Fitur ini sedang dalam proses pengembangan.', confirmButtonText: 'OK' })">KLKH Disposal/Dumping Point</a></li>
                    <li> <a href="#" onclick="Swal.fire({ icon: 'info', title: 'Upps!', text: 'Fitur ini sedang dalam proses pengembangan.', confirmButtonText: 'OK' })">KLKH Dumping di Kolam Air/Lumpur</a></li>
                    <li> <a href="#" onclick="Swal.fire({ icon: 'info', title: 'Upps!', text: 'Fitur ini sedang dalam proses pengembangan.', confirmButtonText: 'OK' })">KLKH OGS</a></li>
                    <li> <a href="#" onclick="Swal.fire({ icon: 'info', title: 'Upps!', text: 'Fitur ini sedang dalam proses pengembangan.', confirmButtonText: 'OK' })">KLKH Batu Bara</a></li>
                    <li> <a href="#" onclick="Swal.fire({ icon: 'info', title: 'Upps!', text: 'Fitur ini sedang dalam proses pengembangan.', confirmButtonText: 'OK' })">KLKH Intersection (Simpang Empat)</a></li>
                </ul> --}}
                <ul class="sidebar-submenu">
                    <li> <a href="{{ route('loadingPoint.index') }}">KLKH Loading Point</a></li>
                    <li> <a href="{{ route('haulRoad.index') }}">KLKH Haul Road</a></li>
                    <li> <a href="{{ route('disposal.index') }}">KLKH Disposal/Dumping Point</a></li>
                    <li> <a href="{{ route('lumpur.index') }}">KLKH Dumping di Kolam Air/Lumpur</a></li>
                    <li> <a href="{{ route('ogs.index') }}">KLKH OGS</a></li>
                    <li> <a href="{{ route('batuBara.index') }}">KLKH Batu Bara</a></li>
                    <li> <a href="{{ route('simpangEmpat.index') }}">KLKH Intersection (Simpang Empat)</a></li>
                </ul>
            </li>
            {{-- <li class="sidebar-list"> <i class="fa-solid fa-thumbtack"></i><a class="sidebar-link"
                    href="letter-box.html">
                    <svg class="stroke-icon">
                        <use
                            href="https://admin.pixelstrap.net/admiro/assets/svg/iconly-sprite.svg#Message">
                        </use>
                    </svg>
                    <h6 class="f-w-600">Letter Box</h6>
                </a>
            </li> --}}

        </ul>
    </div>
    <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
</aside>

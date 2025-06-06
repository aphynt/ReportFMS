<header class="page-header row">
    <div class="logo-wrapper d-flex align-items-center col-auto">

            <a href="#"><img class="light-logo img-fluid" src="{{ asset('dashboard/assets') }}/images/logo/dashboard.png" alt="logo" width="150px"/>
                <img class="dark-logo img-fluid" src="{{ asset('dashboard/assets') }}/images/logo/logo2.png" alt="logo" /></a>
        <a class="close-btn toggle-sidebar" href="javascript:void(0)">
            <i class="fi fi-rr-apps-add"></i>
        </a>
    </div>
    <div class="page-main-header col">
        <div class="header-left">
            <a href="#"><img class="light-logo img-fluid" src="{{ asset('dashboard/assets') }}/images/logo/sims.png" alt="logo" width="150px"/>
            <a href="#"><img class="light-logo img-fluid" src="{{ asset('dashboard/assets') }}/images/logo/logo2.png" alt="logo" width="150px"/>

        </div>
        <div class="nav-right">
            <ul class="header-right">
                <li> <a class="dark-mode" href="javascript:void(0)">
                    <i class="fi fi-rr-dark-mode-alt"></i></a>
                </li>


                <li class="profile-nav custom-dropdown">
                    <div class="user-wrap">
                        <div class="user-img"><img src="{{ asset('dashboard/assets') }}/images/profile.png"
                                alt="user" /></div>
                        <div class="user-content">
                            <h6>{{ Auth::user()->name }}</h6>
                            <p class="mb-0">{{ Auth::user()->role }}</p>
                        </div>
                    </div>
                    <div class="custom-menu overflow-hidden">
                        <ul class="profile-body">
                            <li class="d-flex">
                                <i class="fi fi-rr-user-pen"></i>
                                <a class="ms-2" href="#" onclick="alert('Maaf, fitur ini belum berfungsi'); return false;">Account</a>

                            </li>
                            <li class="d-flex">
                                <i class="fi fi-rr-sign-out-alt"></i><a class="ms-2" href="{{ route('logout') }}">Log Out</a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>
<div class="page-body-wrapper">

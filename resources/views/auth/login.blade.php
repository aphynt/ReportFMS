<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Admiro admin is super flexible, powerful, clean &amp; modern responsive bootstrap 5 admin template with unlimited possibilities.">
    <meta name="keywords"
        content="admin template, Admiro admin template, best javascript admin, dashboard template, bootstrap admin template, responsive admin template, web app">
    <meta name="author" content="pixelstrap">
    <title>Login - Focus Report</title>
    <!-- Favicon icon-->
    <link rel="icon" href="{{ asset('dashboard/assets') }}/images/logo/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('dashboard/assets') }}/images/logo/favicon.png" type="image/x-icon">
    <!-- Google font-->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz,wght@6..12,200;6..12,300;6..12,400;6..12,500;6..12,600;6..12,700;6..12,800;6..12,900;6..12,1000&amp;display=swap"
        rel="stylesheet">
    <!-- Flag icon css -->
    <link rel="stylesheet" href="{{ asset('dashboard/assets') }}/css/vendors/flag-icon.css">
    <!-- iconly-icon-->
    <link rel="stylesheet" href="{{ asset('dashboard/assets') }}/css/iconly-icon.css">
    <link rel="stylesheet" href="{{ asset('dashboard/assets') }}/css/bulk-style.css">
    <!-- iconly-icon-->
    <link rel="stylesheet" href="{{ asset('dashboard/assets') }}/css/themify.css">
    <!--fontawesome-->
    <link rel="stylesheet" href="{{ asset('dashboard/assets') }}/css/fontawesome-min.css">
    <!-- Whether Icon css-->
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard/assets') }}/css/vendors/weather-icons/weather-icons.min.css">
    <!-- App css -->
    <link rel="stylesheet" href="{{ asset('dashboard/assets') }}/css/style.css">
    <link id="color" rel="stylesheet" href="{{ asset('dashboard/assets') }}/css/color-1.css" media="screen">
</head>

<body>
    <!-- tap on top starts-->
    <div class="tap-top"><i class="iconly-Arrow-Up icli"></i></div>
    <!-- tap on tap ends-->
    <!-- loader-->
    <div class="loader-wrapper">
        <div class="loader"><span></span><span></span><span></span><span></span><span></span></div>
    </div>
    <!-- login page start-->
    <div class="container-fluid p-0">
        <div class="row m-0">
            <div class="col-12 p-0">
                <div class="login-card login-dark">
                    <div>
                        <div class="login-main">
                            <div><a class="logo" href="#"><img class="img-fluid for-dark" src="{{ asset('dashboard/assets') }}/images/logo/logo2.png" alt="logo" width="200px"></a>
                        </div>
                            <form action="{{ route('login.post') }}" method="POST" class="theme-form">
                                @csrf
                                <h2 class="text-center">Masuk ke akun Anda</h2>
                                <p class="text-center">Masukkan NRP dan kata sandi Anda untuk masuk.</p>
                                <div class="form-group">
                                    <label class="col-form-label" for="nik">Username/NRP</label>
                                    <input class="form-control" type="text" required="" id="nik" name="nik" placeholder="0123S">
                                </div>
                                <div class="form-group">
                                    <label class="col-form-label" for="password">Password</label>
                                    <div class="form-input position-relative">
                                        <input class="form-control" type="password" id="password" name="password" required=""
                                            placeholder="*********">
                                        {{-- <div class="show-hide"><span class="show"> </span></div> --}}
                                    </div>
                                </div>
                                <div class="form-group mb-0 checkbox-checked">
                                    <div class="form-check checkbox-solid-info">
                                        <input class="form-check-input" id="solid6" type="checkbox">
                                        <label class="form-check-label" for="solid6">Ingat Saya!</label>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button class="btn btn-primary btn-block w-100" type="submit">Masuk </button>
                                    </div>
                                </div>
                                <br>
                                <p class="text-center">Copyright © IT-FMS 2025 PT. SIMS JAYA KALTIM</p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- jquery-->
        <script src="{{ asset('dashboard/assets') }}/js/vendors/jquery/jquery.min.js"></script>
        <!-- bootstrap js-->
        <script src="{{ asset('dashboard/assets') }}/js/vendors/bootstrap/dist/js/bootstrap.bundle.min.js" defer=""></script>
        <script src="{{ asset('dashboard/assets') }}/js/vendors/bootstrap/dist/js/popper.min.js" defer=""></script>
        <!--fontawesome-->
        <script src="{{ asset('dashboard/assets') }}/js/vendors/font-awesome/fontawesome-min.js"></script>
        <!-- password_show-->
        <script src="{{ asset('dashboard/assets') }}/js/password.js"></script>
        <!-- custom script -->
        <script src="{{ asset('dashboard/assets') }}/js/script.js"></script>
    </div>
</body>

</html>

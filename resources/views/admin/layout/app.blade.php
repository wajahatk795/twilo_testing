<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Metoxi | Bootstrap 5 Admin Dashboard Template</title>
    <!--favicon-->
    <link rel="icon" href="{{ asset('assets/images/favicon-32x32.png') }}" type="image/png">

    <!--plugins-->
    <link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/metismenu/metisMenu.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/metismenu/mm-vertical.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}">
    <!--bootstrap css-->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <!--main css-->
    <link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet">
    <link href="{{ asset('sass/main.css') }}" rel="stylesheet">
    <link href="{{ asset('sass/dark-theme.css') }}" rel="stylesheet">
    <link href="{{ asset('sass/semi-dark.css') }}" rel="stylesheet">
    <link href="{{ asset('sass/bordered-theme.css') }}" rel="stylesheet">
    <link href="{{ asset('sass/responsive.css') }}" rel="stylesheet">

    @yield('css')

</head>

<body>

    @include('admin.layout.header')
    @include('admin.layout.sidebar')
    <div class="content">
        <style>
            /* Ensure content sits below the fixed header and has comfortable padding */
            .content {
                width: 84%;
                margin-left: auto;
                margin-top: 90px;
                /* space for header height */
                padding: 20px 24px;
                /* breathing room inside content */
                min-height: calc(100vh - 100px);
            }

            @media (max-width: 992px) {
                .content {
                    width: 100%;
                    margin-left: 0;
                    margin-top: 120px;
                }
            }
        </style>
        @yield('content')

        <!--start switcher-->
        <button class="btn btn-primary position-fixed bottom-0 end-0 m-3 d-flex align-items-center gap-2" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#staticBackdrop" style="margin-bottom: 50px !important">
            <i class="material-icons-outlined">tune</i>Customize
        </button>

        <div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="staticBackdrop">
            <div class="offcanvas-header border-bottom h-70">
                <div class="">
                    <h5 class="mb-0">Theme Customizer</h5>
                    <p class="mb-0">Customize your theme</p>
                </div>
                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="offcanvas">
                    <i class="material-icons-outlined">close</i>
                </a>
            </div>
            <div class="offcanvas-body">
                <div>
                    <p>Theme variation</p>

                    <div class="row g-3">
                        <div class="col-12 col-xl-6">
                            <input type="radio" class="btn-check" name="theme-options" id="LightTheme" checked>
                            <label
                                class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4"
                                for="LightTheme">
                                <span class="material-icons-outlined">light_mode</span>
                                <span>Light</span>
                            </label>
                        </div>
                        <div class="col-12 col-xl-6">
                            <input type="radio" class="btn-check" name="theme-options" id="DarkTheme">
                            <label
                                class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4"
                                for="DarkTheme">
                                <span class="material-icons-outlined">dark_mode</span>
                                <span>Dark</span>
                            </label>
                        </div>
                        <div class="col-12 col-xl-6">
                            <input type="radio" class="btn-check" name="theme-options" id="SemiDarkTheme">
                            <label
                                class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4"
                                for="SemiDarkTheme">
                                <span class="material-icons-outlined">contrast</span>
                                <span>Semi Dark</span>
                            </label>
                        </div>
                        <div class="col-12 col-xl-6">
                            <input type="radio" class="btn-check" name="theme-options" id="BoderedTheme">
                            <label
                                class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4"
                                for="BoderedTheme">
                                <span class="material-icons-outlined">border_style</span>
                                <span>Bordered</span>
                            </label>
                        </div>
                    </div><!--end row-->

                </div>
            </div>
        </div>
        <!--start switcher-->
    </div>
    @include('admin.layout.footer')



    <!--bootstrap js-->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

    <!--plugins-->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <!--plugins-->
    <script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/peity/jquery.peity.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(".data-attributes span").peity("donut")
        </script>
    <script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('assets/js/index.js') }}"></script>

    <script>
        // Success Message
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: "{{ session('success') }}",
            });
        @endif

        // Error Message
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: "{{ session('error') }}",
            });
        @endif

        // Validator Errors
        @if($errors->any())
            let errorMessages = "";
            @foreach($errors->all() as $error)
                errorMessages += "• {{ $error }}\n";
            @endforeach

            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: errorMessages.replace(/\n/g, '<br>'),
            });
        @endif
    </script>


    @yield('js')


</body>

</html>

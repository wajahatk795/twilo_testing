@extends('admin.layout.app')
@section('content')
    <!--start main wrapper-->
    <style>
        /* .extra-class {
            margin-left: 0 !important;
            margin-top: 0 !important;
        } */

        .company-card {
            transition: all .25s ease;
        }

        .company-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, .08);
        }
    </style>
        <div class="main-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Dashboard</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">eCommerce</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <!--end breadcrumb-->


            <div class="row">

                <!-- Company -->
                <div class="col-12 col-xl-4 d-flex">
                    <a href="{{ route('company.admin') }}">
                        <div class="card rounded-4 border-0 w-100 company-card">
                            <div class="card-body d-flex flex-column justify-content-between">

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:52px; height:52px;">
                                        <i class="fa-solid fa-building fs-4"></i>
                                    </div>
                                    <h5 class="mb-0 fw-semibold">Company</h5>
                                </div>

                                <p class="text-muted mb-4">
                                    View and manage your company information and settings.
                                </p>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Phone Numbers -->
                <div class="col-12 col-xl-4 d-flex">
                    <a href="{{ route('admin.phone-numbers.index') }}">
                        <div class="card rounded-4 border-0 w-100 company-card">
                            <div class="card-body d-flex flex-column justify-content-between">

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:52px; height:52px;">
                                        <i class="fa-solid fa-phone fs-4"></i>
                                    </div>
                                    <h5 class="mb-0 fw-semibold">Phone Numbers</h5>
                                </div>

                                <p class="text-muted mb-4">
                                    Manage assigned phone numbers and configurations.
                                </p>

                            </div>
                        </div>
                    </a>
                </div>

                <!-- Leads -->
                <div class="col-12 col-xl-4 d-flex">
                    <a href="{{ route('admin.lead.index') }}">
                        <div class="card rounded-4 border-0 w-100 company-card">
                            <div class="card-body d-flex flex-column justify-content-between">

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:52px; height:52px;">
                                        <i class="fa-solid fa-flag fs-4"></i>
                                    </div>
                                    <h5 class="mb-0 fw-semibold">Leads</h5>
                                </div>

                                <p class="text-muted mb-4">
                                    View, track, and manage all incoming leads.
                                </p>


                            </div>
                        </div>
                    </a>
                </div>

                <!-- Calls -->
                <div class="col-12 col-xl-4 d-flex mt-4">
                    <a href="{{ route('admin.call.index') }}">
                        <div class="card rounded-4 border-0 w-100 company-card">
                            <div class="card-body d-flex flex-column justify-content-between">

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:52px; height:52px;">
                                        <i class="fa-solid fa-phone fs-4"></i>
                                    </div>
                                    <h5 class="mb-0 fw-semibold">Calls</h5>
                                </div>

                                <p class="text-muted mb-4">
                                    Check call logs, durations, and call history.
                                </p>


                            </div>
                        </div>
                    </a>
                </div>

                <!-- Instant Calls -->
                <div class="col-12 col-xl-4 d-flex mt-4">
                    <a href="{{ route('twilio.index') }}">
                        <div class="card rounded-4 border-0 w-100 company-card">
                            <div class="card-body d-flex flex-column justify-content-between">

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:52px; height:52px;">
                                        <i class="fa-solid fa-bolt fs-4"></i>
                                    </div>
                                    <h5 class="mb-0 fw-semibold">Instant Calls</h5>
                                </div>

                                <p class="text-muted mb-4">
                                    Start instant calls without creating a lead.
                                </p>

                            </div>
                        </div>
                    </a>
                </div>

            </div>





        </div>
    <!--end main wrapper-->

    <!--start overlay-->
    <div class="overlay btn-toggle"></div>
    <!--end overlay-->

    <!--start switcher-->
    <!--start switcher-->
@endsection

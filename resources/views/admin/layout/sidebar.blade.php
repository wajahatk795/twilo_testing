    <aside class="sidebar-wrapper">
        <div class="sidebar-header">
            <div class="logo-icon">
                <img src="{{ asset('assets/images/logo-icon.png') }}" class="logo-img" alt="">
            </div>
            <div class="logo-name flex-grow-1">
                <h5 class="mb-0">Metoxi</h5>
            </div>
            <div class="sidebar-close">
                <span class="material-icons-outlined">close</span>
            </div>
        </div>
        <div class="sidebar-nav" data-simplebar="true">

            <!--navigation-->
            <ul class="metismenu" id="sidenav">
                <li>
                    <a href="{{ route('admin.dashboard') }}">
                        <div class="parent-icon"><i class="material-icons-outlined">home</i>
                        </div>
                        <div class="menu-title">Dashboard</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('company.admin') }}">
                        <div class="parent-icon"><i class="fa-solid fa-building"></i>
                        </div>
                        <div class="menu-title">Company</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.phone-numbers.index') }}">
                        <div class="parent-icon"><i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="menu-title">Phone Numbers</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.lead.index') }}">
                        <div class="parent-icon"><i class="fa-solid fa-flag"></i>
                        </div>
                        <div class="menu-title">Lead</div>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.call.index') }}">
                        <div class="parent-icon"><i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="menu-title">Calls</div>
                    </a>
                </li>

                <li>
                    <a href="{{ route('twilio.index') }}">
                        <div class="parent-icon"><i class="fa-solid fa-bolt fs-4"></i>
                        </div>
                        <div class="menu-title">Instant Calls</div>
                    </a>
                </li>

            </ul>
            <!--end navigation-->
        </div>
        <div class="sidebar-bottom gap-4">
            @if (Auth::check() === true)
                <form action="{{ route('auth.logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2"
                        style="border: none; background: none; cursor: pointer; width: 100%; text-align: left;">
                        <i class="material-icons-outlined">power_settings_new</i>Logout
                    </button>
                </form>
            @endif

        </div>
    </aside>

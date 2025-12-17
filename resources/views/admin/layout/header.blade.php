    <header class="top-header">
        <nav class="navbar navbar-expand align-items-center gap-4">
            <div class="btn-toggle">
                <a href="javascript:;"><i class="material-icons-outlined">menu</i></a>
            </div>
            <div class="search-bar flex-grow-1">
            </div>
            @php
                $user = DB::table('users')
                    ->where('id', auth()->user()->id)
                    ->first();
            @endphp
            <ul class="navbar-nav gap-1 nav-right-links align-items-center">

                <li class="nav-item dropdown">
                    <a href="javascrpt:;" class="dropdown-toggle dropdown-toggle-nocaret" data-bs-toggle="dropdown">
                        @if ($user && $user->image)
                            <img src="{{ asset($user->image) }}" class="rounded-circle p-1 border" width="45"
                                height="45">
                        @else
                            <img src="{{ asset('assets/images/avatars/profile.jfif') }}"
                                class="rounded-circle p-1 border" width="45" height="45">
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-user dropdown-menu-end shadow">
                        <a class="dropdown-item  gap-2 py-2" href="javascript:;">
                            <div class="text-center">
                                <img src="{{ asset('assets/images/avatars/01.png') }}"
                                    class="rounded-circle p-1 shadow mb-3" width="90" height="90" alt="">
                                <h5 class="user-name mb-0 fw-bold">Hello, Jhon</h5>
                            </div>
                        </a>
                        <hr class="dropdown-divider">
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                            href="{{ route('admin.profile.index') }}"><i
                                class="material-icons-outlined">person_outline</i>Profile</a>
                        {{-- <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i
                                class="material-icons-outlined">local_bar</i>Setting</a> --}}
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                            href="{{ route('admin.dashboard') }}"><i
                                class="material-icons-outlined">dashboard</i>Dashboard</a>
                        {{-- <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i
                                class="material-icons-outlined">account_balance</i>Earning</a> --}}
                        {{-- <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i
                                class="material-icons-outlined">cloud_download</i>Downloads</a> --}}
                        <hr class="dropdown-divider">
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
                </li>
            </ul>

        </nav>
    </header>

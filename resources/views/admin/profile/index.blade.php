@extends('admin.layout.app')

@section('content')
    <div class="row">
        <!-- ================= BASIC INFO ================= -->
        <div class="col-12 col-xl-6 d-flex">
            <div class="card rounded-4 border-0 w-100">
                <div class="card-body">

                    <h5 class="mb-4 fw-semibold">
                        <i class="fa-solid fa-user me-2"></i>
                        Profile Information
                    </h5>

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf

                        <!-- Name -->
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name', auth()->user()->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email (Read Only) -->
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>

                        <button class="btn btn-primary w-100">
                            Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= PASSWORD UPDATE ================= -->
        <div class="col-12 col-xl-6 d-flex">
            <div class="card rounded-4 border-0 w-100">
                <div class="card-body">

                    <h5 class="mb-4 fw-semibold">
                        <i class="fa-solid fa-lock me-2"></i>
                        Change Password
                    </h5>

                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf

                        <!-- Current Password -->
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password"
                                class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>

                        <button class="btn btn-warning w-100">
                            Update Password
                        </button>
                    </form>

                </div>
            </div>
        </div>

        <!-- ================= ACCOUNT INFO ================= -->
        <div class="col-12 mt-4">
            <div class="card rounded-4 border-0">
                <div class="card-body">

                    <h5 class="mb-3 fw-semibold">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Account Information
                    </h5>

                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Role</p>
                            <h6>{{ auth()->user()->role->name ?? 'User' }}</h6>
                        </div>

                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Account Created</p>
                            <h6>{{ auth()->user()->created_at?->format('d M Y') }}</h6>
                        </div>

                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Status</p>
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>


    </div>
@endsection

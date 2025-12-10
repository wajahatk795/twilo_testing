@extends('admin.layout.app')

@section('content')
    <div class="row">
        <div class="col-12 col-xl-12">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="mb-4">Create User & Company</h5>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form id="user-company-form" action="{{ route('tenants.store') }}" method="POST" class="row g-3">
                        @csrf
                        
                        <!-- User Registration Fields -->

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons-outlined fs-5">email</i></span>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                    placeholder="Email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group" id="show_hide_password">
                                <input type="password" class="form-control border-end-0 @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter Password" required>
                                <a href="javascript:;" class="input-group-text bg-transparent"><i class="bi bi-eye-slash-fill"></i></a>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" placeholder="Confirm Password" required>
                            @error('password_confirmation')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Company Fields -->
                        <hr class="my-3">
                        <h6 class="mb-3">Company Information</h6>

                        <div class="col-md-6">
                            <label for="company_name" class="form-label">Company Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons-outlined fs-5">business</i></span>
                                <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" id="company_name"
                                    placeholder="Company Name" value="{{ old('company_name') }}" required>
                                @error('company_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="plan" class="form-label">Plan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons-outlined fs-5">science</i></span>
                                <select class="form-select @error('plan') is-invalid @enderror" id="plan" name="plan" required>
                                    <option value="">Select Your Plan</option>
                                    <option value="Free" {{ old('plan') === 'Free' ? 'selected' : '' }}>Free</option>
                                </select>
                                @error('plan')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Questions (Optional)</label>
                            <div id="questions-container">
                                <div class="question-row mb-2" style="display: flex; gap: 8px;">
                                    <input type="text" name="questions[]" class="form-control" placeholder="e.g. What is your full name?" />
                                    <button type="button" class="btn btn-sm btn-danger remove-question" style="display: none;">Remove</button>
                                </div>
                            </div>
                            <div style="text-align: end">
                                <button type="button" id="add-question" class="btn btn-sm btn-secondary mt-2">+ Add Question</button>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="d-md-flex d-grid align-items-center gap-3">
                                <button id="submit-btn" type="submit" class="btn btn-primary px-4">Create User & Company</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle
        (function() {
            var toggleLink = document.querySelector('#show_hide_password a');
            if (toggleLink) {
                toggleLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    var input = document.getElementById('password');
                    var icon = this.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('bi-eye-slash-fill');
                        icon.classList.add('bi-eye-fill');
                    } else {
                        input.type = 'password';
                        icon.classList.add('bi-eye-slash-fill');
                        icon.classList.remove('bi-eye-fill');
                    }
                });
            }
        })();

        // Manage multiple questions
        (function(){
            var container = document.getElementById('questions-container');
            var addBtn = document.getElementById('add-question');
            var form = document.getElementById('user-company-form');
            var submitBtn = document.getElementById('submit-btn');

            if (container && addBtn && form) {
                // Add new question input
                addBtn.addEventListener('click', function(e){
                    e.preventDefault();
                    var newRow = document.createElement('div');
                    newRow.className = 'question-row mb-2';
                    newRow.style.display = 'flex';
                    newRow.style.gap = '8px';
                    newRow.innerHTML = '<input type="text" name="questions[]" class="form-control" placeholder="e.g. What is your full name?" />' +
                        '<button type="button" class="btn btn-sm btn-danger remove-question">Remove</button>';
                    container.appendChild(newRow);
                    updateRemoveButtons();
                });

                // Remove question input
                container.addEventListener('click', function(e){
                    if (e.target.classList.contains('remove-question')){
                        e.preventDefault();
                        e.target.closest('.question-row').remove();
                        updateRemoveButtons();
                    }
                });

                // Show/hide remove buttons based on number of rows
                function updateRemoveButtons(){
                    var rows = container.querySelectorAll('.question-row');
                    rows.forEach(function(row){
                        var btn = row.querySelector('.remove-question');
                        btn.style.display = rows.length > 1 ? 'block' : 'none';
                    });
                }

                // Initial check
                updateRemoveButtons();

                // Prevent double-submit by disabling the button on submit
                form.addEventListener('submit', function(){
                    submitBtn.disabled = true;
                    submitBtn.innerText = 'Creating...';
                });
            }
        })();
    </script>
@endsection

@extends('admin.layout.app')

@section('content')
    <div class="row">

        <div class="col-12 col-xl-12">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="mb-4">Company Create</h5>
                    <form id="user-company-edit-form" action="{{ route('company.update.admin', $tenant->id) }}" method="POST"
                        class="row g-3">
                        @csrf
                        @method('PUT')

                        <!-- User Fields -->
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons-outlined fs-5">email</i></span>
                                <input type="email" name="email"
                                    class="form-control @error('email') is-invalid @enderror" id="email"
                                    placeholder="Email" value="{{ old('email', $tenant->owner->email ?? '') }}" required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group" id="show_hide_password">
                                <input type="password"
                                    class="form-control border-end-0 @error('password') is-invalid @enderror" id="password"
                                    name="password" placeholder="Enter Password (leave blank to keep current)">
                                <a href="javascript:;" class="input-group-text bg-transparent"><i
                                        class="bi bi-eye-slash-fill"></i></a>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                                id="password_confirmation" name="password_confirmation" placeholder="Confirm Password">
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
                                <input type="text" name="company_name"
                                    class="form-control @error('company_name') is-invalid @enderror" id="company_name"
                                    placeholder="Company Name" value="{{ old('company_name', $tenant->company_name) }}"
                                    required>
                                @error('company_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="plan" class="form-label">Plan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons-outlined fs-5">science</i></span>
                                <select class="form-select @error('plan') is-invalid @enderror" id="plan"
                                    name="plan" required>
                                    <option value="">Select Your Plan</option>
                                    <option value="Free" {{ old('plan', $tenant->plan) === 'Free' ? 'selected' : '' }}>
                                        Free</option>
                                </select>
                                @error('plan')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Questions -->
                        <div class="col-md-12">
                            <label class="form-label">Questions (Optional)</label>
                            <div id="questions-container">
                                @forelse($tenant->questions as $question)
                                    <div class="question-row mb-2" style="display: flex; gap: 8px;">
                                        <input type="text" name="questions[]" class="form-control"
                                            placeholder="e.g. What is your full name?"
                                            value="{{ old('questions[]', $question->prompt) }}" />
                                        <button type="button" class="btn btn-sm btn-danger remove-question"
                                            style="display: none;">Remove</button>
                                    </div>
                                @empty
                                    <div class="question-row mb-2" style="display: flex; gap: 8px;">
                                        <input type="text" name="questions[]" class="form-control"
                                            placeholder="e.g. What is your full name?" />
                                        <button type="button" class="btn btn-sm btn-danger remove-question"
                                            style="display: none;">Remove</button>
                                    </div>
                                @endforelse
                            </div>
                            <div style="text-align: end">
                                <button type="button" id="add-question" class="btn btn-sm btn-secondary mt-2">+ Add
                                    Question</button>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="d-md-flex d-grid align-items-center gap-3">
                                <button id="submit-btn" type="submit" class="btn btn-primary px-4">Update User &
                                    Company</button>
                            </div>
                        </div>
                    </form>


                </div>
            </div>
        </div>
    </div>
    <script>
        // Manage multiple questions
        (function() {
            var container = document.getElementById('questions-container');
            var addBtn = document.getElementById('add-question');
            var form = document.getElementById('tenant-create-form');
            var submitBtn = document.getElementById('tenant-create-submit');

            // Add new question input
            addBtn.addEventListener('click', function() {
                var newRow = document.createElement('div');
                newRow.className = 'question-row mb-2';
                newRow.style.display = 'flex';
                newRow.style.gap = '8px';
                newRow.innerHTML =
                    '<input type="text" name="questions[]" class="form-control" placeholder="e.g. What is your full name?" />' +
                    '<button type="button" class="btn btn-sm btn-danger remove-question">Remove</button>';
                container.appendChild(newRow);
                updateRemoveButtons();
            });

            // Remove question input
            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-question')) {
                    e.preventDefault();
                    e.target.closest('.question-row').remove();
                    updateRemoveButtons();
                }
            });

            // Show/hide remove buttons based on number of rows
            function updateRemoveButtons() {
                var rows = container.querySelectorAll('.question-row');
                rows.forEach(function(row) {
                    var btn = row.querySelector('.remove-question');
                    btn.style.display = rows.length > 1 ? 'block' : 'none';
                });
            }

            // Initial check
            updateRemoveButtons();

            // Prevent double-submit by disabling the button on submit
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerText = 'Submitting...';
            });
        })();
    </script>
@endsection

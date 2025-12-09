@extends('admin.layout.app')

@section('content')
    <div class="row">

        <div class="col-12 col-xl-12">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="mb-4">Company Create</h5>
                    <form id="tenant-create-form" action="{{ route('company.edit.admin', $tenant->id) }}" method="POST" class="row g-3">
                        @csrf
                        @method('PUT')
                        <div class="col-md-6">
                            <label for="input25" class="form-label">Company Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i
                                        class="material-icons-outlined fs-5">person_outline</i></span>
                                <input type="text" name="company_name" class="form-control" id="input25"
                                    placeholder="Company Name" value="{{ $tenant->company_name }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="input33" class="form-label">Plan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons-outlined fs-5">science</i></span>
                                <select class="form-select" id="input33" name="plan" required>
                                    <option value="" selected>Select Your Plan</option>
                                    <option value="Free" @if($tenant->plan === 'Free') selected @endif>Free</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Questions</label>
                            <div id="questions-container">
                                @forelse($tenant->questions as $question)
                                    <div class="question-row mb-2" style="display: flex; gap: 8px;">
                                        <input type="text" name="questions[]" class="form-control" placeholder="e.g. What is your full name?" value="{{ $question->prompt }}" />
                                        <button type="button" class="btn btn-sm btn-danger remove-question" style="display: none;">Remove</button>
                                    </div>
                                @empty
                                    <div class="question-row mb-2" style="display: flex; gap: 8px;">
                                        <input type="text" name="questions[]" class="form-control" placeholder="e.g. What is your full name?" />
                                        <button type="button" class="btn btn-sm btn-danger remove-question" style="display: none;">Remove</button>
                                    </div>
                                @endforelse
                            </div>
                            <div style="text-align: end">
                                <button type="button" id="add-question" class="btn btn-sm btn-secondary mt-2">+ Add</button>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="d-md-flex d-grid align-items-center gap-3">
                                <button id="tenant-create-submit" type="submit" class="btn btn-primary px-4">Submit</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <script>
        // Manage multiple questions
        (function(){
            var container = document.getElementById('questions-container');
            var addBtn = document.getElementById('add-question');
            var form = document.getElementById('tenant-create-form');
            var submitBtn = document.getElementById('tenant-create-submit');

            // Add new question input
            addBtn.addEventListener('click', function(){
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
                submitBtn.innerText = 'Submitting...';
            });
        })();
    </script>
@endsection

@extends('admin.layout.app')

@section('content')
    <div class="row">
        <div class="col-12 col-xl-12">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="mb-4">Edit Phone Number</h5>

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

                    <form id="user-company-form"
                          action="{{ route('admin.phone-numbers.update', $phoneNumber->id) }}"
                          method="POST" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-md-6">
                            <label for="single-select-field">Company User</label>
                            <select name="user_id" class="form-select" id="single-select-field" required>
                                <option value="" disabled>Select User</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ $phoneNumber->user_id == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="label">Label (optional)</label>
                            <input type="text" name="label" class="form-control"
                                   value="{{ $phoneNumber->label }}" placeholder="Support Line, Sales Line">
                        </div>

                        <div class="col-md-6">
                            <label for="number">Phone Number *</label>
                            <input type="text" name="number" class="form-control"
                                   value="{{ $phoneNumber->number }}" required placeholder="+1 555 888 9999">
                        </div>

                        <div class="col-md-6">
                            <label for="secret_key">Secret Key</label>
                            <input type="text" name="twilio_sid" class="form-control"
                                   value="{{ $phoneNumber->twilio_sid }}" required placeholder="Enter Secret Key">
                        </div>

                        <div class="col-md-12">
                            <div class="d-md-flex d-grid align-items-center gap-3">
                                <button id="submit-btn" type="submit" class="btn btn-primary px-4">Update</button>
                                <a href="{{ route('admin.phone-numbers.index') }}" class="btn btn-secondary px-4">Back</a>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    $('#single-select-field').select2({
        theme: "bootstrap-5",
        width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
        placeholder: $(this).data('placeholder'),
    });
</script>
@endsection

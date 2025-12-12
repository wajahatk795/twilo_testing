@extends('admin.layout.app')

@section('content')
    <div class="row">
        <div class="col-12 col-xl-12">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="mb-4">Twilio Call</h5>

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

                    <form id="twilio-form" action="{{ route('twilio.outbound') }}" method="POST" class="row g-3">
                        @csrf

                        <div class="col-md-6">
                            <label for="label">Enter Phone Number for Outbound Call</label>
                            <input type="text" name="phone" class="form-control" required placeholder="+1234567890">
                        </div>

                        <div class="col-md-12">
                            <div class="d-md-flex d-grid align-items-center gap-3">
                                <button id="submit-btn" type="submit" class="btn btn-primary px-4">Start Call</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
@endsection

@extends('admin.layout.app')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h6 class="mb-0 text-uppercase">DataTable Example</h6>
        <a href="{{ route('create.admin') }}" class="btn btn-primary">Add</a>
    </div>

    <hr>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tenants-table" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Company Name</th>
                            <th>Plan</th>
                            @if (Auth::user()->role_id === 1)
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tenants as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->company_name }}</td>
                                <td>{{ $item->plan }}</td>
                                @if (Auth::user()->role_id === 1)
                                    <td>
                                        <a href="{{ route('company.edit.admin', $item->id) }}">
                                            <button class="btn btn-primary btn-sm">
                                                <i class="fa fa-pencil-square-o" aria-hidden="true"></i> Edit
                                            </button>
                                        </a>
                                        <form action="{{ route('company.destroy.admin', $item->id) }}" method="POST"
                                            style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Are you sure you want to delete this company?')">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Company Name</th>
                            <th>Plan</th>
                            @if (Auth::user()->role_id === 1)
                                <th>Actions</th>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            var table = $('#tenants-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('tenants.data') }}",
                    type: 'GET',
                    error: function(xhr, status, error) {
                        console.error('DataTables AJAX error:', status, error);
                        console.error(xhr.responseText);
                        alert('Data loading error — check console for details.');
                    }
                },
                // ensure search box and length selector are visible
                dom: 'lfrtip',
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'company_name',
                        name: 'company_name'
                    },
                    {
                        data: 'plan',
                        name: 'plan'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        });
    </script>
@endsection

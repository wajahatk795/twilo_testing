@extends('admin.layout.app')

@push('before-css')
    <link href="{{ asset('plugins/components/datatables/jquery.dataTables.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.datatables.net/buttons/1.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h6 class="mb-0 text-uppercase">DataTable Example</h6>
        <a href="{{route("create.admin")}}" class="btn btn-primary">Add</a>
    </div>

    <hr>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="users-table" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Company Name</th>
                            <th>plan</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tenants as $item)
                            <tr>
                                <td>{{$item->id}}</td>
                                <td>{{$item->company_name}}</td>
                                <td>{{$item->plan}}</td>
                                <td>
                                    <a href="{{ route('company.edit.admin', $item->id) }}">
                                        <button class="btn btn-primary btn-sm">
                                            <i class="fa fa-pencil-square-o" aria-hidden="true"></i> Edit
                                        </button>
                                    </a>
                                    <form action="{{ route('company.destroy.admin', $item->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this company?')">
                                            <i class="fa fa-trash-o" aria-hidden="true"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Company Name</th>
                            <th>plan</th>
                            <th>Actions</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>



@endsection

@push('js')
    <!-- ============================================================== -->
    <script src="{{ asset('plugins/components/datatables/jquery.dataTables.min.js') }}"></script>
    <script>
        $(function() {
            $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{!! route('users.data') !!}',
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
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
@endpush

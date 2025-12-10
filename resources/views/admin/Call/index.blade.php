@extends('admin.layout.app')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h6 class="mb-0 text-uppercase">Calls </h6>
        {{-- <a href="{{ route('admin.lead.create') }}" class="btn btn-primary">Add</a> --}}
    </div>

    <hr>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tenants-table" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- @foreach ($lead as $item) --}}
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            {{-- @endforeach --}}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>phone</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    $(function() {
        $('#phone-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.phone-numbers.data') !!}',
            columns: [
                { data: 'id', name: 'id' },
                { data: 'user', name: 'user' },
                { data: 'label', name: 'label' },
                { data: 'number', name: 'number' },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // DELETE ACTION
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('admin.phone-numbers.destroy', ':id') }}".replace(':id', id);

            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function() {
                            $('#phone-table').DataTable().ajax.reload(); // FIXED
                        },
                        error: function() {
                            Swal.fire({
                                icon: "error",
                                title: "Oops...",
                                text: "Something went wrong!"
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Consumers</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Consumers</h2>
        
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <a href="{{ route('consumers.create') }}" class="btn btn-success mb-4">Add New Consumer</a>
        <div class="card">
            <div class="card-header">
                <h4>Search Consumers</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('consumers.search') }}" method="GET">
                    <div class="form-group">
                        <label for="searchName">Name</label>
                        <input type="text" class="form-control" id="searchName" name="name" 
                               placeholder="Enter Name" value="{{ old('name', request('name')) }}">
                    </div>
                    <div class="form-group">
                        <label for="searchContactNo">Contact No</label>
                        <input type="text" class="form-control" id="searchContactNo" name="contactno" 
                               placeholder="Enter Contact No" value="{{ old('contactno', request('contactno')) }}">
                    </div>
                    <div class="form-group">
                        <label for="searchReferenceNo">Reference No</label>
                        <input type="text" class="form-control" id="searchReferenceNo" name="reference_no" 
                               placeholder="Enter Reference No" value="{{ old('reference_no', request('reference_no')) }}">
                    </div>
                    <div class="form-group">
                        <label for="searchCnic">CNIC</label>
                        <input type="text" class="form-control" id="searchCnic" name="occupant_nicno" 
                               placeholder="Enter CNIC" value="{{ old('occupant_nicno', request('occupant_nicno')) }}">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
                
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h4>All Consumers</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact No</th>
                            <th>Reference No</th>
                            <th>Cnic No</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($consumers as $consumer)
                            <tr>
                                <td>{{ isset($consumer['_source']) ? $consumer['_source']['name'] : $consumer->name }}</td>
                                <td>{{ isset($consumer['_source']) ? $consumer['_source']['contactno'] : $consumer->contactno }}</td>
                                <td>{{ isset($consumer['_source']) ? $consumer['_source']['reference_no'] : $consumer->reference_no }}</td>
                                <td>{{ isset($consumer['_source']) ? $consumer['_source']['occupant_nicno'] : $consumer->occupant_nicno }}</td>
                                <td>
                                    <a href="{{ route('consumers.edit', isset($consumer['_source']) ? $consumer['_source']['id'] : $consumer->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                    <form action="{{ route('consumers.destroy', isset($consumer['_source']) ? $consumer['_source']['id'] : $consumer->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No consumers found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if(count($consumers) > 0)
                    <div class="d-flex justify-content-center">
                        @if(isset($totalPages))
                            @for ($i = 1; $i <= $totalPages; $i++)
                                <a href="{{ route('consumers.index', [
                                        'page' => $i, 
                                        'name' => request('name'), 
                                        'contactno' => request('contactno'), 
                                        'reference_no' => request('reference_no'), 
                                        'occupant_nicno' => request('occupant_nicno')
                                    ]) }}" class="btn btn-link">
                                    {{ $i }}
                                </a>
                            @endfor
                        @else
                            {{ $consumers->appends(request()->all())->links() }}
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

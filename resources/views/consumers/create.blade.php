<!-- resources/views/consumers/create.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Consumer</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Add New Consumer</h2>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h4>Consumer Details</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('consumers.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name" value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label for="contactno">Contact No</label>
                        <input type="number" class="form-control" id="contactno" name="contactno" placeholder="Enter Contact No" value="{{ old('contactno') }}">
                    </div>
                    <div class="form-group">
                        <label for="reference_no">Reference No</label>
                        <input type="number" class="form-control" id="reference_no" name="reference_no" placeholder="Enter Reference No" value="{{ old('reference_no') }}">
                    </div>
                    <div class="form-group">
                        <label for="cnic">CNIC</label>
                        <input type="number" class="form-control" id="occupant_nicno" name="occupant_nicno" placeholder="Enter CNIC" value="{{ old('occupant_nicno') }}">
                    </div>
                    <button type="submit" class="btn btn-success">Add Consumer</button>
                    <a href="{{ route('consumers.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

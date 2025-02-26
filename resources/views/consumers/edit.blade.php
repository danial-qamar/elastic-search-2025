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
        <h1 class="text-center mb-4">Consumers</h1>
        
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <h2>Edit Consumer</h2>
        <form action="{{ route('consumers.update', $consumer->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $consumer->name) }}" required>
            </div>
    
            <div class="form-group">
                <label for="contactno">Contact No</label>
                <input type="text" class="form-control" id="contactno" name="contactno" value="{{ old('contactno', $consumer->contactno) }}" required>
            </div>
    
            <div class="form-group">
                <label for="reference_no">Reference No</label>
                <input type="text" class="form-control" id="reference_no" name="reference_no" value="{{ old('reference_no', $consumer->reference_no) }}" required>
            </div>
    
            <button type="submit" class="btn btn-primary">Update Consumer</button>
        </form>
    </div>
</body>
</html>

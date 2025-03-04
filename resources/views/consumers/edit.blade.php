<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Consumer</title>
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
            <div class="row">
                @foreach ($columns as $index => $column)
                    @if ($index % 3 == 0 && $index != 0)
                        </div><div class="row">
                    @endif
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="{{ $column }}">{{ ucwords(str_replace('_', ' ', $column)) }}</label>
                            <input type="text" class="form-control @error($column) is-invalid @enderror" id="{{ $column }}" name="{{ $column }}" 
                                   value="{{ old($column, $consumer->$column) }}">
                            @error($column)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-primary">Update Consumer</button>
                <a href="{{ route('consumers.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>

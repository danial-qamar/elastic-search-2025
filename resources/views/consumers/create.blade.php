@extends('layouts.app')

@section('title', 'Create Consumer')

@section('content')
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
                <form id="consumerStoreForm" action="{{ route('consumers.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        @foreach ($columns as $index => $column)
                            @if ($index % 3 == 0 && $index != 0)
                                </div><div class="row">
                            @endif
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="{{ $column }}">{{ ucwords(str_replace('_', ' ', $column)) }}</label>
                                    <input type="text" class="form-control @error($column) is-invalid @enderror" 
                                           id="{{ $column }}" name="{{ $column }}" 
                                           placeholder="Enter {{ ucwords(str_replace('_', ' ', $column)) }}"
                                           value="{{ old($column) }}">
                                           
                                    @error($column)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-success">Save Consumer</button>
                        <a href="{{ route('consumers.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.19.5/jquery.validate.min.js"></script>
    <script src="{{ asset('js/consumers-validation.js') }}"></script>
@endpush
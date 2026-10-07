@extends('layouts.app')

@section('title', 'New Consumer')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Banner -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 fw-bold text-dark fs-3">Register New Consumer</h2>
            <p class="text-muted small mb-0">Fill in consumer registration details and utility parameters</p>
        </div>
        <a href="{{ route('consumers.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Consumers
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Form Card -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-person-fill text-primary"></i>
                <h4 class="mb-0 text-dark">Consumer Profile Specification</h4>
            </div>
            <span class="badge-violet">Database Transaction</span>
        </div>
        <div class="card-body">
            <form id="consumerStoreForm" action="{{ route('consumers.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    @foreach ($columns as $column)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-group mb-0">
                                <label for="{{ $column }}" class="form-label text-truncate w-100">
                                    {{ ucwords(str_replace('_', ' ', $column)) }}
                                    @if(in_array($column, ['reference_no', 'bill_month', 'name', 'contactno']))
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <input type="text" 
                                       class="form-control @error($column) is-invalid @enderror {{ in_array($column, ['reference_no', 'bill_month', 'contactno', 'occupant_nicno']) ? 'font-mono' : '' }}" 
                                       id="{{ $column }}" 
                                       name="{{ $column }}" 
                                       placeholder="Enter {{ ucwords(str_replace('_', ' ', $column)) }}"
                                       value="{{ old($column) }}">
                                       
                                @error($column)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 pt-3 border-top" style="border-color: var(--border-subtle) !important;">
                    <a href="{{ route('consumers.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-circle-fill"></i> Save Consumer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.19.5/jquery.validate.min.js"></script>
    <script src="{{ asset('js/consumers-validation.js') }}"></script>
@endpush
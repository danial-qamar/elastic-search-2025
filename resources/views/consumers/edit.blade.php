@extends('layouts.app')

@section('title', 'Edit Consumer')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Banner -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 fw-bold text-dark fs-3">Edit Consumer Profile</h2>
            <p class="text-muted small mb-0">
                Updating record for: <span class="text-dark fw-semibold">{{ $consumer->name }}</span>
                <span class="code-ref badge-violet ms-2">{{ $consumer->reference_no }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('consumers.history', $consumer->id) }}" class="btn btn-secondary">
                <i class="bi bi-clock-history"></i> View Audit History
            </a>
            <a href="{{ route('consumers.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Consumers
            </a>
        </div>
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
                <i class="bi bi-pencil-square text-warning"></i>
                <h4 class="mb-0 text-dark">Edit Consumer Details</h4>
            </div>
            <span class="badge-amber">ID #{{ $consumer->id }}</span>
        </div>
        <div class="card-body">
            <form action="{{ route('consumers.update', $consumer->id) }}" method="POST">
                @csrf
                @method('PUT')

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
                                       value="{{ old($column, $consumer->$column) }}">
                                       
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
                        <i class="bi bi-save2-fill"></i> Update Consumer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

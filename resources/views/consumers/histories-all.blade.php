@extends('layouts.app')

@section('title', 'Audit Histories')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Banner -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 fw-bold text-dark fs-3">Consumer Audit Histories</h2>
            <p class="text-muted small mb-0">Track change history, field modifications, and user edit trails</p>
        </div>
        <a href="{{ route('consumers.index') }}" class="btn btn-secondary">
            <i class="bi bi-people-fill"></i> View Consumers
        </a>
    </div>

    <!-- Histories Card -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i>
                <h4 class="mb-0 text-dark">Audit Logs</h4>
            </div>
            <span class="badge-violet">{{ $histories->total() }} Modifications Recorded</span>
        </div>
        <div class="card-body p-0">
            @if($histories->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-clock-history fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-dark fw-bold mb-1">No Audit History Records</h5>
                    <p class="text-muted small">Consumer modifications and field updates will be tracked here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Consumer</th>
                                <th>Operator</th>
                                <th>Field Modifications</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($histories as $history)
                                <tr>
                                    <td>
                                        <div class="font-mono text-secondary small">
                                            <i class="bi bi-calendar3 me-1 text-muted"></i>
                                            {{ $history->created_at->format('d M Y, H:i') }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($history->consumer_id)
                                            <a href="{{ route('consumers.edit', $history->consumer_id) }}" class="fw-semibold text-warning text-decoration-none">
                                                <i class="bi bi-person me-1"></i>{{ $history->consumer->name ?? ('ID #' . $history->consumer_id) }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge" style="background: #F1F5F9; color: #334155; padding: 5px 12px; border-radius: 20px; font-size: 12px; border: 1px solid #CBD5E1; font-weight: 600;">
                                            <i class="bi bi-shield-person me-1 text-muted"></i>
                                            {{ $history->user->name ?? 'System Admin' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            @if(is_array($history->changed_fields) && count($history->changed_fields) > 0)
                                                @foreach($history->changed_fields as $field => $change)
                                                    <div class="small font-mono d-flex align-items-center flex-wrap gap-1">
                                                        <span class="text-dark fw-bold">{{ ucfirst(str_replace('_',' ', $field)) }}:</span>
                                                        <span class="badge-rose px-2 py-0" style="font-size: 11px;">{{ $change['old'] ?? '-' }}</span>
                                                        <i class="bi bi-arrow-right text-muted" style="font-size: 10px;"></i>
                                                        <span class="badge-amber px-2 py-0" style="font-size: 11px;">{{ $change['new'] ?? '-' }}</span>
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-muted small">No field changes detailed</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center p-3 border-top" style="border-color: var(--border-subtle) !important;">
                    {{ $histories->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

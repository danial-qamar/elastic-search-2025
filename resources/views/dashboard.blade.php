@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Banner -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 fw-bold text-dark fs-3">System Overview & Logs</h2>
            <p class="text-muted small mb-0">Live monitoring of consumer data batches, indexing status, and subdivision activity</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('consumers.index') }}" class="btn btn-secondary">
                <i class="bi bi-people-fill"></i> View Consumers
            </a>
            <a href="{{ route('consumers.search') }}" class="btn btn-primary">
                <i class="bi bi-search"></i> Search Registry
            </a>
        </div>
    </div>

    @php
        $latestLog = $logs->first();
        $latestConsumers = $latestLog->consumers_count ?? 0;
        $latestIndexed = $latestLog->indexed_count ?? 0;
        $latestSubdivisions = $latestLog->subdivisions_count ?? 0;
        $latestMonth = '—';
        if ($latestLog && !empty($latestLog->bill_month)) {
            try {
                $latestMonth = \Carbon\Carbon::createFromFormat('Ym', $latestLog->bill_month)->format('M Y');
            } catch (\Throwable $e) {
                $latestMonth = $latestLog->bill_month;
            }
        }
        $totalBatches = $logs->count();
    @endphp

    <!-- Metric Stat Cards (Latest Batch Metrics) -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">Processed (Latest)</div>
                    <div class="stat-number text-dark">{{ number_format($latestConsumers) }}</div>
                    <div class="small text-muted font-mono mt-1" style="font-size: 11px;">Month: {{ $latestMonth }}</div>
                </div>
                <div class="stat-icon violet">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">Indexed (Latest)</div>
                    <div class="stat-number text-dark">{{ number_format($latestIndexed) }}</div>
                    <div class="small text-muted font-mono mt-1" style="font-size: 11px;">Search Synced</div>
                </div>
                <div class="stat-icon amber">
                    <i class="bi bi-database-check"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">Subdivisions (Latest)</div>
                    <div class="stat-number text-dark">{{ number_format($latestSubdivisions) }}</div>
                    <div class="small text-muted font-mono mt-1" style="font-size: 11px;">Active Codes</div>
                </div>
                <div class="stat-icon violet">
                    <i class="bi bi-diagram-3"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">Latest Batch</div>
                    <div class="stat-number text-dark" style="font-size: 20px;">{{ $latestMonth }}</div>
                    <div class="small text-muted font-mono mt-1" style="font-size: 11px;">Total Batches: {{ $totalBatches }}</div>
                </div>
                <div class="stat-icon rose">
                    <i class="bi bi-journal-text"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Logs Card -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i>
                <h4 class="mb-0 text-dark">Batch Import Logs</h4>
            </div>
            <span class="badge-violet">{{ $totalBatches }} Batch Runs</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Bill Month</th>
                            <th>Duration</th>
                            <th>Consumers</th>
                            <th>Subdivisions</th>
                            <th>Indexed</th>
                            <th>Logged At</th>
                            <th class="text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr data-bs-toggle="collapse" data-bs-target="#log-{{ $log->id }}" class="accordion-toggle" style="cursor: pointer;">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-calendar3 text-warning"></i>
                                        <span class="fw-bold text-dark font-mono">
                                            {{ \Carbon\Carbon::createFromFormat('Ym', $log->bill_month)->format('M Y') }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if ($log->duration)
                                        @php
                                            $seconds = (int) $log->duration;
                                            $days = intdiv($seconds, 86400); 
                                            $seconds %= 86400;
                                            $hours = intdiv($seconds, 3600);
                                            $seconds %= 3600;
                                            $minutes = intdiv($seconds, 60);
                                            $seconds %= 60;
                                        @endphp
                                        <span class="badge-amber font-mono">
                                            @if($days > 0) {{ $days }}d @endif
                                            @if($hours > 0) {{ $hours }}h @endif
                                            @if($minutes > 0) {{ $minutes }}m @endif
                                            @if($seconds > 0) {{ $seconds }}s @endif
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark font-mono">{{ number_format($log->consumers_count ?? 0) }}</span>
                                </td>
                                <td>
                                    <span class="font-mono text-secondary">{{ number_format($log->subdivisions_count ?? 0) }}</span>
                                </td>
                                <td>
                                    <span class="code-ref badge-violet">{{ number_format($log->indexed_count ?? 0) }}</span>
                                </td>
                                <td>
                                    <span class="text-muted small font-mono">
                                        {{ \Carbon\Carbon::parse($log->created_at)->format('jS M Y, h:i A') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="btn btn-sm btn-secondary py-1 px-2">
                                        <i class="bi bi-chevron-down"></i> Breakdown
                                    </span>
                                </td>
                            </tr>

                            {{-- Collapsible subdivision rows --}}
                            <tr>
                                <td colspan="7" class="p-0 border-0">
                                    <div class="collapse" id="log-{{ $log->id }}">
                                        <div class="p-3" style="background: #F8FAFC; border-top: 1px dashed var(--border-subtle); border-bottom: 1px dashed var(--border-subtle);">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h6 class="text-warning small text-uppercase fw-bold mb-0">
                                                    <i class="bi bi-diagram-3-fill me-1"></i> Subdivision Breakdown
                                                </h6>
                                                <span class="small text-muted">{{ count($log->subdivisions) }} Subdivisions Recorded</span>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th class="ps-3">Subdivision Code</th>
                                                            <th>Consumers</th>
                                                            <th>Indexed</th>
                                                            <th>Timestamp</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($log->subdivisions as $sub)
                                                            <tr>
                                                                <td class="ps-3 font-mono text-warning fw-semibold">{{ $sub->subdivision_code }}</td>
                                                                <td class="font-mono text-dark">{{ number_format($sub->consumers_count ?? 0) }}</td>
                                                                <td class="font-mono text-secondary">{{ number_format($sub->indexed_count ?? 0) }}</td>
                                                                <td class="text-muted small font-mono">{{ \Carbon\Carbon::parse($sub->created_at)->format('jS M Y, h:i A') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                        <h5 class="text-dark fw-bold mb-1">No Import Logs Recorded</h5>
                                        <p class="text-muted small">Run an import job or CSV batch to see audit log entries.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/consumers.js') }}"></script>
@endpush

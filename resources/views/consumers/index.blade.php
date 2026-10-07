@extends('layouts.app')

@section('title', 'Consumers')

@section('content')
<div class="container-fluid px-0">
    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 fw-bold text-dark fs-3">Consumers Directory</h2>
            <p class="text-muted small mb-0">Browse and manage registered consumers (10 records per page)</p>
        </div>
        <div class="d-flex flex-wrap gap-2 w-100 w-sm-auto">
            <a href="{{ route('consumers.create') }}" class="btn btn-primary flex-fill flex-sm-grow-0">
                <i class="bi bi-person-plus-fill"></i> Add New Consumer
            </a>
            <button type="button" id="btnImportConsumers" class="btn btn-amber flex-fill flex-sm-grow-0">
                <i class="bi bi-cloud-arrow-up-fill"></i> Import Consumers
            </button>
        </div>
    </div>   

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Consumers Table Card -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-people-fill text-primary"></i>
                <h4 class="mb-0 text-dark">All Consumers</h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if (method_exists($consumers, 'total'))
                    <span class="badge-violet">Total: {{ number_format($consumers->total()) }}</span>
                @endif
                <span class="badge-amber">Page {{ $consumers->currentPage() }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Consumer Name</th>
                            <th>Contact No</th>
                            <th>Reference No</th>
                            <th>CNIC No</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($consumers as $consumer)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stat-icon violet" style="width: 32px; height: 32px; font-size: 14px; border-radius: 50%;">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <span class="fw-semibold text-dark">{{ $consumer->name ?: '—' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-mono text-secondary">{{ $consumer->contactno ?: '—' }}</span>
                                </td>
                                <td>
                                    <span class="code-ref badge-violet px-2 py-1">{{ $consumer->reference_no ?: '—' }}</span>
                                </td>
                                <td>
                                    <span class="font-mono text-secondary">{{ $consumer->occupant_nicno ?: '—' }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('consumers.edit', $consumer->id) }}" class="btn btn-warning btn-sm" title="Edit Consumer">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <form action="{{ route('consumers.destroy', $consumer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this consumer?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete Consumer">
                                                <i class="bi bi-trash3-fill"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                        <h5 class="text-dark fw-bold mb-1">No Consumers Found</h5>
                                        <p class="text-muted small">No consumer records exist in the database or match this page.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div class="d-flex justify-content-center p-3 border-top" style="border-color: var(--border-subtle) !important;">
                @if (method_exists($consumers, 'total'))
                    {{ $consumers->links('pagination::bootstrap-4') }}
                @else
                    {{ $consumers->links('pagination::simple-bootstrap-4') }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
<!-- Import Consumers Modal - Rendered at root level of body -->
<div class="modal fade" id="importConsumersModal" tabindex="-1" aria-labelledby="importConsumersModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-cloud-arrow-up-fill text-warning"></i>
                    <span>Import Consumers Data</span>
                </h5>
                <button type="button" class="btn-close" id="modalCloseX" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="importConsumersForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label for="importFile" class="form-label fw-semibold">Select Data File (CSV / TXT)</label>
                        <input type="file" class="form-control" id="importFile" name="importFile" accept=".csv,.txt" required>
                        <div class="form-text text-muted">Supports bulk CSV exports up to 200MB. Background pipeline indexes directly into the database.</div>
                    </div>

                    <!-- Progress indicator -->
                    <div class="progress mb-3" style="height: 22px; display: none;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <div id="importStatus" class="fw-semibold small mb-3 text-secondary"></div>

                    <!-- Live Terminal Log -->
                    <label class="form-label fw-semibold mb-1">Real-Time Processing Log</label>
                    <div id="importLog" class="p-3 rounded mb-3" style="height: 180px; overflow-y: auto; background: #F8FAFC; border: 1px solid #CBD5E1; font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #1E293B;">
                        <div id="importLogContent" class="text-muted">Awaiting file upload...</div>
                    </div>

                    <!-- Import Summary Results -->
                    <div id="importSummary" class="p-3 rounded d-none" style="background: #FEF3C7; border: 1px solid #FDE68A;">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-check2-all me-1 text-warning"></i> Import Completed Successfully</h6>
                        <div class="row g-2 text-secondary small">
                            <div class="col-sm-4"><strong>Total Imported:</strong> <span id="totalImported" class="text-dark fw-bold">0</span></div>
                            <div class="col-sm-4"><strong>Elapsed Time:</strong> <span id="totalTime" class="text-dark fw-bold">0s</span></div>
                            <div class="col-sm-4"><strong>Batches:</strong> <span id="totalBatches" class="text-dark fw-bold">0</span></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelImport" data-bs-dismiss="modal">Cancel / Close</button>
                <button type="submit" form="importConsumersForm" class="btn btn-primary">
                    <i class="bi bi-play-circle-fill"></i> Start Import
                </button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
    <script src="{{ asset('js/consumers.js') }}"></script>
@endpush

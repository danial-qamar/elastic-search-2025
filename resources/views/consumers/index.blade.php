@extends('layouts.app')

@section('title', 'Consumers')

@section('content')
<div class="container-fluid px-0">
    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 fw-bold text-dark fs-3">Consumers Directory</h2>
            <p class="text-muted small mb-0">Browse and manage registered consumers &bull; Total: <strong class="text-dark">{{ number_format($totalConsumers) }}</strong></p>
        </div>
        <div class="d-flex flex-wrap gap-2 ms-sm-4">
            <a href="{{ route('consumers.create') }}" class="btn btn-primary text-nowrap">
                <i class="bi bi-person-plus-fill me-1"></i> Add New Consumer
            </a>
            <button type="button" id="btnImportConsumers" class="btn btn-amber text-nowrap">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Import Consumers
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
                <span class="badge-violet"><i class="bi bi-people-fill me-1"></i> Total: {{ number_format($totalConsumers) }}</span>
                <span class="badge-amber"><i class="bi bi-file-earmark-text me-1"></i> Page {{ $consumers->currentPage() }}</span>
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
                    <tbody id="consumersTableBody">
                        @include('consumers.partials.rows', ['consumers' => $consumers])
                    </tbody>
                </table>
            </div>

            <!-- Clean Navigation Bar (Previous Page | Current Page | Next Page) -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 p-3 border-top" style="border-color: var(--border-subtle) !important;">
                <!-- Previous Page Button -->
                <div>
                    @if ($consumers->onFirstPage())
                        <button class="btn btn-secondary btn-sm" disabled>
                            <i class="bi bi-chevron-left"></i> Previous Page
                        </button>
                    @else
                        <a href="{{ $consumers->previousPageUrl() }}" class="btn btn-secondary btn-sm" id="btnPrevPage">
                            <i class="bi bi-chevron-left"></i> Previous Page
                        </a>
                    @endif
                </div>

                <!-- Center: Current Page Indicator -->
                <div class="text-center">
                    <span class="badge-amber px-3 py-2 fw-semibold fs-6">
                        <i class="bi bi-file-earmark-text me-1"></i> Page {{ $consumers->currentPage() }}
                    </span>
                </div>

                <!-- Next Page Button -->
                <div>
                    @if ($consumers->hasMorePages())
                        <a href="{{ $consumers->nextPageUrl() }}" class="btn btn-primary btn-sm" id="btnNextPage">
                            Next Page <i class="bi bi-chevron-right"></i>
                        </a>
                    @else
                        <button class="btn btn-secondary btn-sm" disabled>
                            Next Page <i class="bi bi-chevron-right"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
<!-- Import Consumers Modal -->
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

@extends('layouts.app')

@section('title', 'Consumers')

@section('content')

<div class="container">
    <h2 class="text-center mb-4">Consumers</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between mb-4">
        <a href="{{ route('consumers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add New Consumer
        </a>
        <button type="button" id="btnImportConsumers" class="btn btn-secondary">
            <i class="bi bi-upload me-1"></i> Import Consumers
        </button>
    </div>   
     <!-- All Consumers -->
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h4>All Consumers</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact No</th>
                        <th>Reference No</th>
                        <th>CNIC No</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($consumers as $consumer)
                        <tr>
                            <td>{{ $consumer->name }}</td>
                            <td>{{ $consumer->contactno }}</td>
                            <td>{{ $consumer->reference_no }}</td>
                            <td>{{ $consumer->occupant_nicno }}</td>
                            <td>
                                <a href="{{ route('consumers.edit', $consumer->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('consumers.destroy', $consumer->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No consumers found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="d-flex justify-content-center">
                <nav>
                    <ul class="pagination pagination-sm">
                        {{ $consumers->links('pagination::bootstrap-4') }}
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importConsumersModal" tabindex="-1" aria-labelledby="importConsumersModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Consumers</h5>
                </div>
                <div class="modal-body">
                    <form id="importConsumersForm" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="importFile" class="form-label">Select File</label>
                            <input type="file" class="form-control" id="importFile" name="importFile" required>
                        </div>
                        <div class="progress mb-3" style="height: 25px; display: none;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                        </div>
                        <div id="importStatus" class="text-muted small"></div>

                        <!-- Live log -->
                        <div id="importLog" class="border rounded p-2 mt-3" style="height: 200px; overflow-y: auto; background: #f9f9f9; font-size: 13px;">
                            <div id="importLogContent" class="text-muted"></div>
                        </div>

                        <!-- Summary -->
                        <div id="importSummary" class="mt-3 d-none">
                            <hr>
                            <p><strong>Total Consumers Imported:</strong> <span id="totalImported">0</span></p>
                            <p><strong>Total Time Taken:</strong> <span id="totalTime">0s</span></p>
                            <p><strong>Batches Processed:</strong> <span id="totalBatches">0</span></p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="submit" form="importConsumersForm" class="btn btn-primary">Start Import</button>
                    <button type="button" class="btn btn-danger" id="cancelImport">Close Import</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script src="{{ asset('js/consumers.js') }}"></script>
@endpush

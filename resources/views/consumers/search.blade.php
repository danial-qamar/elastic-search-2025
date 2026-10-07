@extends('layouts.app')

@section('title', 'Search Consumers')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Banner -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 fw-bold text-dark fs-3">Search Consumers</h2>
            <p class="text-muted small mb-0">Query consumers by Name, Contact Number, Reference No, or CNIC</p>
        </div>
        <a href="{{ route('consumers.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus-fill"></i> Add New Consumer
        </a>
    </div>

    <!-- Search Form Card -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-funnel-fill text-primary"></i>
                <h4 class="mb-0 text-dark">Query Filters</h4>
            </div>
            <span class="badge-violet">Elasticsearch Engine</span>
        </div>
        <div class="card-body">
            <form action="{{ route('consumers.search') }}" method="GET">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="searchName" class="form-label">
                            <i class="bi bi-person me-1 text-muted"></i> Consumer Name
                        </label>
                        <input type="text" class="form-control" id="searchName" name="name" 
                               placeholder="e.g. Muhammad Ali" value="{{ old('name', request('name')) }}">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="searchContactNo" class="form-label">
                            <i class="bi bi-telephone me-1 text-muted"></i> Contact Number
                        </label>
                        <input type="text" class="form-control font-mono" id="searchContactNo" name="contactno" 
                               placeholder="e.g. 923001234567" value="{{ old('contactno', request('contactno')) }}">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="searchReferenceNo" class="form-label">
                            <i class="bi bi-hash me-1 text-muted"></i> Reference Number
                        </label>
                        <input type="text" class="form-control font-mono" id="searchReferenceNo" name="reference_no" 
                               placeholder="14-digit reference" value="{{ old('reference_no', request('reference_no')) }}">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="searchCnic" class="form-label">
                            <i class="bi bi-credit-card-2-front me-1 text-muted"></i> CNIC / NIC
                        </label>
                        <input type="text" class="form-control font-mono" id="searchCnic" name="occupant_nicno" 
                               placeholder="13-digit CNIC" value="{{ old('occupant_nicno', request('occupant_nicno')) }}">
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 pt-3 border-top" style="border-color: var(--border-subtle) !important;">
                    @if(request()->anyFilled(['name', 'contactno', 'reference_no', 'occupant_nicno']))
                        <a href="{{ route('consumers.search') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                        </a>
                    @endif
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-search"></i> Execute Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results Section -->
    @if(isset($searchResults))
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-list-check text-primary"></i>
                    <h4 class="mb-0 text-dark">Matched Results</h4>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge-amber">{{ number_format($total ?? count($searchResults)) }} Matches Found</span>
                    @if(isset($totalPages) && $totalPages > 1)
                        <span class="badge-violet">Page {{ $page }} of {{ $totalPages }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact No</th>
                                <th>Reference No</th>
                                <th>CNIC No</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($searchResults as $consumer)
                                @php
                                    $id = isset($consumer['_source']) ? ($consumer['_source']['id'] ?? $consumer['_id'] ?? null) : $consumer->id;
                                    $name = isset($consumer['_source']) ? ($consumer['_source']['name'] ?? '') : $consumer->name;
                                    $contact = isset($consumer['_source']) ? ($consumer['_source']['contactno'] ?? '') : $consumer->contactno;
                                    $refNo = isset($consumer['_source']) ? ($consumer['_source']['reference_no'] ?? '') : $consumer->reference_no;
                                    $cnic = isset($consumer['_source']) ? ($consumer['_source']['occupant_nicno'] ?? '') : $consumer->occupant_nicno;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="stat-icon violet" style="width: 32px; height: 32px; font-size: 14px; border-radius: 50%;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                            <span class="fw-semibold text-dark">{{ $name ?: '—' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-mono text-secondary">{{ $contact ?: '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="code-ref badge-violet px-2 py-1">{{ $refNo ?: '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="font-mono text-secondary">{{ $cnic ?: '—' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            @if($id)
                                                <a href="{{ route('consumers.edit', $id) }}" class="btn btn-warning btn-sm" title="Edit Consumer">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </a>
                                                <form action="{{ route('consumers.destroy', $id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this consumer?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Consumer">
                                                        <i class="bi bi-trash3-fill"></i> Delete
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="py-4">
                                            <i class="bi bi-search fs-1 text-muted d-block mb-3"></i>
                                            <h5 class="text-dark fw-bold mb-1">No Matching Consumers</h5>
                                            <p class="text-muted small">Try refining your search terms or verify the reference number.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination controls for search results --}}
                @if(isset($totalPages) && $totalPages > 1)
                    <div class="d-flex justify-content-center p-3 border-top" style="border-color: var(--border-subtle) !important;">
                        <nav>
                            <ul class="pagination pagination-sm flex-wrap">
                                {{-- Previous --}}
                                @if($page > 1)
                                    <li class="page-item">
                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}" rel="prev" aria-label="Previous">&lsaquo;</a>
                                    </li>
                                @else
                                    <li class="page-item disabled" aria-disabled="true">
                                        <span class="page-link">&lsaquo;</span>
                                    </li>
                                @endif

                                @php
                                    $start = max(1, $page - 3);
                                    $end = min($totalPages, $page + 3);
                                @endphp

                                @if($start > 1)
                                    <li class="page-item">
                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => 1]) }}">1</a>
                                    </li>
                                    @if($start > 2)
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    @endif
                                @endif

                                @for ($i = $start; $i <= $end; $i++)
                                    <li class="page-item {{ $i == $page ? 'active' : '' }}">
                                        @if ($i == $page)
                                            <span class="page-link">{{ $i }}</span>
                                        @else
                                            <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
                                        @endif
                                    </li>
                                @endfor

                                @if($end < $totalPages)
                                    @if($end < $totalPages - 1)
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    @endif
                                    <li class="page-item">
                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $totalPages]) }}">{{ $totalPages }}</a>
                                    </li>
                                @endif

                                {{-- Next --}}
                                @if($page < $totalPages)
                                    <li class="page-item">
                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}" rel="next" aria-label="Next">&rsaquo;</a>
                                    </li>
                                @else
                                    <li class="page-item disabled" aria-disabled="true">
                                        <span class="page-link">&rsaquo;</span>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

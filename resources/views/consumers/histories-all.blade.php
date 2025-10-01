   
   @extends('layouts.app')

@section('title', 'Consumers')

@section('content')

<div class="container">
   <div class="card mt-5">
        <div class="card-header bg-dark text-white">
            <h4>All Consumers Histories</h4>
        </div>
        <div class="card-body">
            @if($histories->isEmpty())
                <p>No history records found.</p>
            @else
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Consumer</th>
                            <th>Updated By</th>
                            <th>Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($histories as $history)
                            <tr>
                                <td>{{ $history->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('consumers.edit', $history->consumer_id) }}">
                                        {{ $history->consumer->name ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>{{ $history->user->name ?? 'System' }}</td>
                                <td>
                                    <ul class="mb-0">
                                        @foreach($history->changed_fields as $field => $change)
                                            <li>
                                                <strong>{{ ucfirst(str_replace('_',' ', $field)) }}</strong>: 
                                                <span class="text-danger">{{ $change['old'] ?? '-' }}</span> → 
                                                <span class="text-success">{{ $change['new'] ?? '-' }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-center">
                    {{ $histories->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

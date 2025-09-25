@extends('layouts.app')

@section('title', 'Consumers')

@section('content')

<div class="container">
    <h2 class="text-center mb-4">Dashboard</h2>

    <div class="card mt-4">
        <div class="card-header bg-dark text-white">
            <h4>Import Logs</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Bill Month</th>
                        <th>Duration</th>
                        <th>Consumers</th>
                        <th>Subdivisions</th>
                        <th>Indexed</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr data-bs-toggle="collapse" data-bs-target="#log-{{ $log->id }}" class="accordion-toggle table-primary" style="cursor:pointer;">
                            <td>{{ \Carbon\Carbon::createFromFormat('Ym', $log->bill_month)->format('M Y') }}</td>
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
                                    @if($days > 0) {{ $days }}d @endif
                                    @if($hours > 0) {{ $hours }}h @endif
                                    @if($minutes > 0) {{ $minutes }}m @endif
                                    @if($seconds > 0) {{ $seconds }}s @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ number_format($log->consumers_count ?? 0) }}</td>
                            <td>{{ number_format($log->subdivisions_count ?? 0) }}</td>
                            <td>{{ number_format($log->indexed_count ?? 0) }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('jS M, Y h:i A') }}</td>
                        </tr>

                        {{-- Collapsible subdivision rows --}}
                        <tr>
                            <td colspan="6" class="hiddenRow p-0">
                                <div class="collapse" id="log-{{ $log->id }}">
                                    <table class="table table-sm table-striped m-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th class="ps-5">Subdivision Code</th>
                                                <th>Consumers</th>
                                                <th>Indexed</th>
                                                <th>Created</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($log->subdivisions as $sub)
                                                <tr>
                                                    <td class="ps-5">{{ $sub->subdivision_code }}</td>
                                                    <td>{{ number_format($sub->consumers_count ?? 0) }}</td>
                                                    <td>{{ number_format($sub->indexed_count ?? 0) }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($sub->created_at)->format('jS M, Y h:i A') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No import logs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
@push('scripts')
    <script src="{{ asset('js/consumers.js') }}"></script>
@endpush

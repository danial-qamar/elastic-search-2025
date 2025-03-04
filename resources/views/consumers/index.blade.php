@extends('layouts.app')

@section('title', 'Consumers')

@section('content')

<div class="container">
    <h2 class="text-center mb-4">Consumers</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('consumers.create') }}" class="btn btn-success mb-4">Add New Consumer</a>
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
            <div clas, 'searchResults', 'total', 'totalPages', 'page's="d-flex justify-content-center">
                <nav>
                    <ul class="pagination pagination-sm">
                        {{ $consumers->links('pagination::bootstrap-4') }}
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

@endsection

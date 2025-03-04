@extends('layouts.app')

@section('title', 'Consumers')

@section('content')

<div class="container">
    <h2 class="text-center mb-4">Search Consumers</h2>

    <a href="{{ route('consumers.create') }}" class="btn btn-success mb-4">Add New Consumer</a>

    <div class="card">
        <div class="card-header">
            <h4>Search Consumers</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('consumers.search') }}" method="GET">
                <div class="form-group">
                    <label for="searchName">Name</label>
                    <input type="text" class="form-control" id="searchName" name="name" 
                           placeholder="Enter Name" value="{{ old('name', request('name')) }}">
                </div>
                <div class="form-group">
                    <label for="searchContactNo">Contact No</label>
                    <input type="text" class="form-control" id="searchContactNo" name="contactno" 
                           placeholder="Enter Contact No" value="{{ old('contactno', request('contactno')) }}">
                </div>
                <div class="form-group">
                    <label for="searchReferenceNo">Reference No</label>
                    <input type="text" class="form-control" id="searchReferenceNo" name="reference_no" 
                           placeholder="Enter Reference No" value="{{ old('reference_no', request('reference_no')) }}">
                </div>
                <div class="form-group">
                    <label for="searchCnic">CNIC</label>
                    <input type="text" class="form-control" id="searchCnic" name="occupant_nicno" 
                           placeholder="Enter CNIC" value="{{ old('occupant_nicno', request('occupant_nicno')) }}">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    @if(isset($searchResults) && count($searchResults) > 0)
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h4>Search Results</h4>
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
                        @foreach ($searchResults as $consumer)
                        <tr>
                            <td>{{ isset($consumer['_source']) ? $consumer['_source']['name'] : $consumer->name }}</td>
                            <td>{{ isset($consumer['_source']) ? $consumer['_source']['contactno'] : $consumer->contactno }}</td>
                            <td>{{ isset($consumer['_source']) ? $consumer['_source']['reference_no'] : $consumer->reference_no }}</td>
                            <td>{{ isset($consumer['_source']) ? $consumer['_source']['occupant_nicno'] : $consumer->occupant_nicno }}</td>
                            <td>
                                <a href="{{ route('consumers.edit', isset($consumer['_source']) ? $consumer['_source']['id'] : $consumer->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('consumers.destroy', isset($consumer['_source']) ? $consumer['_source']['id'] : $consumer->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

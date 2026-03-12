@extends('layouts.app')

@section('title', 'Create Survey')

@section('content')
    <h1>Create New Survey</h1>

    @if ($errors->any())
        <div class="w3-panel w3-red w3-round">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ route('independent.store-survey') }}" class="w3-container w3-card w3-white w3-padding">
        @csrf
        <p>
            <label>Title</label>
            <input class="w3-input w3-border" type="text" name="title" value="{{ old('title') }}" required>
        </p>
        <p>
            <label>Description</label>
            <textarea class="w3-input w3-border" name="description" rows="4">{{ old('description') }}</textarea>
        </p>
        <p>
            <label>Category</label>
            <select class="w3-select w3-border" name="category" required>
                <option value="">Select Category</option>
                <option value="Marketing">Marketing</option>
                <option value="Academic">Academic</option>
                <option value="Product">Product</option>
                <option value="Political">Political</option>
            </select>
        </p>
        <p>
            <label>Type</label>
            <select class="w3-select w3-border" name="type" required>
                <option value="">Select Type</option>
                <option value="public">Public</option>
                <option value="invitation">Invitation Only</option>
            </select>
        </p>
        <p>
            <button class="w3-button w3-purple w3-round" type="submit">Create Survey</button>
            <a href="{{ route('independent.surveys') }}" class="w3-button w3-grey w3-round">Cancel</a>
        </p>
    </form>
@endsection

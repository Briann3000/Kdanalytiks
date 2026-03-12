@extends('layouts.app')

@section('title', 'Edit Survey')

@section('content')
    <h1>Edit Survey</h1>

    @if ($errors->any())
        <div class="w3-panel w3-red w3-round">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ route('organization.update-survey', $survey) }}" class="w3-container w3-card w3-white w3-padding">
        @csrf
        @method('PUT')
        <p>
            <label>Title</label>
            <input class="w3-input w3-border" type="text" name="title" value="{{ old('title', $survey->title) }}" required>
        </p>
        <p>
            <label>Description</label>
            <textarea class="w3-input w3-border" name="description" rows="4">{{ old('description', $survey->description) }}</textarea>
        </p>
        <p>
            <label>Category</label>
            <select class="w3-select w3-border" name="category" required>
                @foreach(['Marketing', 'Academic', 'Product', 'Political'] as $cat)
                    <option value="{{ $cat }}" {{ old('category', $survey->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </p>
        <p>
            <label>Type</label>
            <select class="w3-select w3-border" name="type" required>
                <option value="public" {{ old('type', $survey->type) == 'public' ? 'selected' : '' }}>Public</option>
                <option value="invitation" {{ old('type', $survey->type) == 'invitation' ? 'selected' : '' }}>Invitation Only</option>
            </select>
        </p>
        <p>
            <label>Status</label>
            <select class="w3-select w3-border" name="status" required>
                @foreach(['draft', 'active', 'closed'] as $s)
                    <option value="{{ $s }}" {{ old('status', $survey->status) == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </p>
        <p>
            <button class="w3-button w3-blue w3-round" type="submit">Update Survey</button>
            <a href="{{ route('organization.surveys') }}" class="w3-button w3-grey w3-round">Cancel</a>
        </p>
    </form>
@endsection

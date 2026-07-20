@extends('layouts.app')

@section('content')
    <div class="w3-container w3-padding">
        <h2 class="w3-text-blue"><i class="fa fa-list"></i> Available Public Surveys</h2>
        <p>Participate in our ongoing public surveys. Your feedback matters!</p>

        <!-- Search and Filter Section -->
        <div class="w3-card w3-white w3-padding w3-round w3-margin-bottom">
            <form method="get" class="w3-row-padding">
                <div class="w3-half">
                    <label>Search Surveys</label>
                    <input class="w3-input w3-border" type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by title or description...">
                </div>
                <div class="w3-quarter">
                    <label>Category</label>
                    <select class="w3-select w3-border" name="category">
                        <option value="all">All Categories</option>
                        <option value="education">Education</option>
                        <option value="health">Health</option>
                        <option value="technology">Technology</option>
                        <option value="business">Business</option>
                    </select>
                </div>
                <div class="w3-quarter">
                    <label>&nbsp;</label>
                    <button type="submit" class="w3-button w3-blue w3-block w3-margin-top">
                        <i class="fa fa-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Survey Statistics -->
        <div class="w3-row-padding w3-margin-top">
            <div class="w3-quarter">
                <div class="w3-card w3-padding w3-blue w3-center">
                    <h3>0</h3>
                    <p>Available Surveys</p>
                </div>
            </div>
            <div class="w3-quarter">
                <div class="w3-card w3-padding w3-green w3-center">
                    <h3>0</h3>
                    <p>Total Responses</p>
                </div>
            </div>
            <div class="w3-quarter">
                <div class="w3-card w3-padding w3-purple w3-center">
                    <h3>4</h3>
                    <p>Categories</p>
                </div>
            </div>
            <div class="w3-quarter">
                <div class="w3-card w3-padding w3-blue w3-center">
                    <h3>24/7</h3>
                    <p>Always Available</p>
                </div>
            </div>
        </div>

        <!-- Surveys List -->
        <div class="w3-margin-top">
            <div class="w3-panel w3-yellow w3-round">
                <h3>Coming Soon</h3>
                <p>Public surveys functionality is being implemented. Please check back later or contact the administrator.
                </p>
            </div>
        </div>
    </div>
@endsection
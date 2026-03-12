<?php include 'header.php'; ?>

<div class="w3-container w3-padding-64 w3-center">
    <h1 class="w3-xxxlarge w3-text-blue"><i class="fa fa-poll"></i> KMSurveyTool</h1>
    <p class="w3-large">Create, manage, and analyze surveys with ease</p>
</div>

<div class="w3-row-padding w3-padding-32 w3-center">
    <div class="w3-quarter w3-card w3-padding w3-hover-shadow">
        <i class="fa fa-user-shield w3-text-blue w3-jumbo"></i>
        <h3>Admin</h3>
        <p>Manage users, surveys, payments, and reports</p>
        <a href="admin-login.php" class="w3-button w3-blue w3-round-large">Login</a>
    </div>

    <div class="w3-quarter w3-card w3-padding w3-hover-shadow">
        <i class="fa fa-building w3-text-green w3-jumbo"></i>
        <h3>Organizations</h3>
        <p>Create and manage surveys for your business</p>
        <a href="organization-login.php" class="w3-button w3-green w3-round-large">Login</a>
        <a href="organization-register.php" class="w3-button w3-light-grey w3-round-large w3-margin-top">Register</a>
    </div>

    <div class="w3-quarter w3-card w3-padding w3-hover-shadow">
        <i class="fa fa-user-graduate w3-text-purple w3-jumbo"></i>
        <h3>Researchers</h3>
        <p>PhD students and independent researchers</p>
        <a href="independent-login.php" class="w3-button w3-purple w3-round-large">Login</a>
        <a href="independent-register.php" class="w3-button w3-light-grey w3-round-large w3-margin-top">Register</a>
    </div>

    <div class="w3-quarter w3-card w3-padding w3-hover-shadow">
        <i class="fa fa-users w3-text-orange w3-jumbo"></i>
        <h3>Respondents</h3>
        <p>Register and take surveys sent to you</p>
        <a href="respondent-login.php" class="w3-button w3-orange w3-round-large">Login</a>
        <a href="respondent-register.php" class="w3-button w3-light-grey w3-round-large w3-margin-top">Register</a>
    </div>
    
    <div class="w3-row-padding w3-padding-32 w3-center">
    <div class="w3-quarter w3-card w3-padding w3-hover-shadow">
        <i class="fa fa-list-alt w3-text-blue w3-jumbo"></i>
        <h3>Public Surveys</h3>
        <p>Browse and participate in public surveys</p>
        <a href="public-list-surveys.php" class="w3-button w3-blue w3-round-large">Browse Surveys</a>
    </div>
    <!-- ... other cards ... -->
</div>
</div>

<div class="w3-container w3-padding-64 w3-center w3-light-grey">
    <h2>Why Choose KMSurveyTool?</h2>
    <div class="w3-row-padding w3-padding-32">
        <div class="w3-third">
            <i class="fa fa-chart-bar w3-text-blue w3-xxlarge"></i>
            <p>Advanced analytics with charts and maps</p>
        </div>
        <div class="w3-third">
            <i class="fa fa-mobile-alt w3-text-green w3-xxlarge"></i>
            <p>Responsive design, works like a mobile app</p>
        </div>
        <div class="w3-third">
            <i class="fa fa-lock w3-text-purple w3-xxlarge"></i>
            <p>Secure, role-based survey management</p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
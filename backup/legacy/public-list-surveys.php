<?php include 'header.php'; ?>

<?php
// Get all active public surveys
$publicSurveys = R::find('surveys', ' status = ? AND type = ? ORDER BY created_at DESC', ['active', 'public']);

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Apply filters if provided
if (!empty($search) || !empty($category)) {
    $conditions = ['status = ? AND type = ?'];
    $params = ['active', 'public'];
    
    if (!empty($search)) {
        $conditions[] = '(title LIKE ? OR description LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    if (!empty($category) && $category != 'all') {
        $conditions[] = 'category = ?';
        $params[] = $category;
    }
    
    $whereClause = implode(' AND ', $conditions);
    $publicSurveys = R::find('surveys', $whereClause, $params);
}

// Get unique categories for filter dropdown
$categories = R::getCol('SELECT DISTINCT category FROM surveys WHERE status = ? AND type = ? ORDER BY category', ['active', 'public']);
?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-list"></i> Available Public Surveys</h2>
    <p>Participate in our ongoing public surveys. Your feedback matters!</p>
    
    <!-- Search and Filter Section -->
    <div class="w3-card w3-white w3-padding w3-round w3-margin-bottom">
        <form method="get" class="w3-row-padding">
            <div class="w3-half">
                <label>Search Surveys</label>
                <input class="w3-input w3-border" type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title or description...">
            </div>
            <div class="w3-quarter">
                <label>Category</label>
                <select class="w3-select w3-border" name="category">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category == $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
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
                <h3><?php echo count($publicSurveys); ?></h3>
                <p>Available Surveys</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-green w3-center">
                <h3><?php echo array_sum(array_map(function($s) { return R::count('responses', ' survey_id = ? ', [$s->id]); }, $publicSurveys)); ?></h3>
                <p>Total Responses</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-purple w3-center">
                <h3><?php echo count($categories); ?></h3>
                <p>Categories</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-orange w3-center">
                <h3>24/7</h3>
                <p>Always Available</p>
            </div>
        </div>
    </div>
    
    <!-- Surveys List -->
    <div class="w3-margin-top">
        <?php if (count($publicSurveys) > 0): ?>
            <div class="w3-row-padding">
                <?php foreach ($publicSurveys as $survey): ?>
                    <div class="w3-third w3-margin-bottom">
                        <div class="w3-card w3-white w3-padding w3-round w3-hover-shadow">
                            <div class="w3-margin-bottom">
                                <span class="w3-tag w3-blue"><?php echo htmlspecialchars($survey->category); ?></span>
                                <span class="w3-tag w3-green w3-right"><?php echo R::count('responses', ' survey_id = ? ', [$survey->id]); ?> responses</span>
                            </div>
                            
                            <h4><?php echo htmlspecialchars($survey->title); ?></h4>
                            <p><?php echo substr(htmlspecialchars($survey->description), 0, 120) . '...'; ?></p>
                            
                            <div class="w3-margin-top">
                                <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($survey->created_at)); ?></p>
                                <p><strong>Type:</strong> 
                                    <?php if ($survey->type == 'public'): ?>
                                        <span class="w3-tag w3-green">Public</span>
                                    <?php else: ?>
                                        <span class="w3-tag w3-orange">Invitation Only</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <div class="w3-margin-top">
                                <a href="public-take-survey.php?id=<?php echo $survey->id; ?>" class="w3-button w3-blue w3-round w3-small">
                                    <i class="fa fa-clipboard-list"></i> Take Survey
                                </a>
                                <a href="public-survey-report.php?id=<?php echo $survey->id; ?>" class="w3-button w3-green w3-round w3-small w3-margin-left">
                                    <i class="fa fa-chart-bar"></i> View Results
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="w3-panel w3-yellow w3-round">
                <h3>No Surveys Found</h3>
                <p>No public surveys are currently available. Please check back later or contact the administrator.</p>
                <?php if (!empty($search) || !empty($category)): ?>
                    <a href="public-list-surveys.php" class="w3-button w3-blue w3-round w3-margin-top">
                        <i class="fa fa-refresh"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Featured Surveys Section -->
    <div class="w3-margin-top">
        <h3 class="w3-text-blue">Featured Surveys</h3>
        <div class="w3-row-padding">
            <?php
            // Get surveys with most responses as featured
            $featuredSurveys = R::getAll('SELECT s.*, COUNT(r.id) as response_count 
                FROM surveys s 
                LEFT JOIN responses r ON s.id = r.survey_id 
                WHERE s.status = ? AND s.type = ? 
                GROUP BY s.id 
                ORDER BY response_count DESC 
                LIMIT 3', ['active', 'public']);
            ?>
            
            <?php foreach ($featuredSurveys as $survey): ?>
                <div class="w3-third w3-margin-bottom">
                    <div class="w3-card w3-blue w3-padding w3-round">
                        <h4 class="w3-text-white"><?php echo htmlspecialchars($survey['title']); ?></h4>
                        <p class="w3-text-white"><?php echo substr(htmlspecialchars($survey['description']), 0, 80) . '...'; ?></p>
                        <p class="w3-text-white"><strong><?php echo $survey['response_count']; ?> responses</strong></p>
                        <a href="public-take-survey.php?id=<?php echo $survey['id']; ?>" class="w3-button w3-white w3-round w3-small">
                            <i class="fa fa-clipboard-list"></i> Take Survey
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Categories Section -->
    <div class="w3-margin-top">
        <h3 class="w3-text-blue">Browse by Category</h3>
        <div class="w3-row-padding">
            <?php foreach ($categories as $category): ?>
                <?php
                $categoryCount = R::count('surveys', ' status = ? AND type = ? AND category = ? ', ['active', 'public', $category]);
                ?>
                <div class="w3-quarter w3-margin-bottom">
                    <div class="w3-card w3-white w3-padding w3-round w3-center">
                        <h4><?php echo htmlspecialchars($category); ?></h4>
                        <p><?php echo $categoryCount; ?> survey<?php echo ($categoryCount != 1) ? 's' : ''; ?></p>
                        <a href="public-list-surveys.php?category=<?php echo urlencode($category); ?>" class="w3-button w3-blue w3-round w3-small w3-margin-top">
                            <i class="fa fa-arrow-right"></i> View All
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Quick Info Section -->
    <div class="w3-margin-top">
        <div class="w3-panel w3-blue w3-round">
            <h3>About Public Surveys</h3>
            <p>Our public surveys are open to everyone and help us gather valuable insights from diverse perspectives. Your participation is completely anonymous and helps improve our services and research.</p>
            <div class="w3-row-padding w3-margin-top">
                <div class="w3-third">
                    <div class="w3-center">
                        <i class="fa fa-user-secret fa-2x w3-text-blue"></i>
                        <p><strong>Anonymous</strong></p>
                        <p>Your responses are confidential</p>
                    </div>
                </div>
                <div class="w3-third">
                    <div class="w3-center">
                        <i class="fa fa-clock fa-2x w3-text-green"></i>
                        <p><strong>Quick</strong></p>
                        <p>Most surveys take 5-10 minutes</p>
                    </div>
                </div>
                <div class="w3-third">
                    <div class="w3-center">
                        <i class="fa fa-chart-line fa-2x w3-text-orange"></i>
                        <p><strong>Impactful</strong></p>
                        <p>Your feedback drives real change</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
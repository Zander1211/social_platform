<?php
// News Feed Dashboard Component
// This component provides navigation and quick actions for the news feed

// Get current page to determine context
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isNewsPage = ($currentPage === 'index');

// Get some basic stats if we have database access
$totalPosts = 0;
$recentPostsCount = 0;
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalPosts = $result['total'] ?? 0;
        
        // Get posts from last 24 hours
        $stmt = $pdo->query("SELECT COUNT(*) as recent FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $recentPostsCount = $result['recent'] ?? 0;
    } catch (Exception $e) {
        // Silently handle database errors
    }
}
?>
<div class="news-feed-dashboard">
    <!-- Post Filters Section -->
    <?php 
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    $scope = $_GET['scope'] ?? 'all';
    $showUserResults = ($scope === 'users');
    
    if ($currentPage === 'index' && !$showUserResults): 
    ?>
    <div class="dashboard-section floating-filters">
        <h4><i class="fas fa-filter"></i> Filter Posts</h4>
        <div class="sidebar-filter-buttons">
            <?php
            // Get current filter for active state
            $currentFilter = $_GET['filter'] ?? 'all';
            $currentQuery = $_GET['q'] ?? '';
            
            // Build base URL with current search query
            $baseUrl = 'index.php';
            $queryParams = [];
            if ($currentQuery) {
                $queryParams['q'] = $currentQuery;
            }
            
            // All Posts button
            $allUrl = $baseUrl . ($queryParams ? '?' . http_build_query($queryParams) : '');
            $allActive = ($currentFilter === 'all' || $currentFilter === null) ? 'active' : '';
            echo '<a href="' . $allUrl . '" class="sidebar-filter-btn ' . $allActive . '" title="Show all posts (Alt+1)">';
            echo '<i class="fas fa-globe"></i> <span>All Posts</span>';
            echo '</a>';
            
            // This Week button
            $weekParams = $queryParams;
            $weekParams['filter'] = 'week';
            $weekUrl = $baseUrl . '?' . http_build_query($weekParams);
            $weekActive = ($currentFilter === 'week') ? 'active' : '';
            echo '<a href="' . $weekUrl . '" class="sidebar-filter-btn ' . $weekActive . '" title="Show posts from this week (Alt+2)">';
            echo '<i class="fas fa-calendar-week"></i> <span>This Week</span>';
            echo '</a>';
            
            // This Month button
            $monthParams = $queryParams;
            $monthParams['filter'] = 'month';
            $monthUrl = $baseUrl . '?' . http_build_query($monthParams);
            $monthActive = ($currentFilter === 'month') ? 'active' : '';
            echo '<a href="' . $monthUrl . '" class="sidebar-filter-btn ' . $monthActive . '" title="Show posts from this month (Alt+3)">';
            echo '<i class="fas fa-calendar-alt"></i> <span>This Month</span>';
            echo '</a>';
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* News Feed Dashboard Styles */
.news-feed-dashboard {
    background: var(--bg-primary);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: var(--space-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: var(--space-lg);
    position: sticky;
    top: var(--space-xl);
    transition: all 0.3s ease;
}

.news-feed-dashboard:hover {
    border-color: var(--academic-green);
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.dashboard-section {
    margin-bottom: var(--space-lg);
    padding-bottom: var(--space-md);
    border-bottom: 2px solid var(--border-color-light);
}

.dashboard-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.dashboard-section h4 {
    margin: 0 0 var(--space-md) 0;
    font-size: var(--font-size-base);
    font-weight: 600;
    color: var(--academic-green);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    font-family: var(--font-family-serif);
}

.dashboard-section h4 i {
    color: var(--academic-gold);
    font-size: var(--font-size-sm);
}

/* Floating Filters Styles */
.floating-filters {
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
    border: 2px solid var(--academic-gold);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    margin-bottom: var(--space-lg);
    box-shadow: 0 8px 25px rgba(30, 58, 138, 0.1);
    position: relative;
    overflow: hidden;
}

.floating-filters::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--academic-green) 0%, var(--academic-gold) 50%, var(--academic-green) 100%);
}

.sidebar-filter-buttons {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.sidebar-filter-btn {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-md) var(--space-lg);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--bg-primary);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: var(--font-size-sm);
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.sidebar-filter-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(30, 58, 138, 0.1), transparent);
    transition: left 0.5s ease;
}

.sidebar-filter-btn:hover {
    background: var(--academic-green);
    color: var(--text-inverse);
    border-color: var(--academic-green);
    transform: translateX(4px);
    box-shadow: var(--shadow-md);
    text-decoration: none;
}

.sidebar-filter-btn:hover::before {
    left: 100%;
}

.sidebar-filter-btn.active {
    background: linear-gradient(135deg, var(--academic-green) 0%, var(--academic-green-dark) 100%);
    color: var(--text-inverse);
    border-color: var(--academic-gold);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    transform: translateX(2px);
}

.sidebar-filter-btn.active:hover {
    background: linear-gradient(135deg, var(--academic-green-light) 0%, var(--academic-green) 100%);
    transform: translateX(6px);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.3), var(--shadow-lg);
}

.sidebar-filter-btn i {
    font-size: var(--font-size-base);
    transition: transform 0.3s ease;
    width: 16px;
    text-align: center;
}

.sidebar-filter-btn:hover i {
    transform: scale(1.2);
}

.sidebar-filter-btn.active i {
    color: var(--academic-gold);
}

.sidebar-filter-btn span {
    flex: 1;
    text-align: left;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .news-feed-dashboard {
        position: static;
        margin-bottom: var(--space-md);
    }
    
    .floating-filters {
        margin-bottom: var(--space-md);
    }
    
    .sidebar-filter-buttons {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .sidebar-filter-btn {
        flex: 1;
        min-width: 0;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .sidebar-filter-buttons {
        flex-direction: column;
    }
    
    .sidebar-filter-btn {
        width: 100%;
    }
}
</style>

<script>
// News Feed Dashboard JavaScript Functions

// Quick action functions removed

// Filter functions moved to header
</script>
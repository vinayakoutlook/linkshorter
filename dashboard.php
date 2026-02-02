<?php
session_start();
$config = require 'config.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Check session timeout
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $config['session_lifetime'])) {
    session_destroy();
    header('Location: index.php?timeout=1');
    exit;
}

// Load links from file
function loadLinks() {
    global $config;
    if (!file_exists($config['data_file'])) {
        return [];
    }
    $content = file_get_contents($config['data_file']);
    return $content ? json_decode($content, true) : [];
}

$links = loadLinks();
$totalLinks = count($links);
$activeLinks = count(array_filter($links, fn($l) => $l['status'] === 'active'));
$totalClicks = array_sum(array_column($links, 'clicks'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $config['app_name']; ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-link"></i>
                </div>
                <h2><?php echo $config['app_name']; ?></h2>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item" data-section="links">
                    <i class="fas fa-list"></i>
                    <span>All Links</span>
                </a>
                <a href="#" class="nav-item" data-section="create">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create Link</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name">Admin</span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 id="pageTitle">Dashboard</h1>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" id="quickCreateBtn">
                        <i class="fas fa-plus"></i>
                        Create New Link
                    </button>
                </div>
            </header>
            
            <!-- Dashboard Section -->
            <section id="dashboardSection" class="content-section active">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-link"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalLinks; ?></h3>
                            <p>Total Links</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $activeLinks; ?></h3>
                            <p>Active Links</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalClicks; ?></h3>
                            <p>Total Clicks</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo date('M d'); ?></h3>
                            <p>Today's Date</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Links -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-clock"></i> Recent Links</h2>
                        <a href="#" class="view-all" data-section="links">View All</a>
                    </div>
                    <div class="card-body">
                        <div id="recentLinksContainer" class="links-list">
                            <!-- Links will be loaded here -->
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- All Links Section -->
            <section id="linksSection" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-list"></i> All Links</h2>
                        <div class="card-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchLinks" placeholder="Search links...">
                            </div>
                            <select id="filterStatus" class="filter-select">
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="allLinksContainer" class="links-list">
                            <!-- Links will be loaded here -->
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Create Link Section -->
            <section id="createSection" class="content-section">
                <div class="card create-card">
                    <div class="card-header">
                        <h2><i class="fas fa-plus-circle"></i> Create New Short Link</h2>
                    </div>
                    <div class="card-body">
                        <form id="createLinkForm" class="create-form">
                            <div class="form-group">
                                <label for="linkTitle">
                                    <i class="fas fa-heading"></i>
                                    Link Title
                                </label>
                                <input type="text" id="linkTitle" name="title" 
                                       placeholder="Enter a title for your link" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="originalUrl">
                                    <i class="fas fa-globe"></i>
                                    Original URL
                                </label>
                                <input type="url" id="originalUrl" name="url" 
                                       placeholder="https://example.com/your-long-url" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="linkCategory">
                                    <i class="fas fa-folder"></i>
                                    Category (Optional)
                                </label>
                                <select id="linkCategory" name="category">
                                    <option value="general">General</option>
                                    <option value="social">Social Media</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="affiliate">Affiliate</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="linkNotes">
                                    <i class="fas fa-sticky-note"></i>
                                    Notes (Optional)
                                </label>
                                <textarea id="linkNotes" name="notes" rows="3" 
                                          placeholder="Add any notes about this link..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-full">
                                <i class="fas fa-magic"></i>
                                Generate Short Link
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    <!-- Create Link Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Quick Create Link</h3>
                <button class="modal-close" id="closeCreateModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="quickCreateForm">
                    <div class="form-group">
                        <label for="quickTitle">Link Title</label>
                        <input type="text" id="quickTitle" name="title" 
                               placeholder="Enter link title" required>
                    </div>
                    <div class="form-group">
                        <label for="quickUrl">Original URL</label>
                        <input type="url" id="quickUrl" name="url" 
                               placeholder="https://example.com" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-magic"></i>
                        Generate Link
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Loading Modal -->
    <div id="loadingModal" class="modal">
        <div class="modal-content modal-loading">
            <div class="loader-container">
                <div class="loader">
                    <div class="loader-ring"></div>
                    <div class="loader-ring"></div>
                    <div class="loader-ring"></div>
                </div>
                <p>Generating your short link...</p>
                <span class="loader-subtext">Please wait a moment</span>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content modal-success">
            <div class="modal-header success">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="modal-body">
                <h3>Link Created Successfully!</h3>
                <p>Your short link is ready to use</p>
                
                <div class="success-link-box">
                    <input type="text" id="generatedLink" readonly>
                    <button class="btn btn-copy" id="copyLinkBtn">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <div class="success-actions">
                    <button class="btn btn-secondary" id="closeSuccessModal">
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                    <button class="btn btn-primary" id="copyAndClose">
                        <i class="fas fa-copy"></i>
                        Copy & Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content modal-details">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Link Details</h3>
                <button class="modal-close" id="closeDetailsModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="linkDetailsContent">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Link</h3>
                <button class="modal-close" id="closeEditModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editLinkForm">
                    <input type="hidden" id="editLinkId" name="id">
                    <div class="form-group">
                        <label for="editTitle">Link Title</label>
                        <input type="text" id="editTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="editUrl">Original URL</label>
                        <input type="url" id="editUrl" name="url" required>
                    </div>
                    <div class="form-group">
                        <label for="editCategory">Category</label>
                        <select id="editCategory" name="category">
                            <option value="general">General</option>
                            <option value="social">Social Media</option>
                            <option value="marketing">Marketing</option>
                            <option value="affiliate">Affiliate</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editNotes">Notes</label>
                        <textarea id="editNotes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editStatus">Status</label>
                        <select id="editStatus" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content modal-delete">
            <div class="modal-header danger">
                <div class="danger-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="modal-body">
                <h3>Delete Link?</h3>
                <p>Are you sure you want to delete this link? This action cannot be undone.</p>
                <input type="hidden" id="deleteLinkId">
                <div class="delete-actions">
                    <button class="btn btn-secondary" id="cancelDelete">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="toast-icon fas fa-check-circle"></i>
        <span class="toast-message"></span>
    </div>
    
    <script src="assets/script.js"></script>
</body>
</html>
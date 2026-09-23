<?php
// myLocalHost v2.0 Dashboard
// Main dashboard entry point with real system data

// Fetch data from API
$apiData = json_decode(file_get_contents('api/dashboard.php'), true);
$systemInfo = $apiData['system'] ?? [];
$services = $apiData['services'] ?? [];
$gitRepos = $apiData['repositories'] ?? [];

// Helper function for uptime display
function formatUptime($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . 'm';
    } elseif ($seconds < 86400) {
        return floor($seconds / 3600) . 'h';
    } else {
        return floor($seconds / 86400) . 'd';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>myLocalHost - Dashboard</title>
    <link rel="icon" href="/.global/assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0d1117;
            --bg-secondary: #161b22;
            --bg-tertiary: #21262d;
            --bg-card: #1c2128;
            --text-primary: #c9d1d9;
            --text-secondary: #8b949e;
            --accent: #58a6ff;
            --accent-hover: #79c0ff;
            --border: #30363d;
            --success: #238636;
            --warning: #d29922;
            --danger: #f85149;
            --sidebar-width: 250px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans', Helvetica, Arial, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .logo i {
            color: var(--accent);
            font-size: 1.5rem;
        }

        .main-nav {
            padding: 16px 0;
            flex: 1;
        }

        .main-nav ul {
            list-style: none;
        }

        .main-nav li {
            margin-bottom: 4px;
        }

        .main-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .main-nav a:hover {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .main-nav a.active {
            background-color: var(--bg-tertiary);
            color: var(--accent);
            border-left-color: var(--accent);
        }

        .main-nav a i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 24px;
            overflow-y: auto;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .main-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .main-header small {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 400;
        }

        .admin-info {
            background-color: var(--bg-tertiary);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        /* Search Bar */
        .search-bar {
            margin-bottom: 24px;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .search-bar input::placeholder {
            color: var(--text-secondary);
        }

        /* Widgets Grid */
        .widgets-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }

        .widget {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
        }

        .widget h2 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        /* Git Repositories */
        .repo-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .repo-item {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: border-color 0.2s;
        }

        .repo-item:hover {
            border-color: var(--accent);
        }

        .repo-info {
            flex: 1;
        }

        .repo-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .repo-meta {
            display: flex;
            gap: 12px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .repo-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .repo-status.clean {
            background-color: rgba(35, 134, 54, 0.2);
            color: #3fb950;
        }

        .repo-status.modified {
            background-color: rgba(210, 153, 34, 0.2);
            color: #d29922;
        }

        .repo-actions {
            display: flex;
            gap: 8px;
        }

        .repo-actions button {
            padding: 6px 12px;
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text-primary);
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .repo-actions button:hover {
            background-color: var(--accent);
            border-color: var(--accent);
            color: white;
        }

        /* Machine Info */
        .machine-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .info-value {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: var(--bg-tertiary);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--danger);
            transition: width 0.3s;
        }

        /* System Status */
        .services-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            background-color: var(--bg-secondary);
            border-radius: 4px;
        }

        .service-name {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .service-version {
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-left: 8px;
        }

        .service-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .service-status.online {
            background-color: rgba(35, 134, 54, 0.2);
            color: #3fb950;
        }

        .service-status.offline {
            background-color: rgba(248, 81, 73, 0.2);
            color: #f85149;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .service-status.online .status-dot {
            background-color: #3fb950;
            box-shadow: 0 0 8px #3fb950;
        }

        .service-status.offline .status-dot {
            background-color: #f85149;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 60px;
            }

            .logo span,
            .main-nav a span {
                display: none;
            }

            .main-nav a {
                justify-content: center;
                padding: 12px;
            }

            .main-nav a i {
                width: auto;
            }

            .main-content {
                margin-left: 60px;
            }

            .widgets-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-server"></i>
            <span>myLocalHost</span>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="myLocalhost.php"><i class="fas fa-folder-open"></i><span>File Manager</span></a></li>
                <li><a href="#"><i class="fas fa-globe"></i><span>Websites</span></a></li>
                <li><a href="#"><i class="fas fa-database"></i><span>Databases</span></a></li>
                <li><a href="#"><i class="fas fa-code-branch"></i><span>Git</span></a></li>
                <li><a href="#"><i class="fas fa-terminal"></i><span>Terminal</span></a></li>
                <li><a href="#"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="main-header">
            <h1>Dashboard <small>Local Development Environment</small></h1>
            <div class="admin-info">
                <i class="fas fa-user-shield"></i> Administrator
            </div>
        </header>

        <div class="search-bar">
            <input type="text" placeholder="Search projects, files, commands..." id="searchInput">
        </div>

        <div class="widgets-container">
            <!-- Git Repositories Widget -->
            <section class="widget git-repositories">
                <h2><i class="fas fa-code-branch"></i> Git Repositories</h2>
                <div class="repo-list">
                    <?php if (empty($gitRepos)): ?>
                        <div class="repo-item">
                            <div class="repo-info">
                                <div class="repo-name">No repositories found</div>
                                <div class="repo-meta">Initialize git in your project folders</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($gitRepos as $repo): ?>
                            <div class="repo-item">
                                <div class="repo-info">
                                    <div class="repo-name"><?php echo htmlspecialchars($repo['name'] ?? 'Unknown'); ?></div>
                                    <div class="repo-meta">
                                        <span><i class="fas fa-code-branch"></i> <?php echo htmlspecialchars($repo['branch'] ?? 'main'); ?></span>
                                        <span class="repo-status <?php echo strtolower($repo['status'] ?? 'unknown'); ?>">
                                            <?php echo ($repo['status'] ?? 'Unknown') === 'Clean' ? '<i class="fas fa-check"></i>' : '<i class="fas fa-exclamation"></i>'; ?>
                                            <?php echo htmlspecialchars($repo['status'] ?? 'Unknown'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="repo-actions">
                                    <button onclick="openExplorer('<?php echo addslashes($repo['path'] ?? ''); ?>')">
                                        <i class="fas fa-folder"></i> Explorer
                                    </button>
                                    <button onclick="openVSCode('<?php echo addslashes($repo['path'] ?? ''); ?>')">
                                        <i class="fas fa-code"></i> VS Code
                                    </button>
                                    <button onclick="openTerminal('<?php echo addslashes($repo['path'] ?? ''); ?>')">
                                        <i class="fas fa-terminal"></i> Terminal
                                    </button>
                                    <button onclick="openWebsite('<?php echo addslashes($repo['name'] ?? ''); ?>')">
                                        <i class="fas fa-globe"></i> Website
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Machine Information Widget -->
            <section class="widget machine-info">
                <h2><i class="fas fa-desktop"></i> Machine Information</h2>
                <div class="machine-info">
                    <div class="info-row">
                        <span class="info-label">Hostname</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['hostname'] ?? 'Unknown'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Operating System</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['os'] ?? 'Unknown'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">PHP Version</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['php_version'] ?? 'Unknown'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Memory Limit</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['memory_limit'] ?? 'Unknown'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Disk Usage</span>
                        <span class="info-value"><?php echo $systemInfo['disk']['used'] ?? '0 B'; ?> / <?php echo $systemInfo['disk']['total'] ?? '0 B'; ?> (<?php echo $systemInfo['disk']['percent'] ?? '0'; ?>%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $systemInfo['disk']['percent'] ?? 0; ?>%;"></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Uptime</span>
                        <span class="info-value"><?php echo formatUptime($systemInfo['uptime'] ?? 0); ?></span>
                    </div>
                </div>
            </section>

            <!-- System Status Widget -->
            <section class="widget system-status">
                <h2><i class="fas fa-server"></i> System Status</h2>
                <div class="services-list">
                    <?php foreach ($services as $service): ?>
                        <div class="service-item">
                            <div>
                                <span class="service-name"><?php echo htmlspecialchars($service['name']); ?></span>
                                <span class="service-version"><?php echo htmlspecialchars($service['version']); ?></span>
                            </div>
                            <span class="service-status <?php echo $service['status']; ?>">
                                <span class="status-dot"></span>
                                <?php echo ucfirst($service['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Action functions using API
        async function executeAction(action, path, name) {
            try {
                const response = await fetch('api/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: action,
                        path: path,
                        name: name
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (action === 'website' && result.url) {
                        window.open(result.url, '_blank');
                    } else {
                        // Show success notification
                        showNotification(result.message, 'success');
                    }
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                console.error('Action failed:', error);
                showNotification('Failed to execute action', 'error');
            }
        }

        function openExplorer(path) {
            executeAction('explorer', path);
        }

        function openVSCode(path) {
            executeAction('vscode', path);
        }

        function openTerminal(path) {
            executeAction('terminal', path);
        }

        function openWebsite(name) {
            executeAction('website', '', name);
        }

        // Notification system
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 6px;
                color: white;
                font-size: 0.9rem;
                z-index: 1000;
                animation: slideIn 0.3s ease;
                ${type === 'success' ? 'background-color: #238636;' : 'background-color: #f85149;'}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const query = this.value;
                if (query) {
                    // Redirect to file manager with search
                    window.location.href = 'myLocalhost.php?p=' + encodeURIComponent(query);
                }
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(async function() {
            try {
                const response = await fetch('api/dashboard.php');
                const data = await response.json();
                
                // Update dashboard data without page reload
                // This would be expanded to update specific elements
                console.log('Dashboard data refreshed');
            } catch (error) {
                console.error('Auto-refresh failed:', error);
            }
        }, 30000);
    </script>
</body>
</html>
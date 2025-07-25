<?php
/*
 * Main Header Template
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // starts a session if it isnt
}
// sets default timezone to each page because we use it for timestamps in certain functions
date_default_timezone_set('Africa/Johannesburg');
?>

<!-- ALL THE HTML CODE AND BASIC BOOTSTRAP STYLING WITH CSS FOR THE SIDEBAR AND THE MAIN HEADER FOR EACH PAGE -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ModernTech HR System - <?php echo $pageTitle ?? 'Admin Portal'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body {
            padding-left: 220px;
            transition: padding 0.3s ease;
            min-width: 300px;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 220px;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            z-index: 1030;
            padding-top: 56px;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        
        body.collapsed {
            padding-left: 60px;
        }
        
        .sidebar.collapsed {
            width: 60px;
        }
        
        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .card-title,
        .sidebar.collapsed .text-muted {
            display: none;
        }
        
        .sidebar.collapsed .nav-link {
            text-align: center;
            padding: 0.5rem;
        }
        
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.25rem;
        }
        
        .nav-link.active {
            background-color: #e9ecef;
            border-left: 3px solid #0d6efd;
            font-weight: 500;
        }
        
        .sidebar-toggle {
            position: absolute;
            right: 10px;
            top: 10px;
            z-index: 1050;
        }
        
        #mainContent {
            padding: 20px;
            width: 100%;
        }

        /* Scrollable content for 1024px and below */
        @media (max-width: 1024px) {
            #mainContent {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table-responsive {
                min-width: 100%;
            }
            
            .card {
                min-width: 100%;
            }
        }
    </style>
</head>
<body class="<?= isset($_COOKIE['sidebarCollapsed']) && $_COOKIE['sidebarCollapsed'] === 'true' ? 'collapsed' : '' ?>"> <!-- checks if the sidebar is collapsed  -->
    <!-- Sidebar Navigation -->
    <div class="sidebar <?= isset($_COOKIE['sidebarCollapsed']) && $_COOKIE['sidebarCollapsed'] === 'true' ? 'collapsed' : '' ?>" id="sidebar">
        <button class="btn btn-outline-secondary sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
            <i class="bi bi-chevron-double-left"></i>
        </button>
        
        <div class="position-sticky pt-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : '' ?>" href="employees.php">
                        <i class="bi bi-people me-2"></i>
                        <span>Employees</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>" href="attendance.php">
                        <i class="bi bi-calendar-check me-2"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'leave.php' ? 'active' : '' ?>" href="leave.php">
                        <i class="bi bi-calendar-event me-2"></i>
                        <span>Leave Requests</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'payroll.php' ? 'active' : '' ?>" href="payroll.php">
                        <i class="bi bi-cash-coin me-2"></i>
                        <span>Payroll</span>
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="container-fluid" id="mainContent">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-briefcase me-2"></i>
                        ModernTech HR System
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="text-muted">
                                <i class="bi bi-person-circle me-1"></i>
                                <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                            </span>
                        </div>
                    </div>
                </div>
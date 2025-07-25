<?php
/*
 * HR Dashboard - Main Overview
 * This is the main dashboard that shows all the important HR stats.
 */

// First we need our database connection and header
require_once 'db.php';      // This handles all the database stuff
require_once 'header.php';  // Standard header with navigation

// Security check
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, send them to login page
    header('Location: index.php');
    exit; // Stop the rest of the page from loading
}

// This code grabs all of the data we need for the dashboard
// We just assigned a superglobal variable for the data we want to represent
try {
    // Total number of employees, just used a simple count query
    $employeeCount = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    
    // Count of pending leave requests
    $pendingLeave = $pdo->query(
        "SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'"
    )->fetchColumn();
    
    // An outside source helped me to do this, this is just extra functionality to improve the website
    $absentToday = $pdo->query(
        "SELECT COUNT(*) FROM attendance 
         WHERE date = CURDATE() AND status = 'Absent'"
    )->fetchColumn();
    
    // Before I added this code that's below I kept on getting a certain error, So I researched it on Github and got this code to fix it. 
} catch (PDOException $e) {
    // If something breaks, log it but keep the page working
    error_log("Dashboard error on " . date('Y-m-d') . ": " . $e->getMessage());
    // Set defaults so the page still loads
    $employeeCount = $pendingLeave = $absentToday = 0;
}
?>

<!-- HTML content -->
<div class="container-fluid">
    <!-- Main heading with icon -->
    <h2 class="mb-4">
        <i class="bi bi-speedometer2 me-2"></i>
        Dashboard Overview
        <small class="text-muted">- Your HR snapshot</small>
    </h2>
    
    <!-- Top row with stat cards -->
    <div class="row mb-4">
        <!-- Employee Count Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">
                        <i class="bi bi-people-fill me-2"></i>
                        Total Employees
                    </h5>
                    <!-- We are just calling the data from the superglobal we created before the HTML code -->
                    <p class="display-4"><?= $employeeCount ?></p>
                    <!-- Link to see details -->
                    <a href="employees.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-right"></i> View All
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Pending Leave Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">
                        <i class="bi bi-hourglass-split me-2"></i>
                        Pending Leave
                    </h5>
                    <!-- We are just calling the data from the superglobal we created before the HTML code -->
                    <p class="display-4"><?= $pendingLeave ?></p>
                    <a href="leave.php" class="btn btn-outline-warning">
                        <i class="bi bi-arrow-right"></i> Review
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Absent Today Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Absent Today
                    </h5>
                    <!-- We are just calling the data from the superglobal we created before the HTML code -->
                    <p class="display-4"><?= $absentToday ?></p>
                    <a href="attendance.php" class="btn btn-outline-danger">
                        <i class="bi bi-arrow-right"></i> Check
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bottom row with activity tables -->
    <div class="row">
        <!-- Recent Leave Requests Table -->
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-calendar-event me-2"></i>
                        Recent Leave Requests
                        <small class="text-muted">- Last 5</small>
                    </h5>
                    
                    <!-- Table with scroll if needed -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get recent leave requests with employee names
                                // Used try block for error handling, this was recommended by Github users
                                try {
                                    // This PDO statement is for querying the database and then its just standard SQL code for selecting the data we want
                                    $stmt = $pdo->query("
                                        SELECT l.date, l.status, e.name 
                                        FROM leave_requests l
                                        JOIN employees e ON l.employee_id = e.employee_id
                                        ORDER BY l.date DESC
                                        LIMIT 5
                                    ");
                                    
                                    // Loop through each result
                                    while ($row = $stmt->fetch()):
                                ?>
                                <tr>
                                    <!-- This prevents the use of special characters -->
                                    <td><?= htmlspecialchars($row['name']) ?></td>

                                    <!-- Shows the date of the leave request -->
                                    <td><?= $row['date'] ?></td>
                                    <td>
                                        <!-- Just assigned appropriate colours based on the actions -->
                                        <span class="badge bg-<?= 
                                            $row['status'] == 'Approved' ? 'success' : 
                                            ($row['status'] == 'Denied' ? 'danger' : 'warning')
                                        ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <!-- Before I added this code we kept on getting error messages and then this fix I got from an outside source -->
                                <?php endwhile; ?>
                                <?php } catch (PDOException $e) {
                                    // Show error message if query fails
                                    echo '<tr><td colspan="3" class="text-muted">Temporarily unavailable</td></tr>';
                                    error_log("Leave requests error: " . $e->getMessage());
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Attendance Table -->
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-calendar-check me-2"></i>
                        Recent Attendance
                        <small class="text-muted">- Last 5</small>
                    </h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get recent attendance records
                                // Used try block for error handling, this was recommended by Github users (Same as the leave requests above)
                                try {
                                    // This PDO statement is for querying the database and then its just standard SQL code for selecting the data we want
                                    $stmt = $pdo->query("
                                        SELECT a.date, a.status, e.name 
                                        FROM attendance a
                                        JOIN employees e ON a.employee_id = e.employee_id
                                        ORDER BY a.date DESC
                                        LIMIT 5
                                    ");
                                    
                                    while ($row = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= $row['date'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $row['status'] == 'Present' ? 'success' : 'danger'
                                        ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php } catch (PDOException $e) {
                                    echo '<tr><td colspan="3" class="text-muted">Temporarily unavailable</td></tr>';
                                    error_log("Attendance error: " . $e->getMessage());
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Standard footer that appears on all pages -->
<?php require_once 'footer.php'; ?>
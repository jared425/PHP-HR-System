<?php
/*
 * Leave Management System
 * Handles employee leave requests and approvals
 */

require_once 'db.php';       // Database connection
require_once 'header.php';   // Common header

// Check if user is logged in 
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit; // Stops the rest of the page from loading
}

// The same as we have been doing in all the other pages with getting the ID
$employeeId = $_GET['employee_id'] ?? 0;
$employeeName = '';

// If filtering, get the employee's name for the heading
if ($employeeId) {
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE employee_id = ?"); // preparing the statement
    $stmt->execute([$employeeId]); // assigning the info to a superglobal
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeName = $employee ? $employee['name'] : '';
}

// Handle status updates (approve/deny)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['status'])) {
    $requestId = $_POST['request_id'];
    $status = $_POST['status'];
    
    // Update the leave request status
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $requestId]);
    
    // If approved, we need to mark them absent in attendance
    if ($status === 'Approved') {
        // Get the leave details first
        $leaveStmt = $pdo->prepare("SELECT employee_id, date FROM leave_requests WHERE id = ?"); // preparing the variable
        $leaveStmt->execute([$requestId]); // assigning it
        $leave = $leaveStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($leave) {
            // Check if attendance record already exists for this date, i had to add this after because i noticed repetitive data when testing the code
            $attendanceCheck = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
            $attendanceCheck->execute([$leave['employee_id'], $leave['date']]);
            
            // Only inserts if no record exists
            if ($attendanceCheck->rowCount() === 0) {
                $insertAttendance = $pdo->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, 'Absent')"); // prepare the superglobal as we have been doing all the time first
                $insertAttendance->execute([$leave['employee_id'], $leave['date']]); // and here we are assigning it to the variable
            }
        }
    }
}

// checks if someone subitted a leave form and collects the form data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
    $employeeId = $_POST['employee_id'];
    $date = $_POST['date'];
    $reason = trim($_POST['reason']);
    
    // Basic validation, user must fill all the fields
    if (empty($employeeId) || empty($date) || empty($reason)) {
        $error = "Please fill in all required fields";
    } else {
        // Check for existing leave request on this date, you cant submit leave for the same date
        $checkStmt = $pdo->prepare("SELECT id FROM leave_requests WHERE employee_id = ? AND date = ?");
        $checkStmt->execute([$employeeId, $date]);
        
        if ($checkStmt->rowCount() > 0) {
            $error = "This employee already has a leave request for {$date}"; // handling for the duplicates
        } else {
            // Insert the new leave request if the date is not duplicated
            $insertStmt = $pdo->prepare("
                INSERT INTO leave_requests 
                (employee_id, date, reason, status) 
                VALUES (?, ?, ?, 'Pending')
            ");
            $insertStmt->execute([$employeeId, $date, $reason]); // assigns ti the insert statement superglobal we are calling later 
        }
    }
}

// Build our query based on whether we're filtering
if ($employeeId) {
    // Filtered view for one employee, first we check for the pending leave requests so they are at the top
    $sql = "
        SELECT l.*, e.name 
        FROM leave_requests l 
        JOIN employees e ON l.employee_id = e.employee_id 
        WHERE l.employee_id = ? 
        ORDER BY l.status = 'Pending' DESC, l.date DESC
    ";
    $stmt = $pdo->prepare($sql); // prepating the statement
    $stmt->execute([$employeeId]); // assigning it
} else {
    // Full view of all leave requests
    $sql = "
        SELECT l.*, e.name 
        FROM leave_requests l 
        JOIN employees e ON l.employee_id = e.employee_id 
        ORDER BY l.status = 'Pending' DESC, l.date DESC
    ";
    $stmt = $pdo->query($sql);
}

$leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all employees for the dropdown
$employees = $pdo->query("SELECT employee_id, name FROM employees ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Main page content 
    This is all the bootrstrap and where we are calling all of the functions we just created to display the data
-->
<div class="container-fluid">
    <h2 class="mb-4">
        <i class="bi bi-calendar-event me-2"></i>
        Leave Requests <?= $employeeName ? "for {$employeeName}" : '' ?>
    </h2>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="row mt-4">
        <!-- View All button when filtered -->
        <?php if ($employeeId): ?>
            <div class="col-12 mb-3">
                <a href="leave.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i> View All Requests
                </a>
            </div>
        <?php endif; ?>
        
        <div class="col-md-12">
            <!-- New Leave Request Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-plus-circle me-2"></i>
                        Submit New Leave Request
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <!-- Employee Select -->
                            <div class="col-md-4">
                                <label for="employee_id" class="form-label">Employee*</label>
                                <select class="form-select" id="employee_id" name="employee_id" required 
                                    <?= $employeeId ? 'disabled' : '' ?>>
                                    <?php if ($employeeId): ?>
                                        <?php 
                                        $selectedEmployee = $pdo->prepare("SELECT employee_id, name FROM employees WHERE employee_id = ?");
                                        $selectedEmployee->execute([$employeeId]);
                                        $employee = $selectedEmployee->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <option value="<?= $employee['employee_id'] ?>">
                                            <?= htmlspecialchars($employee['name']) ?>
                                        </option>
                                        <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                    <?php else: ?>
                                        <option value="">Select Employee</option>
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?= $emp['employee_id'] ?>" 
                                                <?= isset($_POST['employee_id']) && $_POST['employee_id'] == $emp['employee_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($emp['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <!-- Date Input -->
                            <div class="col-md-3">
                                <label for="date" class="form-label">Date*</label>
                                <input type="date" class="form-control" id="date" name="date" required 
                                    value="<?= $_POST['date'] ?? '' ?>" min="<?= date('Y-m-d') ?>">
                            </div>
                            
                            <!-- Reason Input -->
                            <div class="col-md-5">
                                <label for="reason" class="form-label">Reason*</label>
                                <input type="text" class="form-control" id="reason" name="reason" required 
                                    value="<?= $_POST['reason'] ?? '' ?>" placeholder="e.g., Vacation, Sick Leave, Family Emergency">
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="col-12">
                                <button type="submit" name="submit_leave" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i> Submit Request
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Leave Requests Table Card -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-check me-2"></i>
                        Leave Requests
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <?php if (!$employeeId): ?>
                                        <th><i class="bi bi-person me-2"></i>Employee</th>
                                    <?php endif; ?>
                                    <th><i class="bi bi-calendar me-2"></i>Date</th>
                                    <th><i class="bi bi-chat-square-text me-2"></i>Reason</th>
                                    <th><i class="bi bi-info-circle me-2"></i>Status</th>
                                    <th><i class="bi bi-gear me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaveRequests)): ?>
                                    <tr>
                                        <td colspan="<?= $employeeId ? 4 : 5 ?>" class="text-center py-4 text-muted">
                                            <i class="bi bi-calendar-x display-6 d-block mb-2"></i>
                                            No leave requests found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($leaveRequests as $request): ?>
                                        <tr>
                                            <?php if (!$employeeId): ?>
                                                <td><?= htmlspecialchars($request['name']) ?></td>
                                            <?php endif; ?>
                                            <td><?= date('D, M j, Y', strtotime($request['date'])) ?></td>
                                            <td><?= htmlspecialchars($request['reason']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $request['status'] == 'Approved' ? 'success' : 
                                                    ($request['status'] == 'Denied' ? 'danger' : 'warning') 
                                                ?>">
                                                    <?= $request['status'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($request['status'] == 'Pending'): ?>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <form method="POST" class="me-1">
                                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                            <button type="submit" name="status" value="Approved" 
                                                                class="btn btn-success" title="Approve">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST">
                                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                            <button type="submit" name="status" value="Denied" 
                                                                class="btn btn-danger" title="Deny">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Processed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
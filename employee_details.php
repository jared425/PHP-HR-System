<?php
/*
 * Employee Details Page
 * Shows the wanted info about a single employee when you click to view them 
 */

// First we need our standard includes
require_once 'db.php';       // Database connection
require_once 'header.php';   // Common header

// Always have to check if the user is logged in first
if (!isset($_SESSION['loggedin'])) {
    // This sends them back to the login page if they're not logged in
    header('Location: index.php');
    exit; // Stops the rest of the page from loading 
}

// Get the employee ID from URL to retrieve that specific persons details we want 
// Using null coalescing operator to prevent errors
// This just retrieves the ID from the URL
$employeeId = $_GET['id'] ?? 0;


// This gets the employee's basic info

// This creates a reusable statement for safety and for ease later on
$stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
// This binds the empoyee ID to the placeholder
$stmt->execute([$employeeId]);
// This fetches the result and attaches it to the superglobal variable $employee
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// If no employee found, show error and and then exit
if (!$employee) {
    echo "<div class='alert alert-danger'>Whoops! Couldn't find that employee</div>";
    require_once 'footer.php';
    exit;
}

// This just gets the last 5 attendance records of the matching employee
$attendanceStmt = $pdo->prepare("
    SELECT * FROM attendance 
    WHERE employee_id = ? 
    ORDER BY date DESC 
    LIMIT 5
");
// This binds the employee ID to the attendanceStmt
$attendanceStmt->execute([$employeeId]);

// Get all leave requests for this employee
// First we had to create the statement with a placeholder, we are preparing the statement first
$leaveStmt = $pdo->prepare(" 
    SELECT * FROM leave_requests 
    WHERE employee_id = ? 
    ORDER BY date DESC
");
// and then here we are taking the employee ID from above and attaching it to the leaveStmt superglobal variable
$leaveStmt->execute([$employeeId]);

// Getting the payroll data
// again we first had to prepare it
$payrollStmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ?");
// then we attach it to the superglobal we just made to show the payroll statement
$payrollStmt->execute([$employeeId]);
// here we are fetching the associated data by using the (PDO::FETCH_ASSOC)
$payroll = $payrollStmt->fetch(PDO::FETCH_ASSOC);
?>

<!-- Page content starts here -->
<!-- All the below code is just basic bootstrap and we are calling all of the variables we have just created above -->
<!-- It looks long but its just standard divs and bootstrap styling according to all the cards we want to display on the page -->
<h2>
    <i class="bi bi-person-badge me-2"></i>
    Employee Details
    <small class="text-muted">- <?php echo htmlspecialchars($employee['name']); ?></small>
</h2>

<!-- Main info cards row -->
<div class="row mt-4">
    <!-- Personal Info Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title">
                    <i class="bi bi-person-lines-fill me-2"></i>
                    Personal Information
                </h5>
            </div>
            <div class="card-body">
                <!-- All the personal details -->
                <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['name']); ?></p>
                <p><strong>Position:</strong> <?php echo htmlspecialchars($employee['position']); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['department']); ?></p>
                <p><strong>Salary:</strong> R<?php echo number_format($employee['salary'], 2); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($employee['contact']); ?></p>
                <p><strong>Employment History:</strong><br><?php echo htmlspecialchars($employee['employment_history']); ?></p>
                
            </div>
        </div>
    </div>
    
    <!-- Attendance Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title">
                    <i class="bi bi-calendar-check me-2"></i>
                    Recent Attendance
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $attendance['date']; ?></td>
                                <td class="<?php echo $attendance['status'] == 'Present' ? 'text-success fw-bold' : 'text-danger'; ?>">
                                    <i class="bi bi-<?php echo $attendance['status'] == 'Present' ? 'check-circle' : 'x-circle'; ?>"></i>
                                    <?php echo $attendance['status']; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="attendance.php?employee_id=<?php echo $employeeId; ?>" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-eye"></i> View All Attendance
                </a>
            </div>
        </div>
    </div>
    
    <!-- Leave Requests Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title">
                    <i class="bi bi-calendar-event me-2"></i>
                    Leave Requests
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($leave = $leaveStmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $leave['date']; ?></td>
                                <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $leave['status'] == 'Approved' ? 'success' : 
                                        ($leave['status'] == 'Denied' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo $leave['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="leave.php?employee_id=<?php echo $employeeId; ?>" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-eye"></i> View All Leave
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Payroll Section - Only shows if payroll data exists -->
<?php if ($payroll): ?>
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title">
                    <i class="bi bi-cash-stack me-2"></i>
                    Payroll Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Hours Worked:</strong> <?php echo $payroll['hours_worked']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Leave Deductions:</strong> <?php echo $payroll['leave_deductions']; ?> days</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Final Salary:</strong> R<?php echo number_format($payroll['final_salary'], 2); ?></p>
                    </div>
                    <div class="col-md-3">
                        <a href="payroll.php?employee_id=<?php echo $employeeId; ?>" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-file-earmark-text"></i> Payroll Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Back button at bottom -->
<div class="mt-4">
    <a href="employees.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Employees
    </a>
</div>

<!-- Standard footer -->
<?php require_once 'footer.php'; ?>
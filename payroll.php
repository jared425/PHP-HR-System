<?php
/*
 * Payroll Management System
 * Handles employee payroll calculations and reporting
 */

require_once 'db.php';       // Database connection
require_once 'header.php';   // Common header

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit; // stops the page from continuing to load 
}

// Check if we're filtering by a specific employee,  just as we have been doing in all the previous files
$employeeId = $_GET['employee_id'] ?? 0;
$employeeName = '';

// If filtering, get the employee's name for the heading
// and all the rest of the code like the preparing and assigning we have been doing in all the other pages
if ($employeeId) {
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeName = $employee ? $employee['name'] : '';
}

/**
 * Calculates payroll data for employees
 * 
 * @param PDO $pdo Database connection
 * @param int $employeeId Optional employee ID to filter
 * @return array Processed payroll data
 */
function calculatePayroll($pdo, $employeeId = 0) {
    // Build query based on whether we're filtering
    if ($employeeId) {
        $sql = "SELECT p.*, e.name, e.salary 
                FROM payroll p
                JOIN employees e ON p.employee_id = e.employee_id
                WHERE p.employee_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employeeId]);
    } else {
        $sql = "SELECT p.*, e.name, e.salary 
                FROM payroll p
                JOIN employees e ON p.employee_id = e.employee_id
                ORDER BY e.name";
        $stmt = $pdo->query($sql);
    }

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Calculate all the payroll values we need
        $hoursWorked = floatval($row['hours_worked']);
        
        // Prevent division by zero - this caused issues last month!
        $hourlyRate = ($hoursWorked > 0) ? ($row['final_salary'] / $hoursWorked) : 0;
        
        // Convert leave days to hours (8 hours per day)
        $leaveHours = $row['leave_deductions'] * 8;
        $deductionAmount = $hourlyRate * $leaveHours;
        $netSalary = $row['final_salary'] - $deductionAmount;

        $results[] = [
            'employeeId' => $row['employee_id'],
            'name' => $row['name'],
            'hoursWorked' => $hoursWorked,
            'leaveDeductions' => $row['leave_deductions'],
            'grossSalary' => $row['salary'],
            'hourlyRate' => round($hourlyRate, 2),
            'leaveHours' => $leaveHours,
            'deductionAmount' => round($deductionAmount, 2),
            'netSalary' => round($netSalary, 2)
        ];
    }
    return $results;
}

// Get the payroll data we'll display
$payrollData = calculatePayroll($pdo, $employeeId);
?>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="bi bi-cash-stack me-2"></i>
        Payroll Management <?= $employeeName ? "for {$employeeName}" : '' ?>
    </h2>

    <div class="row mt-4">
        <!-- View All button when filtered -->
        <?php if ($employeeId): ?>
            <div class="col-12 mb-3">
                <a href="payroll.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i> View All Payroll
                </a>
            </div>
        <?php endif; ?>
        
        <div class="col-md-12">
            <!-- Payroll Summary Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        Payroll Summary
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payrollData)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            No payroll data found
                        </div>
                    <?php else: ?>
                        <!-- Totals Row -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-muted">Total Employees</h6>
                                        <h3><?= count($payrollData) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-muted">Total Payroll</h6>
                                        <h3>R<?= number_format(array_sum(array_column($payrollData, 'netSalary')), 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-muted">Total Deductions</h6>
                                        <h3>R<?= number_format(array_sum(array_column($payrollData, 'deductionAmount')), 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payroll Details Card -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table me-2"></i>
                        Payroll Details
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
                                    <th><i class="bi bi-clock me-2"></i>Hours</th>
                                    <th><i class="bi bi-calendar-x me-2"></i>Leave Days</th>
                                    <th><i class="bi bi-clock-history me-2"></i>Hourly Rate</th>
                                    <th><i class="bi bi-cash me-2"></i>Gross Salary</th>
                                    <th><i class="bi bi-calculator me-2"></i>Deductions</th>
                                    <th><i class="bi bi-wallet2 me-2"></i>Net Salary</th>
                                    <th><i class="bi bi-gear me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payrollData)): ?>
                                    <tr>
                                        <td colspan="<?= $employeeId ? 7 : 8 ?>" class="text-center py-4 text-muted">
                                            <i class="bi bi-cash-coin display-6 d-block mb-2"></i>
                                            No payroll records found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payrollData as $payroll): ?>
                                        <tr>
                                            <?php if (!$employeeId): ?>
                                                <td><?= htmlspecialchars($payroll['name']) ?></td>
                                            <?php endif; ?>
                                            <td><?= $payroll['hoursWorked'] ?></td>
                                            <td><?= $payroll['leaveDeductions'] ?> days</td>
                                            <td>R<?= number_format($payroll['hourlyRate'], 2) ?></td>
                                            <td>R<?= number_format($payroll['grossSalary'], 2) ?></td>
                                            <td class="text-danger">- R<?= number_format($payroll['deductionAmount'], 2) ?></td>
                                            <td class="fw-bold">R<?= number_format($payroll['netSalary'], 2) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="generate_payslip.php?employee_id=<?= $payroll['employeeId'] ?>" 
                                                       class="btn btn-primary" title="Download Payslip">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                    <a href="payroll.php?employee_id=<?= $payroll['employeeId'] ?>" 
                                                       class="btn btn-info" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </div>
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
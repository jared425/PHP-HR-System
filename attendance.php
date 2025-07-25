<?php
// Attendance tracking system

// First we need our database connection and header
require_once 'db.php';  // This connects this file to the database
require_once 'header.php'; // Standard header for all pages

// Check if someone is actually logged in before showing this page
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, send them to the login page
    header('Location: index.php');
    exit; // This code doesn't allow the rest of the page to load incase someone tries to access the page by entering the URL 
}

// Check if we are filtering by a specific employee
// Using null coalescing operator to prevent index errors, an external source helped me with ths, I was getting errors before this code
$employeeId = $_GET['employee_id'] ?? 0;
$employeeName = ''; // We'll fill this in if we have an ID

// If we have an employee ID, we get there name and then use it as a heading
if ($employeeId) {
    // we used $pdo for 'safety' purposes 
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Only set the name if we actually found the employee
    if ($employee) {
        $employeeName = $employee['name'];
    }
}

// We're building a query based on whether we're filtering or not
// An outside source helped me to perfect this, it was very confusing
if ($employeeId) {
    // Filtered view for one employee
    $sql = "SELECT a.*, e.name 
            FROM attendance a 
            JOIN employees e ON a.employee_id = e.employee_id 
            WHERE a.employee_id = ? 
            ORDER BY a.date DESC"; // Decided to sort it in ascending
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employeeId]);
} else {
    // Default view showing recent attendance for all employees
    $sql = "SELECT a.*, e.name 
            FROM attendance a 
            JOIN employees e ON a.employee_id = e.employee_id 
            ORDER BY a.date DESC, e.name 
            LIMIT 50"; // Only show 50 most recent records
    
    $stmt = $pdo->query($sql); // No parameters needed here
}

// Get all our records
$attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- Start of the actual HTML content -->
<h2>Attendance Tracking <?php echo $employeeName ? "for $employeeName" : ''; ?></h2>

<div class="row mt-4">
    <div class="col-md-12">
        <!-- Show 'View All' button only when filtered -->
        <?php if ($employeeId): ?>
            <a href="attendance.php" class="btn btn-secondary mb-3">
                <i class="fas fa-users"></i> View All Attendance
            </a>
        <?php endif; ?>
        
        <!-- Main card for the table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <!-- Only show employee column if we're not filtered -->
                                <?php if (!$employeeId): ?>
                                    <th>Employee</th>
                                <?php endif; ?>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Day</th> <!-- Added day of week for better readability -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loop through each attendance record -->
                            <?php foreach ($attendanceRecords as $record): ?>
                                <tr>
                                    <!-- Again, only show name if viewing all -->
                                    <?php if (!$employeeId): ?>
                                        <td><?php echo htmlspecialchars($record['name']); ?></td>
                                    <?php endif; ?>
                                    
                                    <td><?php echo $record['date']; ?></td>
                                    
                                    <!-- Color-code present/absent status -->
                                    <td class="<?php echo $record['status'] == 'Present' ? 'attendance-present' : 'attendance-absent'; ?>">
                                        <?php echo $record['status']; ?>
                                    </td>
                                    
                                    <!-- Convert date to day name -->
                                    <td><?php echo date('l', strtotime($record['date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Standard footer -->
<?php require_once 'footer.php'; ?>
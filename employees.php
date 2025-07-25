<?php
/*
 * Employee Management System
 * This is the bulk of the entrire website where we are doing most of the functionality
 * This code is where all CRUD takes place and we can edit, create and delete employees from the database
 * We also added the reviews to this page instead of having a whole extra web page just to view and write reviews
 */

// Get our required files
require_once 'db.php';// Database connection setup
require_once 'header.php';// Common header

// Security first - check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    //Redirect to login page if not logged in
    header('Location: index.php');
    exit; // Stops the rest of the page from loading
}

// Handle form submissions, this code very long but it's just standards PHP practices
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Adding a new employee
    // This just identifies we are adding a new employee
    if (isset($_POST['add_employee'])) {
        // Validates the input data
        // but we first needed to prepare the variables
        $name = trim($_POST['name']); // Assigning the name and removing extra whitespace
        $position = trim($_POST['position']); // Assigning the position and removing extra whitespace
        $department = trim($_POST['department']); // Assigning the department and removing extra whitespace
        $salary = floatval($_POST['salary']); // Assigning the salary and converting it into a float
        $employmentHistory = trim($_POST['employment_history']); // Assigning the history and removing extra whitespace
        $contact = trim($_POST['contact']); // Assigning the contact and removing extra whitespace

        // Validate all required fields, makes sure they enter data for each variable since we need it for the sql tables
        $errors = [];
        if (empty($name)) $errors[] = "Please enter the employee's name";
        if (empty($position)) $errors[] = "Position is required";
        if (empty($department)) $errors[] = "Please select a department";
        if (empty($salary) || $salary <= 0) $errors[] = "Please enter a valid salary amount";
        if (empty($contact) || !filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "A valid email address is required";
        }

        // If no errors, then continue with the database operations
        // This code adds a new empployee and also creates their first payroll record this will add to the payroll page upon submitting the new employee
        if (empty($errors)) {
            try {
                $pdo->beginTransaction(); // This adds multiple operations into one unit, so everything must be a success for it to work 

                // Insert new employee record
                // Just preapres the statement and then below adds it
                $stmt = $pdo->prepare("
                    INSERT INTO employees 
                    (name, position, department, salary, employment_history, contact) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                // Here is where it adds it
                $stmt->execute([
                    $name,
                    $position,
                    $department,
                    $salary,
                    $employmentHistory,
                    $contact
                ]);
                
                // Get the new employee ID
                // Gets the latest number from the last employee
                $employeeId = $pdo->lastInsertId();

                // Default payroll values (160 hours = 8hrs/day * 20 days)
                // This is just the assumption we are using, since we are entering the employees salary. And since they are new we have no records of leave or absent days 
                $hoursWorked = 160;
                $leaveDeductions = 0;

                // Create payroll record for new employee, here we are preparing first
                $payrollStmt = $pdo->prepare("
                    INSERT INTO payroll 
                    (employee_id, hours_worked, leave_deductions, final_salary) 
                    VALUES (?, ?, ?, ?)
                ");
                // And then here we are attaching
                $payrollStmt->execute([$employeeId, $hoursWorked, $leaveDeductions, $salary]);

                // This completes/successfully ends the transaction
                $pdo->commit();

                // Success message
                $_SESSION['success'] = "New employee added successfully!";

                // Error handling for anything that doesnt happen correctly above
            } catch (PDOException $e) {
                $pdo->rollBack(); // This just undoes all of the changes so it doesnt add the data to the sql tables even if it's incorrect
                $errors[] = "Oops! Something went wrong: " . $e->getMessage(); // this is just the error message
            }
        }
    } 
    // Updating an existing employee
    // again it is the same as adding a new employee, the same process, we first preapre and then attach
    elseif (isset($_POST['update_employee'])) {
        $employeeId = $_POST['employee_id'];
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $department = trim($_POST['department']);
        $salary = floatval($_POST['salary']);
        $employmentHistory = trim($_POST['employment_history']);
        $contact = trim($_POST['contact']);

        // Validate all required fields
        // same validation as the adding employee
        $errors = [];
        if (empty($name)) $errors[] = "Please enter the employee's name";
        if (empty($position)) $errors[] = "Position is required";
        if (empty($department)) $errors[] = "Please select a department";
        if (empty($salary) || $salary <= 0) $errors[] = "Please enter a valid salary amount";
        if (empty($contact) || !filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "A valid email address is required";
        }

        // Also the same as the adding employee, same process, we first begin the transaction then we prepare our variables and then we assign
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Update employee record
                $stmt = $pdo->prepare("
                    UPDATE employees SET 
                    name = ?, 
                    position = ?, 
                    department = ?, 
                    salary = ?, 
                    employment_history = ?, 
                    contact = ?
                    WHERE employee_id = ?
                ");
                $stmt->execute([
                    $name,
                    $position,
                    $department,
                    $salary,
                    $employmentHistory,
                    $contact,
                    $employeeId
                ]);

                // Update payroll to match new salary
                // preparing first
                $payrollStmt = $pdo->prepare("
                    UPDATE payroll 
                    SET final_salary = ? 
                    WHERE employee_id = ?
                ");
                // assigning it 
                $payrollStmt->execute([$salary, $employeeId]);

                $pdo->commit(); // ending the transaction

                $_SESSION['success'] = "Employee updated successfully!"; // success message
            } catch (PDOException $e) {
                $pdo->rollBack(); // undoes entered information if it fails
                $errors[] = "Error updating employee: " . $e->getMessage(); // error message
            }
        }
    }
    // Adding a performance review
    elseif (isset($_POST['add_review'])) {
        $review_employee_id = $_POST['review_employee_id']; // assigning the review ID 
        $review_text = trim($_POST['review_text']); // assigning the text 
        
        if (!empty($review_text)) {
            $stmt = $pdo->prepare("
                INSERT INTO employee_reviews 
                (employee_id, review_text) 
                VALUES (?, ?)
            "); // again here we prepared the statement first

            // here we are assigning 
            $stmt->execute([$review_employee_id, $review_text]);

            // success message
            $_SESSION['success'] = "Review added successfully!";

        } else {
            $errors[] = "Review text cannot be empty"; // error message
        }
    }
}

// Handle employee deletion
if (isset($_GET['delete'])) {
    $employeeId = $_GET['delete']; // identifies when you want to delete and then gets the ID from the URl
    
    try {
        $pdo->beginTransaction(); // begins the transaction
        
        // First delete payroll record (due to foreign key constraint)
        $pdo->prepare("DELETE FROM payroll WHERE employee_id = ?")->execute([$employeeId]);
        
        // Then delete the employee
        $pdo->prepare("DELETE FROM employees WHERE employee_id = ?")->execute([$employeeId]);
        
        $pdo->commit(); // ends the transaction
        
        $_SESSION['success'] = "Employee record deleted"; // success message

    } catch (PDOException $e) {
        $pdo->rollBack(); // undoes incase of error
        $errors[] = "Couldn't delete employee: " . $e->getMessage(); // error message
    }
}

// Handle review deletion
if (isset($_GET['delete_review'])) {
    $review_id = $_GET['delete_review']; // indentifes you want to delete a review
    $stmt = $pdo->prepare("DELETE FROM employee_reviews WHERE review_id = ?"); // prepares the variable first
    $stmt->execute([$review_id]); // assigns to the variable
    $_SESSION['success'] = "Review deleted"; // success message
}

// Get all employees for the table
$stmt = $pdo->query("SELECT * FROM employees ORDER BY employee_id ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC); // fetches all the data with the fetchAll
?>

<!-- page content -->
<!-- This is just the visuals for the page, I try to make it shorter but i couldnt this is all styling for all the different functionality the page has -->
<!-- Here is where we are calling all the variables we just created above -->
<div class="container-fluid">
    <h2 class="mb-4">
        <i class="bi bi-people-fill me-2"></i>
        Employee Management
        <small class="text-muted">- Company Staff Directory</small>
    </h2>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Add Employee Button -->
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
        <i class="bi bi-person-plus me-2"></i> Add New Employee
    </button>

    <!-- Employees Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Salary</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-people-slash display-6 d-block mb-2"></i>
                                    No employees found in the system
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?= $employee['employee_id'] ?></td>
                                    <td><?= htmlspecialchars($employee['name']) ?></td>
                                    <td><?= htmlspecialchars($employee['position']) ?></td>
                                    <td><?= htmlspecialchars($employee['department']) ?></td>
                                    <td>R<?= number_format($employee['salary'], 2) ?></td>
                                    <td><?= htmlspecialchars($employee['contact']) ?></td>
                                    <td>
                                        <!-- View Button -->
                                        <a href="employee_details.php?id=<?= $employee['employee_id'] ?>" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <!-- Edit Button -->
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editEmployeeModal<?= $employee['employee_id'] ?>"
                                                title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <!-- Delete Button -->
                                        <a href="employees.php?delete=<?= $employee['employee_id'] ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure? This cannot be undone!')"
                                           title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>

                                        <!-- Reviews Button -->
                                        <button type="button" class="btn btn-sm btn-secondary" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#reviewsModal<?= $employee['employee_id'] ?>" 
                                                title="Performance Reviews">
                                            <i class="bi bi-chat-dots"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Edit Employee Modal -->
                                <div class="modal fade" id="editEmployeeModal<?= $employee['employee_id'] ?>" tabindex="-1" 
                                     aria-labelledby="editEmployeeModalLabel<?= $employee['employee_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning text-dark">
                                                <h5 class="modal-title" id="editEmployeeModalLabel<?= $employee['employee_id'] ?>">
                                                    <i class="bi bi-pencil-square me-2"></i>
                                                    Edit: <?= htmlspecialchars($employee['name']) ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="employee_id" value="<?= $employee['employee_id'] ?>">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="name<?= $employee['employee_id'] ?>" class="form-label">Full Name*</label>
                                                                <input type="text" class="form-control" id="name<?= $employee['employee_id'] ?>" 
                                                                       name="name" value="<?= htmlspecialchars($employee['name']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="position<?= $employee['employee_id'] ?>" class="form-label">Position*</label>
                                                                <input type="text" class="form-control" id="position<?= $employee['employee_id'] ?>" 
                                                                       name="position" value="<?= htmlspecialchars($employee['position']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="department<?= $employee['employee_id'] ?>" class="form-label">Department*</label>
                                                                <select class="form-select" id="department<?= $employee['employee_id'] ?>" name="department" required>
                                                                    <option value="">Select Department</option>
                                                                    <?php
                                                                    $departments = ['Development', 'HR', 'QA', 'Sales', 'Marketing', 'Design', 'IT', 'Finance', 'Support'];
                                                                    foreach ($departments as $dept) {
                                                                        $selected = ($dept == $employee['department']) ? 'selected' : '';
                                                                        echo "<option value=\"$dept\" $selected>$dept</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="salary<?= $employee['employee_id'] ?>" class="form-label">Monthly Salary (R)*</label>
                                                                <input type="number" class="form-control" id="salary<?= $employee['employee_id'] ?>" 
                                                                       name="salary" value="<?= $employee['salary'] ?>" min="0" step="0.01" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="contact<?= $employee['employee_id'] ?>" class="form-label">Email*</label>
                                                                <input type="email" class="form-control" id="contact<?= $employee['employee_id'] ?>" 
                                                                       name="contact" value="<?= htmlspecialchars($employee['contact']) ?>" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="employment_history<?= $employee['employee_id'] ?>" class="form-label">Employment History</label>
                                                        <textarea class="form-control" id="employment_history<?= $employee['employee_id'] ?>" 
                                                                  name="employment_history" rows="3"><?= htmlspecialchars($employee['employment_history']) ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="bi bi-x-circle me-2"></i>Cancel
                                                    </button>
                                                    <button type="submit" name="update_employee" class="btn btn-primary">
                                                        <i class="bi bi-save me-2"></i>Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reviews Modal -->
                                <div class="modal fade" id="reviewsModal<?= $employee['employee_id'] ?>" tabindex="-1"
                                    aria-labelledby="reviewsModalLabel<?= $employee['employee_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-secondary text-white">
                                                <h5 class="modal-title" id="reviewsModalLabel<?= $employee['employee_id'] ?>">
                                                    <i class="bi bi-chat-square-text me-2"></i>
                                                    Reviews for <?= htmlspecialchars($employee['name']) ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- Existing Reviews -->
                                                <?php
                                                $reviewStmt = $pdo->prepare("
                                                    SELECT * FROM employee_reviews 
                                                    WHERE employee_id = ? 
                                                    ORDER BY created_at DESC
                                                ");
                                                $reviewStmt->execute([$employee['employee_id']]);
                                                $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
                                                ?>
                                                
                                                <?php if ($reviews): ?>
                                                    <ul class="list-group mb-3">
                                                        <?php foreach ($reviews as $review): ?>
                                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                                <div class="ms-2 me-auto">
                                                                    <div><?= nl2br(htmlspecialchars($review['review_text'])) ?></div>
                                                                    <small class="text-muted">
                                                                        <i class="bi bi-clock"></i> <?= $review['created_at'] ?>
                                                                    </small>
                                                                </div>
                                                                <a href="employees.php?delete_review=<?= $review['review_id'] ?>"
                                                                   class="btn btn-sm btn-outline-danger ms-2"
                                                                   onclick="return confirm('Delete this review?')">
                                                                    <i class="bi bi-trash"></i>
                                                                </a>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <div class="alert alert-info">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        No reviews yet for this employee
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Add New Review Form -->
                                                <form method="POST">
                                                    <input type="hidden" name="review_employee_id" value="<?= $employee['employee_id'] ?>">
                                                    <div class="mb-3">
                                                        <label for="review_text<?= $employee['employee_id'] ?>" class="form-label">
                                                            <i class="bi bi-pencil-square me-2"></i>Add New Review
                                                        </label>
                                                        <textarea class="form-control" id="review_text<?= $employee['employee_id'] ?>"
                                                                  name="review_text" rows="3" required></textarea>
                                                    </div>
                                                    <button type="submit" name="add_review" class="btn btn-primary">
                                                        <i class="bi bi-send me-2"></i>Submit Review
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addEmployeeModalLabel">
                    <i class="bi bi-person-plus me-2"></i>
                    Add New Employee
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name*</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="position" class="form-label">Position*</label>
                                <input type="text" class="form-control" id="position" name="position" required>
                            </div>
                            <div class="mb-3">
                                <label for="department" class="form-label">Department*</label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <?php
                                    $departments = ['Development', 'HR', 'QA', 'Sales', 'Marketing', 'Design', 'IT', 'Finance', 'Support'];
                                    foreach ($departments as $dept) {
                                        echo "<option value=\"$dept\">$dept</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="salary" class="form-label">Monthly Salary (R)*</label>
                                <input type="number" class="form-control" id="salary" name="salary" min="0" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="contact" class="form-label">Email*</label>
                                <input type="email" class="form-control" id="contact" name="contact" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="employment_history" class="form-label">Employment History</label>
                        <textarea class="form-control" id="employment_history" name="employment_history" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" name="add_employee" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Add Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
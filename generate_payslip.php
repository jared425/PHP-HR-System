<?php
/*
 * Payslip Generator
 * Creates PDF payslips for employees
 * Note: This uses dompdf for PDF generation
 */

// We need dompdf for PDF generation and our database connection
require_once 'dompdf/autoload.inc.php'; // Path to dompdf installation
require_once 'db.php'; // Database connection

// Use the dompdf classes we need
use Dompdf\Dompdf;
use Dompdf\Options;

// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit; // Stop if not logged in
}

// Get employee ID from URL - default to 0 if not provided
$employeeId = $_GET['employee_id'] ?? 0;

// Gets the employee's basic information first
// prepares the statement first
$employeeStmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
// then we assign 
$employeeStmt->execute([$employeeId]);
$employee = $employeeStmt->fetch(PDO::FETCH_ASSOC);

// Get the payroll data for this employee
// same as above, we preapre and then assign
$payrollStmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ?");
$payrollStmt->execute([$employeeId]);
$payroll = $payrollStmt->fetch(PDO::FETCH_ASSOC);

// error handling incase somehow the wrong ID gets fetched
if (!$employee || !$payroll) {
    die("Oops! Couldn't find employee or payroll data. Please check the ID.");
}

// Calculate all the payroll values we need
$hourlyRate = $payroll['final_salary'] / $payroll['hours_worked']; // Hourly rate calculation
$leaveHours = $payroll['leave_deductions'] * 8; // Convert leave days to hours (8hrs/day)
$deductionAmount = $hourlyRate * $leaveHours; // Total leave deduction
$netSalary = $payroll['final_salary'] - $deductionAmount; // Final take-home pay

// Calculate these variables before using them 
$month = date('F Y');
$payDate = date('Y-m-d');
$basicSalary = number_format($employee['salary'], 2);
$hourlyRateFormatted = number_format($hourlyRate, 2);
$deductionFormatted = number_format($deductionAmount, 2);
$netSalaryFormatted = number_format($netSalary, 2);

// HTML template for our payslip PDF
// Using heredoc syntax for better readability, this was a recommendation 
$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip - {$employee['name']}</title>
    <style>
        /* Basic styling for the payslip */
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            color: #333;
        }
        .payslip { 
            max-width: 800px; 
            margin: 0 auto; 
            border: 1px solid #ddd; 
            padding: 20px; 
            background-color: white;
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #2c3e50; 
            padding-bottom: 10px; 
        }
        .company { 
            font-size: 24px; 
            font-weight: bold; 
            color: #2c3e50; 
            margin-bottom: 5px;
        }
        .title { 
            font-size: 18px; 
            margin: 5px 0; 
            color: #7f8c8d; 
        }
        .details { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 20px; 
            flex-wrap: wrap;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
        }
        th, td { 
            padding: 10px; 
            border: 1px solid #ddd; 
            text-align: left; 
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold;
        }
        .total { 
            font-weight: bold; 
            background-color: #f9f9f9; 
        }
        .negative {
            color: #e74c3c;
        }
        .footer { 
            text-align: center; 
            margin-top: 30px; 
            font-size: 12px; 
            color: #95a5a6; 
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="payslip">
        <div class="header">
            <div class="company">ModernTech Solutions</div>
            <div class="title">PAYSLIP - {$month}</div>
        </div>
        
        <div class="details">
            <div>
                <p><strong>Employee ID:</strong> {$employee['employee_id']}</p>
                <p><strong>Name:</strong> {$employee['name']}</p>
                <p><strong>Position:</strong> {$employee['position']}</p>
            </div>
            <div>
                <p><strong>Department:</strong> {$employee['department']}</p>
                <p><strong>Pay Date:</strong> {$payDate}</p>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount (R)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td>{$basicSalary}</td>
                </tr>
                <tr>
                    <td>Hours Worked</td>
                    <td>{$payroll['hours_worked']}</td>
                </tr>
                <tr>
                    <td>Hourly Rate</td>
                    <td>{$hourlyRateFormatted}</td>
                </tr>
                <tr>
                    <td>Leave Deductions ({$payroll['leave_deductions']} days)</td>
                    <td class="negative">- {$deductionFormatted}</td>
                </tr>
                <tr class="total">
                    <td>Net Salary</td>
                    <td>{$netSalaryFormatted}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="footer">
            <p>This is a computer generated payslip and does not require a signature.</p>
            <p>ModernTech Solutions HR System | {$payDate}</p>
        </div>
    </div>
</body>
</html>
HTML;

// Configure dompdf settings, this was external help
$options = new Options();
$options->set('isRemoteEnabled', true); // Allow loading external resources
$options->set('isHtml5ParserEnabled', true); // Better HTML5 support
$options->set('defaultFont', 'Arial'); // Set default font

// Create and setup the PDF generator
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait'); // Standard A4 size, portrait orientation

// Render the PDF - this does the actual conversion
$dompdf->render();

// Create a filename for the PDF
$filename = 'payslip_' . $employee['employee_id'] . '_' . date('Y-m-d') . '.pdf';

// Output the PDF to the browser for download
$dompdf->stream($filename, [
    'Attachment' => true // Force download instead of displaying
]);

exit;
?>
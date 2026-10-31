<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app.php';
require_admin();

$result = db()->query(
    'SELECT name, email, course, year, department, city, status
     FROM students ORDER BY name'
);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="studenthub-students.csv"');
$output = fopen('php://output', 'w');
if ($output === false) {
    http_response_code(500);
    exit('Unable to create the CSV export.');
}
fputcsv($output, ['Name', 'Email', 'Course', 'Year', 'Department', 'City', 'Status']);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, array_values($row));
}
fclose($output);

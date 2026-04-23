<?php
date_default_timezone_set('Asia/Kolkata');

require_once 'config.php';


// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_signup.php");
    exit();
}

// Get format (pdf, csv, excel)
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Set filename
$filename = "full_admin_report_" . date('Y-m-d_H-i-s');

// Function to safely execute queries
function safeQuery($conn, $sql) {
    $result = $conn->query($sql);
    if ($result === false) {
        return false;
    }
    return $result;
}

// Function to get all data
function getAllReportData($conn) {
    $data = [];
    
    // System Overview
    $data['overview'] = [
        'title' => 'SYSTEM OVERVIEW',
        'headers' => ['Metric', 'Value'],
        'rows' => []
    ];
    
    // Safe count queries - using correct table names from your database
    $users_count = 0;
    $result = safeQuery($conn, "SELECT COUNT(*) as count FROM users");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $users_count = $row['count'];
    }
    
    $resources_count = 0;
    $result = safeQuery($conn, "SELECT COUNT(*) as count FROM Resources");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $resources_count = $row['count'];
    }
    
    $pending_count = 0;
    $result = safeQuery($conn, "SELECT COUNT(*) as count FROM Resources WHERE approval_status = 'pending'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $pending_count = $row['count'];
    }
    
    $approved_count = 0;
    $result = safeQuery($conn, "SELECT COUNT(*) as count FROM Resources WHERE approval_status = 'approved'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $approved_count = $row['count'];
    }
    
    $rejected_count = 0;
    $result = safeQuery($conn, "SELECT COUNT(*) as count FROM Resources WHERE approval_status = 'rejected'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rejected_count = $row['count'];
    }
    
    $ratings_count = 0;
    $result = safeQuery($conn, "SELECT COUNT(*) as count FROM Ratings");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $ratings_count = $row['count'];
    }
    
    $notes_count = 0;
    $result = safeQuery($conn, "SELECT COUNT(*) as count FROM Resources WHERE Resource_type = 'notes'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $notes_count = $row['count'];
    }
    
    $papers_count = 0;
    $result = safeQuery($conn, "SELECT COUNT(*) as count FROM Resources WHERE Resource_type = 'question_paper'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $papers_count = $row['count'];
    }
    
    $data['overview']['rows'] = [
        ['Total Users', $users_count],
        ['Total Resources', $resources_count],
        ['Pending Approval', $pending_count],
        ['Approved Resources', $approved_count],
        ['Rejected Resources', $rejected_count],
        ['Total Ratings', $ratings_count],
        ['Notes', $notes_count],
        ['Question Papers', $papers_count],
    ];
    
    // USERS DATA - Fixed column names
    $data['users'] = [
        'title' => 'USERS LIST',
        'headers' => ['ID', 'Full Name', 'Username', 'Email', 'Registration Date'],
        'rows' => []
    ];
    
    // Using correct column names from your users table
    $result = safeQuery($conn, "SELECT id, full_name, username, email, registration_date FROM users ORDER BY id ASC");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['users']['rows'][] = [
                $row['id'],
                $row['full_name'],
                $row['username'],
                $row['email'],
                $row['registration_date'] // Default date if created_at doesn't exist
            ];
        }
    } else {
        // Debug: Add error info
        $data['users']['rows'][] = ['No users found', '', '', '', ''];
    }
    
    // RESOURCES DATA
    $data['resources'] = [
        'title' => 'RESOURCES LIST',
        'headers' => ['ID', 'Title', 'Type', 'Course', 'Subject', 'University', 'Uploader', 'Status', 'Upload Date', 'File Type'],
        'rows' => []
    ];
    
    $result = safeQuery($conn, "
        SELECT r.*, u.username as uploader_name 
        FROM Resources r 
        LEFT JOIN users u ON r.User_id = u.id 
        ORDER BY r.Upload_date ASC
    ");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['resources']['rows'][] = [
                $row['Resource_id'],
                $row['Title'],
                $row['Resource_type'] == 'notes' ? 'Notes' : 'Question Paper',
                $row['Course_name'],
                $row['Subject_name'],
                isset($row['University_name']) && $row['University_name'] ? $row['University_name'] : 'N/A',
                isset($row['uploader_name']) ? $row['uploader_name'] : 'Unknown',
                ucfirst($row['approval_status']),
                date('Y-m-d', strtotime($row['Upload_date'])),
                strtoupper($row['File_type'])
            ];
        }
    }
    
    // RATINGS DATA
    $data['ratings'] = [
        'title' => 'RATINGS LIST',
        'headers' => ['ID', 'User', 'Resource', 'Rating', 'Review', 'Date'],
        'rows' => []
    ];
    
    $result = safeQuery($conn, "
        SELECT rt.*, u.username as user_name, res.Title as resource_title 
        FROM Ratings rt 
        LEFT JOIN users u ON rt.user_id = u.id 
        LEFT JOIN Resources res ON rt.resource_id = res.Resource_id 
        ORDER BY rt.rating_id ASC
    ");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['ratings']['rows'][] = [
                $row['rating_id'],
                isset($row['user_name']) ? $row['user_name'] : 'Unknown',
                isset($row['resource_title']) ? $row['resource_title'] : 'Unknown',
                $row['rating'],
                isset($row['review']) && $row['review'] ? $row['review'] : '',
                isset($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : 'N/A'
            ];
        }
    }
    
    // TOP CONTRIBUTORS
    $data['contributors'] = [
        'title' => 'TOP CONTRIBUTORS',
        'headers' => ['Rank', 'Full Name', 'Username', 'Total Uploads'],
        'rows' => []
    ];
    
    $result = safeQuery($conn, "
        SELECT 
            u.id,
            u.full_name,
            u.username,
            COUNT(r.Resource_id) as upload_count
        FROM users u
        LEFT JOIN Resources r ON u.id = r.User_id
        GROUP BY u.id
        ORDER BY upload_count DESC
        LIMIT 10
    ");
    if ($result && $result->num_rows > 0) {
        $rank = 1;
        while ($row = $result->fetch_assoc()) {
            $data['contributors']['rows'][] = [
                $rank++,
                $row['full_name'],
                $row['username'],
                $row['upload_count']
            ];
        }
    }
    
    // MONTHLY UPLOADS
    $data['monthly'] = [
        'title' => 'MONTHLY UPLOADS (Last 12 months)',
        'headers' => ['Month', 'Total Uploads', 'Notes', 'Question Papers'],
        'rows' => []
    ];
    
    $result = safeQuery($conn, "
        SELECT 
            DATE_FORMAT(Upload_date, '%Y-%m') as month,
            COUNT(*) as total,
            SUM(CASE WHEN Resource_type = 'notes' THEN 1 ELSE 0 END) as notes,
            SUM(CASE WHEN Resource_type = 'question_paper' THEN 1 ELSE 0 END) as papers
        FROM Resources 
        WHERE Upload_date IS NOT NULL
        GROUP BY DATE_FORMAT(Upload_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['monthly']['rows'][] = [
                $row['month'],
                $row['total'],
                $row['notes'],
                $row['papers']
            ];
        }
    }
    
    // FILE TYPE DISTRIBUTION
    $data['file_types'] = [
        'title' => 'FILE TYPE DISTRIBUTION',
        'headers' => ['File Type', 'Count', 'Percentage'],
        'rows' => []
    ];
    
    $total_resources = $resources_count;
    $result = safeQuery($conn, "
        SELECT File_type, COUNT(*) as count 
        FROM Resources 
        WHERE File_type IS NOT NULL
        GROUP BY File_type
    ");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $percentage = $total_resources > 0 ? round(($row['count'] / $total_resources) * 100, 2) : 0;
            $data['file_types']['rows'][] = [
                strtoupper($row['File_type']),
                $row['count'],
                $percentage . '%'
            ];
        }
    }
    
    // APPROVAL STATUS DISTRIBUTION
    $data['approval'] = [
        'title' => 'APPROVAL STATUS DISTRIBUTION',
        'headers' => ['Status', 'Count', 'Percentage'],
        'rows' => []
    ];
    
    $result = safeQuery($conn, "
        SELECT 
            approval_status,
            COUNT(*) as count
        FROM Resources 
        WHERE approval_status IS NOT NULL
        GROUP BY approval_status
    ");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $percentage = $total_resources > 0 ? round(($row['count'] / $total_resources) * 100, 2) : 0;
            $data['approval']['rows'][] = [
                ucfirst($row['approval_status']),
                $row['count'],
                $percentage . '%'
            ];
        }
    }
    
    // POPULAR COURSES
    $data['courses'] = [
        'title' => 'MOST POPULAR COURSES',
        'headers' => ['Course Name', 'Number of Resources'],
        'rows' => []
    ];
    
    $result = safeQuery($conn, "
        SELECT Course_name, COUNT(*) as count 
        FROM Resources 
        WHERE Course_name IS NOT NULL AND Course_name != ''
        GROUP BY Course_name 
        ORDER BY count DESC 
        LIMIT 10
    ");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['courses']['rows'][] = [
                $row['Course_name'],
                $row['count']
            ];
        }
    }
    
    // POPULAR SUBJECTS
    $data['subjects'] = [
        'title' => 'MOST POPULAR SUBJECTS',
        'headers' => ['Subject Name', 'Number of Resources'],
        'rows' => []
    ];
    
    $result = safeQuery($conn, "
        SELECT Subject_name, COUNT(*) as count 
        FROM Resources 
        WHERE Subject_name IS NOT NULL AND Subject_name != ''
        GROUP BY Subject_name 
        ORDER BY count DESC 
        LIMIT 10
    ");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['subjects']['rows'][] = [
                $row['Subject_name'],
                $row['count']
            ];
        }
    }
    
    return $data;
}

// Generate based on format
$all_data = getAllReportData($conn);

// // Generate CSV
// if ($format == 'csv') {
//     header('Content-Type: text/csv; charset=utf-8');
//     header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
//     $output = fopen('php://output', 'w');
//     fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
//     foreach ($all_data as $section) {
//         fputcsv($output, []);
//         fputcsv($output, [$section['title']]);
//         fputcsv($output, []);
//         fputcsv($output, $section['headers']);
        
//         foreach ($section['rows'] as $row) {
//             fputcsv($output, $row);
//         }
//     }
    
//     fclose($output);
// }
// Generate Excel
if ($format == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '<html><head><meta charset="UTF-8">';
    echo '<style>';
    echo 'body, td, th { font-family: "Calibri", "Arial", "Verdana", sans-serif; }';    
    echo '</style>';
    echo '</head><body>';
    echo '<h1>Full Admin Report</h1>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    
    foreach ($all_data as $section) {
        echo '<h2>' . htmlspecialchars($section['title']) . '</h2>';
        echo '<table border="1">';
        echo '<tr>';
        foreach ($section['headers'] as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        foreach ($section['rows'] as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }
    
    echo '</body></html>';
}
// Generate HTML/PDF
elseif ($format == 'pdf') {
    // Include TCPDF library
    require_once('TCPDF-main/tcpdf.php');
    
    // Clear any output buffers to prevent corruption
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    // Extend TCPDF class to create custom header/footer
    class MYPDF extends TCPDF {
        // Page header
        public function Header() {
            // Logo or title
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 10, 'NoteStation - Admin Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            $this->Ln(5);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, false, 'C', 0, '', 0, false, 'M', 'M');
            $this->Ln(10);
        }
        
        // Page footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }
    
    // Create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('NoteStation');
    $pdf->SetAuthor('NoteStation Admin');
    $pdf->SetTitle('Full Admin Report');
    $pdf->SetSubject('System Report');
    $pdf->SetKeywords('Report, Admin, NoteStation');
    
    // Set margins
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Build the report content
    $report_data = getAllReportData($conn);
    
    // Title
    $html = '<h1 style="color: #4a3f7a; text-align: center;">Full System Report</h1>';
    $html .= '<br>';
    
    // Loop through all sections
    foreach ($report_data as $section) {
        if (!empty($section['rows'])) {
            $html .= '<h2 style="color: #4a3f7a; background-color: #f0f0f0; padding: 5px;">' . htmlspecialchars($section['title']) . '</h2>';
            
            // Build table
            $html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            
            // Table header
            $html .= '<thead>';
            $html .= '<tr style="background-color: #4a3f7a; color: white;">';
            foreach ($section['headers'] as $header) {
                $html .= '<th style="padding: 8px;"><strong>' . htmlspecialchars($header) . '</strong></th>';
            }
            $html .= '</tr>';
            $html .= '</thead>';
            
            // Table body
            $html .= '<tbody>';
            foreach ($section['rows'] as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td style="padding: 6px;">' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table><br><br>';
        }
    }
    
    // Output HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Clear output buffer
    ob_clean();
    
    // Close and output PDF document (D = download, I = inline, F = save to file)
    $pdf->Output('full_admin_report_' . date('Y-m-d') . '.pdf', 'D');
    exit();
}

$conn->close();
?>
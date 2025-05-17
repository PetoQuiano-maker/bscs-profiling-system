<?php
include 'config.php';

// Add cache busting variable
$cacheBuster = time(); // For development
// or use this for production:
// $cacheBuster = '1.0.0'; 

// Set cache control headers with UTF-8 charset
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: public, max-age=31536000, immutable");
header("Pragma: cache");

// Get gender counts at the top of the file
function checkDuplicateName($conn, $first_name, $middle_name, $last_name, $extension_name) {
    $sql = "SELECT COUNT(*) as count FROM students WHERE 
            LOWER(first_name) = LOWER(?) AND 
            LOWER(COALESCE(middle_name, '')) = LOWER(COALESCE(?, '')) AND 
            LOWER(last_name) = LOWER(?) AND 
            LOWER(COALESCE(extension_name, '')) = LOWER(COALESCE(?, ''))";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $first_name, $middle_name, $last_name, $extension_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

function checkDuplicateEmail($conn, $email) {
    $sql = "SELECT COUNT(*) as count FROM students WHERE LOWER(email) = LOWER(?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

// Get gender counts
$male_count_sql = "SELECT COUNT(*) as count FROM students WHERE sex = 'Male'";
$female_count_sql = "SELECT COUNT(*) as count FROM students WHERE sex = 'Female'";

$male_result = $conn->query($male_count_sql);
$female_result = $conn->query($female_count_sql);

$male_count = $male_result->fetch_assoc()['count'];
$female_count = $female_result->fetch_assoc()['count'];

// Fetch all students for search functionality
$all_students_sql = "SELECT * FROM students";
$all_students_result = $conn->query($all_students_sql);
$all_students = [];
while ($row = $all_students_result->fetch_assoc()) {
    $all_students[] = $row;
}

// Set the number of students to display per page
$students_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Fixed unmatched parenthesis
$offset = ($page - 1) * $students_per_page;

// Get the total number of students
$total_students_sql = "SELECT COUNT(*) as total FROM students";
$total_students_result = $conn->query($total_students_sql);
$total_students_row = $total_students_result->fetch_assoc();
$total_students = $total_students_row['total'];

// Calculate the total number of pages
$total_pages = $total_students > 0 ? ceil($total_students / $students_per_page) : 1;

// Fetch students for the current page
$sql = "SELECT * FROM students LIMIT $students_per_page OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSCS Profiling System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v=5.3.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css?v=1.7.2">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=5.3.0"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js?v=2.5.1"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js?v=3.5.31"></script>
    <style>
        :root {
            --primary-color: #34495e;
            --secondary-color: #34495e;
            --accent-color: #3498db;
        }
        
        body {
            margin: 0;
            font-family: var(--bs-body-font-family);
            font-size: var(--bs-body-font-size);
            font-weight: var(--bs-body-font-weight);
            line-height: var(--bs-body-line-height);
            color: var(--bs-body-color);
            text-align: var(--bs-body-text-align);
            background-color: #f8f9fa;
            -webkit-text-size-adjust: 100%;
            -moz-text-size-adjust: 100%;
            text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .sidebar {
            background-color: var(--secondary-color);
            min-height: calc(100vh - 56px);
            padding: 2rem 1rem;
            color: white;
        }

        .main-content {
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem;
        }

        .table {
            margin-bottom: 0;
            white-space: nowrap;
            border-collapse: collapse;
            text-align: center;
        }

        .table td, .table th {
            text-align: -webkit-match-parent;
            text-align: match-parent;
            text-align: inherit;
            text-align: center;
            vertical-align: middle;
        }

        .table-responsive {
            overflow-x: auto;
            overflow-y: visible;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            max-height: none;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        .btn-primary {
            background-color: var(--accent-color);
            border: none;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .form-check-input {
            --bs-form-check-bg: var(--bs-body-bg);
            width: 1em;
            height: 1em;
            margin-top: .25em;
            vertical-align: top;
            background-color: var(--bs-form-check-bg);
            background-image: var(--bs-form-check-bg-image);
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            border: var(--bs-border-width) solid var(--bs-border-color);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .pagination .page-link {
            color: var(--primary-color);
        }

        .pagination .active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            font-weight: bold;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #fff;
            border-color: #dee2e6;
        }

        .stats-card {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .stats-card h3 {
            font-size: 2rem;
            margin: 0;
        }

        .stats-card p {
            margin: 0;
            opacity: 0.8;
        }

        .spinner-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .highlight {
            background-color: yellow;
        }
    </style>
    <link rel="stylesheet" href="styles.css?v=<?php echo $cacheBuster; ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">BSCS Profiling System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    title="Toggle navigation menu" aria-label="Toggle navigation" aria-expanded="false" aria-controls="navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0)" onclick="window.location.reload()">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#settingsModal">Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login/login.html" onclick="return confirm('Are you sure you want to logout?')">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Add Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="settingsModalLabel">Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="backupDatabase()">
                            <i class="bi bi-download"></i> Backup Database
                        </button>
                        <div class="mt-3">
                            <label for="restoreFile" class="form-label">Restore Database</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="restoreFile" accept=".json">
                                <button class="btn btn-warning" onclick="restoreDatabase()">
                                    <i class="bi bi-upload"></i> Restore
                                </button>
                            </div>
                            <div class="form-text">Only .json backup files are supported</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Stats Cards -->
            <div class="col-12 main-content">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3 id="totalStudents"><?php echo $total_students; ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3 id="maleCount"><?php echo $male_count ?? 0; ?></h3>
                            <p>Male Students</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3 id="femaleCount"><?php echo $female_count ?? 0; ?></h3>
                            <p>Female Students</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3>4</h3>
                            <p>Year Levels</p>
                        </div>
                    </div>
                </div>

                <!-- Main Content Card -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Student Records</h5>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="bi bi-plus-circle"></i> New Student
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Search and Filter Inputs -->
                        <div class="d-flex justify-content-between mb-3">
                            <div class="d-flex gap-2">
                                <div class="input-group search-input-group" style="width: 220px;">
                                    <input type="text" id="search" class="form-control form-control-sm" placeholder="Search Student Name or ID" onkeyup="filterTable()">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="clearSearch()" title="Clear Search" aria-label="Clear Search">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Filter Options" aria-label="Filter Options">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filter" viewBox="0 0 16 16">
                                            <path d="M6 10.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/>
                                        </svg>
                                        <span class="visually-hidden">Filter</span>
                                    </button>
                                    <div class="dropdown-menu p-3 filter-dropdown-menu" style="width: 220px;">
                                        <div class="mb-2">
                                            <label class="form-label small" for="filterSex">Sex</label>
                                            <select id="filterSex" class="form-select form-select-sm" onchange="filterTable()" aria-label="Filter by sex">
                                                <option value="">All Sex</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small" for="filterYearLevel">Year Level</label>
                                            <select id="filterYearLevel" class="form-select form-select-sm" onchange="filterTable()" aria-label="Filter by year level">
                                                <option value="">All Years</option>
                                                <option value="1">1st Year</option>
                                                <option value="2">2nd Year</option>
                                                <option value="3">3rd Year</option>
                                                <option value="4">4th Year</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small" for="filterCivilStatus">Civil Status</label>
                                            <select id="filterCivilStatus" class="form-select form-select-sm" onchange="filterTable()" aria-label="Filter by civil status">
                                                <option value="">All Civil Status</option>
                                                <option value="Single">Single</option>
                                                <option value="Married">Married</option>
                                                <option value="Divorced">Divorced</option>
                                                <option value="Widowed">Widowed</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small" for="filterCitizenship">Citizenship</label>
                                            <select id="filterCitizenship" class="form-select form-select-sm" onchange="filterTable()" aria-label="Filter by citizenship">
                                                <option value="">All Citizenship</option>
                                                <option value="Filipino">Filipino</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>
                                        <div class="d-flex justify-content-between mt-3">
                                            <button class="btn btn-sm btn-secondary" onclick="clearFilters()">Clear Filters</button>
                                            <button class="btn btn-sm btn-primary" onclick="applyFilters()">Apply</button>
                                        </div>
                                    </div>
                                </div>

                                <select id="recordsPerPage" class="form-select form-select-sm records-per-page-select" style="width: 80px;" onchange="changeRecordsPerPage()" aria-label="Records per page">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Export Options" aria-label="Export Options">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer" viewBox="0 0 16 16">
                                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/>
                                    </svg>
                                    Export
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="printTable()"><i class="bi bi-printer"></i> Print Table</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportToPDF()">Export as PDF</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportToCSV()">Export as CSV</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportToJSON()">Export as JSON</a></li>
                                </ul>
                            </div>
                        </div>
    
    <div class="table-responsive">
        <table class="table table-bordered text-center" id="studentTable">
            <thead class="table-dark">
                <tr>
                    <th class="text-center">Student ID</th>
                    <th class="text-center">First Name</th>
                    <th class="text-center">Middle Name</th>
                    <th class="text-center">Last Name</th>
                    <th class="text-center">Extension Name</th>
                    <th class="text-center">Email</th>
                    <th class="text-center">Phone</th>
                    <th class="text-center">Year Level</th>
                    <th class="text-center">Permanent Address</th>
                    <th class="text-center">Birthday</th>
                    <th class="text-center">Sex</th>
                    <th class="text-center">Citizenship</th>
                    <th class="text-center">Civil Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['student_id']}</td>
                                <td>{$row['first_name']}</td>
                                <td>{$row['middle_name']}</td>
                                <td>{$row['last_name']}</td>
                                <td>{$row['extension_name']}</td>
                                <td>{$row['email']}</td>
                                <td>{$row['phone']}</td>
                                <td>{$row['year_level']}</td>
                                <td>{$row['permanent_address']}</td>
                                <td>{$row['birthday']}</td>
                                <td>{$row['sex']}</td>
                                <td>{$row['citizenship']}</td>
                                <td>{$row['civil_status']}</td>
                                <td class='action-buttons'>
                                    <button class='btn btn-sm btn-info' onclick='viewStudent(\"{$row['student_id']}\")' title='View'><i class='bi bi-eye'></i></button>
                                    <button class='btn btn-sm btn-primary' onclick='editStudent(\"{$row['student_id']}\")' title='Edit'><i class='bi bi-pencil'></i></button>
                                    <button class='btn btn-sm btn-danger' onclick='deleteStudent(\"{$row['student_id']}\")' title='Delete'><i class='bi bi-trash'></i></button>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='14' class='text-center'>No students found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div id="noResultsMessage" class="text-center mt-3 no-results-message" style="display: none;">No records found</div>
    </div>
    
    <!-- Pagination Controls -->
    <div class="d-flex justify-content-end mt-3">
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0" id="pagination">
            </ul>
        </nav>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-labelledby="addStudentModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm" onsubmit="return submitNewStudent(event)" autocomplete="on">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="studentId">Student ID</label>
                                <input type="text" class="form-control" id="studentId" name="student_id" 
                                       pattern="^\d{2}-\d{4}$" 
                                       title="Please enter a valid student ID in the format: YY-NNNN (e.g., 22-1234)" 
                                       required autocomplete="off">
                                <div class="form-text">Format: YY-NNNN (e.g., 22-1234)</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="studentEmail">Email</label>
                                <input type="email" class="form-control email-field" id="studentEmail" 
                                       name="email" required autocomplete="email"
                                       pattern="[a-z0-9._%+-]+@(gmail\.com|yahoo\.com|yahoo\.com\.ph|outlook\.com|hotmail\.com|isu\.edu\.ph)$"
                                       title="Please use Gmail, Yahoo, Outlook, Hotmail or ISU email address">
                                <div class="form-text">Accepted domains: Gmail, Yahoo, Outlook, Hotmail, ISU</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="firstName">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="first_name" required autocomplete="given-name">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="middleName">Middle Name</label>
                                <input type="text" class="form-control" id="middleName" name="middle_name" autocomplete="additional-name">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="lastName">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="last_name" required autocomplete="family-name">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="extensionName">Extension Name</label>
                                <input type="text" class="form-control" id="extensionName" name="extension_name" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="phoneNumber">Phone</label>
                                <input type="tel" class="form-control" id="phoneNumber" name="phone" 
                                       pattern="^09\d{2}-\d{3}-\d{4}$"
                                       title="Please enter a valid phone number in the format: 0912-345-6789"
                                       required autocomplete="tel">
                                <div class="form-text">Format: 0912-345-6789</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="birthDate">Birthday</label>
                                <input type="date" class="form-control" id="birthDate" name="birthday" 
                                       required autocomplete="bday" 
                                       max="2009-12-31" 
                                       onchange="validateBirthday(this)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="studentSex">Sex</label>
                                <select class="form-select" id="studentSex" name="sex" required autocomplete="sex">
                                    <option value="">Choose...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="yearLevel">Year Level</label>
                                <select class="form-select" id="yearLevel" name="year_level" required autocomplete="off">
                                    <option value="">Choose...</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="civilStatus">Civil Status</label>
                                <select class="form-select" id="civilStatus" name="civil_status" required autocomplete="off">
                                    <option value="">Choose...</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="citizenship">Citizenship</label>
                                <select class="form-select" id="citizenship" name="citizenship" required>
                                    <option value="">Choose...</option>
                                    <option value="Filipino">Filipino</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="permanentAddress">Permanent Address</label>
                                <textarea class="form-control" id="permanentAddress" name="permanent_address" required autocomplete="street-address"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="clearAddStudentForm()">Clear Form</button>
                    <button type="submit" form="addStudentForm" class="btn btn-primary">Save Student</button>
                </div>
            </div>
        </div>
    </div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" role="dialog" aria-labelledby="editStudentModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStudentForm" onsubmit="return submitEditStudent(event)" autocomplete="on">
                    <input type="hidden" id="editStudentId" name="student_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="editEmail">Email</label>
                            <input type="email" class="form-control email-field" id="editEmail" 
                                   name="email" required autocomplete="email"
                                   pattern="[a-z0-9._%+-]+@(gmail\.com|yahoo\.com|yahoo\.com\.ph|outlook\.com|hotmail\.com|isu\.edu\.ph)$"
                                   title="Please use Gmail, Yahoo, Outlook, Hotmail or ISU email address">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="editFirstName">First Name</label>
                            <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="editMiddleName">Middle Name</label>
                            <input type="text" class="form-control" id="editMiddleName" name="middle_name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="editLastName">Last Name</label>
                            <input type="text" class="form-control" id="editLastName" name="last_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="editExtensionName">Extension Name</label>
                            <input type="text" class="form-control" id="editExtensionName" name="extension_name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="editPhone">Phone</label>
                            <input type="tel" class="form-control" id="editPhone" name="phone" 
                                   pattern="^09\d{2}-\d{3}-\d{4}$"
                                   title="Please enter a valid phone number in the format: 0912-345-6789"
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="editBirthday">Birthday</label>
                            <input type="date" class="form-control" id="editBirthday" name="birthday" 
                                   required max="2009-12-31">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="editSex">Sex</label>
                            <select class="form-select" id="editSex" name="sex" required>
                                <option value="">Choose...</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="editYearLevel">Year Level</label>
                            <select class="form-select" id="editYearLevel" name="year_level" required>
                                <option value="">Choose...</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="editCivilStatus">Civil Status</label>
                            <select class="form-select" id="editCivilStatus" name="civil_status" required>
                                <option value="">Choose...</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="editCitizenship">Citizenship</label>
                            <select class="form-select" id="editCitizenship" name="citizenship" required>
                                <option value="">Choose...</option>
                                <option value="Filipino">Filipino</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="editPermanentAddress">Permanent Address</label>
                            <textarea class="form-control" id="editPermanentAddress" name="permanent_address" required></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="editStudentForm" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Loading Spinner -->
<div class="spinner-overlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<script>
    let allStudents = <?php echo json_encode($all_students); ?>;
    let filteredStudents = [];
    let currentPage = 1;
    let recordsPerPage = 10;

    function validateStudentId(studentId) {
        const pattern = /^\d{2}-\d{4}$/;
        return pattern.test(studentId);
    }

    function validatePhoneNumber(phone) {
        const pattern = /^09\d{2}-\d{3}-\d{4}$/;
        return pattern.test(phone);
    }

    function validateEmail(email) {
        const emailRegex = /^[a-z0-9._%+-]+@(gmail\.com|yahoo\.com|yahoo\.com\.ph|outlook\.com|hotmail\.com|isu\.edu\.ph)$/i;
        return emailRegex.test(email);
    }

    function validateBirthday(input) {
        const birthDate = new Date(input.value);
        const now = new Date();
        const year = birthDate.getFullYear();
        
        // Don't allow birth years 2010 and above
        if (year >= 2010) {
            input.setCustomValidity('Birth year must be before 2010');
            return false;
        }
        
        // Calculate age
        const age = now.getFullYear() - year - 
                   (now.getMonth() < birthDate.getMonth() || 
                   (now.getMonth() === birthDate.getMonth() && now.getDate() < birthDate.getDate()) ? 1 : 0);
        
        if (age < 16) {
            input.setCustomValidity('Student must be at least 16 years old');
            return false;
        }
        
        input.setCustomValidity('');
        return true;
    }

    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length >= 11) {
            value = value.substring(0, 11);
            value = value.replace(/^(\d{4})(\d{3})(\d{4})$/, "$1-$2-$3");
        }
        input.value = value;
    }

    function formatStudentId(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length >= 6) {
            value = value.substring(0, 6); // Limit to 6 digits
        }
        if (value.length > 2) {
            value = value.substring(0, 2) + '-' + value.substring(2);
        }
        input.value = value;
        return value;
    }

    function capitalizeFirstLetter(input) {
        input.value = input.value
            .split(/[\s\-\.]+/)
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    }

    function standardizeCitizenship(input) {
        const value = input.value.trim();
        input.value = value.toLowerCase() === 'filipino' ? 'Filipino' : 'Others';
    }

    function updatePagination() {
        const totalPages = Math.ceil(filteredStudents.length / recordsPerPage);
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Previous</a>`;
        pagination.appendChild(prevLi);
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${currentPage === i ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`;
            pagination.appendChild(li);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Next</a>`;
        pagination.appendChild(nextLi);
    }

    function changePage(page) {
        if (page < 1 || page > Math.ceil(filteredStudents.length / recordsPerPage)) return;
        currentPage = page;
        updateTable();
    }

    function changeRecordsPerPage() {
        recordsPerPage = parseInt(document.getElementById('recordsPerPage').value);
        currentPage = 1;
        filterTable();
    }

    function filterTable() {
        showLoading();
        const searchInput = document.getElementById("search").value.toLowerCase();
        const sexFilter = document.getElementById("filterSex").value;
        const yearFilter = document.getElementById("filterYearLevel").value;
        const civilStatusFilter = document.getElementById("filterCivilStatus").value;
        const citizenshipFilter = document.getElementById("filterCitizenship").value;
        
        filteredStudents = allStudents.filter(student => {
            const textMatch = `${student.student_id} ${student.first_name} ${student.middle_name} ${student.last_name}`.toLowerCase().includes(searchInput);
            const sexMatch = !sexFilter || student.sex === sexFilter;
            // Convert both to strings for comparison
            const yearMatch = !yearFilter || String(student.year_level) === String(yearFilter);
            const civilStatusMatch = !civilStatusFilter || student.civil_status === civilStatusFilter;
            const citizenshipMatch = !citizenshipFilter || student.citizenship === citizenshipFilter;
            
            return textMatch && sexMatch && yearMatch && civilStatusMatch && citizenshipMatch;
        });

        currentPage = 1;
        updateTable();
        hideLoading();
    }

    function updateTable() {
        let tableBody = document.querySelector("#studentTable tbody");
        let searchInput = document.getElementById("search").value.toLowerCase(); // Get search input value
        document.getElementById("noResultsMessage").style.display = "none";
        
        tableBody.innerHTML = "";

        if (filteredStudents.length === 0) {
            tableBody.innerHTML = "<tr><td colspan='14' class='text-center'>No records found</td></tr>";
            updatePagination();
            return;
        }

        const start = (currentPage - 1) * recordsPerPage;
        const end = start + recordsPerPage;
        const paginatedStudents = filteredStudents.slice(start, end);

        paginatedStudents.sort((a, b) => a.student_id.localeCompare(b.student_id));

        paginatedStudents.forEach(student => {
            let row = `<tr>
                <td>${highlightText(student.student_id || '', searchInput)}</td>
                <td>${highlightText(student.first_name || '', searchInput)}</td>
                <td>${highlightText(student.middle_name || '', searchInput)}</td>
                <td>${highlightText(student.last_name || '', searchInput)}</td>
                <td>${student.extension_name || ''}</td>
                <td>${student.email || ''}</td>
                <td>${student.phone || ''}</td>
                <td>${student.year_level || ''}</td>
                <td>${student.permanent_address || ''}</td>
                <td>${student.birthday || ''}</td>
                <td>${student.sex || ''}</td>
                <td>${student.citizenship || ''}</td>
                <td>${student.civil_status || ''}</td>
                <td class='action-buttons'>
                    <button class='btn btn-sm btn-info' onclick='viewStudent("${student.student_id}")' title='View'><i class='bi bi-eye'></i></button>
                    <button class='btn btn-sm btn-primary' onclick='editStudent("${student.student_id}")' title='Edit'><i class='bi bi-pencil'></i></button>
                    <button class='btn btn-sm btn-danger' onclick='deleteStudent("${student.student_id}")' title='Delete'><i class='bi bi-trash'></i></button>
                </td>
            </tr>`;
            tableBody.innerHTML += row;
        });

        updatePagination();
    }

    function clearSearch() {
        document.getElementById("search").value = "";
        currentPage = 1; // Reset to first page
        filterTable();
    }

    function clearFilters() {
        document.getElementById("filterSex").value = "";
        document.getElementById("filterYearLevel").value = "";
        document.getElementById("filterCivilStatus").value = "";
        document.getElementById("filterCitizenship").value = "";
        filterTable();
    }

    function applyFilters() {
        filterTable();
    }

    function getCurrentDateTime() {
        const now = new Date();
        return now.toLocaleString();
    }

    function getPrinterInfo() {
        return "System User";
    }

    function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        doc.setFontSize(12);
        doc.text(`Printed by: ${getPrinterInfo()}`, 14, 15);
        doc.text(`Date/Time: ${getCurrentDateTime()}`, 14, 22);
        doc.text("BSCS Student Records", 14, 30);

        const tableData = filteredStudents.map(student => [
            student.student_id,
            student.first_name,
            student.last_name,
            student.email,
            student.phone,
            student.year_level
        ]);

        doc.autoTable({
            head: [['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Year']],
            body: tableData,
            startY: 35,
        });

        doc.save(`students_${new Date().toISOString().slice(0,10)}.pdf`);
    }

    function exportToCSV() {
        const headers = ['Student ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Year Level'];
        const csvData = filteredStudents.map(student => 
            [student.student_id, student.first_name, student.last_name, 
             student.email, student.phone, student.year_level]
        );
        
        const metadata = [
            [`Printed by: ${getPrinterInfo()}`],
            [`Date/Time: ${getCurrentDateTime()}`],
            [],
            headers
        ];
        
        const csvContent = [...metadata, ...csvData]
            .map(row => row.join(','))
            .join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', `students_${new Date().toISOString().slice(0,10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportToJSON() {
        const exportData = {
            metadata: {
                printed_by: getPrinterInfo(),
                timestamp: getCurrentDateTime(),
                total_records: filteredStudents.length
            },
            students: filteredStudents
        };

        const jsonString = JSON.stringify(exportData, null, 2);
        const blob = new Blob([jsonString], { type: 'application/json' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', `students_${new Date().toISOString().slice(0,10)}.json`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function printTable() {
        let printContents = document.querySelector('.table-responsive').innerHTML;
        let originalContents = document.body.innerHTML;

        let printHeader = `
            <div style="margin-bottom: 20px">
                <h4>BSCS Student Records</h4>
                <p>Printed by: ${getPrinterInfo()}</p>
                <p>Date/Time: ${getCurrentDateTime()}</p>
            </div>
        `;

        document.body.innerHTML = printHeader + printContents;
        window.print();
        document.body.innerHTML = originalContents;
        
        filteredStudents = allStudents;
        updateTable();
    }

    function showLoading() {
        document.querySelector('.spinner-overlay').style.display = 'flex';
    }

    function hideLoading() {
        document.querySelector('.spinner-overlay').style.display = 'none';
    }

    function viewStudent(id) {
        showLoading();
        fetch(`api/get_student.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                // Handle view logic
            })
            .catch(error => {
                hideLoading();
                alert('Error loading student data');
            });
    }

    function editStudent(id) {
        showLoading();
        fetch(`api/get_student.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.student) {
                    const student = data.student;
                    document.getElementById('editStudentId').value = student.student_id;
                    document.getElementById('editEmail').value = student.email;
                    document.getElementById('editFirstName').value = student.first_name;
                    document.getElementById('editMiddleName').value = student.middle_name || '';
                    document.getElementById('editLastName').value = student.last_name;
                    document.getElementById('editExtensionName').value = student.extension_name || '';
                    document.getElementById('editPhone').value = student.phone;
                    document.getElementById('editBirthday').value = student.birthday;
                    document.getElementById('editSex').value = student.sex;
                    document.getElementById('editYearLevel').value = student.year_level;
                    document.getElementById('editCivilStatus').value = student.civil_status;
                    document.getElementById('editCitizenship').value = student.citizenship;
                    document.getElementById('editPermanentAddress').value = student.permanent_address;

                    const editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
                    editModal.show();
                } else {
                    alert('Error loading student data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading student data');
            })
            .finally(() => {
                hideLoading();
            });
    }

    function submitEditStudent(event) {
        event.preventDefault();
        showLoading();

        const form = event.target;
        const formData = new FormData(form);

        // Client-side validation
        try {
            if (!validatePhoneNumber(formData.get('phone'))) {
                throw new Error('Invalid phone number format');
            }
            if (!validateEmail(formData.get('email'))) {
                throw new Error('Invalid email format');
            }
        } catch (error) {
            hideLoading();
            alert(error.message);
            return false;
        }

        fetch('api/update_student.php', {
            method: 'POST',
            body: formData
        })
        .then(handleServerResponse)
        .then(data => {
            if (data.success) {
                // Update the students arrays
                const index = allStudents.findIndex(s => s.student_id === data.student.student_id);
                if (index !== -1) {
                    allStudents[index] = data.student;
                }
                filteredStudents = [...allStudents];
                updateTable();
                
                if (data.stats) {
                    updateDashboardStats(data.stats);
                }
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('editStudentModal'));
                modal?.hide();
                
                alert('Student updated successfully!');
            } else {
                throw new Error(data.message || 'Failed to update student');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Failed to update student');
        })
        .finally(() => {
            hideLoading();
        });

        return false;
    }

    function highlightText(text, searchTerm) {
        if (!searchTerm) return text;
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<span class="highlight">$1</span>');
    }

    async function handleServerResponse(response) {
        const contentType = response.headers.get('content-type');
        
        if (!contentType || !contentType.includes('application/json')) {
            // Log the actual response for debugging
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned non-JSON response');
        }

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        
        return data;
    }

    function submitNewStudent(event) {
        event.preventDefault();
        showLoading();

        const form = event.target;
        const formData = new FormData(form);

        // Client-side validation
        try {
            if (!validateStudentId(formData.get('student_id'))) {
                throw new Error('Invalid student ID format');
            }
            if (!validatePhoneNumber(formData.get('phone'))) {
                throw new Error('Invalid phone number format');
            }
            if (!validateEmail(formData.get('email'))) {
                throw new Error('Invalid email format');
            }
        } catch (error) {
            hideLoading();
            alert(error.message);
            return false;
        }

        fetch('api/add_student.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned invalid response');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message);
            }
            
            // Update table and stats
            allStudents = [...allStudents, data.student];
            filteredStudents = [...allStudents];
            updateTable();
            
            if (data.stats) {
                updateDashboardStats(data.stats);
            }
            
            // Reset form and close modal
            form.reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
            modal?.hide();
            
            alert('Student added successfully!');
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Failed to add student');
        })
        .finally(() => {
            hideLoading();
        });

        return false;
    }

    function updateDashboardStats(stats) {
        const elements = {
            total: document.getElementById('totalStudents'),
            male: document.getElementById('maleCount'),
            female: document.getElementById('femaleCount')
        };

        if (elements.total && stats.total_students !== undefined) {
            elements.total.textContent = stats.total_students;
        }
        if (elements.male && stats.male_count !== undefined) {
            elements.male.textContent = stats.male_count;
        }
        if (elements.female && stats.female_count !== undefined) {
            elements.female.textContent = stats.female_count;
        }
    }

    function clearAddStudentForm() {
        const form = document.getElementById('addStudentForm');
        form.reset();
        
        // Clear any custom validity messages
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.setCustomValidity('');
            input.classList.remove('is-invalid');
        });

        // Focus on the first field
        document.getElementById('studentId').focus();
    }

    function deleteStudent(id) {
        // Show confirmation dialog with student details
        if (confirm('Are you sure you want to delete this student?')) {
            showLoading();
            
            const formData = new FormData();
            formData.append('student_id', id);

            fetch('api/delete_student.php', {
                method: 'POST',
                body: formData
            })
            .then(handleServerResponse)
            .then(data => {
                if (data.success) {
                    // Remove student from arrays
                    allStudents = allStudents.filter(student => student.student_id !== id);
                    filteredStudents = filteredStudents.filter(student => student.student_id !== id);
                    
                    // Update table and stats
                    updateTable();
                    if (data.stats) {
                        updateDashboardStats(data.stats);
                    }
                    
                    alert('Student deleted successfully');
                } else {
                    throw new Error(data.message || 'Error deleting student');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting student: ' + error.message);
            })
            .finally(() => {
                hideLoading();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        filteredStudents = allStudents;
        updateTable();

        // Improved modal focus management
        const addStudentModal = document.getElementById('addStudentModal');
        const openModalButton = document.querySelector('[data-bs-target="#addStudentModal"]');
        const closeModalButton = addStudentModal.querySelector('.btn-close');
        const studentIdInput = document.getElementById('studentId');
        
        // Store last focused element before modal opens
        let lastActiveElement;

        addStudentModal.addEventListener('show.bs.modal', function () {
            lastActiveElement = document.activeElement;
        });

        addStudentModal.addEventListener('shown.bs.modal', function () {
            studentIdInput.focus();
            
            // Trap focus within modal
            const focusableElements = addStudentModal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const firstFocusableElement = focusableElements[0];
            const lastFocusableElement = focusableElements[focusableElements.length - 1];

            addStudentModal.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === firstFocusableElement) {
                            e.preventDefault();
                            lastFocusableElement.focus();
                        }
                    } else {
                        if (document.activeElement === lastFocusableElement) {
                            e.preventDefault();
                            firstFocusableElement.focus();
                        }
                    }
                }

                if (e.key === 'Escape') {
                    bootstrap.Modal.getInstance(addStudentModal).hide();
                }
            });
        });

        addStudentModal.addEventListener('hidden.bs.modal', function () {
            // Return focus to the button that opened the modal
            if (lastActiveElement) {
                lastActiveElement.focus();
            }
            clearAddStudentForm();
        });

        // Add student ID formatting
        studentIdInput.addEventListener('input', function() {
            formatStudentId(this);
        });
        
        // Prevent form submission if ID format is incorrect
        studentIdInput.addEventListener('blur', function() {
            const value = this.value;
            if (value && !validateStudentId(value)) {
                this.setCustomValidity('Please enter a valid student ID in the format: YY-NNNN (e.g., 22-1234)');
            } else {
                this.setCustomValidity('');
            }
        });

        // Add phone number formatting
        const phoneInput = document.getElementById('phoneNumber');
        phoneInput.addEventListener('input', function() {
            formatPhoneNumber(this);
        });

        // Add name input handlers
        const nameInputs = ['firstName', 'middleName', 'lastName', 'extensionName'];
        nameInputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('blur', function() {
                    capitalizeFirstLetter(this);
                });
            }
        });

        // Add address input handler
        const addressInput = document.getElementById('permanentAddress');
        if (addressInput) {
            addressInput.addEventListener('blur', function() {
                capitalizeFirstLetter(this);
            });
        }

        // Add edit form input handlers
        const editPhoneInput = document.getElementById('editPhone');
        editPhoneInput.addEventListener('input', function() {
            formatPhoneNumber(this);
        });

        const editNameInputs = ['editFirstName', 'editMiddleName', 'editLastName', 'editExtensionName'];
        editNameInputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('blur', function() {
                    capitalizeFirstLetter(this);
                });
            }
        });

        const editAddressInput = document.getElementById('editPermanentAddress');
        if (editAddressInput) {
            editAddressInput.addEventListener('blur', function() {
                capitalizeFirstLetter(this);
            });
        }
    });

    function backupDatabase() {
        if (!confirm('Do you want to create a database backup?')) return;
        
        showLoading();
        const formData = new FormData();
        formData.append('action', 'backup');
        
        fetch('backup_restore.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Backup created successfully!\nFile: ' + data.filename);
                // Download the file in the background
                const link = document.createElement('a');
                link.href = 'backups/' + data.filename;
                link.download = data.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                // Redirect to dashboard
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Backup failed: ' + error.message);
        })
        .finally(() => {
            hideLoading();
        });
    }

    function restoreDatabase() {
        const fileInput = document.getElementById('restoreFile');
        if (!fileInput.files.length) {
            alert('Please select a backup file first');
            return;
        }

        if (!confirm('Warning: This will override all existing data. Continue?')) return;
        
        showLoading();
        const formData = new FormData();
        formData.append('action', 'restore');
        formData.append('backupFile', fileInput.files[0]);
        
        fetch('backup_restore.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Database restored successfully!');
                window.location.reload();
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Restore failed: ' + error.message);
        })
        .finally(() => {
            hideLoading();
            fileInput.value = '';
        });
    }
    </script>
</body>
</html>

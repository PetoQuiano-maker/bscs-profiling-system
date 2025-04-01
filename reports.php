<?php
include 'config.php';

// Set cache control headers
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Add date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build the SQL query with optional date filter
$where_clause = "";
if ($start_date && $end_date) {
    $where_clause = " WHERE DATE(timestamp) BETWEEN ? AND ?";
}

// Get total count with date filter
$total_sql = "SELECT COUNT(*) as count FROM audit_logs" . $where_clause;
$stmt = $conn->prepare($total_sql);
if ($start_date && $end_date) {
    $stmt->bind_param("ss", $start_date, $end_date);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_rows = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_rows / $limit);

// Fetch audit logs with pagination and date filter
$sql = "SELECT * FROM audit_logs" . $where_clause . " ORDER BY timestamp DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($start_date && $end_date) {
    $stmt->bind_param("ssii", $start_date, $end_date, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Store results in array for export
$audit_logs = [];
while ($row = $result->fetch_assoc()) {
    $audit_logs[] = $row;
}

// Reset result pointer for table display
$result = $stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records Audit Log</title>
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

    <style>
        :root {
            --primary-color: #34495e;
            --secondary-color: #34495e;
            --accent-color: #3498db;
        }
        
        body {
            background-color: #f8f9fa;
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

        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.8) !important;
        }

        .navbar-nav .nav-link.active {
            color: white !important;
            font-weight: bold;
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
        }

        .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }

        .action-add { color: #28a745; font-weight: bold; }
        .action-edit { color: #ffc107; font-weight: bold; }
        .action-delete { color: #dc3545; font-weight: bold; }

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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">BSCS Profiling System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php" onclick="navigateToDashboard(event)">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Settings</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Student Records Audit Log</h5>
                <div class="d-flex gap-2">
                    <form class="d-flex gap-2" method="get">
                        <input type="date" class="form-control form-control-sm" name="start_date" 
                               value="<?php echo $start_date; ?>" placeholder="Start Date">
                        <input type="date" class="form-control form-control-sm" name="end_date" 
                               value="<?php echo $end_date; ?>" placeholder="End Date">
                        <select name="limit" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 records</option>
                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 records</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 records</option>
                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 records</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    </form>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportToPDF()">Export as PDF</a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportToCSV()">Export as CSV</a></li>
                            <li><a class="dropdown-item" href="#" onclick="printReport()">Print Report</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Add an ID to the table for easier reference -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="auditTable" data-version="<?php echo $result->fetch_assoc()['version']; ?>">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Action</th>
                                <th>Student ID</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_logs as $row): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($row['timestamp'])); ?></td>
                                <td>
                                    <span class="action-<?php echo strtolower($row['action']); ?>">
                                        <?php echo $row['action']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['details']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Update pagination links to include all parameters -->
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&limit=<?php echo $limit; ?><?php echo $start_date ? '&start_date='.$start_date : ''; ?><?php echo $end_date ? '&end_date='.$end_date : ''; ?>">Previous</a>
                        </li>
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?><?php echo $start_date ? '&start_date='.$start_date : ''; ?><?php echo $end_date ? '&end_date='.$end_date : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&limit=<?php echo $limit; ?><?php echo $start_date ? '&start_date='.$start_date : ''; ?><?php echo $end_date ? '&end_date='.$end_date : ''; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script>
    // Update export functions to work with audit logs
    window.jsPDF = window.jspdf.jsPDF;

    function exportToPDF() {
        const doc = new jsPDF();
        
        // Add header
        doc.setFontSize(16);
        doc.text("Audit Log Report", 14, 15);
        
        // Add metadata
        doc.setFontSize(10);
        doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 25);
        if (document.querySelector('[name="start_date"]').value) {
            doc.text(`Date Range: ${document.querySelector('[name="start_date"]').value} to ${document.querySelector('[name="end_date"]').value}`, 14, 30);
        }
        
        // Prepare data for table
        const headers = [['Timestamp', 'Action', 'Student ID', 'Details']];
        const data = <?php echo json_encode($audit_logs); ?>.map(log => [
            new Date(log.timestamp).toLocaleString(),
            log.action,
            log.student_id,
            log.details
        ]);

        // Add table
        doc.autoTable({
            head: headers,
            body: data,
            startY: 35,
            styles: {
                fontSize: 8,
                cellPadding: 2
            },
            columnStyles: {
                0: { cellWidth: 40 },
                1: { cellWidth: 25 },
                2: { cellWidth: 25 },
                3: { cellWidth: 'auto' }
            }
        });

        doc.save('audit_log_report.pdf');
    }

    function exportToCSV() {
        const headers = ['Timestamp', 'Action', 'Student ID', 'Details'];
        const data = <?php echo json_encode($audit_logs); ?>;
        
        let csvContent = headers.join(',') + '\n';
        data.forEach(row => {
            const timestamp = new Date(row.timestamp).toLocaleString();
            const values = [
                `"${timestamp}"`,
                `"${row.action}"`,
                `"${row.student_id}"`,
                `"${row.details.replace(/"/g, '""')}"`
            ];
            csvContent += values.join(',') + '\n';
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `audit_log_${new Date().toISOString().slice(0,10)}.csv`;
        link.click();
    }

    function printReport() {
        const printContent = document.querySelector('.table-responsive').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `
            <div style="padding: 20px;">
                <h1>Audit Log Report</h1>
                <p>Generated: ${new Date().toLocaleString()}</p>
                ${document.querySelector('[name="start_date"]').value ? 
                    `<p>Date Range: ${document.querySelector('[name="start_date"]').value} to ${document.querySelector('[name="end_date"]').value}</p>` : 
                    ''}
                ${printContent}
            </div>
        `;
        
        window.print();
        document.body.innerHTML = originalContent;
        
        // Reattach event listeners
        location.reload();
    }

    // Add form submit handler
    document.querySelector('form').addEventListener('submit', function(e) {
        const startDate = document.querySelector('[name="start_date"]').value;
        const endDate = document.querySelector('[name="end_date"]').value;
        
        if (startDate && !endDate) {
            e.preventDefault();
            alert('Please select an end date');
            return false;
        }
        if (!startDate && endDate) {
            e.preventDefault();
            alert('Please select a start date');
            return false;
        }
        if (startDate && endDate && startDate > endDate) {
            e.preventDefault();
            alert('Start date cannot be later than end date');
            return false;
        }
    });

    // Add records per page change handler
    document.querySelector('[name="limit"]').addEventListener('change', function() {
        this.form.submit();
    });

    // Function to refresh the table data
    function refreshTable() {
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page') || 1;
        const limit = urlParams.get('limit') || 10;
        const startDate = urlParams.get('start_date') || '';
        const endDate = urlParams.get('end_date') || '';
        
        // Get current version
        const currentVersion = document.getElementById('auditTable').getAttribute('data-version');

        const controller = new AbortController();
        window.activeFetch = controller;

        fetch(`reports.php?page=${page}&limit=${limit}&start_date=${startDate}&end_date=${endDate}&version=${currentVersion}`, { signal: controller.signal })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.getElementById('auditTable');
                
                if (newTable) {
                    const newVersion = newTable.getAttribute('data-version');
                    const oldTable = document.getElementById('auditTable');
                    
                    // Update table only if version has changed
                    if (newVersion !== currentVersion) {
                        oldTable.innerHTML = newTable.innerHTML;
                        oldTable.setAttribute('data-version', newVersion);
                        
                        // Highlight new entries
                        const tbody = oldTable.querySelector('tbody');
                        const firstRow = tbody.firstElementChild;
                        if (firstRow) {
                            firstRow.style.animation = 'highlightNew 2s ease-in-out';
                        }
                    }
                }
            })
            .catch(error => {
                if (error.name !== 'AbortError') {
                    console.error('Error refreshing table:', error);
                }
            });
    }

    // Add animation style
    document.head.insertAdjacentHTML('beforeend', `
        <style>
            @keyframes highlightNew {
                0% { background-color: #d4edda; }
                100% { background-color: transparent; }
            }
            #auditTable tr:first-child {
                transition: background-color 0.5s ease-in-out;
            }
        </style>
    `);

    // Replace conflicting setInterval calls with a single interval
    const refreshInterval = setInterval(refreshTable, 5000);

    // Add date range validation
    const startDate = document.querySelector('[name="start_date"]');
    const endDate = document.querySelector('[name="end_date"]');

    if (startDate && endDate) {
        startDate.addEventListener('change', function() {
            endDate.min = this.value;
        });
        endDate.addEventListener('change', function() {
            startDate.max = this.value;
        });
    }

    function handleNavigation(event, destination) {
        event.preventDefault();
        
        // Clear the specific refresh interval
        clearInterval(refreshInterval);
        
        // Stop any ongoing fetch requests
        if (window.activeFetch) {
            window.activeFetch.abort();
        }
        
        // Perform the navigation
        window.location.href = destination;
    }
    </script>
</body>
</html>

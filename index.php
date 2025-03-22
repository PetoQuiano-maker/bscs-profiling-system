<?php
include 'config.php';

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        .table-responsive {
            max-width: 1366px; /* Adjust to fit a 14-inch screen */
            overflow-x: auto; /* Enable horizontal scrolling */
            margin: auto; /* Center the table */
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            overflow: auto;
            white-space: nowrap; /* Ensure content is in one line */
            text-align: center; /* Center text in table cells */
        }
        th {
            background-color: #343a40;
            color: white;
        }
        .square-select {
            border-radius: 0; /* Remove border radius to make it square */
        }
        .container {
            max-width: 1366px; /* Adjust to fit a 14-inch screen */
        }
    </style>
</head>
<body>

<div class="container mt-5">
    
    <!-- Search and Filter Inputs -->
    <div class="d-flex justify-content-between mb-3">
        <div class="d-flex gap-2">
            <div class="input-group" style="width: 220px;">
                <input type="text" id="search" class="form-control form-control-sm" placeholder="Search Student Name or ID" onkeyup="filterTable()">
                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="clearSearch()">
                    <span>&times;</span>
                </button>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filter" viewBox="0 0 16 16">
                        <path d="M6 10.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/>
                    </svg>
                </button>
                <div class="dropdown-menu p-3" style="width: 220px;">
                    <div class="mb-2">
                        <label class="form-label small">Sex</label>
                        <select id="filterSex" class="form-select form-select-sm" onchange="filterTable()">
                            <option value="">All Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Year Level</label>
                        <select id="filterYearLevel" class="form-select form-select-sm" onchange="filterTable()">
                            <option value="">All Years</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Civil Status</label>
                        <select id="filterCivilStatus" class="form-select form-select-sm" onchange="filterTable()">
                            <option value="">All Civil Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Citizenship</label>
                        <select id="filterCitizenship" class="form-select form-select-sm" onchange="filterTable()">
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

            <select id="recordsPerPage" class="form-select form-select-sm" style="width: 80px;" onchange="changeRecordsPerPage()">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/>
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
        <table class="table table-bordered" id="studentTable">
            <thead class="table-dark">
                <tr>
                    <th>Student ID</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Last Name</th>
                    <th>Extension Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Year Level</th>
                    <th>Permanent Address</th>
                    <th>Birthday</th>
                    <th>Sex</th>
                    <th>Citizenship</th>
                    <th>Civil Status</th>
                    <th>Actions</th>
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
                                <td>
                                    <a href='edit_student.php?id={$row['student_id']}' class='btn btn-warning btn-sm'>Edit</a>
                                    <a href='delete_student.php?id={$row['student_id']}' class='btn btn-danger btn-sm'>Delete</a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='14' class='text-center'>No students found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div id="noResultsMessage" class="text-center mt-3" style="display: none;">No records found</div>
    </div>
    
    <!-- Pagination Controls -->
    <div class="d-flex justify-content-end mt-3">
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0" id="pagination">
            </ul>
        </nav>
    </div>

    <!-- Student Registration Form -->
    <h3 class="mt-4">Add Student</h3>
    <form id="studentForm" action="api/add_student.php" method="POST">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="student_id" class="form-control mb-2" 
                    placeholder="Student ID (YY-####)" required 
                    pattern="\d{2}-\d{4}" 
                    title="Student ID must be in format YY-#### (e.g., 22-4567)"
                    oninput="formatStudentId(this)">
            </div>
            <div class="col-md-4"><input type="text" name="first_name" class="form-control mb-2" placeholder="First Name" required></div>
            <div class="col-md-4"><input type="text" name="middle_name" class="form-control mb-2" placeholder="Middle Name"></div>
            <div class="col-md-4"><input type="text" name="last_name" class="form-control mb-2" placeholder="Last Name" required></div>
            <div class="col-md-4"><input type="text" name="extension_name" class="form-control mb-2" placeholder="Extension Name"></div>
            <div class="col-md-4">
                <input type="email" name="email" class="form-control mb-2" 
                    placeholder="Email" required
                    pattern="[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|outlook\.com|isu\.edu\.ph)$"
                    title="Please enter a valid email from: gmail.com, yahoo.com, outlook.com, or isu.edu.ph">
            </div>
            <div class="col-md-4">
                <input type="text" name="phone" class="form-control mb-2" 
                    placeholder="Phone (0XXX-XXX-XXXX)" required 
                    pattern="0\d{3}-\d{3}-\d{4}" 
                    title="Phone number must be in format 0XXX-XXX-XXXX (e.g., 0912-345-6789)"
                    oninput="formatPhoneNumber(this)">
            </div>
            <div class="col-md-4"><input type="text" name="year_level" class="form-control mb-2" placeholder="Year Level" required></div>
            <div class="col-md-4"><input type="text" name="permanent_address" class="form-control mb-2" placeholder="Permanent Address" required></div>
            <div class="col-md-4"><input type="date" name="birthday" class="form-control mb-2" required></div>
            <div class="col-md-4">
                <select name="sex" class="form-control mb-2" required>
                    <option value="" disabled selected>Select Sex</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="col-md-4"><input type="text" name="citizenship" class="form-control mb-2" placeholder="Citizenship" required></div>
            <div class="col-md-4">
                <select name="civil_status" class="form-control mb-2" required>
                    <option value="" disabled selected>Select Civil Status</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Divorced">Divorced</option>
                    <option value="Widowed">Widowed</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Add Student</button>
        <button type="button" class="btn btn-secondary" onclick="clearForm()">Clear All</button>
    </form>
</div>

<script>
    const allStudents = <?php echo json_encode($all_students); ?>;
    let filteredStudents = [];
    let currentPage = 1;
    let recordsPerPage = 10;

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
        let input = document.getElementById("search").value.toLowerCase();
        let sexFilter = document.getElementById("filterSex").value;
        let yearFilter = document.getElementById("filterYearLevel").value;
        let civilStatusFilter = document.getElementById("filterCivilStatus").value;
        let citizenshipFilter = document.getElementById("filterCitizenship").value;
        
        filteredStudents = allStudents.filter(student => {
            let textMatch = `${student.student_id} ${student.first_name} ${student.middle_name} ${student.last_name}`.toLowerCase().includes(input);
            let sexMatch = !sexFilter || student.sex === sexFilter;
            let yearMatch = !yearFilter || student.year_level === yearFilter;
            let civilStatusMatch = !civilStatusFilter || student.civil_status === civilStatusFilter;
            let citizenshipMatch = !citizenshipFilter || student.citizenship.includes(citizenshipFilter);
            
            return textMatch && sexMatch && yearMatch && civilStatusMatch && citizenshipMatch;
        });

        currentPage = 1; // Reset to first page when filtering
        updateTable();
    }

    function updateTable() {
        let tableBody = document.querySelector("#studentTable tbody");
        document.getElementById("noResultsMessage").style.display = "none";
        tableBody.innerHTML = "";

        if (filteredStudents.length === 0) {
            tableBody.innerHTML = "<tr><td colspan='14' class='text-center'>No records found</td></tr>";
            updatePagination();
            return;
        }

        // Calculate the range of records to show
        const start = (currentPage - 1) * recordsPerPage;
        const end = start + recordsPerPage;
        const paginatedStudents = filteredStudents.slice(start, end);

        paginatedStudents.forEach(student => {
            let row = `<tr>
                <td>${student.student_id}</td>
                <td>${student.first_name}</td>
                <td>${student.middle_name}</td>
                <td>${student.last_name}</td>
                <td>${student.extension_name}</td>
                <td>${student.email}</td>
                <td>${student.phone}</td>
                <td>${student.year_level}</td>
                <td>${student.permanent_address}</td>
                <td>${student.birthday}</td>
                <td>${student.sex}</td>
                <td>${student.citizenship}</td>
                <td>${student.civil_status}</td>
                <td>
                    <a href="javascript:void(0)" onclick="editStudent('${student.student_id}')" class="btn btn-warning btn-sm">Edit</a>
                    <a href="javascript:void(0)" onclick="deleteStudent('${student.student_id}')" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>`;
            tableBody.innerHTML += row;
        });

        updatePagination();
    }

    function clearForm() {
        const form = document.getElementById('studentForm');
        form.reset();
        
        // Reset to add mode
        const studentIdField = form.querySelector('input[name="student_id"]');
        studentIdField.readOnly = false;
        
        // Remove edit mode hidden field
        const hiddenEdit = form.querySelector('input[name="isEdit"]');
        if (hiddenEdit) hiddenEdit.remove();
        
        // Reset form action and submit button
        form.action = 'api/add_student.php';
        form.querySelector('button[type="submit"]').textContent = 'Add Student';
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
        // Optional: close the dropdown after applying filters
        // You can uncomment the next line if you want this behavior
        // document.querySelector('.dropdown-menu').classList.remove('show');
    }

    function getCurrentDateTime() {
        const now = new Date();
        return now.toLocaleString();
    }

    function getPrinterInfo() {
        // You can modify this to get actual user info from your system
        return "System User"; // Placeholder
    }

    function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Add header with timestamp and user info
        doc.setFontSize(12);
        doc.text(`Printed by: ${getPrinterInfo()}`, 14, 15);
        doc.text(`Date/Time: ${getCurrentDateTime()}`, 14, 22);
        doc.text("BSCS Student Records", 14, 30);

        // Convert data to format suitable for autotable
        const tableData = filteredStudents.map(student => [
            student.student_id,
            student.first_name,
            student.last_name,
            student.email,
            student.phone,
            student.year_level
        ]);

        // Generate table
        doc.autoTable({
            head: [['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Year']],
            body: tableData,
            startY: 35,
        });

        // Save PDF
        doc.save(`students_${new Date().toISOString().slice(0,10)}.pdf`);
    }

    function exportToCSV() {
        const headers = ['Student ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Year Level'];
        const csvData = filteredStudents.map(student => 
            [student.student_id, student.first_name, student.last_name, 
             student.email, student.phone, student.year_level]
        );
        
        // Add metadata as first rows
        const metadata = [
            [`Printed by: ${getPrinterInfo()}`],
            [`Date/Time: ${getCurrentDateTime()}`],
            [], // Empty row
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

        // Add print metadata
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
        
        // Reinitialize the page after printing
        filteredStudents = allStudents;
        updateTable();
    }

    function formatPhoneNumber(input) {
        // Remove non-digits
        let numbers = input.value.replace(/\D/g, '');
        
        // Ensure we don't exceed 11 digits
        numbers = numbers.substring(0, 11);
        
        // Format with dashes
        if (numbers.length > 0) {
            let parts = [];
            parts.push(numbers.substring(0, 4));
            if (numbers.length > 4) {
                parts.push(numbers.substring(4, 7));
            }
            if (numbers.length > 7) {
                parts.push(numbers.substring(7, 11));
            }
            input.value = parts.join('-');
        } else {
            input.value = numbers;
        }
    }

    function formatStudentId(input) {
        // Remove non-digits
        let numbers = input.value.replace(/\D/g, '');
        
        // Limit to 6 digits (2 for year + 4 for number)
        numbers = numbers.substring(0, 6);
        
        // Format with dash
        if (numbers.length > 0) {
            let parts = [];
            parts.push(numbers.substring(0, 2));
            if (numbers.length > 2) {
                parts.push(numbers.substring(2, 6));
            }
            input.value = parts.join('-');
        } else {
            input.value = numbers;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        filteredStudents = allStudents;
        updateTable();
    });

    function editStudent(studentId) {
        fetch('api/get_student.php?id=' + studentId)
            .then(response => response.json())
            .then(data => {
                if (data.student) {
                    const form = document.getElementById('studentForm');
                    // Reset form and remove any previous hidden fields
                    form.reset();
                    const oldHidden = form.querySelector('input[name="isEdit"]');
                    if (oldHidden) oldHidden.remove();

                    // Add hidden field to store original student ID for verification
                    form.insertAdjacentHTML('beforeend', 
                        `<input type="hidden" name="original_student_id" value="${studentId}">`);

                    // Fill form with student data
                    for (let key in data.student) {
                        if (form.elements[key]) {
                            form.elements[key].value = data.student[key];
                        }
                    }

                    // Add hidden field for edit mode
                    form.insertAdjacentHTML('beforeend', 
                        `<input type="hidden" name="isEdit" value="true">`);
                    form.action = 'api/update_student.php';
                    
                    // Update submit button
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.textContent = 'Update Student';
                    
                    // Scroll to form
                    form.scrollIntoView({ behavior: 'smooth' });
                } else {
                    alert('Student not found');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading student data');
            });
    }

    document.getElementById('studentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const isEdit = formData.get('isEdit');
        const action = isEdit ? 'api/update_student.php' : 'api/add_student.php';

        fetch(action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } else {
                alert(data.message);
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while ' + (isEdit ? 'updating' : 'adding') + ' the student');
        });
    });

    function deleteStudent(studentId) {
        if (confirm('Are you sure you want to delete this student?')) {
            fetch('api/delete_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + studentId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Student deleted successfully');
                    location.reload();
                } else {
                    alert(data.error || 'Error deleting student');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting student');
            });
        }
    }
</script>

</body>
</html>

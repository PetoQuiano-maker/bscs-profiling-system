<?php
function validateStudentId($studentId) {
    // Must be in format YY-NNNN where YY is year and NNNN is sequence number
    if (!preg_match('/^\d{2}-\d{4}$/', $studentId)) {
        return false;
    }
    
    list($year, $sequence) = explode('-', $studentId);
    
    // Ensure sequence number is between 0001 and 9999
    if ((int)$sequence < 1 || (int)$sequence > 9999) {
        return false;
    }
    
    return true;
}

function formatStudentId($studentId) {
    // Remove any non-digit characters
    $clean = preg_replace('/[^0-9]/', '', $studentId);
    
    // Limit to 6 digits
    $clean = substr($clean, 0, 6);
    
    // Format with hyphen if we have enough digits
    if (strlen($clean) > 2) {
        return substr($clean, 0, 2) . '-' . str_pad(substr($clean, 2), 4, '0', STR_PAD_LEFT);
    }
    
    return $clean;
}

function validatePhoneNumber($phone) {
    return preg_match('/^09\d{2}-\d{3}-\d{4}$/', $phone);
}

function formatPhoneNumber($phone) {
    // Remove any non-digit characters
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // If we have exactly 11 digits and starts with 09
    if (strlen($clean) === 11 && substr($clean, 0, 2) === '09') {
        return substr($clean, 0, 4) . '-' . 
               substr($clean, 4, 3) . '-' . 
               substr($clean, 7);
    }
    
    return $phone;
}

function validateEmail($email) {
    $pattern = '/^[a-z0-9._%+-]+@(gmail\.com|yahoo\.com|yahoo\.com\.ph|outlook\.com|hotmail\.com|isu\.edu\.ph)$/i';
    return preg_match($pattern, strtolower($email));
}

function properNameCase($string) {
    // Handle empty or null values
    if (empty($string)) return '';
    
    // Split the string into words while preserving separators
    $words = preg_split('/(\s+|(?=[,]))/', $string, -1, PREG_SPLIT_NO_EMPTY);
    
    // Process each word
    $result = array_map(function($word) {
        // Remove any extra spaces
        $word = trim($word);
        
        // Skip if empty
        if (empty($word)) return '';
        
        // Handle comma separately
        if ($word === ',') return ', ';
        
        // Capitalize first letter, lowercase the rest
        return ucfirst(strtolower($word));
    }, $words);
    
    // Join words back together
    return implode(' ', array_filter($result));
}

function validateBirthDate($birthDate) {
    $date = new DateTime($birthDate);
    $now = new DateTime();
    $year = $date->format('Y');
    
    // Don't allow birth years 2010 and above
    if ($year >= 2010) {
        return false;
    }
    
    // Calculate age
    $age = $now->diff($date)->y;
    
    // Typically college students are at least 16 years old
    return $age >= 16;
}

function standardizeCitizenship($citizenship) {
    // Remove extra spaces and convert to lowercase for comparison
    $citizenship = trim(strtolower($citizenship));
    
    // Check if it's Filipino
    if ($citizenship === 'filipino' || $citizenship === 'philippines' || $citizenship === 'pilipino') {
        return 'Filipino';
    }
    
    // Any other citizenship is considered "Others"
    return 'Others';
}
?>

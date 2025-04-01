<?php

use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    public function testValidateStudentId()
    {
        // Test valid student IDs
        $this->assertTrue($this->validateStudentId('21-1234'));
        $this->assertTrue($this->validateStudentId('22-5678'));
        
        // Test invalid student IDs
        $this->assertFalse($this->validateStudentId('2-1234'));
        $this->assertFalse($this->validateStudentId('221-234'));
        $this->assertFalse($this->validateStudentId('ab-1234'));
    }

    public function testValidateEmail()
    {
        // Valid email formats
        $this->assertTrue($this->validateEmail('test@gmail.com'));
        $this->assertTrue($this->validateEmail('user@yahoo.com'));
        $this->assertTrue($this->validateEmail('student@isu.edu.ph'));
        $this->assertTrue($this->validateEmail('test@outlook.com'));
        
        // Invalid email formats
        $this->assertFalse($this->validateEmail('test@invalid.com'));
        $this->assertFalse($this->validateEmail('test@example.com'));
        $this->assertFalse($this->validateEmail('invalid-email'));
    }

    public function testValidatePhoneNumber()
    {
        // Valid phone numbers (Philippine format)
        $this->assertTrue($this->validatePhoneNumber('0912-345-6789'));
        $this->assertTrue($this->validatePhoneNumber('0998-765-4321'));
        $this->assertTrue($this->validatePhoneNumber('0917-123-4567'));
        $this->assertTrue($this->validatePhoneNumber('0905-555-5555'));
        
        // Invalid phone numbers
        $this->assertFalse($this->validatePhoneNumber('091-234-5678')); // Wrong prefix length
        $this->assertFalse($this->validatePhoneNumber('0912-34-5678')); // Wrong middle group
        $this->assertFalse($this->validatePhoneNumber('1912-345-6789')); // Doesn't start with 09
        $this->assertFalse($this->validatePhoneNumber('0912.345.6789')); // Wrong separators
        $this->assertFalse($this->validatePhoneNumber('0912-3456-789')); // Wrong grouping
        $this->assertFalse($this->validatePhoneNumber('09123456789')); // No separators
        $this->assertFalse($this->validatePhoneNumber('')); // Empty string
        $this->assertFalse($this->validatePhoneNumber('abc-def-ghij')); // Non-numeric
    }

    private function validateStudentId($studentId) 
    {
        $pattern = '/^\d{2}-\d{4}$/';
        return preg_match($pattern, $studentId) === 1;
    }

    private function validateEmail($email)
    {
        $emailRegex = '/^[a-z0-9._%+-]+@(gmail\.com|yahoo\.com|yahoo\.com\.ph|outlook\.com|hotmail\.com|isu\.edu\.ph)$/i';
        return preg_match($emailRegex, $email) === 1;
    }

    private function validatePhoneNumber($phone)
    {
        $pattern = '/^09\d{2}-\d{3}-\d{4}$/';
        return preg_match($pattern, $phone) === 1;
    }

    public function testValidateBirthday()
    {
        // Valid birthdates (assuming minimum age of 16 and maximum age of 60)
        $this->assertTrue($this->validateBirthday('2005-01-01')); // 18-19 years old
        $this->assertTrue($this->validateBirthday('1990-12-31')); // ~33 years old
        
        // Invalid birthdates
        $this->assertFalse($this->validateBirthday('2020-01-01')); // Too young
        $this->assertFalse($this->validateBirthday('1950-01-01')); // Too old
        $this->assertFalse($this->validateBirthday('invalid-date')); // Invalid format
        $this->assertFalse($this->validateBirthday('2024-13-45')); // Invalid date
    }

    private function validateBirthday($birthday)
    {
        // Check if date is valid
        $date = date_create_from_format('Y-m-d', $birthday);
        if (!$date || $date->format('Y-m-d') !== $birthday) {
            return false;
        }

        // Calculate age
        $today = new DateTime();
        $birthDate = new DateTime($birthday);
        $age = $today->diff($birthDate)->y;

        // Check if age is within acceptable range (16-60 years)
        return ($age >= 16 && $age <= 60);
    }

    public function testDeleteStudent()
    {
        // Test valid student deletion
        $this->assertTrue($this->deleteStudent('21-1234', true)); // Exists in DB
        
        // Test invalid deletion scenarios
        $this->assertFalse($this->deleteStudent('', true)); // Empty student ID
        $this->assertFalse($this->deleteStudent('invalid-id', true)); // Invalid format
        $this->assertFalse($this->deleteStudent('21-9999', true)); // Non-existent student
        $this->assertFalse($this->deleteStudent('21-1234', false)); // DB connection failed
    }

    private function deleteStudent($studentId, $dbConnected = true)
    {
        // Validate student ID format first
        if (!$this->validateStudentId($studentId)) {
            return false;
        }

        // Mock database connection
        if (!$dbConnected) {
            return false;
        }

        // Mock successful deletion for specific test IDs
        $existingIds = ['21-1234', '22-5678'];
        return in_array($studentId, $existingIds);
    }

    public function testEditStudent()
    {
        // Test valid edit data
        $validData = [
            'student_id' => '21-1234',
            'email' => 'john.doe@gmail.com',
            'phone' => '0912-345-6789',
            'first_name' => 'John',
            'middle_name' => 'Smith',
            'last_name' => 'Doe',
            'birthday' => '2000-01-01',
            'year_level' => '1',
            'permanent_address' => 'Test Address',
            'sex' => 'Male',
            'citizenship' => 'Filipino',
            'civil_status' => 'Single'
        ];
        $this->assertTrue($this->validateEditStudent($validData));

        // Test duplicate email
        $duplicateData = $validData;
        $duplicateData['email'] = 'existing@gmail.com';
        $this->assertFalse($this->validateEditStudent($duplicateData), 'Should fail on duplicate email');

        // Test invalid email format
        $invalidEmail = $validData;
        $invalidEmail['email'] = 'invalid@example.com';
        $this->assertFalse($this->validateEditStudent($invalidEmail), 'Should fail on invalid email domain');

        // Test invalid phone format
        $invalidPhone = $validData;
        $invalidPhone['phone'] = '123456789';
        $this->assertFalse($this->validateEditStudent($invalidPhone), 'Should fail on invalid phone format');

        // Test missing required field
        $missingField = $validData;
        unset($missingField['first_name']);
        $this->assertFalse($this->validateEditStudent($missingField), 'Should fail on missing required field');

        // Test invalid birthday
        $invalidBirthday = $validData;
        $invalidBirthday['birthday'] = '2020-01-01';
        $this->assertFalse($this->validateEditStudent($invalidBirthday), 'Should fail on invalid age');
    }

    private function validateEditStudent($data)
    {
        // Check required fields
        $required_fields = ['student_id', 'first_name', 'last_name', 'email', 'phone', 
                          'year_level', 'permanent_address', 'birthday', 'sex', 
                          'citizenship', 'civil_status'];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return false;
            }
        }

        // Validate email
        if (!$this->validateEmail($data['email'])) {
            return false;
        }

        // Check for duplicate email (mock existing emails)
        $existingEmails = ['existing@gmail.com', 'taken@yahoo.com'];
        if (in_array($data['email'], $existingEmails)) {
            return false;
        }

        // Validate phone number
        if (!$this->validatePhoneNumber($data['phone'])) {
            return false;
        }

        // Validate birthday
        if (!$this->validateBirthday($data['birthday'])) {
            return false;
        }

        // Validate student ID
        if (!$this->validateStudentId($data['student_id'])) {
            return false;
        }

        return true;
    }
}
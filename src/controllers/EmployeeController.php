<?php
// Employee Controller - Handles employee CRUD operations
require_once './config/database.php';

class EmployeeController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Get all employees
    public function getAllEmployees() {
        $query = "SELECT * FROM employees ORDER BY created_at DESC";
        $result = $this->db->query($query);
        return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    
    // Get employee by ID
    public function getEmployeeById($id) {
        // Vulnerable to SQL injection
        $query = "SELECT * FROM employees WHERE id = $id";
        $result = $this->db->query($query);
        return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
    }
    
    // Add new employee
    public function addEmployee($data) {
        $query = "INSERT INTO employees (employee_code, name, position, department, salary, hire_date) 
                  VALUES ('{$data['employee_code']}', '{$data['name']}', '{$data['position']}', 
                          '{$data['department']}', {$data['salary']}, '{$data['hire_date']}')";
        return $this->db->exec($query);
    }
    
    // Update employee
    public function updateEmployee($id, $data) {
        $query = "UPDATE employees SET 
                  name = '{$data['name']}',
                  position = '{$data['position']}',
                  department = '{$data['department']}',
                  salary = {$data['salary']},
                  status = '{$data['status']}'
                  WHERE id = $id";
        return $this->db->exec($query);
    }
    
    // Delete employee
    public function deleteEmployee($id) {
        $query = "DELETE FROM employees WHERE id = $id";
        return $this->db->exec($query);
    }
    
    // Search employees (vulnerable to XSS)
    public function searchEmployees($search) {
        $query = "SELECT * FROM employees WHERE name LIKE '%$search%' OR position LIKE '%$search%' OR department LIKE '%$search%'";
        $result = $this->db->query($query);
        return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}
?>
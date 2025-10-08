<?php
// Payroll Controller - Handles payroll processing
require_once './config/database.php';

class PayrollController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Process payroll for employee
    public function processPayroll($employee_id, $amount, $processed_by, $notes = '') {
        $query = "INSERT INTO payroll_history (employee_id, amount, processed_by, notes) 
                  VALUES ($employee_id, $amount, $processed_by, '$notes')";
        return $this->db->exec($query);
    }
    
    // Get payroll history
    public function getPayrollHistory($employee_id = null) {
        if ($employee_id) {
            $query = "SELECT ph.*, e.name as employee_name, u.username as processed_by_name 
                      FROM payroll_history ph 
                      JOIN employees e ON ph.employee_id = e.id 
                      JOIN users u ON ph.processed_by = u.id 
                      WHERE ph.employee_id = $employee_id 
                      ORDER BY ph.pay_date DESC";
        } else {
            $query = "SELECT ph.*, e.name as employee_name, u.username as processed_by_name 
                      FROM payroll_history ph 
                      JOIN employees e ON ph.employee_id = e.id 
                      JOIN users u ON ph.processed_by = u.id 
                      ORDER BY ph.pay_date DESC";
        }
        
        $result = $this->db->query($query);
        return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    
    // Get total payroll amount
    public function getTotalPayroll($month = null, $year = null) {
        $where = "";
        if ($month && $year) {
            $where = "WHERE MONTH(pay_date) = $month AND YEAR(pay_date) = $year";
        }
        
        $query = "SELECT SUM(amount) as total FROM payroll_history $where";
        $result = $this->db->query($query);
        $row = $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
        return $row ? $row['total'] : 0;
    }
}
?>
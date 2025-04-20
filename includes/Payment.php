<?php
class Payment {
    private $conn;
    private $table = 'payments';

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Generate GCash payment reference ID in format FINEYYYYMMDD-STU{studentId}
     */
    private function generateGcashReferenceNumber($studentId) {
        $date = date('Ymd');
        return "FINE{$date}-STU{$studentId}";
    }

    /**
     * Record a new payment
     */
    public function recordPayment($data, $proofPath = null) {
        try {
            $this->conn->begin_transaction();

            // Generate reference number based on method
            if (isset($data['method']) && $data['method'] === 'gcash' && isset($data['student_id'])) {
                $referenceNumber = $this->generateGcashReferenceNumber($data['student_id']);
            } else {
                $referenceNumber = $this->generateReferenceNumber();
            }

            $sql = "INSERT INTO {$this->table} (reference_number, user_id, loan_id, amount, method, status, transaction_proof, reference_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $status = ($data['method'] === 'cash') ? 'verified' : 'pending';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "siidssss",
                $referenceNumber,
                $data['user_id'],
                $data['loan_id'],
                $data['amount'],
                $data['method'],
                $status,
                $proofPath,
                $referenceNumber
            );
            $stmt->execute();

            // Log the activity
            $this->logActivity($data['user_id'], 'payment_recorded', "Payment recorded: {$referenceNumber}");

            $this->conn->commit();
            return $referenceNumber;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error recording payment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record a cash payment (automatically verified)
     */
    public function recordCashPayment($data) {
        try {
            $this->conn->begin_transaction();

            // Generate reference number
            $referenceNumber = $this->generateReferenceNumber();

            // Insert payment record
            $sql = "INSERT INTO {$this->table} (reference_number, user_id, loan_id, amount, method, status, reference_id) 
                    VALUES (?, ?, ?, ?, 'cash', 'verified', ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "siids",
                $referenceNumber,
                $data['user_id'],
                $data['loan_id'],
                $data['amount'],
                $referenceNumber
            );
            $stmt->execute();

            // Update loan fine status
            $sql = "UPDATE loans 
                    SET fine_paid = fine_paid + ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("di", $data['amount'], $data['loan_id']);
            $stmt->execute();

            // Log the activity
            $this->logActivity($data['user_id'], 'cash_payment_recorded', "Cash payment recorded: {$referenceNumber}");

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error recording cash payment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify a payment
     */
    public function verifyPayment($paymentId) {
        try {
            $this->conn->begin_transaction();

            // Get payment details
            $payment = $this->getById($paymentId);
            if (!$payment) {
                throw new Exception("Payment not found");
            }

            // Update payment status
            $sql = "UPDATE {$this->table} SET status = 'verified' WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $paymentId);
            $stmt->execute();

            // Update loan fine status
            $sql = "UPDATE loans 
                    SET fine_paid = fine_paid + ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("di", $payment['amount'], $payment['loan_id']);
            $stmt->execute();

            // Send confirmation email
            $this->sendPaymentConfirmation($payment);

            // Log the activity
            $this->logActivity($payment['user_id'], 'payment_verified', "Payment verified: {$payment['reference_number']}");

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error verifying payment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject a payment
     */
    public function rejectPayment($paymentId, $reason) {
        try {
            // Get payment details
            $payment = $this->getById($paymentId);
            if (!$payment) {
                throw new Exception("Payment not found");
            }

            // Update payment status
            $sql = "UPDATE {$this->table} 
                    SET status = 'rejected', rejection_reason = ? 
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $reason, $paymentId);
            $stmt->execute();

            // Send rejection notification
            $this->sendPaymentRejection($payment, $reason);

            // Log the activity
            $this->logActivity($payment['user_id'], 'payment_rejected', "Payment rejected: {$payment['reference_number']}");

            return true;
        } catch (Exception $e) {
            error_log("Error rejecting payment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all payments with filtering and pagination
     */
    public function getAll($search = '', $status = '', $method = '', $sort = 'created_at', $order = 'desc', $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $where = ["1=1"];
            $params = [];
            $types = "";

            if ($search) {
                $where[] = "(p.reference_number LIKE ? OR u.name LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm]);
                $types .= "ss";
            }

            if ($status) {
                $where[] = "p.status = ?";
                $params[] = $status;
                $types .= "s";
            }

            if ($method) {
                $where[] = "p.method = ?";
                $params[] = $method;
                $types .= "s";
            }

            $whereClause = implode(" AND ", $where);
            $orderBy = in_array($sort, ['created_at', 'amount', 'reference_number']) ? $sort : 'created_at';
            $orderDir = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

            $sql = "SELECT p.*, u.name as user_name, u.student_id
                    FROM {$this->table} p
                    JOIN users u ON p.user_id = u.id
                    WHERE {$whereClause}
                    ORDER BY p.{$orderBy} {$orderDir}
                    LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";

            $stmt = $this->conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting payments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total number of payments (for pagination)
     */
    public function getTotal($search = '', $status = '', $method = '') {
        try {
            $where = ["1=1"];
            $params = [];
            $types = "";

            if ($search) {
                $where[] = "(p.reference_number LIKE ? OR u.name LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm]);
                $types .= "ss";
            }

            if ($status) {
                $where[] = "p.status = ?";
                $params[] = $status;
                $types .= "s";
            }

            if ($method) {
                $where[] = "p.method = ?";
                $params[] = $method;
                $types .= "s";
            }

            $whereClause = implode(" AND ", $where);
            $sql = "SELECT COUNT(*) as total 
                    FROM {$this->table} p
                    JOIN users u ON p.user_id = u.id
                    WHERE {$whereClause}";
            
            $stmt = $this->conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return (int)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting total payments: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get payment by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT p.*, u.name as user_name, u.email
                    FROM {$this->table} p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error getting payment by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get total fines
     */
    public function getTotalFines() {
        try {
            $sql = "SELECT COALESCE(SUM(fine_amount), 0) as total FROM loans";
            $result = $this->conn->query($sql);
            return (float)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting total fines: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get collected fines
     */
    public function getCollectedFines() {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                    FROM {$this->table} 
                    WHERE status = 'verified'";
            $result = $this->conn->query($sql);
            return (float)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting collected fines: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get pending fines
     */
    public function getPendingFines() {
        try {
            $sql = "SELECT COALESCE(SUM(fine_amount - fine_paid), 0) as total 
                    FROM loans 
                    WHERE fine_amount > fine_paid";
            $result = $this->conn->query($sql);
            return (float)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting pending fines: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get today's collections
     */
    public function getTodayCollections() {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                    FROM {$this->table} 
                    WHERE status = 'verified' 
                    AND DATE(created_at) = CURRENT_DATE";
            $result = $this->conn->query($sql);
            return (float)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting today's collections: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generate unique reference number
     */
    private function generateReferenceNumber() {
        $prefix = 'PAY';
        $timestamp = date('YmdHis');
        $random = rand(1000, 9999);
        return $prefix . $timestamp . $random;
    }

    /**
     * Send payment confirmation email
     */
    private function sendPaymentConfirmation($payment) {
        try {
            $to = $payment['email'];
            $subject = "Payment Confirmation - " . $payment['reference_number'];
            
            ob_start();
            include '../templates/emails/payment_confirmation.php';
            $message = ob_get_clean();

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: ' . LIBRARY_EMAIL . "\r\n";

            mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log("Error sending payment confirmation: " . $e->getMessage());
        }
    }

    /**
     * Send payment rejection notification
     */
    private function sendPaymentRejection($payment, $reason) {
        try {
            $to = $payment['email'];
            $subject = "Payment Rejected - " . $payment['reference_number'];
            
            $message = "Dear " . $payment['user_name'] . ",\n\n";
            $message .= "Your payment (Reference: " . $payment['reference_number'] . ") has been rejected.\n";
            $message .= "Reason: " . $reason . "\n\n";
            $message .= "Please contact the library for assistance.\n\n";
            $message .= "Best regards,\nLibrary Team";

            $headers = "From: " . LIBRARY_EMAIL . "\r\n";
            $headers .= "Reply-To: " . LIBRARY_EMAIL . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log("Error sending payment rejection: " . $e->getMessage());
        }
    }

    /**
     * Log activity
     */
    private function logActivity($userId, $action, $details) {
        try {
            $sql = "INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iss", $userId, $action, $details);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }

    /**
     * Get report data for payments
     */
    public function getReportData($startDate = null, $endDate = null) {
        try {
            $where = ["1=1"];
            $params = [];
            $types = "";

            if ($startDate) {
                $where[] = "p.created_at >= ?";
                $params[] = $startDate;
                $types .= "s";
            }

            if ($endDate) {
                $where[] = "p.created_at <= ?";
                $params[] = $endDate;
                $types .= "s";
            }

            $whereClause = implode(" AND ", $where);

            $sql = "SELECT p.*,
                    u.name as user_name,
                    u.student_id,
                    u.email,
                    l.book_id,
                    b.title as book_title,
                    b.isbn
                    FROM {$this->table} p
                    JOIN users u ON p.user_id = u.id
                    JOIN loans l ON p.loan_id = l.id
                    JOIN books b ON l.book_id = b.id
                    WHERE {$whereClause}
                    ORDER BY p.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting payment report data: " . $e->getMessage());
            return [];
        }
    }
}

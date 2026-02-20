<?php
/**
 * Helper Functions and Utilities
 */

class Helper {
    /**
     * Format date to readable format
     */
    public static function formatDate($date, $format = 'M d, Y') {
        return date($format, strtotime($date));
    }

    /**
     * Format number with thousands separator
     */
    public static function formatNumber($number, $decimals = 2) {
        return number_format($number, $decimals);
    }

    /**
     * Sanitize input
     */
    public static function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate date format (YYYY-MM-DD)
     */
    public static function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Check if value is numeric
     */
    public static function isNumeric($value) {
        return is_numeric($value) && $value >= 0;
    }

    /**
     * Generate random string
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Get file extension
     */
    public static function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
<<<<<<< HEAD
     * Get Month name from number
     */
    public static function getMonthName($month){
        $month = (int)$month; // ensure integer
        $dateObj = DateTime::createFromFormat('!m', $month);
        if (!$dateObj) {
            return "Unknown"; // prevents fatal error
        }
        return $dateObj->format('F');
    }

    /**
     * Get Quarter from month
     */
    public static function getQuarter($month){
        $val = ceil($month / 3);
        switch ($val) {
            case 1:
                return "Q1 (Jan-Mar)";
            case 2:
                return "Q2 (Apr-Jun)";
            case 3:
                return "Q3 (Jul-Sep)";
            case 4:
                return "Q4 (Oct-Dec)";
            default:
                print_r("Invalid month");
                break;
        }
    }

    /**
=======
>>>>>>> 3c4d3d619b6602e11c434c946c294534539ab9d3
     * Format file size
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get time ago from timestamp
     */
    public static function timeAgo($timestamp) {
        $time_ago = strtotime($timestamp);
        $current_time = time();
        $time_difference = $current_time - $time_ago;
        
        if ($time_difference < 1) {
            return 'just now';
        }
        
        $condition = [
            12 * 30 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];
        
        foreach ($condition as $secs => $str) {
            $d = $time_difference / $secs;
            if ($d >= 1) {
                $t = round($d);
                return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
            }
        }
    }

    /**
     * Redirect to URL
     */
    public static function redirect($url) {
        header('Location: ' . $url);
        exit();
    }

    /**
     * Get query string
     */
    public static function getQuery($key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get post data
     */
    public static function getPost($key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    /**
     * Check if request method is POST
     */
    public static function isPostRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Check if request method is GET
     */
    public static function isGetRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Get client IP
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Log activity
     */
    public static function logActivity($conn, $action, $report_name = '') {
        if (Auth::isLoggedIn()) {
            $user = Auth::getCurrentUser();
            $timestamp = date('Y-m-d H:i:s');
            $ip = self::getClientIP();
            
            $stmt = $conn->prepare("INSERT INTO activity_log (username, campus, action, report_name, timestamp) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssss", $user['username'], $user['campus'], $action, $report_name, $timestamp);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    /**
     * Calculate emissions
     */
    public static function calculateElectricityEmissions($kwh, $factor = 0.92) {
        // kg CO2 per kWh (Australia average)
        return $kwh * $factor;
    }

    /**
     * Get pagination
     */
    public static function getPagination($page, $total_records, $records_per_page = 10) {
        $total_pages = ceil($total_records / $records_per_page);
        $page = max(1, min($page, $total_pages));
        $offset = ($page - 1) * $records_per_page;
        
        return [
            'page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'offset' => $offset,
            'limit' => $records_per_page
        ];
    }
}

/**
 * Response class for API endpoints
 */
class Response {
    public static function success($data = [], $message = 'Success') {
        http_response_code(200);
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public static function error($message = 'Error', $code = 400) {
        http_response_code($code);
        return self::json([
            'success' => false,
            'message' => $message
        ]);
    }

    public static function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}

/**
 * Data validation class
 */
class Validator {
    private $errors = [];

    public function validate($field, $value, $rules) {
        $rule_array = explode('|', $rules);
        
        foreach ($rule_array as $rule) {
            $rule_name = trim($rule);
            
            if ($rule_name === 'required' && empty($value)) {
                $this->errors[$field] = ucfirst($field) . ' is required';
                break;
            }
            
            if (strpos($rule_name, 'min:') === 0) {
                $min = (int)str_replace('min:', '', $rule_name);
                if (strlen($value) < $min) {
                    $this->errors[$field] = ucfirst($field) . ' must be at least ' . $min . ' characters';
                    break;
                }
            }
            
            if (strpos($rule_name, 'max:') === 0) {
                $max = (int)str_replace('max:', '', $rule_name);
                if (strlen($value) > $max) {
                    $this->errors[$field] = ucfirst($field) . ' must not exceed ' . $max . ' characters';
                    break;
                }
            }
            
            if ($rule_name === 'email' && !Helper::validateEmail($value)) {
                $this->errors[$field] = 'Invalid email format';
                break;
            }
            
            if ($rule_name === 'date' && !Helper::validateDate($value)) {
                $this->errors[$field] = 'Invalid date format (YYYY-MM-DD)';
                break;
            }
            
            if ($rule_name === 'numeric' && !Helper::isNumeric($value)) {
                $this->errors[$field] = ucfirst($field) . ' must be a number';
                break;
            }
        }
        
        return $this;
    }

    public function passes() {
        return empty($this->errors);
    }

    public function fails() {
        return !empty($this->errors);
    }

    public function errors() {
        return $this->errors;
    }

    public function getError($field) {
        return $this->errors[$field] ?? null;
    }
}
?>

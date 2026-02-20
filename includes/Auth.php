<?php
/**
 * Authentication and Session Management
 */

session_start();

class Auth {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Login user
     */
    public function login($username, $password) {
        try {
            $conn = $this->db->getConnection();
            
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT * FROM tblsignin WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }

            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && $password === $user['password']) {
                // Set session variables
                $_SESSION['loggedIn'] = true;
                $_SESSION['username'] = $user['username'];
                $_SESSION['office'] = $user['office'];
                $_SESSION['campus'] = $user['campus'];
                $_SESSION['user_id'] = $user['id'];
                
                // Set campus logo
                $campus_logo_map = [
                    "Alangilan" => "alangilan.png",
                    "ARASOF-Nasugbu" => "arasof-nasugbu.png",
                    "Balayan" => "balayan.png",
                    "Central" => "central.png",
                    "JPLPC-Malvar" => "jplpc-malvar.png",
                    "Lipa" => "lipa.png",
                    "Lemery" => "lemery.png",
                    "Lobo" => "lobo.png",
                    "Mabini" => "mabini.png",
                    "Pablo Borbon" => "pablo borbon.png",
                    "Rosario" => "rosario.png",
                    "San Juan" => "san juan.png"
                ];
                
                $_SESSION['logo'] = "images/campuses/" . ($campus_logo_map[$user['campus']] ?? 'default.png');
                
                $stmt->close();
                return true;
            }

            $stmt->close();
            return false;

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return true;
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;
    }

    /**
     * Get current user
     */
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'username' => $_SESSION['username'] ?? '',
                'office' => $_SESSION['office'] ?? '',
                'campus' => $_SESSION['campus'] ?? '',
                'user_id' => $_SESSION['user_id'] ?? ''
            ];
        }
        return null;
    }

    /**
     * Require login - redirect if not logged in
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: /ghg/login.php");
            exit();
        }
    }

    /**
     * Get office redirect
     */
    public static function getOfficeRedirect($office) {
        $office_redirects = [
            'Central Sustainable Office' => 'csd_dashboard_v2.php',
            'Sustainable Development Office' => 'sdo_dashboard_v2.php',
            'Environmental Management Unit' => 'emu_dashboard_v2.php',
            'Resource Generation Office' => 'rgo_dashboard_v2.php',
            'General Services Office' => 'gso_dashboard_v2.php',
            'Procurement Office' => 'procurement_dashboard_v2.php'
        ];
        
        return $office_redirects[$office] ?? 'emu_dashboard_v2.php';
    }

    /**
     * Change password
     */
    public function changePassword($username, $newPassword) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("UPDATE tblsignin SET password = ? WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }

            $stmt->bind_param("ss", $newPassword, $username);
            $result = $stmt->execute();

            $stmt->close();
            return $result;

        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return false;
        }
    }
}
?>

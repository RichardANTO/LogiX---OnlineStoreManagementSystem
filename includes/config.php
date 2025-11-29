<?php
// Database Configuration for XAMPP (assuming default settings)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Default XAMPP username
define('DB_PASSWORD', '');     // Default XAMPP password (empty)
define('DB_NAME', 'online_store_db');

// Connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Start a session for user management and authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Executes a secure database query using prepared statements or direct execution.
 * @param string $sql The SQL query string.
 * @param string $types String containing types of parameters (e.g., 'ssd').
 * @param array $params Array of parameters to bind.
 * @return mixed mysqli_result object for SELECT, boolean for other queries, or false on failure.
 */
function secure_query($sql, $types = '', $params = []) {
    global $link;
    
    // 🔑 CORRECTED LOGIC: Handle simple queries (like TRUNCATE or SET) that don't need parameters
    if (empty($types) && empty($params)) {
        if ($result = $link->query($sql)) {
            // Return true for non-select queries, or the result object for simple SELECTs
            return $result === true ? true : $result;
        } else {
            error_log("Direct query failed: (" . $link->errno . ") " . $link->error . " for SQL: " . $sql);
            return false;
        }
    }
    
    // --- EXISTING PREPARED STATEMENT LOGIC BELOW ---
    
    // Check if the query is for selecting data (SELECT) or for manipulation (INSERT/UPDATE/DELETE)
    $is_select = stripos(trim($sql), 'SELECT') === 0;

    if ($stmt = $link->prepare($sql)) {
        if (!empty($types) && !empty($params)) {
            // Bind parameters dynamically
            $stmt->bind_param($types, ...$params); 
        }

        if ($stmt->execute()) {
            if ($is_select) {
                // Return result set for SELECT queries
                return $stmt->get_result();
            } else {
                // Return success for manipulation queries
                return true;
            }
        } else {
            error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
            return false;
        }
    } else {
        error_log("Prepare failed: (" . $link->errno . ") " . $link->error . " for SQL: " . $sql);
        return false;
    }
}

// Utility function to check if the user is logged in
function is_logged_in() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

// Utility function to check if the user is an admin
function is_admin() {
    return is_logged_in() && $_SESSION["role"] === 'admin';
}
?>
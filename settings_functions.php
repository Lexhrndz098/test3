<?php
// Functions for managing system settings

/**
 * Get a setting value from the database
 * 
 * @param string $setting_name The name of the setting to retrieve
 * @param mixed $default Default value if setting is not found
 * @return mixed The setting value or default if not found
 */
function get_setting($setting_name, $default = null) {
    global $conn;
    
    $sql = "SELECT setting_value FROM system_settings WHERE setting_name = ?";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $setting_name);
        
        if($stmt->execute()) {
            $stmt->store_result();
            
            if($stmt->num_rows == 1) {
                $stmt->bind_result($setting_value);
                $stmt->fetch();
                return $setting_value;
            }
        }
        
        $stmt->close();
    }
    
    return $default;
}

/**
 * Update a setting value in the database
 * 
 * @param string $setting_name The name of the setting to update
 * @param mixed $setting_value The new value for the setting
 * @return bool True if successful, false otherwise
 */
function update_setting($setting_name, $setting_value) {
    global $conn;
    
    $sql = "UPDATE system_settings SET setting_value = ? WHERE setting_name = ?";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $setting_value, $setting_name);
        
        if($stmt->execute()) {
            return $stmt->affected_rows > 0;
        }
        
        $stmt->close();
    }
    
    return false;
}

/**
 * Update multiple settings at once
 * 
 * @param array $settings Associative array of setting_name => setting_value
 * @return bool True if all updates were successful, false otherwise
 */
function update_settings($settings) {
    global $conn;
    $success = true;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        foreach($settings as $name => $value) {
            if(!update_setting($name, $value)) {
                $success = false;
                break;
            }
        }
        
        if($success) {
            $conn->commit();
        } else {
            $conn->rollback();
        }
    } catch (Exception $e) {
        $conn->rollback();
        $success = false;
    }
    
    return $success;
}

/**
 * Check if maintenance mode is enabled
 * 
 * @return bool True if maintenance mode is enabled, false otherwise
 */
function is_maintenance_mode() {
    return get_setting('maintenance_mode', '0') === '1';
}
?>
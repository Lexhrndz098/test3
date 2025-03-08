<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }
        .success {
            color: green;
            font-size: 14px;
            margin-top: 5px;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
        .info-text {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Student Registration</h2>
        <div class="info-text">
            <p>Note: You must use an approved email address provided by your administrator to register.</p>
        </div>
        <?php
        // Include database connection
        require_once 'db_connect.php';
        
        // Initialize variables
        $username = $email = $password = $confirm_password = $lrn = $grade_level = $section = $adviser = "";
        $username_err = $email_err = $password_err = $confirm_password_err = $lrn_err = $grade_level_err = $section_err = $adviser_err = "";
        $success_msg = "";
        
        // Process form data when form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            
            // Validate username
            if (empty(trim($_POST["username"]))) {
                $username_err = "Please enter a username.";
            } else {
                // Prepare a select statement
                $sql = "SELECT id FROM users WHERE username = ?";
                
                if ($stmt = $conn->prepare($sql)) {
                    // Bind variables to the prepared statement as parameters
                    $stmt->bind_param("s", $param_username);
                    
                    // Set parameters
                    $param_username = trim($_POST["username"]);
                    
                    // Attempt to execute the prepared statement
                    if ($stmt->execute()) {
                        // Store result
                        $stmt->store_result();
                        
                        if ($stmt->num_rows == 1) {
                            $username_err = "This username is already taken.";
                        } else {
                            $username = trim($_POST["username"]);
                        }
                    } else {
                        echo "Oops! Something went wrong. Please try again later.";
                    }
                    
                    // Close statement
                    $stmt->close();
                }
            }
            
            // Validate email
            if (empty(trim($_POST["email"]))) {
                $email_err = "Please enter an email.";
            } else {
                // Check if email is valid
                if (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
                    $email_err = "Please enter a valid email address.";
                } else {
                    // Check if email is in the approved list
                    $sql = "SELECT id, is_used FROM approved_emails WHERE email = ?";
                    
                    if ($stmt = $conn->prepare($sql)) {
                        // Bind variables to the prepared statement as parameters
                        $stmt->bind_param("s", $param_email);
                        
                        // Set parameters
                        $param_email = trim($_POST["email"]);
                        
                        // Attempt to execute the prepared statement
                        if ($stmt->execute()) {
                            // Store result
                            $stmt->store_result();
                            
                            if ($stmt->num_rows == 0) {
                                $email_err = "This email is not approved for registration. Please contact your administrator.";
                            } else {
                                // Bind result variables
                                $stmt->bind_result($approved_id, $is_used);
                                $stmt->fetch();
                                
                                if ($is_used) {
                                    $email_err = "This approved email has already been used for registration.";
                                } else {
                                    // Check if email already exists in users table
                                    $stmt->close();
                                    
                                    $sql = "SELECT id FROM users WHERE email = ?";
                                    if ($stmt = $conn->prepare($sql)) {
                                        $stmt->bind_param("s", $param_email);
                                        $param_email = trim($_POST["email"]);
                                        
                                        if ($stmt->execute()) {
                                            $stmt->store_result();
                                            
                                            if ($stmt->num_rows == 1) {
                                                $email_err = "This email is already registered.";
                                            } else {
                                                $email = trim($_POST["email"]);
                                            }
                                        } else {
                                            echo "Oops! Something went wrong. Please try again later.";
                                        }
                                        $stmt->close();
                                    }
                                }
                            }
                        } else {
                            echo "Oops! Something went wrong. Please try again later.";
                        }
                    }
                }
            }
            
            // Validate password
            if (empty(trim($_POST["password"]))) {
                $password_err = "Please enter a password.";
            } elseif (strlen(trim($_POST["password"])) < 6) {
                $password_err = "Password must have at least 6 characters.";
            } else {
                $password = trim($_POST["password"]);
            }
            
            // Validate confirm password
            if (empty(trim($_POST["confirm_password"]))) {
                $confirm_password_err = "Please confirm password.";
            } else {
                $confirm_password = trim($_POST["confirm_password"]);
                if (empty($password_err) && ($password != $confirm_password)) {
                    $confirm_password_err = "Password did not match.";
                }
            }
            
            // Validate LRN
            if (empty(trim($_POST["lrn"]))) {
                $lrn_err = "Please enter your Learner Reference Number (LRN).";
            } elseif (strlen(trim($_POST["lrn"])) != 12 || !is_numeric(trim($_POST["lrn"]))) {
                $lrn_err = "LRN must be exactly 12 digits.";
            } else {
                // Check if LRN already exists
                $sql = "SELECT profile_id FROM user_profiles WHERE lrn = ?";
                
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("s", $param_lrn);
                    $param_lrn = trim($_POST["lrn"]);
                    
                    if ($stmt->execute()) {
                        $stmt->store_result();
                        
                        if ($stmt->num_rows > 0) {
                            $lrn_err = "This LRN is already registered.";
                        } else {
                            $lrn = trim($_POST["lrn"]);
                        }
                    } else {
                        echo "Oops! Something went wrong. Please try again later.";
                    }
                    $stmt->close();
                }
            }
            
            // Validate Grade Level
            if (empty(trim($_POST["grade_level"]))) {
                $grade_level_err = "Please enter your Grade Level.";
            } else {
                $grade_level = trim($_POST["grade_level"]);
            }
            
            // Validate Section
            if (empty(trim($_POST["section"]))) {
                $section_err = "Please enter your Section.";
            } else {
                $section = trim($_POST["section"]);
            }
            
            // Validate Adviser
            if (empty(trim($_POST["adviser"]))) {
                $adviser_err = "Please enter your Adviser's name.";
            } else {
                $adviser = trim($_POST["adviser"]);
            }
            
            // Check input errors before inserting in database
            if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && 
                empty($lrn_err) && empty($grade_level_err) && empty($section_err) && empty($adviser_err)) {
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert into users table
                    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                    
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("sss", $param_username, $param_email, $param_password);
                        
                        $param_username = $username;
                        $param_email = $email;
                        $param_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        $stmt->execute();
                        $user_id = $conn->insert_id;
                        $stmt->close();
                        
                        // Insert into user_profiles table
                        $sql = "INSERT INTO user_profiles (user_id, lrn, grade_level, section, adviser) VALUES (?, ?, ?, ?, ?)";
                        
                        if ($stmt = $conn->prepare($sql)) {
                            $stmt->bind_param("issss", $param_user_id, $param_lrn, $param_grade_level, $param_section, $param_adviser);
                            
                            $param_user_id = $user_id;
                            $param_lrn = $lrn;
                            $param_grade_level = $grade_level;
                            $param_section = $section;
                            $param_adviser = $adviser;
                            
                            $stmt->execute();
                            $stmt->close();
                            
                            // Update approved_emails table to mark email as used
                            $sql = "UPDATE approved_emails SET is_used = 1 WHERE email = ?";
                            
                            if ($stmt = $conn->prepare($sql)) {
                                $stmt->bind_param("s", $param_email);
                                $param_email = $email;
                                $stmt->execute();
                                $stmt->close();
                            }
                            
                            // Commit transaction
                            $conn->commit();
                            
                            // Success message
                            $success_msg = "Registration successful! You can now login.";
                            $username = $email = $password = $confirm_password = $lrn = $grade_level = $section = $adviser = "";
                        }
                    }
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    echo "Oops! Something went wrong. Please try again later. Error: " . $e->getMessage();
                }
            }
            
            // Close connection
            $conn->close();
        }
        ?>
        
        <?php if (!empty($success_msg)): ?>
            <div class="success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo $username; ?>">
                <span class="error"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Email (Must be approved by administrator)</label>
                <input type="email" name="email" value="<?php echo $email; ?>">
                <span class="error"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password">
                <span class="error"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password">
                <span class="error"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Learner Reference Number (LRN)</label>
                <input type="text" name="lrn" maxlength="12" value="<?php echo $lrn; ?>" placeholder="12-digit LRN">
                <span class="error"><?php echo $lrn_err; ?></span>
            </div>
            <div class="form-group">
                <label>Grade Level</label>
                <select name="grade_level">
                    <option value="" <?php echo empty($grade_level) ? 'selected' : ''; ?>>Select Grade Level</option>
                    <option value="Grade 7" <?php echo ($grade_level == "Grade 7") ? 'selected' : ''; ?>>Grade 7</option>
                    <option value="Grade 8" <?php echo ($grade_level == "Grade 8") ? 'selected' : ''; ?>>Grade 8</option>
                    <option value="Grade 9" <?php echo ($grade_level == "Grade 9") ? 'selected' : ''; ?>>Grade 9</option>
                    <option value="Grade 10" <?php echo ($grade_level == "Grade 10") ? 'selected' : ''; ?>>Grade 10</option>
                    <option value="Grade 11" <?php echo ($grade_level == "Grade 11") ? 'selected' : ''; ?>>Grade 11</option>
                    <option value="Grade 12" <?php echo ($grade_level == "Grade 12") ? 'selected' : ''; ?>>Grade 12</option>
                </select>
                <span class="error"><?php echo $grade_level_err; ?></span>
            </div>
            <div class="form-group">
                <label>Section</label>
                <input type="text" name="section" value="<?php echo $section; ?>">
                <span class="error"><?php echo $section_err; ?></span>
            </div>
            <div class="form-group">
                <label>Adviser</label>
                <input type="text" name="adviser" value="<?php echo $adviser; ?>">
                <span class="error"><?php echo $adviser_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Register">
            </div>
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a>.</p>
            </div>
        </form>
    </div>
</body>
</html>
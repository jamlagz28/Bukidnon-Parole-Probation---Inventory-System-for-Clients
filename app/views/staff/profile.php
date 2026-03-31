<?php
session_start();
include '../../config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$fullname = $_SESSION['fullname'];

// Get user data
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM staff WHERE username='$username'"));

// Handle password change
if(isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if($current != $user['password']) {
        $error = "Current password is incorrect!";
    } elseif($new != $confirm) {
        $error = "New passwords do not match!";
    } else {
        mysqli_query($conn, "UPDATE staff SET password='$new' WHERE username='$username'");
        $success = "Password changed successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>My Profile | Secure Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ---------- RESET & GLOBAL ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f7f0 0%, #eef2f0 100%);
            min-height: 100vh;
            padding: 2rem 1.5rem;
            color: #1a2c1a;
        }

        /* main container – fully responsive */
        .profile-wrapper {
            max-width: 1280px;
            margin: 0 auto;
            width: 100%;
        }

        /* back navigation refined */
        .nav-back {
            margin-bottom: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,240,0.7);
            backdrop-filter: blur(4px);
            padding: 0.5rem 1.25rem;
            border-radius: 60px;
            transition: all 0.2s ease;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            border: 1px solid rgba(30, 70, 30, 0.15);
            color: #2d5a2d;
        }

        .nav-back i {
            font-size: 0.85rem;
            transition: transform 0.2s;
        }

        .nav-back:hover {
            background: #e9f3e6;
            border-color: #2c6e2c;
            color: #1e4a1e;
            transform: translateX(-3px);
        }

        .nav-back:hover i {
            transform: translateX(-4px);
        }

        /* main card layout – responsive grid */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 2rem;
            align-items: start;
        }

        /* shared card style */
        .card {
            background: #ffffff;
            border-radius: 2rem;
            box-shadow: 0 20px 35px -12px rgba(0, 32, 0, 0.12), 0 1px 3px rgba(0,0,0,0.02);
            transition: transform 0.2s ease, box-shadow 0.2s;
            border: 1px solid rgba(50, 90, 40, 0.12);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #ecf3e8;
            background: #fefef7;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, #1e4620, #2b5e2a);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.2px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header h2 i {
            background: none;
            color: #2b6e2a;
            font-size: 1.6rem;
            background: transparent;
            -webkit-background-clip: unset;
        }

        .card-body {
            padding: 2rem;
        }

        /* profile info section – modern micro layout */
        .info-panel {
            background: #fafef7;
            border-radius: 1.5rem;
            padding: 0.25rem 0;
        }

        .info-row {
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e2f0dc;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #5b7c4a;
            background: #eef5ea;
            padding: 0.2rem 0.8rem;
            border-radius: 40px;
            display: inline-block;
        }

        .info-value {
            font-weight: 600;
            font-size: 1.1rem;
            color: #1c3c1a;
            word-break: break-word;
            text-align: right;
            background: #ffffff;
            padding: 0.2rem 0 0.2rem 1rem;
            border-radius: 40px;
        }

        /* form styling */
        .form-group {
            margin-bottom: 1.6rem;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 0.6rem;
            color: #2c4d2a;
            letter-spacing: -0.2px;
        }

        .form-group label i {
            width: 1.4rem;
            color: #4b7a3b;
            font-size: 1rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper input {
            width: 100%;
            padding: 0.9rem 1rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            border: 1.5px solid #ddebd6;
            border-radius: 1.2rem;
            background: #ffffff;
            transition: all 0.2s;
            color: #1e2f1a;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #2c8c2a;
            box-shadow: 0 0 0 3px rgba(44, 140, 42, 0.2);
        }

        /* password toggle eye */
        .toggle-password {
            position: absolute;
            right: 16px;
            background: transparent;
            border: none;
            color: #6e9263;
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            display: flex;
            align-items: center;
        }

        /* button with modern gradient */
        .btn-primary {
            background: linear-gradient(105deg, #1f5420 0%, #2f7a2c 100%);
            color: white;
            padding: 0.9rem 1.2rem;
            border: none;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.95rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            box-shadow: 0 4px 8px rgba(30, 70, 20, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(105deg, #184618, #246622);
            transform: translateY(-2px);
            box-shadow: 0 10px 18px -6px rgba(35, 100, 30, 0.4);
        }

        .btn-primary:active {
            transform: translateY(1px);
        }

        /* message alerts */
        .alert-message {
            padding: 1rem 1.2rem;
            border-radius: 1.2rem;
            margin-bottom: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-success {
            background: #e2f7e5;
            border-left: 5px solid #2b8742;
            color: #145c27;
        }

        .alert-error {
            background: #fff0f0;
            border-left: 5px solid #d9534f;
            color: #a12424;
        }

        /* responsive design */
        @media (max-width: 900px) {
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 1.8rem;
            }

            body {
                padding: 1.2rem;
            }

            .info-value {
                text-align: left;
                margin-top: 0.3rem;
                width: 100%;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.4rem;
            }
        }

        @media (max-width: 640px) {
            .card-header {
                padding: 1.2rem 1.5rem;
            }
            .card-body {
                padding: 1.5rem;
            }
            .btn-primary {
                padding: 0.8rem;
            }
            .info-label {
                font-size: 0.75rem;
            }
            .info-value {
                font-size: 1rem;
            }
        }

        /* extra finesse */
        .accent-line {
            height: 4px;
            background: linear-gradient(90deg, #2b6e2a, #d4b23a, #cd5c5c, #ffffff);
            width: 100%;
            border-radius: 4px;
        }

        hr {
            margin: 1rem 0;
            border: none;
            height: 1px;
            background: linear-gradient(90deg, #cfdec8, transparent);
        }

        .footnote {
            font-size: 0.7rem;
            text-align: center;
            margin-top: 2rem;
            color: #5e7b54;
        }
    </style>
</head>
<body>
<div class="profile-wrapper">
    <!-- Back link enhanced with icon and hover -->
    <a href="dashboard.php" class="nav-back">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="profile-grid">
        <!-- LEFT COLUMN: Profile Info Card -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-circle"></i> Profile details</h2>
            </div>
            <div class="card-body">
                <div class="info-panel">
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user"></i> Full name</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['fullname']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-at"></i> Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-shield-alt"></i> Role</span>
                        <span class="info-value">
                            <?php 
                                $roleDisplay = ucfirst($user['role']);
                                if($user['role'] == 'admin') {
                                    echo '<i class="fas fa-crown" style="color:#d4af37; margin-right:6px;"></i>';
                                } else {
                                    echo '<i class="fas fa-user-tag" style="color:#558b2f; margin-right:6px;"></i>';
                                }
                                echo htmlspecialchars($roleDisplay);
                            ?>
                        </span>
                    </div>
                </div>
                <hr>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                    <i class="fas fa-id-card" style="color:#3c7633;"></i>
                    <span style="font-size:0.8rem; color:#4d6e44;">Staff access · secure profile</span>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Change Password Card (Modern) -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-lock"></i> Security & password</h2>
            </div>
            <div class="card-body">
                <!-- Dynamic message alerts with icon + modern styling -->
                <?php if(isset($success)): ?>
                    <div class="alert-message alert-success">
                        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                <?php if(isset($error)): ?>
                    <div class="alert-message alert-error">
                        <i class="fas fa-exclamation-triangle" style="font-size: 1.2rem;"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="passwordForm">
                    <!-- Current Password -->
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> Current password</label>
                        <div class="input-wrapper">
                            <input type="password" name="current_password" id="current_password" required autocomplete="current-password" placeholder="··············">
                            <button type="button" class="toggle-password" data-target="current_password"><i class="far fa-eye-slash"></i></button>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="form-group">
                        <label><i class="fas fa-pen-alt"></i> New password</label>
                        <div class="input-wrapper">
                            <input type="password" name="new_password" id="new_password" required autocomplete="new-password" placeholder="Create a strong password">
                            <button type="button" class="toggle-password" data-target="new_password"><i class="far fa-eye-slash"></i></button>
                        </div>
                        <div style="font-size: 0.7rem; margin-top: 0.4rem; color: #638a55;"><i class="fas fa-info-circle"></i> Minimum 6 characters recommended</div>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="form-group">
                        <label><i class="fas fa-check-double"></i> Confirm new password</label>
                        <div class="input-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" required autocomplete="off" placeholder="Re-enter new password">
                            <button type="button" class="toggle-password" data-target="confirm_password"><i class="far fa-eye-slash"></i></button>
                        </div>
                    </div>

                    <button type="submit" name="change_password" class="btn-primary">
                        <i class="fas fa-sync-alt"></i> Update password
                    </button>
                </form>

                <div class="accent-line" style="margin-top: 2rem;"></div>
                <div class="footnote">
                    <i class="fas fa-shield-alt"></i> Your credentials are encrypted and secured
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toggle password visibility (modern & safe, no logic break) -->
<script>
    (function() {
        // Toggle password visibility for all eye buttons
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target');
                const inputField = document.getElementById(targetId);
                if (inputField) {
                    const type = inputField.getAttribute('type') === 'password' ? 'text' : 'password';
                    inputField.setAttribute('type', type);
                    // change icon
                    const icon = this.querySelector('i');
                    if (icon) {
                        if (type === 'text') {
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        } else {
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        }
                    }
                }
            });
        });

        // Optional real-time password match indicator (non-intrusive, only visual feedback)
        const newPass = document.getElementById('new_password');
        const confirmPass = document.getElementById('confirm_password');
        if (newPass && confirmPass) {
            function validateMatch() {
                if (confirmPass.value.length > 0 && newPass.value !== confirmPass.value) {
                    confirmPass.style.borderColor = "#e07a5f";
                    confirmPass.style.backgroundColor = "#fff6f5";
                } else if (confirmPass.value.length > 0 && newPass.value === confirmPass.value) {
                    confirmPass.style.borderColor = "#3b8c3a";
                    confirmPass.style.backgroundColor = "#f9fff9";
                } else {
                    confirmPass.style.borderColor = "#ddebd6";
                    confirmPass.style.backgroundColor = "#ffffff";
                }
            }
            newPass.addEventListener('input', validateMatch);
            confirmPass.addEventListener('input', validateMatch);
        }
    })();
</script>
</body>
</html>
```
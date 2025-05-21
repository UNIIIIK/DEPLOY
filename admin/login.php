<?php
session_start();
require __DIR__ . '/../dbconnection.php';

// Redirect if already logged in
if (isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
} elseif (isset($_SESSION['student'])) {
    header("Location: ../student_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../style.css" rel="stylesheet">
    <style>
        #quantum-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.6;
            background: var(--quantum-darker);
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 20%, rgba(0, 245, 212, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0, 187, 249, 0.08) 0%, transparent 50%);
            z-index: -1;
        }

        .login-card {
            background: var(--quantum-card);
            border-radius: 24px;
            border: 1px solid var(--quantum-border);
            padding: 3rem;
            width: 100%;
            max-width: 480px;
            backdrop-filter: blur(12px);
            box-shadow: var(--quantum-elevation-1);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--quantum-primary);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-header h3 {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 1rem;
            background: var(--quantum-primary);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-header p {
            color: var(--quantum-text-secondary);
            font-size: 1rem;
            margin: 0;
        }

        .quantum-form-group {
            margin-bottom: 1.5rem;
        }

        .quantum-input-label {
            display: block;
            font-size: 0.9rem;
            color: var(--quantum-text-secondary);
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .quantum-input {
            width: 100%;
            background: rgba(30, 33, 58, 0.6);
            border: 1px solid var(--quantum-border);
            border-radius: 12px;
            padding: 12px 16px;
            color: var(--quantum-text);
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .quantum-input:focus {
            outline: none;
            border-color: var(--quantum-accent);
            box-shadow: 0 0 0 3px rgba(0, 245, 212, 0.2);
        }

        .quantum-btn {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            margin-bottom: 1rem;
        }

        .quantum-btn-primary {
            background: var(--quantum-primary);
            color: var(--quantum-dark);
            border: none;
        }

        .quantum-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--quantum-glow);
        }

        .quantum-btn-secondary {
            background: rgba(0, 245, 212, 0.08);
            color: var(--quantum-accent);
            border: 1px solid rgba(0, 245, 212, 0.3);
        }

        .quantum-btn-secondary:hover {
            background: rgba(0, 245, 212, 0.12);
            transform: translateY(-2px);
        }

        #loginMessage {
            text-align: center;
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
        }

        #loginMessage.error {
            background: rgba(255, 92, 141, 0.1);
            color: var(--quantum-error);
            border: 1px solid rgba(255, 92, 141, 0.2);
        }

        #loginMessage.success {
            background: rgba(0, 245, 160, 0.1);
            color: var(--quantum-success);
            border: 1px solid rgba(0, 245, 160, 0.2);
        }
    </style>
</head>
<body>
    <canvas id="quantum-canvas"></canvas>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h3>Welcome Back</h3>
                <p>Sign in to access your dashboard</p>
            </div>
            <form id="adminLoginForm">
                <div class="quantum-form-group">
                    <label for="username" class="quantum-input-label">Username</label>
                    <input type="text" class="quantum-input" id="username" name="username" required>
                </div>
                <div class="quantum-form-group">
                    <label for="password" class="quantum-input-label">Password</label>
                    <input type="password" class="quantum-input" id="password" name="password" required>
                </div>
                <button type="submit" class="quantum-btn quantum-btn-primary">Sign In</button>
                <button type="button" class="quantum-btn quantum-btn-secondary" data-bs-toggle="modal" data-bs-target="#registerModal">
                    Create Account
                </button>
            </form>
            <div id="loginMessage"></div>
        </div>
    </div>

    <!-- Registration Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content quantum-modal">
                <div class="modal-header quantum-modal-header">
                    <h5 class="modal-title quantum-gradient-text" id="registerModalLabel">Create New Account</h5>
                    <button type="button" class="btn-close quantum-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body quantum-modal-body">
                    <form id="registerForm" enctype="multipart/form-data" method="POST" novalidate>
                        <div class="quantum-form-grid">
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">First Name</label>
                                <input type="text" class="quantum-input" id="first_name" name="first_name" required>
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Last Name</label>
                                <input type="text" class="quantum-input" id="last_name" name="last_name" required>
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Email</label>
                                <input type="email" class="quantum-input" id="email" name="email" required>
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Gender</label>
                                <select class="quantum-input" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Phone Number</label>
                                <input type="text" class="quantum-input" id="phone_number" name="phone_number" required>
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Address</label>
                                <input type="text" class="quantum-input" id="user_address" name="user_address" required>
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Birthdate</label>
                                <input type="date" class="quantum-input" id="birthdate" name="birthdate" required>
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Course</label>
                                <input type="text" class="quantum-input" id="course" name="course" required>
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Profile Image</label>
                                <div class="quantum-upload-btn">
                                    <i class="bi bi-cloud-upload"></i>
                                    <input type="file" class="quantum-input" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png,.gif">
                                </div>
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Password</label>
                                <input type="password" class="quantum-input" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="quantum-btn quantum-btn-success">Create Account</button>
                        </div>
                        <div id="registerMessage" class="mt-3"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Quantum Animation
    const canvas = document.getElementById('quantum-canvas');
    const ctx = canvas.getContext('2d');
    
    // Set canvas size
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    
    // Particle class
    class Particle {
        constructor() {
            this.reset();
        }
    
        reset() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.vx = (Math.random() - 0.5) * 0.5;
            this.vy = (Math.random() - 0.5) * 0.5;
            this.radius = Math.random() * 2;
            this.waveAngle = Math.random() * Math.PI * 2;
            this.waveSpeed = 0.02 + Math.random() * 0.02;
            this.originalX = this.x;
            this.originalY = this.y;
            this.amplitude = 30 + Math.random() * 20;
        }
    
        update() {
            // Wave motion
            this.waveAngle += this.waveSpeed;
            this.x = this.originalX + Math.sin(this.waveAngle) * this.amplitude;
            this.y = this.originalY + Math.cos(this.waveAngle) * this.amplitude;
    
            // Boundary check
            if (this.x < 0 || this.x > canvas.width || 
                this.y < 0 || this.y > canvas.height) {
                this.reset();
            }
        }
    
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(0, 245, 212, 0.6)';
            ctx.fill();
        }
    }
    
    // Create particles
    const particles = [];
    const particleCount = 100;
    
    for (let i = 0; i < particleCount; i++) {
        particles.push(new Particle());
    }
    
    // Animation function
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    
        // Update and draw particles
        particles.forEach(particle => {
            particle.update();
            particle.draw();
        });
    
        // Draw connections
        particles.forEach((p1, i) => {
            particles.slice(i + 1).forEach(p2 => {
                const dx = p1.x - p2.x;
                const dy = p1.y - p2.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
    
                if (distance < 150) {
                    ctx.beginPath();
                    ctx.moveTo(p1.x, p1.y);
                    ctx.lineTo(p2.x, p2.y);
                    const alpha = 1 - (distance / 150);
                    ctx.strokeStyle = `rgba(0, 245, 212, ${alpha * 0.2})`;
                    ctx.lineWidth = 1;
                    ctx.stroke();
                }
            });
        });
    
        // Quantum glow effect
        const gradient = ctx.createRadialGradient(
            canvas.width / 2, canvas.height / 2, 0,
            canvas.width / 2, canvas.height / 2, canvas.width / 2
        );
        gradient.addColorStop(0, 'rgba(0, 245, 212, 0.03)');
        gradient.addColorStop(1, 'rgba(0, 187, 249, 0.01)');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    
        requestAnimationFrame(animate);
    }
    
    animate();

    $(document).ready(function() {
        // Login form AJAX submission
        $("#adminLoginForm").submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                url: "login_process.php",
                type: "POST",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        $("#loginMessage").removeClass("error").addClass("success").html(response.message);
                        if (response.is_admin) {
                            window.location.href = "../index.php";
                        } else {
                            window.location.href = "../student_dashboard.php";
                        }
                    } else {
                        $("#loginMessage").removeClass("success").addClass("error").html(response.message);
                    }
                },
                error: function() {
                    $("#loginMessage").removeClass("success").addClass("error").html("Login error. Please try again.");
                }
            });
        });

        // Registration form AJAX submission
        $("#registerForm").submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: "../register.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        $("#registerMessage").html('<div class="quantum-alert success">Registration successful! You may now log in.</div>');
                        $("#registerForm")[0].reset();
                        setTimeout(function() {
                            $("#registerModal").modal("hide");
                            $("#registerMessage").empty();
                        }, 3000);
                    } else {
                        $("#registerMessage").html('<div class="quantum-alert error">' + response.message + '</div>');
                    }
                },
                error: function() {
                    $("#registerMessage").html('<div class="quantum-alert error">An error occurred during registration.</div>');
                }
            });
        });

        // Profile image preview
        $("#profile_image").change(function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $("#profileImagePreview").attr("src", e.target.result);
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    </script>
</body>
</html>

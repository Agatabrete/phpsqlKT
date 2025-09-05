<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'simple_hotel';
$username = 'agata'; // Change as needed
$password = 'agata';     // Change as needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ?page=login");
        exit();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Login
    if (isset($_POST['login'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        $user = $stmt->fetch();
        
        if ($user && $_POST['password'] === $user['password']) { // Simple password check
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            header("Location: ?page=home");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    }
    
    // Register
    if (isset($_POST['register'])) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$_POST['name'], $_POST['email'], $_POST['password']]);
            $success = "Registration successful! Please login.";
        } catch(PDOException $e) {
            $error = "Email already exists or other error occurred.";
        }
    }
    
    // Book room
    if (isset($_POST['book_room'])) {
        requireLogin();
        
        $days = (strtotime($_POST['check_out']) - strtotime($_POST['check_in'])) / (60*60*24);
        $stmt = $pdo->prepare("SELECT price_per_night FROM rooms WHERE id = ?");
        $stmt->execute([$_POST['room_id']]);
        $room = $stmt->fetch();
        $total_price = $room['price_per_night'] * $days;
        
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $_POST['room_id'], $_POST['check_in'], $_POST['check_out'], $total_price]);
        
        $success = "Room booked successfully!";
    }
    
    // Cancel booking (Admin)
    if (isset($_POST['cancel_booking']) && isAdmin()) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$_POST['booking_id']]);
        $success = "Booking cancelled successfully!";
    }
}

// Get current page
$page = $_GET['page'] ?? 'home';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Hotel Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .room-card { transition: transform 0.2s; }
        .room-card:hover { transform: translateY(-2px); }
        .price-tag { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="?page=home">🏨 Simple Hotel</a>
            <div class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="?page=home">Rooms</a>
                    <a class="nav-link" href="?page=my_bookings">My Bookings</a>
                    <?php if (isAdmin()): ?>
                        <a class="nav-link" href="?page=admin">Admin</a>
                    <?php endif; ?>
                    <a class="nav-link" href="?page=logout">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
                <?php else: ?>
                    <a class="nav-link" href="?page=login">Login</a>
                    <a class="nav-link" href="?page=register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php
        // Page routing
        switch($page) {
            case 'login':
                if (isLoggedIn()) {
                    header("Location: ?page=home");
                    exit();
                }
                ?>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h4>Login</h4></div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                                </form>
                                <p class="mt-3 text-center">Don't have an account? <a href="?page=register">Register here</a></p>
                                <hr>
                                <small class="text-muted">
                                    <strong>Demo accounts:</strong><br>
                                    Admin: admin@hotel.com / admin123<br>
                                    User: john@email.com / password123
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                break;

            case 'register':
                if (isLoggedIn()) {
                    header("Location: ?page=home");
                    exit();
                }
                ?>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h4>Register</h4></div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <button type="submit" name="register" class="btn btn-success w-100">Register</button>
                                </form>
                                <p class="mt-3 text-center">Already have an account? <a href="?page=login">Login here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                break;

            case 'logout':
                session_destroy();
                header("Location: ?page=home");
                exit();
                break;

            case 'my_bookings':
                requireLogin();
                $stmt = $pdo->prepare("
                    SELECT b.*, r.room_number, r.room_type, r.description 
                    FROM bookings b 
                    JOIN rooms r ON b.room_id = r.id 
                    WHERE b.user_id = ? 
                    ORDER BY b.booking_date DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $bookings = $stmt->fetchAll();
                ?>
                <h2>My Bookings</h2>
                <?php if (empty($bookings)): ?>
                    <div class="alert alert-info">You have no bookings yet. <a href="?page=home">Browse rooms</a></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Type</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['room_number']) ?></td>
                                    <td><?= ucfirst($booking['room_type']) ?></td>
                                    <td><?= $booking['check_in'] ?></td>
                                    <td><?= $booking['check_out'] ?></td>
                                    <td class="price-tag">$<?= number_format($booking['total_price'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $booking['status'] === 'confirmed' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <?php
                break;

            case 'admin':
                if (!isAdmin()) {
                    header("Location: ?page=home");
                    exit();
                }
                
                // Get all bookings
                $stmt = $pdo->query("
                    SELECT b.*, u.name as user_name, u.email, r.room_number, r.room_type 
                    FROM bookings b 
                    JOIN users u ON b.user_id = u.id 
                    JOIN rooms r ON b.room_id = r.id 
                    ORDER BY b.booking_date DESC
                ");
                $all_bookings = $stmt->fetchAll();
                
                // Get statistics
                $stats = $pdo->query("
                    SELECT 
                        COUNT(*) as total_bookings,
                        SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END) as total_revenue,
                        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings
                    FROM bookings
                ")->fetch();
                ?>
                <h2>Admin Dashboard</h2>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5>Total Bookings</h5>
                                <h3><?= $stats['total_bookings'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5>Confirmed Bookings</h5>
                                <h3><?= $stats['confirmed_bookings'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5>Total Revenue</h5>
                                <h3>$<?= number_format($stats['total_revenue'], 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Bookings -->
                <h4>All Bookings</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_bookings as $booking): ?>
                            <tr>
                                <td>#<?= $booking['id'] ?></td>
                                <td><?= htmlspecialchars($booking['user_name']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($booking['email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($booking['room_number']) ?><br>
                                    <small><?= ucfirst($booking['room_type']) ?></small>
                                </td>
                                <td><?= $booking['check_in'] ?></td>
                                <td><?= $booking['check_out'] ?></td>
                                <td class="price-tag">$<?= number_format($booking['total_price'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $booking['status'] === 'confirmed' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['status'] === 'confirmed'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                            <button type="submit" name="cancel_booking" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure?')">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                break;

            default: // Home page - show rooms
                $stmt = $pdo->query("SELECT * FROM rooms WHERE is_available = TRUE ORDER BY room_number");
                $rooms = $stmt->fetchAll();
                ?>
                <div class="row">
                    <div class="col-12">
                        <h1 class="mb-4">Welcome to Simple Hotel</h1>
                        <?php if (!isLoggedIn()): ?>
                            <div class="alert alert-info">
                                <strong>Please <a href="?page=login">login</a> to book rooms.</strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <?php foreach ($rooms as $room): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card room-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Room <?= htmlspecialchars($room['room_number']) ?></h5>
                                <p class="card-text">
                                    <strong><?= ucfirst($room['room_type']) ?> Room</strong><br>
                                    <?= htmlspecialchars($room['description']) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price-tag">$<?= number_format($room['price_per_night'], 2) ?>/night</span>
                                    <?php if (isLoggedIn()): ?>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                                data-bs-target="#bookModal<?= $room['id'] ?>">Book Now</button>
                                    <?php else: ?>
                                        <a href="?page=login" class="btn btn-outline-primary btn-sm">Login to Book</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (isLoggedIn()): ?>
                        <!-- Booking Modal -->
                        <div class="modal fade" id="bookModal<?= $room['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Book Room <?= htmlspecialchars($room['room_number']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Check-in Date</label>
                                                <input type="date" class="form-control" name="check_in" required
                                                       min="<?= date('Y-m-d') ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Check-out Date</label>
                                                <input type="date" class="form-control" name="check_out" required
                                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                            </div>
                                            <div class="alert alert-info">
                                                <strong>Price:</strong> $<?= number_format($room['price_per_night'], 2) ?> per night
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="book_room" class="btn btn-primary">Confirm Booking</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php
                break;
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
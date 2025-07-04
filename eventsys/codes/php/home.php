<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include('../includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="../css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="dashboard-layout">
  <aside class="sidebar">
      <h2 class="logo">Eventix</h2>
      <nav>
        <a href="home.php" class="<?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">
          <i data-lucide="home"></i> Home
        </a>
        <a href="events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>">
          <i data-lucide="calendar"></i> Browse Events
        </a>
        <a href="my_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'my_events.php' ? 'active' : '' ?>">
          <i data-lucide="user-check"></i> My Events
        </a>
        <a href="attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active' : '' ?>">
          <i data-lucide="check-square"></i> Attendance
        </a>

        <?php if ($role === 'event_head'): ?>
          <a href="manage_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_events.php' ? 'active' : '' ?>">
            <i data-lucide="settings"></i> Manage Events
          </a>
          <a href="view_attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'view_attendance.php' ? 'active' : '' ?>">
            <i data-lucide="eye"></i> View Attendance
          </a>
        <?php endif; ?>

        <div id="overlay_container" class="overlay hidden">
          <div id="delete_event_container" class="delete-event-container">
              <label id="delete_prompt_text">Are you sure to logout?</label>
              <div class="event-options">
                  <button class="close-button" style="margin-top: 60px" onClick="logout()">Logout</button>
                  <button class="close-button" onClick="showDeletePrompt()">Cancel</button>
              </div>
          </div>
        </div>

        <?php if ($role === 'admin'): ?>
          <div class="dropdown-nav">
            <div class="dropdown-toggle" onclick="toggleDropdown(this)">
              <i data-lucide="database" style="margin-right: 8px;"></i>
              <span>Maintenance</span>
              <span style="margin-left:auto;">▾</span>
            </div>

            <div class="dropdown-menu">
              <a href="manage_users.php">Users</a>
              <a href="manage_venues.php">Venues</a>
              <a href="manage_organizers.php">Organizers</a>
              <a href="manage_categories.php">Categories</a>
            </div>
          </div>
        <?php endif; ?>

        <a onClick="showLogoutButton()" style="cursor: pointer;"><i data-lucide="log-out"></i> Logout</a>
      </nav>
  </aside>

  <main class="main-content">
      <header class="banner">
          <div>
              <h1>Hi, <?= htmlspecialchars($full_name) ?></h1>
              <p>Welcome to your dashboard. Let’s manage and discover events easily.</p>
          </div>
          <img src="../assets/eventix-logo.png" alt="Eventix logo" />
      </header>

      <section class="grid-section">
        <div class="card">
          <h3>Browse Events</h3>
          <p>Find and register for upcoming events.</p>
          <a href="events.php">Explore</a>
        </div>

        <div class="card">
          <h3>My Registrations</h3>
          <p>View events you’ve registered for.</p>
          <a href="my_events.php">View</a>
        </div>

        <?php if ($role === 'event_head'): ?>
        <div class="card">
            <h3>Manage Events</h3>
            <p>Create and update the events you organize.</p>
            <a href="manage_events.php">Manage</a>
        </div>
        <?php endif; ?>
      </section>
  </main>
  <script src="../js/script.js"></script>
  <script>
    function toggleDropdown(toggle) {
      const container = toggle.closest(".dropdown-nav");
      container.classList.toggle("open");
    }

    lucide.createIcons();
  </script>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include('../includes/db.php');

$user_id = $_SESSION['user_id'];

// Check role
$role_stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if ($role !== 'event_head') {
    echo "Access denied.";
    exit();
}

// Handle add event
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_event'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $venue_name = $_POST['venue_name'];
    $venue_address = $_POST['venue_address'];
    $venue_city = $_POST['venue_city'];
    $capacity = $_POST['capacity'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];

    // Insert venue and get ID
    $venue_stmt = $conn->prepare("INSERT INTO venue (name, address, city) VALUES (?, ?, ?)");
    $venue_stmt->bind_param("sss", $venue_name, $venue_address, $venue_city);
    $venue_stmt->execute();
    $venue_id = $venue_stmt->insert_id;
    $venue_stmt->close();

    // Fetch user's info
    $user_stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, phone FROM user WHERE user_id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_stmt->bind_result($first_name, $middle_name, $last_name, $email, $phone);
    $user_stmt->fetch();
    $user_stmt->close();

    // Check or insert organizer
    $org_stmt = $conn->prepare("SELECT organizer_id FROM organizer WHERE contact_email = ?");
    $org_stmt->bind_param("s", $email);
    $org_stmt->execute();
    $org_stmt->bind_result($organizer_id);
    $org_stmt->fetch();
    $org_stmt->close();

    if (!$organizer_id) {
        $insert_org = $conn->prepare("INSERT INTO organizer (first_name, middle_name, last_name, contact_email, phone) VALUES (?, ?, ?, ?, ?)");
        $insert_org->bind_param("sssss", $first_name, $middle_name, $last_name, $email, $phone);
        $insert_org->execute();
        $organizer_id = $insert_org->insert_id;
        $insert_org->close();
    }

    // Insert event
    $stmt = $conn->prepare("INSERT INTO event (title, description, start_time, end_time, venue_id, organizer_id, capacity, price, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiiidi", $title, $description, $start_time, $end_time, $venue_id, $organizer_id, $capacity, $price, $category_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_events.php");
    exit();
}

// Handle delete
// if (isset($_GET['delete'])) {
//     $delete_id = $_GET['delete'];
//     $stmt = $conn->prepare("DELETE FROM event WHERE event_id = ? AND organizer_id IN (SELECT organizer_id FROM organizer WHERE contact_email = (SELECT email FROM user WHERE user_id = ?))");
//     $stmt->bind_param("ii", $delete_id, $user_id);
//     $stmt->execute();
//     $stmt->close();
//     header("Location: manage_events.php");
//     exit();
// }

// Fetch own events
$stmt = $conn->prepare("SELECT e.event_id, e.title, e.start_time, e.end_time, v.name AS venue FROM event e JOIN venue v ON e.venue_id = v.venue_id JOIN organizer o ON e.organizer_id = o.organizer_id JOIN user u ON o.contact_email = u.email WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage My Events</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<!-- Nav Bar -->
 <div class="navbar-container">
    <h1 style="margin: 5px 0px; color: white;">Manage My Events</h1>
 </div>

<div class="dashboard">
    <div class="manage-events-container">
        <!-- Flex container for title and buttons -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <div style="display: flex; gap: 12px;">
                <a href="home.php" class="back-link">
                    &larr; Back to Dashboard
                </a>
                <?php if ($role === 'event_head'): ?>
                    <a href="view_attendance.php" class="attendance-link">
                        View Attendance
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Fetch event to edit
        $edit_event = null;
        if (isset($_GET['edit'])) {
            $edit_id = $_GET['edit'];
            $stmt = $conn->prepare("SELECT * FROM event WHERE event_id = ? AND organizer_id IN (SELECT organizer_id FROM organizer WHERE contact_email = (SELECT email FROM user WHERE user_id = ?))");
            $stmt->bind_param("ii", $edit_id, $user_id);
            $stmt->execute();
            $edit_event = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        // Handle update (venue editing skipped)
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_event'])) {
            $title = $_POST['title'];
            $description = $_POST['description'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $capacity = $_POST['capacity'];
            $price = $_POST['price'];
            $category_id = $_POST['category_id'];
            $event_id = $_POST['event_id'];

            $stmt = $conn->prepare("UPDATE event SET title=?, description=?, start_time=?, end_time=?, capacity=?, price=?, category_id=? WHERE event_id=? AND organizer_id IN (SELECT organizer_id FROM organizer WHERE contact_email = (SELECT email FROM user WHERE user_id = ?))");
            $stmt->bind_param("ssssiddii", $title, $description, $start_time, $end_time, $capacity, $price, $category_id, $event_id, $user_id);
            $stmt->execute();
            $stmt->close();
            header("Location: manage_events.php");
            exit();
        }
        ?>

        <?php
        $category_result = $conn->query("SELECT category_id, category_name FROM event_category");
        ?>
        <h2><?= $edit_event ? "Edit Event" : "Create New Event" ?></h2>
        <form method="POST">
            <input type="hidden" name="event_id" value="<?= $edit_event['event_id'] ?? '' ?>">
            <input type="text" name="title" placeholder="Event Title" value="<?= $edit_event['title'] ?? '' ?>" required>
            <textarea name="description" placeholder="Event Description" required><?= $edit_event['description'] ?? '' ?></textarea>
            <input type="datetime-local" name="start_time" value="<?= date("M d, Y h:i A", strtotime($row['start_time'])) ?>" required>
            <input type="datetime-local" name="end_time" value=" <?= date("M d, Y h:i A", strtotime($row['end_time'])) ?>" required>

            <?php if (!$edit_event): ?>
                <input type="text" name="venue_name" placeholder="Venue Name" required>
                <input type="text" name="venue_address" placeholder="Venue Address">
                <input type="text" name="venue_city" placeholder="Venue City">
            <?php else: ?>
                <p><em>Venue cannot be edited for existing events.</em></p>
            <?php endif; ?>

            <input type="number" name="capacity" placeholder="Capacity" value="<?= $edit_event['capacity'] ?? '' ?>" required>
            <input type="number" step="0.01" name="price" placeholder="Price" value="<?= $edit_event['price'] ?? '' ?>" required>

            <select name="category_id" required>
                <option value="">-- Select Category --</option>
                <?php while ($cat = $category_result->fetch_assoc()): ?>
                    <option value="<?= $cat['category_id'] ?>"
                        <?= (isset($edit_event['category_id']) && $edit_event['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <?php if ($edit_event): ?>
                <button type="submit" name="update_event">Update Event</button>
            <?php else: ?>
                <button type="submit" name="add_event">Add Event</button>
            <?php endif; ?>
        </form>
    </div>
   
    <div id="overlay_container" class="overlay hidden">
        <div id="delete_event_container" class="delete-event-container">
            <label id="delete_prompt_text">Are you sure to delete this event?</label>
            <div class="event-options">
                <button class="close-button" id="confirmDeleteBtn" style="margin-top: 60px">Delete</button>
                <button class="close-button" onClick="showDeletePrompt()">Cancel</button>
            </div>
        </div>
    </div>

    <div class="my-events-container">
    <h2>My Events</h2>
        <div class="event-list">
            <?php while ($row = $events->fetch_assoc()): ?>
                <div class="event-card">
                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <p><strong>Venue:</strong> <?= htmlspecialchars($row['venue']) ?></p>
                    <p><strong>From:</strong> <?= date("M d, Y h:i A", strtotime($row['start_time'])) ?>
                    <br><strong>To:</strong><?= date("M d, Y h:i A", strtotime($row['end_time'])) ?></p>
                    <div class="event-actions" style="margin-top: 18px;">
                        <a href="manage_events.php?edit=<?= $row['event_id'] ?>" class="edit-link">Edit</a>
                        <button type="button" class="delete-link delete-personal" onClick="showDeletePrompt(<?= $row['event_id']?>)">Delete</button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<script src="../js/script.js"></script>
</body>
</html>
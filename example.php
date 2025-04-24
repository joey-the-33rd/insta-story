<?php
require_once 'class.instagram.php';

$message = '';
$story_html = '';

if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    if (empty($username)) {
        $message = "Please enter a username.";
    } else {
        $story = new instagram_story();
        ob_start();
        $story->getStory($username);
        $story_html = ob_get_clean();
    }
}
?>
<div id="center" style="text-align: center; margin-top: 100px;">
    <form action="" method="post">
        <label for="username">Instagram Username:</label><br>
        <input id="username" name="username" type="text" placeholder="Enter Instagram username" required>
        <br><br>
        <input type="submit" name="submit" value="Get Stories">
    </form>
    <div style="margin-top: 20px; color: red;">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <div style="margin-top: 20px;">
        <?php echo $story_html; ?>
    </div>
</div>

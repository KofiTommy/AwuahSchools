<?php
session_start();
include("dbstring.php");
include("check-login.php");
include_once("module-settings-utils.php");

if(!isset($_SESSION['ACCESSLEVEL']) || $_SESSION['ACCESSLEVEL'] !== 'administrator'){
    header('Location: index.php');
    exit;
}

if(empty($_SESSION['module_settings_csrf'])){
    $_SESSION['module_settings_csrf'] = bin2hex(random_bytes(24));
}
$message = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if(!hash_equals($_SESSION['module_settings_csrf'], $token)){
        $message = '<div class="notice error">Your form expired. Please try again.</div>';
    } else {
        $enabled = isset($_POST['modules']) ? $_POST['modules'] : array();
        if(school_modules_save($con, $enabled, isset($_SESSION['USERID']) ? $_SESSION['USERID'] : '')){
            $message = '<div class="notice success">Module visibility saved. Nothing was deleted.</div>';
        } else {
            $message = '<div class="notice error">Settings could not be saved. Existing menu visibility has not been changed.</div>';
        }
    }
}
$catalog = school_modules_catalog();
?>
<html><head>
<?php include("links.php"); ?>
<title>Module Settings</title>
<style>
.module-page{max-width:900px;margin:28px auto;padding:0 18px;font-family:Arial,sans-serif}.module-card{background:#fff;border:1px solid #dbe3ec;border-radius:12px;padding:24px;box-shadow:0 4px 18px rgba(15,23,42,.06)}.module-row{display:flex;gap:14px;align-items:flex-start;padding:16px 0;border-bottom:1px solid #edf1f5}.module-row:last-of-type{border:0}.module-row input{width:20px;height:20px;margin-top:2px}.module-row label{cursor:pointer}.module-row strong{display:block;font-size:16px;color:#172033}.module-row span{display:block;color:#5c687a;margin-top:4px}.notice{padding:12px 14px;border-radius:8px;margin:14px 0}.success{background:#ecfdf3;color:#166534}.error{background:#fef2f2;color:#b91c1c}.module-note{color:#526174;line-height:1.5}.save-button{margin-top:20px;background:#1769aa;color:#fff;border:0;border-radius:8px;padding:12px 18px;font-weight:bold;cursor:pointer}
</style></head><body>
<div class="header"><?php include("menu.php"); ?></div>
<main class="module-page"><div class="module-card">
<h1>Module Visibility</h1>
<p class="module-note">Choose which optional modules appear in menus. Turning one off only hides its navigation links; it does not delete data, tables, users, or historical records. You can restore any module here at any time.</p>
<?php echo $message; ?>
<form method="post" action="module-settings.php">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['module_settings_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
<?php foreach($catalog as $key => $module){ $checked = school_module_is_enabled($con, $key) ? ' checked' : ''; ?>
<div class="module-row"><input type="checkbox" id="module-<?php echo $key; ?>" name="modules[]" value="<?php echo $key; ?>"<?php echo $checked; ?>><label for="module-<?php echo $key; ?>"><strong><?php echo htmlspecialchars($module['label'], ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($module['description'], ENT_QUOTES, 'UTF-8'); ?></span></label></div>
<?php } ?>
<button class="save-button" type="submit">Save module visibility</button>
</form></div></main></body></html>

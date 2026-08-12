<?php
/*
 * Reversible school module visibility settings. A missing setting always means
 * "enabled", preserving the behaviour of existing installations.
 */
if(!function_exists('school_modules_catalog')){
function school_modules_catalog(){
    return array(
        'boarding_houses' => array('label' => 'Boarding & Houses', 'description' => 'Houses, house masters, matrons and exeats.'),
        'waec_analysis' => array('label' => 'WAEC Analysis', 'description' => 'WAEC-specific analysis and shortcuts.'),
        'course_registration' => array('label' => 'Course Registration', 'description' => 'Subject/course registration workflows.'),
        'departments' => array('label' => 'Departments & HODs', 'description' => 'Department teams and result approval.'),
        'online_voting' => array('label' => 'Online Voting', 'description' => 'Student and administrator voting links.'),
        'student_chat' => array('label' => 'Student Chat', 'description' => 'Student chat links and administration.'),
        'transport' => array('label' => 'Transport', 'description' => 'Transport routes and student assignments.'),
    );
}
}

if(!function_exists('school_modules_ensure_table')){
function school_modules_ensure_table($con){
    static $done = false;
    if($done || !$con){ return; }
    $done = true;
    @mysqli_query($con, "CREATE TABLE IF NOT EXISTS tblmodulevisibility (
        modulekey VARCHAR(64) NOT NULL PRIMARY KEY,
        enabled TINYINT(1) NOT NULL DEFAULT 1,
        updatedby VARCHAR(100) NOT NULL DEFAULT '',
        updatedat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
}

if(!function_exists('school_module_is_enabled')){
function school_module_is_enabled($con, $moduleKey){
    static $settings = null;
    $catalog = school_modules_catalog();
    if(!isset($catalog[$moduleKey])){ return true; }
    if($settings === null){
        $settings = array();
        school_modules_ensure_table($con);
        $result = @mysqli_query($con, "SELECT modulekey, enabled FROM tblmodulevisibility");
        if($result){
            while($row = mysqli_fetch_assoc($result)){
                $settings[$row['modulekey']] = (int)$row['enabled'] === 1;
            }
        }
    }
    return !array_key_exists($moduleKey, $settings) || $settings[$moduleKey];
}
}

if(!function_exists('school_modules_save')){
function school_modules_save($con, $enabledKeys, $updatedBy){
    school_modules_ensure_table($con);
    $catalog = school_modules_catalog();
    $enabledKeys = is_array($enabledKeys) ? $enabledKeys : array();
    foreach($catalog as $key => $module){
        $keyEsc = mysqli_real_escape_string($con, $key);
        $userEsc = mysqli_real_escape_string($con, (string)$updatedBy);
        $enabled = in_array($key, $enabledKeys, true) ? 1 : 0;
        $sql = "INSERT INTO tblmodulevisibility(modulekey, enabled, updatedby) VALUES('$keyEsc', $enabled, '$userEsc')
                ON DUPLICATE KEY UPDATE enabled=VALUES(enabled), updatedby=VALUES(updatedby), updatedat=CURRENT_TIMESTAMP";
        if(!mysqli_query($con, $sql)){ return false; }
    }
    return true;
}
}
?>

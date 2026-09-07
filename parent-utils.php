<?php
/* Parent Portal helpers. Parent access is deliberately separate from student accounts. */
if(!function_exists('parent_portal_ensure_tables')){
function parent_portal_ensure_tables($con){
    @mysqli_query($con,"CREATE TABLE IF NOT EXISTS tblparentaccount (
        parentid VARCHAR(48) NOT NULL PRIMARY KEY,
        fullname VARCHAR(180) NOT NULL,
        mobile VARCHAR(40) DEFAULT NULL,
        email VARCHAR(160) DEFAULT NULL,
        passwordhash VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        createdat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updatedat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        lastloginat DATETIME DEFAULT NULL,
        UNIQUE KEY uq_parent_email (email), KEY idx_parent_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    @mysqli_query($con,"CREATE TABLE IF NOT EXISTS tblparentstudentlink (
        linkid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        parentid VARCHAR(48) NOT NULL,
        studentid VARCHAR(60) NOT NULL,
        relationshiptext VARCHAR(80) DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        requestedat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        approvedat DATETIME DEFAULT NULL,
        approvedby VARCHAR(60) DEFAULT NULL,
        UNIQUE KEY uq_parent_student (parentid,studentid),
        KEY idx_parent_link (parentid,status), KEY idx_student_link (studentid,status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
}
if(!function_exists('parent_portal_id')){function parent_portal_id(){return 'PAR'.date('ymdHis').strtoupper(bin2hex(random_bytes(4)));}}
if(!function_exists('parent_portal_safe')){function parent_portal_safe($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}}
if(!function_exists('parent_portal_csrf')){function parent_portal_csrf(){if(empty($_SESSION['parent_portal_csrf']))$_SESSION['parent_portal_csrf']=bin2hex(random_bytes(32));return $_SESSION['parent_portal_csrf'];}}
if(!function_exists('parent_portal_csrf_valid')){function parent_portal_csrf_valid($value){return is_string($value)&&!empty($_SESSION['parent_portal_csrf'])&&hash_equals($_SESSION['parent_portal_csrf'],$value);}}
if(!function_exists('parent_portal_phone_digits')){function parent_portal_phone_digits($value){return preg_replace('/\D+/','',(string)$value);}}
if(!function_exists('parent_portal_phone_matches')){function parent_portal_phone_matches($one,$two){$one=parent_portal_phone_digits($one);$two=parent_portal_phone_digits($two);return strlen($one)>=9&&strlen($two)>=9&&substr($one,-9)===substr($two,-9);}}
if(!function_exists('parent_portal_current_parent')){
function parent_portal_current_parent($con){
    $id=trim((string)($_SESSION['PARENT_PORTAL_ID']??'')); if($id==='')return null;
    $stmt=mysqli_prepare($con,"SELECT * FROM tblparentaccount WHERE parentid=? AND status='active' LIMIT 1"); if(!$stmt)return null;
    mysqli_stmt_bind_param($stmt,'s',$id);mysqli_stmt_execute($stmt);$result=mysqli_stmt_get_result($stmt);$row=$result?mysqli_fetch_assoc($result):null;mysqli_stmt_close($stmt);return $row;
}}
if(!function_exists('parent_portal_require_login')){function parent_portal_require_login($con){$parent=parent_portal_current_parent($con);if(!$parent){unset($_SESSION['PARENT_PORTAL_ID']);header('location:parent-portal.php');exit();}return $parent;}}
if(!function_exists('parent_portal_is_admin')){function parent_portal_is_admin(){return isset($_SESSION['ACCESSLEVEL'],$_SESSION['SYSTEMTYPE'])&&$_SESSION['ACCESSLEVEL']==='administrator'&&in_array($_SESSION['SYSTEMTYPE'],array('normal_user','super_user'),true);}}
?>

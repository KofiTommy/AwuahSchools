<?php
session_start();
include('check-login.php');
include('dbstring.php');
include_once('transport-utils.php');

if(!transport_is_admin()){
    http_response_code(403);
    exit('Administrator access required.');
}
transport_ensure_tables($con);

function tm($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

$dashboardLink = (isset($_SESSION['SYSTEMTYPE']) && $_SESSION['SYSTEMTYPE'] === 'super_user') ? 'super.php' : 'admin.php';
$message = '';
$actor = isset($_SESSION['USERID']) ? $_SESSION['USERID'] : '';

if(isset($_POST['save_vehicle'])){
    $name = trim((string)$_POST['vehiclename']);
    $registration = trim((string)$_POST['registrationno']);
    $capacity = (int)$_POST['capacity'];
    $driverId = isset($_POST['driverid']) ? $_POST['driverid'] : '';
    $assistantId = isset($_POST['assistantid']) ? $_POST['assistantid'] : '';
    if($name === '' || $registration === '' || $capacity < 1){
        $message = 'Enter vehicle name, registration number, and capacity.';
    } else {
        $id = transport_id('VEH_');
        $ok = mysqli_query($con, "INSERT INTO tbltransportvehicle(vehicleid,vehiclename,registrationno,capacity,driverid,assistantid,status,createdby,createdat) VALUES('$id','".mysqli_real_escape_string($con,$name)."','".mysqli_real_escape_string($con,$registration)."',$capacity,'".mysqli_real_escape_string($con,$driverId)."','".mysqli_real_escape_string($con,$assistantId)."','active','".mysqli_real_escape_string($con,$actor)."',NOW())");
        $message = $ok ? 'Vehicle saved.' : 'Vehicle could not be saved; registration number may already exist.';
    }
}
if(isset($_POST['save_route'])){
    $name = trim((string)$_POST['routename']);
    if($name === ''){
        $message = 'Enter a route name.';
    } else {
        $id = transport_id('RTE_');
        $ok = mysqli_query($con, "INSERT INTO tbltransportroute(routeid,routename,vehicleid,fee,status,createdby,createdat) VALUES('$id','".mysqli_real_escape_string($con,$name)."','".mysqli_real_escape_string($con,$_POST['vehicleid'])."',".(float)$_POST['fee'].",'active','".mysqli_real_escape_string($con,$actor)."',NOW())");
        $message = $ok ? 'Route saved.' : 'Route could not be saved; route name may already exist.';
    }
}
if(isset($_POST['save_stop'])){
    $id = transport_id('STP_');
    $ok = mysqli_query($con, "INSERT INTO tbltransportstop(stopid,routeid,stopname,pickuptime,sortorder,status,createdat) VALUES('$id','".mysqli_real_escape_string($con,$_POST['routeid'])."','".mysqli_real_escape_string($con,trim((string)$_POST['stopname']))."','".mysqli_real_escape_string($con,$_POST['pickuptime'])."',".(int)$_POST['sortorder'].",'active',NOW())");
    $message = $ok ? 'Route stop saved.' : 'Stop could not be saved.';
}

$staff = array();
$result = mysqli_query($con, "SELECT userid, CONCAT_WS(' ',firstname,othernames,surname) AS name FROM tblsystemuser WHERE systemtype IN ('Teacher','User') AND status='active' ORDER BY firstname,surname");
if($result) while($row = mysqli_fetch_assoc($result)) $staff[] = $row;

$vehicles = array();
$result = mysqli_query($con, "SELECT v.*, CONCAT_WS(' ',d.firstname,d.othernames,d.surname) AS drivername, CONCAT_WS(' ',a.firstname,a.othernames,a.surname) AS assistantname FROM tbltransportvehicle v LEFT JOIN tblsystemuser d ON d.userid=v.driverid LEFT JOIN tblsystemuser a ON a.userid=v.assistantid WHERE v.status='active' ORDER BY v.vehiclename");
if($result) while($row = mysqli_fetch_assoc($result)) $vehicles[] = $row;

$routes = array();
$result = mysqli_query($con, "SELECT tr.*,v.vehiclename FROM tbltransportroute tr LEFT JOIN tbltransportvehicle v ON v.vehicleid=tr.vehicleid WHERE tr.status='active' ORDER BY tr.routename");
if($result) while($row = mysqli_fetch_assoc($result)) $routes[] = $row;
?>
<!doctype html>
<html><head>
<?php include('links.php'); ?>
<title>Transport Management</title>
<style>
body{background:#f4f8fb;font-family:Arial;color:#17314b}.tm{max-width:1100px;margin:25px auto;background:#fff;padding:24px;border-radius:16px}.tm-nav{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}.tm-link{display:inline-flex;align-items:center;gap:7px;padding:10px 13px;background:#e7f1fa;color:#17314b;border-radius:8px;text-decoration:none;font-weight:bold}.tm-link--primary{background:#087443;color:#fff}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:15px}.card{border:1px solid #dce6ee;border-radius:12px;padding:16px}.card input,.card select{width:100%;box-sizing:border-box;padding:10px;margin:5px 0;background:#fff;color:#102a43;border:1px solid #7893aa;border-radius:7px}.card input[readonly]{background:#eef5f9;color:#25445e;font-weight:600}.card button{background:#087443;color:#fff;border:0;border-radius:8px;padding:11px 14px;margin-top:8px}.field-label{display:block;font-size:13px;font-weight:bold;margin-top:10px}.tm table{width:100%;border-collapse:collapse;margin-top:22px}.tm td,.tm th{padding:9px;border-bottom:1px solid #dce6ee;text-align:left}@media(max-width:700px){.tm{margin:8px;padding:14px}.grid{grid-template-columns:1fr}}
</style></head>
<body><main class="tm">
<nav class="tm-nav" aria-label="Transport shortcuts">
    <a class="tm-link" href="<?php echo tm($dashboardLink); ?>"><i class="fa fa-dashboard"></i> Dashboard</a>
    <a class="tm-link tm-link--primary" href="transport-student-assignment.php"><i class="fa fa-users"></i> Assign Students to Transport</a>
</nav>
<h1>Transport Management</h1>
<p>Create vehicles, routes, and pickup stops. Then assign students to an active route.</p>
<?php if($message !== ''){ ?><p><?php echo tm($message); ?></p><?php } ?>
<div class="grid">
<section class="card"><h2>Vehicle</h2><form method="post">
<input name="vehiclename" placeholder="Vehicle name" required><input name="registrationno" placeholder="Registration number" required><input type="number" name="capacity" min="1" placeholder="Capacity" required>
<label class="field-label" for="driverid">Driver</label><select id="driverid" name="driverid"><option value="">Choose driver (optional)</option><?php foreach($staff as $staffMember){ ?><option value="<?php echo tm($staffMember['userid']); ?>"><?php echo tm($staffMember['name']); ?></option><?php } ?></select>
<input id="driver-name-preview" type="text" value="No driver selected" readonly aria-label="Selected driver name">
<label class="field-label" for="assistantid">Assistant</label><select id="assistantid" name="assistantid"><option value="">Choose assistant (optional)</option><?php foreach($staff as $staffMember){ ?><option value="<?php echo tm($staffMember['userid']); ?>"><?php echo tm($staffMember['name']); ?></option><?php } ?></select>
<input id="assistant-name-preview" type="text" value="No assistant selected" readonly aria-label="Selected assistant name">
<button name="save_vehicle">Save Vehicle</button></form></section>
<section class="card"><h2>Route</h2><form method="post"><input name="routename" placeholder="Route name" required><label class="field-label" for="vehicleid">Vehicle</label><select id="vehicleid" name="vehicleid"><option value="">Choose vehicle</option><?php foreach($vehicles as $vehicle){ ?><option value="<?php echo tm($vehicle['vehicleid']); ?>"><?php echo tm($vehicle['vehiclename'].' — '.$vehicle['registrationno']); ?></option><?php } ?></select><input id="vehicle-name-preview" type="text" value="No vehicle selected" readonly aria-label="Selected vehicle"><input type="number" name="fee" min="0" step="0.01" placeholder="Transport fee per term (GHS)"><button name="save_route">Save Route</button></form></section>
<section class="card"><h2>Pickup Stop</h2><form method="post"><select name="routeid" required><option value="">Choose route</option><?php foreach($routes as $route){ ?><option value="<?php echo tm($route['routeid']); ?>"><?php echo tm($route['routename']); ?></option><?php } ?></select><input name="stopname" placeholder="Stop name" required><input name="pickuptime" placeholder="Pickup time, e.g. 6:30 AM"><input type="number" name="sortorder" value="0" min="0" placeholder="Stop order"><button name="save_stop">Save Stop</button></form></section>
</div>
<h2>Active Vehicles</h2><table><tr><th>Vehicle</th><th>Registration</th><th>Driver</th><th>Assistant</th><th>Capacity</th></tr><?php foreach($vehicles as $vehicle){ ?><tr><td><?php echo tm($vehicle['vehiclename']); ?></td><td><?php echo tm($vehicle['registrationno']); ?></td><td><?php echo tm($vehicle['drivername'] !== '' ? $vehicle['drivername'] : 'Not assigned'); ?></td><td><?php echo tm($vehicle['assistantname'] !== '' ? $vehicle['assistantname'] : 'Not assigned'); ?></td><td><?php echo number_format((int)$vehicle['capacity']); ?></td></tr><?php } ?></table>
<h2>Active Routes</h2><table><tr><th>Route</th><th>Vehicle</th><th>Fee / Term</th></tr><?php foreach($routes as $route){ ?><tr><td><?php echo tm($route['routename']); ?></td><td><?php echo tm($route['vehiclename']); ?></td><td>GHS <?php echo number_format($route['fee'],2); ?></td></tr><?php } ?></table>
</main>
<script>
function showSelectedStaffName(selectId, previewId, emptyLabel){
    var select = document.getElementById(selectId), preview = document.getElementById(previewId);
    if(!select || !preview){ return; }
    var update = function(){ preview.value = select.value ? select.options[select.selectedIndex].text : emptyLabel; };
    select.addEventListener('change', update); update();
}
showSelectedStaffName('driverid', 'driver-name-preview', 'No driver selected');
showSelectedStaffName('assistantid', 'assistant-name-preview', 'No assistant selected');
showSelectedStaffName('vehicleid', 'vehicle-name-preview', 'No vehicle selected');
</script>
</body></html>

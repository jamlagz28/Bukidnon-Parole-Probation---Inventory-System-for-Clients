<?php
include 'config/database.php';

/* COUNT CASE STATUS */
$active = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM clients WHERE status='Active'"));
$terminated = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM clients WHERE status='Terminated'"));
$revoked = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM clients WHERE status='Revoked'"));
$denied = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM clients WHERE status='Denied'"));

/* RECENT CLIENTS */
$recent = mysqli_query($conn,"SELECT * FROM clients ORDER BY id DESC LIMIT 5");

/* PLACEHOLDER DATA FOR MONTHLY GRAPH */
$month_labels = ["January","February","March","April","May","June"];
$month_data = [0,0,0,0,0,0]; // Demo placeholder

/* PLACEHOLDER DATA FOR BARANGAY GRAPH */
$barangay_labels = ["Brgy. Alae","Brgy. Poblacion"];
$barangay_data = [3,2]; // Demo placeholder
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
body{font-family: Arial;margin:0;background:#f4f6f9;}
.header{background:#2c3e50;color:white;padding:15px;font-size:20px;}
.container{padding:20px;}
.search-box{margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.search-box input{padding:10px;width:300px;}
.search-box button{padding:10px;cursor:pointer;background:#28a745;color:white;border:none;border-radius:5px;}
.search-box button:hover{background:#218838;}
.cards{display:flex;gap:20px;margin-bottom:30px;}
.card{flex:1;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);text-align:center;}
.card h3{margin:0;}
.card p{font-size:22px;margin-top:10px;}
.graph-row{display:flex;gap:20px;justify-content:space-between;margin-bottom:50px;}
.graph{background:white;padding:15px;border-radius:8px;flex:1;height:260px;}
.graph canvas{width:100% !important;height:100% !important;}
table{width:100%;border-collapse:collapse;background:white;margin-top:20px;}
th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;}
th{background:#eee;}
.status-dropdown{padding:5px;border-radius:4px;}
/* Modal Styles */
.modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.5);}
.modal-content{background:#fff;margin:10% auto;padding:20px;border-radius:8px;width:400px;position:relative;}
.close-btn{position:absolute;top:10px;right:15px;font-size:20px;cursor:pointer;}
.modal-content input, .modal-content select{width:100%;padding:8px;margin:5px 0;border-radius:4px;border:1px solid #ccc;}
.modal-content button{padding:10px;width:100%;background:#28a745;color:white;border:none;border-radius:5px;cursor:pointer;margin-top:10px;}
.modal-content button:hover{background:#218838;}
</style>
</head>
<body>

<div class="header">Parole & Probation Administration System</div>
<div class="container">

<!-- SEARCH + ADD CLIENT BUTTON -->
<div class="search-box">
<form action="search.php" method="GET" style="display:flex;gap:10px;">
<input type="text" name="search" placeholder="Search by Name, Docket # or CC Number">
<button type="submit">Search</button>
</form>
<!-- Add Client Icon/Button -->
<button id="addClientBtn">&#x2795; Add Client</button>
</div>

<!-- STATISTICS CARDS -->
<div class="cards">
<div class="card"><h3>Active</h3><p id="activeCount"><?php echo $active; ?></p></div>
<div class="card"><h3>Terminated</h3><p id="terminatedCount"><?php echo $terminated; ?></p></div>
<div class="card"><h3>Revoked</h3><p id="revokedCount"><?php echo $revoked; ?></p></div>
<div class="card"><h3>Denied</h3><p id="deniedCount"><?php echo $denied; ?></p></div>
</div>

<!-- GRAPHS SIDE BY SIDE -->
<div class="graph-row">
    <div class="graph"><h3 style="text-align:center;">Case Status</h3><canvas id="caseChart"></canvas></div>
    <div class="graph"><h3 style="text-align:center;">Clients per Month</h3><canvas id="monthChart"></canvas></div>
    <div class="graph"><h3 style="text-align:center;">Clients per Barangay</h3><canvas id="barangayChart"></canvas></div>
</div>

<!-- RECENT CLIENTS TABLE -->
<h3>Recent Clients</h3>
<table id="clientsTable">
<tr>
<th>Docket #</th><th>Name</th><th>Offense</th><th>Court</th><th>Status</th>
</tr>
<?php while($row = mysqli_fetch_assoc($recent)){ ?>
<tr>
<td><?php echo $row['docket_number']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['offense']; ?></td>
<td><?php echo $row['court']; ?></td>
<td>
  <select class="status-dropdown" data-client-id="<?php echo $row['id']; ?>">
    <option value="Active" <?php if($row['status']=='Active') echo 'selected'; ?>>Active</option>
    <option value="Terminated" <?php if($row['status']=='Terminated') echo 'selected'; ?>>Terminated</option>
    <option value="Revoked" <?php if($row['status']=='Revoked') echo 'selected'; ?>>Revoked</option>
    <option value="Denied" <?php if($row['status']=='Denied') echo 'selected'; ?>>Denied</option>
  </select>
</td>
</tr>
<?php } ?>
</table>

</div>

<!-- ADD CLIENT MODAL -->
<div id="addClientModal" class="modal">
<div class="modal-content">
<span class="close-btn">&times;</span>
<h3>Add New Client</h3>
<input type="text" id="docket_number" placeholder="Docket Number" required>
<input type="text" id="name" placeholder="Name" required>
<input type="text" id="offense" placeholder="Offense" required>
<input type="text" id="court" placeholder="Court" required>
<select id="status">
  <option value="Active">Active</option>
  <option value="Terminated">Terminated</option>
  <option value="Revoked">Revoked</option>
  <option value="Denied">Denied</option>
</select>
<input type="text" id="address" placeholder="Address">
<button id="saveClientBtn">Save Client</button>
</div>
</div>

<script>
// GRAPHS
const ctxCase = document.getElementById('caseChart');
const caseChart = new Chart(ctxCase,{type:'bar',data:{labels:['Active','Terminated','Revoked','Denied'],datasets:[{data:[<?php echo $active;?>,<?php echo $terminated;?>,<?php echo $revoked;?>,<?php echo $denied;?>],backgroundColor:['#28a745','#17a2b8','#ffc107','#dc3545'],borderRadius:6}]},options:{plugins:{legend:{display:false}},responsive:true,maintainAspectRatio:false}});

const ctxMonth = document.getElementById('monthChart');
new Chart(ctxMonth,{type:'line',data:{labels: <?php echo json_encode($month_labels); ?>, datasets:[{label:'Clients per Month', data: <?php echo json_encode($month_data); ?>, borderColor:'#17a2b8', backgroundColor:'rgba(23,130,184,0.2)', tension:0.3, fill:true}]},options:{plugins:{legend:{display:false}},responsive:true,maintainAspectRatio:false}});

const ctxBarangay = document.getElementById('barangayChart');
new Chart(ctxBarangay,{type:'bar',data:{labels: <?php echo json_encode($barangay_labels); ?>, datasets:[{label:'Clients per Barangay', data: <?php echo json_encode($barangay_data); ?>, backgroundColor:'#ffc107', borderRadius:6}]},options:{plugins:{legend:{display:false}},responsive:true,maintainAspectRatio:false, scales:{x:{ticks:{autoSkip:false}}}}});

// AJAX Status Update
$(document).ready(function(){
  $('.status-dropdown').change(function(){
      var clientId = $(this).data('client-id');
      var newStatus = $(this).val();
      $.ajax({
          url:'update_status.php', type:'POST', data:{id:clientId,status:newStatus},
          success:function(response){
              if(response=='success'){
                  $.getJSON('get_case_counts.php', function(data){
                      $('#activeCount').text(data.Active);
                      $('#terminatedCount').text(data.Terminated);
                      $('#revokedCount').text(data.Revoked);
                      $('#deniedCount').text(data.Denied);
                      caseChart.data.datasets[0].data=[data.Active,data.Terminated,data.Revoked,data.Denied];
                      caseChart.update();
                  });
              } else alert('Update failed!');
          }
      });
  });
});

// MODAL
const modal = document.getElementById('addClientModal');
const btn = document.getElementById('addClientBtn');
const span = document.getElementsByClassName('close-btn')[0];

btn.onclick = ()=>modal.style.display='block';
span.onclick = ()=>modal.style.display='none';
window.onclick = (e)=>{if(e.target==modal) modal.style.display='none';}

// SAVE CLIENT VIA AJAX
$('#saveClientBtn').click(function(){
    let docket = $('#docket_number').val();
    let name = $('#name').val();
    let offense = $('#offense').val();
    let court = $('#court').val();
    let status = $('#status').val();
    let address = $('#address').val();

    if(docket && name && offense && court){
        $.ajax({
            url:'add_client.php', type:'POST',
            data:{docket_number:docket,name:name,offense:offense,court:court,status:status,address:address},
            success:function(response){
                if(response=='success'){
                    alert('Client added successfully!');
                    modal.style.display='none';
                    location.reload(); // reload to update table & graphs
                } else alert('Failed to add client!');
            }
        });
    } else alert('Please fill all required fields.');
});
</script>
</body>
</html>
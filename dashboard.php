<?php
include 'config/database.php';

/* ------------------------------
   CASE STATUS COUNTS
--------------------------------*/
$active = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Active'"))['total'];
$terminated = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Terminated'"))['total'];
$revoked = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Revoked'"))['total'];
$denied = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Denied'"))['total'];

/* ------------------------------
   CLIENT LIST
--------------------------------*/
$all_clients = mysqli_query($conn,"SELECT * FROM clients ORDER BY name ASC");

/* ------------------------------
   MONTHLY CLIENT DATA
--------------------------------*/
$month_labels = [];
$month_data = [];
for($m=1;$m<=12;$m++){
    $monthName = date("F", mktime(0,0,0,$m,10));
    $month_labels[] = $monthName;

    $query = mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE MONTH(created_at)='$m'");
    $row = mysqli_fetch_assoc($query);
    $month_data[] = $row['total'];
}

/* ------------------------------
   BARANGAY CLIENT DATA
--------------------------------*/
$barangay_labels = [];
$barangay_data = [];
$barangay_map = [];

// Predefined barangay coordinates
$barangay_coords = [
    "Alae" => ["lat"=>8.2900, "lng"=>125.0400],
    "Poblacion" => ["lat"=>8.3000, "lng"=>125.0500],
    "San Isidro" => ["lat"=>8.3100, "lng"=>125.0600],
    "Manolo Fortich Proper" => ["lat"=>8.3200, "lng"=>125.0700]
];

$barangay_query = mysqli_query($conn,"SELECT address, COUNT(*) total FROM clients GROUP BY address ORDER BY total DESC");
while($row=mysqli_fetch_assoc($barangay_query)){
    $barangay_labels[] = $row['address'];
    $barangay_data[] = $row['total'];
    $lat = isset($barangay_coords[$row['address']]) ? $barangay_coords[$row['address']]['lat'] : 8.2;
    $lng = isset($barangay_coords[$row['address']]) ? $barangay_coords[$row['address']]['lng'] : 125.0;
    $barangay_map[] = ['barangay'=>$row['address'],'total'=>$row['total'],'lat'=>$lat,'lng'=>$lng];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Parole & Probation Administration System</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
body{font-family:Arial;margin:0;background:#f4f6f9;}
.header{background:#2c3e50;color:white;padding:15px;display:flex;justify-content:space-between;align-items:center;}
.container{padding:20px;}
.cards{display:flex;gap:20px;margin-bottom:30px;}
.card{flex:1;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);text-align:center;}
.card p{font-size:22px;margin-top:10px;}
.graph-row{display:flex;gap:20px;margin-bottom:40px;}
.graph{flex:1;background:white;padding:15px;border-radius:8px;height:260px;}
table{width:100%;border-collapse:collapse;background:white;margin-top:20px;}
th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;}
th{background:#eee;}
.search-box{margin-bottom:20px;position:relative;display:flex;gap:10px;}
.search-box input{padding:10px;width:250px;}
.search-box select{padding:10px;border-radius:5px;border:1px solid #ccc;}
.search-box button{padding:10px;cursor:pointer;background:#28a745;color:white;border:none;border-radius:5px;}
.search-box button:hover{background:#218838;}
#searchSuggestions{position:absolute;background:white;border:1px solid #ccc;width:250px;max-height:150px;overflow-y:auto;z-index:100;}
#searchSuggestions div{padding:8px;cursor:pointer;}
#searchSuggestions div:hover{background:#f1f1f1;}
#clientModal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.5);}
.modal-content{background:#fff;margin:10% auto;padding:20px;border-radius:8px;width:400px;position:relative;}
.close-btn{position:absolute;top:10px;right:15px;font-size:20px;cursor:pointer;}
#map{width:100%;height:400px;margin-top:20px;border-radius:8px;}
</style>
</head>
<body>

<div class="header">
<span>Parole & Probation Administration System</span>
<a href="logout.php" style="background:#dc3545;color:white;padding:8px 15px;border-radius:5px;text-decoration:none;">Logout</a>
</div>

<div class="container">

<!-- SEARCH AND EXPORT -->
<div class="search-box">
<input type="text" id="searchClient" placeholder="Search by Name or Docket #">
<select id="statusFilter">
    <option value="All">All</option>
    <option value="Active">Active</option>
    <option value="Terminated">Terminated</option>
    <option value="Revoked">Revoked</option>
    <option value="Denied">Denied</option>
</select>
<button id="exportCsvBtn">Export CSV</button>
<div id="searchSuggestions"></div>
</div>

<!-- CLIENT INFO DISPLAY -->
<div id="clientInfo" style="margin-bottom:20px;"></div>

<!-- DASHBOARD CARDS -->
<div class="cards">
<div class="card"><h3>Active</h3><p id="activeCount"><?php echo $active; ?></p></div>
<div class="card"><h3>Terminated</h3><p id="terminatedCount"><?php echo $terminated; ?></p></div>
<div class="card"><h3>Revoked</h3><p id="revokedCount"><?php echo $revoked; ?></p></div>
<div class="card"><h3>Denied</h3><p id="deniedCount"><?php echo $denied; ?></p></div>
</div>

<!-- CHARTS -->
<div class="graph-row">
<div class="graph"><h3 style="text-align:center;">Case Status</h3><canvas id="caseChart"></canvas></div>
<div class="graph"><h3 style="text-align:center;">Clients Per Month</h3><canvas id="monthChart"></canvas></div>
<div class="graph"><h3 style="text-align:center;">Clients Per Barangay</h3><canvas id="barangayChart"></canvas></div>
</div>

<!-- CLIENT TABLE -->
<h3>Client List</h3>
<table id="clientsTable">
<tr><th>Docket #</th><th>Name</th><th>Offense</th><th>Court</th><th>Status</th></tr>
<?php while($row=mysqli_fetch_assoc($all_clients)){ ?>
<tr class="client-row" data-docket="<?php echo $row['docket_number'];?>" data-name="<?php echo $row['name'];?>" data-offense="<?php echo $row['offense'];?>" data-court="<?php echo $row['court'];?>" data-status="<?php echo $row['status'];?>" data-address="<?php echo $row['address'];?>">
<td><?php echo $row['docket_number'];?></td>
<td><?php echo $row['name'];?></td>
<td><?php echo $row['offense'];?></td>
<td><?php echo $row['court'];?></td>
<td><?php echo $row['status'];?></td>
</tr>
<?php } ?>
</table>

<!-- LEAFLET MAP -->
<h3>Clients per Barangay Map</h3>
<div id="map"></div>

<!-- CLIENT INFO MODAL -->
<div id="clientModal">
<div class="modal-content">
<span class="close-btn">&times;</span>
<h3>Client Information</h3>
<p id="modalDocket"></p>
<p id="modalName"></p>
<p id="modalOffense"></p>
<p id="modalCourt"></p>
<p id="modalStatus"></p>
<p id="modalAddress"></p>
</div>
</div>

</div>

<script>
// ----------------------- CHARTS -----------------------
const caseChart = new Chart(document.getElementById('caseChart'), {
type:'bar',
data:{labels:['Active','Terminated','Revoked','Denied'],datasets:[{data:[<?php echo $active;?>,<?php echo $terminated;?>,<?php echo $revoked;?>,<?php echo $denied;?>],backgroundColor:['#28a745','#17a2b8','#ffc107','#dc3545']}]},
options:{plugins:{legend:{display:false}},responsive:true}
});

new Chart(document.getElementById('monthChart'),{
type:'line',
data:{labels:<?php echo json_encode($month_labels); ?>,datasets:[{data:<?php echo json_encode($month_data); ?>,borderColor:'#17a2b8',backgroundColor:'rgba(23,130,184,0.2)',fill:true,tension:0.4}]},
options:{plugins:{legend:{display:false}},responsive:true}
});

new Chart(document.getElementById('barangayChart'),{
type:'bar',
data:{labels:<?php echo json_encode($barangay_labels); ?>,datasets:[{data:<?php echo json_encode($barangay_data); ?>,backgroundColor:'#ffc107'}]},
options:{plugins:{legend:{display:false}},responsive:true}
});

// ----------------------- LEAFLET MAP -----------------------
var map = L.map('map').setView([8.2,125.0], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'&copy; OpenStreetMap contributors'
}).addTo(map);

var barangays = <?php echo json_encode($barangay_map); ?>;
barangays.forEach(function(b){
    L.marker([b.lat, b.lng]).addTo(map)
     .bindPopup('<b>'+b.barangay+'</b><br>Total Clients: '+b.total);
});

// ----------------------- LIVE SEARCH -----------------------
$('#searchClient').on('keyup', function(){
    let val = $(this).val().toLowerCase();
    if(val.length>0){
        let suggestions = '';
        $('#clientsTable tr.client-row').each(function(){
            let name = $(this).data('name').toLowerCase();
            if(name.indexOf(val) !== -1){
                suggestions += '<div>'+$(this).data('name')+'</div>';
            }
        });
        $('#searchSuggestions').html(suggestions).show();
    }else $('#searchSuggestions').hide();
});

$(document).on('click','#searchSuggestions div',function(){
    let name = $(this).text();
    let row = $('#clientsTable tr.client-row').filter(function(){ return $(this).data('name')==name; });
    if(row.length>0){
        $('#modalDocket').text('Docket #: '+row.data('docket'));
        $('#modalName').text('Name: '+row.data('name'));
        $('#modalOffense').text('Offense: '+row.data('offense'));
        $('#modalCourt').text('Court: '+row.data('court'));
        $('#modalStatus').text('Status: '+row.data('status'));
        $('#modalAddress').text('Address: '+row.data('address'));
        $('#clientModal').fadeIn();
        $('#searchSuggestions').hide();
    }
});

// CLOSE MODAL
$('.close-btn').click(()=>$('#clientModal').fadeOut());
$(window).click(function(e){if(e.target.id=='clientModal') $('#clientModal').fadeOut();});

// ----------------------- EXPORT CSV WITH FILTER -----------------------
$('#exportCsvBtn').click(function(){
    let status = $('#statusFilter').val();
    let csv = [];
    $('#clientsTable tr.client-row').each(function(){
        let rowStatus = $(this).data('status');
        if(status==='All' || rowStatus===status){
            let row = [];
            $(this).find('td').each(function(){row.push('"'+$(this).text().trim()+'"');});
            csv.push(row.join(','));
        }
    });
    // Add header
    csv.unshift('"Docket #","Name","Offense","Court","Status"');
    let blob = new Blob([csv.join("\n")], { type:'text/csv;charset=utf-8;' });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "clients_list.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});
</script>

</body>
</html>
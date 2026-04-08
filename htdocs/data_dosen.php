<?php
include "db.php";

$data = mysqli_query($conn,"SELECT * FROM dosen");
?>

<!DOCTYPE html>
<html>
<head>

<title>Data Dosen</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
padding:40px;
}

.container{
background:white;
padding:30px;
border-radius:10px;
width:600px;
box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

table{
width:100%;
border-collapse:collapse;
margin-top:20px;
}

th,td{
border:1px solid #ddd;
padding:10px;
text-align:left;
}

th{
background:#1F618D;
color:white;
}

</style>

</head>

<body>

<div class="container">

<h2>Daftar Dosen</h2>

<table>

<tr>
<th>No</th>
<th>Nama</th>
</tr>

<?php
$no = 1;
while($d = mysqli_fetch_array($data)){
?>

<tr>

<td><?php echo $no++; ?></td>

<td>
<a href="detail_dosen.php?id=<?php echo $d['id']; ?>">
<?php echo $d['nama_lengkap']; ?>
</a>
</td>

</tr>

<?php } ?>

</table>

<br>

<a href="dashboard.php">← Kembali ke Dashboard</a>

</div>

</body>
</html>
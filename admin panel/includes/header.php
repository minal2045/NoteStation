<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NoteStation Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    background: linear-gradient(135deg,#eef2ff,#f8fbff);
    font-family: 'Segoe UI', sans-serif;
}
.sidebar{
    height:100vh;
    background:white;
    padding:25px;
    box-shadow:5px 0 20px rgba(0,0,0,0.05);
}
.sidebar a{
    display:block;
    padding:12px;
    border-radius:12px;
    margin-bottom:10px;
    text-decoration:none;
    color:#444;
}
.sidebar a:hover,
.sidebar a.active{
    background:#eef2ff;
    color:#4361ee;
}
.card{
    border:none;
    border-radius:20px;
    box-shadow:0 10px 25px rgba(0,0,0,0.05);
}
</style>
</head>
<body>

<div class="container-fluid">
<div class="row">
<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
// base path para imágenes y assets (maneja subcarpetas)
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '') $base = '.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>InModa</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <script defer src="<?= $base ?>/assets/js/app.js"></script>
</head>
<body>

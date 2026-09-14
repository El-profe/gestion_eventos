<?php
require_once __DIR__ . '/../../helpers/AuthHelper.php';
AuthHelper::initSession();
$usuarioSesion = AuthHelper::obtenerUsuario();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($titulo_pagina ?? 'UAB Eventos — Sistema de Gestión') ?></title>
  
  <!-- Tipografías y Bootstrap 5.3 Oficial -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  
  <!-- Estilos propios -->
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
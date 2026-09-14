<?php
// gestion_eventos/index.php (Puente en la raíz)
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: public/index.php' . $query);
exit();
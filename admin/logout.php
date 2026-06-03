<?php
session_start();
session_unset(); // Supprime toutes les variables de session
session_destroy(); // Détruit la session
header("Location: ../hello/login.php"); // Redirection vers login.php
exit();
?>
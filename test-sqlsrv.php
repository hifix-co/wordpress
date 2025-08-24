<?php
try {
    $conn = new PDO(
        "sqlsrv:Server=tcp:TU-SERVIDOR.database.windows.net,1433;Database=TU_BASE;Encrypt=Yes;TrustServerCertificate=No",
        "USUARIO",
        "PASSWORD"
    );
    echo "Conexión OK a Azure SQL!";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}
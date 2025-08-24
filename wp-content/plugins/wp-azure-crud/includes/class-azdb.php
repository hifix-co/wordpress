<?php
if (!defined('ABSPATH')) exit;

class AZ_DB {
  private static $conn = null; // sqlsrv resource

  private static function conn() {
    if (self::$conn) return self::$conn;

    foreach (['AZSQL_HOST','AZSQL_DB','AZSQL_USER','AZSQL_PASS'] as $c) {
      if (!defined($c)) wp_die('WP Azure SQL CRUD: Falta '.$c.' en wp-config.php');
    }

    $server = AZSQL_HOST; // ej: tcp:sql-weastus2-prd-hifix.database.windows.net,1433
    $options = [
      "Database" => AZSQL_DB,
      "UID" => AZSQL_USER,                 // usa el mismo usuario que probaste
      "PWD" => AZSQL_PASS,
      "Encrypt" => 1,
      "TrustServerCertificate" => (defined('AZSQL_TRUST') && AZSQL_TRUST) ? 1 : 0,
      "LoginTimeout" => 15,
      "CharacterSet" => "UTF-8",
      "APP" => "WP-Azure-CRUD",
      "HostNameInCertificate" => "*.database.windows.net",
    ];

    $conn = @sqlsrv_connect($server, $options);
    if ($conn === false) {
      $errs = sqlsrv_errors(SQLSRV_ERR_ALL);
      $msg = 'Conexión SQLSRV falló';
      if ($errs) {
        $chunks = [];
        foreach ($errs as $e) $chunks[] = "[{$e['SQLSTATE']}] {$e['code']}: {$e['message']}";
        $msg .= ' — '.implode(' | ', $chunks);
      }
      wp_die(esc_html($msg));
    }
    self::$conn = $conn;
    return self::$conn;
  }

  public static function query($sql, $params = []) {
    $stmt = sqlsrv_query(self::conn(), $sql, array_values($params), ["Scrollable" => SQLSRV_CURSOR_KEYSET]);
    if ($stmt === false) self::die_sqlsrv();
    $rows = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
      foreach ($row as $k=>$v) if ($v instanceof DateTimeInterface) $row[$k] = $v->format('Y-m-d H:i:s');
      $rows[] = $row;
    }
    sqlsrv_free_stmt($stmt);
    return $rows;
  }

  public static function execute($sql, $params = []) {
    $stmt = sqlsrv_query(self::conn(), $sql, array_values($params));
    if ($stmt === false) self::die_sqlsrv();
    sqlsrv_free_stmt($stmt);
    return true;
  }

  private static function die_sqlsrv() {
    $errs = sqlsrv_errors(SQLSRV_ERR_ALL);
    $msg = 'Error SQLSRV';
    if ($errs) {
      $parts = [];
      foreach ($errs as $e) $parts[] = "[{$e['SQLSTATE']}] {$e['code']}: {$e['message']}";
      $msg .= ' — '.implode(' | ', $parts);
    }
    wp_die(esc_html($msg));
  }
}

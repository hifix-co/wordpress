<?php
if (!defined('ABSPATH')) exit;

class AZ_DB {
  /** @var PDO */
  private static $pdo;

  public static function pdo() {
    if (self::$pdo) return self::$pdo;

    $encrypt = defined('AZSQL_ENCRYPT') && AZSQL_ENCRYPT ? 'Yes' : 'No';
    $trust   = defined('AZSQL_TRUST')   && AZSQL_TRUST   ? 'Yes' : 'No';

    $dsn = "sqlsrv:Server=".AZSQL_HOST.";Database=".AZSQL_DB.";Encrypt=$encrypt;TrustServerCertificate=$trust;LoginTimeout=30";

    try {
      self::$pdo = new PDO($dsn, AZSQL_USER, AZSQL_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 20
      ]);
    } catch (Throwable $e) {
      wp_die('Error de conexión a Azure SQL: '.esc_html($e->getMessage()));
    }
    return self::$pdo;
  }

  public static function query($sql, $params = []) {
    $stmt = self::pdo()->prepare($sql);
    foreach ($params as $k => $v) {
      $type = is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR;
      $stmt->bindValue(is_int($k) ? $k+1 : $k, $v, $type);
    }
    $stmt->execute();
    return $stmt;
  }
}

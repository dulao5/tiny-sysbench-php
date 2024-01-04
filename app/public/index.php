<?php
// 定义常量
define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ? getenv('DB_PORT') : 4000);
define('DB_NAME', getenv('DB_NAME') ? getenv('DB_NAME') : 'test');
define('DB_USER', getenv('DB_USER') ? getenv('DB_USER') : 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ? getenv('DB_PASSWORD') : '');

define('SYSBENCH_MULTI_DB', getenv('SYSBENCH_MULTI_DB') ? getenv('SYSBENCH_MULTI_DB') : false);
define('OLTP_DB_COUNT', getenv('OLTP_DB_COUNT') ? getenv('OLTP_DB_COUNT') : 50);
define('OLTP_TABLE_COUNT', getenv('OLTP_TABLE_COUNT') ? getenv('OLTP_TABLE_COUNT') : 100);
define('OLTP_TABLE_SIZE', getenv('OLTP_TABLE_SIZE') ? getenv('OLTP_TABLE_SIZE') : 10000);

define('OLTP_POINT_SELECTS', 10);
define('OLTP_SIMPLE_RANGES', 1);
define('OLTP_SUM_RANGES', 1);
define('OLTP_ORDER_RANGES', 1);
define('OLTP_DISTINCT_RANGES', 1);
define('OLTP_INDEX_UPDATES', 1);
define('OLTP_NON_INDEX_UPDATES', 1);

define('OLTP_RANGE_SIZE', 100);
define('SBTEST_COLUMN_C_LENGTH', 120);
define('SBTEST_COLUMN_PAD_LENGTH', 60);

if (SYSBENCH_MULTI_DB) {
    $dbId = mt_rand(0, OLTP_DB_COUNT-1);
    $dbName = sprintf("sbtest%03d", $dbId);
    define('PDO_DSN', 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.$dbName);
    echo("connecting to the database: " . PDO_DSN);
} else {
    define('PDO_DSN', 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME);
}

// 创建 PDO 对象
try {
    $pdo = new PDO(PDO_DSN, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error connecting to the database: " . $e->getMessage());
}

// query 函数
function query($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo "$sql , [ " . join(' , ', $params) . " ]\n";
    // 如果是 SELECT 查询，读取并废弃结果集
    if (strpos(strtoupper($sql), 'SELECT') === 0) {
        while ($stmt->fetch()) {
            //print_r($row);
        }
    }
}

// 获取随机表名
function getRandomTableName() {
    $tableId = mt_rand(1, OLTP_TABLE_COUNT);
    return "sbtest" . $tableId;
}

// 获取随机 ID
function getRandomId() {
    return mt_rand(1, OLTP_TABLE_SIZE);
}

function getRandomString($length = 60) {
    // 生成一半长度的随机字节，因为bin2hex后长度会翻倍
    $bytes = random_bytes($length / 2);

    // 将字节转换为十六进制字符串
    $string = bin2hex($bytes);

    // 如果需要的长度是奇数，则移除最后一个字符
    if ($length % 2 != 0) {
        $string = substr($string, 0, -1);
    }

    return $string;
}

// 执行 OLTP 场景查询
function executeOltpScenarios($pdo) {
    $randomTableName = getRandomTableName();

    // Point Selects
    for ($i = 0; $i < OLTP_POINT_SELECTS; $i++) {
        $id = getRandomId();
        query($pdo, "SELECT c FROM $randomTableName WHERE id = ?", [$id]);
    }

    // Simple Ranges
    for ($i = 0; $i < OLTP_SIMPLE_RANGES; $i++) {
        $from = getRandomId();
        $to = $from + 100; // Assuming a range of 100
        query($pdo, "SELECT c FROM $randomTableName WHERE id BETWEEN ? AND ?", [$from, $to]);
    }

    // Sum Ranges
    for ($i = 0; $i < OLTP_SUM_RANGES; $i++) {
        $from = getRandomId();
        $to = $from + OLTP_RANGE_SIZE; // Assuming a range of 100
        query($pdo, "SELECT SUM(c) FROM $randomTableName WHERE id BETWEEN ? AND ?", [$from, $to]);
    }

    // Order Ranges
    for ($i = 0; $i < OLTP_ORDER_RANGES; $i++) {
        $from = getRandomId();
        $to = $from + OLTP_RANGE_SIZE; // Assuming a range of 100
        query($pdo, "SELECT c FROM $randomTableName WHERE id BETWEEN ? AND ? ORDER BY c", [$from, $to]);
    }

    // Distinct Ranges
    for ($i = 0; $i < OLTP_DISTINCT_RANGES; $i++) {
        $from = getRandomId();
        $to = $from + OLTP_RANGE_SIZE; // Assuming a range of 100
        query($pdo, "SELECT DISTINCT c FROM $randomTableName WHERE id BETWEEN ? AND ?", [$from, $to]);
    }

    // Index Updates
    for ($i = 0; $i < OLTP_INDEX_UPDATES; $i++) {
        query($pdo, "UPDATE $randomTableName SET k = k + 1 WHERE id = ?", [getRandomId()]);
    }

    // Non Index Updates
    for ($i = 0; $i < OLTP_NON_INDEX_UPDATES; $i++) {
        query($pdo, "UPDATE $randomTableName SET c = ? WHERE id = ?", [getRandomString(SBTEST_COLUMN_C_LENGTH), getRandomId()]);
    }

    // Delete and Insert
    $id = getRandomId();
    query($pdo, "DELETE FROM $randomTableName WHERE id = ?", [$id]);
    query($pdo,
          "INSERT INTO $randomTableName (id, k, c, pad) VALUES (?, ?, ?, ?)", 
          [
              $id,
              getRandomId(),
              getRandomString(SBTEST_COLUMN_C_LENGTH),
              getRandomString(SBTEST_COLUMN_PAD_LENGTH)
         ]);
}

// 执行 OLTP 测试
executeOltpScenarios($pdo);


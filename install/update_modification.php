<?php
/**
 * Update OCMOD in database from file (no reinstall needed).
 * Run from opencart root: php extension/termopab/install/update_modification.php
 * Then: Extensions → Modifications → Refresh
 */
$root = realpath(__DIR__ . '/../../..');
if (!$root || !is_dir($root . '/admin')) {
    die("Error: Cannot find OpenCart root. Run from opencart dir: php extension/termopab/install/update_modification.php\n");
}

require $root . '/admin/config.php';

$xmlFile = __DIR__ . '/category_layout_mod.ocmod.xml';
if (!is_file($xmlFile)) {
    die("Error: File not found: $xmlFile\n");
}

$xml = file_get_contents($xmlFile);
if ($xml === false || trim($xml) === '') {
    die("Error: Empty or unreadable XML file.\n");
}

$modName = 'Termopab: Category Layout Custom Fields UI';
$conn = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($conn->connect_error) {
    die("Error: DB connect failed: " . $conn->connect_error . "\n");
}

$conn->set_charset('utf8mb4');
$table = DB_PREFIX . 'modification';
$xmlEsc = $conn->real_escape_string($xml);

$sql = "UPDATE `{$table}` SET `xml` = '{$xmlEsc}' WHERE `name` = '" . $conn->real_escape_string($modName) . "'";
$res = $conn->query($sql);

if (!$res) {
    die("Error: " . $conn->error . "\n");
}
if ($conn->affected_rows === 0) {
    die("Warning: No rows updated. Modification '{$modName}' not found in DB. Install it first.\n");
}

echo "OK: Modification updated. Go to Extensions → Modifications → Refresh\n";
$conn->close();

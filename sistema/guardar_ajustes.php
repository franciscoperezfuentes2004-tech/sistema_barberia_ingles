<?php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();

header("Content-Type: application/json; charset=UTF-8");
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    ob_clean();
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/auth_middleware.php';
verificarJWT();

require_once __DIR__ . "/conexion.php";

$data_json = json_decode(file_get_contents("php://input"), true);

// Todos los campos posibles que envía el CMS
$campos = [
    'nombre_empresa' => htmlspecialchars(trim($_POST['site_name'] ?? $data_json['site_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'site_phone' => htmlspecialchars(trim($_POST['site_phone'] ?? $data_json['site_phone'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'site_email' => htmlspecialchars(trim($_POST['site_email'] ?? $data_json['site_email'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'site_address' => htmlspecialchars(trim($_POST['site_address'] ?? $data_json['site_address'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'site_map' => trim($_POST['site_map'] ?? $data_json['site_map'] ?? ''),
    'site_instagram' => htmlspecialchars(trim($_POST['site_instagram'] ?? $data_json['site_instagram'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'site_facebook' => htmlspecialchars(trim($_POST['site_facebook'] ?? $data_json['site_facebook'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'site_tiktok' => htmlspecialchars(trim($_POST['site_tiktok'] ?? $data_json['site_tiktok'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'site_slogan' => htmlspecialchars(trim($_POST['site_slogan'] ?? $data_json['site_slogan'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'site_hero_desc' => htmlspecialchars(trim($_POST['site_hero_desc'] ?? $data_json['site_hero_desc'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'stat_exp' => htmlspecialchars(trim($_POST['stat_exp'] ?? $data_json['stat_exp'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'stat_clientes' => htmlspecialchars(trim($_POST['stat_clientes'] ?? $data_json['stat_clientes'] ?? ''), ENT_QUOTES, 'UTF-8')
];

// Opcionales (Base64 largos)
$logo = trim($_POST['site_logo'] ?? $data_json['site_logo'] ?? '');
$hero_bg = trim($_POST['site_hero_bg'] ?? $data_json['site_hero_bg'] ?? '');

$check = mysqli_query($conexion, "SELECT COUNT(*) as total FROM `ajustes`");
if ($check) {
    $fila = mysqli_fetch_assoc($check);
    if ((int)$fila['total'] > 0) {
        // UPDATE
        $updates = [];
        $types = "";
        $params = [];
        foreach ($campos as $col => $val) {
            $updates[] = "`$col` = ?";
            $types .= "s";
            $params[] = $val;
        }
        if (!empty($logo)) {
            $updates[] = "`logo` = ?";
            $types .= "s";
            $params[] = $logo;
        }
        if (!empty($hero_bg)) {
            $updates[] = "`site_hero_bg` = ?";
            $types .= "s";
            $params[] = $hero_bg;
        }
        
        $sql = "UPDATE `ajustes` SET " . implode(", ", $updates);
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } else {
        // INSERT
        $cols = array_keys($campos);
        $vals = array_values($campos);
        if (!empty($logo)) {
            $cols[] = 'logo';
            $vals[] = $logo;
        }
        if (!empty($hero_bg)) {
            $cols[] = 'site_hero_bg';
            $vals[] = $hero_bg;
        }
        
        $placeholders = implode(", ", array_fill(0, count($cols), "?"));
        $sql = "INSERT INTO `ajustes` (`" . implode("`, `", $cols) . "`) VALUES ($placeholders)";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            $types = str_repeat("s", count($vals));
            mysqli_stmt_bind_param($stmt, $types, ...$vals);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

mysqli_close($conexion);
ob_clean();

if (isset($success) && $success) {
    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Ajustes guardados exitosamente."]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "No se pudieron guardar los ajustes."]);
}

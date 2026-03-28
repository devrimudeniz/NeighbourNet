<?php
header('Content-Type: application/json');
echo json_encode([
    'loaded_ini' => php_ini_loaded_file(),
    'disable_functions' => ini_get('disable_functions')
]);
?>

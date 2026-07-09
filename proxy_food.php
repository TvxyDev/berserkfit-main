<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$query = $_GET['search_terms'] ?? '';
$barcode = $_GET['barcode'] ?? '';

if (empty($barcode) && empty($query)) {
    echo json_encode(['products' => []]);
    exit;
}

// Configuração de Cache
$cacheDir = __DIR__ . '/cache_food';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

// Gerar chave única para o cache (baseada na URL do pedido)
$cacheKey = !empty($barcode) ? "barcode_" . $barcode : "search_" . md5($query);
$cacheFile = $cacheDir . '/' . $cacheKey . '.json';
$cacheTime = 3600; // 1 hora de cache

// Se o cache existir e for recente, devolver do ficheiro
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    echo file_get_contents($cacheFile);
    exit;
}

// Se não houver cache, fazer o pedido à API
if (!empty($barcode)) {
    $url = "https://world.openfoodfacts.org/api/v0/product/" . urlencode($barcode) . ".json";
} else {
    // API V2 (Normalmente mais moderna e estável que o cgi/search.pl)
    $url = "https://world.openfoodfacts.org/cgi/search.pl?search_terms=" . urlencode($query) . "&search_simple=1&action=process&json=1&page_size=20";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 25);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Accept-Language: pt-PT,pt;q=0.9,en-US;q=0.8,en;q=0.7'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode == 503) {
    // Se falhar e tivermos um cache antigo, devolvemos o antigo mesmo que tenha expirado
    if (file_exists($cacheFile)) {
        echo file_get_contents($cacheFile);
        exit;
    }
    http_response_code(503);
    echo json_encode(['error' => 'API Indisponível (503)', 'message' => 'O servidor oficial está ocupado. Tente o scanner!']);
    exit;
}

if ($httpCode >= 400) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'Erro na API: ' . $httpCode]);
    exit;
}

// Guardar no cache apenas se for uma resposta válida (com produtos ou dados)
$data = json_decode($response, true);
if ($data && (isset($data['products']) || isset($data['product']))) {
    file_put_contents($cacheFile, $response);
}

echo $response;
?>
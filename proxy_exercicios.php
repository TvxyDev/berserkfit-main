<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$query = trim($_GET['q'] ?? '');

if (empty($query)) {
    echo json_encode([]);
    exit;
}

// ════════════ CONFIGURAÇÃO ════════════
$rapidApiKey = "585445edb3msh775eea16b474abfp119369jsn39572fa880cd";
$cacheDir    = __DIR__ . '/cache_exercicios';
$gifsDatasetPath = __DIR__ . '/API/gifs_dataset.json';
// ══════════════════════════════════════

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

// Dicionário PT→EN Universal (Categorizado)
$translations = [
    // --- PEITO ---
    'voador'                 => 'pec deck fly',
    'pec deck'               => 'pec deck fly',
    'pack deck'              => 'pec deck fly',
    'peck deck'              => 'pec deck fly',
    'butterfly'              => 'pec deck fly',
    'voador peitoral'        => 'pec deck fly',
    'voador dorsal'          => 'rear delt fly machine',
    'crucifixo'              => 'dumbbell fly',
    'crucifixo inclinado'    => 'incline dumbbell fly',
    'crucifixo cabo'         => 'cable fly',
    'crucifixo polia'        => 'cable fly',
    'crossover'              => 'cable crossover',
    'supino com halteres'    => 'dumbbell bench press',
    'supino com barra'       => 'barbell bench press',
    'supino inclinado'       => 'incline bench press',
    'supino declinado'       => 'decline bench press',
    'supino'                 => 'bench press',
    'peito'                  => 'chest press',

    // --- OMBROS ---
    'elevação lateral'       => 'lateral raise',
    'elevacao lateral'       => 'lateral raise',
    'elevações laterais'     => 'lateral raise',
    'elevacoes laterais'     => 'lateral raise',
    'elevação frontal'       => 'front raise',
    'elevacao frontal'       => 'front raise',
    'elevações frontais'     => 'front raise',
    'elevacoes frontais'     => 'front raise',
    'desenvolvimento'        => 'shoulder press',
    'militar'                => 'military press',
    'press ombros'           => 'shoulder press',
    'encolhimento'           => 'shrug',
    'ombros'                 => 'shoulder press',

    // --- COSTAS ---
    'puxada frontal'         => 'lat pulldown',
    'puxada'                 => 'lat pulldown',
    'serrote'                => 'dumbbell row',
    'remada curvada'         => 'bent over row',
    'remada sentada'         => 'seated row',
    'remada'                 => 'row',
    'barra fixa'             => 'pull up',
    'puxadas'                => 'lat pulldowns',
    'remadas'                => 'rows',
    'peso morto'             => 'deadlift',
    'costas'                 => 'back row',

    // --- PERNAS ---
    'extensora'              => 'leg extension',
    'cadeira extensora'      => 'leg extension',
    'flexora'                => 'leg curl',
    'mesa flexora'           => 'leg curl',
    'panturrilha'            => 'calf raise',
    'gémeos'                 => 'calf raise',
    'gemeos'                 => 'calf raise',
    'leg press'              => 'leg press',
    'agachamento'            => 'squat',
    'agachamento livre'      => 'barbell squat',
    'afundo'                 => 'lunge',
    'avançar'                => 'lunge',
    'adutora'                => 'adductor',
    'abdutora'               => 'abductor',
    'cadeira'                => 'leg extension',
    'pernas'                 => 'leg press',

    // --- BRAÇOS ---
    'rosca direta'           => 'biceps curl',
    'rosca martelo'          => 'hammer curl',
    'rosca concentrada'      => 'concentration curl',
    'rosca scott'            => 'preacher curl',
    'scott'                  => 'preacher curl',
    'scoot'                  => 'preacher curl',
    'triceps corda'          => 'triceps rope pushdown',
    'tricep corda'           => 'triceps rope pushdown',
    'triceps testa'          => 'skull crusher',
    'tricep testa'           => 'skull crusher',
    'triceps pulley'         => 'triceps pushdown',
    'biceps'                 => 'biceps curl',
    'triceps'                => 'triceps extension',
    'paralelas'              => 'triceps dip',

    // --- MODIFICADORES E EQUIPAMENTO ---
    'halteres'               => 'dumbbell',
    'haltere'                => 'dumbbell',
    'barra'                  => 'barbell',
    'cabos'                  => 'cable',
    'cabo'                   => 'cable',
    'polia'                  => 'cable',
    'maquina'                => 'machine',
    'máquina'                => 'machine',
    'aparelho'               => 'machine',
    'com '                   => '', 
    'na '                    => '',
    'no '                    => '',
];

$searchTerm = strtolower($query);

// Tenta encontrar a tradução mais específica primeiro
foreach ($translations as $pt => $en) {
    if (strpos($searchTerm, $pt) !== false) {
        $searchTerm = str_replace($pt, $en, $searchTerm);
        // Se já traduzimos o termo principal, limpamos preposições restantes do PT
        $searchTerm = str_replace([' na ', ' no ', ' com ', ' em '], ' ', ' ' . $searchTerm . ' ');
        break;
    }
}
$searchTerm = trim($searchTerm);

$cacheFile = $cacheDir . '/ex_' . md5($searchTerm) . '.json';
$cacheTime = 86400 * 7; // 7 dias

// Servir cache se válido
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    echo file_get_contents($cacheFile);
    exit;
}

// Chamar ExerciseDB para obter metadata
$url = "https://exercisedb.p.rapidapi.com/exercises/name/" . urlencode($searchTerm) . "?limit=20&offset=0";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => [
        "X-RapidAPI-Host: exercisedb.p.rapidapi.com",
        "X-RapidAPI-Key: $rapidApiKey",
    ],
]);
$response = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);

    if (is_array($data) && count($data) > 0) {
        // Carregar dataset pirata
        $gifsDataset = [];
        if (file_exists($gifsDatasetPath)) {
            $gifsDataset = json_decode(file_get_contents($gifsDatasetPath), true) ?: [];
        }

        // Função auxiliar para normalizar nomes (remover espaços extras, pontuação) para comparar
        function cleanName($str) {
            return strtolower(preg_replace('/[^a-z0-9]/i', '', $str));
        }

        // A ExerciseDB gratuita não retorna gifUrl — vamos cruzar os dados com a DB pirata
        $normalised = array_map(function ($ex) use ($gifsDataset) {
            if (empty($ex['gifUrl'])) {
                $targetName = cleanName($ex['name']);
                $foundGif = '';
                
                // Procurar no array pirata
                $fallbackGif = '';
                foreach ($gifsDataset as $pGif) {
                    $cTitle = cleanName($pGif['title']);
                    if ($cTitle === $targetName) {
                        $foundGif = $pGif['gif_url'];
                        break; // Bingo! O nome é exatamente o que procuramos
                    }
                    if ($fallbackGif === '' && (strpos($cTitle, $targetName) !== false || strpos($targetName, $cTitle) !== false)) {
                        $fallbackGif = $pGif['gif_url']; // Encontrou um parecido, guarda caso não achemos o exato
                    }
                }
                
                if ($foundGif === '') {
                    $foundGif = $fallbackGif;
                }
                
                $ex['gifUrl'] = $foundGif; // Fica a String do gif achado ou vazio
            }
            return $ex;
        }, $data);

        // FILTRAR: Apenas mostrar os que têm GIF verificado no nosso sistema
        $normalised = array_filter($normalised, function($ex) {
            return !empty($ex['gifUrl']);
        });
        $normalised = array_values($normalised); // Reindexar array

        // Se a API não devolveu nenhum GIF válido, avançamos para o Fallback local para garantir resultados
        if (empty($normalised)) {
            goto local_fallback;
        }

        $output = json_encode($normalised, JSON_UNESCAPED_UNICODE);
        file_put_contents($cacheFile, $output);
        echo $output;
        exit;
    }
}

local_fallback:
// Fallback: se a API não retornou nada ou falhou, procurar apenas na BD pirata local
$foundLocalGif = '';
if (!isset($gifsDataset)) {
    // Carregar se não foi carregado acima
    $gifsDatasetPath = __DIR__ . '/API/gifs_dataset.json';
    $gifsDataset = file_exists($gifsDatasetPath) ? (json_decode(file_get_contents($gifsDatasetPath), true) ?: []) : [];
}

function cleanFallbackName($str) {
    return strtolower(preg_replace('/[^a-z0-9]/i', '', $str));
}

$targetNameFallback = cleanFallbackName($searchTerm);
$fallbacks = [];

foreach ($gifsDataset as $pGif) {
    if (count($fallbacks) >= 15) break; // Limite de 15 resultados no fallback
    
    $cTitle = cleanFallbackName($pGif['title']);
    // Prioridade para quem começa pela palavra ou contém a palavra
    if (strpos($cTitle, $targetNameFallback) !== false || strpos($targetNameFallback, $cTitle) !== false) {
        $fallbacks[] = [
            'name'      => $pGif['title'],
            'equipment' => 'various',
            'target'    => 'chest',
            'gifUrl'    => $pGif['gif_url'],
            'id'        => 'local_' . md5($pGif['title'])
        ];
    }
}

// Se não achou nada mesmo, envia um vazio ou o termo original
if (empty($fallbacks)) {
    $fallbacks[] = [
        'name'      => $searchTerm,
        'equipment' => 'various',
        'target'    => 'various',
        'gifUrl'    => ''
    ];
}

$output = json_encode($fallbacks, JSON_UNESCAPED_UNICODE);
file_put_contents($cacheFile, $output);
echo $output;
?>

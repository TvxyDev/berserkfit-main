<?php
function extrair_url_projeto($content, $tipo) {
    if (empty($content)) return 'VAZIO';
    
    // Suportando aspas duplas do CSV ("") ou normais (")
    preg_match_all('/href=["\']?([^"\'>\s]+)["\']?/', $content, $matches);
    
    if (isset($matches[1])) {
        foreach ($matches[1] as $url) {
            // Limpa aspas extras que o CSV pode trazer
            $url = trim($url, '"\'');

            if ($tipo == 'email' && strpos($url, 'mailto:') !== false) {
                return str_replace('mailto:', '', $url);
            }
            if (in_array($tipo, ['instagram', 'facebook', 'maps']) && strpos($url, $tipo) !== false) {
                return $url;
            }
            if ($tipo == 'website' && 
                strpos($url, 'instagram') === false && 
                strpos($url, 'facebook') === false && 
                strpos($url, 'google') === false && 
                strpos($url, 'mailto:') === false &&
                strpos($url, 'http') !== false) {
                return $url;
            }
        }
    }
    return 'NÃO ENCONTRADO';
}

function extrair_telefone_projeto($content) {
    if (empty($content)) return 'VAZIO';
    // Regex ajustada para ser mais flexível com espaços e tags
    if (preg_match('/CONTACTOS<\/strong><br \/>\s*([0-9\s+]{9,15})/', $content, $matches)) {
        return trim($matches[1]);
    }
    return 'NÃO ENCONTRADO';
}

// TESTE COM O CONTEÚDO DO SEU EXCEL
$exemplo_excel = '<p><strong>CONTACTOS</strong><br />251 404 404<br /><a href=""mailto:info@termasdemelgaco.pt"">info@termasdemelgaco.pt</a><br /><a href=""https://www.termasdemelgaco.pt/"" target=""_blank"">Site</a></p>';

echo "TESTE DE EXTRAÇÃO:\n";
echo "Telefone: " . extrair_telefone_projeto($exemplo_excel) . "\n";
echo "Email: " . extrair_url_projeto($exemplo_excel, 'email') . "\n";
echo "Website: " . extrair_url_projeto($exemplo_excel, 'website') . "\n";

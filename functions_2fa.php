<?php
/**
 * Funções para Autenticação de Dois Fatores (2FA)
 * BerserkFit - Sistema de 2FA usando Google Authenticator
 */

require_once __DIR__ . '/vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\ImageChartsQRCodeProvider;

/**
 * Retorna uma instância do TwoFactorAuth
 */
function get_tfa_instance()
{
    // Usar ImageChartsQRCodeProvider para gerar QR Codes sem dependências locais complexas
    $qrProvider = new ImageChartsQRCodeProvider();
    return new TwoFactorAuth($qrProvider, 'BerserkFit');
}

/**
 * Gera um novo segredo 2FA e salva na base de dados
 * @param mysqli $conn Conexão com a base de dados
 * @param int $user_id ID do utilizador
 * @return string O segredo gerado
 */
function generate_tfa_secret($conn, $user_id)
{
    // Se $conn não for passado, tenta usar a global
    if (!$conn) {
        global $conn;
    }

    $tfa = get_tfa_instance();
    $secret = $tfa->createSecret();

    // Salvar o segredo na base de dados
    $stmt = $conn->prepare("UPDATE user SET tfa_secret = ? WHERE id_user = ?");
    $stmt->bind_param("si", $secret, $user_id);
    $stmt->execute();
    $stmt->close();

    return $secret;
}

/**
 * Gera a URL do QR Code para configuração no Google Authenticator
 * @param string $secret O segredo 2FA
 * @param string $username Nome do utilizador
 * @return string URL do QR Code
 */
function get_tfa_qr_code_url($secret, $username)
{
    $tfa = get_tfa_instance();
    return $tfa->getQRCodeImageAsDataUri($username, $secret);
}

/**
 * Verifica se o código fornecido é válido
 * @param string $secret O segredo 2FA do utilizador
 * @param string $code O código de 6 dígitos fornecido
 * @return bool True se o código for válido
 */
function verify_tfa_code($secret, $code)
{
    if (empty($secret) || empty($code))
        return false;
    $tfa = get_tfa_instance();
    return $tfa->verifyCode($secret, $code, 1); // 1 = discrepância de tempo (30s)
}

/**
 * Desativa o 2FA para um utilizador
 * @param mysqli $conn Conexão com a base de dados
 * @param int $user_id ID do utilizador
 */
function disable_tfa($conn, $user_id)
{
    // Se $conn não for passado, tenta usar a global
    if (!$conn) {
        global $conn;
    }

    $stmt = $conn->prepare("UPDATE user SET tfa_secret = NULL WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Verifica se o utilizador tem 2FA ativo
 * @param mysqli $conn Conexão com a base de dados
 * @param int $user_id ID do utilizador
 * @return bool True se o 2FA estiver ativo
 */
function user_has_tfa_enabled($conn, $user_id)
{
    // Se $conn não for passado, tenta usar a global
    if (!$conn) {
        global $conn;
    }

    $stmt = $conn->prepare("SELECT tfa_secret FROM user WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return !empty($user['tfa_secret']);
}

/**
 * Registra ação do utilizador (log)
 * @param mysqli $conn Conexão com a base de dados
 * @param int $user_id ID do utilizador
 * @param string $action Descrição da ação
 */
function log_user_action($conn, $user_id, $action)
{
    error_log("User $user_id: $action");
}

/**
 * Gera um conjunto de códigos de backup aleatórios (8 caracteres + hífen)
 * @param int $count Quantidade de códigos a gerar
 * @return array Lista de códigos em texto simples
 */
function generate_backup_codes($count = 8)
{
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        // Formato: ABCD-1234
        $code = strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));
        $codes[] = $code;
    }
    return $codes;
}

/**
 * Salva os códigos de backup na base de dados (em hash para segurança)
 * @param mysqli $conn Conexão com a base de dados
 * @param int $user_id ID do utilizador
 * @param array $codes Lista de códigos em texto simples
 */
function save_backup_codes($conn, $user_id, $codes)
{
    $hashed_codes = array_map(function ($code) {
        return password_hash($code, PASSWORD_DEFAULT);
    }, $codes);

    $json_codes = json_encode($hashed_codes);

    $stmt = $conn->prepare("UPDATE user SET tfa_backup_codes = ? WHERE id_user = ?");
    $stmt->bind_param("si", $json_codes, $user_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Verifica se um código de backup é válido e o remove se for usado
 * @param mysqli $conn Conexão com a base de dados
 * @param int $user_id ID do utilizador
 * @param string $input_code Código inserido pelo utilizador
 * @return bool True se for válido
 */
function verify_and_use_backup_code($conn, $user_id, $input_code)
{
    $stmt = $conn->prepare("SELECT tfa_backup_codes FROM user WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || empty($user['tfa_backup_codes'])) {
        return false;
    }

    $hashed_codes = json_decode($user['tfa_backup_codes'], true);
    if (!is_array($hashed_codes)) return false;

    foreach ($hashed_codes as $index => $hashed_code) {
        if (password_verify(strtoupper($input_code), $hashed_code)) {
            // Código válido! Remover da lista para não ser usado de novo
            unset($hashed_codes[$index]);
            $new_json = json_encode(array_values($hashed_codes));
            
            $update = $conn->prepare("UPDATE user SET tfa_backup_codes = ? WHERE id_user = ?");
            if (!$update) return true; // Falhou a remover mas o código era válido

            $update->bind_param("si", $new_json, $user_id);
            $update->execute();
            $update->close();
            
            return true;
        }
    }

    return false;
}


<?php
// api/cleanup_expired_ads.php - Script para excluir anúncios em draft não pagos há mais de 24 horas.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

$logFile = __DIR__ . '/../logs/cleanup-expired-ads.log';

function cleanup_ad_log(string $message): void
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $message" . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND);
}

try {
    cleanup_ad_log('Iniciando limpeza de anúncios expirados em draft...');

    $pdo = db();
    $pdo->beginTransaction();

    // Buscar anúncios em draft há mais de 24 horas
    $expiredThreshold = date('Y-m-d H:i:s', time() - (24 * 3600));

    $stmt = $pdo->prepare('
        SELECT id, title, user_id, created_at
        FROM ads
        WHERE status = "draft"
          AND created_at < :threshold
    ');
    $stmt->execute([':threshold' => $expiredThreshold]);
    $expiredAds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalDeleted = 0;

    foreach ($expiredAds as $ad) {
        $adId = (int)$ad['id'];

        // Buscar e deletar mídias
        $mediaStmt = $pdo->prepare('SELECT filepath FROM ad_media WHERE ad_id = :ad_id');
        $mediaStmt->execute([':ad_id' => $adId]);
        $mediaFiles = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mediaFiles as $media) {
            delete_media_file($media['filepath']);
        }

        // Deletar anúncio (CASCADE remove mídias e outras refs)
        $deleteStmt = $pdo->prepare('DELETE FROM ads WHERE id = :id');
        $deleteStmt->execute([':id' => $adId]);

        // Registrar auditoria
        record_audit(
            1,
            'ad_draft_expired_deleted',
            'ads',
            $adId,
            [
                'title' => $ad['title'],
                'user_id' => (int)$ad['user_id'],
                'created_at' => $ad['created_at'],
                'deleted_at' => now(),
            ]
        );

        $totalDeleted++;
        cleanup_ad_log("Anúncio #$adId excluído (draft expirado em {$ad['created_at']}): {$ad['title']}");
    }

    $pdo->commit();

    cleanup_ad_log("Limpeza concluída: $totalDeleted anúncios excluídos.");

    // Se executado via browser (para testes), exibir resultado
    if (PHP_SAPI !== 'cli') {
        echo json_encode([
            'success' => true,
            'deleted_ads' => $totalDeleted,
            'message' => "$totalDeleted anúncios em draft expirados excluídos com sucesso.",
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $errorMsg = 'Erro ao limpar anúncios expirados: ' . $exception->getMessage();
    cleanup_ad_log($errorMsg);
    error_log($errorMsg);

    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Falha ao executar limpeza de anúncios expirados.',
        ], JSON_UNESCAPED_UNICODE);
    }
}

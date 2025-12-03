<?php
/**
 * API para gerenciamento de buscas salvas (APENAS PREMIUM)
 * 
 * GET /anuncios/api/save-search.php - Listar buscas salvas do usuário
 * POST /anuncios/api/save-search.php - Criar nova busca salva (PREMIUM ONLY)
 * DELETE /anuncios/api/save-search.php?id=X - Deletar busca salva (PREMIUM ONLY)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../sqlconnect.php';
require_once __DIR__ . '/../../login_system/middleware.php';

// Verifica autenticação
if (!current_user()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$user = current_user();
$userId = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();

// GET - Listar buscas salvas do usuário (qualquer pessoa logada)
if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare('
            SELECT id, search_name, search_params, notify, created_at
            FROM saved_searches
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ');
        $stmt->execute([':user_id' => $userId]);
        $searches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON nos search_params
        foreach ($searches as &$search) {
            $search['search_params'] = json_decode($search['search_params'], true);
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'searches' => $searches,
            'count' => count($searches),
            'is_premium' => (bool)$user['is_premium']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// POST - Criar nova busca salva (APENAS PREMIUM)
if ($method === 'POST') {
    try {
        // Verificar se é premium
        if (!$user['is_premium']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Apenas usuários premium podem salvar buscas',
                'requires_premium' => true
            ]);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true);

        if (!isset($body['search_name']) || empty(trim($body['search_name']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nome da busca é obrigatório']);
            exit;
        }

        if (!isset($body['search_params']) || !is_array($body['search_params'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parâmetros de busca inválidos']);
            exit;
        }

        $searchName = trim($body['search_name']);
        $searchParams = json_encode($body['search_params'], JSON_UNESCAPED_UNICODE);
        $notify = isset($body['notify']) ? (int)$body['notify'] : 1;

        // Verificar limite de 10 buscas salvas por usuário (premium)
        $countStmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM saved_searches WHERE user_id = :user_id');
        $countStmt->execute([':user_id' => $userId]);
        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['cnt'];

        if ($count >= 10) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Limite de 10 buscas salvas atingido',
                'current_count' => $count
            ]);
            exit;
        }

        $stmt = $pdo->prepare('
            INSERT INTO saved_searches (user_id, search_name, search_params, notify, created_at, updated_at)
            VALUES (:user_id, :search_name, :search_params, :notify, NOW(), NOW())
        ');

        $stmt->execute([
            ':user_id' => $userId,
            ':search_name' => $searchName,
            ':search_params' => $searchParams,
            ':notify' => $notify
        ]);

        $searchId = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Busca salva com sucesso',
            'search_id' => (int)$searchId,
            'search_name' => $searchName
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// DELETE - Deletar busca salva (APENAS PREMIUM)
if ($method === 'DELETE') {
    try {
        // Verificar se é premium
        if (!$user['is_premium']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Apenas usuários premium podem deletar buscas',
                'requires_premium' => true
            ]);
            exit;
        }

        $id = $_GET['id'] ?? null;

        if (!$id || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID da busca inválido']);
            exit;
        }

        // Verificar se a busca pertence ao usuário
        $checkStmt = $pdo->prepare('
            SELECT id FROM saved_searches
            WHERE id = :id AND user_id = :user_id
        ');
        $checkStmt->execute([':id' => (int)$id, ':user_id' => $userId]);

        if (!$checkStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Busca não encontrada ou não é sua']);
            exit;
        }

        $stmt = $pdo->prepare('
            DELETE FROM saved_searches
            WHERE id = :id AND user_id = :user_id
        ');

        $stmt->execute([':id' => (int)$id, ':user_id' => $userId]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Busca removida com sucesso',
            'search_id' => (int)$id
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Método não suportado
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método HTTP não suportado']);
?>

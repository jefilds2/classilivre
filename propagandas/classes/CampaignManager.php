<?php

declare(strict_types=1);

class PropagandaCampaignManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create(array $data): int {
        $ad_type = (int)($data['ad_type'] ?? 1);
        $sql = "INSERT INTO propagandas_campanhas (title, ad_type, status, description, image_url, affiliate_link, cta_text, phone, whatsapp, city, html_code, pages, position, priority_weight, display_frequency, display_mode, rotation_type, initial_delay_ms, reopen_delay_ms, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $title = trim($data['title'] ?? 'Sem Título');
        $description = trim($data['description'] ?? 'Sem descrição');
        $stmt->execute([$title, $ad_type, $data['status'] ?? 'ativa', $description, $data['image_url'] ?? null, $data['affiliate_link'] ?? null, $data['cta_text'] ?? 'Saiba Mais', $data['phone'] ?? null, $data['whatsapp'] ?? null, $data['city'] ?? null, $data['html_code'] ?? null, json_encode($data['pages'] ?? ['home']), $data['position'] ?? 'center', (int)($data['priority_weight'] ?? 1), $data['display_frequency'] ?? 'por_sessao', $data['display_mode'] ?? 'normal', $data['rotation_type'] ?? 'random', (int)($data['initial_delay_ms'] ?? 3000), (int)($data['reopen_delay_ms'] ?? 300000), $data['created_by'] ?? null]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM propagandas_campanhas WHERE id = ?");
        $stmt->execute([$id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($campaign) $campaign['pages'] = json_decode($campaign['pages'], true) ?? [];
        return $campaign ?: null;
    }

    public function list(array $filters = []): array {
        try {
            $sql = "SELECT c.*, 
                    COALESCE(m.views_count, 0) as total_visualizacoes,
                    COALESCE(m.clicks_count, 0) as total_cliques
                    FROM propagandas_campanhas c
                    LEFT JOIN propaganda_metrics m ON c.id = m.campaign_id
                    WHERE 1=1";
            $params = [];
            if (!empty($filters['status'])) { $sql .= " AND c.status = ?"; $params[] = $filters['status']; }
            if (!empty($filters['ad_type'])) { $sql .= " AND c.ad_type = ?"; $params[] = (int)$filters['ad_type']; }
            if (!empty($filters['page'])) { $sql .= " AND JSON_SEARCH(c.pages, 'one', ?) IS NOT NULL"; $params[] = $filters['page']; }
            $sql .= " ORDER BY c.priority_weight DESC, c.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($campaigns as &$c) {
                $c['pages'] = json_decode($c['pages'], true) ?? [];
                $c['total_visualizacoes'] = (int)($c['total_visualizacoes'] ?? 0);
                $c['total_cliques'] = (int)($c['total_cliques'] ?? 0);
            }
            return $campaigns;
        } catch (Throwable $e) {
            error_log('CampaignManager::list - ' . $e->getMessage());
            return [];
        }
    }

    public function getActiveForPage(string $page): array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM propagandas_campanhas WHERE status = 'ativa' AND JSON_SEARCH(pages, 'one', ?) IS NOT NULL ORDER BY priority_weight DESC, created_at DESC");
            $stmt->execute([$page]);
            $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($campaigns as &$c) $c['pages'] = json_decode($c['pages'], true) ?? [];
            return $campaigns;
        } catch (Throwable $e) {
            error_log('CampaignManager::getActiveForPage - ' . $e->getMessage());
            return [];
        }
    }

    public function getActiveByType(int $ad_type, string $page = ''): array {
        try {
            $sql = "SELECT * FROM propagandas_campanhas WHERE status = 'ativa' AND ad_type = ?";
            $params = [$ad_type];
            if (!empty($page)) { $sql .= " AND JSON_SEARCH(pages, 'one', ?) IS NOT NULL"; $params[] = $page; }
            $sql .= " ORDER BY priority_weight DESC, created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($campaigns as &$c) $c['pages'] = json_decode($c['pages'], true) ?? [];
            return $campaigns;
        } catch (Throwable $e) {
            error_log('CampaignManager::getActiveByType - ' . $e->getMessage());
            return [];
        }
    }

    public function update(int $id, array $data): bool {
        $allowed = ['title', 'ad_type', 'status', 'description', 'image_url', 'affiliate_link', 'cta_text', 'phone', 'whatsapp', 'city', 'html_code', 'pages', 'position', 'priority_weight', 'display_frequency', 'display_mode', 'rotation_type', 'initial_delay_ms', 'reopen_delay_ms'];
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $fields[] = "{$key} = ?";
                $params[] = ($key === 'pages') ? json_encode($value) : $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE propagandas_campanhas SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM propagandas_campanhas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateStatus(int $id, string $status): bool {
        if ($status !== 'ativa' && $status !== 'inativa') return false;
        $stmt = $this->pdo->prepare("UPDATE propagandas_campanhas SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function uploadImage(array $file, int $max_size = 5242880): ?string {
        try {
            if (!isset($file['tmp_name'])) return null;
            if ($file['size'] > $max_size) throw new Exception('Arquivo muito grande');
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed, true)) throw new Exception('Tipo inválido');
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'campaign_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dir = __DIR__ . '/../img/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $path = $dir . $filename;
            if (!move_uploaded_file($file['tmp_name'], $path)) throw new Exception('Erro upload');
            return '/propagandas/img/' . $filename;
        } catch (Throwable $e) {
            error_log('CampaignManager::uploadImage - ' . $e->getMessage());
            return null;
        }
    }

    public function deleteImage(string $image_path): bool {
        try {
            $path = __DIR__ . '/..' . $image_path;
            return file_exists($path) ? @unlink($path) : true;
        } catch (Throwable $e) {
            error_log('CampaignManager::deleteImage - ' . $e->getMessage());
            return false;
        }
    }

    public function getGeneralStats(): array {
        try {
            // Obter stats de campanhas
            $stmt = $this->pdo->query("SELECT COUNT(*) as total_campanhas, SUM(CASE WHEN status = 'ativa' THEN 1 ELSE 0 END) as campanhas_ativas, SUM(CASE WHEN ad_type = 1 THEN 1 ELSE 0 END) as campanhas_web, SUM(CASE WHEN ad_type = 2 THEN 1 ELSE 0 END) as campanhas_local, SUM(CASE WHEN ad_type = 3 THEN 1 ELSE 0 END) as campanhas_embed FROM propagandas_campanhas");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obter métricas totais (somadas de todas as campanhas ativas)
            $metricsStmt = $this->pdo->query("
                SELECT 
                    COALESCE(SUM(m.views_count), 0) as total_visualizacoes,
                    COALESCE(SUM(m.clicks_count), 0) as total_cliques
                FROM propaganda_metrics m
                WHERE EXISTS (
                    SELECT 1 FROM propagandas_campanhas c 
                    WHERE c.id = m.campaign_id AND c.status = 'ativa'
                )
            ");
            $metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular CTR geral
            $total_views = (int)($metrics['total_visualizacoes'] ?? 0);
            $total_clicks = (int)($metrics['total_cliques'] ?? 0);
            $ctr_geral = $total_views > 0 ? round(($total_clicks / $total_views) * 100, 2) : 0;
            
            return [
                'total_campanhas' => (int)($stats['total_campanhas'] ?? 0),
                'campanhas_ativas' => (int)($stats['campanhas_ativas'] ?? 0),
                'campanhas_web' => (int)($stats['campanhas_web'] ?? 0),
                'campanhas_local' => (int)($stats['campanhas_local'] ?? 0),
                'campanhas_embed' => (int)($stats['campanhas_embed'] ?? 0),
                'total_visualizacoes' => $total_views,
                'total_cliques' => $total_clicks,
                'ctr_geral' => $ctr_geral
            ];
        } catch (Throwable $e) {
            error_log('CampaignManager::getGeneralStats - ' . $e->getMessage());
            return [
                'total_campanhas' => 0,
                'campanhas_ativas' => 0,
                'campanhas_web' => 0,
                'campanhas_local' => 0,
                'campanhas_embed' => 0,
                'total_visualizacoes' => 0,
                'total_cliques' => 0,
                'ctr_geral' => 0
            ];
        }
    }
}

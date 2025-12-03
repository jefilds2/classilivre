<?php
declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';

if (!function_exists('setting_value_enabled')) {
    /**
     * Normaliza valores vindos do BD para boolean.
     */
    function setting_value_enabled($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return false;
        }

        $normalized = strtolower(trim((string)$value));
        if ($normalized === '') {
            return false;
        }

        return in_array(
            $normalized,
            ['1', 'true', 'on', 'yes', 'sim', 'habilitado', 'enabled'],
            true
        );
    }
}

if (!function_exists('invalidate_coin_cache_safe')) {
    function invalidate_coin_cache_safe(int $userId): void
    {
        if (function_exists('invalidate_coin_cache')) {
            invalidate_coin_cache($userId);
            return;
        }

        if (isset($GLOBALS['_coin_cache'][$userId])) {
            unset($GLOBALS['_coin_cache'][$userId]);
        }
    }
}

if (!function_exists('summarize_exception_message')) {
    function summarize_exception_message(Throwable $throwable): string
    {
        $message = trim(preg_replace('/\s+/', ' ', (string)$throwable->getMessage()));
        if ($message === '') {
            return 'Erro inesperado';
        }

        return mb_strlen($message) > 180 ? mb_substr($message, 0, 180) . '...' : $message;
    }
}

if (!function_exists('ensure_coin_transaction_types')) {
    function ensure_coin_transaction_types(PDO $pdo): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $column = $pdo->query('SHOW COLUMNS FROM coin_transactions LIKE "type"')->fetch(PDO::FETCH_ASSOC);
            if (!$column) {
                return;
            }

            $definition = (string)($column['Type'] ?? '');
            if (strpos($definition, 'service_auto_renewal') === false || strpos($definition, 'service_renewal') === false) {
                $pdo->exec(
                    'ALTER TABLE coin_transactions MODIFY COLUMN type ' .
                    'ENUM("buy","spend","admin_add","admin_remove","refund","bonus","ad_publish","ad_feature","ad_renewal","ad_auto_renewal","service_renewal","service_auto_renewal") ' .
                    'NOT NULL COMMENT "Tipo de transacao"'
                );
            }
        } catch (Throwable $migrationError) {
            error_log('[service_renewal_helpers] Falha ao garantir enum de coin_transactions.type: ' . $migrationError->getMessage());
        }
    }
}

if (!function_exists('ensure_service_auto_renewal_schema')) {
    function ensure_service_auto_renewal_schema(PDO $pdo): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $columns = [
            'renewal_count' => 'ALTER TABLE user_service_listings ADD COLUMN renewal_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER payment_method',
            'auto_renewal_enabled' => 'ALTER TABLE user_service_listings ADD COLUMN auto_renewal_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER expires_at',
            'last_renewed_at' => 'ALTER TABLE user_service_listings ADD COLUMN last_renewed_at DATETIME NULL AFTER auto_renewal_enabled',
        ];

        foreach ($columns as $column => $sql) {
            try {
                $exists = $pdo->query('SHOW COLUMNS FROM user_service_listings LIKE ' . $pdo->quote($column))->fetch(PDO::FETCH_ASSOC);
                if (!$exists) {
                    $pdo->exec($sql);
                }
            } catch (Throwable $migrationError) {
                error_log(sprintf('[service_renewal_helpers] Falha ao garantir coluna %s: %s', $column, $migrationError->getMessage()));
            }
        }
    }
}

if (!function_exists('apply_service_renewal_update')) {
    function apply_service_renewal_update(PDO $pdo, int $userId, int $serviceId, string $expiresAt, string $now): void
    {
        $extendedSql = 'UPDATE user_service_listings SET 
                status = "active",
                payment_status = "paid",
                expires_at = :expires_at,
                published_at = COALESCE(published_at, :published_now),
                last_paid_at = :last_paid_now,
                last_renewed_at = :last_renewed_now,
                renewal_count = renewal_count + 1,
                updated_at = :updated_now
            WHERE id = :id AND user_id = :user';

        $extendedParams = [
            ':expires_at' => $expiresAt,
            ':published_now' => $now,
            ':last_paid_now' => $now,
            ':last_renewed_now' => $now,
            ':updated_now' => $now,
            ':id' => $serviceId,
            ':user' => $userId,
        ];

        try {
            $stmt = $pdo->prepare($extendedSql);
            $stmt->execute($extendedParams);
            return;
        } catch (PDOException $exception) {
            if (strpos($exception->getMessage(), 'Unknown column') === false) {
                throw $exception;
            }

            error_log(sprintf('[service_renewal_update] Colunas ausentes detectadas. Aplicando fallback. Erro: %s', $exception->getMessage()));
        }

        $fallbackSql = 'UPDATE user_service_listings SET 
                status = "active",
                payment_status = "paid",
                expires_at = :expires_at,
                published_at = COALESCE(published_at, :published_now),
                updated_at = :updated_now
            WHERE id = :id AND user_id = :user';

        $fallbackParams = [
            ':expires_at' => $expiresAt,
            ':published_now' => $now,
            ':updated_now' => $now,
            ':id' => $serviceId,
            ':user' => $userId,
        ];

        $fallback = $pdo->prepare($fallbackSql);
        $fallback->execute($fallbackParams);
    }
}

if (!function_exists('auto_renew_expired_service')) {
    /**
     * Executa a renovação automática (débitos, atualização de datas, auditoria).
     */
    function auto_renew_expired_service(array $service, array $user, ?int $coinCost = null): array
    {
        $userId = (int)$user['id'];
        $serviceId = (int)$service['id'];

        if ($coinCost === null) {
            $coinCost = (int)get_setting('service_auto_renewal_coin_cost', '1');
        }
        if ($coinCost <= 0) {
            $coinCost = 1;
        }

        try {
            $pdo = db();
            ensure_service_auto_renewal_schema($pdo);
            ensure_coin_transaction_types($pdo);

            $balance = get_user_coin_balance($userId);
            if ($balance < $coinCost) {
                return [
                    'success' => false,
                    'status_code' => 400,
                    'error' => 'Saldo insuficiente. É necessário possuir ao menos ' . $coinCost . ' Clivre Medal para renovar automaticamente.',
                ];
            }

            if (!adjust_user_coins($userId, -$coinCost, 'service_auto_renewal', $serviceId, 'user_service_listings', 'Renovacao automatica do servico #' . $serviceId)) {
                return [
                    'success' => false,
                    'status_code' => 500,
                    'error' => 'Nao foi possível debitar a moeda para renovar automaticamente. Tente novamente.',
                ];
            }

            invalidate_coin_cache_safe($userId);

            $serviceDurationDays = (int)get_setting('services_free_duration_days', '30');
            if ($serviceDurationDays <= 0) {
                $serviceDurationDays = 30;
            }

            $now = now();
            $newExpiresAt = date('Y-m-d H:i:s', strtotime($now . " +{$serviceDurationDays} days"));

            try {
                apply_service_renewal_update($pdo, $userId, $serviceId, $newExpiresAt, $now);
            } catch (Throwable $updateError) {
                adjust_user_coins($userId, $coinCost, 'refund', $serviceId, 'user_service_listings', 'Estorno renovacao automatica falhou');
                invalidate_coin_cache_safe($userId);
                error_log(sprintf('[service_auto_renewal] Falha ao aplicar renovacao (service_id=%d): %s', $serviceId, $updateError->getMessage()));

                return [
                    'success' => false,
                    'status_code' => 500,
                    'error' => 'Nao foi possível concluir a renovação automática: ' . summarize_exception_message($updateError),
                ];
            }

            record_audit($userId, 'service_auto_renewal', 'user_service_listings', $serviceId, [
                'coins_spent' => $coinCost,
                'expires_at' => $newExpiresAt,
                'mode' => 'auto_paid',
            ]);

            $newBalance = get_user_coin_balance($userId);

            return [
                'success' => true,
                'expires_at' => $newExpiresAt,
                'coins_spent' => $coinCost,
                'coin_balance' => $newBalance,
                'message' => 'Serviço renovado automaticamente. Foi debitada ' . $coinCost . ' Clivre Medal.',
            ];
        } catch (Throwable $e) {
            error_log(sprintf('[service_auto_renewal] Erro inesperado na auto-renovacao (service_id=%d): %s', $serviceId, $e->getMessage()));
            return [
                'success' => false,
                'status_code' => 500,
                'error' => 'Falha ao renovar automaticamente. Tente novamente mais tarde.',
            ];
        }
    }
}

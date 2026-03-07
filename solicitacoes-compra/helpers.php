<?php
// solicitacoes-compra/helpers.php - Funcoes utilitarias para solicitacoes de compra.

declare(strict_types=1);

if (!function_exists('purchase_request_table_exists')) {
    function purchase_request_table_exists(PDO $pdo): bool
    {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_requests'");
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('purchase_request_states')) {
    function purchase_request_states(): array
    {
        return [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapa',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceara',
            'DF' => 'Distrito Federal',
            'ES' => 'Espirito Santo',
            'GO' => 'Goias',
            'MA' => 'Maranhao',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Para',
            'PB' => 'Paraiba',
            'PR' => 'Parana',
            'PE' => 'Pernambuco',
            'PI' => 'Piaui',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondonia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'Sao Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];
    }
}

if (!function_exists('purchase_request_vehicle_types')) {
    function purchase_request_vehicle_types(): array
    {
        return [
            'carro' => 'Carro',
            'moto' => 'Moto',
            'caminhao' => 'Caminhao',
            'outros' => 'Outros',
        ];
    }
}

if (!function_exists('purchase_request_transmission_options')) {
    function purchase_request_transmission_options(): array
    {
        return [
            'Automatico',
            'Manual',
            'CVT',
            'Automatizado',
            'Nao informado',
        ];
    }
}

if (!function_exists('purchase_request_fuel_options')) {
    function purchase_request_fuel_options(): array
    {
        return [
            'Gasolina',
            'Etanol',
            'Flex',
            'Diesel',
            'Eletrico',
            'Hibrido',
            'GNV',
            'Nao informado',
        ];
    }
}

if (!function_exists('purchase_request_color_options')) {
    function purchase_request_color_options(): array
    {
        return [
            'Preto',
            'Branco',
            'Prata',
            'Cinza',
            'Vermelho',
            'Azul',
            'Verde',
            'Marrom',
            'Amarelo',
            'Laranja',
            'Bege',
            'Outra',
        ];
    }
}

if (!function_exists('purchase_request_contact_methods')) {
    function purchase_request_contact_methods(): array
    {
        return [
            'whatsapp' => 'Apenas WhatsApp',
            'phone' => 'Apenas ligacao',
            'both' => 'WhatsApp e ligacao',
        ];
    }
}

if (!function_exists('purchase_request_sanitize_phone_digits')) {
    function purchase_request_sanitize_phone_digits(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if (!$digits) {
            return '';
        }

        if (strlen($digits) >= 12 && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }
}

if (!function_exists('purchase_request_format_phone')) {
    function purchase_request_format_phone(?string $value): ?string
    {
        $digits = purchase_request_sanitize_phone_digits($value);
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($digits, 0, 2),
                substr($digits, 2, 4),
                substr($digits, 6, 4)
            );
        }

        if (strlen($digits) === 11) {
            return sprintf(
                '(%s) %s-%s',
                substr($digits, 0, 2),
                substr($digits, 2, 5),
                substr($digits, 7, 4)
            );
        }

        return $digits;
    }
}

if (!function_exists('purchase_request_phone_dial_link')) {
    function purchase_request_phone_dial_link(?string $value): ?string
    {
        $digits = purchase_request_sanitize_phone_digits($value);
        if (strlen($digits) < 10) {
            return null;
        }

        return '+55' . $digits;
    }
}

if (!function_exists('purchase_request_whatsapp_link')) {
    function purchase_request_whatsapp_link(?string $value, string $message): ?string
    {
        $digits = purchase_request_sanitize_phone_digits($value);
        if (strlen($digits) < 10) {
            return null;
        }

        return 'https://wa.me/55' . $digits . '?text=' . rawurlencode($message);
    }
}

if (!function_exists('purchase_request_parse_decimal')) {
    function purchase_request_parse_decimal(?string $value): ?float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $raw = str_replace(['R$', ' '], '', $raw);
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);

        if (!is_numeric($raw)) {
            return null;
        }

        $number = (float) $raw;
        return $number >= 0 ? $number : null;
    }
}

if (!function_exists('purchase_request_trim_or_null')) {
    function purchase_request_trim_or_null(?string $value): ?string
    {
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
}

if (!function_exists('purchase_request_remove_filter_url')) {
    function purchase_request_remove_filter_url(array $query, array $keys): string
    {
        foreach ($keys as $key) {
            unset($query[$key]);
        }
        unset($query['page']);

        return '/solicitacoes-compra/' . ($query ? '?' . http_build_query($query) : '');
    }
}

if (!function_exists('purchase_request_page_url')) {
    function purchase_request_page_url(array $query, int $page): string
    {
        $query['page'] = max(1, $page);
        return '/solicitacoes-compra/' . ($query ? '?' . http_build_query($query) : '');
    }
}

if (!function_exists('purchase_request_closure_log_table_exists')) {
    function purchase_request_closure_log_table_exists(PDO $pdo): bool
    {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_request_closure_logs'");
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('purchase_request_delete_reasons')) {
    function purchase_request_delete_reasons(): array
    {
        return [
            'sold_classilivre' => 'Vendi pelo Classi Livre',
            'sold_other' => 'Vendi por outro local',
            'no_contact' => 'Ninguem entrou em contato comigo',
            'quit_search' => 'Desisti da compra no momento',
            'other' => 'Outro motivo',
        ];
    }
}

if (!function_exists('purchase_request_delete_reason_label')) {
    function purchase_request_delete_reason_label(?string $reasonCode): string
    {
        $reasons = purchase_request_delete_reasons();
        $code = trim((string) $reasonCode);
        return $reasons[$code] ?? ($code !== '' ? $code : 'Nao informado');
    }
}

if (!function_exists('purchase_request_log_closure')) {
    function purchase_request_log_closure(
        PDO $pdo,
        int $requestId,
        int $ownerUserId,
        ?int $actorUserId,
        string $source,
        string $reasonCode,
        ?string $details = null,
        ?string $reasonLabel = null
    ): void {
        if (!purchase_request_closure_log_table_exists($pdo)) {
            return;
        }

        $source = in_array($source, ['user', 'admin', 'system'], true) ? $source : 'system';
        $reasonCode = trim($reasonCode) !== '' ? trim($reasonCode) : 'other';
        $resolvedReasonLabel = trim((string) $reasonLabel) !== ''
            ? trim((string) $reasonLabel)
            : purchase_request_delete_reason_label($reasonCode);

        $stmt = $pdo->prepare('
            INSERT INTO purchase_request_closure_logs (
                request_id,
                actor_user_id,
                owner_user_id,
                source,
                reason_code,
                reason_label,
                details,
                created_at
            ) VALUES (
                :request_id,
                :actor_user_id,
                :owner_user_id,
                :source,
                :reason_code,
                :reason_label,
                :details,
                :created_at
            )
        ');

        $stmt->execute([
            ':request_id' => $requestId,
            ':actor_user_id' => $actorUserId,
            ':owner_user_id' => $ownerUserId,
            ':source' => $source,
            ':reason_code' => $reasonCode,
            ':reason_label' => $resolvedReasonLabel,
            ':details' => purchase_request_trim_or_null($details),
            ':created_at' => now(),
        ]);
    }
}

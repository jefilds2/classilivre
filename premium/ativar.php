<?php
// premium/ativar.php - rota descontinuada. Mantida apenas para compatibilidade.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_login();
ensure_user_not_blocked();

flash_add(
    'error',
    'A ativação manual do plano premium foi descontinuada. Finalize o pagamento pelo fluxo oficial.'
);

redirect('/premium/status.php');

<?php
namespace MercadoPago;

// Autoloader simplificado - Mercado Pago classes via namespace
spl_autoload_register(function ($class) {
    // Se for classe do Mercado Pago
    if (strpos($class, 'MercadoPago\\') === 0) {
        // Vamos usar uma implementação simples baseada em HTTP requests
        // Ao invés de carregar classe de arquivo, retornamos true para evitar erro
        return true;
    }
});

class SDK {
    private static $accessToken = null;
    
    public static function setAccessToken($token) {
        self::$accessToken = $token;
    }
    
    public static function getAccessToken() {
        return self::$accessToken;
    }
}

class Item {
    public $id;
    public $title;
    public $quantity;
    public $unit_price;
    public $currency_id = 'BRL';
    public $description;
    public $picture_url;
}

class Payer {
    public $name;
    public $email;
    public $phone;
    public $identification;
    public $address;
}

class Preference {
    public $id;
    public $items = [];
    public $payer;
    public $back_urls;
    public $auto_return;
    public $notification_url;
    public $external_reference;
    public $init_point;
    
    public function save() {
        if (!SDK::getAccessToken()) {
            throw new \Exception('Access token não configurado');
        }
        
        // Montar payload
        $items = [];
        foreach ($this->items as $item) {
            $items[] = [
                'id' => $item->id ?? 'item',
                'title' => $item->title ?? '',
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->unit_price ?? 0,
                'currency_id' => $item->currency_id ?? 'BRL',
                'description' => $item->description ?? '',
                'picture_url' => $item->picture_url ?? ''
            ];
        }
        
        $payload = [
            'items' => $items,
            'back_urls' => $this->back_urls,
            'auto_return' => $this->auto_return ?? 'approved',
            'notification_url' => $this->notification_url,
            'external_reference' => $this->external_reference,
        ];
        
        if ($this->payer) {
            $payload['payer'] = [
                'name' => $this->payer->name ?? '',
                'email' => $this->payer->email ?? '',
                'phone' => $this->payer->phone ?? null,
                'identification' => $this->payer->identification ?? null,
                'address' => $this->payer->address ?? null,
            ];
        }
        
        // Fazer requisição à API do Mercado Pago
        $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . SDK::getAccessToken(),
            'Content-Type: application/json',
            'X-Idempotency-Key: ' . uniqid(),
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('Erro cURL: ' . $error);
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode !== 201) {
            $errorMsg = $data['message'] ?? $data['error'] ?? 'Erro desconhecido';
            throw new \Exception('API Error (' . $httpCode . '): ' . $errorMsg);
        }
        
        if (!isset($data['id'])) {
            throw new \Exception('Resposta inválida da API - sem ID de preference');
        }
        
        // Atribuir valores de resposta
        $this->id = $data['id'];
        $this->init_point = $data['init_point'] ?? '';
        
        if (!$this->init_point) {
            throw new \Exception('API não retornou init_point');
        }
        
        return true;
    }
}

<?php
/**
 * Profile Form Processor - Limpo e Testável
 * 
 * Responsabilidades:
 * 1. Capturar dados do formulário
 * 2. Validar dados
 * 3. Atualizar banco de dados
 * 4. Retornar resultado (sucesso/erro)
 */

class ProfileFormProcessor {
    private $pdo;
    private $userId;
    private $errors = [];
    private $data = [];
    
    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }
    
    /**
     * Processar submissão do formulário
     */
    public function process($postData) {
        // 1. Capturar dados
        $this->captureData($postData);
        
        // 2. Validar
        if (!$this->validate()) {
            return false;
        }
        
        // 3. Salvar
        if (!$this->save()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Capturar dados do formulário
     */
    private function captureData($postData) {
        // Dados de texto
        $this->data['name'] = trim((string)($postData['name'] ?? ''));
        $this->data['phone'] = preg_replace('/\D+/', '', (string)($postData['phone'] ?? ''));
        $this->data['about'] = trim((string)($postData['about'] ?? ''));
        
        // Dados de endereço
        $this->data['address_street'] = trim((string)($postData['address_street'] ?? ''));
        $this->data['address_number'] = trim((string)($postData['address_number'] ?? ''));
        $this->data['address_complement'] = trim((string)($postData['address_complement'] ?? ''));
        $this->data['address_neighborhood'] = trim((string)($postData['address_neighborhood'] ?? ''));
        $this->data['address_city'] = trim((string)($postData['address_city'] ?? ''));
        $this->data['address_state'] = strtoupper(substr(trim((string)($postData['address_state'] ?? '')), 0, 2));
        $this->data['address_postal_code'] = preg_replace('/\D+/', '', (string)($postData['address_postal_code'] ?? ''));
        
        // Checkboxes - CHAVE: usar isset() para capturar como boolean
        $this->data['show_phone'] = isset($postData['show_phone']);
        $this->data['show_email'] = isset($postData['show_email']);
        $this->data['show_address'] = isset($postData['show_address']);
        $this->data['show_whatsapp'] = isset($postData['show_whatsapp']);
        $this->data['show_document'] = isset($postData['show_document']);
        
        // Country/Phone info
        $selectedIso = strtoupper(substr((string)($postData['phone_country_iso'] ?? 'BR'), 0, 2));
        $this->data['phone_country_iso'] = $selectedIso;
        $this->data['phone_is_whatsapp'] = isset($postData['phone_is_whatsapp']);
    }
    
    /**
     * Validar dados
     */
    private function validate() {
        // Nome obrigatório
        $nameLen = function_exists('mb_strlen') ? mb_strlen($this->data['name']) : strlen($this->data['name']);
        if (empty($this->data['name']) || $nameLen < 3) {
            $this->errors[] = 'Informe seu nome completo (mínimo 3 caracteres).';
        }
        
        // Telefone (opcional, mas se preenchido, validar)
        if ($this->data['phone'] !== '' && strlen($this->data['phone']) < 10) {
            $this->errors[] = 'Telefone deve ter ao menos 10 dígitos.';
        }
        
        // Se tem estado, deve ter cidade
        if ($this->data['address_state'] !== '' && empty($this->data['address_city'])) {
            $this->errors[] = 'Selecione uma cidade.';
        }
        
        // Validar estado
        if ($this->data['address_state'] !== '') {
            $validStates = $this->getValidStates();
            if (!array_key_exists($this->data['address_state'], $validStates)) {
                $this->errors[] = 'Estado inválido.';
            }
        }
        
        // Validar comprimento do "sobre"
        $aboutLen = function_exists('mb_strlen') ? mb_strlen($this->data['about']) : strlen($this->data['about']);
        if ($aboutLen > 1000) {
            $this->errors[] = 'Campo "Sobre" não pode ter mais de 1000 caracteres.';
        }
        
        return empty($this->errors);
    }
    
    /**
     * Salvar dados no banco
     */
    private function save() {
        try {
            $query = 'UPDATE users SET
                name = :name,
                phone = :phone,
                phone_country_iso = :phone_country_iso,
                phone_is_whatsapp = :phone_is_whatsapp,
                show_phone = :show_phone,
                show_email = :show_email,
                show_address = :show_address,
                show_whatsapp = :show_whatsapp,
                show_document = :show_document,
                address_street = :address_street,
                address_number = :address_number,
                address_complement = :address_complement,
                address_neighborhood = :address_neighborhood,
                address_city = :address_city,
                address_state = :address_state,
                address_postal_code = :address_postal_code,
                about = :about,
                updated_at = NOW()
            WHERE id = :id';
            
            $stmt = $this->pdo->prepare($query);
            
            $result = $stmt->execute([
                ':name' => $this->data['name'],
                ':phone' => $this->data['phone'] !== '' ? $this->data['phone'] : null,
                ':phone_country_iso' => $this->data['phone_country_iso'],
                ':phone_is_whatsapp' => $this->data['phone_is_whatsapp'] ? 1 : 0,
                ':show_phone' => $this->data['show_phone'] ? 1 : 0,
                ':show_email' => $this->data['show_email'] ? 1 : 0,
                ':show_address' => $this->data['show_address'] ? 1 : 0,
                ':show_whatsapp' => $this->data['show_whatsapp'] ? 1 : 0,
                ':show_document' => $this->data['show_document'] ? 1 : 0,
                ':address_street' => $this->data['address_street'] !== '' ? $this->data['address_street'] : null,
                ':address_number' => $this->data['address_number'] !== '' ? $this->data['address_number'] : null,
                ':address_complement' => $this->data['address_complement'] !== '' ? $this->data['address_complement'] : null,
                ':address_neighborhood' => $this->data['address_neighborhood'] !== '' ? $this->data['address_neighborhood'] : null,
                ':address_city' => $this->data['address_city'] !== '' ? $this->data['address_city'] : null,
                ':address_state' => $this->data['address_state'] !== '' ? $this->data['address_state'] : null,
                ':address_postal_code' => $this->data['address_postal_code'] !== '' ? $this->data['address_postal_code'] : null,
                ':about' => $this->data['about'] !== '' ? $this->data['about'] : null,
                ':id' => (int)$this->userId,
            ]);
            
            if (!$result) {
                $this->errors[] = 'Erro ao atualizar banco de dados.';
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            $this->errors[] = 'Erro ao salvar: ' . $e->getMessage();
            error_log('[ProfileFormProcessor] Erro: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter erros
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Obter dados capturados
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * Estados válidos
     */
    private function getValidStates() {
        return [
            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
            'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
            'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
            'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
            'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
        ];
    }
}
?>

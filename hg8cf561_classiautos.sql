-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 12/11/2025 às 17:02
-- Versão do servidor: 8.0.43-34
-- Versão do PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `hg8cf561_classiautos`
--

DELIMITER $$
--
-- Procedimentos
--
$$

$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ads`
--

CREATE TABLE `ads` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `vehicle_type` enum('carro','moto','caminhao','outros') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'carro',
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `city` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manufacture_year` smallint UNSIGNED NOT NULL,
  `model_year` smallint UNSIGNED NOT NULL,
  `mileage` int UNSIGNED NOT NULL DEFAULT '0',
  `transmission` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fuel_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doors` tinyint UNSIGNED DEFAULT NULL,
  `license_plate` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plate_last_digit` tinyint UNSIGNED DEFAULT NULL,
  `extra_attributes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','pending','approved','rejected','blocked','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `verified_badge` tinyint(1) NOT NULL DEFAULT '0',
  `verification_document_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_reviewed_at` datetime DEFAULT NULL,
  `verification_reviewed_by` int UNSIGNED DEFAULT NULL,
  `external_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason_rejected` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  `sold_at` datetime DEFAULT NULL,
  `sold_source` enum('platform','external') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `auto_renewal_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `last_renewed_at` datetime DEFAULT NULL,
  `payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ads`
--

INSERT INTO `ads` (`id`, `user_id`, `vehicle_type`, `title`, `slug`, `description`, `price`, `city`, `state`, `brand`, `model`, `manufacture_year`, `model_year`, `mileage`, `transmission`, `fuel_type`, `color`, `doors`, `license_plate`, `plate_last_digit`, `extra_attributes`, `status`, `verified_badge`, `verification_document_path`, `verification_reviewed_at`, `verification_reviewed_by`, `external_reference`, `reason_rejected`, `created_at`, `updated_at`, `approved_at`, `sold_at`, `sold_source`, `expires_at`, `payment_status`, `auto_renewal_enabled`, `last_renewed_at`, `payment_id`, `payment_amount`, `paid_at`) VALUES
(142, 55, 'carro', 'Gla200 1.6T Enduro Teto panoramico', 'gla200-1-6t-enduro-teto-panoramico', 'Mercedes-Benz GLA 200 Enduro\r\n\r\n-Ano/Modelo: 2017\r\n-Quilometragem: 117.000 km\r\n-Versão top full opcionais | Teto solar panorâmico\r\n\r\nDestaques:\r\n• Câmbio automático de 7 marchas com dupla embreagem\r\n• Direção elétrica progressiva\r\n• Ar-condicionado digital dual zone\r\n• Central multimídia com conectividade Carplay, Android auto e GPS\r\n• Bancos em couro com ajustes elétricos\r\n• Sensor de estacionamento e câmera de ré\r\n• Pneus novos \r\n\r\n- Valor: R$ 113.990,00\r\n\r\n- Avaliamos seu usado na troca, aceitamos carta de crédito, financiamos em até 60x e dividimos sua entrada em até 21x no cartão.\r\n\r\n#MercedesBenz #GLA200 #GLAEnduro #SUVPremium #FullOpcionais #TetoPanorâmico #Financiamos #AceitamosTroca', 113990.00, 'Guanhães', 'MG', 'Mercedes-Benz', 'GLA200 1.6T ENDURO 2017 TETO PANORAMICO', 2017, 2017, 117000, 'Automático', 'Gasolina', 'Prata', 5, NULL, 9, NULL, 'approved', 1, NULL, NULL, NULL, NULL, NULL, '2025-11-11 12:21:37', '2025-11-11 12:22:13', '2025-11-11 12:22:13', NULL, NULL, '2025-12-11 12:22:13', 'paid', 0, NULL, NULL, NULL, NULL),
(143, 58, 'carro', 'Carro Volkswagen Saveiro Trendline completo', 'carro-volkswagen-saveiro-trendline-completo', '✅Motor 1.6\r\n✅Roda de Liga aro 15\r\n✅Direção hidráulica \r\n✅Ar-condicionado \r\n✅Multimídia \r\n✅Câmera de ré \r\n✅Sensor de estacionamento \r\n✅Vidros e trava elétrica \r\n✅Airbags \r\n✅Freio ABS\r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33) 99102-6213\r\nInstagram @opcaoveiculosguanhaes', 69000.00, 'Guanhães', 'MG', 'Volkswagen', 'Saveiro', 2020, 2021, 66000, 'Manual', 'Flex', 'Prata', 2, NULL, 9, NULL, 'approved', 1, NULL, '2025-11-11 13:39:07', 3, NULL, NULL, '2025-11-11 13:35:55', '2025-11-11 13:39:09', '2025-11-11 13:39:09', NULL, NULL, '2025-12-11 13:39:09', 'paid', 0, NULL, NULL, NULL, NULL),
(144, 58, 'carro', 'Volkswagen Amarok 2020', 'volkswagen-amarok-2020', '✅ 180cv\r\n✅ Tração 4x4\r\n✅ Direção Hidráulica \r\n✅ Ar-condicionado Dual zone \r\n✅ Multimídia \r\n✅ Câmera de ré\r\n✅ Sensor traseiro \r\n✅Banco de Couro com regulagem elétrica\r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33) 99102-6213\r\nInstagram @opcaoveiculosguanhaes', 150000.00, 'Guanhães', 'MG', 'Volkswagen', 'Amarok', 2019, 2020, 76000, 'Automático', 'Diesel', 'Branco', 4, NULL, 3, NULL, 'approved', 1, NULL, NULL, NULL, NULL, NULL, '2025-11-11 14:17:35', '2025-11-11 14:19:16', '2025-11-11 14:19:16', NULL, NULL, '2025-12-11 14:19:16', 'paid', 0, NULL, NULL, NULL, NULL),
(145, 58, 'carro', 'Ford F250 XLT 2000', 'ford-f250-xlt-2000', '✅ Motor 4.2\r\n✅ Direção hidráulica \r\n✅ Ar-condicionado \r\n✅ Multimídia \r\n✅ Roda de liga\r\n✅ Retrovisor elétrico \r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33) 99102-6213\r\nInstagram @opcaoveiculosguanhaes', 145000.00, 'Guanhães', 'MG', 'Ford', 'F250', 2000, 2000, 363000, 'Manual', 'Diesel', 'Prata', 2, NULL, 5, NULL, 'approved', 1, NULL, NULL, NULL, NULL, NULL, '2025-11-11 14:22:09', '2025-11-11 14:27:19', '2025-11-11 14:27:19', NULL, NULL, '2025-12-11 14:27:19', 'paid', 0, NULL, NULL, NULL, NULL),
(146, 59, 'carro', 'Carro Jeep Compass completo', 'carro-jeep-compass-completo', 'Jeep Compass 2022 em ótimo estado, 64.500 km, combustível Flex, câmbio Automático. Revisões em dia e documentação ok.', 142900.00, 'Guanhães', 'MG', 'Jeep', 'Compass', 2021, 2022, 64500, 'Automático', 'Flex', 'Branco', 4, NULL, 3, NULL, 'approved', 1, NULL, NULL, NULL, NULL, NULL, '2025-11-11 14:42:03', '2025-11-12 12:07:47', '2025-11-12 12:07:47', NULL, NULL, '2025-12-12 12:07:47', 'paid', 0, NULL, NULL, NULL, NULL),
(148, 58, 'carro', 'Toyota Hilux SRV 2019', 'toyota-hilux-srv-2019', '✅ Motor 2.8 turbo\r\n✅ Tração 4x4\r\n✅ Direção Hidráulica \r\n✅ Ar-condicionado Digital \r\n✅ Multimídia \r\n✅ Câmera de ré\r\n✅ Sensor traseiro \r\n✅Banco de Couro com regulagem elétrica\r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33) 99102-6213\r\nInstagram @opcaoveiculosguanhaes', 190000.00, 'Guanhães', 'MG', 'Toyota', 'Hilux SRV', 2018, 2019, 102000, 'Automático', 'Diesel', 'Branco', 4, NULL, 8, NULL, 'approved', 1, NULL, NULL, NULL, NULL, NULL, '2025-11-11 16:41:41', '2025-11-11 16:51:02', '2025-11-11 16:51:02', NULL, NULL, '2025-12-11 16:51:02', 'paid', 0, NULL, NULL, NULL, NULL),
(150, 58, 'carro', 'Fiat Toro 2018', 'fiat-toro-2018', '✅ Apenas 47.000km\r\n✅ Motor 1.8\r\n✅ Direção Elétrica\r\n✅ Ar-condicionado \r\n✅ Multimídia \r\n✅ Sensor traseiro \r\n✅ Roda de liga\r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33) 99102-6213\r\nInstagram @opcaoveiculosguanhaes', 85000.00, 'Guanhães', 'MG', 'Fiat', 'Toro', 2017, 2018, 42000, 'Automático', 'Flex', 'Preto', 4, NULL, 9, NULL, 'approved', 1, NULL, '2025-11-11 16:50:52', 3, NULL, NULL, '2025-11-11 16:48:59', '2025-11-11 16:50:53', '2025-11-11 16:50:53', NULL, NULL, '2025-12-11 16:50:53', 'paid', 0, NULL, NULL, NULL, NULL),
(151, 58, 'carro', 'Volkswagen Nivus Comfortiline 2021', 'volkswagen-nivus-comfortiline-2021', '✅ Motor 1.0 turbo\r\n✅ Direção Elétrica\r\n✅ Ar-condicionado\r\n✅ Bancos em couro\r\n✅ Multimídia Bluetooth\r\n✅ CarPlay e Android Auto\r\n✅ Câmera de ré \r\n✅ Vidros e trava elétrica\r\n✅ Airbags\r\n✅ Freio ABS\r\n✅ Faróis de led\r\n✅ Faróis automático \r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33)99102-6213\r\nInstagram @opcaoveiculosguanhaes', 100000.00, 'Guanhães', 'MG', 'Volkswagen', 'Nivus Comfortiline', 2020, 2021, 42000, 'Automático', 'Flex', 'Cinza', 4, NULL, 3, NULL, 'approved', 1, NULL, '2025-11-11 17:03:52', 3, NULL, NULL, '2025-11-11 17:00:47', '2025-11-11 17:03:54', '2025-11-11 17:03:54', NULL, NULL, '2025-12-11 17:03:54', 'paid', 0, NULL, NULL, NULL, NULL),
(153, 58, 'carro', 'Carro Fiat Argo Drive 1.0 completo', 'carro-fiat-argo-drive-1-0-completo', '✅Motor 1.0\r\n✅Direção Elétrica \r\n✅Ar-condicionado \r\n✅Multimídia\r\n✅ Câmera de ré \r\n✅Vidros e trava elétrica \r\n✅Airbags \r\n✅Freio ABS\r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33) 99102-6213\r\nInstagram @opcaoveiculosguanhaes', 66000.00, 'Guanhães', 'MG', 'Fiat', 'Argo Drive 1.0', 2022, 2023, 75000, 'Manual', 'Flex', 'Branco', 4, NULL, 3, NULL, 'approved', 1, NULL, '2025-11-11 17:58:01', 3, NULL, NULL, '2025-11-11 17:29:16', '2025-11-11 17:58:02', '2025-11-11 17:58:02', NULL, NULL, '2025-12-11 17:58:02', 'paid', 0, NULL, NULL, NULL, NULL),
(154, 58, 'carro', 'Volkswagen Virtus Highline 2024 2024', 'volkswagen-virtus-highline-2024-2024', '✅ Motor 1.0 turbo\r\n✅ Direção Elétrica\r\n✅ Ar-condicionado Digital\r\n✅ Bancos em couro\r\n✅ Multimídia Bluetooth\r\n✅ CarPlay e Android Auto\r\n✅ Câmera de ré \r\n✅ Vidros e trava elétrica\r\n✅ Airbags\r\n✅ Freio ABS\r\n✅ Faróis de led\r\n✅ Faróis automático \r\n✅ Câmera de Ré\r\n✅ Roda de liga \r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33)99102-6213\r\nInstagram @opcaoveiculosguanhaes', 117000.00, 'Guanhães', 'MG', 'Volkswagen', 'Virtus Highline 2024', 2023, 2024, 60000, 'Automático', 'Flex', 'Prata', 4, NULL, 3, NULL, 'approved', 1, NULL, '2025-11-11 17:58:10', 3, NULL, NULL, '2025-11-11 17:49:23', '2025-11-11 17:58:12', '2025-11-11 17:58:12', NULL, NULL, '2025-12-11 17:58:12', 'paid', 0, NULL, NULL, NULL, NULL),
(155, 58, 'carro', 'Volkswagen Virtus 2023', 'volkswagen-virtus-2023', '✅ Motor 1.0 turbo\r\n✅ Direção Elétrica\r\n✅ Ar-condicionado\r\n✅ Painel digital \r\n✅ Multimídia Bluetooth\r\n✅ CarPlay e Android Auto\r\n✅ Vidros e trava elétrica\r\n✅ Airbags\r\n✅ Freio ABS\r\n✅ Faróis de led\r\n✅ Roda de liga \r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33)99102-6213\r\nInstagram @opcaoveiculosguanhaes', 85000.00, 'Guanhães', 'MG', 'Volkswagen', 'Virtus Comfortiline', 2023, 2023, 88000, 'Manual', 'Flex', 'Azul', 4, NULL, 4, NULL, 'approved', 1, NULL, '2025-11-11 18:16:11', 3, NULL, NULL, '2025-11-11 18:15:41', '2025-11-12 16:29:51', '2025-11-12 16:29:51', NULL, NULL, '2025-12-12 16:29:51', 'paid', 0, NULL, NULL, NULL, NULL),
(156, 58, 'carro', 'Carro Fiat Mobi completo', 'carro-fiat-mobi-completo', '✅Motor 1.0\r\n✅Direção Hidráulica \r\n✅Ar-condicionado \r\n✅Vidros e trava elétrica \r\n✅Airbags \r\n✅Freio ABS', 57000.00, 'Guanhães', 'MG', 'Fiat', 'Mobi', 2023, 2023, 47000, 'Manual', 'Flex', 'Branco', 4, NULL, 0, NULL, 'approved', 1, NULL, '2025-11-11 18:34:42', 3, NULL, NULL, '2025-11-11 18:29:55', '2025-11-11 18:34:43', '2025-11-11 18:34:43', NULL, NULL, '2025-12-11 18:34:43', 'paid', 0, NULL, NULL, NULL, NULL),
(157, 59, 'carro', 'Volkswagen Nivus 2021 por R$ 108.900,00', 'volkswagen-nivus-2021-por-r-108-900-00', 'Volkswagen Nivus 2021 em ótimo estado, 96.000 km, combustível Flex, câmbio Automático. Revisões em dia e documentação ok.', 108900.00, 'Guanhães', 'MG', 'Volkswagen', 'Nivus', 2021, 2021, 96000, 'Automático', 'Flex', 'Cinza', 4, NULL, 6, NULL, 'approved', 1, NULL, '2025-11-12 11:34:38', 3, NULL, NULL, '2025-11-12 11:32:03', '2025-11-12 12:07:32', '2025-11-12 11:34:44', NULL, NULL, '2025-12-12 11:34:44', 'paid', 1, NULL, NULL, NULL, NULL),
(158, 3, 'carro', 'Honda City 2022', 'honda-city-2022', 'Honda City 2022 em ótimo estado, 2.000 km, combustível GNV, câmbio Automático. Revisões em dia e documentação ok.', 0.22, 'Guanhães', 'MG', 'Honda', 'City', 2021, 2022, 2000, 'Automático', 'GNV', 'Vermelho', 4, NULL, 5, NULL, 'rejected', 0, 'assets/uploads/images/158/verification_1762957705.jpg', NULL, NULL, NULL, 'teste', '2025-11-12 14:28:25', '2025-11-12 14:45:02', NULL, NULL, NULL, NULL, 'paid', 0, NULL, NULL, NULL, NULL),
(159, 3, 'moto', 'teste teste 2023', 'teste-teste-2023', 'teste teste 2023 em ótimo estado, 8.500 km, combustível Gasolina, câmbio Manual. Revisões em dia e documentação ok.', 0.01, 'Galiléia', 'MG', 'teste', 'teste', 2023, 2023, 8500, 'Manual', 'Gasolina', 'Laranja', NULL, NULL, 8, '{\"estilo\":\"Esportiva\",\"cilindradas\":\"1200cc\"}', 'rejected', 0, 'assets/uploads/images/159/verification_1762957745.jpg', NULL, NULL, NULL, 'teste', '2025-11-12 14:29:05', '2025-11-12 14:45:12', NULL, NULL, NULL, NULL, 'paid', 0, NULL, NULL, NULL, NULL),
(160, 55, 'carro', 'L200 3.2 HPE 2014 4x4', 'l200-3-2-hpe-2014-4x4', 'Mitsubishi L200 Triton HPE 3.2 4x4 Diesel\r\n	-	Ano/Modelo: 2014\r\n	-	Quilometragem: 207.000 km\r\n	-	Versão HPE top de linha | Pneus bons\r\n\r\nDestaques:\r\n• Motor 3.2 Turbo Diesel \r\n• Tração 4x4 com seletor eletrônico\r\n• Câmbio automático\r\n• Bancos em couro | Ar-condicionado digital\r\n• Central multimídia com conectividade\r\n• Airbags frontais | Freios ABS\r\n•  Estribos, capota marítima e rodas de liga leve\r\n•Melhor custo benefício para a categoria.\r\n\r\n - Valor: R$ 113.990,00\r\n\r\n - Avaliamos seu usado na troca, aceitamos carta de crédito, financiamos em até 60x e dividimos sua entrada em até 21x no cartão.\r\n\r\n#Mitsubishi #L200Triton #TritonHPE #4x4Diesel #PickupMitsubishi #L2002014 #TopDeLinha #PickupRevisada #Financiamos #AceitamosTroca', 113990.00, 'Guanhães', 'MG', 'Mitsubishi', 'L200 Triton', 2014, 2014, 207000, 'Automático', 'Diesel', 'Branco', 5, NULL, 9, NULL, 'approved', 1, NULL, '2025-11-12 14:42:22', 55, NULL, NULL, '2025-11-12 14:40:26', '2025-11-12 14:42:26', '2025-11-12 14:42:26', NULL, NULL, '2025-12-12 14:42:26', 'paid', 0, NULL, NULL, NULL, NULL),
(161, 58, 'carro', 'Fiat Argo Drive 2025', 'fiat-argo-drive-2025', '✅Apenas 3.000km\r\n✅Motor 1.0\r\n✅Direção Elétrica \r\n✅Ar-condicionado \r\n✅Multimídia\r\n✅Vidros e trava elétrica \r\n✅Airbags \r\n✅Freio ABS\r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33) 99102-6213\r\nInstagram @opcaoveiculosguanhaes', 84000.00, 'Guanhães', 'MG', 'Fiat', 'Argo Drive', 2025, 2025, 3000, 'Manual', 'Flex', 'Preto', 4, NULL, 5, NULL, 'approved', 1, NULL, '2025-11-12 16:30:05', 3, NULL, NULL, '2025-11-12 16:28:52', '2025-11-12 16:30:07', '2025-11-12 16:30:07', NULL, NULL, '2025-12-12 16:30:07', 'paid', 0, NULL, NULL, NULL, NULL),
(162, 58, 'carro', 'Chevrolet Tracker LTZ 2015', 'chevrolet-tracker-ltz-2015', 'Chevrolet Tracker LTZ 2015 Automática\r\n✅ Motor 1.8 Flex\r\n✅ Câmbio Automático\r\n✅ Direção Elétrica\r\n✅ Ar-condicionado\r\n✅ Bancos em Couro\r\n✅ Central Multimídia com Bluetooth\r\n✅ Câmera de Ré + Sensor de Estacionamento\r\n✅ Airbags Frontais e Laterais\r\n✅ Freios ABS\r\n✅ Vidros, travas e retrovisores elétricos\r\n✅ Rodas de Liga Leve\r\n✅ Piloto Automático\r\n✅ Faróis de Neblina\r\n\r\n📲 Pedro Henrique: (33) 99930-3851\r\n📲 Fernando: (33) 99102-6213\r\n📸 Instagram: @opcaoveiculosguanhaes', 69900.00, 'Guanhães', 'MG', 'Chevrolet', 'Tracker LTZ', 2014, 2015, 123000, 'Automático', 'Flex', 'Preto', 4, NULL, 1, NULL, 'approved', 1, NULL, '2025-11-12 16:42:53', 3, NULL, NULL, '2025-11-12 16:35:46', '2025-11-12 16:42:55', '2025-11-12 16:42:55', NULL, NULL, '2025-12-12 16:42:55', 'paid', 0, NULL, NULL, NULL, NULL),
(163, 58, 'carro', 'Volkswagen Polo Track 2025', 'volkswagen-polo-track-2025', '✅Motor 1.0\r\n✅Direção Elétrica \r\n✅Ar-condicionado \r\n✅Radio Bluetooth \r\n✅Vidros e trava elétrica \r\n✅Airbags \r\n✅Freio ABS\r\n✅Porta malas 300L\r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33)99102-6213\r\nInstagram @opcaoveiculosguanhaes', 79000.00, 'Guanhães', 'MG', 'Volkswagen', 'Polo Track', 2024, 2025, 49000, 'Manual', 'Flex', 'Branco', 4, NULL, 6, NULL, 'approved', 1, NULL, '2025-11-12 17:10:27', 3, NULL, NULL, '2025-11-12 16:54:44', '2025-11-12 17:10:28', '2025-11-12 17:10:28', NULL, NULL, '2025-12-12 17:10:28', 'paid', 0, NULL, NULL, NULL, NULL),
(164, 58, 'carro', 'Fiat Strada Volcano 2025', 'fiat-strada-volcano-2025', '✅ Motor 1.3\r\n✅ 109cv\r\n✅ Câmbio automático \r\n✅ Direção Elétrica\r\n✅ Ar-condicionado Digital\r\n✅ Bancos parcial em couro\r\n✅ Multimídia Bluetooth\r\n✅ CarPlay e Android Auto\r\n✅ Vidros e trava elétrica\r\n✅ Airbags\r\n✅ Freio ABS\r\n✅ Carregador de celular por indução\r\n\r\nMais informações:\r\nPedro Henrique (33)99930-3851\r\nFernando (33)99102-6213\r\nInstagram @opcaoveiculosguanhaes', 140000.00, 'Guanhães', 'MG', 'Fiat', 'Strada Volcano', 2025, 2025, 1, 'CVT', 'Flex', 'Preto', 4, NULL, 0, NULL, 'approved', 1, NULL, '2025-11-12 17:10:34', 3, NULL, NULL, '2025-11-12 17:05:11', '2025-11-12 17:10:35', '2025-11-12 17:10:35', NULL, NULL, '2025-12-12 17:10:35', 'paid', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `ad_media`
--

CREATE TABLE `ad_media` (
  `id` int UNSIGNED NOT NULL,
  `ad_id` int UNSIGNED NOT NULL,
  `type` enum('image','video') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint UNSIGNED NOT NULL DEFAULT '0',
  `filepath` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ad_media`
--

INSERT INTO `ad_media` (`id`, `ad_id`, `type`, `sort_order`, `filepath`, `created_at`) VALUES
(478, 142, 'image', 0, 'assets/uploads/images/142/2b0f5129cca30021eee55cfc.jpg', '2025-11-11 12:21:37'),
(479, 142, 'image', 1, 'assets/uploads/images/142/252a21c4a7d781001afd99ec.jpg', '2025-11-11 12:21:37'),
(480, 142, 'image', 2, 'assets/uploads/images/142/b03ac63cb1a194e7ac9586c1.jpg', '2025-11-11 12:21:37'),
(481, 142, 'image', 3, 'assets/uploads/images/142/5d5ea77196ded13ed7010e5f.jpg', '2025-11-11 12:21:37'),
(482, 142, 'image', 4, 'assets/uploads/images/142/cf9c23e382b484adec8d75c9.jpg', '2025-11-11 12:21:37'),
(483, 142, 'image', 5, 'assets/uploads/images/142/73afcd4c142faa35c8cd40e9.jpg', '2025-11-11 12:21:37'),
(484, 142, 'image', 6, 'assets/uploads/images/142/7d568f871a5881af59a6975d.jpg', '2025-11-11 12:21:37'),
(485, 142, 'image', 7, 'assets/uploads/images/142/e56ea049c839ee4efbc3aaa9.jpg', '2025-11-11 12:21:37'),
(486, 142, 'image', 8, 'assets/uploads/images/142/f0264c5c7e913c41e82d3765.jpg', '2025-11-11 12:21:37'),
(487, 142, 'image', 9, 'assets/uploads/images/142/4cb5a119a83099adede02df9.jpg', '2025-11-11 12:21:37'),
(488, 142, 'image', 10, 'assets/uploads/images/142/f040d013c1f4d7f7ae699ce2.jpg', '2025-11-11 12:21:37'),
(489, 142, 'image', 11, 'assets/uploads/images/142/cdd14364a9e4e7385e369983.jpg', '2025-11-11 12:21:37'),
(490, 143, 'image', 0, 'assets/uploads/images/143/777eac646fd189b439e94fb4.jpg', '2025-11-11 13:35:55'),
(491, 143, 'image', 1, 'assets/uploads/images/143/2eaf7c5ef762f1b5e5b6a912.jpg', '2025-11-11 13:35:55'),
(492, 143, 'image', 2, 'assets/uploads/images/143/fda7f0a100d10a5bd42de835.jpg', '2025-11-11 13:35:55'),
(493, 143, 'image', 3, 'assets/uploads/images/143/474a00085f95190e87d77eee.jpg', '2025-11-11 13:35:55'),
(494, 143, 'image', 4, 'assets/uploads/images/143/80b29ec854235f43d6af789d.jpg', '2025-11-11 13:35:55'),
(495, 143, 'image', 5, 'assets/uploads/images/143/27818c629d674cfcc89fe2a2.jpg', '2025-11-11 13:35:55'),
(496, 143, 'video', 0, 'assets/uploads/videos/143/7b1650f1906365f62b3718a2ca196462.mp4', '2025-11-11 13:35:55'),
(497, 144, 'image', 0, 'assets/uploads/images/144/e21844c0c33930cef988f04a.jpg', '2025-11-11 14:17:35'),
(498, 144, 'image', 1, 'assets/uploads/images/144/5615543ac96b79fad84d0f5a.jpg', '2025-11-11 14:17:35'),
(499, 144, 'image', 2, 'assets/uploads/images/144/9b0b759f2a8f3fab0162cf78.jpg', '2025-11-11 14:17:35'),
(500, 144, 'image', 3, 'assets/uploads/images/144/179e3bd2c3355ad6fee0599e.jpg', '2025-11-11 14:17:35'),
(501, 144, 'image', 4, 'assets/uploads/images/144/dd62cd7a9ad144d32e2986ee.jpg', '2025-11-11 14:17:35'),
(502, 144, 'image', 5, 'assets/uploads/images/144/f70c7bb9979b32ba16bb80a9.jpg', '2025-11-11 14:17:35'),
(503, 144, 'image', 6, 'assets/uploads/images/144/d2202161aec9f522f77b2845.jpg', '2025-11-11 14:17:35'),
(504, 144, 'video', 0, 'assets/uploads/videos/144/ccf6de7857953951ae6709e437effee1.mp4', '2025-11-11 14:17:35'),
(505, 145, 'image', 0, 'assets/uploads/images/145/91dd96dd621b56c8900f0942.jpg', '2025-11-11 14:22:09'),
(506, 145, 'image', 1, 'assets/uploads/images/145/fe586c52a2b1cf6be40aa25a.jpg', '2025-11-11 14:22:09'),
(507, 145, 'image', 2, 'assets/uploads/images/145/475a5cc5606e174a3b93a7ef.jpg', '2025-11-11 14:22:09'),
(508, 145, 'image', 3, 'assets/uploads/images/145/eb222fa6ebc0d9899fd71d56.jpg', '2025-11-11 14:22:09'),
(509, 145, 'image', 4, 'assets/uploads/images/145/5df96e3c5bbc0f10717e3c95.jpg', '2025-11-11 14:22:09'),
(510, 145, 'video', 0, 'assets/uploads/videos/145/8709f1580aeeb7fa97daabf195457e10.mp4', '2025-11-11 14:22:09'),
(511, 146, 'image', 0, 'assets/uploads/images/146/0414fa9e765a1304a0114201.jpg', '2025-11-11 14:42:03'),
(512, 146, 'image', 1, 'assets/uploads/images/146/c6c4262eb0a28bcaea7fbc34.jpg', '2025-11-11 14:42:03'),
(513, 146, 'image', 2, 'assets/uploads/images/146/238df49d06cc32879a2d46cc.jpg', '2025-11-11 14:42:03'),
(519, 148, 'image', 0, 'assets/uploads/images/148/4e30b0015162a281ea0ef539.jpg', '2025-11-11 16:41:41'),
(520, 148, 'image', 1, 'assets/uploads/images/148/42813751e220080935d91df0.jpg', '2025-11-11 16:41:41'),
(521, 148, 'image', 2, 'assets/uploads/images/148/72e769e8bda308eecfaf1116.jpg', '2025-11-11 16:41:41'),
(522, 148, 'image', 3, 'assets/uploads/images/148/e851c0371adfa7acc2cf0f29.jpg', '2025-11-11 16:41:41'),
(523, 148, 'image', 4, 'assets/uploads/images/148/feb6e3d92ee0749cb15d04f2.jpg', '2025-11-11 16:41:41'),
(524, 148, 'image', 5, 'assets/uploads/images/148/636346a0984e75a9fb9d2fec.jpg', '2025-11-11 16:41:41'),
(529, 150, 'image', 0, 'assets/uploads/images/150/0fd37e3bbcdb45c60dda1d17.jpg', '2025-11-11 16:48:59'),
(530, 150, 'image', 1, 'assets/uploads/images/150/3945081c8f500a7dd72ffa03.jpg', '2025-11-11 16:48:59'),
(531, 150, 'image', 2, 'assets/uploads/images/150/7d0e20a49ef4c921005d2bd2.jpg', '2025-11-11 16:48:59'),
(532, 150, 'image', 3, 'assets/uploads/images/150/fb67c212cc9d2f11c229b086.jpg', '2025-11-11 16:48:59'),
(533, 150, 'image', 4, 'assets/uploads/images/150/e2f39530c4e06073c5129f8f.jpg', '2025-11-11 16:48:59'),
(534, 150, 'image', 5, 'assets/uploads/images/150/17dcb33c1214d2a46b6a16e6.jpg', '2025-11-11 16:48:59'),
(535, 150, 'video', 0, 'assets/uploads/videos/150/6c454b3d7d321f32ee38e548c548b319.mp4', '2025-11-11 16:48:59'),
(536, 151, 'image', 0, 'assets/uploads/images/151/67da5929c26d8392d13ac4d3.jpg', '2025-11-11 17:00:47'),
(537, 151, 'image', 1, 'assets/uploads/images/151/c73860f5d96a590b1fa768ee.jpg', '2025-11-11 17:00:47'),
(538, 151, 'image', 2, 'assets/uploads/images/151/d9c3d8f8a10d80e0b2d740f9.jpg', '2025-11-11 17:00:47'),
(539, 151, 'image', 3, 'assets/uploads/images/151/ab6fae59ca591b8a2596f3a6.jpg', '2025-11-11 17:00:47'),
(540, 151, 'image', 4, 'assets/uploads/images/151/1b4c1048d24e677d59fca24f.jpg', '2025-11-11 17:00:47'),
(541, 151, 'image', 5, 'assets/uploads/images/151/4a32ae3daa85273b2f30a6ac.jpg', '2025-11-11 17:00:47'),
(542, 151, 'image', 6, 'assets/uploads/images/151/6154a086dd4913da209fa44e.jpg', '2025-11-11 17:00:47'),
(543, 151, 'image', 7, 'assets/uploads/images/151/5851b31e568237d3d97ad911.jpg', '2025-11-11 17:00:47'),
(550, 153, 'image', 0, 'assets/uploads/images/153/c39010916453c15bd5ebc7b8.jpg', '2025-11-11 17:29:16'),
(551, 153, 'image', 1, 'assets/uploads/images/153/8080f7c1c8a8c5c79119d70d.jpg', '2025-11-11 17:29:16'),
(552, 153, 'image', 2, 'assets/uploads/images/153/747e444dcdc3c7caf1ae3f4a.jpg', '2025-11-11 17:29:16'),
(553, 153, 'image', 3, 'assets/uploads/images/153/c3dcbcc41bb2e81417ce48dc.jpg', '2025-11-11 17:29:16'),
(554, 153, 'image', 4, 'assets/uploads/images/153/d3d6e15c1fb8f325ba09417c.jpg', '2025-11-11 17:29:16'),
(555, 153, 'image', 5, 'assets/uploads/images/153/dac2de1f6c79416564b53868.jpg', '2025-11-11 17:29:16'),
(556, 153, 'video', 0, 'assets/uploads/videos/153/e48c5271dfaae364bc8c1202c73bf3e3.mp4', '2025-11-11 17:29:16'),
(557, 154, 'image', 0, 'assets/uploads/images/154/b903f6691dce89406044bb0c.jpg', '2025-11-11 17:49:23'),
(558, 154, 'image', 1, 'assets/uploads/images/154/2e7444b9b1fddbbcbcd580e1.jpg', '2025-11-11 17:49:23'),
(559, 154, 'image', 2, 'assets/uploads/images/154/080b046f736ecffd66adf30c.jpg', '2025-11-11 17:49:23'),
(560, 154, 'image', 3, 'assets/uploads/images/154/518f55f6bb7220601f8dc725.jpg', '2025-11-11 17:49:23'),
(561, 154, 'image', 4, 'assets/uploads/images/154/571cd0c3ffc10b4287b1f586.jpg', '2025-11-11 17:49:23'),
(562, 154, 'image', 5, 'assets/uploads/images/154/d804a15e84857ab608efdf23.jpg', '2025-11-11 17:49:23'),
(563, 154, 'image', 6, 'assets/uploads/images/154/4bb65eebb0e940e90e126c63.jpg', '2025-11-11 17:49:23'),
(564, 154, 'image', 7, 'assets/uploads/images/154/137fd451e982bab981fe5613.jpg', '2025-11-11 17:49:23'),
(565, 154, 'image', 8, 'assets/uploads/images/154/2cbe090351e96f610b574471.jpg', '2025-11-11 17:49:23'),
(566, 154, 'video', 0, 'assets/uploads/videos/154/8c4ec8d53623845be562722eb02eecaf.mp4', '2025-11-11 17:49:23'),
(567, 155, 'image', 0, 'assets/uploads/images/155/9b61d82cd5bfe9ac87f38e1e.jpg', '2025-11-11 18:15:41'),
(568, 155, 'image', 1, 'assets/uploads/images/155/68c00eed2ba6a8544e239276.jpg', '2025-11-11 18:15:41'),
(569, 155, 'image', 2, 'assets/uploads/images/155/7e74ffae3fc47f8e5702a2d3.jpg', '2025-11-11 18:15:41'),
(570, 155, 'image', 3, 'assets/uploads/images/155/e941b2eb23334ca16ea66230.jpg', '2025-11-11 18:15:41'),
(571, 155, 'image', 4, 'assets/uploads/images/155/96158131ecbae76772c856cb.jpg', '2025-11-11 18:15:41'),
(572, 156, 'image', 0, 'assets/uploads/images/156/2ba3c6d9cd3d015a89ce6b8d.jpg', '2025-11-11 18:29:55'),
(573, 156, 'image', 1, 'assets/uploads/images/156/8c350959e0473359aa18ec7a.jpg', '2025-11-11 18:29:55'),
(574, 156, 'image', 2, 'assets/uploads/images/156/323640efc08d72ae4df3ad4a.jpg', '2025-11-11 18:29:55'),
(575, 156, 'image', 3, 'assets/uploads/images/156/97c4cc0fb1a3378922f5e4c9.jpg', '2025-11-11 18:29:55'),
(576, 157, 'image', 0, 'assets/uploads/images/157/7e82d83ad71ecf31be0a94b2.jpg', '2025-11-12 11:32:03'),
(577, 157, 'image', 1, 'assets/uploads/images/157/46455f89c2531fad6ec0f005.jpg', '2025-11-12 11:32:03'),
(578, 157, 'image', 2, 'assets/uploads/images/157/2e8234775880b69ca8d8a1a9.jpg', '2025-11-12 11:32:03'),
(579, 157, 'video', 0, 'assets/uploads/videos/157/ed9ee7522265e1b98df0a6dcc0675006.mp4', '2025-11-12 11:32:03'),
(580, 158, 'image', 0, 'assets/uploads/images/158/438a2482a8053f740d9a82fd.jpg', '2025-11-12 14:28:25'),
(581, 158, 'image', 1, 'assets/uploads/images/158/cf5e42eb62c3d1f1c1ff2758.jpg', '2025-11-12 14:28:25'),
(582, 158, 'image', 2, 'assets/uploads/images/158/abc79f64b9b793c1a779961f.jpg', '2025-11-12 14:28:25'),
(583, 158, 'image', 3, 'assets/uploads/images/158/6539a4cc8510672cde1e602a.jpg', '2025-11-12 14:28:25'),
(584, 158, 'image', 4, 'assets/uploads/images/158/5d6f336bcaa57a774cdd4adc.jpg', '2025-11-12 14:28:25'),
(585, 158, 'image', 5, 'assets/uploads/images/158/6d3dd8c340ad8b1383df6258.jpg', '2025-11-12 14:28:25'),
(586, 158, 'image', 6, 'assets/uploads/images/158/5a1bb741b8d15b891464a265.jpg', '2025-11-12 14:28:25'),
(587, 159, 'image', 0, 'assets/uploads/images/159/e36f3d0000e0e86acacf81e2.jpg', '2025-11-12 14:29:05'),
(588, 159, 'image', 1, 'assets/uploads/images/159/2c63d1cd7d118ed6c146df36.jpg', '2025-11-12 14:29:05'),
(589, 159, 'image', 2, 'assets/uploads/images/159/4effce7f73b36e48c110318c.jpg', '2025-11-12 14:29:05'),
(590, 159, 'image', 3, 'assets/uploads/images/159/27700cfd7328e5851fe5ed40.jpg', '2025-11-12 14:29:05'),
(591, 159, 'image', 4, 'assets/uploads/images/159/eee5abaada4aa59077832419.jpg', '2025-11-12 14:29:05'),
(592, 159, 'image', 5, 'assets/uploads/images/159/acdd615dc905c93090e355d7.jpg', '2025-11-12 14:29:05'),
(593, 160, 'image', 0, 'assets/uploads/images/160/7a2ef7ed59808dd11cf0ce2e.jpg', '2025-11-12 14:40:26'),
(594, 160, 'image', 1, 'assets/uploads/images/160/17a59f652ad38d20264fd26a.jpg', '2025-11-12 14:40:26'),
(595, 160, 'image', 2, 'assets/uploads/images/160/f0faf172c001557b64a74103.jpg', '2025-11-12 14:40:26'),
(596, 160, 'image', 3, 'assets/uploads/images/160/231be695af32a701f54b5990.jpg', '2025-11-12 14:40:26'),
(597, 160, 'image', 4, 'assets/uploads/images/160/d9dc41c4f4913fc8b77c413d.jpg', '2025-11-12 14:40:26'),
(598, 160, 'image', 5, 'assets/uploads/images/160/815f1fcf5ce596b46c7e5f40.jpg', '2025-11-12 14:40:26'),
(599, 160, 'image', 6, 'assets/uploads/images/160/b06128e7eca4acf0bc10f24a.jpg', '2025-11-12 14:40:26'),
(600, 160, 'image', 7, 'assets/uploads/images/160/227f2d42d6c0ac19ba003bd1.jpg', '2025-11-12 14:40:26'),
(601, 160, 'image', 8, 'assets/uploads/images/160/1161191801ff2e4ca73fe461.jpg', '2025-11-12 14:40:26'),
(602, 160, 'image', 9, 'assets/uploads/images/160/8256feede1356b6ad2f4b651.jpg', '2025-11-12 14:40:26'),
(603, 160, 'image', 10, 'assets/uploads/images/160/2a777468101f88ab90bdc908.jpg', '2025-11-12 14:40:26'),
(604, 160, 'image', 11, 'assets/uploads/images/160/d5e604bb39e841ce3d6b9cc8.jpg', '2025-11-12 14:40:26'),
(605, 160, 'video', 0, 'assets/uploads/videos/160/2a56704c14f4aaaf748db92a3d7e4d78.mp4', '2025-11-12 14:40:26'),
(606, 161, 'image', 0, 'assets/uploads/images/161/21bb6dd9b7ce007c6b13be89.jpg', '2025-11-12 16:28:52'),
(607, 161, 'image', 1, 'assets/uploads/images/161/de1c8a95207739cec3a338a7.jpg', '2025-11-12 16:28:52'),
(608, 161, 'image', 2, 'assets/uploads/images/161/6fdaebbd46015428612f49b4.jpg', '2025-11-12 16:28:52'),
(609, 161, 'image', 3, 'assets/uploads/images/161/40170faaa390e188e01c7a97.jpg', '2025-11-12 16:28:52'),
(610, 161, 'image', 4, 'assets/uploads/images/161/18122f874af134166173f863.jpg', '2025-11-12 16:28:52'),
(611, 161, 'image', 5, 'assets/uploads/images/161/51d125aeba3274795e7b790a.jpg', '2025-11-12 16:28:52'),
(612, 161, 'video', 0, 'assets/uploads/videos/161/034365d3e06263a97c572c33393fc246.mp4', '2025-11-12 16:28:52'),
(613, 162, 'image', 0, 'assets/uploads/images/162/37c1af09b9530343baf1a87c.jpg', '2025-11-12 16:35:46'),
(614, 162, 'image', 1, 'assets/uploads/images/162/2dea06fba606df2fbe563f4e.jpg', '2025-11-12 16:35:46'),
(615, 162, 'image', 2, 'assets/uploads/images/162/10070d5be61a16a99d92da63.jpg', '2025-11-12 16:35:46'),
(616, 162, 'image', 3, 'assets/uploads/images/162/b3d381e2c1ec48df4b461325.jpg', '2025-11-12 16:35:46'),
(617, 162, 'image', 4, 'assets/uploads/images/162/1e5b1a7aa809cff2f69476ca.jpg', '2025-11-12 16:35:46'),
(618, 162, 'image', 5, 'assets/uploads/images/162/aac929cfd691ffc0adb656ee.jpg', '2025-11-12 16:35:46'),
(619, 162, 'image', 6, 'assets/uploads/images/162/43b9141a94473da048d635af.jpg', '2025-11-12 16:35:46'),
(620, 162, 'image', 7, 'assets/uploads/images/162/025502019e09225638f65574.jpg', '2025-11-12 16:35:46'),
(621, 163, 'image', 0, 'assets/uploads/images/163/36cf53e240e1e30f7cd35567.jpg', '2025-11-12 16:54:44'),
(622, 163, 'image', 1, 'assets/uploads/images/163/f48744d1ed83237d35d6ad94.jpg', '2025-11-12 16:54:44'),
(623, 163, 'image', 2, 'assets/uploads/images/163/e520097c0fe9c30623da4e2e.jpg', '2025-11-12 16:54:44'),
(624, 163, 'image', 3, 'assets/uploads/images/163/07eb126c6d342c58fe5afcb8.jpg', '2025-11-12 16:54:44'),
(625, 163, 'image', 4, 'assets/uploads/images/163/53095ec5d3f071c7f5d39ee5.jpg', '2025-11-12 16:54:44'),
(626, 163, 'video', 0, 'assets/uploads/videos/163/e414e7833cc299e366d10156ea09ff16.mp4', '2025-11-12 16:54:44'),
(627, 164, 'image', 0, 'assets/uploads/images/164/a53ec55b3f9be4d761078719.jpg', '2025-11-12 17:05:11'),
(628, 164, 'image', 1, 'assets/uploads/images/164/4729a84bf3803365db0cdb51.jpg', '2025-11-12 17:05:11'),
(629, 164, 'image', 2, 'assets/uploads/images/164/c4d67d3e39dd429274a1260e.jpg', '2025-11-12 17:05:11'),
(630, 164, 'image', 3, 'assets/uploads/images/164/3944a3e6230cf82d03689482.jpg', '2025-11-12 17:05:11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ad_reports`
--

CREATE TABLE `ad_reports` (
  `id` int UNSIGNED NOT NULL,
  `ad_id` int UNSIGNED NOT NULL,
  `reporter_user_id` int UNSIGNED NOT NULL,
  `status` enum('open','under_review','resolved','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `reason` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `evidence_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `handled_by` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int UNSIGNED NOT NULL,
  `actor_user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `actor_user_id`, `action`, `entity`, `entity_id`, `meta`, `created_at`) VALUES
(1702, 3, 'user_login', 'users', 3, NULL, '2025-11-05 15:38:52'),
(1703, 3, 'prices_and_free_settings_updated', 'settings', 0, '{\"service_price\":10,\"clivre_medal_price\":15,\"ads_free_for_all\":0,\"services_free_for_all\":1,\"new_account_coins\":1}', '2025-11-05 15:39:10'),
(1704, 3, 'premium_prices_updated', 'settings', 0, '{\"premium_base_mensal\":29.89999999999999857891452847979962825775146484375,\"desconto_trimestral_pct\":11,\"desconto_semestral_pct\":17}', '2025-11-05 15:39:16'),
(1705, 53, 'welcome_bonus_coins', 'users', 53, '{\"amount\":1,\"reason\":\"Bônus inicial para novo usuário\"}', '2025-11-05 16:00:14'),
(1706, 53, 'user_registered', 'users', 53, '{\"email\":\"edmar.rocha.eng@gmail.com\",\"document_type\":\"cpf\"}', '2025-11-05 16:00:14'),
(1707, 53, 'user_login', 'users', 53, NULL, '2025-11-05 16:00:14'),
(1708, 53, 'user_login', 'users', 53, NULL, '2025-11-05 16:04:33'),
(1709, 53, 'service_free_published', 'user_service_listings', 91, '{\"payment_reference\":\"SRV-E90CF4AD\",\"expires_at\":\"2025-12-05 16:08:04\",\"mode\":\"free\"}', '2025-11-05 16:08:04'),
(1710, 53, 'user_logout', 'users', 53, NULL, '2025-11-05 16:10:31'),
(1711, NULL, 'welcome_bonus_coins', 'users', 54, '{\"amount\":1,\"reason\":\"Bônus inicial para novo usuário\"}', '2025-11-05 16:18:10'),
(1712, NULL, 'user_registered', 'users', 54, '{\"email\":\"jefilds3@gmail.com\",\"document_type\":\"cnpj\"}', '2025-11-05 16:18:18'),
(1713, NULL, 'user_login', 'users', 54, NULL, '2025-11-05 16:18:18'),
(1714, NULL, 'user_login', 'users', 54, NULL, '2025-11-05 16:18:53'),
(1715, NULL, 'user_logout', 'users', 54, NULL, '2025-11-05 16:18:57'),
(1716, NULL, 'password_reset_requested', 'users', 54, NULL, '2025-11-05 16:19:06'),
(1717, NULL, 'password_reset_completed', 'users', 54, NULL, '2025-11-05 16:19:27'),
(1718, NULL, 'user_login', 'users', 54, NULL, '2025-11-05 16:19:36'),
(1719, 3, 'user_login', 'users', 3, NULL, '2025-11-05 17:32:17'),
(1720, 3, 'prices_and_free_settings_updated', 'settings', 0, '{\"service_price\":10,\"clivre_medal_price\":15,\"ads_free_for_all\":0,\"services_free_for_all\":1,\"new_account_coins\":3}', '2025-11-05 17:32:24'),
(1721, 3, 'admin_coin_adjustment', 'users', 3, '{\"amount\":4,\"type\":\"admin_add\",\"description\":\"Teste\"}', '2025-11-05 17:47:26'),
(1722, 3, 'ad_created', 'ads', 138, '{\"title\":\"Honda City 2022\"}', '2025-11-05 18:56:28'),
(1723, 3, 'coin_deducted_for_ad', 'ads', 138, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":4,\"balance_after\":3}', '2025-11-05 18:56:28'),
(1724, 3, 'ad_verification_activated', 'ads', 138, '{\"title\":\"Honda City 2022\"}', '2025-11-05 18:56:38'),
(1725, 3, 'ad_approved', 'ads', 138, '{\"title\":\"Honda City 2022\"}', '2025-11-05 18:56:40'),
(1726, 3, 'user_profile_updated', 'users', 3, NULL, '2025-11-05 18:58:32'),
(1727, 3, 'user_profile_updated', 'users', 3, NULL, '2025-11-05 18:59:02'),
(1728, 3, 'ad_updated', 'ads', 138, '{\"title\":\"(DEMO) Honda City 2022\"}', '2025-11-05 19:01:10'),
(1729, 3, 'ad_approved', 'ads', 138, '{\"title\":\"(DEMO) Honda City 2022\"}', '2025-11-05 19:01:21'),
(1730, 3, 'user_login', 'users', 3, NULL, '2025-11-05 19:21:30'),
(1731, 3, 'ad_created', 'ads', 139, '{\"title\":\"(DEMO) Honda Africa Twin 2023\"}', '2025-11-05 19:27:06'),
(1732, 3, 'coin_deducted_for_ad', 'ads', 139, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":3,\"balance_after\":2}', '2025-11-05 19:27:06'),
(1733, 3, 'ad_verification_activated', 'ads', 139, '{\"title\":\"(DEMO) Honda Africa Twin 2023\"}', '2025-11-05 19:27:14'),
(1734, 3, 'ad_approved', 'ads', 139, '{\"title\":\"(DEMO) Honda Africa Twin 2023\"}', '2025-11-05 19:27:15'),
(1735, 3, 'user_login', 'users', 3, NULL, '2025-11-05 19:46:11'),
(1736, 3, 'user_login', 'users', 3, NULL, '2025-11-05 20:40:04'),
(1737, 3, 'user_login', 'users', 3, NULL, '2025-11-05 22:44:49'),
(1738, 3, 'user_login', 'users', 3, NULL, '2025-11-05 23:13:12'),
(1739, 3, 'user_login', 'users', 3, NULL, '2025-11-06 00:15:17'),
(1740, 3, 'user_login', 'users', 3, NULL, '2025-11-06 13:02:23'),
(1741, 3, 'user_login', 'users', 3, NULL, '2025-11-06 13:41:57'),
(1742, 3, 'user_login', 'users', 3, NULL, '2025-11-06 19:30:35'),
(1743, 3, 'ad_created', 'ads', 140, '{\"title\":\"Honda Civic 2025\"}', '2025-11-06 19:34:06'),
(1744, 3, 'coin_deducted_for_ad', 'ads', 140, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":2,\"balance_after\":1}', '2025-11-06 19:34:06'),
(1745, 3, 'user_login', 'users', 3, NULL, '2025-11-06 20:07:42'),
(1746, 3, 'user_login', 'users', 3, NULL, '2025-11-07 00:23:16'),
(1747, 3, 'user_login', 'users', 3, NULL, '2025-11-07 01:27:02'),
(1748, 3, 'user_login', 'users', 3, NULL, '2025-11-07 01:27:04'),
(1749, 3, 'user_login', 'users', 3, NULL, '2025-11-07 01:27:05'),
(1750, 3, 'user_login', 'users', 3, NULL, '2025-11-07 01:27:15'),
(1751, 3, 'user_login', 'users', 3, NULL, '2025-11-07 01:27:43'),
(1752, 3, 'user_login', 'users', 3, NULL, '2025-11-07 02:17:13'),
(1753, 3, 'user_login', 'users', 3, NULL, '2025-11-07 02:25:11'),
(1754, 3, 'user_login', 'users', 3, NULL, '2025-11-07 02:34:33'),
(1755, 3, 'user_login', 'users', 3, NULL, '2025-11-07 02:39:22'),
(1756, 3, 'user_login', 'users', 3, NULL, '2025-11-07 02:40:47'),
(1757, 3, 'user_login', 'users', 3, NULL, '2025-11-07 03:09:21'),
(1758, 3, 'user_login', 'users', 3, NULL, '2025-11-07 03:11:20'),
(1759, 3, 'user_login', 'users', 3, NULL, '2025-11-07 03:12:06'),
(1760, 3, 'user_login', 'users', 3, NULL, '2025-11-07 03:18:44'),
(1761, 3, 'user_login', 'users', 3, NULL, '2025-11-07 03:28:07'),
(1762, 3, 'user_login', 'users', 3, NULL, '2025-11-07 03:35:26'),
(1763, 3, 'user_login', 'users', 3, NULL, '2025-11-07 13:37:19'),
(1764, 3, 'user_login', 'users', 3, NULL, '2025-11-07 13:46:35'),
(1765, 3, 'user_login', 'users', 3, NULL, '2025-11-07 13:52:19'),
(1766, 3, 'user_login', 'users', 3, NULL, '2025-11-07 14:07:33'),
(1767, 3, 'user_login', 'users', 3, NULL, '2025-11-07 14:36:46'),
(1768, 3, 'user_login', 'users', 3, NULL, '2025-11-07 14:37:52'),
(1769, 3, 'user_login', 'users', 3, NULL, '2025-11-07 14:44:33'),
(1770, 3, 'user_login', 'users', 3, NULL, '2025-11-07 16:20:45'),
(1771, 3, 'user_login', 'users', 3, NULL, '2025-11-07 16:48:44'),
(1772, 3, 'user_login', 'users', 3, NULL, '2025-11-07 17:24:27'),
(1773, 3, 'user_login', 'users', 3, NULL, '2025-11-07 18:18:43'),
(1774, 3, 'user_login', 'users', 3, NULL, '2025-11-07 18:28:27'),
(1775, 3, 'user_login', 'users', 3, NULL, '2025-11-07 18:30:56'),
(1776, 3, 'user_login', 'users', 3, NULL, '2025-11-07 18:31:33'),
(1777, 3, 'user_login', 'users', 3, NULL, '2025-11-07 18:49:57'),
(1778, 3, 'user_login', 'users', 3, NULL, '2025-11-07 18:56:35'),
(1779, 3, 'user_login', 'users', 3, NULL, '2025-11-07 19:00:43'),
(1780, 3, 'user_login', 'users', 3, NULL, '2025-11-07 19:42:14'),
(1781, 3, 'user_login', 'users', 3, NULL, '2025-11-07 19:50:13'),
(1782, 3, 'user_login', 'users', 3, NULL, '2025-11-07 19:55:16'),
(1783, 3, 'user_login', 'users', 3, NULL, '2025-11-07 20:08:58'),
(1784, 3, 'user_login', 'users', 3, NULL, '2025-11-07 20:36:02'),
(1785, 3, 'user_login', 'users', 3, NULL, '2025-11-07 22:19:36'),
(1786, 3, 'user_login', 'users', 3, NULL, '2025-11-07 23:45:29'),
(1787, 3, 'user_login', 'users', 3, NULL, '2025-11-08 00:07:40'),
(1788, 3, 'ad_verification_activated', 'ads', 140, '{\"title\":\"Honda Civic 2025\"}', '2025-11-08 00:22:50'),
(1789, 3, 'ad_rejected', 'ads', 140, '{\"reason\":\"corrija tal coisa\"}', '2025-11-08 00:23:06'),
(1790, 3, 'ad_updated', 'ads', 140, '{\"title\":\"Honda Civic 2025\"}', '2025-11-08 00:23:17'),
(1791, 3, 'ad_approved', 'ads', 140, '{\"title\":\"Honda Civic 2025\"}', '2025-11-08 00:23:29'),
(1792, 3, 'admin_coin_adjustment', 'users', 3, '{\"amount\":1000,\"type\":\"admin_add\",\"description\":\"meus\"}', '2025-11-08 00:27:05'),
(1793, 3, 'user_logout', 'users', 3, NULL, '2025-11-08 00:28:44'),
(1794, 3, 'user_login', 'users', 3, NULL, '2025-11-08 00:29:36'),
(1795, 3, 'user_profile_updated', 'users', 3, NULL, '2025-11-08 00:31:15'),
(1796, 3, 'user_logout', 'users', 3, NULL, '2025-11-08 00:32:24'),
(1797, 55, 'welcome_bonus_coins', 'users', 55, '{\"amount\":3,\"reason\":\"Bônus inicial para novo usuário\"}', '2025-11-08 00:37:58'),
(1798, 55, 'user_registered', 'users', 55, '{\"email\":\"joubertrodrigues97@gmail.com\",\"document_type\":\"cnpj\"}', '2025-11-08 00:37:59'),
(1799, 55, 'user_login', 'users', 55, NULL, '2025-11-08 00:37:59'),
(1800, 55, 'user_login', 'users', 55, NULL, '2025-11-08 00:40:16'),
(1801, 55, 'admin_coin_adjustment', 'users', 55, '{\"amount\":10000,\"type\":\"admin_add\",\"description\":\"teste\"}', '2025-11-08 00:42:06'),
(1802, 55, 'user_profile_updated', 'users', 55, NULL, '2025-11-08 00:45:40'),
(1803, 3, 'user_login', 'users', 3, NULL, '2025-11-08 01:35:45'),
(1804, 3, 'user_login', 'users', 3, NULL, '2025-11-08 13:31:59'),
(1805, 3, 'ad_deleted_admin', 'ads', 140, '{\"title\":\"Honda Civic 2025\",\"media_deleted\":4}', '2025-11-08 13:32:14'),
(1806, 3, 'user_login', 'users', 3, NULL, '2025-11-08 18:13:28'),
(1807, 3, 'user_login', 'users', 3, NULL, '2025-11-08 18:20:05'),
(1808, 3, 'user_profile_updated', 'users', 3, NULL, '2025-11-08 18:36:54'),
(1809, 3, 'user_login', 'users', 3, NULL, '2025-11-08 19:01:03'),
(1810, 3, 'user_login', 'users', 3, NULL, '2025-11-08 19:08:33'),
(1811, 3, 'user_login', 'users', 3, NULL, '2025-11-08 19:18:16'),
(1812, 3, 'user_login', 'users', 3, NULL, '2025-11-08 19:43:36'),
(1813, 3, 'user_login', 'users', 3, NULL, '2025-11-08 20:23:51'),
(1814, 3, 'user_login', 'users', 3, NULL, '2025-11-08 22:00:43'),
(1815, 3, 'user_login', 'users', 3, NULL, '2025-11-09 00:32:03'),
(1816, 3, 'user_login', 'users', 3, NULL, '2025-11-09 02:26:00'),
(1817, 3, 'user_login', 'users', 3, NULL, '2025-11-09 17:33:56'),
(1818, 3, 'user_login', 'users', 3, NULL, '2025-11-09 22:09:47'),
(1819, 3, 'user_logout', 'users', 3, NULL, '2025-11-09 22:42:22'),
(1820, 3, 'user_login', 'users', 3, NULL, '2025-11-09 22:43:46'),
(1821, 3, 'user_login', 'users', 3, NULL, '2025-11-10 02:37:19'),
(1822, 3, 'user_login', 'users', 3, NULL, '2025-11-10 12:02:27'),
(1823, 3, 'user_login', 'users', 3, NULL, '2025-11-10 12:26:32'),
(1824, 3, 'user_profile_updated', 'users', 3, NULL, '2025-11-10 12:26:48'),
(1825, 3, 'user_login', 'users', 3, NULL, '2025-11-10 14:00:06'),
(1826, 3, 'user_login', 'users', 3, NULL, '2025-11-10 16:05:19'),
(1827, 3, 'user_login', 'users', 3, NULL, '2025-11-10 16:41:30'),
(1828, 3, 'user_login', 'users', 3, NULL, '2025-11-10 16:52:14'),
(1829, 3, 'user_login', 'users', 3, NULL, '2025-11-10 17:22:38'),
(1830, 3, 'user_login', 'users', 3, NULL, '2025-11-10 18:54:46'),
(1831, 56, 'welcome_bonus_coins', 'users', 56, '{\"amount\":3,\"reason\":\"Bônus inicial para novo usuário\"}', '2025-11-10 19:02:07'),
(1832, 56, 'user_registered', 'users', 56, '{\"email\":\"hf74550@gmail.com\",\"document_type\":\"cpf\"}', '2025-11-10 19:02:09'),
(1833, 56, 'user_login', 'users', 56, NULL, '2025-11-10 19:02:09'),
(1834, 3, 'user_login', 'users', 3, NULL, '2025-11-10 19:17:47'),
(1835, 3, 'user_login', 'users', 3, NULL, '2025-11-10 19:43:29'),
(1836, 55, 'user_login', 'users', 55, NULL, '2025-11-10 20:12:15'),
(1837, 57, 'welcome_bonus_coins', 'users', 57, '{\"amount\":3,\"reason\":\"Bônus inicial para novo usuário\"}', '2025-11-10 20:13:32'),
(1838, 57, 'user_registered', 'users', 57, '{\"email\":\"lucimarasilva17955@gmail.com\",\"document_type\":\"cpf\"}', '2025-11-10 20:13:34'),
(1839, 57, 'user_login', 'users', 57, NULL, '2025-11-10 20:13:34'),
(1840, 57, 'password_reset_requested', 'users', 57, NULL, '2025-11-10 20:15:19'),
(1841, 57, 'password_reset_completed', 'users', 57, NULL, '2025-11-10 20:16:13'),
(1842, 3, 'settings_updated', 'settings', NULL, '{\"max_images\":13,\"allow_video_premium\":\"1\"}', '2025-11-10 20:26:02'),
(1843, 3, 'settings_updated', 'settings', NULL, '{\"max_images\":13,\"allow_video_premium\":\"1\"}', '2025-11-10 20:26:09'),
(1844, 55, 'user_login_remember', 'users', 55, NULL, '2025-11-10 20:26:28'),
(1845, 55, 'user_login', 'users', 55, NULL, '2025-11-10 20:26:30'),
(1846, 3, 'settings_updated', 'settings', NULL, '{\"max_images\":13,\"allow_video_premium\":\"1\"}', '2025-11-10 20:31:38'),
(1847, 3, 'settings_updated', 'settings', NULL, '{\"max_images\":13,\"allow_video_premium\":\"1\"}', '2025-11-10 20:32:10'),
(1848, 3, 'user_login', 'users', 3, NULL, '2025-11-10 20:37:15'),
(1849, 3, 'settings_updated', 'settings', NULL, '{\"max_images\":12,\"allow_video_premium\":\"1\"}', '2025-11-10 20:45:21'),
(1850, 3, 'settings_updated', 'settings', NULL, '{\"max_images\":13,\"allow_video_premium\":\"1\"}', '2025-11-10 20:45:30'),
(1851, 3, 'user_login', 'users', 3, NULL, '2025-11-10 20:52:15'),
(1852, 3, 'ad_created', 'ads', 141, '{\"title\":\"Tesla teste 2021\"}', '2025-11-10 20:55:33'),
(1853, 3, 'coin_deducted_for_ad', 'ads', 141, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":1001,\"balance_after\":1000}', '2025-11-10 20:55:33'),
(1854, 3, 'ad_rejected', 'ads', 141, '{\"reason\":\"teste fotos\"}', '2025-11-10 20:55:59'),
(1855, 3, 'ad_deleted', 'ads', 141, NULL, '2025-11-10 20:56:04'),
(1856, 3, 'user_login', 'users', 3, NULL, '2025-11-10 21:16:08'),
(1857, 3, 'user_login', 'users', 3, NULL, '2025-11-10 21:20:41'),
(1858, 3, 'user_login', 'users', 3, NULL, '2025-11-10 21:38:49'),
(1859, 3, 'user_login', 'users', 3, NULL, '2025-11-10 22:41:16'),
(1860, 3, 'user_login', 'users', 3, NULL, '2025-11-10 23:30:54'),
(1861, 3, 'user_login', 'users', 3, NULL, '2025-11-11 00:20:11'),
(1862, 3, 'user_login', 'users', 3, NULL, '2025-11-11 01:11:07'),
(1863, 3, 'user_login', 'users', 3, NULL, '2025-11-11 10:51:32'),
(1864, 3, 'user_login', 'users', 3, NULL, '2025-11-11 11:44:22'),
(1865, 55, 'user_login', 'users', 55, NULL, '2025-11-11 11:56:29'),
(1866, 55, 'ad_created', 'ads', 142, '{\"title\":\"Gla200 1.6T Enduro Teto panoramico\"}', '2025-11-11 12:21:39'),
(1867, 55, 'coin_deducted_for_ad', 'ads', 142, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":10003,\"balance_after\":10002}', '2025-11-11 12:21:39'),
(1868, 55, 'ad_approved', 'ads', 142, '{\"title\":\"Gla200 1.6T Enduro Teto panoramico\"}', '2025-11-11 12:22:13'),
(1869, 55, 'ad_deleted_admin', 'ads', 139, '{\"title\":\"(DEMO) Honda Africa Twin 2023\",\"media_deleted\":13}', '2025-11-11 12:22:43'),
(1870, 55, 'ad_deleted_admin', 'ads', 138, '{\"title\":\"(DEMO) Honda City 2022\",\"media_deleted\":13}', '2025-11-11 12:22:48'),
(1871, 58, 'welcome_bonus_coins', 'users', 58, '{\"amount\":3,\"reason\":\"Bônus inicial para novo usuário\"}', '2025-11-11 13:10:06'),
(1872, 58, 'user_registered', 'users', 58, '{\"email\":\"opcaoveiculos1@hotmail.com\",\"document_type\":\"cnpj\"}', '2025-11-11 13:10:08'),
(1873, 58, 'user_login', 'users', 58, NULL, '2025-11-11 13:10:08'),
(1874, 3, 'user_toggle_premium', 'users', 58, '{\"new_value\":1}', '2025-11-11 13:10:44'),
(1875, 58, 'user_login', 'users', 58, NULL, '2025-11-11 13:12:23'),
(1876, 3, 'admin_coin_adjustment', 'users', 58, '{\"amount\":100,\"type\":\"admin_add\",\"description\":\"Bônus de cadastro\"}', '2025-11-11 13:13:27'),
(1877, 58, 'user_login', 'users', 58, NULL, '2025-11-11 13:16:10'),
(1878, 58, 'user_login', 'users', 58, NULL, '2025-11-11 13:29:03'),
(1879, 58, 'ad_created', 'ads', 143, '{\"title\":\"Carro Volkswagen Saveiro Trendline completo\"}', '2025-11-11 13:35:55'),
(1880, 58, 'coin_deducted_for_ad', 'ads', 143, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":103,\"balance_after\":102}', '2025-11-11 13:35:55'),
(1881, 58, 'user_profile_updated', 'users', 58, NULL, '2025-11-11 13:38:51'),
(1882, 3, 'ad_verification_activated', 'ads', 143, '{\"title\":\"Carro Volkswagen Saveiro Trendline completo\"}', '2025-11-11 13:39:07'),
(1883, 3, 'ad_approved', 'ads', 143, '{\"title\":\"Carro Volkswagen Saveiro Trendline completo\"}', '2025-11-11 13:39:09'),
(1884, 58, 'user_profile_updated', 'users', 58, NULL, '2025-11-11 13:41:01'),
(1885, 3, 'user_login', 'users', 3, NULL, '2025-11-11 13:48:00'),
(1886, 58, 'service_free_published', 'user_service_listings', 92, '{\"payment_reference\":\"SRV-AB775E98\",\"expires_at\":\"2025-12-11 13:58:54\",\"mode\":\"free\"}', '2025-11-11 13:58:54'),
(1887, 3, 'user_login', 'users', 3, NULL, '2025-11-11 14:13:15'),
(1888, 58, 'ad_created', 'ads', 144, '{\"title\":\"Volkswagen Amarok 2020\"}', '2025-11-11 14:17:35'),
(1889, 58, 'coin_deducted_for_ad', 'ads', 144, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":102,\"balance_after\":101}', '2025-11-11 14:17:35'),
(1890, 59, 'welcome_bonus_coins', 'users', 59, '{\"amount\":3,\"reason\":\"Bônus inicial para novo usuário\"}', '2025-11-11 14:18:21'),
(1891, 59, 'user_registered', 'users', 59, '{\"email\":\"byel17lg@gmai.com\",\"document_type\":\"cnpj\"}', '2025-11-11 14:18:23'),
(1892, 59, 'user_login', 'users', 59, NULL, '2025-11-11 14:18:23'),
(1893, 3, 'ad_approved', 'ads', 144, '{\"title\":\"Volkswagen Amarok 2020\"}', '2025-11-11 14:19:16'),
(1894, 58, 'ad_created', 'ads', 145, '{\"title\":\"Ford F250 XLT 2000\"}', '2025-11-11 14:22:09'),
(1895, 58, 'coin_deducted_for_ad', 'ads', 145, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":101,\"balance_after\":100}', '2025-11-11 14:22:09'),
(1896, 59, 'user_login', 'users', 59, NULL, '2025-11-11 14:24:03'),
(1897, 3, 'user_login', 'users', 3, NULL, '2025-11-11 14:24:32'),
(1898, 3, 'admin_coin_adjustment', 'users', 59, '{\"amount\":100,\"type\":\"admin_add\",\"description\":\"bonus\"}', '2025-11-11 14:24:55'),
(1899, 3, 'user_toggle_premium', 'users', 59, '{\"new_value\":1}', '2025-11-11 14:25:45'),
(1900, 3, 'ad_approved', 'ads', 145, '{\"title\":\"Ford F250 XLT 2000\"}', '2025-11-11 14:27:19'),
(1901, 59, 'ad_created', 'ads', 146, '{\"title\":\"Carro Jeep Compass completo\"}', '2025-11-11 14:42:03'),
(1902, 59, 'coin_deducted_for_ad', 'ads', 146, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":103,\"balance_after\":102}', '2025-11-11 14:42:03'),
(1903, 3, 'ad_approved', 'ads', 146, '{\"title\":\"Carro Jeep Compass completo\"}', '2025-11-11 14:42:29'),
(1904, 3, 'user_login', 'users', 3, NULL, '2025-11-11 15:12:31'),
(1905, 58, 'user_login_remember', 'users', 58, NULL, '2025-11-11 16:16:26'),
(1906, 3, 'user_login', 'users', 3, NULL, '2025-11-11 16:29:24'),
(1907, 3, 'user_login', 'users', 3, NULL, '2025-11-11 16:37:48'),
(1908, 3, 'ad_created', 'ads', 147, '{\"title\":\"Honda Civic 2021\"}', '2025-11-11 16:39:34'),
(1909, 3, 'coin_deducted_for_ad', 'ads', 147, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":1000,\"balance_after\":999}', '2025-11-11 16:39:34'),
(1910, 58, 'ad_created', 'ads', 148, '{\"title\":\"Toyota Hilux SRV 2019\"}', '2025-11-11 16:41:41'),
(1911, 58, 'coin_deducted_for_ad', 'ads', 148, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":100,\"balance_after\":99}', '2025-11-11 16:41:41'),
(1912, 3, 'settings_updated', 'settings', NULL, '{\"max_images\":13,\"allow_video_premium\":\"1\"}', '2025-11-11 16:46:08'),
(1913, 3, 'ad_created', 'ads', 149, '{\"title\":\"teste 2 vídeo teste 2021\"}', '2025-11-11 16:48:18'),
(1914, 3, 'coin_deducted_for_ad', 'ads', 149, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":999,\"balance_after\":998}', '2025-11-11 16:48:18'),
(1915, 3, 'settings_updated', 'settings', NULL, '{\"max_images\":13,\"allow_video_premium\":\"1\"}', '2025-11-11 16:48:48'),
(1916, 58, 'ad_created', 'ads', 150, '{\"title\":\"Fiat Toro 2018\"}', '2025-11-11 16:48:59'),
(1917, 58, 'coin_deducted_for_ad', 'ads', 150, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":99,\"balance_after\":98}', '2025-11-11 16:48:59'),
(1918, 3, 'ad_deleted', 'ads', 149, NULL, '2025-11-11 16:49:50'),
(1919, 3, 'ad_deleted', 'ads', 147, NULL, '2025-11-11 16:49:55'),
(1920, 3, 'ad_verification_activated', 'ads', 150, '{\"title\":\"Fiat Toro 2018\"}', '2025-11-11 16:50:52'),
(1921, 3, 'ad_approved', 'ads', 150, '{\"title\":\"Fiat Toro 2018\"}', '2025-11-11 16:50:53'),
(1922, 3, 'ad_approved', 'ads', 148, '{\"title\":\"Toyota Hilux SRV 2019\"}', '2025-11-11 16:51:02'),
(1923, 58, 'ad_created', 'ads', 151, '{\"title\":\"Volkswagen Nivus Comfortiline 2021\"}', '2025-11-11 17:00:47'),
(1924, 58, 'coin_deducted_for_ad', 'ads', 151, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":98,\"balance_after\":97}', '2025-11-11 17:00:47'),
(1925, 3, 'ad_created', 'ads', 152, '{\"title\":\"teste teete 2021\"}', '2025-11-11 17:01:22'),
(1926, 3, 'coin_deducted_for_ad', 'ads', 152, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":998,\"balance_after\":997}', '2025-11-11 17:01:22'),
(1927, 3, 'ad_verification_activated', 'ads', 151, '{\"title\":\"Volkswagen Nivus Comfortiline 2021\"}', '2025-11-11 17:03:52'),
(1928, 3, 'ad_approved', 'ads', 151, '{\"title\":\"Volkswagen Nivus Comfortiline 2021\"}', '2025-11-11 17:03:54'),
(1929, 3, 'ad_rejected', 'ads', 152, '{\"reason\":\"teste\"}', '2025-11-11 17:06:48'),
(1930, 3, 'ad_updated', 'ads', 152, '{\"title\":\"teste teete 2021\"}', '2025-11-11 17:10:38'),
(1931, 3, 'ad_updated', 'ads', 152, '{\"title\":\"teste teete 2021\"}', '2025-11-11 17:11:06'),
(1932, 3, 'ad_rejected', 'ads', 152, '{\"reason\":\"teste\"}', '2025-11-11 17:11:25'),
(1933, 3, 'ad_deleted', 'ads', 152, NULL, '2025-11-11 17:11:30'),
(1934, 58, 'ad_created', 'ads', 153, '{\"title\":\"Carro Fiat Argo Drive 1.0 completo\"}', '2025-11-11 17:29:16'),
(1935, 58, 'coin_deducted_for_ad', 'ads', 153, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":97,\"balance_after\":96}', '2025-11-11 17:29:16'),
(1936, 58, 'ad_created', 'ads', 154, '{\"title\":\"Volkswagen Virtus Highline 2024 2024\"}', '2025-11-11 17:49:23'),
(1937, 58, 'coin_deducted_for_ad', 'ads', 154, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":96,\"balance_after\":95}', '2025-11-11 17:49:23'),
(1938, 3, 'user_login', 'users', 3, NULL, '2025-11-11 17:57:43'),
(1939, 3, 'ad_verification_activated', 'ads', 153, '{\"title\":\"Carro Fiat Argo Drive 1.0 completo\"}', '2025-11-11 17:58:01'),
(1940, 3, 'ad_approved', 'ads', 153, '{\"title\":\"Carro Fiat Argo Drive 1.0 completo\"}', '2025-11-11 17:58:02'),
(1941, 3, 'ad_verification_activated', 'ads', 154, '{\"title\":\"Volkswagen Virtus Highline 2024 2024\"}', '2025-11-11 17:58:10'),
(1942, 3, 'ad_approved', 'ads', 154, '{\"title\":\"Volkswagen Virtus Highline 2024 2024\"}', '2025-11-11 17:58:12'),
(1943, 58, 'ad_created', 'ads', 155, '{\"title\":\"Volkswagen Virtus Comfortiline  2023\"}', '2025-11-11 18:15:41'),
(1944, 58, 'coin_deducted_for_ad', 'ads', 155, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":95,\"balance_after\":94}', '2025-11-11 18:15:41'),
(1945, 3, 'user_login', 'users', 3, NULL, '2025-11-11 18:15:58'),
(1946, 3, 'ad_verification_activated', 'ads', 155, '{\"title\":\"Volkswagen Virtus Comfortiline  2023\"}', '2025-11-11 18:16:11'),
(1947, 3, 'ad_approved', 'ads', 155, '{\"title\":\"Volkswagen Virtus Comfortiline  2023\"}', '2025-11-11 18:16:13'),
(1948, 3, 'ad_approved', 'ads', 155, '{\"title\":\"Volkswagen Virtus Comfortiline  2023\"}', '2025-11-11 18:27:28'),
(1949, 3, 'ad_approved', 'ads', 155, '{\"title\":\"Volkswagen Virtus Comfortiline  2023\"}', '2025-11-11 18:27:33'),
(1950, 58, 'ad_created', 'ads', 156, '{\"title\":\"Carro Fiat Mobi completo\"}', '2025-11-11 18:29:55'),
(1951, 58, 'coin_deducted_for_ad', 'ads', 156, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":94,\"balance_after\":93}', '2025-11-11 18:29:55'),
(1952, 3, 'ad_verification_activated', 'ads', 156, '{\"title\":\"Carro Fiat Mobi completo\"}', '2025-11-11 18:34:42'),
(1953, 3, 'ad_approved', 'ads', 156, '{\"title\":\"Carro Fiat Mobi completo\"}', '2025-11-11 18:34:43'),
(1954, 3, 'user_login', 'users', 3, NULL, '2025-11-11 20:03:44'),
(1955, 3, 'user_login', 'users', 3, NULL, '2025-11-11 21:32:54'),
(1956, 3, 'user_login', 'users', 3, NULL, '2025-11-11 22:04:00'),
(1957, 3, 'user_login', 'users', 3, NULL, '2025-11-11 23:38:17'),
(1958, 59, 'user_login_remember', 'users', 59, NULL, '2025-11-12 11:08:14'),
(1959, 3, 'user_login', 'users', 3, NULL, '2025-11-12 11:29:21'),
(1960, 3, 'settings_updated', 'settings', NULL, '{\"max_images\":13,\"allow_video_premium\":\"1\"}', '2025-11-12 11:29:32'),
(1961, 59, 'ad_created', 'ads', 157, '{\"title\":\"Volkswagen Nivus 2021 por R$ 108.900,00\"}', '2025-11-12 11:32:03'),
(1962, 59, 'coin_deducted_for_ad', 'ads', 157, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":102,\"balance_after\":101}', '2025-11-12 11:32:03'),
(1963, 3, 'user_login', 'users', 3, NULL, '2025-11-12 11:33:45'),
(1964, 3, 'ad_verification_activated', 'ads', 157, '{\"title\":\"Volkswagen Nivus 2021 por R$ 108.900,00\"}', '2025-11-12 11:34:38'),
(1965, 3, 'ad_approved', 'ads', 157, '{\"title\":\"Volkswagen Nivus 2021 por R$ 108.900,00\"}', '2025-11-12 11:34:44'),
(1966, 59, 'ad_updated', 'ads', 146, '{\"title\":\"Carro Jeep Compass completo\"}', '2025-11-12 11:37:13'),
(1967, 59, 'user_profile_updated', 'users', 59, NULL, '2025-11-12 11:50:22'),
(1968, 59, 'user_profile_updated', 'users', 59, NULL, '2025-11-12 11:51:18'),
(1969, 59, 'service_free_published', 'user_service_listings', 93, '{\"payment_reference\":\"SRV-B3E77AA0\",\"expires_at\":\"2025-12-12 11:54:34\",\"mode\":\"free\"}', '2025-11-12 11:54:34'),
(1970, 59, 'service_auto_renewal_toggle', 'user_service_listings', 93, '{\"auto_renewal_enabled\":1,\"action\":\"enabled\",\"status\":\"active\",\"payment_status\":\"paid\"}', '2025-11-12 11:59:20'),
(1971, 59, 'ad_auto_renewal_toggle', 'ads', 157, '{\"auto_renewal_enabled\":1,\"action\":\"enabled\"}', '2025-11-12 12:07:32'),
(1972, 3, 'ad_approved', 'ads', 146, '{\"title\":\"Carro Jeep Compass completo\"}', '2025-11-12 12:07:47'),
(1973, 59, 'user_logout', 'users', 59, NULL, '2025-11-12 12:11:01'),
(1974, 59, 'user_login', 'users', 59, NULL, '2025-11-12 12:12:25'),
(1975, 59, 'password_reset_requested', 'users', 59, NULL, '2025-11-12 12:14:01'),
(1976, 59, 'user_login', 'users', 59, NULL, '2025-11-12 12:14:29'),
(1977, 59, 'user_login', 'users', 59, NULL, '2025-11-12 12:15:16'),
(1978, 3, 'user_login', 'users', 3, NULL, '2025-11-12 12:32:12'),
(1979, 3, 'user_login', 'users', 3, NULL, '2025-11-12 13:54:45'),
(1980, 3, 'user_login', 'users', 3, NULL, '2025-11-12 14:27:28'),
(1981, 55, 'user_login', 'users', 55, NULL, '2025-11-12 14:27:37'),
(1982, 3, 'ad_created', 'ads', 158, '{\"title\":\"Honda City 2022\"}', '2025-11-12 14:28:25'),
(1983, 3, 'coin_deducted_for_ad', 'ads', 158, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":997,\"balance_after\":996}', '2025-11-12 14:28:25'),
(1984, 3, 'ad_created', 'ads', 159, '{\"title\":\"teste teste 2023\"}', '2025-11-12 14:29:05'),
(1985, 3, 'coin_deducted_for_ad', 'ads', 159, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":996,\"balance_after\":995}', '2025-11-12 14:29:05'),
(1986, 55, 'ad_created', 'ads', 160, '{\"title\":\"L200 3.2 HPE 2014 4x4\"}', '2025-11-12 14:40:26'),
(1987, 55, 'coin_deducted_for_ad', 'ads', 160, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":10002,\"balance_after\":10001}', '2025-11-12 14:40:26'),
(1988, 55, 'ad_verification_activated', 'ads', 160, '{\"title\":\"L200 3.2 HPE 2014 4x4\"}', '2025-11-12 14:42:22'),
(1989, 55, 'ad_approved', 'ads', 160, '{\"title\":\"L200 3.2 HPE 2014 4x4\"}', '2025-11-12 14:42:26'),
(1990, 3, 'ad_rejected', 'ads', 158, '{\"reason\":\"teste\"}', '2025-11-12 14:45:02'),
(1991, 3, 'ad_rejected', 'ads', 159, '{\"reason\":\"teste\"}', '2025-11-12 14:45:08'),
(1992, 3, 'ad_rejected', 'ads', 159, '{\"reason\":\"teste\"}', '2025-11-12 14:45:12'),
(1993, 3, 'settings_updated', 'settings', NULL, '{\"max_images\":13,\"allow_video_premium\":\"1\"}', '2025-11-12 14:48:07'),
(1994, 58, 'user_login_remember', 'users', 58, NULL, '2025-11-12 16:17:17'),
(1995, 58, 'ad_updated', 'ads', 155, '{\"title\":\"Volkswagen Virtus 2023\"}', '2025-11-12 16:18:05'),
(1996, 58, 'ad_created', 'ads', 161, '{\"title\":\"Fiat Argo Drive 2025\"}', '2025-11-12 16:28:52'),
(1997, 58, 'coin_deducted_for_ad', 'ads', 161, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":93,\"balance_after\":92}', '2025-11-12 16:28:52'),
(1998, 3, 'user_login', 'users', 3, NULL, '2025-11-12 16:29:35'),
(1999, 3, 'ad_approved', 'ads', 155, '{\"title\":\"Volkswagen Virtus 2023\"}', '2025-11-12 16:29:51'),
(2000, 3, 'ad_verification_activated', 'ads', 161, '{\"title\":\"Fiat Argo Drive 2025\"}', '2025-11-12 16:30:05'),
(2001, 3, 'ad_approved', 'ads', 161, '{\"title\":\"Fiat Argo Drive 2025\"}', '2025-11-12 16:30:07'),
(2002, 58, 'ad_created', 'ads', 162, '{\"title\":\"Chevrolet Tracker LTZ 2015\"}', '2025-11-12 16:35:47'),
(2003, 58, 'coin_deducted_for_ad', 'ads', 162, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":92,\"balance_after\":91}', '2025-11-12 16:35:47'),
(2004, 3, 'ad_verification_activated', 'ads', 162, '{\"title\":\"Chevrolet Tracker LTZ 2015\"}', '2025-11-12 16:42:53'),
(2005, 3, 'ad_approved', 'ads', 162, '{\"title\":\"Chevrolet Tracker LTZ 2015\"}', '2025-11-12 16:42:55'),
(2006, 58, 'ad_created', 'ads', 163, '{\"title\":\"Volkswagen Polo Track 2025\"}', '2025-11-12 16:54:44'),
(2007, 58, 'coin_deducted_for_ad', 'ads', 163, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":91,\"balance_after\":90}', '2025-11-12 16:54:44'),
(2008, 58, 'ad_created', 'ads', 164, '{\"title\":\"Fiat Strada Volcano 2025\"}', '2025-11-12 17:05:11'),
(2009, 58, 'coin_deducted_for_ad', 'ads', 164, '{\"amount\":1,\"reason\":\"Publicação de anúncio veicular\",\"balance_before\":90,\"balance_after\":89}', '2025-11-12 17:05:11'),
(2010, 3, 'user_login', 'users', 3, NULL, '2025-11-12 17:10:16'),
(2011, 3, 'ad_verification_activated', 'ads', 163, '{\"title\":\"Volkswagen Polo Track 2025\"}', '2025-11-12 17:10:27'),
(2012, 3, 'ad_approved', 'ads', 163, '{\"title\":\"Volkswagen Polo Track 2025\"}', '2025-11-12 17:10:28'),
(2013, 3, 'ad_verification_activated', 'ads', 164, '{\"title\":\"Fiat Strada Volcano 2025\"}', '2025-11-12 17:10:34'),
(2014, 3, 'ad_approved', 'ads', 164, '{\"title\":\"Fiat Strada Volcano 2025\"}', '2025-11-12 17:10:35'),
(2015, 3, 'user_login', 'users', 3, NULL, '2025-11-12 17:50:36'),
(2016, 3, 'user_login', 'users', 3, NULL, '2025-11-12 19:02:07'),
(2017, 3, 'user_login', 'users', 3, NULL, '2025-11-12 19:26:20'),
(2018, 3, 'user_login', 'users', 3, NULL, '2025-11-12 19:31:29'),
(2019, 3, 'user_login', 'users', 3, NULL, '2025-11-12 19:37:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `coin_purchase_orders`
--

CREATE TABLE `coin_purchase_orders` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `package_id` int UNSIGNED NOT NULL,
  `package_label` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coins_base` int NOT NULL DEFAULT '0',
  `coins_bonus` int NOT NULL,
  `coins_total` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BRL',
  `external_reference` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preference_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','cancelled','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_id` bigint UNSIGNED DEFAULT NULL,
  `payment_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raw_payload` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `coin_transactions`
--

CREATE TABLE `coin_transactions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `type` enum('buy','spend','admin_add','admin_remove','refund','bonus','ad_publish','ad_feature','ad_renewal','ad_auto_renewal','service_renewal','service_auto_renewal') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo de transação',
  `amount` int NOT NULL COMMENT 'Positivo=entrada, Negativo=saída',
  `balance_before` int NOT NULL COMMENT 'Saldo antes',
  `balance_after` int NOT NULL COMMENT 'Saldo depois',
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ads|purchase|manual|system',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mercado_pago|manual|system',
  `external_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID externo (MP preference id)',
  `payment_id` bigint UNSIGNED DEFAULT NULL,
  `payment_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Descrição legível',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `coin_transactions`
--

INSERT INTO `coin_transactions` (`id`, `user_id`, `type`, `amount`, `balance_before`, `balance_after`, `reference_id`, `reference_type`, `payment_method`, `external_reference`, `payment_id`, `payment_status`, `description`, `created_at`, `updated_at`) VALUES
(143, 3, 'admin_add', 4, 0, 4, 0, 'admin_operation', NULL, NULL, NULL, NULL, 'Teste', '2025-11-05 17:47:26', NULL),
(144, 3, 'ad_publish', -1, 4, 3, 138, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-05 18:56:28', NULL),
(145, 3, 'ad_publish', -1, 3, 2, 139, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-05 19:27:06', NULL),
(146, 3, 'ad_publish', -1, 2, 1, 140, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-06 19:34:06', NULL),
(147, 3, 'admin_add', 1000, 1, 1001, 0, 'admin_operation', NULL, NULL, NULL, NULL, 'meus', '2025-11-08 00:27:05', NULL),
(148, 55, 'admin_add', 10000, 3, 10003, 0, 'admin_operation', NULL, NULL, NULL, NULL, 'teste', '2025-11-08 00:42:06', NULL),
(149, 3, 'ad_publish', -1, 1001, 1000, 141, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-10 20:55:33', NULL),
(150, 55, 'ad_publish', -1, 10003, 10002, 142, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 12:21:39', NULL),
(151, 58, 'admin_add', 100, 3, 103, 0, 'admin_operation', NULL, NULL, NULL, NULL, 'Bônus de cadastro', '2025-11-11 13:13:27', NULL),
(152, 58, 'ad_publish', -1, 103, 102, 143, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 13:35:55', NULL),
(153, 58, 'ad_publish', -1, 102, 101, 144, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 14:17:35', NULL),
(154, 58, 'ad_publish', -1, 101, 100, 145, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 14:22:09', NULL),
(155, 59, 'admin_add', 100, 3, 103, 0, 'admin_operation', NULL, NULL, NULL, NULL, 'bonus', '2025-11-11 14:24:55', NULL),
(156, 59, 'ad_publish', -1, 103, 102, 146, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 14:42:03', NULL),
(157, 3, 'ad_publish', -1, 1000, 999, 147, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 16:39:34', NULL),
(158, 58, 'ad_publish', -1, 100, 99, 148, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 16:41:41', NULL),
(159, 3, 'ad_publish', -1, 999, 998, 149, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 16:48:18', NULL),
(160, 58, 'ad_publish', -1, 99, 98, 150, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 16:48:59', NULL),
(161, 58, 'ad_publish', -1, 98, 97, 151, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 17:00:47', NULL),
(162, 3, 'ad_publish', -1, 998, 997, 152, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 17:01:22', NULL),
(163, 58, 'ad_publish', -1, 97, 96, 153, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 17:29:16', NULL),
(164, 58, 'ad_publish', -1, 96, 95, 154, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 17:49:23', NULL),
(165, 58, 'ad_publish', -1, 95, 94, 155, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 18:15:41', NULL),
(166, 58, 'ad_publish', -1, 94, 93, 156, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-11 18:29:55', NULL),
(167, 59, 'ad_publish', -1, 102, 101, 157, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-12 11:32:03', NULL),
(168, 3, 'ad_publish', -1, 997, 996, 158, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-12 14:28:25', NULL),
(169, 3, 'ad_publish', -1, 996, 995, 159, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-12 14:29:05', NULL),
(170, 55, 'ad_publish', -1, 10002, 10001, 160, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-12 14:40:26', NULL),
(171, 58, 'ad_publish', -1, 93, 92, 161, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-12 16:28:52', NULL),
(172, 58, 'ad_publish', -1, 92, 91, 162, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-12 16:35:47', NULL),
(173, 58, 'ad_publish', -1, 91, 90, 163, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-12 16:54:44', NULL),
(174, 58, 'ad_publish', -1, 90, 89, 164, 'ads', NULL, NULL, NULL, NULL, 'Publicação de anúncio veicular', '2025-11-12 17:05:11', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int UNSIGNED NOT NULL,
  `to_email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_name` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_html` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_text` mediumtext COLLATE utf8mb4_unicode_ci,
  `attachments_json` json DEFAULT NULL,
  `status` enum('queued','sent','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `tries` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `email_queue`
--

INSERT INTO `email_queue` (`id`, `to_email`, `to_name`, `subject`, `body_html`, `body_text`, `attachments_json`, `status`, `tries`, `last_error`, `scheduled_at`, `sent_at`, `created_at`) VALUES
(186, 'jefilds2@gmail.com', 'Jefferson Aparecido Rodrigues Miranda', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Honda City 2022</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/138/honda-city-2022\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Honda City 2022 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-05 18:57:10', '2025-11-05 18:56:40'),
(187, 'jefilds2@gmail.com', 'Jefferson Aparecido Rodrigues Miranda', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>(DEMO) Honda City 2022</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/138/demo-honda-city-2022\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: (DEMO) Honda City 2022 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-05 19:03:09', '2025-11-05 19:01:21'),
(188, 'jefilds2@gmail.com', 'Jefferson Aparecido Rodrigues Miranda', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>(DEMO) Honda Africa Twin 2023</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/139/demo-honda-africa-twin-2023\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: (DEMO) Honda Africa Twin 2023 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-05 19:30:10', '2025-11-05 19:27:15'),
(189, 'jefilds2@gmail.com', 'Jefferson Aparecido Rodrigues Miranda', 'Seu anúncio foi rejeitado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Honda Civic 2025</em></h3>\n  <p>Status: <strong>Rejeitado</strong></p>\n    <p><strong>Motivo:</strong> corrija tal coisa</p>\n    <p><a href=\"https://classilivreautos.com/anuncios/editar.php?id=140\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Honda Civic 2025 Status: Rejeitado Motivo: corrija tal coisa Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-08 00:36:04', '2025-11-08 00:23:06'),
(190, 'jefilds2@gmail.com', 'Jefferson Aparecido Rodrigues Miranda', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Honda Civic 2025</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/140/honda-civic-2025\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Honda Civic 2025 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-08 00:36:05', '2025-11-08 00:23:29'),
(191, 'jefilds2@gmail.com', 'Jefferson Aparecido Rodrigues Miranda', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Jefferson Aparecido Rodrigues Miranda,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Honda Civic 2025</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 148.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/140/honda-civic-2025\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Jefferson Aparecido Rodrigues Miranda, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Honda Civic 2025 R$ 148.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-08 00:36:07', '2025-11-08 00:23:29'),
(192, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Seu anúncio foi rejeitado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Tesla teste 2021</em></h3>\n  <p>Status: <strong>Rejeitado</strong></p>\n    <p><strong>Motivo:</strong> teste fotos</p>\n    <p><a href=\"https://classilivreautos.com/anuncios/editar.php?id=141\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Tesla teste 2021 Status: Rejeitado Motivo: teste fotos Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-10 21:00:03', '2025-11-10 20:55:59'),
(193, 'joubertrodrigues97@gmail.com', 'JRM AUTO MULTIMARCAS', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Gla200 1.6T Enduro Teto panoramico</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/142/gla200-1-6t-enduro-teto-panoramico\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Gla200 1.6T Enduro Teto panoramico Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 12:36:03', '2025-11-11 12:22:13'),
(194, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Gla200 1.6T Enduro Teto panoramico</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 113.990,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/142/gla200-1-6t-enduro-teto-panoramico\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Gla200 1.6T Enduro Teto panoramico R$ 113.990,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 12:36:05', '2025-11-11 12:22:13'),
(195, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Carro Volkswagen Saveiro Trendline completo</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/143/carro-volkswagen-saveiro-trendline-completo\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Carro Volkswagen Saveiro Trendline completo Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 13:54:03', '2025-11-11 13:39:09'),
(196, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Carro Volkswagen Saveiro Trendline completo</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 69.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/143/carro-volkswagen-saveiro-trendline-completo\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Carro Volkswagen Saveiro Trendline completo R$ 69.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 13:54:04', '2025-11-11 13:39:09'),
(197, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Volkswagen Amarok 2020</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/144/volkswagen-amarok-2020\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Volkswagen Amarok 2020 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 14:36:03', '2025-11-11 14:19:16'),
(198, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Volkswagen Amarok 2020</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 150.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/144/volkswagen-amarok-2020\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Volkswagen Amarok 2020 R$ 150.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 14:36:05', '2025-11-11 14:19:16'),
(199, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Ford F250 XLT 2000</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/145/ford-f250-xlt-2000\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Ford F250 XLT 2000 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 14:36:07', '2025-11-11 14:27:19'),
(200, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Ford F250 XLT 2000</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 145.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/145/ford-f250-xlt-2000\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Ford F250 XLT 2000 R$ 145.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 14:36:09', '2025-11-11 14:27:19'),
(201, 'byel17lg@gmai.com', 'TUNEGA VEICULOS LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Carro Jeep Compass completo</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/146/carro-jeep-compass-completo\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Carro Jeep Compass completo Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 14:54:03', '2025-11-11 14:42:29'),
(202, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Carro Jeep Compass completo</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 142.900,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/146/carro-jeep-compass-completo\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Carro Jeep Compass completo R$ 142.900,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 14:54:05', '2025-11-11 14:42:29'),
(203, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Fiat Toro 2018</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/150/fiat-toro-2018\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Fiat Toro 2018 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 16:54:03', '2025-11-11 16:50:53'),
(204, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Fiat Toro 2018</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 85.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/150/fiat-toro-2018\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Fiat Toro 2018 R$ 85.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 16:54:05', '2025-11-11 16:50:53'),
(205, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Toyota Hilux SRV 2019</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/148/toyota-hilux-srv-2019\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Toyota Hilux SRV 2019 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 16:54:07', '2025-11-11 16:51:02');
INSERT INTO `email_queue` (`id`, `to_email`, `to_name`, `subject`, `body_html`, `body_text`, `attachments_json`, `status`, `tries`, `last_error`, `scheduled_at`, `sent_at`, `created_at`) VALUES
(206, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Toyota Hilux SRV 2019</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 190.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/148/toyota-hilux-srv-2019\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Toyota Hilux SRV 2019 R$ 190.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 16:54:08', '2025-11-11 16:51:02'),
(207, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Volkswagen Nivus Comfortiline 2021</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/151/volkswagen-nivus-comfortiline-2021\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Volkswagen Nivus Comfortiline 2021 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 17:18:03', '2025-11-11 17:03:54'),
(208, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Volkswagen Nivus Comfortiline 2021</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 100.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/151/volkswagen-nivus-comfortiline-2021\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Volkswagen Nivus Comfortiline 2021 R$ 100.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 17:18:05', '2025-11-11 17:03:54'),
(209, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Seu anúncio foi rejeitado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>teste teete 2021</em></h3>\n  <p>Status: <strong>Rejeitado</strong></p>\n    <p><strong>Motivo:</strong> teste</p>\n    <p><a href=\"https://classilivreautos.com/anuncios/editar.php?id=152\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: teste teete 2021 Status: Rejeitado Motivo: teste Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 17:18:07', '2025-11-11 17:06:48'),
(210, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Seu anúncio foi rejeitado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>teste teete 2021</em></h3>\n  <p>Status: <strong>Rejeitado</strong></p>\n    <p><strong>Motivo:</strong> teste</p>\n    <p><a href=\"https://classilivreautos.com/anuncios/editar.php?id=152\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: teste teete 2021 Status: Rejeitado Motivo: teste Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 17:18:09', '2025-11-11 17:11:25'),
(211, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Carro Fiat Argo Drive 1.0 completo</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/153/carro-fiat-argo-drive-1-0-completo\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Carro Fiat Argo Drive 1.0 completo Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 18:00:03', '2025-11-11 17:58:02'),
(212, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Carro Fiat Argo Drive 1.0 completo</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 66.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/153/carro-fiat-argo-drive-1-0-completo\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Carro Fiat Argo Drive 1.0 completo R$ 66.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 18:00:05', '2025-11-11 17:58:02'),
(213, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Volkswagen Virtus Highline 2024 2024</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/154/volkswagen-virtus-highline-2024-2024\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Volkswagen Virtus Highline 2024 2024 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 18:00:07', '2025-11-11 17:58:12'),
(214, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Volkswagen Virtus Highline 2024 2024</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 117.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/154/volkswagen-virtus-highline-2024-2024\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Volkswagen Virtus Highline 2024 2024 R$ 117.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 18:00:09', '2025-11-11 17:58:12'),
(215, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Volkswagen Virtus Comfortiline  2023</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/155/volkswagen-virtus-comfortiline-2023\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Volkswagen Virtus Comfortiline 2023 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 18:18:03', '2025-11-11 18:16:13'),
(216, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Volkswagen Virtus Comfortiline  2023</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 85.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/155/volkswagen-virtus-comfortiline-2023\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Volkswagen Virtus Comfortiline 2023 R$ 85.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 18:18:05', '2025-11-11 18:16:13'),
(217, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Volkswagen Virtus Comfortiline  2023</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/155/volkswagen-virtus-comfortiline-2023\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Volkswagen Virtus Comfortiline 2023 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 18:36:03', '2025-11-11 18:27:28'),
(218, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Volkswagen Virtus Comfortiline  2023</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/155/volkswagen-virtus-comfortiline-2023\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Volkswagen Virtus Comfortiline 2023 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 18:36:05', '2025-11-11 18:27:33'),
(219, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Carro Fiat Mobi completo</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/156/carro-fiat-mobi-completo\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Carro Fiat Mobi completo Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 18:36:07', '2025-11-11 18:34:43'),
(220, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Carro Fiat Mobi completo</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 57.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/156/carro-fiat-mobi-completo\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Carro Fiat Mobi completo R$ 57.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-11 18:36:08', '2025-11-11 18:34:43'),
(221, 'byel17lg@gmai.com', 'TUNEGA VEICULOS LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Volkswagen Nivus 2021 por R$ 108.900,00</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/157/volkswagen-nivus-2021-por-r-108-900-00\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Volkswagen Nivus 2021 por R$ 108.900,00 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 11:36:03', '2025-11-12 11:34:44'),
(222, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Volkswagen Nivus 2021 por R$ 108.900,00</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 108.900,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/157/volkswagen-nivus-2021-por-r-108-900-00\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Volkswagen Nivus 2021 por R$ 108.900,00 R$ 108.900,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 11:36:06', '2025-11-12 11:34:44'),
(223, 'byel17lg@gmai.com', 'TUNEGA VEICULOS LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Carro Jeep Compass completo</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/146/carro-jeep-compass-completo\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Carro Jeep Compass completo Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 12:18:03', '2025-11-12 12:07:47'),
(224, 'joubertrodrigues97@gmail.com', 'JRM AUTO MULTIMARCAS', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>L200 3.2 HPE 2014 4x4</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/160/l200-3-2-hpe-2014-4x4\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: L200 3.2 HPE 2014 4x4 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 14:54:03', '2025-11-12 14:42:26'),
(225, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">L200 3.2 HPE 2014 4x4</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 113.990,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/160/l200-3-2-hpe-2014-4x4\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: L200 3.2 HPE 2014 4x4 R$ 113.990,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 14:54:05', '2025-11-12 14:42:26');
INSERT INTO `email_queue` (`id`, `to_email`, `to_name`, `subject`, `body_html`, `body_text`, `attachments_json`, `status`, `tries`, `last_error`, `scheduled_at`, `sent_at`, `created_at`) VALUES
(226, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Seu anúncio foi rejeitado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Honda City 2022</em></h3>\n  <p>Status: <strong>Rejeitado</strong></p>\n    <p><strong>Motivo:</strong> teste</p>\n    <p><a href=\"https://classilivreautos.com/anuncios/editar.php?id=158\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Honda City 2022 Status: Rejeitado Motivo: teste Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 14:54:07', '2025-11-12 14:45:02'),
(227, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Seu anúncio foi rejeitado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>teste teste 2023</em></h3>\n  <p>Status: <strong>Rejeitado</strong></p>\n    <p><strong>Motivo:</strong> teste</p>\n    <p><a href=\"https://classilivreautos.com/anuncios/editar.php?id=159\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: teste teste 2023 Status: Rejeitado Motivo: teste Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 14:54:09', '2025-11-12 14:45:08'),
(228, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Seu anúncio foi rejeitado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>teste teste 2023</em></h3>\n  <p>Status: <strong>Rejeitado</strong></p>\n    <p><strong>Motivo:</strong> teste</p>\n    <p><a href=\"https://classilivreautos.com/anuncios/editar.php?id=159\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: teste teste 2023 Status: Rejeitado Motivo: teste Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 14:54:11', '2025-11-12 14:45:12'),
(229, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Volkswagen Virtus 2023</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/155/volkswagen-virtus-2023\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Volkswagen Virtus 2023 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 16:36:03', '2025-11-12 16:29:51'),
(230, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Fiat Argo Drive 2025</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/161/fiat-argo-drive-2025\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Fiat Argo Drive 2025 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 16:36:05', '2025-11-12 16:30:07'),
(231, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Fiat Argo Drive 2025</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 84.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/161/fiat-argo-drive-2025\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Fiat Argo Drive 2025 R$ 84.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 16:36:07', '2025-11-12 16:30:07'),
(232, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Chevrolet Tracker LTZ 2015</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/162/chevrolet-tracker-ltz-2015\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Chevrolet Tracker LTZ 2015 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 16:54:03', '2025-11-12 16:42:55'),
(233, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Chevrolet Tracker LTZ 2015</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 69.900,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/162/chevrolet-tracker-ltz-2015\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Chevrolet Tracker LTZ 2015 R$ 69.900,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 16:54:05', '2025-11-12 16:42:55'),
(234, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Volkswagen Polo Track 2025</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/163/volkswagen-polo-track-2025\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Volkswagen Polo Track 2025 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 17:18:03', '2025-11-12 17:10:28'),
(235, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Volkswagen Polo Track 2025</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 79.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/163/volkswagen-polo-track-2025\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Volkswagen Polo Track 2025 R$ 79.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 17:18:04', '2025-11-12 17:10:28'),
(236, 'opcaoveiculos1@hotmail.com', 'nunes e oliveira LTDA', 'Seu anúncio foi aprovado - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h3>Atualização do seu anúncio: <em>Fiat Strada Volcano 2025</em></h3>\n  <p>Status: <strong>Aprovado</strong></p>\n    <p><a href=\"https://classilivreautos.com/anuncio/164/fiat-strada-volcano-2025\">Ver anúncio</a></p>\n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n </div>\n', ' Atualização do seu anúncio: Fiat Strada Volcano 2025 Status: Aprovado Ver anúncio Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 17:18:06', '2025-11-12 17:10:35'),
(237, 'jefilds2@gmail.com', 'Classi Livre Autos', 'Novo anúncio encontrado para sua busca - Classi Livre Autos', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\">\n  <h2 style=\"color:#1a7f4e\">🎯 Novo anúncio encontrado!</h2>\n  <p>Oi Classi Livre Autos,</p>\n  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>\n  \n  <div style=\"background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e\">\n    <h3 style=\"margin-top:0\">Fiat Strada Volcano 2025</h3>\n    <p style=\"font-size:18px;color:#2d5f3f\"><strong>R$ 140.000,00</strong></p>\n    <p style=\"margin-bottom:0\">\n      <a href=\"https://classilivreautos.com/anuncio/164/fiat-strada-volcano-2025\" style=\"background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block\">Ver anúncio completo</a>\n    </p>\n  </div>\n  \n  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>\n  \n  <div style=\"margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;\">\n  <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;\">\n    <tr>\n      <td style=\"vertical-align:top;padding-right:20px;\">\n        <img src=\"https://classilivreautos.com/assets/img/assinatura.png\" alt=\"Classi Livre Autos\" style=\"width:140px;height:auto;display:block;\">\n      </td>\n      <td style=\"vertical-align:top;\">\n        <div style=\"font-weight:bold;font-size:16px;color:#006837;\">Equipe <span style=\"color:#C62828;\">Classi Livre</span></div>\n        <div style=\"color:#555555;font-size:13px;margin-top:3px;\">Suporte • Classi Livre Autos</div>\n        <div style=\"color:#333333;font-size:13px;margin-top:10px;\">\n          <strong>E-mail:</strong> <a href=\"mailto:contato@classilivre.com\" style=\"color:#006837;text-decoration:none;\">contato@classilivre.com</a>\n        </div>\n        <div style=\"color:#333333;font-size:13px;\">\n          <strong>Site:</strong> <a href=\"https://classilivreautos.com\" style=\"color:#006837;text-decoration:none;\">classilivreautos.com</a>\n        </div>\n        <p style=\"margin:6px 0 0 0;font-style:italic;color:#777777;font-size:12px;\">Classi Livre Autos — veículo certo, negócio transparente.</p>\n      </td>\n    </tr>\n  </table>\n</div>\n</div>\n', ' 🎯 Novo anúncio encontrado! Oi Classi Livre Autos, Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas: Fiat Strada Volcano 2025 R$ 140.000,00 Ver anúncio completo Acesse sua conta para gerenciar suas buscas salvas e notificações. Equipe Classi Livre Suporte • Classi Livre Autos E-mail: contato@classilivre.com Site: classilivreautos.com Classi Livre Autos — veículo certo, negócio transparente. ', NULL, 'sent', 1, NULL, NULL, '2025-11-12 17:18:08', '2025-11-12 17:10:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used_at`, `created_at`) VALUES
(19, 57, 'fd000d5d68d9c2ef71cdddcd703ce2c0a3cca61d4abef0471957eb10519bac36', '2025-11-10 21:15:19', '2025-11-10 20:16:13', '2025-11-10 20:15:19'),
(20, 59, '1b22cdd24c4baf4c5935c812fa9b39bcde61ba466294f3155710e180026ece18', '2025-11-12 13:14:01', NULL, '2025-11-12 12:14:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `premium_events_log`
--

CREATE TABLE `premium_events_log` (
  `id` int UNSIGNED NOT NULL,
  `subscription_id` int UNSIGNED DEFAULT NULL,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'payment.created, payment.updated, subscription.created, etc',
  `payload_snapshot` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON do webhook',
  `processed_at` datetime NOT NULL,
  `status` enum('sucesso','erro','pendente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `premium_plans`
--

CREATE TABLE `premium_plans` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price_decimal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `duration_days` int UNSIGNED NOT NULL DEFAULT '30',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `premium_plans`
--

INSERT INTO `premium_plans` (`id`, `name`, `description`, `price_decimal`, `duration_days`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Premium Mensal', 'Anuncie veículos sem limites, incluindo vídeo.', 29.90, 30, 1, '2025-10-30 02:55:44', '2025-10-30 02:55:44'),
(2, 'Premium Trimestral', 'Anuncie veículos sem limites, incluindo vídeo.', 79.83, 90, 1, '2025-10-30 02:55:44', '2025-10-30 02:55:44'),
(3, 'Premium Semestral', 'Anuncie veículos sem limites, incluindo vídeo.', 148.90, 180, 1, '2025-10-30 02:55:44', '2025-10-30 02:55:44'),
(8, 'Premium Ilimitado', 'Anuncie veículos sem limites, incluindo vídeo, enquanto testamos o sistema de pagamentos.', 0.00, 30, 1, '2025-10-31 14:46:21', '2025-10-31 14:46:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `premium_subscriptions`
--

CREATE TABLE `premium_subscriptions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `plan_id` int UNSIGNED NOT NULL,
  `started_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `payment_id` bigint UNSIGNED DEFAULT NULL,
  `payment_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `payment_currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'BRL',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `propagandas_campanhas`
--

CREATE TABLE `propagandas_campanhas` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ad_type` tinyint DEFAULT '1',
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affiliate_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cta_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Saiba Mais',
  `pages` json NOT NULL,
  `position` enum('center','bottom-right','bottom-left') COLLATE utf8mb4_unicode_ci DEFAULT 'center',
  `display_frequency` enum('sempre','por_sessao','por_dia','por_semana') COLLATE utf8mb4_unicode_ci DEFAULT 'por_sessao',
  `max_displays_per_period` int DEFAULT '1',
  `display_mode` enum('normal','exclusive','prioritized') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `rotation_type` enum('random','sequential','both') COLLATE utf8mb4_unicode_ci DEFAULT 'random',
  `priority_weight` int DEFAULT '1',
  `initial_delay_ms` int DEFAULT '3000',
  `reopen_delay_ms` int DEFAULT '300000',
  `status` enum('ativa','inativa','arquivada') COLLATE utf8mb4_unicode_ci DEFAULT 'ativa',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `total_visualizacoes` int DEFAULT '0',
  `total_cliques` int DEFAULT '0',
  `html_code` longtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `propagandas_campanhas`
--

INSERT INTO `propagandas_campanhas` (`id`, `title`, `ad_type`, `company_name`, `whatsapp`, `phone`, `city`, `company_image`, `description`, `image_url`, `affiliate_link`, `cta_text`, `pages`, `position`, `display_frequency`, `max_displays_per_period`, `display_mode`, `rotation_type`, `priority_weight`, `initial_delay_ms`, `reopen_delay_ms`, `status`, `created_at`, `updated_at`, `created_by`, `total_visualizacoes`, `total_cliques`, `html_code`) VALUES
(52, 'teste', 3, NULL, NULL, NULL, NULL, NULL, 'Campanha EMBED', NULL, NULL, 'Saiba Mais', '[\"home\", \"servicos\", \"anuncios\"]', 'center', 'por_dia', 1, 'normal', 'random', 1, 0, 0, 'inativa', '2025-11-11 01:17:46', '2025-11-11 01:25:19', 3, 0, 0, '<a href=\"https://classilivreautos.com/login_system/register.php\"\r\n   class=\"inline-block m-4 sm:m-6\" target=\"_blank\" rel=\"noopener\">\r\n  <img src=\"https://iili.io/KmonAtn.png\" width=\"900\"\r\n       alt=\"Crie sua conta no Classi Livre Autos\"\r\n       class=\"block max-w-full w-[1000px] h-auto object-contain\">\r\n</a>'),
(53, 'Inaguração', 3, NULL, NULL, NULL, NULL, NULL, 'Campanha EMBED', NULL, NULL, 'Saiba Mais', '[\"home\"]', 'center', 'por_dia', 1, 'normal', 'random', 1, 0, 0, 'ativa', '2025-11-11 01:31:39', '2025-11-11 01:31:39', 3, 0, 0, '<a href=\"https://classilivreautos.com/login_system/register.php\"\r\n   class=\"block w-full h-full\" target=\"_blank\" rel=\"noopener\">\r\n  <img src=\"https://iili.io/KmonAtn.png\" \r\n       alt=\"Crie sua conta no Classi Livre Autos\"\r\n       class=\"w-full h-full object-cover block\">\r\n</a>');

-- --------------------------------------------------------

--
-- Estrutura para tabela `propaganda_metrics`
--

CREATE TABLE `propaganda_metrics` (
  `campaign_id` int NOT NULL,
  `views_count` int DEFAULT '0',
  `clicks_count` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `propaganda_metrics`
--

INSERT INTO `propaganda_metrics` (`campaign_id`, `views_count`, `clicks_count`, `updated_at`) VALUES
(40, 22, 0, '2025-11-09 22:51:18'),
(41, 4, 0, '2025-11-09 22:58:09'),
(47, 4, 3, '2025-11-09 22:53:46'),
(48, 14, 2, '2025-11-09 23:01:34'),
(49, 15, 0, '2025-11-10 12:28:03'),
(50, 34, 1, '2025-11-10 19:02:58'),
(51, 3, 0, '2025-11-10 19:05:07'),
(52, 3, 0, '2025-11-11 01:25:23'),
(53, 21, 0, '2025-11-12 19:28:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int UNSIGNED NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `last_attempt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `ip_address`, `route`, `attempts`, `last_attempt`) VALUES
(52, '177.155.141.5', 'login', 1, '2025-10-19 02:20:27'),
(75, '186.195.134.90', 'register', 2, '2025-10-19 21:13:23'),
(139, '186.195.128.113', 'login', 3, '2025-10-21 16:11:03'),
(142, '186.195.132.202', 'resend_verify', 1, '2025-10-21 22:57:02'),
(384, '186.195.132.202', 'forgot_password', 1, '2025-10-29 15:05:45'),
(567, '2804:2a80:c02e:9500:9984:6f83:9015:5624', 'resend_verify', 1, '2025-11-05 16:02:47'),
(652, '2804:2a80:c061:8200:39dc:6100:5b96:5ffc', 'forgot_password', 4, '2025-11-10 19:00:21'),
(654, '2804:2a80:c061:8200:39dc:6100:5b96:5ffc', 'resend_verify', 1, '2025-11-10 19:02:16'),
(657, '128.201.65.181', 'login', 1, '2025-11-10 20:16:32'),
(660, '128.201.65.181', 'resend_verify', 1, '2025-11-10 20:13:41'),
(665, '2804:389:a13c:cab4:91f7:ec12:4d69:9b75', 'login', 1, '2025-11-10 21:15:58'),
(699, '2804:2a80:c030:e900:4986:ddb4:e404:144c', 'forgot_password', 1, '2025-11-12 12:14:03'),
(701, '2804:2a80:c03a:8200:d84:7403:f3ed:1090', 'login', 2, '2025-11-12 12:32:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `selector` char(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validator_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `last_used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `remember_tokens`
--

INSERT INTO `remember_tokens` (`id`, `user_id`, `selector`, `validator_hash`, `expires_at`, `created_at`, `last_used_at`) VALUES
(3, 3, 'e5c99ef55a264a6e6e', '9d2f00f3c215af88503c4a3305c00b3c305b8ec144a27533a88e1277f6e5edcf', '2025-11-24 18:59:06', '2025-10-25 18:59:06', NULL),
(10, 58, '40296cd3c2bc4bdd55', 'ce4c029c0249105cb3a5633faa9fb5fe0d16b28b91ae6ec68afa1f0529aae5fa', '2025-12-12 16:17:17', '2025-11-11 13:29:03', '2025-11-12 16:17:17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sales`
--

CREATE TABLE `sales` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `ad_id` int UNSIGNED NOT NULL,
  `source` enum('platform','external') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'platform',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `saved_searches`
--

CREATE TABLE `saved_searches` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `search_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `search_params` json NOT NULL,
  `notify` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `saved_searches`
--

INSERT INTO `saved_searches` (`id`, `user_id`, `search_name`, `search_params`, `notify`, `created_at`, `updated_at`) VALUES
(15, 3, 'Carros Guanhães', '{\"city\": \"Guanhães\", \"tipo\": \"todos\", \"price_max\": \"500000\", \"price_min\": \"0\"}', 1, '2025-11-08 00:19:50', '2025-11-08 00:19:50');

-- --------------------------------------------------------

--
-- Estrutura para tabela `search_notifications`
--

CREATE TABLE `search_notifications` (
  `id` int UNSIGNED NOT NULL,
  `saved_search_id` int UNSIGNED NOT NULL,
  `ad_id` int UNSIGNED NOT NULL,
  `notified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `search_notifications`
--

INSERT INTO `search_notifications` (`id`, `saved_search_id`, `ad_id`, `notified_at`) VALUES
(22, 15, 142, '2025-11-11 12:22:13'),
(23, 15, 143, '2025-11-11 13:39:09'),
(24, 15, 144, '2025-11-11 14:19:16'),
(25, 15, 145, '2025-11-11 14:27:19'),
(26, 15, 146, '2025-11-11 14:42:29'),
(27, 15, 150, '2025-11-11 16:50:53'),
(28, 15, 148, '2025-11-11 16:51:02'),
(29, 15, 151, '2025-11-11 17:03:54'),
(30, 15, 153, '2025-11-11 17:58:02'),
(31, 15, 154, '2025-11-11 17:58:12'),
(32, 15, 155, '2025-11-11 18:16:13'),
(33, 15, 156, '2025-11-11 18:34:43'),
(34, 15, 157, '2025-11-12 11:34:44'),
(35, 15, 160, '2025-11-12 14:42:26'),
(36, 15, 161, '2025-11-12 16:30:07'),
(37, 15, 162, '2025-11-12 16:42:55'),
(38, 15, 163, '2025-11-12 17:10:28'),
(39, 15, 164, '2025-11-12 17:10:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `service_custom_suggestions`
--

CREATE TABLE `service_custom_suggestions` (
  `id` int UNSIGNED NOT NULL,
  `label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usage_count` int UNSIGNED NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `settings`
--

CREATE TABLE `settings` (
  `id` int UNSIGNED NOT NULL,
  `key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `updated_at`) VALUES
(1, 'free_ads_per_month', '1', '2025-10-19 12:47:40'),
(2, 'max_images', '13', '2025-11-12 14:48:07'),
(3, 'allow_video_premium', '1', '2025-11-12 14:48:07'),
(4, 'max_video_size_mb', '60', '2025-11-12 14:48:07'),
(5, 'max_image_size_mb', '5', '2025-11-12 14:48:07'),
(12, 'free_ads_duration_days', '30', '2025-10-19 12:47:40'),
(17, 'services_free_limit_per_month', '10', '2025-10-19 12:47:40'),
(18, 'services_free_duration_days', '30', '2025-10-19 12:47:40'),
(19, 'services_all_free_enabled', '1', '2025-10-19 12:47:40'),
(20, 'services_base_price', '0', '2025-10-19 12:47:40'),
(21, 'services_plan_30_price', '0', '2025-10-19 12:47:40'),
(22, 'services_plan_120_price', '0', '2025-10-19 12:47:40'),
(23, 'services_plan_365_price', '0', '2025-10-19 12:47:40'),
(24, 'ad_price_premium', '0.04', '2025-10-27 19:43:29'),
(25, 'ad_price_free', '0.04', '2025-10-27 19:43:29'),
(26, 'ads_free_for_all', '0', '2025-11-05 17:32:24'),
(27, 'services_free_for_all', '1', '2025-11-05 17:32:24'),
(28, 'free_ads_per_user', '999', '2025-10-29 12:52:51'),
(29, 'free_services_per_user', '999', '2025-10-29 12:52:51'),
(32, 'service_price', '10.00', '2025-11-05 17:32:24'),
(470, 'clivre_medal_price', '15.00', '2025-11-05 17:32:24'),
(557, 'new_account_coins', '3', '2025-11-05 17:32:24'),
(563, 'premium_base_mensal', '29.90', '2025-11-05 15:39:16'),
(565, 'desconto_trimestral_pct', '11', '2025-11-05 15:39:16'),
(566, 'desconto_semestral_pct', '17', '2025-11-05 15:39:16'),
(570, 'cron_secret_token', '7d4e2f9c1a8b6e3d5f2c9a1b7e4d8a6f', '2025-10-29 21:18:23');

-- --------------------------------------------------------

--
-- Estrutura para tabela `store_metrics`
--

CREATE TABLE `store_metrics` (
  `id` int UNSIGNED NOT NULL,
  `store_id` int UNSIGNED NOT NULL,
  `ad_id` int UNSIGNED DEFAULT NULL,
  `event_type` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `viewer_id` int UNSIGNED DEFAULT NULL,
  `metadata` longtext COLLATE utf8mb4_unicode_ci,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `store_metrics`
--

INSERT INTO `store_metrics` (`id`, `store_id`, `ad_id`, `event_type`, `viewer_id`, `metadata`, `user_agent`, `ip_address`, `created_at`) VALUES
(145, 3, 138, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 18:56:44'),
(146, 3, 138, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 18:57:16'),
(147, 3, 138, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 18:58:34'),
(148, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 18:58:41'),
(149, 3, 138, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 18:59:04'),
(150, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 18:59:06'),
(151, 3, NULL, 'whatsapp_click', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 18:59:12'),
(152, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:00:14'),
(153, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:00:34'),
(154, 3, 138, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:01:27'),
(155, 3, 138, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:05:44'),
(156, 3, 138, 'favorite_added', 3, '{\"origin\":\"favorite_control\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:05:46'),
(157, 3, 138, 'favorite_removed', 3, '{\"origin\":\"favorite_control\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:05:47'),
(158, 3, 138, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:08:33'),
(159, 3, 138, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:11:58'),
(160, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:14:41'),
(161, 3, NULL, 'share_email', 3, '{\"origin\":\"ad_detail\",\"channel\":\"email\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:21:50'),
(162, 3, NULL, 'share_whatsapp', 3, '{\"origin\":\"ad_detail\",\"channel\":\"whatsapp\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:21:55'),
(163, 3, NULL, 'share_whatsapp', 3, '{\"origin\":\"ad_detail\",\"channel\":\"whatsapp\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:22:04'),
(164, 3, NULL, 'share_whatsapp', 3, '{\"origin\":\"ad_detail\",\"channel\":\"whatsapp\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:22:43'),
(165, 3, NULL, 'share_instagram', 3, '{\"origin\":\"ad_detail\",\"channel\":\"instagram\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:23:38'),
(166, 3, NULL, 'share_instagram', 3, '{\"origin\":\"ad_detail\",\"channel\":\"instagram\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:23:43'),
(167, 3, 138, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.130.212', '2025-11-05 19:25:03'),
(168, 3, NULL, 'share_whatsapp', NULL, '{\"origin\":\"ad_detail\",\"channel\":\"whatsapp\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.130.212', '2025-11-05 19:25:17'),
(169, 3, 138, 'favorite_added', 3, '{\"origin\":\"favorite_control\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:25:40'),
(170, 3, 138, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:27:21'),
(171, 3, 138, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:27:30'),
(172, 3, 139, 'ad_click', 3, '{\"origin\":\"favorites_list\",\"page\":\"/favoritos/meus_favoritos.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:28:02'),
(173, 3, 138, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:33:24'),
(174, 3, 138, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:33:55'),
(175, 3, 138, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:39:19'),
(176, 3, NULL, 'whatsapp_click', 3, '{\"origin\":\"contact_whatsapp\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:39:54'),
(177, 3, NULL, 'phone_click', 3, '{\"origin\":\"contact_phone\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:40:03'),
(178, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-05 19:40:06'),
(179, 3, 138, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":2}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.130.212', '2025-11-05 19:46:19'),
(180, 3, 139, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1d4:756f:0:58:be76:3c01', '2025-11-05 21:24:06'),
(181, 3, 139, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1d4:756f:0:58:be76:3c01', '2025-11-05 22:45:12'),
(182, 3, 139, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2607:fb91:d42:4680:8d54:236:7fec:647a', '2025-11-05 22:55:31'),
(183, 3, 139, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1d4:756f:0:58:be76:3c01', '2025-11-05 23:20:55'),
(184, 3, 139, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c07a:e200:913a:d201:70c8:e0d8', '2025-11-06 00:17:56'),
(185, 3, 139, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c02e:9500:9984:6f83:9015:5624', '2025-11-06 00:56:40'),
(186, 3, 139, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-06 19:30:02'),
(187, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-06 19:30:44'),
(188, 3, 139, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-07 02:11:51'),
(189, 3, 139, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:14:57'),
(190, 3, 138, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:18:00'),
(191, 3, 138, 'ad_click', 3, '{\"origin\":\"favorites_list\",\"page\":\"/favoritos/meus_favoritos.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:18:16'),
(192, 3, NULL, 'share_whatsapp', 3, '{\"origin\":\"ad_detail\",\"channel\":\"whatsapp\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:18:35'),
(193, 3, 138, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:20:49'),
(194, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:20:56'),
(195, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:30:45'),
(196, 3, 140, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:31:22'),
(197, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:31:43'),
(198, 3, NULL, 'route_google', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:31:53'),
(199, 3, 140, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:32:29'),
(200, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:32:33'),
(201, 55, NULL, 'store_visit', 55, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:44:40'),
(202, 55, NULL, 'store_visit', 55, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:45:47'),
(203, 3, 140, 'ad_click', 55, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:49:01'),
(204, 3, 140, 'ad_click', 55, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 00:50:45'),
(205, 3, 138, 'ad_click', 55, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":3}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 01:06:28'),
(206, 3, 138, 'favorite_removed', 55, '{\"origin\":\"favorite_control\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 01:07:32'),
(207, 3, NULL, 'share_instagram', 55, '{\"origin\":\"ad_detail\",\"channel\":\"instagram\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 01:09:01'),
(208, 3, NULL, 'share_whatsapp', 55, '{\"origin\":\"ad_detail\",\"channel\":\"whatsapp\",\"page\":\"/anuncio/138/demo-honda-city-2022\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', '2025-11-08 01:09:33'),
(209, 3, 140, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:2a80:c037:8300:d422:b68b:436f:1c6f', '2025-11-08 01:11:16'),
(210, 3, NULL, 'share_whatsapp', NULL, '{\"origin\":\"ad_detail\",\"channel\":\"whatsapp\",\"page\":\"/anuncio/140/honda-civic-2025\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:2a80:c037:8300:d422:b68b:436f:1c6f', '2025-11-08 01:11:33'),
(211, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '187.102.28.150', '2025-11-08 01:36:22'),
(212, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '187.102.28.150', '2025-11-08 02:35:00'),
(213, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '187.102.28.150', '2025-11-08 13:32:23'),
(214, 3, 138, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 17:49:19'),
(215, 3, 138, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 17:53:27'),
(216, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 17:54:48'),
(217, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:04:21'),
(218, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:04:23'),
(219, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:09:39'),
(220, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:10:04'),
(221, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:10:41'),
(222, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:15:36'),
(223, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:19:36'),
(224, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:24:08'),
(225, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:24:57'),
(226, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:25:34'),
(227, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:29:46'),
(228, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:30:46'),
(229, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:30:47'),
(230, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:30:57'),
(231, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:31:23'),
(232, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:32:43'),
(233, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:35:13'),
(234, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:35:23'),
(235, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:35:36'),
(236, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:36:56'),
(237, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:39:45'),
(238, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:39:59'),
(239, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:40:02'),
(240, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:40:04'),
(241, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '187.102.28.150', '2025-11-08 18:41:02'),
(242, 3, 139, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1d4:b1a3:0:65:2a89:af01', '2025-11-09 00:33:44'),
(243, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1d4:b1a3:0:65:2a89:af01', '2025-11-09 00:33:49'),
(244, 3, 139, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '187.102.28.150', '2025-11-09 00:40:31'),
(245, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '187.102.28.150', '2025-11-09 00:40:38'),
(246, 3, 138, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c4:6a45:ac7:d05a:3036:4f78', '2025-11-09 00:54:21'),
(247, 3, 139, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.130.212', '2025-11-10 02:36:40'),
(248, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.130.212', '2025-11-10 02:36:46'),
(249, 3, NULL, 'route_google', NULL, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.130.212', '2025-11-10 02:36:52'),
(250, 3, NULL, 'route_waze', NULL, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.130.212', '2025-11-10 02:36:59'),
(251, 3, 138, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-10 12:26:14'),
(252, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-10 12:26:19'),
(253, 3, 139, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-10 12:26:52'),
(254, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-10 12:26:59'),
(255, 3, 138, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-10 12:35:19'),
(256, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-10 12:35:21'),
(257, 3, 138, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a11c:e6e3:0:49:47a2:2601', '2025-11-10 16:03:51'),
(258, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a11c:e6e3:0:49:47a2:2601', '2025-11-10 16:04:19'),
(259, 3, 138, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '186.195.133.211', '2025-11-10 16:34:39'),
(260, 3, 138, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '186.195.133.211', '2025-11-10 16:51:37'),
(261, 3, 138, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a11c:e6e3:0:49:47a2:2601', '2025-11-10 16:51:41'),
(262, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a11c:e6e3:0:49:47a2:2601', '2025-11-10 16:52:00'),
(263, 3, 139, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '186.195.133.211', '2025-11-10 16:53:01'),
(264, 3, 139, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '191.39.80.88', '2025-11-10 18:22:18'),
(265, 3, 139, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '187.102.28.154', '2025-11-10 18:24:32'),
(266, 3, 139, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.130.212', '2025-11-10 19:15:03'),
(267, 3, 139, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a11c:e6e3:0:49:47a2:2601', '2025-11-11 00:51:38'),
(268, 3, 139, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a11c:e6e3:0:49:47a2:2601', '2025-11-11 00:53:28'),
(269, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a11c:e6e3:0:49:47a2:2601', '2025-11-11 00:53:31'),
(270, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.130.212', '2025-11-11 03:08:27'),
(271, 3, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.130.212', '2025-11-11 10:51:25'),
(272, 55, 142, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 12:22:35'),
(273, 55, 142, 'ad_click', 55, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2804:18:40a2:cdcf:591c:baa1:2faa:d468', '2025-11-11 12:23:58'),
(274, 55, 142, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 12:25:15'),
(275, 55, NULL, 'whatsapp_click', 3, '{\"origin\":\"contact_whatsapp\",\"page\":\"/anuncio/142/gla200-1-6t-enduro-teto-panoramico\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 12:25:23'),
(276, 55, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 12:25:32'),
(277, 55, 142, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 12:41:40'),
(278, 55, 142, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 12:42:17'),
(279, 55, NULL, 'whatsapp_click', 3, '{\"origin\":\"contact_whatsapp\",\"page\":\"/anuncio/142/gla200-1-6t-enduro-teto-panoramico\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 12:42:27'),
(280, 55, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 12:42:56'),
(281, 55, 142, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:00:30'),
(282, 55, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:01:08'),
(283, 55, 142, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:27:35'),
(284, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:38:03'),
(285, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:38:06'),
(286, 58, 143, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:39:41'),
(287, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:39:50'),
(288, 58, NULL, 'route_google', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:39:53'),
(289, 58, NULL, 'whatsapp_click', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:40:04'),
(290, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:41:05'),
(291, 58, NULL, 'route_google', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:41:06'),
(292, 58, 143, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:41:28'),
(293, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:41:34'),
(294, 58, NULL, 'route_waze', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:41:36'),
(295, 58, NULL, 'route_google', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:41:51'),
(296, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:44:47'),
(297, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:44:53'),
(298, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:45:07'),
(299, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:46:22'),
(300, 58, 143, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:50:56'),
(301, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:51:03'),
(302, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:51:11'),
(303, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 13:51:13'),
(304, 58, NULL, 'store_visit', 58, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-11 13:51:19'),
(305, 58, NULL, 'store_visit', 58, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-11 13:52:22'),
(306, 58, 143, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:b479:e438:daf7:bd44', '2025-11-11 13:54:28'),
(307, 58, 143, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 14:05:10'),
(308, 58, 143, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 14:09:16'),
(309, 58, NULL, 'phone_click', 3, '{\"origin\":\"contact_phone\",\"page\":\"/anuncio/143/carro-volkswagen-saveiro-trendline-completo\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 14:09:49'),
(310, 58, NULL, 'store_visit', 58, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-11 14:10:06'),
(311, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 14:10:21'),
(312, 58, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c5:a2d7::f4bf:c101', '2025-11-11 14:11:37'),
(313, 58, 145, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:a5f5:9e7c:627b:5078', '2025-11-11 14:27:33'),
(314, 58, 145, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c02e:9500:9984:6f83:9015:5624', '2025-11-11 14:35:39'),
(315, 58, 145, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:a5f5:9e7c:627b:5078', '2025-11-11 14:37:38'),
(316, 55, 142, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":4}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c02e:9500:9984:6f83:9015:5624', '2025-11-11 14:43:03'),
(317, 58, 145, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c02e:9500:9984:6f83:9015:5624', '2025-11-11 14:50:14'),
(318, 58, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c02e:9500:9984:6f83:9015:5624', '2025-11-11 14:51:21'),
(319, 59, 146, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c02e:9500:9984:6f83:9015:5624', '2025-11-11 14:51:49'),
(320, 58, 144, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":3}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c02e:9500:9984:6f83:9015:5624', '2025-11-11 14:52:30'),
(321, 58, 143, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":4}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-11 15:21:08'),
(322, 58, NULL, 'whatsapp_click', NULL, '{\"origin\":\"contact_whatsapp\",\"page\":\"/anuncio/143/carro-volkswagen-saveiro-trendline-completo\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-11 15:21:18'),
(323, 59, 146, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 15:35:33'),
(324, 58, 145, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 15:37:00'),
(325, 58, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 15:37:02'),
(326, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 15:40:37'),
(327, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 15:40:55'),
(328, 58, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 15:41:50'),
(329, 55, 142, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":5}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 15:44:00'),
(330, 55, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 15:44:02'),
(331, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 15:44:51'),
(332, 3, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 15:45:06'),
(333, 58, 144, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":3}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:b162:8feb:1:0:31cc:fde9', '2025-11-11 15:55:39'),
(334, 58, 144, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 16:23:29'),
(335, 58, 145, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.132.240', '2025-11-11 16:28:34'),
(336, 58, 150, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', '2025-11-11 16:53:06'),
(337, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', '2025-11-11 16:53:08'),
(338, 58, NULL, 'store_visit', 58, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-11 17:01:20'),
(339, 58, 151, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', '2025-11-11 17:04:49'),
(340, 58, 148, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', '2025-11-11 17:05:16');
INSERT INTO `store_metrics` (`id`, `store_id`, `ad_id`, `event_type`, `viewer_id`, `metadata`, `user_agent`, `ip_address`, `created_at`) VALUES
(341, 58, 150, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":3}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', '2025-11-11 17:05:25'),
(342, 58, 148, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', '2025-11-11 17:06:06'),
(343, 58, 143, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":7}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', '2025-11-11 17:12:26'),
(344, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', '2025-11-11 17:12:34'),
(345, 58, NULL, 'whatsapp_click', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', '2025-11-11 17:12:58'),
(346, 58, NULL, 'route_google', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', '2025-11-11 17:13:05'),
(347, 58, 156, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.128.60', '2025-11-11 20:05:42'),
(348, 55, 142, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":12}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.128.60', '2025-11-11 20:29:07'),
(349, 55, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '186.195.128.60', '2025-11-11 20:29:27'),
(350, 55, 142, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":12}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.128.60', '2025-11-11 20:29:31'),
(351, 55, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.128.60', '2025-11-11 20:29:33'),
(352, 58, 156, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.128.60', '2025-11-11 20:32:02'),
(353, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.128.60', '2025-11-11 20:32:04'),
(354, 58, 155, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.128.60', '2025-11-11 20:47:49'),
(355, 58, 143, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":11}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.128.60', '2025-11-11 20:47:58'),
(356, 58, 156, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:36:15'),
(357, 58, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:36:19'),
(358, 59, 157, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:37:08'),
(359, 59, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:37:11'),
(360, 59, NULL, 'store_visit', NULL, '{\"page\":\"/loja/59/tunega-veiculos-ltda\"}', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2804:2a80:c030:e900:4986:ddb4:e404:144c', '2025-11-12 11:38:09'),
(361, 59, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:42:17'),
(362, 59, NULL, 'whatsapp_click', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:44:06'),
(363, 59, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:44:54'),
(364, 59, 157, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:50:27'),
(365, 59, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:50:40'),
(366, 59, 157, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-12 11:51:02'),
(367, 59, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:51:19'),
(368, 59, NULL, 'route_google', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:51:31'),
(369, 59, NULL, 'route_waze', 3, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 11:51:44'),
(370, 59, 157, 'ad_click', 59, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c030:e900:e561:569:498:504c', '2025-11-12 11:56:15'),
(371, 59, NULL, 'share_whatsapp', 59, '{\"origin\":\"ad_detail\",\"channel\":\"whatsapp\",\"page\":\"/anuncio/157/volkswagen-nivus-2021-por-r-108-900-00\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c030:e900:e561:569:498:504c', '2025-11-12 11:56:31'),
(372, 59, NULL, 'store_visit', 59, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c030:e900:e561:569:498:504c', '2025-11-12 11:58:27'),
(373, 59, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/22G100 Instagram 405.1.0.27.75 (iPhone14,7; iOS 18_6_2; pt_BR; pt; scale=3.00; 1170x2532; IABMV/1; 817093285)', '2804:2a80:c030:e900:4986:ddb4:e404:144c', '2025-11-12 12:03:21'),
(374, 58, 155, 'ad_click', 59, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":3}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c030:e900:e561:569:498:504c', '2025-11-12 12:05:28'),
(375, 58, 156, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 12:07:02'),
(376, 59, 146, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 12:09:09'),
(377, 59, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:2a80:c032:e700:b518:c923:d792:88d7', '2025-11-12 12:09:14'),
(378, 58, 154, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":5}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c03a:8200:d84:7403:f3ed:1090', '2025-11-12 12:28:54'),
(379, 59, NULL, 'store_visit', NULL, '{\"page\":\"/loja/59/tunega-veiculos-ltda\"}', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2804:2a80:c030:e900:4986:ddb4:e404:144c', '2025-11-12 12:29:43'),
(380, 55, 142, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":13}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c03a:8200:d84:7403:f3ed:1090', '2025-11-12 12:30:02'),
(381, 59, 157, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c03a:8200:d84:7403:f3ed:1090', '2025-11-12 12:31:05'),
(382, 59, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c03a:8200:d84:7403:f3ed:1090', '2025-11-12 12:31:07'),
(383, 58, 155, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":4}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c03a:8200:d84:7403:f3ed:1090', '2025-11-12 12:31:12'),
(384, 58, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c03a:8200:d84:7403:f3ed:1090', '2025-11-12 12:31:15'),
(385, 58, NULL, 'route_google', NULL, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c03a:8200:d84:7403:f3ed:1090', '2025-11-12 12:31:26'),
(386, 58, NULL, 'whatsapp_click', NULL, '{\"origin\":\"store_hero\",\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2804:2a80:c03a:8200:d84:7403:f3ed:1090', '2025-11-12 12:31:30'),
(387, 59, 146, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2804:18:4089:f4ed:41e4:47bd:e8e:c6cc', '2025-11-12 13:05:28'),
(388, 58, 153, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":6}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a13f:f36c:d53d:54a:7253:45b0', '2025-11-12 13:52:33'),
(389, 58, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2804:389:a13f:f36c:d53d:54a:7253:45b0', '2025-11-12 13:53:28'),
(390, 55, 160, 'ad_click', 55, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2804:2a80:c04f:1e00:d1ee:7d2e:9ec8:f24d', '2025-11-12 14:44:32'),
(391, 55, 160, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.128.60', '2025-11-12 14:58:59'),
(392, 55, NULL, 'store_visit', 3, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.128.60', '2025-11-12 15:00:06'),
(393, 55, 160, 'ad_click', 3, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '186.195.128.60', '2025-11-12 15:03:29'),
(394, 55, 160, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c6:e77f:0:5e:8557:3101', '2025-11-12 16:18:31'),
(395, 55, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2804:389:a1c6:e77f:0:5e:8557:3101', '2025-11-12 16:18:36'),
(396, 59, 146, 'ad_click', 58, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":5}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-12 17:05:36'),
(397, 55, 160, 'ad_click', 58, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":4}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-12 17:05:51'),
(398, 55, 160, 'ad_click', 58, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":4}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-12 17:06:09'),
(399, 59, 157, 'ad_click', 58, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":6}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '143.208.69.117', '2025-11-12 17:06:23'),
(400, 58, 164, 'ad_click', NULL, '{\"origin\":\"homepage_featured\",\"page\":\"/\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '191.39.80.99', '2025-11-12 19:22:33'),
(401, 55, 160, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":6}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '191.39.80.99', '2025-11-12 19:23:05'),
(402, 58, 164, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '191.39.80.99', '2025-11-12 19:23:35'),
(403, 58, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '191.39.80.99', '2025-11-12 19:23:38'),
(404, 58, 163, 'ad_click', NULL, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":2}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '191.39.80.99', '2025-11-12 19:34:52'),
(405, 58, NULL, 'store_visit', NULL, '{\"page\":\"/loja.php\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '191.39.80.99', '2025-11-12 19:34:55'),
(406, 55, 160, 'ad_click', 3, '{\"origin\":\"search_results\",\"page\":\"/anuncios/listar.php\",\"position\":6}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '191.39.80.99', '2025-11-12 19:37:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `show_email` tinyint(1) NOT NULL DEFAULT '0',
  `phone` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_country_iso` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_country_code` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_is_whatsapp` tinyint(1) NOT NULL DEFAULT '0',
  `show_phone` tinyint(1) NOT NULL DEFAULT '1',
  `show_whatsapp` tinyint(1) NOT NULL DEFAULT '1',
  `document_type` enum('cpf','cnpj') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cpf',
  `document_number` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_street` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_complement` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_neighborhood` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_city` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_state` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_postal_code` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_address` tinyint(1) NOT NULL DEFAULT '0',
  `show_document` tinyint(1) NOT NULL DEFAULT '0',
  `terms_accepted_at` datetime DEFAULT NULL,
  `google_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `about` text COLLATE utf8mb4_unicode_ci,
  `instagram_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `store_theme_config` longtext COLLATE utf8mb4_unicode_ci,
  `free_ads_remaining` int UNSIGNED NOT NULL DEFAULT '0',
  `free_paid_ads_balance` int NOT NULL DEFAULT '0',
  `free_services_remaining` int UNSIGNED NOT NULL DEFAULT '0',
  `platform_sales_count` int UNSIGNED NOT NULL DEFAULT '0',
  `external_sales_count` int UNSIGNED NOT NULL DEFAULT '0',
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_premium` tinyint(1) NOT NULL DEFAULT '0',
  `coin_balance` int NOT NULL DEFAULT '0',
  `email_verified_at` datetime DEFAULT NULL,
  `status` enum('active','blocked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `last_session_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Token da última sessão ativa',
  `email_verify_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `email_pending` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `show_email`, `phone`, `phone_country_iso`, `phone_country_code`, `phone_is_whatsapp`, `show_phone`, `show_whatsapp`, `document_type`, `document_number`, `address_street`, `address_number`, `address_complement`, `address_neighborhood`, `address_city`, `address_state`, `address_postal_code`, `show_address`, `show_document`, `terms_accepted_at`, `google_id`, `google_avatar`, `about`, `instagram_url`, `store_theme_config`, `free_ads_remaining`, `free_paid_ads_balance`, `free_services_remaining`, `platform_sales_count`, `external_sales_count`, `password_hash`, `is_admin`, `is_premium`, `coin_balance`, `email_verified_at`, `status`, `created_at`, `updated_at`, `last_session_token`, `email_verify_token`, `reset_token`, `reset_expires_at`, `email_pending`) VALUES
(3, 'Classi Livre Autos', 'jefilds2@gmail.com', 0, '33987494051', 'BR', '+55', 1, 1, 1, 'cnpj', '52160988000150', 'Rua Demonstrativo', '344', 'Casa A', 'Demonstrativo', 'Guanhães', 'MG', '39740000', 1, 1, '2025-10-15 18:42:57', NULL, NULL, 'Vendedor de Veículos há mais de 20 anos em Guanhães-MG', 'https://www.instagram.com/jefferson.miranda.31', '{\"hero_background\":\"#ff7b00\",\"hero_text_color\":\"#2b11e8\",\"hero_accent_color\":\"#000000\",\"highlight_message\":\"MENSAGEM DESMONSTRATIVA: Nesse local você destaca a mensagem aos seus clientes! Teste\",\"cover_image\":\"https://i.postimg.cc/W3Sk5vL9/Chat-GPT-Image-10-de-nov-de-2025-09-02-03.png\",\"layout_style\":\"minimal\"}', 0, 0, 0, 1, 0, '$2y$10$W.ycEg.RW8X5qDWkf7Wl8upd1kPyee.xsTiaX6fuP6hfsFQ2ZY1W2', 1, 1, 995, '2025-10-15 18:43:01', 'active', '2025-10-15 18:42:57', '2025-11-12 19:37:27', '2bdd971c1f41a955c2e6d9b01514771d782b0027c2f9fa1e4549f1262779d9f9', NULL, NULL, NULL, NULL),
(53, 'Edmar Rocha', 'edmar.rocha.eng@gmail.com', 0, '31987634680', 'BR', '+55', 0, 1, 1, 'cpf', '11545346690', 'Av James Jacob de Queiroz', '410', NULL, 'Colina Verde II', 'Guanhães', 'MG', '39740000', 0, 0, '2025-11-05 16:00:13', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, '$2y$10$976sH63GBkcfOBqHZoE.HeANrmMsfRqihsX35mwCEMCaljThNsNc6', 0, 0, 1, '2025-11-05 16:04:16', 'active', '2025-11-05 16:00:13', '2025-11-05 16:04:33', 'bcc04702e9d3bfc90ebae67c8b9dca467b4c4d9df930092d7febb860c7d2689c', NULL, NULL, NULL, NULL),
(55, 'JRM AUTO MULTIMARCAS', 'joubertrodrigues97@gmail.com', 1, '33988489833', 'BR', '+55', 1, 1, 1, 'cnpj', '59969645000180', 'rua amarilis', '88', 'a', 'jardins 2', 'Guanhães', 'MG', '39740000', 0, 1, '2025-11-08 00:37:57', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, '$2y$10$w0Y2jUWE9OMIGPJ9K3OOIu0WNqwxWkrYT4.xNAgSxisxAv/NAU11.', 1, 1, 10001, '2025-11-08 00:38:45', 'active', '2025-11-08 00:37:57', '2025-11-12 14:40:26', '3853ba714ec7fd51aceffbeb3976747b876915ad132ab001cd81af15c4d064ba', NULL, NULL, NULL, NULL),
(56, 'HUGO FERREIRA DE JESUS', 'hf74550@gmail.com', 0, '33988296808', 'BR', '+55', 0, 1, 1, 'cpf', '12071729609', 'Rua Bruno Glória', '364', 'Casa fundos', 'Pito', 'Guanhães', 'MG', '39740000', 0, 0, '2025-11-10 19:02:07', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, '$2y$10$theJKSWax51KXnnbEFw4WO7bJsmRn.eQ/vhk3McNZ54LxiZG7PdQS', 0, 0, 3, NULL, 'active', '2025-11-10 19:02:07', '2025-11-10 19:02:16', '38e649323caf9ca016c2dc8a0bfc6b69d61b4c54a88baf07127f412b773cf911', '780ad1cf1f5f9f0f30e88797bc8087e3e283901d1bae268502d4304a42d6e9c6', NULL, NULL, NULL),
(57, 'Lucimara Moreira da Silva', 'lucimarasilva17955@gmail.com', 0, '31995713339', 'BR', '+55', 1, 1, 1, 'cpf', '70673633659', 'Casa', '18', 'Casa', 'Nova pousada', 'Sabinópolis', 'MG', '39750000', 0, 0, '2025-11-10 20:13:32', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, '$2y$10$3eQ7oN8ZKHOzd5Ux7DGGJetxOA8Hm8hPXon32ZvIoTPJUrbtRVAKK', 0, 0, 3, NULL, 'active', '2025-11-10 20:13:32', '2025-11-10 20:16:13', '1b85eb7aa7460d287442b3da44e4e4164b2089cb20c3cb3295ce107580cb42b4', '500df6fa79455173ff888f7c392c3fbff7cb291425b882accb25fe2e6a6669e4', NULL, NULL, NULL),
(58, 'nunes e oliveira LTDA', 'opcaoveiculos1@hotmail.com', 0, '3334212858', 'BR', '+55', 1, 1, 1, 'cnpj', '11943473000149', 'BR-120', '420', NULL, 'santa rita de cassia', 'Guanhães', 'MG', '39740000', 1, 1, '2025-11-11 13:10:06', NULL, NULL, '🚘Veículos novos e seminovos\r\n💰Descontos para Produtor Rural e CNPJ\r\n🤝Compra, venda e troca\r\n📍Guanhães-MG\r\n15 anos realizando sonhos!\r\nEntre em contato: (33) 3421-2858', 'https://www.instagram.com/opcaoveiculosguanhaes?igsh=cTM0aWN2ZWRiNjY3&utm_source=qr', '{\"hero_background\":\"#155e2b\",\"hero_text_color\":\"#ffffff\",\"hero_accent_color\":\"#facc15\",\"highlight_message\":\"\",\"cover_image\":\"https://iili.io/Kp952Cg.md.png\",\"layout_style\":\"default\"}', 0, 0, 0, 0, 0, '$2y$10$08JdGNI3Q3ojeZS26oBK.uuMtKg2TDsaThlTLY0JT8zoVoYmsEB7q', 0, 1, 89, '2025-11-11 13:12:04', 'active', '2025-11-11 13:10:06', '2025-11-12 17:05:11', '8e1305e2dd68bea7d61f3bec386b90cded24a5ae27a2496eabf7b11b182b960e', NULL, NULL, NULL, NULL),
(59, 'TUNEGA VEICULOS LTDA', 'byel17lg@gmail.com', 0, '33988322705', 'BR', '+55', 1, 1, 1, 'cnpj', '44592543000110', 'AV GOVERNADO MILTON CAMPOS', '4221', 'LOJA 2', 'CENTRO', 'GUANHAES', 'MG', '39740000', 1, 1, '2025-11-11 14:18:21', NULL, NULL, '🚘Venda de carros 🚘 \r\n🚘Troca de veículos🚘\r\n🚘Compra de veículos🚘\r\n🚘Seminovos com qualidade e preço justo 🚘\r\n\r\n📍Venha visitar nossa loja', 'https://www.instagram.com/tunegaveiculosltda?igsh=MWFiaXIzMzBpZ2k2YQ==', '{\"hero_background\":\"#155e2b\",\"hero_text_color\":\"#ffffff\",\"hero_accent_color\":\"#facc15\",\"highlight_message\":\"\",\"cover_image\":\"https://i.postimg.cc/yNnmgjSk/Whats-App-Image-2025-11-12-at-08-43-34.jpg\",\"layout_style\":\"default\"}', 0, 0, 0, 0, 0, '$2y$10$QyVTJ/WwkoG.d//zIxQuP.08xB.dCVxLcON7Vw7gAg7ZpfjS2yafO', 0, 1, 101, '2025-11-11 11:23:52', 'active', '2025-11-11 14:18:21', '2025-11-12 12:15:16', 'f167b7451bf7125503e785a933e590cbd45642577602df206e2480dcf315bdfe', '686d26421766c2747229dfcea620fd1e48509e6cd53fb1dbefb467421c105784', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_blacklist`
--

CREATE TABLE `user_blacklist` (
  `id` int UNSIGNED NOT NULL,
  `original_user_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_type` enum('cpf','cnpj') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_number` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `banned_by` int UNSIGNED DEFAULT NULL,
  `banned_at` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_service_listings`
--

CREATE TABLE `user_service_listings` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `document_type` enum('cpf','cnpj') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_number` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_document` tinyint(1) NOT NULL DEFAULT '0',
  `external_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `custom_category` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_street` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_complement` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_neighborhood` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_city` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_state` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_postal_code` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plan_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plan_label` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_decimal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BRL',
  `is_free` tinyint(1) NOT NULL DEFAULT '0',
  `is_highlighted` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending_payment','pending_review','active','suspended','archived','rejected','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_payment',
  `payment_status` enum('pending','pending_gateway','paid','failed','refunded','cancelled','free') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `renewal_count` int UNSIGNED NOT NULL DEFAULT '0',
  `payment_provider` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_expires_at` datetime DEFAULT NULL,
  `payment_metadata` text COLLATE utf8mb4_unicode_ci,
  `current_payment_id` int UNSIGNED DEFAULT NULL,
  `payment_preference_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `moderation_notes` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `last_paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `auto_renewal_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `last_renewed_at` datetime DEFAULT NULL,
  `auto_delete_at` datetime DEFAULT NULL,
  `highlight_until` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `user_service_listings`
--

INSERT INTO `user_service_listings` (`id`, `user_id`, `document_type`, `document_number`, `show_document`, `external_reference`, `name`, `category`, `custom_category`, `description`, `phone`, `whatsapp`, `email`, `address_street`, `address_number`, `address_complement`, `address_neighborhood`, `address_city`, `address_state`, `address_postal_code`, `plan_code`, `plan_label`, `price_decimal`, `currency`, `is_free`, `is_highlighted`, `status`, `payment_status`, `payment_method`, `renewal_count`, `payment_provider`, `payment_reference`, `payment_expires_at`, `payment_metadata`, `current_payment_id`, `payment_preference_id`, `notes`, `moderation_notes`, `rejection_reason`, `last_paid_at`, `created_at`, `updated_at`, `published_at`, `reviewed_at`, `expires_at`, `auto_renewal_enabled`, `last_renewed_at`, `auto_delete_at`, `highlight_until`, `deleted_at`, `payment_id`, `payment_amount`, `paid_at`) VALUES
(91, 53, 'cpf', '11545346690', 0, NULL, 'Engenheiro Mecanico', 'Outro serviço', 'Laudo Cautelar, Reenquadramento de Dano/Monta, Pericia Veicular', 'Somos um escritorio de engenharia especializados em vistorias e regularização de Monta.', '31987634680', '31987634680', 'edmar.rocha.eng@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'BRL', 0, 0, 'active', 'paid', NULL, 1, NULL, 'SRV-E90CF4AD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-05 16:08:04', '2025-11-05 16:08:04', '2025-11-05 16:08:04', '2025-11-05 16:08:04', NULL, NULL, 0, '2025-11-05 16:08:04', NULL, NULL, NULL, NULL, NULL, NULL),
(92, 58, 'cnpj', '11943473000149', 1, NULL, 'Opção Veiculos', 'Outro serviço', 'Concessionária ', '🚘Veículos novos e seminovos\r\n💰Descontos para Produtor Rural e CNPJ\r\n🤝Compra, venda e troca\r\n📍Guanhães-MG\r\n15 anos realizando sonhos!', '3334212858', '3334212858', NULL, 'BR-120', '420', NULL, 'santa rita de cassia', 'Guanhães', 'MG', '39740000', NULL, NULL, 0.00, 'BRL', 0, 0, 'active', 'paid', NULL, 1, NULL, 'SRV-AB775E98', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-11 13:58:54', '2025-11-11 13:58:54', '2025-11-11 13:58:54', '2025-11-11 13:58:54', NULL, '2025-12-11 13:58:54', 0, '2025-11-11 13:58:54', NULL, NULL, NULL, NULL, NULL, NULL),
(93, 59, 'cnpj', '44592543000110', 1, NULL, 'TUNEGA VEICULOS', 'Outro serviço', 'Concessionária ', '🚘Venda de carros 🚘 \r\n🚘Troca de veículos🚘\r\n🚘Compra de veículos🚘\r\n🚘Seminovos com qualidade e preço justo 🚘\r\n\r\n📍Venha visitar nossa loja', '33988322705', '33988322705', NULL, 'AV GOVERNADO MILTON CAMPOS', '4221', 'LOJA 2', 'CENTRO', 'GUANHAES', 'MG', '39740000', NULL, NULL, 0.00, 'BRL', 0, 0, 'active', 'paid', NULL, 1, NULL, 'SRV-B3E77AA0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-12 11:54:34', '2025-11-12 11:54:34', '2025-11-12 11:59:20', '2025-11-12 11:54:34', NULL, '2025-12-12 11:54:34', 1, '2025-11-12 11:54:34', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `session_token` varchar(255) NOT NULL COMMENT 'Token único da sessão',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP do navegador',
  `user_agent` text COMMENT 'User agent do navegador',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_activity_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL COMMENT 'Quando a sessão expira',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '1=ativo, 0=invalidado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `last_activity_at`, `expires_at`, `is_active`) VALUES
(380, 3, '41a4552f7105f89f3750b395bb8c39e993738b4d4fa7d3d0438ea7330e0c04e5', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-05 15:38:52', '2025-11-05 17:32:17', '2025-12-05 15:38:52', 0),
(381, 53, '120e1223dd530ea1a592c3d767d6af8874735d9bcbb2340d3215079bfdf68dcd', '2804:2a80:c02e:9500:9984:6f83:9015:5624', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 16:00:14', '2025-11-05 16:04:33', '2025-12-05 16:00:14', 0),
(382, 53, 'bcc04702e9d3bfc90ebae67c8b9dca467b4c4d9df930092d7febb860c7d2689c', '2804:2a80:c02e:9500:9984:6f83:9015:5624', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 16:04:33', '2025-11-05 16:10:31', '2025-12-05 16:04:33', 0),
(386, 3, '91bde44d41816b375fffaa672ce9644d0cde1461a5f3bd67487db3e0dc71e6b9', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-05 17:32:17', '2025-11-05 19:21:30', '2025-12-05 17:32:17', 0),
(387, 3, '92ee363fd6b10b528d0add38efcfc606d5ecdcef434ea262b9b3f13b1ccc275e', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-05 19:21:30', '2025-11-05 19:46:11', '2025-12-05 19:21:30', 0),
(388, 3, '8a7f1bb74d59bd1d590a2e50fe2eb56c19f3022c851eea752bcd90a421222d4c', '186.195.130.212', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-05 19:46:11', '2025-11-05 20:40:04', '2025-12-05 19:46:11', 0),
(389, 3, 'b8c71a7c70d4b0860ccaeb905012755d68bdc2141366eb991580de9fa2557ffe', '186.195.130.212', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-05 20:40:04', '2025-11-05 22:44:49', '2025-12-05 20:40:04', 0),
(390, 3, 'db1db8b10c4bcbac35aa6c0ca8fc49f84d182ca1959bf5a2593c6bd057c9a0a5', '2804:389:a1d4:756f:0:58:be76:3c01', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-05 22:44:49', '2025-11-05 23:13:12', '2025-12-05 22:44:49', 0),
(391, 3, '1d32af4b956764e06374f702546fc5cd29bbb851b7694ba2542a485f8080657d', '2804:389:a1d4:756f:0:58:be76:3c01', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-05 23:13:12', '2025-11-06 00:15:17', '2025-12-05 23:13:12', 0),
(392, 3, '0bdeed80adfd7a4fcbce7bded571a025ee616e1c012a399c3b07ca854d44ed27', '2804:2a80:c07a:e200:913a:d201:70c8:e0d8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-06 00:15:17', '2025-11-06 13:02:23', '2025-12-06 00:15:17', 0),
(393, 3, '288cd29a1c67a885a4113536f1cae9eb8917e012e3ad09652bba900a64cde17a', '186.195.130.212', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-06 13:02:23', '2025-11-06 13:41:57', '2025-12-06 13:02:23', 0),
(394, 3, '10f698afc4d29a86413ba0607fde0e5e0d926710d34eca0b917b6497277e9fac', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-06 13:41:57', '2025-11-06 19:30:35', '2025-12-06 13:41:57', 0),
(395, 3, '2b1962bc3a1ecff146572f88dbcf26f6a09c184d22ce3710dab3f47ee4585a6b', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-06 19:30:35', '2025-11-06 20:07:42', '2025-12-06 19:30:35', 0),
(396, 3, '7ffcc32bc58fd35ef02dfefa6ae5f7117bb9346e67fe7e5b56f3d4bd2d0cab29', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-06 20:07:42', '2025-11-07 00:23:16', '2025-12-06 20:07:42', 0),
(397, 3, '1b5e7dae3acbff1737aa88cb727ae68c2e2ff5d589f6b02d07b1ecbe69780877', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 00:23:16', '2025-11-07 01:27:02', '2025-12-07 00:23:16', 0),
(398, 3, '764bed06cbaab91d39fd3deb9a80daabe825b978eb3874fa036ebd3eae4e4eef', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 01:27:02', '2025-11-07 01:27:04', '2025-12-07 01:27:02', 0),
(399, 3, '1df74dd32765baab1d5f97e6e6bf824ed128a5a8f2ba1ebd9f63cc6168ac22c3', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 01:27:04', '2025-11-07 01:27:05', '2025-12-07 01:27:04', 0),
(400, 3, '741d6e40f78a516a364cbf33af4b879e48ebea2b83ae1df5ae4498977dbc73fa', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 01:27:05', '2025-11-07 01:27:15', '2025-12-07 01:27:05', 0),
(401, 3, '680be17e32bb92a6ae0b7e387d96613d626db2b34656dc9e283f0a32c94590d8', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 01:27:15', '2025-11-07 01:27:43', '2025-12-07 01:27:15', 0),
(402, 3, '04c51bd563f0707412cc7e61f1e8b4e2ed294b0c7a913a7342f3c6a0c47b3a68', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 01:27:43', '2025-11-07 02:17:13', '2025-12-07 01:27:43', 0),
(403, 3, '72f4d61b74a57878b8958174a2a900aec00e97975f55f86993fdb61c615a354c', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 02:17:13', '2025-11-07 02:25:11', '2025-12-07 02:17:13', 0),
(404, 3, 'fd23ce10ae2b5ed8ee4e423f939d2885e22e985fa1f40e2d0a96d04f347307e3', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 02:25:11', '2025-11-07 02:34:33', '2025-12-07 02:25:11', 0),
(405, 3, '308236bbd06d01fcab0134957da18b2b9038b02d137e2778885e6e5a019b7018', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 02:34:33', '2025-11-07 02:39:22', '2025-12-07 02:34:33', 0),
(406, 3, '4cd0f55bda9264a83ad0845be90703ccf6850403ba36fbba5fad9868c043f658', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 02:39:22', '2025-11-07 02:40:47', '2025-12-07 02:39:22', 0),
(407, 3, '4341936a30b6c87bc5d6acbed2b4b7fb695742bbd94e4dd194cbf8e8f3eedd69', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 02:40:47', '2025-11-07 03:09:21', '2025-12-07 02:40:47', 0),
(408, 3, 'de2d66a074ba311dd67f3d332710a19b6673237cc6043c5e56c134dac1cd75ba', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 03:09:21', '2025-11-07 03:11:20', '2025-12-07 03:09:21', 0),
(409, 3, '5e7b2c6cc6f3ee4c3b3c23a0be102ffbc5842da53c6323a1f9be9f55caad3300', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 03:11:20', '2025-11-07 03:12:06', '2025-12-07 03:11:20', 0),
(410, 3, 'b06e944c3bdaefdc8ed1f205100b9b7d4131c6f86fe507d0a5b9b9ac5f5cb6e3', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 03:12:06', '2025-11-07 03:18:44', '2025-12-07 03:12:06', 0),
(411, 3, '5ebfeb1a6f03bc48b2e0576c120934602e45e1d678a785bc6c21f200105832dd', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 03:18:44', '2025-11-07 03:28:07', '2025-12-07 03:18:44', 0),
(412, 3, '96cb1f367d468178ce16ea557a4bf47fb54d7ffae9a542c08cb8b6e51b6c8c45', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 03:28:07', '2025-11-07 03:35:26', '2025-12-07 03:28:07', 0),
(413, 3, '0b304c139e385a851f9e435ede108345a7f2b154c3052032051e683d71c6f133', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 03:35:26', '2025-11-07 13:37:19', '2025-12-07 03:35:26', 0),
(414, 3, '15c8d880a188702c6e7632acbd2f6a40f8de044cdb2c6340282943ce5e09993f', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 13:37:19', '2025-11-07 13:46:35', '2025-12-07 13:37:19', 0),
(415, 3, '009af0f85bdbeb842487081a606b1aa652eb63cd0ecf3cb47b033830a4df2511', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 13:46:35', '2025-11-07 13:52:19', '2025-12-07 13:46:35', 0),
(416, 3, '602ad9188fccbc91211c037d08921b66ec52c51bd56d98dfbfdba0ef0d3f1b94', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 13:52:19', '2025-11-07 14:07:33', '2025-12-07 13:52:19', 0),
(417, 3, 'b5e97b8f1109d61263deeba3bc92e8f8e7fb1024976bd0ce1ff5c51f88d9a58d', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 14:07:33', '2025-11-07 14:36:46', '2025-12-07 14:07:33', 0),
(418, 3, 'a1b2b783cb8296d8660ca82b388278a21b14a8ab4a31085e3627e0b7bacbc91e', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 14:36:46', '2025-11-07 14:37:52', '2025-12-07 14:36:46', 0),
(419, 3, 'e84fd7c0486dd4220f6464b64c891337ed5757dd6456703c67f7f3ef507c2a06', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 14:37:52', '2025-11-07 14:44:33', '2025-12-07 14:37:52', 0),
(420, 3, '1f078f0bc000337d8926d67df79f4fec8178fbd968d859db3dc850dfeb5446dd', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 14:44:33', '2025-11-07 16:20:45', '2025-12-07 14:44:33', 0),
(421, 3, '0812198bb948ff3fe734fba723801978130e9f0df474c8d9d83fd2fbcaeb0fb9', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 16:20:45', '2025-11-07 16:48:44', '2025-12-07 16:20:45', 0),
(422, 3, '74bb8dfe8a54a9c38a245e44c522df64fc4f953cb0364e9957ddc33b4f89f172', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 16:48:44', '2025-11-07 17:24:27', '2025-12-07 16:48:44', 0),
(423, 3, '9f38072e04953a43a39ceeb5b4802f2f15d449f6de4d968734043c10c0a64e07', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 17:24:27', '2025-11-07 18:18:43', '2025-12-07 17:24:27', 0),
(424, 3, 'a5346cb74099f9e33b565ca66fccdb3c49f2d9c389dcbfd04abe92d4a9b4e007', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 18:18:43', '2025-11-07 18:28:27', '2025-12-07 18:18:43', 0),
(425, 3, 'ff2a5293f3398fd8aa9d54f182d5e4b338dae2d3737a443d681cc781cbc09404', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 18:28:27', '2025-11-07 18:30:56', '2025-12-07 18:28:27', 0),
(426, 3, 'a6dfb6a97db99877328f82f46b4a176b07f53fafa55b2b1a5ace12dfaa24764c', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 18:30:56', '2025-11-07 18:31:33', '2025-12-07 18:30:56', 0),
(427, 3, '934cdaca5ae4a5663c4298f2b75f2991137029f401e8fbf53fccf6a995c6dbb2', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 18:31:33', '2025-11-07 18:49:57', '2025-12-07 18:31:33', 0),
(428, 3, 'beed0da0bde2c9362218f9f8bd70090f9e1a992294ac25876770267440e2e479', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 18:49:57', '2025-11-07 18:56:35', '2025-12-07 18:49:57', 0),
(429, 3, 'a97599df116db234b516a64cd407ebc3a799bba75a21e0054f94472d9efa5d59', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 18:56:35', '2025-11-07 19:00:43', '2025-12-07 18:56:35', 0),
(430, 3, 'f6618dba767d2bd3a9486264aaef7003680d2f20791f43bb89f15b588c46e1ed', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 19:00:43', '2025-11-07 19:42:14', '2025-12-07 19:00:43', 0),
(431, 3, '1f221e0dc257b495e940cec4681722bba535220663ec2d23da5aadebb55c5cfa', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 19:42:14', '2025-11-07 19:50:13', '2025-12-07 19:42:14', 0),
(432, 3, '76353814841c108cf42bf5657d902da7b2a70343541957a55e30509fbd629f63', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 19:50:13', '2025-11-07 19:55:16', '2025-12-07 19:50:13', 0),
(433, 3, 'e20e47cdc5e3223c9a73c577598902dce31610d2e556058b81a8bf3414e116d3', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 19:55:16', '2025-11-07 20:08:58', '2025-12-07 19:55:16', 0),
(434, 3, 'b49dd5e53db851488e854055b648ba0d556d8d0f4ccecbcd391614aa3048ad98', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 20:08:58', '2025-11-07 20:36:02', '2025-12-07 20:08:58', 0),
(435, 3, 'f6090101d80c1b7ee560e3db52cb1b786a4b1e25bc434cd3955efdab85ee72a7', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 20:36:02', '2025-11-07 22:19:36', '2025-12-07 20:36:02', 0),
(436, 3, '5413d06f38dd17dc1ada85bc331832eb32dbe7de68830611a50320d2c5a95e16', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-07 22:19:36', '2025-11-07 23:45:29', '2025-12-07 22:19:36', 0),
(437, 3, 'be388de3cf1ded228c07914e9da63e228bb7fff6327a86adfc07bbb58c993858', '2804:2a80:c037:8300:d422:b68b:436f:1c6f', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-07 23:45:29', '2025-11-08 00:07:40', '2025-12-07 23:45:29', 0),
(438, 3, '33cd48ff4c3e33c6a74fa22074c83db9654cdac273163a6a6068e5ac78b3737a', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 00:07:40', '2025-11-08 00:28:44', '2025-12-08 00:07:40', 0),
(439, 3, 'ddd2928b51960f051d1ebfcca61fdcd59fe0013740aa72817e04d16c3db22375', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 00:29:36', '2025-11-08 00:32:24', '2025-12-08 00:29:36', 0),
(440, 55, '8556670643dcaae401d68d6ae3fc84a0250ef925b079c6c925fc5a27bd2fa741', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 00:37:59', '2025-11-08 00:40:16', '2025-12-08 00:37:59', 0),
(441, 55, 'c2ec9b94c2347b88f07f52a65d54c27b6731de4a9495e3c0fef52efba4671907', '2804:2a80:c037:8300:8c5f:1829:48aa:2852', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 00:40:16', '2025-11-10 20:12:15', '2025-12-08 00:40:16', 0),
(442, 3, '709d04d7eff91ca9c8f22c5e51c2317bb7d0938c4712afd86aa51cb7175b97f6', '187.102.28.150', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-08 01:35:45', '2025-11-08 13:31:59', '2025-12-08 01:35:45', 0),
(443, 3, '4e64f3e5a76cd65302e1a3d436c8c26d09a262f7083efa7b7cee5afdea05f621', '187.102.28.150', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-08 13:31:59', '2025-11-08 18:13:28', '2025-12-08 13:31:59', 0),
(444, 3, '77067fd17b30d00f1d2fbfc537bb4ee5df8b1bd448c1b07edd2748a8a56052cb', '187.102.28.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 18:13:28', '2025-11-08 18:20:05', '2025-12-08 18:13:28', 0),
(445, 3, 'a231d547021aacf0fe3b08db63f95d36cae7b68c79495787de1840c7e77b288e', '187.102.28.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 18:20:05', '2025-11-08 19:01:03', '2025-12-08 18:20:05', 0),
(446, 3, 'fd7759dbefd7090d3fd13374e09aa86b6adee2e313cad4a062a56a760a03878c', '187.102.28.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 19:01:03', '2025-11-08 19:08:33', '2025-12-08 19:01:03', 0),
(447, 3, 'b9df5d5d76c068422ace91f0ed14f7ccbee205231d351fb47725cf86eca4dd8a', '187.102.28.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 19:08:33', '2025-11-08 19:18:16', '2025-12-08 19:08:33', 0),
(448, 3, '0329a2f14af2e56238cf369714d4e938c1a58185903685b09b0fb3893dcb5ad4', '187.102.28.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 19:18:16', '2025-11-08 19:43:36', '2025-12-08 19:18:16', 0),
(449, 3, '2f789bd7f28c4a45f6be9b364e4fb55c6d2a5c30accca5f9795a1018143c42f0', '187.102.28.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 19:43:36', '2025-11-08 20:23:51', '2025-12-08 19:43:36', 0),
(450, 3, '595762a16d241b5b345b70d4fd5515f22d7cdf1e8f4d56174512cb6847eb3d54', '187.102.28.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 20:23:51', '2025-11-08 22:00:43', '2025-12-08 20:23:51', 0),
(451, 3, '99ae1fa872f2b3f2f22545c4c1c69589bcff435c3af362e60552ab6a1054f6df', '187.102.28.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-08 22:00:43', '2025-11-09 00:32:03', '2025-12-08 22:00:43', 0),
(452, 3, 'f703f1be409fa4f832dcf41d2bd67026a51aa84ac775d084edc3476e5102700b', '2804:389:a1d4:b1a3:0:65:2a89:af01', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-09 00:32:03', '2025-11-09 02:26:00', '2025-12-09 00:32:03', 0),
(453, 3, '182fd46738544ce3bf87f7c00b1863117d398527245ba7d52c8b56b5fe511e82', '186.195.130.212', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-09 02:26:00', '2025-11-09 17:33:56', '2025-12-09 02:26:00', 0),
(454, 3, '8f9a6b2c84682fd94657f794a94caf879838ceed353999e649055d8833a95ef8', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-09 17:33:56', '2025-11-09 22:09:47', '2025-12-09 17:33:56', 0),
(455, 3, 'deaba639cae47835227c0745ac995884de26284fa3bc0abbae1e9730cfa7d9b5', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-09 22:09:47', '2025-11-09 22:42:22', '2025-12-09 22:09:47', 0),
(456, 3, '01a0d2437c0b3849725c48bef2780fabef6f49ba6b3ca98fac46240640f6068c', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-09 22:43:46', '2025-11-10 02:37:19', '2025-12-09 22:43:46', 0),
(457, 3, '113e0ec9f30c66f0af2e0439051acacec88e3939e4cc5a584d7908602e8aa23f', '186.195.130.212', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-10 02:37:19', '2025-11-10 12:02:27', '2025-12-10 02:37:19', 0),
(458, 3, '9c80c8aa60725607b13f4437ff38cdb23656dd672c3dcd0067cff82fc398d6ee', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-10 12:02:27', '2025-11-10 12:26:32', '2025-12-10 12:02:27', 0),
(459, 3, '962a5ff7a48694f69f45373c62bbf01894502b8155b2dd4f11da9bd4c630c126', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-10 12:26:32', '2025-11-10 14:00:06', '2025-12-10 12:26:32', 0),
(460, 3, '2a46046f7c64c4380a25bdcebccb0651cb9008a949917914799c0a43af44ceab', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-10 14:00:06', '2025-11-10 16:05:19', '2025-12-10 14:00:06', 0),
(461, 3, '7effa3a970e466da5f2b3eb5574444abf814e52d3cb75523ec9d6fcc2ae8265b', '2804:389:a11c:e6e3:0:49:47a2:2601', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-10 16:05:19', '2025-11-10 16:41:30', '2025-12-10 16:05:19', 0),
(462, 3, 'c9ab132a835f83d0bf80c62fe6e2f81e6489b5829f0f1a9b7ed3330465754cbe', '186.195.131.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-10 16:41:30', '2025-11-10 16:52:14', '2025-12-10 16:41:30', 0),
(463, 3, 'de6d066670fefd6747a3c6f83e2e86135d40b11d4449e800f3b2e3c8bc3f93e5', '2804:389:a11c:e6e3:0:49:47a2:2601', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-10 16:52:14', '2025-11-10 17:22:38', '2025-12-10 16:52:14', 0),
(464, 3, '8443032a87bbb50015f355c28f849d131f4e1fd5e260b956d228d2a6db45e52a', '186.195.131.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-10 17:22:38', '2025-11-10 18:54:46', '2025-12-10 17:22:38', 0),
(465, 3, '1ba30c371d80af17281fe4555b8c95185cb9f6900cb52fd575df49d1e9e880d6', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-10 18:54:46', '2025-11-10 19:17:47', '2025-12-10 18:54:46', 0),
(466, 56, '38e649323caf9ca016c2dc8a0bfc6b69d61b4c54a88baf07127f412b773cf911', '2804:2a80:c061:8200:39dc:6100:5b96:5ffc', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-10 19:02:09', '2025-11-10 19:02:31', '2025-12-10 19:02:09', 1),
(467, 3, '61fd59573cd2cb8c74d83a68937d57b88970c4f46c4ac1bf1d969f01fdca4e48', '186.195.130.212', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-10 19:17:47', '2025-11-10 19:43:29', '2025-12-10 19:17:47', 0),
(468, 3, 'e404b4f243791de4e8811c3d68927e201ae128e1a8c7c20579df732a58bc79d2', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-10 19:43:29', '2025-11-10 20:37:15', '2025-12-10 19:43:29', 0),
(469, 55, '3232bacf875bf5deefbc2480f759d23bdcb0093c3cd6780873aa435b1d3a2a36', '2804:18:4081:f3d6:f0aa:b7df:6f74:362f', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2025-11-10 20:12:15', '2025-11-10 20:26:28', '2025-12-10 20:12:15', 0),
(470, 57, '1b85eb7aa7460d287442b3da44e4e4164b2089cb20c3cb3295ce107580cb42b4', '128.201.65.181', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-10 20:13:34', '2025-11-10 20:29:01', '2025-12-10 20:13:34', 1),
(471, 55, 'b0e059f30f13c3f1796e1c4afbf41117d4dd1a3d09f798ad41f2d12ba5341c2d', '2804:18:4081:f3d6:f0aa:b7df:6f74:362f', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2025-11-10 20:26:28', '2025-11-10 20:26:30', '2025-12-10 20:26:28', 0),
(472, 55, '4cce1439e880664828dd0d8c02dd985f8a6ceb46cbfcf0d69fa19bfe7b8031a3', '2804:18:4081:f3d6:f0aa:b7df:6f74:362f', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2025-11-10 20:26:30', '2025-11-11 11:56:29', '2025-12-10 20:26:30', 0),
(473, 3, 'b70fb3a45f032047990d540d2ed5e137d1f37feeea13f27c0cc5f811b895012a', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-10 20:37:15', '2025-11-10 20:52:15', '2025-12-10 20:37:15', 0),
(474, 3, 'deee935c2651799b7aaeaf3c8c90d19f55feae869dcf8dd6cff7f7ae89d7c72f', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-10 20:52:15', '2025-11-10 21:16:08', '2025-12-10 20:52:15', 0),
(475, 3, '04072d33b3b3be5c44f832d5b86c55fbfe70c29ab202f1d4424c30b10255b841', '2804:389:a11c:e6e3:0:49:47a2:2601', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-10 21:16:08', '2025-11-10 21:20:41', '2025-12-10 21:16:08', 0),
(476, 3, '618b4e84a6b1a46542a1b9e75fb91e0f39397f679318526eca2c0b08e019fe21', '2804:389:a11c:e6e3:0:49:47a2:2601', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-10 21:20:41', '2025-11-10 21:38:49', '2025-12-10 21:20:41', 0),
(477, 3, 'bcc88afb634738d930c938d168fcf33cb0df2573cd8c1d61446bbae484376bf2', '2804:2a80:c07a:e200:a1ab:1ca:5ffc:da3c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-10 21:38:49', '2025-11-10 22:41:16', '2025-12-10 21:38:49', 0),
(478, 3, 'aad1416f3c75fb22256265b28e53b29918100dc7015f32e7a4178ca366e45882', '2804:389:a11c:e6e3:0:49:47a2:2601', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-10 22:41:16', '2025-11-10 23:30:54', '2025-12-10 22:41:16', 0),
(479, 3, '2d401c157c8ecbdbdcb1f343295533b54959e090a3d5a3df2c5965beb485b310', '2804:389:a11c:e6e3:0:49:47a2:2601', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-10 23:30:54', '2025-11-11 00:20:11', '2025-12-10 23:30:54', 0),
(480, 3, '318450e7719dd494aaba3abb3f6cf59a67ef02b25c2812b742278ba2a90dffa5', '2804:389:a11c:e6e3:0:49:47a2:2601', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 00:20:11', '2025-11-11 01:11:07', '2025-12-11 00:20:11', 0),
(481, 3, '42bb64916875471792c069a60e0210d4c42a7b49f2ed8333d2d63d1075f43434', '186.195.130.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-11 01:11:07', '2025-11-11 10:51:32', '2025-12-11 01:11:07', 0),
(482, 3, '4bde079f3cbf9f9bb1c2faf76604786997f74e6e0ed0c4c30cf2259010c091b1', '186.195.130.212', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 10:51:32', '2025-11-11 11:44:22', '2025-12-11 10:51:32', 0),
(483, 3, '1569fdc4f179fb9fea71cdd9cd0462178ab30954149c8a176e509851e53c7b4f', '186.195.130.212', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 11:44:22', '2025-11-11 13:48:00', '2025-12-11 11:44:22', 0),
(484, 55, 'd403239e4f8d297e7682151d870f456f619f521fe93d8bf9094b3775875535a7', '2804:18:40a2:cdcf:591c:baa1:2faa:d468', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2025-11-11 11:56:29', '2025-11-12 14:27:37', '2025-12-11 11:56:29', 0),
(485, 58, '84eb6e4a2c5787322fdcccc479d1864d073f1ca1426558ca8e6616581a7f1566', '143.208.69.117', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '2025-11-11 13:10:08', '2025-11-11 13:12:23', '2025-12-11 13:10:08', 0),
(486, 58, '27bb91510700fa2875c7943b85e0e8aca6fdbed932e288ff4b9b7481aa3061b0', '143.208.69.117', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '2025-11-11 13:12:23', '2025-11-11 13:16:10', '2025-12-11 13:12:23', 0),
(487, 58, '0f969300c7a68d19043491a05ec3e21c69128df8474f1d555105959c0138c73a', '143.208.69.117', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.7 Mobile/15E148 Safari/604.1', '2025-11-11 13:16:10', '2025-11-11 13:29:03', '2025-12-11 13:16:10', 0),
(488, 58, '7f8930b7287efdbea621e4881fb8c48bcfcdcd23c1186fe7a8b7b5aca85d6c68', '143.208.69.117', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '2025-11-11 13:29:03', '2025-11-11 16:16:26', '2025-12-11 13:29:03', 0),
(489, 3, '8a2ace2cafccc5dd72641ef04b605c610ccd6afd634a0dd33979e9a983cb71db', '2804:389:a1c5:a2d7::f4bf:c101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 13:48:00', '2025-11-11 14:13:15', '2025-12-11 13:48:00', 0),
(490, 3, '5cd058ce5b398ff0d360b5d1b623ff1bdcc75c162d724e5b63968a6408b34cfa', '2804:389:a1c5:a2d7::f4bf:c101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 14:13:15', '2025-11-11 14:24:32', '2025-12-11 14:13:15', 0),
(491, 59, '98c730995db6f504f1da5151d8c9eac5c9f241165495e2e7101f0779ce26a3d6', '2804:2a80:c030:e900:dd25:80c4:4aa8:98c0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:18:23', '2025-11-11 14:24:03', '2025-12-11 14:18:23', 0),
(492, 59, '13502e5f982ce2e3125b0fa2992ceddb80c83873353fa1a771eb7304aca3a063', '2804:2a80:c030:e900:dd25:80c4:4aa8:98c0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:24:03', '2025-11-12 11:08:14', '2025-12-11 14:24:03', 0),
(493, 3, '0500c8568ee6dc364e807db1a260398eff152575f4a404d32d2dae76ee509971', '2804:389:a1c5:a2d7:a5f5:9e7c:627b:5078', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-11 14:24:32', '2025-11-11 15:12:31', '2025-12-11 14:24:32', 0),
(494, 3, 'f317e49e5a44652776f9e36a08d5a19cc9aa6043dee21dd84f221ec4419f630e', '186.195.132.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-11 15:12:31', '2025-11-11 16:29:24', '2025-12-11 15:12:31', 0),
(495, 58, '86c1ced9a217eeaabdd8c0000806555b1b700b44e7486a722ebd3109317a27f8', '143.208.69.117', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '2025-11-11 16:16:26', '2025-11-12 16:17:17', '2025-12-11 16:16:26', 0),
(496, 3, '6d1160b7e69576fa3b05a8639730d5052a50f3cacb9c14f7d3c7815034edaa9d', '186.195.132.240', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 16:29:24', '2025-11-11 16:37:48', '2025-12-11 16:29:24', 0),
(497, 3, '1d706e7b3e7c80cf8c1bbcff73bdea888fbc56162f6f93f392e458c27a5432a5', '2804:389:a1c5:a2d7:196a:1436:2381:1a2c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-11 16:37:48', '2025-11-11 17:57:43', '2025-12-11 16:37:48', 0),
(498, 3, 'd02374d70fd1a3a30c894162ae117744817e31b33f03a419e5f493d087914565', '187.102.28.71', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 17:57:43', '2025-11-11 18:15:58', '2025-12-11 17:57:43', 0),
(499, 3, 'ff46347c1223e513d7666b351d8d30f8a52105ac07f65ff34d2b03e723ee9044', '187.102.28.71', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 18:15:58', '2025-11-11 20:03:44', '2025-12-11 18:15:58', 0),
(500, 3, '035b374eb94bf10fda29a29219f5e4e296f208b8320627d40686fe8922880bf0', '186.195.128.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-11 20:03:44', '2025-11-11 21:32:54', '2025-12-11 20:03:44', 0),
(501, 3, 'f23f1586e9c6d83ab7c1e2c8f7a1f783083efd5563c5c752fb53a322c01ac3d7', '2804:389:a1c5:a2d7::f4bf:c101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 21:32:54', '2025-11-11 22:04:00', '2025-12-11 21:32:54', 0),
(502, 3, '18c65a6cf134f2bb48e724e6f39e44fef399aa3e0aed82b05ff55e9c75e02fe6', '2804:389:a1c5:a2d7::f4bf:c101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 22:04:00', '2025-11-11 23:38:17', '2025-12-11 22:04:00', 0),
(503, 3, 'b51f424980fb120d21eea3e631f6216a2906ed7d9f8dbac69e76662676305fa9', '2804:389:a1c5:a2d7::f4bf:c101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-11 23:38:17', '2025-11-12 11:29:21', '2025-12-11 23:38:17', 0),
(504, 59, 'ecd62041b40709d8aa5fcba541550ae791932e999fac6acf1c432d10679326ae', '2804:2a80:c030:e900:dd25:80c4:4aa8:98c0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 11:08:14', '2025-11-12 12:11:01', '2025-12-12 11:08:14', 0),
(505, 3, '1a10cf0a29ad2e0d222637e4dabd12e668e993858f0e21aad2d2ed6b9f40117a', '2804:389:a13f:f36c:0:5f:d5ae:6d01', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-12 11:29:21', '2025-11-12 11:33:45', '2025-12-12 11:29:21', 0),
(506, 3, '796f6b80537ac3e98f1d6bb10d4a2db093d02f8727073bb49bed7158825ef9cb', '2804:2a80:c032:e700:b518:c923:d792:88d7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-12 11:33:45', '2025-11-12 12:32:12', '2025-12-12 11:33:45', 0),
(507, 59, '9bc11ab1b2de1a6e8314b4e429033627eaa0d90a2ad5db70fdfb32bd681f252d', '2804:2a80:c030:e900:e561:569:498:504c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 12:12:25', '2025-11-12 12:14:29', '2025-12-12 12:12:25', 0),
(508, 59, '360cdc09b5ffd01082d02ad08d58c116964884eb52f35c536425dbda8cd7165d', '2804:2a80:c030:e900:4986:ddb4:e404:144c', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/393.0.825685754 Mobile/15E148 Safari/604.1', '2025-11-12 12:14:29', '2025-11-12 12:15:16', '2025-12-12 12:14:29', 0),
(509, 59, 'f167b7451bf7125503e785a933e590cbd45642577602df206e2480dcf315bdfe', '2804:2a80:c030:e900:e561:569:498:504c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 12:15:16', '2025-11-12 12:21:54', '2025-12-12 12:15:16', 1),
(510, 3, '171dfbddcacee27a5c303080d36d9e2c7d5bd17c1b2de0f6017a15dcf9aa0eee', '2804:389:a13f:f36c:0:5f:d5ae:6d01', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-12 12:32:12', '2025-11-12 13:54:45', '2025-12-12 12:32:12', 0),
(511, 3, '2a07a6fbe7b98097c29bd031db54907169569fdfe2ff0a2e8fa6ffca27320ee8', '2804:389:a13f:f36c:d53d:54a:7253:45b0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-12 13:54:45', '2025-11-12 14:27:28', '2025-12-12 13:54:45', 0),
(512, 3, '6328f6a6aed7dffa32ce933b91e0d83d7c2becd085d77639b09d0d27fe048df9', '186.195.128.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-12 14:27:28', '2025-11-12 16:29:35', '2025-12-12 14:27:28', 0),
(513, 55, '3853ba714ec7fd51aceffbeb3976747b876915ad132ab001cd81af15c4d064ba', '2804:2a80:c04f:1e00:d1ee:7d2e:9ec8:f24d', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2025-11-12 14:27:37', '2025-11-12 15:00:59', '2025-12-12 14:27:37', 1),
(514, 58, '8e1305e2dd68bea7d61f3bec386b90cded24a5ae27a2496eabf7b11b182b960e', '143.208.69.117', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '2025-11-12 16:17:17', '2025-11-12 20:01:59', '2025-12-12 16:17:17', 1),
(515, 3, '626becd9cf6867d4d849349f473e81c0c9df1a3d5b3ed571734412740112cc2c', '186.195.131.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-12 16:29:35', '2025-11-12 17:10:16', '2025-12-12 16:29:35', 0),
(516, 3, 'a734753d78eb34187dbd7c9e48e5a7ff6a3389e2e27d2c43d516c2ac0dd26bdc', '186.195.128.60', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-12 17:10:16', '2025-11-12 17:50:36', '2025-12-12 17:10:16', 0),
(517, 3, '9ce5408196ed1c70d023317c561cea3fc1945889333e9f54c4f30a435068a5ef', '186.195.128.60', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-12 17:50:36', '2025-11-12 19:02:07', '2025-12-12 17:50:36', 0),
(518, 3, '9ffa7c9c95ae5be0eb95d650cc5164193f8d20f182c9dd63ebed87c2e330c7b0', '186.195.128.60', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-12 19:02:07', '2025-11-12 19:26:20', '2025-12-12 19:02:07', 0),
(519, 3, '99e67da586ca533bc1495ed6a435d227e50dc9caa3ad0cc1901faf76457ebafa', '191.39.80.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-12 19:26:20', '2025-11-12 19:31:29', '2025-12-12 19:26:20', 0),
(520, 3, '15bbef382b6c15839845127250c0f68667c8e7b855a11e6943e8f19567803036', '191.39.80.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-12 19:31:29', '2025-11-12 19:37:27', '2025-12-12 19:31:29', 0),
(521, 3, '2bdd971c1f41a955c2e6d9b01514771d782b0027c2f9fa1e4549f1262779d9f9', '191.39.80.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2025-11-12 19:37:27', '2025-11-12 19:39:48', '2025-12-12 19:37:27', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `external_reference` (`external_reference`),
  ADD KEY `idx_ads_user_id` (`user_id`),
  ADD KEY `idx_ads_status` (`status`),
  ADD KEY `idx_ads_slug` (`slug`),
  ADD KEY `idx_ads_verified_badge` (`verified_badge`);

--
-- Índices de tabela `ad_media`
--
ALTER TABLE `ad_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ad_media_ad_id` (`ad_id`);

--
-- Índices de tabela `ad_reports`
--
ALTER TABLE `ad_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ad_reports_ad` (`ad_id`),
  ADD KEY `fk_ad_reports_reporter` (`reporter_user_id`),
  ADD KEY `fk_ad_reports_handler` (`handled_by`),
  ADD KEY `idx_ad_reports_status` (`status`),
  ADD KEY `idx_ad_reports_created` (`created_at`);

--
-- Índices de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_logs_user` (`actor_user_id`),
  ADD KEY `idx_audit_logs_action` (`action`),
  ADD KEY `idx_audit_logs_created_at` (`created_at`);

--
-- Índices de tabela `coin_purchase_orders`
--
ALTER TABLE `coin_purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `external_reference` (`external_reference`),
  ADD KEY `idx_coin_purchase_user` (`user_id`),
  ADD KEY `idx_coin_purchase_status` (`status`),
  ADD KEY `idx_coin_purchase_created` (`created_at`);

--
-- Índices de tabela `coin_transactions`
--
ALTER TABLE `coin_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_external_ref` (`external_reference`),
  ADD KEY `idx_reference_id` (`reference_id`);

--
-- Índices de tabela `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_queue_status_sched` (`status`,`scheduled_at`,`created_at`),
  ADD KEY `idx_email_queue_created` (`created_at`);

--
-- Índices de tabela `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_email_verifications_user` (`user_id`);

--
-- Índices de tabela `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_password_resets_user` (`user_id`);

--
-- Índices de tabela `premium_events_log`
--
ALTER TABLE `premium_events_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_premium_events_log_subscription` (`subscription_id`),
  ADD KEY `idx_premium_events_log_event_type` (`event_type`),
  ADD KEY `idx_premium_events_log_created_at` (`created_at`);

--
-- Índices de tabela `premium_plans`
--
ALTER TABLE `premium_plans`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `premium_subscriptions`
--
ALTER TABLE `premium_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_premium_subscriptions_plan` (`plan_id`),
  ADD KEY `idx_premium_subscriptions_user` (`user_id`);

--
-- Índices de tabela `propagandas_campanhas`
--
ALTER TABLE `propagandas_campanhas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `display_frequency` (`display_frequency`),
  ADD KEY `idx_company_name` (`company_name`);

--
-- Índices de tabela `propaganda_metrics`
--
ALTER TABLE `propaganda_metrics`
  ADD PRIMARY KEY (`campaign_id`);

--
-- Índices de tabela `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rate_limits` (`ip_address`,`route`);

--
-- Índices de tabela `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `idx_remember_tokens_user` (`user_id`),
  ADD KEY `idx_remember_tokens_expires` (`expires_at`);

--
-- Índices de tabela `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sales_user` (`user_id`),
  ADD KEY `idx_sales_ad` (`ad_id`),
  ADD KEY `idx_sales_created` (`created_at`);

--
-- Índices de tabela `saved_searches`
--
ALTER TABLE `saved_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_saved_searches_user` (`user_id`),
  ADD KEY `idx_saved_searches_created` (`created_at`);

--
-- Índices de tabela `search_notifications`
--
ALTER TABLE `search_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_search_ad` (`saved_search_id`,`ad_id`),
  ADD KEY `fk_search_notif_ad` (`ad_id`),
  ADD KEY `idx_search_notif_created` (`notified_at`);

--
-- Índices de tabela `service_custom_suggestions`
--
ALTER TABLE `service_custom_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_usage` (`usage_count`,`last_used_at`);

--
-- Índices de tabela `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Índices de tabela `store_metrics`
--
ALTER TABLE `store_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_store_created` (`store_id`,`created_at`),
  ADD KEY `idx_store_event` (`store_id`,`event_type`),
  ADD KEY `idx_ad_event` (`ad_id`,`event_type`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_users_google_id` (`google_id`),
  ADD KEY `idx_session_token` (`last_session_token`);

--
-- Índices de tabela `user_blacklist`
--
ALTER TABLE `user_blacklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_blacklist_banned_by` (`banned_by`),
  ADD KEY `idx_user_blacklist_email` (`email`),
  ADD KEY `idx_user_blacklist_document` (`document_number`);

--
-- Índices de tabela `user_service_listings`
--
ALTER TABLE `user_service_listings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `external_reference` (`external_reference`),
  ADD KEY `idx_user_service_listings_user` (`user_id`),
  ADD KEY `idx_user_service_listings_status` (`status`),
  ADD KEY `idx_user_service_listings_payment` (`payment_status`),
  ADD KEY `idx_user_service_listings_plan` (`plan_code`),
  ADD KEY `idx_user_service_listings_expires` (`expires_at`),
  ADD KEY `idx_user_service_listings_auto_delete` (`auto_delete_at`);

--
-- Índices de tabela `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_active` (`is_active`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT de tabela `ad_media`
--
ALTER TABLE `ad_media`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=631;

--
-- AUTO_INCREMENT de tabela `ad_reports`
--
ALTER TABLE `ad_reports`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2020;

--
-- AUTO_INCREMENT de tabela `coin_purchase_orders`
--
ALTER TABLE `coin_purchase_orders`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de tabela `coin_transactions`
--
ALTER TABLE `coin_transactions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT de tabela `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=238;

--
-- AUTO_INCREMENT de tabela `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `premium_events_log`
--
ALTER TABLE `premium_events_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `premium_plans`
--
ALTER TABLE `premium_plans`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `premium_subscriptions`
--
ALTER TABLE `premium_subscriptions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `propagandas_campanhas`
--
ALTER TABLE `propagandas_campanhas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de tabela `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=713;

--
-- AUTO_INCREMENT de tabela `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `saved_searches`
--
ALTER TABLE `saved_searches`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `search_notifications`
--
ALTER TABLE `search_notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de tabela `service_custom_suggestions`
--
ALTER TABLE `service_custom_suggestions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=801;

--
-- AUTO_INCREMENT de tabela `store_metrics`
--
ALTER TABLE `store_metrics`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=407;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT de tabela `user_blacklist`
--
ALTER TABLE `user_blacklist`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `user_service_listings`
--
ALTER TABLE `user_service_listings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT de tabela `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=522;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `ads`
--
ALTER TABLE `ads`
  ADD CONSTRAINT `fk_ads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ad_media`
--
ALTER TABLE `ad_media`
  ADD CONSTRAINT `fk_ad_media_ad` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ad_reports`
--
ALTER TABLE `ad_reports`
  ADD CONSTRAINT `fk_ad_reports_ad` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ad_reports_handler` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ad_reports_reporter` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `coin_purchase_orders`
--
ALTER TABLE `coin_purchase_orders`
  ADD CONSTRAINT `fk_coin_purchase_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `coin_transactions`
--
ALTER TABLE `coin_transactions`
  ADD CONSTRAINT `fk_coin_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `premium_events_log`
--
ALTER TABLE `premium_events_log`
  ADD CONSTRAINT `fk_premium_events_log_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `premium_subscriptions` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `premium_subscriptions`
--
ALTER TABLE `premium_subscriptions`
  ADD CONSTRAINT `fk_premium_subscriptions_plan` FOREIGN KEY (`plan_id`) REFERENCES `premium_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_premium_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `fk_remember_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_ad` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `saved_searches`
--
ALTER TABLE `saved_searches`
  ADD CONSTRAINT `fk_saved_searches_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `search_notifications`
--
ALTER TABLE `search_notifications`
  ADD CONSTRAINT `fk_search_notif_ad` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_search_notif_search` FOREIGN KEY (`saved_search_id`) REFERENCES `saved_searches` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_blacklist`
--
ALTER TABLE `user_blacklist`
  ADD CONSTRAINT `fk_user_blacklist_banned_by` FOREIGN KEY (`banned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `user_service_listings`
--
ALTER TABLE `user_service_listings`
  ADD CONSTRAINT `fk_user_service_listings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

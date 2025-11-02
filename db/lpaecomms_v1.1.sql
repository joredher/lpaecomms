-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.0.42 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para lpaecommerce
CREATE DATABASE IF NOT EXISTS `lpaecommerce` /*!40100 DEFAULT CHARACTER SET utf8mb3 */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `lpaecommerce`;

-- Volcando estructura para tabla lpaecommerce.lpa_category
CREATE TABLE IF NOT EXISTS `lpa_category` (
  `lpa_category_ID` int NOT NULL AUTO_INCREMENT,
  `lpa_category_name` varchar(45) NOT NULL,
  `lpa_category_desc` text,
  PRIMARY KEY (`lpa_category_ID`),
  UNIQUE KEY `lpa_category_name_UNIQUE` (`lpa_category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

-- Volcando datos para la tabla lpaecommerce.lpa_category: ~4 rows (aproximadamente)
DELETE FROM `lpa_category`;
INSERT INTO `lpa_category` (`lpa_category_ID`, `lpa_category_name`, `lpa_category_desc`) VALUES
	(1, 'INPUT', 'ALL Input pheripherics hadware'),
	(2, 'OUTPUT', 'ALL Output pheripherics hadware'),
	(3, 'STORAGE', 'Storage devices'),
	(4, 'NETWORK', 'Connections to devices like input or output');

-- Volcando estructura para tabla lpaecommerce.lpa_category_type
CREATE TABLE IF NOT EXISTS `lpa_category_type` (
  `lpa_category_fk_ID` int NOT NULL,
  `lpa_type_fk_ID` int NOT NULL,
  PRIMARY KEY (`lpa_category_fk_ID`,`lpa_type_fk_ID`),
  UNIQUE KEY `fk_category_type_idx` (`lpa_category_fk_ID`,`lpa_type_fk_ID`) /*!80000 INVISIBLE */,
  KEY `fk_category_id_idx` (`lpa_category_fk_ID`) /*!80000 INVISIBLE */,
  KEY `fk_type_id_idx` (`lpa_type_fk_ID`),
  CONSTRAINT `fk_category_id` FOREIGN KEY (`lpa_category_fk_ID`) REFERENCES `lpa_category` (`lpa_category_ID`),
  CONSTRAINT `fk_type_id` FOREIGN KEY (`lpa_type_fk_ID`) REFERENCES `lpa_type` (`lpa_type_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Volcando datos para la tabla lpaecommerce.lpa_category_type: ~8 rows (aproximadamente)
DELETE FROM `lpa_category_type`;
INSERT INTO `lpa_category_type` (`lpa_category_fk_ID`, `lpa_type_fk_ID`) VALUES
	(1, 1),
	(3, 1),
	(4, 1),
	(1, 2),
	(2, 2),
	(2, 3),
	(1, 4),
	(2, 4),
	(3, 4),
	(4, 4);

-- Volcando estructura para tabla lpaecommerce.lpa_clients
CREATE TABLE IF NOT EXISTS `lpa_clients` (
  `lpa_clients_ID` int NOT NULL AUTO_INCREMENT,
  `lpa_clients_firstname` varchar(50) NOT NULL,
  `lpa_clients_lastname` varchar(50) CHARACTER SET armscii8 COLLATE armscii8_general_ci NOT NULL,
  `lpa_client_address` varchar(250) NOT NULL,
  `lpa_client_phone` int NOT NULL,
  `lpa_client_email` varchar(500) NOT NULL,
  `lpa_client_status` char(1) DEFAULT 'A',
  `lpa_clients_fk_user_id` int DEFAULT NULL,
  `lpa_client_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lpa_clients_ID`),
  UNIQUE KEY `lpa_client_email_UNIQUE` (`lpa_client_email`),
  KEY `fk_client_user_id_idx` (`lpa_clients_fk_user_id`),
  CONSTRAINT `fk_client_user_id` FOREIGN KEY (`lpa_clients_fk_user_id`) REFERENCES `lpa_users` (`lpa_users_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Volcando datos para la tabla lpaecommerce.lpa_clients: ~0 rows (aproximadamente)
DELETE FROM `lpa_clients`;

-- Volcando estructura para tabla lpaecommerce.lpa_invoices
CREATE TABLE IF NOT EXISTS `lpa_invoices` (
  `lpa_invoices_ID` int NOT NULL AUTO_INCREMENT,
  `lpa_inv_no` varchar(20) NOT NULL,
  `lpa_inv_date` datetime NOT NULL,
  `lpa_fk_clients_ID` int NOT NULL,
  `lpa_inv_client_name` varchar(50) NOT NULL,
  `lpa_inv_client_address` varchar(250) NOT NULL,
  `lpa_inv_amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `lpa_inv_status` char(1) DEFAULT 'A',
  PRIMARY KEY (`lpa_invoices_ID`),
  UNIQUE KEY `lpa_inv_no_UNIQUE` (`lpa_inv_no`),
  KEY `fk_lpa_invoices_lpa_clients1_idx` (`lpa_fk_clients_ID`),
  CONSTRAINT `fk_lpa_invoices_lpa_clients1` FOREIGN KEY (`lpa_fk_clients_ID`) REFERENCES `lpa_clients` (`lpa_clients_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Volcando datos para la tabla lpaecommerce.lpa_invoices: ~0 rows (aproximadamente)
DELETE FROM `lpa_invoices`;

-- Volcando estructura para tabla lpaecommerce.lpa_invoice_items
CREATE TABLE IF NOT EXISTS `lpa_invoice_items` (
  `lpa_invoice_items_ID` int NOT NULL AUTO_INCREMENT,
  `lpa_fk_invoices_ID` int NOT NULL,
  `lpa_invitem_stock_name` varchar(250) NOT NULL,
  `lpa_fk_stock_ID` bigint NOT NULL,
  `lpa_invitem_qty` int NOT NULL DEFAULT '1',
  `lpa_invitem_stock_price` decimal(7,2) NOT NULL DEFAULT '0.00',
  `lpa_invitem_stock_amount` decimal(7,2) NOT NULL DEFAULT '0.00',
  `lpa_inv_status` char(1) DEFAULT 'A',
  PRIMARY KEY (`lpa_invoice_items_ID`),
  KEY `fk_lpa_invoice_items_lpa_invoices1_idx` (`lpa_fk_invoices_ID`),
  KEY `fk_lpa_invoice_items_lpa_stock1_idx` (`lpa_fk_stock_ID`),
  CONSTRAINT `fk_lpa_invoice_items_lpa_invoices1` FOREIGN KEY (`lpa_fk_invoices_ID`) REFERENCES `lpa_invoices` (`lpa_invoices_ID`),
  CONSTRAINT `fk_lpa_invoice_items_lpa_stock1` FOREIGN KEY (`lpa_fk_stock_ID`) REFERENCES `lpa_stock` (`lpa_stock_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Volcando datos para la tabla lpaecommerce.lpa_invoice_items: ~0 rows (aproximadamente)
DELETE FROM `lpa_invoice_items`;

-- Volcando estructura para tabla lpaecommerce.lpa_stock
CREATE TABLE IF NOT EXISTS `lpa_stock` (
  `lpa_stock_ID` bigint NOT NULL,
  `lpa_stock_name` varchar(250) NOT NULL,
  `lpa_stock_desc` text,
  `lpa_stock_features` text NOT NULL,
  `lpa_stock_onhand` varchar(5) DEFAULT NULL,
  `lpa_stock_price` decimal(7,2) DEFAULT '0.00',
  `lpa_stock_image` longtext,
  `lpa_stock_status` char(1) DEFAULT 'P',
  `lpa_stock_publish_at` datetime DEFAULT NULL,
  `lpa_fk_category_ID` int NOT NULL,
  `lpa_fk_type_ID` int NOT NULL,
  `lpa_invitem_inv_no` varchar(20) NOT NULL,
  PRIMARY KEY (`lpa_stock_ID`),
  KEY `fk_lpa_stock_lpa_category_idx` (`lpa_fk_category_ID`),
  KEY `fk_lpa_stock_lpa_type1_idx` (`lpa_fk_type_ID`),
  CONSTRAINT `fk_lpa_stock_lpa_category` FOREIGN KEY (`lpa_fk_category_ID`) REFERENCES `lpa_category` (`lpa_category_ID`),
  CONSTRAINT `fk_lpa_stock_lpa_type1` FOREIGN KEY (`lpa_fk_type_ID`) REFERENCES `lpa_type` (`lpa_type_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Volcando datos para la tabla lpaecommerce.lpa_stock: ~20 rows (aproximadamente)
DELETE FROM `lpa_stock`;
INSERT INTO `lpa_stock` (`lpa_stock_ID`, `lpa_stock_name`, `lpa_stock_desc`, `lpa_stock_features`, `lpa_stock_onhand`, `lpa_stock_price`, `lpa_stock_image`, `lpa_stock_status`, `lpa_stock_publish_at`, `lpa_fk_category_ID`, `lpa_fk_type_ID`, `lpa_invitem_inv_no`) VALUES
	(1001, 'Wireless Keyboard K580', 'Silent multitasking keyboard with low-profile keys.', '- Wireless connectivity for clutter-free setup. - Low-profile keys for quiet typing. - Compact design ideal for multitasking. - Multi-device switching with easy-access keys. - Long battery life for extended use.', '50', 59.99, 'keyboard1.jpg', 'P', NULL, 1, 1, 'INV-001'),
	(1002, 'Gaming Mouse G502', 'High-performance wired gaming mouse with 11 buttons.', '- High-performance optical sensor for precision. - 11 programmable buttons for gaming macros. - Adjustable DPI settings up to 16000. - Tunable weights for personalized feel. - RGB lighting customization.', '30', 89.99, 'mouse1.jpg', 'P', NULL, 1, 1, 'INV-002'),
	(1003, 'Stylus Pen SP10', 'Precision stylus for tablets and smartphones.', '- Precision tip for accurate input. - Compatible with most capacitive screens. - Slim, lightweight, and ergonomic. - Ideal for drawing, writing, and navigation. - No batteries or Bluetooth required.', '70', 29.99, 'stylus1.jpg', 'P', NULL, 1, 1, 'INV-003'),
	(1004, 'Barcode Scanner BS100', 'Fast laser barcode scanner for retail.', '- Laser scanning for high-speed barcode reading. - Plug-and-play via USB connection. - Durable and ergonomic design. - Ideal for retail and warehouse use. - Compatible with major POS systems.', '40', 119.00, 'scanner1.jpg', 'P', NULL, 1, 1, 'INV-004'),
	(1005, 'Webcam Pro W920', 'HD webcam with autofocus and built-in microphone.', '- Full HD resolution for sharp video. - Autofocus for clear image adjustment. - Built-in noise-reducing microphone. - Flexible mounting clip included. - Plug-and-play with USB.', '25', 79.99, 'webcam1.jpg', 'P', NULL, 1, 1, 'INV-005'),
	(1006, 'LED Monitor 24"', 'Full HD 24-inch LED display.', '- 24-inch Full HD 1080p display. - Slim bezels for immersive viewing. - HDMI and VGA input ports. - LED backlight for energy efficiency. - Tilt adjustable stand.', '35', 149.99, 'monitor1.jpg', 'P', NULL, 2, 3, 'INV-006'),
	(1007, 'Bluetooth Speaker BX50', 'Portable speaker with deep bass.', '- Bluetooth 5.0 connectivity. - Deep bass and rich audio quality. - Up to 10 hours battery life. - Built-in mic for calls. - Compact and portable design.', '45', 89.50, 'speaker1.jpg', 'P', NULL, 2, 3, 'INV-007'),
	(1008, 'Thermal Printer TP20', 'Compact thermal receipt printer.', '- Thermal printing technology. - Fast receipt printing speed. - Compact footprint saves space. - Easy paper loading design. - USB interface for easy setup.', '15', 199.99, 'printer1.jpg', 'P', NULL, 2, 3, 'INV-008'),
	(1009, 'Smart TV 40"', 'Smart TV with built-in apps.', '- 40-inch Full HD Smart TV. - Built-in streaming apps. - Multiple HDMI and USB ports. - Energy-efficient LED panel. - Remote control included.', '10', 349.00, 'tv1.jpg', 'P', NULL, 2, 3, 'INV-009'),
	(1010, 'Audio Dock Station', 'High-fidelity audio docking system.', '- High-fidelity stereo sound. - Multiple device docking support. - Remote control functionality. - USB and AUX input compatibility. - Compact and modern design.', '20', 129.00, 'dock1.jpg', 'P', NULL, 2, 3, 'INV-010'),
	(1011, 'External HDD 2TB', 'Reliable storage for large files.', '- 2TB of external storage capacity. - USB 3.0 high-speed interface. - Shock-resistant housing. - Plug-and-play compatibility. - Ideal for large file backups.', '20', 109.50, 'hdd1.jpg', 'P', NULL, 3, 4, 'INV-011'),
	(1012, 'USB Flash Drive 64GB', 'Portable flash drive.', '- 64GB of portable storage. - Fast data transfer via USB 3.0. - Lightweight and durable. - Plug-and-play with any OS. - Secure and reliable design.', '100', 19.99, 'usb1.jpg', 'P', NULL, 3, 4, 'INV-012'),
	(1013, 'SSD 500GB', 'Fast internal solid-state drive.', '- 500GB SSD storage. - Fast read/write speeds. - Enhanced system performance. - Compact and lightweight. - Ideal for laptops and desktops.', '35', 89.00, 'ssd1.jpg', 'P', NULL, 3, 4, 'INV-013'),
	(1014, 'Memory Card 128GB', 'MicroSDXC memory card.', '- 128GB MicroSDXC capacity. - High-speed data transfer. - Compatible with phones and cameras. - Durable and shockproof. - Includes SD adapter.', '80', 24.99, 'sdcard1.jpg', 'P', NULL, 3, 4, 'INV-014'),
	(1015, 'NAS Storage Unit', 'Network attached storage with 4 bays.', '- Supports 4 hard drive bays. - Reliable NAS for data sharing. - Built-in data redundancy. - Remote access functionality. - Secure user access controls.', '5', 599.99, 'nas1.jpg', 'P', NULL, 3, 4, 'INV-015'),
	(1016, 'Wi-Fi Router AX1800', 'High-speed wireless router.', '- Dual-band AX1800 Wi-Fi. - Fast wireless speed. - Wide coverage and stable signal. - Easy mobile app setup. - Supports multiple devices.', '25', 120.00, 'router1.jpg', 'P', NULL, 4, 1, 'INV-016'),
	(1017, 'Ethernet Switch 8-Port', 'Unmanaged gigabit switch.', '- 8-port Gigabit Ethernet switch. - Unmanaged, plug-and-play setup. - Compact and fanless design. - Energy-efficient operation. - Ideal for small networks.', '30', 59.00, 'switch1.jpg', 'P', NULL, 4, 1, 'INV-017'),
	(1018, 'Wireless Access Point', 'Seamless Wi-Fi expansion device.', '- Boosts existing Wi-Fi coverage. - Supports dual-band connectivity. - Simple setup and management. - Wall-mountable design. - Stable and fast connections.', '18', 139.00, 'accesspoint1.jpg', 'P', NULL, 4, 1, 'INV-018'),
	(1019, 'USB Wi-Fi Adapter', 'Mini wireless USB adapter.', '- Mini USB Wi-Fi adapter. - Stable wireless performance. - Compact and low-profile design. - Plug-and-play installation. - Ideal for laptops and PCs.', '60', 25.00, 'adapter1.jpg', 'P', NULL, 4, 1, 'INV-019'),
	(1020, 'LAN Cable CAT6', 'High-speed Ethernet cable 5m.', '- Category 6 high-speed cable. - Supports up to 1Gbps transfer. - 5-meter length for flexible use. - Durable and reliable material. - Great for routers and modems.', '200', 9.99, 'cable1.jpg', 'P', NULL, 4, 1, 'INV-020');

-- Volcando estructura para tabla lpaecommerce.lpa_type
CREATE TABLE IF NOT EXISTS `lpa_type` (
  `lpa_type_ID` int NOT NULL AUTO_INCREMENT,
  `lpa_type_name` varchar(45) NOT NULL,
  `lpa_type_desc` text,
  PRIMARY KEY (`lpa_type_ID`),
  UNIQUE KEY `lpa_type_name_UNIQUE` (`lpa_type_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

-- Volcando datos para la tabla lpaecommerce.lpa_type: ~4 rows (aproximadamente)
DELETE FROM `lpa_type`;
INSERT INTO `lpa_type` (`lpa_type_ID`, `lpa_type_name`, `lpa_type_desc`) VALUES
	(1, 'Connectivity', 'Devices used to connect other components or networks.'),
	(2, 'Dual-Function', 'Peripherals that work as both input and output devices.'),
	(3, 'Display / Audio', 'Devices that show visual content or produce sound.'),
	(4, 'All', 'Includes all types. Shows every available peripheral.');

-- Volcando estructura para tabla lpaecommerce.lpa_users
CREATE TABLE IF NOT EXISTS `lpa_users` (
  `lpa_users_ID` int NOT NULL AUTO_INCREMENT,
  `lpa_user_username` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `lpa_user_email` varchar(500) NOT NULL,
  `lpa_user_password` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `lpa_user_firstname` varchar(50) NOT NULL,
  `lpa_user_lastname` varchar(50) NOT NULL,
  `lpa_fk_user_group_ID` int NOT NULL,
  `validation_token` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `token_created_at` datetime DEFAULT NULL,
  `lpa_user_status` char(1) DEFAULT 'A',
  `is_verified` tinyint DEFAULT '0',
  PRIMARY KEY (`lpa_users_ID`),
  UNIQUE KEY `lpa_user_username_UNIQUE` (`lpa_user_username`),
  UNIQUE KEY `lpa_user_email_UNIQUE` (`lpa_user_email`),
  KEY `fk_lpa_users_lpa_user_group1_idx` (`lpa_fk_user_group_ID`),
  CONSTRAINT `fk_lpa_users_lpa_user_group1` FOREIGN KEY (`lpa_fk_user_group_ID`) REFERENCES `lpa_user_group` (`lpa_user_group_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3;

-- Volcando datos para la tabla lpaecommerce.lpa_users: ~3 rows (aproximadamente)
DELETE FROM `lpa_users`;
INSERT INTO `lpa_users` (`lpa_users_ID`, `lpa_user_username`, `lpa_user_email`, `lpa_user_password`, `lpa_user_firstname`, `lpa_user_lastname`, `lpa_fk_user_group_ID`, `validation_token`, `token_created_at`, `lpa_user_status`, `is_verified`) VALUES
	(15, 'joredher302025', 'joredher30@gmail.com', '$2y$10$CDVFVjngnIUh5NF/JzRBX.OGFAVuVcXGDiKeHsE2oNptNpL7d.Fz6', 'Jorge', 'Hernandez', 2, '', NULL, 'A', 1),
	(16, 'ben2025', 'ben@mack.com', '$2y$10$N58VFBk/mOzEHbk8a2cJZu8G4Al75ZZ.q3E1r9Ksa89.N0WSr8uN6', 'Ben', 'Mack', 2, '', NULL, 'A', 1),
	(17, 'maria2025', 'maria@pajona.com', '$2y$10$St2hWTHNnB8bx2Gi1NM8Q.tIFKlU3tmeM2zZa6RyZBnAji1oba.sm', 'Maria', 'Pajona', 2, '', '2025-06-02 07:36:08', 'A', 1),
	(18, 'msossa692025', 'msossa69@hotmail.com', '$2y$10$1uwMiI.By8XtwOHVISVQReuINXmIIpoKiXyKAV3crCqBFT5VgAfgS', 'Mildred', 'Sossa', 2, '', '2025-06-02 08:09:30', 'A', 1),
	(19, 'lucia2025', 'lucia@mail.com', '$2y$10$QbdGarU5JIJTb/bojCrBhew8Ppu6rzMal.hPh/dyh9vzqvbvyuIt.', 'Lucia', 'Sossa', 2, '', '2025-06-06 11:39:58', 'A', 1);

-- Volcando estructura para tabla lpaecommerce.lpa_user_group
CREATE TABLE IF NOT EXISTS `lpa_user_group` (
  `lpa_user_group_ID` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY (`lpa_user_group_ID`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

-- Volcando datos para la tabla lpaecommerce.lpa_user_group: ~2 rows (aproximadamente)
DELETE FROM `lpa_user_group`;
INSERT INTO `lpa_user_group` (`lpa_user_group_ID`, `name`) VALUES
	(1, 'Admin'),
	(2, 'Client');

-- Volcando estructura para tabla lpaecommerce.lpa_verification_tokens
CREATE TABLE IF NOT EXISTS `lpa_verification_tokens` (
  `lpa_verification_token_ID` int NOT NULL AUTO_INCREMENT,
  `lpa_users_ID` int NOT NULL DEFAULT '0',
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lpa_verification_token_ID`),
  KEY `FK_USER_ID` (`lpa_users_ID`),
  CONSTRAINT `FK_USER_ID` FOREIGN KEY (`lpa_users_ID`) REFERENCES `lpa_users` (`lpa_users_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COMMENT='This table was created to give a flow during the verification process once the user got a registration.';

-- Volcando datos para la tabla lpaecommerce.lpa_verification_tokens: ~0 rows (aproximadamente)
DELETE FROM `lpa_verification_tokens`;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

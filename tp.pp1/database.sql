-- ============================================================
-- DeliStock - Base de Datos (Sistema de Gestión para Fiambrería)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `delistock` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `delistock`;

-- 1. Tabla Administrador
CREATE TABLE IF NOT EXISTS `administrador` (
    `idAdministrador` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `usuario` VARCHAR(50) NOT NULL UNIQUE,
    `contrasena` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla Producto
CREATE TABLE IF NOT EXISTS `producto` (
    `idProducto` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL UNIQUE,
    `precio` DECIMAL(10,2) NOT NULL,
    `stock` DECIMAL(10,3) NOT NULL DEFAULT 0.000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla Venta
CREATE TABLE IF NOT EXISTS `venta` (
    `idVenta` INT AUTO_INCREMENT PRIMARY KEY,
    `idAdministrador` INT NOT NULL,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_venta_administrador` FOREIGN KEY (`idAdministrador`) REFERENCES `administrador`(`idAdministrador`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla DetalleVenta
CREATE TABLE IF NOT EXISTS `detalle_venta` (
    `idDetalleVenta` INT AUTO_INCREMENT PRIMARY KEY,
    `idVenta` INT NOT NULL,
    `idProducto` INT NOT NULL,
    `cantidad` DECIMAL(10,3) NOT NULL,
    `precioUnitario` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_detalle_venta` FOREIGN KEY (`idVenta`) REFERENCES `venta`(`idVenta`) ON DELETE CASCADE,
    CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`idProducto`) REFERENCES `producto`(`idProducto`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Datos de prueba iniciales
-- ------------------------------------------------------------

-- Usuario Administrador por defecto (usuario: admin / pass: admin123)
INSERT INTO `administrador` (`idAdministrador`, `nombre`, `usuario`, `contrasena`)
VALUES (1, 'Administrador DeliStock', 'admin', 'admin123')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Productos típicos de fiambrería
INSERT INTO `producto` (`idProducto`, `nombre`, `precio`, `stock`) VALUES
(1, 'Jamón Cocido Natural (kg)', 8500.00, 25),
(2, 'Queso Tybo en Barra (kg)', 7200.00, 30),
(3, 'Salame Milán Fino (unidad)', 4500.00, 18),
(4, 'Jamón Crudo Estacionado (kg)', 14200.00, 12),
(5, 'Queso Azul Especial (kg)', 12800.00, 8),
(6, 'Mortadela con Pistacho (kg)', 6400.00, 15),
(7, 'Panceta Ahumada Artesanal (kg)', 11500.00, 10),
(8, 'Queso Danbo Criollo (kg)', 7800.00, 22),
(9, 'Leche Entera (botella 1L)', 1400.00, 30),
(10, 'Tapas de Empanadas (paquete x12)', 1800.00, 20),
(11, 'Harina 000 (paquete 1 kg)', 1200.00, 25)
ON DUPLICATE KEY UPDATE `precio` = VALUES(`precio`), `stock` = VALUES(`stock`);

-- =========================================================================
-- NATURISTA DB - TABLAS ADICIONALES Y POBLADO DE DATOS REALES (SRI ECUADOR)
-- =========================================================================

-- 1. TABLA DE TURNOS DE CAJA
CREATE TABLE IF NOT EXISTS `caja_turnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `punto_emision_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_apertura` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `monto_inicial` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ventas_efectivo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ventas_tarjetas` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ventas_transferencia` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_ventas` decimal(12,2) NOT NULL DEFAULT 0.00,
  `efectivo_esperado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `efectivo_real` decimal(12,2) DEFAULT NULL,
  `diferencia` decimal(12,2) DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_caja_usuario` (`usuario_id`),
  KEY `fk_caja_punto` (`punto_emision_id`),
  CONSTRAINT `fk_caja_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_usuario` (`id`),
  CONSTRAINT `fk_caja_punto` FOREIGN KEY (`punto_emision_id`) REFERENCES `configuracion_puntos_emision` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. TABLA DE PROVEEDORES
CREATE TABLE IF NOT EXISTS `compras_proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_identificacion` varchar(2) NOT NULL DEFAULT '04',
  `identificacion` varchar(20) NOT NULL,
  `razon_social` varchar(300) NOT NULL,
  `direccion` varchar(300) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identificacion` (`identificacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. TABLA DE COMPRAS A PROVEEDORES
CREATE TABLE IF NOT EXISTS `compras_facturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` int(11) NOT NULL,
  `bodega_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `movimiento_id` int(11) DEFAULT NULL,
  `numero_factura` varchar(50) NOT NULL,
  `fecha_emision` date NOT NULL,
  `subtotal_sin_impuestos` decimal(12,2) NOT NULL DEFAULT 0.00,
  `iva` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_compras_proveedor` (`proveedor_id`),
  KEY `fk_compras_bodega` (`bodega_id`),
  KEY `fk_compras_usuario` (`usuario_id`),
  CONSTRAINT `fk_compras_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `compras_proveedores` (`id`),
  CONSTRAINT `fk_compras_bodega` FOREIGN KEY (`bodega_id`) REFERENCES `inventario_bodegas` (`id`),
  CONSTRAINT `fk_compras_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. TABLA DE DETALLES DE COMPRA
CREATE TABLE IF NOT EXISTS `compras_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compra_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` decimal(12,4) NOT NULL,
  `costo_unitario` decimal(12,4) NOT NULL,
  `costo_total` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_detalles_compra` (`compra_id`),
  KEY `fk_detalles_producto` (`producto_id`),
  CONSTRAINT `fk_detalles_compra` FOREIGN KEY (`compra_id`) REFERENCES `compras_facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalles_producto` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. TABLAS ESTÁNDAR LARAVEL (SESSIONS, CACHE, JOBS)
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- POBLADO DE DATOS BASE (SEEDERS)
-- =========================================================================

-- 1. EMISOR PRINCIPAL
INSERT INTO `configuracion_emisor` (`id`, `ruc`, `razon_social`, `nombre_comercial`, `direccion_matriz`, `direccion_establecimiento`, `contribuyente_especial`, `obligado_contabilidad`, `agente_retencion`, `regimen_rimpe`, `firma_electronica_ruta`, `firma_electronica_clave`, `ambiente_sri`, `proveedor_sistema_ruc`, `moneda`)
VALUES (1, '1792948201001', 'NATURISTA EXPRESS CIA. LTDA.', 'NATURISTA EXPRESS', 'Av. 10 de Agosto N24-150 y Colón, Quito - Ecuador', 'Av. 10 de Agosto N24-150 y Colón', NULL, 'SI', NULL, 'CONTRIBUYENTE RÉGIMEN RIMPE', 'storage/firmas/firma_naturista.p12', 'Naturista2026*', 1, '1792948201001', 'DOLAR')
ON DUPLICATE KEY UPDATE `razon_social` = VALUES(`razon_social`);

-- 2. PUNTO DE EMISIÓN 001-001
INSERT INTO `configuracion_puntos_emision` (`id`, `emisor_id`, `establecimiento`, `punto_emision`, `secuencial_factura`, `secuencial_nota_credito`, `secuencial_guia_remision`, `secuencial_retencion`, `secuencial_liquidacion_compra`, `estado`)
VALUES (1, 1, '001', '001', 186, 1, 1, 1, 1, 'ACTIVO')
ON DUPLICATE KEY UPDATE `secuencial_factura` = VALUES(`secuencial_factura`);

-- 3. ROLES DE USUARIO
INSERT INTO `usuarios_roles` (`id`, `nombre`, `permisos_json`) VALUES
(1, 'Administrador', '{"all": true}'),
(2, 'Cajero', '{"pos": true, "caja": true, "clientes": true}')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 4. USUARIOS / CAJEROS
INSERT INTO `usuarios_usuario` (`id`, `rol_id`, `punto_emision_id`, `identificacion`, `nombres`, `apellidos`, `correo`, `password_hash`, `estado`)
VALUES
(1, 1, 1, '1712345678', 'Juan Carlos', 'Pérez Morales', 'juan.perez@naturista.com', '$2y$12$eImiTXuWVxfM37uY4JANjOL.oDRrpxlq6X4.1LGB8m4Jp1VzQzN9m', 'ACTIVO')
ON DUPLICATE KEY UPDATE `nombres` = VALUES(`nombres`);

-- 5. BODEGAS
INSERT INTO `inventario_bodegas` (`id`, `nombre`, `ubicacion`) VALUES
(1, 'Bodega Principal (Local Centro)', 'Av. 10 de Agosto N24-150'),
(2, 'Bodega de Reserva (Almacén Norte)', 'Av. Galo Plaza Lasso N58')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 6. CATEGORÍAS
INSERT INTO `inventario_categorias` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Suplementos', 'Proteínas, colágenos, creatinas y suplementos dietéticos'),
(2, 'Vitaminas', 'Complejo B, Vitamina C, D3, Multivitamínicos'),
(3, 'Jarabes', 'Jarabes naturales expectorantes y digestivos'),
(4, 'Naturales', 'Miel pura, propóleo, polen y extractos botánicos'),
(5, 'Cuidado Personal', 'Jabones artesanales, aceites esenciales y cremas'),
(6, 'Infusiones', 'Tés herbales, té verde y aromáticas')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 7. PRODUCTOS DE INVENTARIO
INSERT INTO `inventario_productos` (`id`, `categoria_id`, `codigo_principal`, `codigo_auxiliar`, `nombre`, `tipo_producto`, `precio_unitario`, `costo_promedio`, `codigo_iva`, `codigo_ice`, `tiene_irbpnr`, `estado`) VALUES
(1, 1, 'PROD-001', 'COLAG-500', 'Colágeno Hidrolizado 500g', 'BIEN', 25.0000, 15.0000, '2', NULL, 0, 'ACTIVO'),
(2, 2, 'PROD-002', 'VITC-1000', 'Vitamina C 1000mg x 60 Tabletas', 'BIEN', 14.5000, 8.5000, '2', NULL, 0, 'ACTIVO'),
(3, 3, 'PROD-003', 'JRTOT-250', 'Jarabe de Totumo & Eucalipto 250ml', 'BIEN', 8.0000, 4.2000, '0', NULL, 0, 'ACTIVO'),
(4, 4, 'PROD-004', 'MIEL-1KG', 'Miel de Abeja Orgánica Pura 1Kg', 'BIEN', 12.0000, 7.0000, '0', NULL, 0, 'ACTIVO'),
(5, 1, 'PROD-005', 'OMEG-100', 'Omega 3 Concentrado x 100 Softgels', 'BIEN', 28.9000, 16.5000, '2', NULL, 0, 'ACTIVO'),
(6, 5, 'PROD-006', 'JABSAB-100', 'Jabón Artesanal de Sábila & Caléndula', 'BIEN', 4.5000, 2.1000, '2', NULL, 0, 'ACTIVO'),
(7, 6, 'PROD-007', 'TEVRD-30', 'Té Verde Orgánico x 30 Bolsitas', 'BIEN', 5.2500, 2.8000, '0', NULL, 0, 'ACTIVO'),
(8, 5, 'PROD-008', 'ALC-500', 'Alcohol Antiséptico 70% 500ml', 'BIEN', 3.5000, 1.8000, '2', NULL, 0, 'ACTIVO')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `precio_unitario` = VALUES(`precio_unitario`);

-- 8. STOCK EN BODEGA PRINCIPAL (`inventario_general`)
INSERT INTO `inventario_general` (`bodega_id`, `producto_id`, `stock_actual`, `stock_minimo`, `stock_maximo`) VALUES
(1, 1, 35.0000, 5.0000, 100.0000),
(1, 2, 50.0000, 10.0000, 150.0000),
(1, 3, 4.0000, 10.0000, 50.0000),
(1, 4, 20.0000, 5.0000, 60.0000),
(1, 5, 40.0000, 8.0000, 100.0000),
(1, 6, 60.0000, 15.0000, 200.0000),
(1, 7, 25.0000, 10.0000, 80.0000),
(1, 8, 0.0000, 10.0000, 100.0000)
ON DUPLICATE KEY UPDATE `stock_actual` = VALUES(`stock_actual`);

-- 9. CLIENTES REGISTRADOS
INSERT INTO `clientes_cliente` (`id`, `tipo_identificacion`, `identificacion`, `razon_social`, `direccion`, `telefono`, `correo`, `obligado_contabilidad`) VALUES
(1, '07', '9999999999999', 'CONSUMIDOR FINAL', 'Quito', '9999999999', 'ventas@naturista.com', 'NO'),
(2, '05', '1723456789', 'María Fernanda López', 'Av. Naciones Unidas y Shyris', '0991234567', 'mflopez@correo.com', 'NO'),
(3, '04', '1792345678001', 'Distribuidora Naturista S.A.', 'Parque Industrial Sur, Lote 12', '022345678', 'compras@distrinaturista.com', 'SI'),
(4, '05', '1712345678', 'Juan Carlos Pérez', 'Av. América y Mariana de Jesús', '0987654321', 'jcperez@correo.com', 'NO')
ON DUPLICATE KEY UPDATE `razon_social` = VALUES(`razon_social`);

-- 10. PROVEEDORES
INSERT INTO `compras_proveedores` (`id`, `tipo_identificacion`, `identificacion`, `razon_social`, `direccion`, `telefono`, `correo`) VALUES
(1, '04', '1790012486001', 'Laboratorios NaturalMed S.A.', 'Av. Panamericana Norte Km 12', '022987654', 'ventas@naturalmed.ec'),
(2, '04', '1791122334001', 'BioFlora Ecuador Cía. Ltda.', 'Parque Empresarial San Isidro', '023456789', 'pedidos@bioflora.ec'),
(3, '04', '1798765432001', 'Apícola Los Andes Cía.', 'Valle de los Chillos, Sangolquí', '0998877665', 'contacto@apicolalosandes.com')
ON DUPLICATE KEY UPDATE `razon_social` = VALUES(`razon_social`);

-- 11. TURNO DE CAJA ACTIVO DE HOY
INSERT INTO `caja_turnos` (`id`, `punto_emision_id`, `usuario_id`, `fecha_apertura`, `monto_inicial`, `ventas_efectivo`, `ventas_tarjetas`, `ventas_transferencia`, `total_ventas`, `efectivo_esperado`, `estado`, `observaciones`)
VALUES
(1, 1, 1, NOW(), 50.00, 18.50, 42.00, 125.00, 185.50, 68.50, 'ABIERTA', 'Apertura de turno matutino sin novedades')
ON DUPLICATE KEY UPDATE `total_ventas` = VALUES(`total_ventas`);

-- 12. COMPRAS REGISTRADAS
INSERT INTO `compras_facturas` (`id`, `proveedor_id`, `bodega_id`, `usuario_id`, `numero_factura`, `fecha_emision`, `subtotal_sin_impuestos`, `iva`, `total`, `observaciones`) VALUES
(1, 1, 1, 1, '002-005-00012486', '2026-09-14', 450.00, 67.50, 517.50, 'Reposición mensual de vitaminas y suplementos'),
(2, 2, 1, 1, '001-002-00008451', '2026-09-12', 280.00, 0.00, 280.00, 'Ingreso de jarabes y miel orgánica')
ON DUPLICATE KEY UPDATE `total` = VALUES(`total`);

-- 13. FACTURAS EMITIDAS DE MUESTRA
INSERT INTO `ventas_facturas` (`id`, `emisor_id`, `punto_emision_id`, `cliente_id`, `usuario_id`, `ambiente`, `tipo_emision`, `codigo_documento`, `establecimiento`, `punto_emision`, `secuencial`, `clave_acceso`, `fecha_emision`, `total_sin_impuestos`, `total_descuento`, `base_imponible_0`, `base_imponible_iva`, `valor_iva`, `importe_total`, `estado_sri`, `fecha_autorizacion`, `numero_autorizacion`) VALUES
(1, 1, 1, 1, 1, 1, 1, '01', '001', '001', '000000185', '1609202601179294820100110010010000001851234567819', CURDATE(), 16.09, 0.00, 0.00, 16.09, 2.41, 18.50, 'AUTORIZADO', NOW(), '1609202601179294820100110010010000001851234567819'),
(2, 1, 1, 2, 1, 1, 1, '01', '001', '001', '000000184', '1609202601179294820100110010010000001841234567818', CURDATE(), 36.52, 0.00, 0.00, 36.52, 5.48, 42.00, 'AUTORIZADO', NOW(), '1609202601179294820100110010010000001841234567818'),
(3, 1, 1, 4, 1, 1, 1, '01', '001', '001', '000000183', '1609202601179294820100110010010000001831234567817', CURDATE(), 108.70, 0.00, 0.00, 108.70, 16.30, 125.00, 'AUTORIZADO', NOW(), '1609202601179294820100110010010000001831234567817')
ON DUPLICATE KEY UPDATE `importe_total` = VALUES(`importe_total`);

-- 14. PAGOS DE FACTURA
INSERT INTO `ventas_pagos_factura` (`id`, `factura_id`, `metodo_pago_id`, `total`, `plazo`, `unidad_tiempo`) VALUES
(1, 1, 1, 18.50, 0, 'DIAS'),
(2, 2, 3, 42.00, 0, 'DIAS'),
(3, 3, 7, 125.00, 0, 'DIAS')
ON DUPLICATE KEY UPDATE `total` = VALUES(`total`);

-- 15. MOVIMIENTOS KARDEX INICIALES
INSERT INTO `inventario_movimientos` (`id`, `bodega_id`, `tipo_movimiento_id`, `usuario_id`, `fecha_movimiento`, `referencia`, `observaciones`) VALUES
(1, 1, 1, 1, '2026-09-14 10:00:00', '002-005-00012486', 'Compra a Laboratorios NaturalMed'),
(2, 1, 2, 1, '2026-09-16 11:40:00', '001-001-000000183', 'Venta Factura #183'),
(3, 1, 2, 1, '2026-09-16 13:15:00', '001-001-000000184', 'Venta Factura #184'),
(4, 1, 2, 1, '2026-09-16 14:32:00', '001-001-000000185', 'Venta Factura #185')
ON DUPLICATE KEY UPDATE `referencia` = VALUES(`referencia`);

INSERT INTO `inventario_movimientos_detalles` (`id`, `movimiento_id`, `producto_id`, `cantidad`, `costo_unitario`, `costo_total`) VALUES
(1, 1, 1, 20.0000, 15.0000, 300.0000),
(2, 2, 1, 5.0000, 15.0000, 75.0000),
(3, 3, 2, 2.0000, 8.5000, 17.0000),
(4, 4, 3, 1.0000, 4.2000, 4.2000)
ON DUPLICATE KEY UPDATE `cantidad` = VALUES(`cantidad`);

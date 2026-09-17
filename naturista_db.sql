-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-09-2026 a las 01:17:53
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.5.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `naturista_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes_cliente`
--

CREATE TABLE `clientes_cliente` (
  `id` int(11) NOT NULL,
  `tipo_identificacion` varchar(2) NOT NULL COMMENT 'Catálogo SRI: 04=RUC, 05=Cédula, 06=Pasaporte, 07=Consumidor Final, 08=Id. Exterior',
  `identificacion` varchar(20) NOT NULL,
  `razon_social` varchar(300) NOT NULL,
  `direccion` varchar(300) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `obligado_contabilidad` enum('SI','NO') DEFAULT 'NO',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_emisor`
--

CREATE TABLE `configuracion_emisor` (
  `id` int(11) NOT NULL,
  `ruc` varchar(13) NOT NULL,
  `razon_social` varchar(300) NOT NULL,
  `nombre_comercial` varchar(300) DEFAULT NULL,
  `direccion_matriz` varchar(300) NOT NULL,
  `direccion_establecimiento` varchar(300) DEFAULT NULL,
  `contribuyente_especial` varchar(20) DEFAULT NULL COMMENT 'Número de resolución del SRI, si aplica',
  `obligado_contabilidad` enum('SI','NO') NOT NULL DEFAULT 'NO',
  `agente_retencion` varchar(20) DEFAULT NULL COMMENT 'Número de resolución como agente de retención',
  `regimen_rimpe` enum('CONTRIBUYENTE RÉGIMEN RIMPE','CONTRIBUYENTE NEGOCIO POPULAR - RÉGIMEN RIMPE','NO APLICA') NOT NULL DEFAULT 'NO APLICA',
  `firma_electronica_ruta` varchar(500) DEFAULT NULL COMMENT 'Ruta física, nombre del archivo p12 o URL de la firma',
  `firma_electronica_clave` varchar(255) DEFAULT NULL COMMENT 'Contraseña de la firma electrónica',
  `ambiente_sri` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Pruebas, 2 = Producción',
  `proveedor_sistema_ruc` varchar(13) DEFAULT NULL COMMENT 'RUC del proveedor del sistema facturador (para auditorías SRI)',
  `moneda` varchar(15) DEFAULT 'DOLAR',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_metodos_pago`
--

CREATE TABLE `configuracion_metodos_pago` (
  `id` int(11) NOT NULL,
  `codigo_sri` varchar(2) NOT NULL COMMENT 'Código asignado por el SRI',
  `nombre` varchar(150) NOT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_metodos_pago`
--

INSERT INTO `configuracion_metodos_pago` (`id`, `codigo_sri`, `nombre`, `estado`) VALUES
(1, '01', 'SIN UTILIZACION DEL SISTEMA FINANCIERO', 'ACTIVO'),
(2, '15', 'COMPENSACIÓN DE DEUDAS', 'ACTIVO'),
(3, '16', 'TARJETA DE DÉBITO', 'ACTIVO'),
(4, '17', 'DINERO ELECTRÓNICO', 'ACTIVO'),
(5, '18', 'TARJETA PREPAGO', 'ACTIVO'),
(6, '19', 'TARJETA DE CRÉDITO', 'ACTIVO'),
(7, '20', 'OTROS CON UTILIZACION DEL SISTEMA FINANCIERO', 'ACTIVO'),
(8, '21', 'ENDOSO DE TÍTULOS', 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_puntos_emision`
--

CREATE TABLE `configuracion_puntos_emision` (
  `id` int(11) NOT NULL,
  `emisor_id` int(11) NOT NULL,
  `establecimiento` varchar(3) NOT NULL COMMENT 'Ej: 001',
  `punto_emision` varchar(3) NOT NULL COMMENT 'Ej: 001',
  `secuencial_factura` int(11) NOT NULL DEFAULT 1,
  `secuencial_nota_credito` int(11) NOT NULL DEFAULT 1,
  `secuencial_guia_remision` int(11) NOT NULL DEFAULT 1,
  `secuencial_retencion` int(11) NOT NULL DEFAULT 1,
  `secuencial_liquidacion_compra` int(11) NOT NULL DEFAULT 1,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_bodegas`
--

CREATE TABLE `inventario_bodegas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `ubicacion` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_categorias`
--

CREATE TABLE `inventario_categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_general`
--

CREATE TABLE `inventario_general` (
  `id` int(11) NOT NULL,
  `bodega_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `stock_actual` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `stock_minimo` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `stock_maximo` decimal(12,4) DEFAULT NULL,
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_movimientos`
--

CREATE TABLE `inventario_movimientos` (
  `id` int(11) NOT NULL,
  `bodega_id` int(11) NOT NULL,
  `tipo_movimiento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_movimiento` datetime NOT NULL DEFAULT current_timestamp(),
  `referencia` varchar(100) DEFAULT NULL COMMENT 'Ej: ID de Factura, Guía de Remisión o Ajuste',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_movimientos_detalles`
--

CREATE TABLE `inventario_movimientos_detalles` (
  `id` int(11) NOT NULL,
  `movimiento_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` decimal(12,4) NOT NULL,
  `costo_unitario` decimal(12,4) NOT NULL,
  `costo_total` decimal(12,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_productos`
--

CREATE TABLE `inventario_productos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `codigo_principal` varchar(25) NOT NULL,
  `codigo_auxiliar` varchar(25) DEFAULT NULL,
  `nombre` varchar(300) NOT NULL,
  `tipo_producto` enum('BIEN','SERVICIO') DEFAULT 'BIEN',
  `precio_unitario` decimal(12,4) NOT NULL COMMENT 'Hasta 4 decimales requeridos en algunos rubros',
  `costo_promedio` decimal(12,4) DEFAULT 0.0000,
  `codigo_iva` varchar(2) NOT NULL DEFAULT '2',
  `codigo_ice` varchar(4) DEFAULT NULL COMMENT 'Código de ICE SRI si aplica',
  `tiene_irbpnr` tinyint(1) DEFAULT 0 COMMENT 'Impuesto a botellas plásticas (SI/NO)',
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_tipos_movimiento`
--

CREATE TABLE `inventario_tipos_movimiento` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `naturaleza` enum('INGRESO','EGRESO') NOT NULL,
  `factor` int(11) NOT NULL COMMENT '1 para sumar stock, -1 para restar stock',
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario_tipos_movimiento`
--

INSERT INTO `inventario_tipos_movimiento` (`id`, `nombre`, `naturaleza`, `factor`, `descripcion`) VALUES
(1, 'COMPRA', 'INGRESO', 1, NULL),
(2, 'VENTA', 'EGRESO', -1, NULL),
(3, 'DEVOLUCIÓN EN VENTA', 'INGRESO', 1, NULL),
(4, 'DEVOLUCIÓN EN COMPRA', 'EGRESO', -1, NULL),
(5, 'AJUSTE INGRESO', 'INGRESO', 1, NULL),
(6, 'AJUSTE EGRESO', 'EGRESO', -1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_roles`
--

CREATE TABLE `usuarios_roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT 'Ej: Administrador, Cajero, Bodeguero',
  `permisos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Permite manejar permisos granulares si se requiere' CHECK (json_valid(`permisos_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_usuario`
--

CREATE TABLE `usuarios_usuario` (
  `id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `punto_emision_id` int(11) DEFAULT NULL COMMENT 'Asignación directa a una caja/punto de emisión',
  `identificacion` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_detalles_factura`
--

CREATE TABLE `ventas_detalles_factura` (
  `id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `codigo_principal` varchar(25) NOT NULL,
  `codigo_auxiliar` varchar(25) DEFAULT NULL,
  `descripcion` varchar(300) NOT NULL,
  `cantidad` decimal(12,4) NOT NULL,
  `precio_unitario` decimal(12,4) NOT NULL,
  `descuento` decimal(12,2) NOT NULL DEFAULT 0.00,
  `precio_total_sin_impuestos` decimal(12,2) NOT NULL,
  `codigo_impuesto_iva` varchar(2) NOT NULL COMMENT 'Código SRI. Ej: 2, 4',
  `tarifa_iva` decimal(5,2) NOT NULL COMMENT 'Porcentaje. Ej: 12.00, 15.00',
  `base_imponible_iva` decimal(12,2) NOT NULL,
  `valor_iva` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_detalles_nc`
--

CREATE TABLE `ventas_detalles_nc` (
  `id` int(11) NOT NULL,
  `nota_credito_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `codigo_interno` varchar(25) NOT NULL,
  `descripcion` varchar(300) NOT NULL,
  `cantidad` decimal(12,4) NOT NULL,
  `precio_unitario` decimal(12,4) NOT NULL,
  `descuento` decimal(12,2) NOT NULL DEFAULT 0.00,
  `precio_total_sin_impuestos` decimal(12,2) NOT NULL,
  `codigo_impuesto_iva` varchar(2) NOT NULL,
  `tarifa_iva` decimal(5,2) NOT NULL,
  `base_imponible_iva` decimal(12,2) NOT NULL,
  `valor_iva` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_facturas`
--

CREATE TABLE `ventas_facturas` (
  `id` int(11) NOT NULL,
  `emisor_id` int(11) NOT NULL,
  `punto_emision_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Cajero que emite la factura',
  `ambiente` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Pruebas, 2=Producción',
  `tipo_emision` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Emisión Normal',
  `codigo_documento` varchar(2) NOT NULL DEFAULT '01' COMMENT '01=Factura',
  `establecimiento` varchar(3) NOT NULL,
  `punto_emision` varchar(3) NOT NULL,
  `secuencial` varchar(9) NOT NULL,
  `numero_documento` varchar(17) GENERATED ALWAYS AS (concat(`establecimiento`,'-',`punto_emision`,'-',`secuencial`)) VIRTUAL,
  `clave_acceso` varchar(49) DEFAULT NULL COMMENT 'Clave de 49 dígitos requerida por el SRI',
  `fecha_emision` date NOT NULL,
  `total_sin_impuestos` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_descuento` decimal(12,2) NOT NULL DEFAULT 0.00,
  `base_imponible_0` decimal(12,2) NOT NULL DEFAULT 0.00,
  `base_imponible_iva` decimal(12,2) NOT NULL DEFAULT 0.00,
  `base_no_objeto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `base_exento` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_iva` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_ice` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_irbpnr` decimal(12,2) NOT NULL DEFAULT 0.00,
  `propina` decimal(12,2) NOT NULL DEFAULT 0.00,
  `importe_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `moneda` varchar(15) DEFAULT 'DOLAR',
  `guia_remision` varchar(17) DEFAULT NULL,
  `estado_sri` enum('CREADO','FIRMADO','ENVIADO','AUTORIZADO','RECHAZADO','DEVUELTO','ANULADO') DEFAULT 'CREADO',
  `fecha_autorizacion` datetime DEFAULT NULL,
  `numero_autorizacion` varchar(49) DEFAULT NULL COMMENT 'Suele ser igual a la clave de acceso tras la autorización',
  `mensajes_sri` text DEFAULT NULL COMMENT 'XML o mensaje de respuesta del WebService',
  `xml_generado` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_notas_credito`
--

CREATE TABLE `ventas_notas_credito` (
  `id` int(11) NOT NULL,
  `emisor_id` int(11) NOT NULL,
  `punto_emision_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `factura_modificada_id` int(11) NOT NULL,
  `ambiente` tinyint(1) NOT NULL DEFAULT 1,
  `tipo_emision` tinyint(1) NOT NULL DEFAULT 1,
  `codigo_documento` varchar(2) NOT NULL DEFAULT '04' COMMENT '04=Nota de Crédito',
  `establecimiento` varchar(3) NOT NULL,
  `punto_emision` varchar(3) NOT NULL,
  `secuencial` varchar(9) NOT NULL,
  `numero_documento` varchar(17) GENERATED ALWAYS AS (concat(`establecimiento`,'-',`punto_emision`,'-',`secuencial`)) VIRTUAL,
  `clave_acceso` varchar(49) DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `tipo_modificacion` enum('DEVOLUCION','DESCUENTO') NOT NULL,
  `fecha_emision_documento_modificado` date NOT NULL,
  `total_sin_impuestos` decimal(12,2) NOT NULL DEFAULT 0.00,
  `base_imponible_0` decimal(12,2) NOT NULL DEFAULT 0.00,
  `base_imponible_iva` decimal(12,2) NOT NULL DEFAULT 0.00,
  `base_no_objeto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `base_exento` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_iva` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_ice` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_modificacion` decimal(12,2) NOT NULL COMMENT 'Importe Total devuelto o descontado',
  `moneda` varchar(15) DEFAULT 'DOLAR',
  `estado_sri` enum('CREADO','FIRMADO','ENVIADO','AUTORIZADO','RECHAZADO','DEVUELTO','ANULADO') DEFAULT 'CREADO',
  `fecha_autorizacion` datetime DEFAULT NULL,
  `numero_autorizacion` varchar(49) DEFAULT NULL,
  `mensajes_sri` text DEFAULT NULL,
  `xml_generado` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_pagos_factura`
--

CREATE TABLE `ventas_pagos_factura` (
  `id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `metodo_pago_id` int(11) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `plazo` int(11) DEFAULT 0 COMMENT 'Tiempo de crédito',
  `unidad_tiempo` varchar(15) DEFAULT 'DIAS' COMMENT 'DIAS, MESES, ANIOS'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_inventario_kardex`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_inventario_kardex` (
`id_movimiento` int(11)
,`fecha_movimiento` datetime
,`bodega` varchar(100)
,`concepto_movimiento` varchar(50)
,`tipo` enum('INGRESO','EGRESO')
,`referencia` varchar(100)
,`codigo_producto` varchar(25)
,`producto` varchar(300)
,`cantidad` decimal(12,4)
,`costo_unitario` decimal(12,4)
,`costo_total` decimal(12,4)
,`variacion_stock` decimal(22,4)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_inventario_kardex`
--
DROP TABLE IF EXISTS `vw_inventario_kardex`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_inventario_kardex`  AS SELECT `m`.`id` AS `id_movimiento`, `m`.`fecha_movimiento` AS `fecha_movimiento`, `b`.`nombre` AS `bodega`, `tm`.`nombre` AS `concepto_movimiento`, `tm`.`naturaleza` AS `tipo`, `m`.`referencia` AS `referencia`, `p`.`codigo_principal` AS `codigo_producto`, `p`.`nombre` AS `producto`, `d`.`cantidad` AS `cantidad`, `d`.`costo_unitario` AS `costo_unitario`, `d`.`costo_total` AS `costo_total`, `d`.`cantidad`* `tm`.`factor` AS `variacion_stock` FROM ((((`inventario_movimientos_detalles` `d` join `inventario_movimientos` `m` on(`d`.`movimiento_id` = `m`.`id`)) join `inventario_tipos_movimiento` `tm` on(`m`.`tipo_movimiento_id` = `tm`.`id`)) join `inventario_bodegas` `b` on(`m`.`bodega_id` = `b`.`id`)) join `inventario_productos` `p` on(`d`.`producto_id` = `p`.`id`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes_cliente`
--
ALTER TABLE `clientes_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `identificacion` (`identificacion`);

--
-- Indices de la tabla `configuracion_emisor`
--
ALTER TABLE `configuracion_emisor`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuracion_metodos_pago`
--
ALTER TABLE `configuracion_metodos_pago`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuracion_puntos_emision`
--
ALTER TABLE `configuracion_puntos_emision`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emisor_id` (`emisor_id`);

--
-- Indices de la tabla `inventario_bodegas`
--
ALTER TABLE `inventario_bodegas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario_categorias`
--
ALTER TABLE `inventario_categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario_general`
--
ALTER TABLE `inventario_general`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_bodega_producto` (`bodega_id`,`producto_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bodega_id` (`bodega_id`),
  ADD KEY `tipo_movimiento_id` (`tipo_movimiento_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `inventario_movimientos_detalles`
--
ALTER TABLE `inventario_movimientos_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movimiento_id` (`movimiento_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `inventario_productos`
--
ALTER TABLE `inventario_productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_principal` (`codigo_principal`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `inventario_tipos_movimiento`
--
ALTER TABLE `inventario_tipos_movimiento`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios_roles`
--
ALTER TABLE `usuarios_roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios_usuario`
--
ALTER TABLE `usuarios_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `punto_emision_id` (`punto_emision_id`);

--
-- Indices de la tabla `ventas_detalles_factura`
--
ALTER TABLE `ventas_detalles_factura`
  ADD PRIMARY KEY (`id`),
  ADD KEY `factura_id` (`factura_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `ventas_detalles_nc`
--
ALTER TABLE `ventas_detalles_nc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nota_credito_id` (`nota_credito_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `ventas_facturas`
--
ALTER TABLE `ventas_facturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave_acceso` (`clave_acceso`),
  ADD KEY `emisor_id` (`emisor_id`),
  ADD KEY `punto_emision_id` (`punto_emision_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `ventas_notas_credito`
--
ALTER TABLE `ventas_notas_credito`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave_acceso` (`clave_acceso`),
  ADD KEY `emisor_id` (`emisor_id`),
  ADD KEY `punto_emision_id` (`punto_emision_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `factura_modificada_id` (`factura_modificada_id`);

--
-- Indices de la tabla `ventas_pagos_factura`
--
ALTER TABLE `ventas_pagos_factura`
  ADD PRIMARY KEY (`id`),
  ADD KEY `factura_id` (`factura_id`),
  ADD KEY `metodo_pago_id` (`metodo_pago_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes_cliente`
--
ALTER TABLE `clientes_cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_emisor`
--
ALTER TABLE `configuracion_emisor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_metodos_pago`
--
ALTER TABLE `configuracion_metodos_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `configuracion_puntos_emision`
--
ALTER TABLE `configuracion_puntos_emision`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_bodegas`
--
ALTER TABLE `inventario_bodegas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_categorias`
--
ALTER TABLE `inventario_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_general`
--
ALTER TABLE `inventario_general`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_movimientos_detalles`
--
ALTER TABLE `inventario_movimientos_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_productos`
--
ALTER TABLE `inventario_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_tipos_movimiento`
--
ALTER TABLE `inventario_tipos_movimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios_roles`
--
ALTER TABLE `usuarios_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios_usuario`
--
ALTER TABLE `usuarios_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ventas_detalles_factura`
--
ALTER TABLE `ventas_detalles_factura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ventas_detalles_nc`
--
ALTER TABLE `ventas_detalles_nc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ventas_facturas`
--
ALTER TABLE `ventas_facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ventas_notas_credito`
--
ALTER TABLE `ventas_notas_credito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ventas_pagos_factura`
--
ALTER TABLE `ventas_pagos_factura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `configuracion_puntos_emision`
--
ALTER TABLE `configuracion_puntos_emision`
  ADD CONSTRAINT `configuracion_puntos_emision_ibfk_1` FOREIGN KEY (`emisor_id`) REFERENCES `configuracion_emisor` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inventario_general`
--
ALTER TABLE `inventario_general`
  ADD CONSTRAINT `inventario_general_ibfk_1` FOREIGN KEY (`bodega_id`) REFERENCES `inventario_bodegas` (`id`),
  ADD CONSTRAINT `inventario_general_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`);

--
-- Filtros para la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD CONSTRAINT `inventario_movimientos_ibfk_1` FOREIGN KEY (`bodega_id`) REFERENCES `inventario_bodegas` (`id`),
  ADD CONSTRAINT `inventario_movimientos_ibfk_2` FOREIGN KEY (`tipo_movimiento_id`) REFERENCES `inventario_tipos_movimiento` (`id`),
  ADD CONSTRAINT `inventario_movimientos_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_usuario` (`id`);

--
-- Filtros para la tabla `inventario_movimientos_detalles`
--
ALTER TABLE `inventario_movimientos_detalles`
  ADD CONSTRAINT `inventario_movimientos_detalles_ibfk_1` FOREIGN KEY (`movimiento_id`) REFERENCES `inventario_movimientos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventario_movimientos_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`);

--
-- Filtros para la tabla `inventario_productos`
--
ALTER TABLE `inventario_productos`
  ADD CONSTRAINT `inventario_productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `inventario_categorias` (`id`);

--
-- Filtros para la tabla `usuarios_usuario`
--
ALTER TABLE `usuarios_usuario`
  ADD CONSTRAINT `usuarios_usuario_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `usuarios_roles` (`id`),
  ADD CONSTRAINT `usuarios_usuario_ibfk_2` FOREIGN KEY (`punto_emision_id`) REFERENCES `configuracion_puntos_emision` (`id`);

--
-- Filtros para la tabla `ventas_detalles_factura`
--
ALTER TABLE `ventas_detalles_factura`
  ADD CONSTRAINT `ventas_detalles_factura_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `ventas_facturas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ventas_detalles_factura_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`);

--
-- Filtros para la tabla `ventas_detalles_nc`
--
ALTER TABLE `ventas_detalles_nc`
  ADD CONSTRAINT `ventas_detalles_nc_ibfk_1` FOREIGN KEY (`nota_credito_id`) REFERENCES `ventas_notas_credito` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ventas_detalles_nc_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`);

--
-- Filtros para la tabla `ventas_facturas`
--
ALTER TABLE `ventas_facturas`
  ADD CONSTRAINT `ventas_facturas_ibfk_1` FOREIGN KEY (`emisor_id`) REFERENCES `configuracion_emisor` (`id`),
  ADD CONSTRAINT `ventas_facturas_ibfk_2` FOREIGN KEY (`punto_emision_id`) REFERENCES `configuracion_puntos_emision` (`id`),
  ADD CONSTRAINT `ventas_facturas_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `clientes_cliente` (`id`),
  ADD CONSTRAINT `ventas_facturas_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_usuario` (`id`);

--
-- Filtros para la tabla `ventas_notas_credito`
--
ALTER TABLE `ventas_notas_credito`
  ADD CONSTRAINT `ventas_notas_credito_ibfk_1` FOREIGN KEY (`emisor_id`) REFERENCES `configuracion_emisor` (`id`),
  ADD CONSTRAINT `ventas_notas_credito_ibfk_2` FOREIGN KEY (`punto_emision_id`) REFERENCES `configuracion_puntos_emision` (`id`),
  ADD CONSTRAINT `ventas_notas_credito_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `clientes_cliente` (`id`),
  ADD CONSTRAINT `ventas_notas_credito_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_usuario` (`id`),
  ADD CONSTRAINT `ventas_notas_credito_ibfk_5` FOREIGN KEY (`factura_modificada_id`) REFERENCES `ventas_facturas` (`id`);

--
-- Filtros para la tabla `ventas_pagos_factura`
--
ALTER TABLE `ventas_pagos_factura`
  ADD CONSTRAINT `ventas_pagos_factura_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `ventas_facturas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ventas_pagos_factura_ibfk_2` FOREIGN KEY (`metodo_pago_id`) REFERENCES `configuracion_metodos_pago` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

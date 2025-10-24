-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 24, 2025 at 08:28 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inmoda_db`
--
CREATE DATABASE IF NOT EXISTS `inmoda_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `inmoda_db`;

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Base de datos de clientes';

--
-- Dumping data for table `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `documento`, `telefono`, `email`, `direccion`, `ciudad`, `fecha_registro`, `activo`) VALUES
(1, 'Ana Martínez', '43567890', '3201234567', 'ana.martinez@email.com', 'Calle 45 #23-10', 'Medellín', '2025-10-24 18:27:58', 1),
(2, 'Pedro Sánchez', '1234567890', '3109876543', 'pedro.sanchez@email.com', 'Carrera 50 #40-20', 'Medellín', '2025-10-24 18:27:58', 1),
(3, 'Laura Gómez', '1098765432', '3156789012', 'laura.gomez@email.com', 'Avenida 33 #70-15', 'Bello', '2025-10-24 18:27:58', 1);

-- --------------------------------------------------------

--
-- Table structure for table `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detalle_venta`
--

INSERT INTO `detalle_venta` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 1, 2, 65000.00, 130000.00),
(2, 1, 9, 1, 30000.00, 30000.00),
(3, 2, 3, 1, 85000.00, 85000.00),
(4, 3, 5, 1, 95000.00, 95000.00),
(5, 3, 6, 1, 52000.00, 52000.00),
(6, 4, 2, 2, 55000.00, 110000.00),
(7, 4, 4, 1, 75000.00, 75000.00),
(8, 5, 7, 1, 110000.00, 110000.00),
(9, 6, 1, 1, 65000.00, 65000.00),
(10, 6, 3, 1, 85000.00, 85000.00),
(11, 7, 10, 3, 35000.00, 105000.00),
(12, 7, 9, 1, 30000.00, 30000.00);

--
-- Triggers `detalle_venta`
--
DELIMITER $$
CREATE TRIGGER `after_detalle_venta_insert` AFTER INSERT ON `detalle_venta` FOR EACH ROW BEGIN
    -- Reducir el stock del producto
    UPDATE productos 
    SET stock = stock - NEW.cantidad 
    WHERE id = NEW.producto_id;
    
    -- Registrar movimiento en inventario
    INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, usuario_id, motivo, fecha)
    SELECT 
        NEW.producto_id,
        'Venta',
        NEW.cantidad,
        stock + NEW.cantidad,
        stock,
        (SELECT usuario_id FROM ventas WHERE id = NEW.venta_id),
        CONCAT('Venta #', NEW.venta_id),
        NOW()
    FROM productos WHERE id = NEW.producto_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `tipo_movimiento` varchar(50) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_nuevo` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `motivo` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notas`
--

CREATE TABLE `notas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `categoria` enum('Trabajo','Personal','Recordatorio','Idea') DEFAULT 'Trabajo',
  `prioridad` enum('Alta','Media','Baja') DEFAULT 'Media',
  `completada` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notas y recordatorios del sistema';

--
-- Dumping data for table `notas`
--

INSERT INTO `notas` (`id`, `usuario_id`, `titulo`, `contenido`, `categoria`, `prioridad`, `completada`, `fecha_creacion`, `fecha_modificacion`) VALUES
(1, 1, 'Inventario de Camisetas', 'Revisar stock de camisetas básicas - están bajando rápido. Contactar proveedor esta semana.', 'Trabajo', 'Alta', 0, '2025-10-24 18:27:58', '2025-10-24 18:27:58'),
(2, 1, 'Cliente Importante', 'Ana Martínez solicita más productos de la línea ejecutiva. Preparar catálogo especial.', 'Trabajo', 'Media', 0, '2025-10-24 18:27:58', '2025-10-24 18:27:58'),
(3, 1, 'Nuevo Proveedor', 'Contactar con Textiles El Sol para nueva colección de temporada. Cita pendiente.', 'Recordatorio', 'Alta', 0, '2025-10-24 18:27:58', '2025-10-24 18:27:58'),
(4, 2, 'Promoción Mes', 'Revisar productos para promoción del próximo mes. Enfoque en pantalones.', 'Trabajo', 'Media', 0, '2025-10-24 18:27:58', '2025-10-24 18:27:58');

-- --------------------------------------------------------

--
-- Table structure for table `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `talla` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `precio_compra` decimal(10,2) DEFAULT 0.00,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `proveedor_id` int(11) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_ingreso` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Inventario de productos textiles y de moda';

--
-- Dumping data for table `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `descripcion`, `categoria`, `talla`, `color`, `precio_compra`, `precio_venta`, `stock`, `stock_minimo`, `proveedor_id`, `marca`, `imagen`, `fecha_ingreso`, `activo`) VALUES
(1, 'CAM001', 'Camisa Manga Larga Formal', 'Camisa formal para hombre, 100% algodón', 'Camisas', 'M', 'Blanco', 35000.00, 65000.00, 25, 5, 1, 'Fashion Line', NULL, '2025-10-24 18:27:58', 1),
(2, 'CAM002', 'Camisa Manga Corta Casual', 'Camisa casual para hombre', 'Camisas', 'L', 'Azul', 30000.00, 55000.00, 30, 5, 1, 'Urban Style', NULL, '2025-10-24 18:27:58', 1),
(3, 'PANT001', 'Pantalón Jean Clásico', 'Pantalón jean corte clásico', 'Pantalones', '32', 'Azul Oscuro', 45000.00, 85000.00, 20, 5, 2, 'Denim Pro', NULL, '2025-10-24 18:27:58', 1),
(4, 'PANT002', 'Pantalón Drill Ejecutivo', 'Pantalón drill formal', 'Pantalones', '34', 'Negro', 40000.00, 75000.00, 15, 5, 2, 'Executive', NULL, '2025-10-24 18:27:58', 1),
(5, 'VEST001', 'Vestido Casual Floral', 'Vestido casual estampado floral', 'Vestidos', 'S', 'Multicolor', 55000.00, 95000.00, 12, 3, 3, 'Trendy', NULL, '2025-10-24 18:27:58', 1),
(6, 'BLUS001', 'Blusa Manga Corta', 'Blusa elegante para dama', 'Blusas', 'M', 'Rosa', 28000.00, 52000.00, 18, 5, 3, 'Feminine', NULL, '2025-10-24 18:27:58', 1),
(7, 'CHAQ001', 'Chaqueta Jean', 'Chaqueta jean unisex', 'Chaquetas', 'L', 'Azul', 60000.00, 110000.00, 8, 3, 1, 'Denim Pro', NULL, '2025-10-24 18:27:58', 1),
(8, 'FALDA001', 'Falda Tubo Ejecutiva', 'Falda tubo para oficina', 'Faldas', 'M', 'Negro', 32000.00, 60000.00, 14, 5, 2, 'Executive', NULL, '2025-10-24 18:27:58', 1),
(9, 'CAMISETA001', 'Camiseta Básica', 'Camiseta cuello redondo', 'Camisetas', 'L', 'Blanco', 15000.00, 30000.00, 50, 10, 1, 'Basic', NULL, '2025-10-24 18:27:58', 1),
(10, 'CAMISETA002', 'Camiseta Deportiva', 'Camiseta tipo dry-fit', 'Camisetas', 'M', 'Negro', 18000.00, 35000.00, 40, 10, 1, 'Sport Line', NULL, '2025-10-24 18:27:58', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `productos_mas_vendidos`
-- (See below for the actual view)
--
CREATE TABLE `productos_mas_vendidos` (
`id` int(11)
,`codigo` varchar(50)
,`nombre` varchar(150)
,`categoria` varchar(50)
,`total_vendido` decimal(32,0)
,`ingresos_generados` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `productos_stock_bajo`
-- (See below for the actual view)
--
CREATE TABLE `productos_stock_bajo` (
`id` int(11)
,`codigo` varchar(50)
,`nombre` varchar(150)
,`stock` int(11)
,`stock_minimo` int(11)
,`categoria` varchar(50)
,`proveedor` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `empresa` varchar(100) DEFAULT NULL,
  `nit` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(50) DEFAULT NULL,
  `productos_suministrados` text DEFAULT NULL,
  `contacto_principal` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Proveedores y distribuidores';

--
-- Dumping data for table `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `empresa`, `nit`, `telefono`, `email`, `direccion`, `ciudad`, `productos_suministrados`, `contacto_principal`, `fecha_registro`, `activo`) VALUES
(1, 'Carlos Textiles', 'Fashion Line S.A.S', '900123456-1', '3001234567', 'carlos@fashionline.com', 'Calle 50 #30-20', 'Medellín', 'Camisas, Camisetas', 'Carlos Rodríguez', '2025-10-24 18:27:58', 1),
(2, 'Laura Confecciones', 'Denim Pro LTDA', '900234567-2', '3102345678', 'laura@denimpro.com', 'Carrera 70 #45-30', 'Medellín', 'Pantalones, Jeans', 'Laura Martínez', '2025-10-24 18:27:58', 1),
(3, 'Ana Modas', 'Trendy Fashion', '900345678-3', '3203456789', 'ana@trendy.com', 'Avenida 33 #80-15', 'Bello', 'Vestidos, Blusas', 'Ana López', '2025-10-24 18:27:58', 1);

-- --------------------------------------------------------

--
-- Table structure for table `turnos`
--

CREATE TABLE `turnos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gestión de turnos y horarios del personal';

--
-- Dumping data for table `turnos`
--

INSERT INTO `turnos` (`id`, `titulo`, `descripcion`, `fecha_inicio`, `fecha_fin`, `creado_por`, `fecha_creacion`) VALUES
(1, 'Turno Mañana', 'Turno matutino de lunes a viernes', '2025-10-27 08:00:00', '2025-10-27 14:00:00', 1, '2025-10-24 18:27:58'),
(2, 'Turno Tarde', 'Turno vespertino de lunes a viernes', '2025-10-27 14:00:00', '2025-10-27 20:00:00', 1, '2025-10-24 18:27:58'),
(3, 'Inventario Mensual', 'Revisión completa del inventario', '2025-10-30 09:00:00', '2025-10-30 17:00:00', 1, '2025-10-24 18:27:58');

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `clave` varchar(255) NOT NULL,
  `rol` enum('Administrador','Vendedor') NOT NULL DEFAULT 'Vendedor',
  `empresa` varchar(100) DEFAULT 'InModa',
  `estado` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios del sistema con roles de Administrador y Vendedor';

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `email`, `clave`, `rol`, `empresa`, `estado`, `fecha_registro`) VALUES
(1, 'Administrador Principal', 'AdminOn1', 'admin@inmoda.com', 'CorporationIn1', 'Administrador', 'InModa', 'Activo', '2025-10-24 18:27:58'),
(2, 'Vendedor Principal', 'VendedorOn2', 'vendedor@inmoda.com', 'CorporationIn2', 'Vendedor', 'InModa', 'Activo', '2025-10-24 18:27:58');

-- --------------------------------------------------------

--
-- Table structure for table `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia','Otro') DEFAULT 'Efectivo',
  `estado` enum('Completada','Pendiente','Cancelada') DEFAULT 'Completada',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de todas las ventas realizadas';

--
-- Dumping data for table `ventas`
--

INSERT INTO `ventas` (`id`, `usuario_id`, `cliente_id`, `total`, `metodo_pago`, `estado`, `fecha`, `observaciones`) VALUES
(1, 1, 1, 160000.00, 'Efectivo', 'Completada', '2025-10-20 15:30:00', 'Cliente frecuente'),
(2, 2, 2, 85000.00, 'Tarjeta', 'Completada', '2025-10-21 19:15:00', NULL),
(3, 1, 3, 147000.00, 'Transferencia', 'Completada', '2025-10-22 14:45:00', NULL),
(4, 2, 1, 185000.00, 'Efectivo', 'Completada', '2025-10-22 21:20:00', NULL),
(5, 1, 2, 110000.00, 'Tarjeta', 'Completada', '2025-10-23 16:30:00', NULL),
(6, 2, 3, 150000.00, 'Efectivo', 'Completada', '2025-10-23 20:00:00', NULL),
(7, 1, 1, 135000.00, 'Transferencia', 'Completada', '2025-10-24 15:00:00', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `ventas_por_usuario`
-- (See below for the actual view)
--
CREATE TABLE `ventas_por_usuario` (
`usuario_id` int(11)
,`usuario` varchar(100)
,`rol` enum('Administrador','Vendedor')
,`total_ventas` bigint(21)
,`monto_total_vendido` decimal(32,2)
,`promedio_venta` decimal(14,6)
);

-- --------------------------------------------------------

--
-- Structure for view `productos_mas_vendidos`
--
DROP TABLE IF EXISTS `productos_mas_vendidos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `productos_mas_vendidos`  AS SELECT `p`.`id` AS `id`, `p`.`codigo` AS `codigo`, `p`.`nombre` AS `nombre`, `p`.`categoria` AS `categoria`, sum(`dv`.`cantidad`) AS `total_vendido`, sum(`dv`.`subtotal`) AS `ingresos_generados` FROM (`detalle_venta` `dv` join `productos` `p` on(`dv`.`producto_id` = `p`.`id`)) GROUP BY `p`.`id`, `p`.`codigo`, `p`.`nombre`, `p`.`categoria` ORDER BY sum(`dv`.`cantidad`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `productos_stock_bajo`
--
DROP TABLE IF EXISTS `productos_stock_bajo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `productos_stock_bajo`  AS SELECT `p`.`id` AS `id`, `p`.`codigo` AS `codigo`, `p`.`nombre` AS `nombre`, `p`.`stock` AS `stock`, `p`.`stock_minimo` AS `stock_minimo`, `p`.`categoria` AS `categoria`, `prov`.`nombre` AS `proveedor` FROM (`productos` `p` left join `proveedores` `prov` on(`p`.`proveedor_id` = `prov`.`id`)) WHERE `p`.`stock` <= `p`.`stock_minimo` AND `p`.`activo` = 1 ORDER BY `p`.`stock` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `ventas_por_usuario`
--
DROP TABLE IF EXISTS `ventas_por_usuario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ventas_por_usuario`  AS SELECT `u`.`id` AS `usuario_id`, `u`.`nombre` AS `usuario`, `u`.`rol` AS `rol`, count(`v`.`id`) AS `total_ventas`, sum(`v`.`total`) AS `monto_total_vendido`, avg(`v`.`total`) AS `promedio_venta` FROM (`usuarios` `u` left join `ventas` `v` on(`u`.`id` = `v`.`usuario_id`)) GROUP BY `u`.`id`, `u`.`nombre`, `u`.`rol` ORDER BY sum(`v`.`total`) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clientes_nombre` (`nombre`);

--
-- Indexes for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detalle_venta` (`venta_id`),
  ADD KEY `fk_detalle_producto` (`producto_id`);

--
-- Indexes for table `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_movimiento_producto` (`producto_id`),
  ADD KEY `fk_movimiento_usuario` (`usuario_id`);

--
-- Indexes for table `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_nota_usuario` (`usuario_id`);

--
-- Indexes for table `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `fk_proveedor` (`proveedor_id`),
  ADD KEY `idx_productos_categoria` (`categoria`),
  ADD KEY `idx_productos_stock` (`stock`);

--
-- Indexes for table `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_turno_usuario` (`creado_por`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `idx_usuarios_usuario` (`usuario`);

--
-- Indexes for table `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_venta_usuario` (`usuario_id`),
  ADD KEY `fk_venta_cliente` (`cliente_id`),
  ADD KEY `idx_ventas_fecha` (`fecha`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notas`
--
ALTER TABLE `notas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detalle_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `fk_movimiento_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_movimiento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `fk_nota_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `fk_turno_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-10-2026 a las 20:28:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `inmobiliaria`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alquileres`
--

CREATE TABLE `alquileres` (
  `id_alquiler` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_agente` int(11) NOT NULL,
  `monto_mensual` decimal(12,2) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('Pendiente','Activo','Finalizado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_evento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `entidad` varchar(40) NOT NULL,
  `id_registro` int(11) NOT NULL,
  `detalle` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id_evento`, `id_usuario`, `accion`, `entidad`, `id_registro`, `detalle`, `fecha`) VALUES
(1, 2, 'Alta de usuario', 'usuarios', 2, 'Rol 1', '2026-10-18 01:12:11'),
(2, 3, 'Alta de usuario', 'usuarios', 3, 'Rol 3', '2026-10-18 02:32:29'),
(3, 7, 'Estado de usuario', 'usuarios', 1, 'Activo', '2026-10-18 20:16:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_cambios`
--

CREATE TABLE `auditoria_cambios` (
  `id_cambio` int(11) NOT NULL,
  `id_evento` int(11) DEFAULT NULL,
  `entidad` varchar(40) NOT NULL,
  `id_registro` int(11) NOT NULL,
  `campo` varchar(80) NOT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_nuevo` text DEFAULT NULL,
  `motivo` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caracteristicas`
--

CREATE TABLE `caracteristicas` (
  `id_caracteristica` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `caracteristicas`
--

INSERT INTO `caracteristicas` (`id_caracteristica`, `nombre`) VALUES
(8, 'Aire Acondicionado'),
(1, 'Balcón'),
(7, 'Calefacción'),
(2, 'Cochera'),
(6, 'Jardín'),
(3, 'Patio'),
(5, 'Pileta'),
(10, 'Seguridad 24hs'),
(9, 'SUM'),
(4, 'Terraza');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_publicacion`
--

CREATE TABLE `checklist_publicacion` (
  `id_propiedad` int(11) NOT NULL,
  `id_requisito` int(11) NOT NULL,
  `id_documento` int(11) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `observaciones` varchar(500) DEFAULT NULL,
  `id_revisor` int(11) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `fecha_inicio_relacion` date NOT NULL,
  `id_agente_asignado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `fecha_inicio_relacion`, `id_agente_asignado`) VALUES
(1, '2026-10-14', NULL),
(3, '2026-10-17', NULL),
(5, '2026-10-18', 4),
(6, '2026-10-18', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_alertas`
--

CREATE TABLE `configuracion_alertas` (
  `clave` varchar(80) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_alertas`
--

INSERT INTO `configuracion_alertas` (`clave`, `valor`, `descripcion`, `actualizado_por`, `fecha_actualizacion`) VALUES
('dias_aviso_reserva', '3', 'Días antes del vencimiento para avisar al equipo.', NULL, '2026-10-19 16:24:12'),
('dias_cliente_sin_contacto', '14', 'Días sin contacto para alertar al agente.', NULL, '2026-10-19 16:24:12'),
('dias_propiedad_sin_actividad', '30', 'Días sin actividad para alertar al responsable.', NULL, '2026-10-19 16:24:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos`
--

CREATE TABLE `contactos` (
  `id_contacto` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_agente` int(11) NOT NULL,
  `id_propiedad` int(11) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `descripcion` text NOT NULL,
  `resultado` text DEFAULT NULL,
  `estado` enum('Pendiente','Respondida','Cerrada') NOT NULL DEFAULT 'Pendiente',
  `fecha_respuesta` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos_adicionales_cliente`
--

CREATE TABLE `contactos_adicionales_cliente` (
  `id_contacto` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `convenios`
--

CREATE TABLE `convenios` (
  `id_convenio` int(11) NOT NULL,
  `id_propietario` int(11) NOT NULL,
  `id_propiedad` int(11) DEFAULT NULL,
  `profesional` varchar(150) DEFAULT NULL,
  `condiciones` text NOT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos`
--

CREATE TABLE `documentos` (
  `id_documento` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `tipo` varchar(40) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `archivo` varchar(500) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `edificios`
--

CREATE TABLE `edificios` (
  `id_edificio` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `localidad` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_comerciales`
--

CREATE TABLE `estados_comerciales` (
  `id_estado_comercial` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estados_comerciales`
--

INSERT INTO `estados_comerciales` (`id_estado_comercial`, `nombre`) VALUES
(4, 'Alquilada'),
(1, 'Disponible'),
(5, 'En trámite'),
(6, 'Fuera de mercado'),
(2, 'Reservada'),
(3, 'Vendida');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_verificacion`
--

CREATE TABLE `estados_verificacion` (
  `id_estado_verificacion` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estados_verificacion`
--

INSERT INTO `estados_verificacion` (`id_estado_verificacion`, `nombre`) VALUES
(3, 'Aprobado'),
(1, 'Borrador'),
(5, 'Inactivo'),
(2, 'Pendiente de Verificación'),
(4, 'Rechazado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_asignaciones`
--

CREATE TABLE `historial_asignaciones` (
  `id_asignacion` int(11) NOT NULL,
  `tipo_entidad` varchar(20) NOT NULL,
  `id_entidad` int(11) NOT NULL,
  `agente_anterior` int(11) DEFAULT NULL,
  `agente_nuevo` int(11) DEFAULT NULL,
  `id_usuario_cambio` int(11) NOT NULL,
  `motivo` varchar(500) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_estados`
--

CREATE TABLE `historial_estados` (
  `id_historial` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `estado_anterior` int(11) NOT NULL,
  `estado_nuevo` int(11) NOT NULL,
  `motivo` varchar(500) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_precios`
--

CREATE TABLE `historial_precios` (
  `id_historial_precio` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `id_tipo_transaccion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `precio_anterior` decimal(12,2) NOT NULL,
  `precio_nuevo` decimal(12,2) NOT NULL,
  `moneda` varchar(3) NOT NULL,
  `motivo` varchar(500) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intereses`
--

CREATE TABLE `intereses` (
  `id_cliente` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'Interesado',
  `acciones` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `es_favorito` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `intereses`
--

INSERT INTO `intereses` (`id_cliente`, `id_propiedad`, `estado`, `acciones`, `fecha`, `es_favorito`) VALUES
(6, 1, 'Interesado', 'Interés de ejemplo para probar el seguimiento.', '2026-10-18 03:06:26', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `mensaje` varchar(500) NOT NULL,
  `leida` int(11) NOT NULL DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ofertas_cliente`
--

CREATE TABLE `ofertas_cliente` (
  `id_oferta` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_agente` int(11) NOT NULL,
  `id_tipo_transaccion` int(11) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `moneda` varchar(3) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `observaciones` text DEFAULT NULL,
  `id_revisor` int(11) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preferencias_caracteristicas`
--

CREATE TABLE `preferencias_caracteristicas` (
  `id_preferencia` int(11) NOT NULL,
  `id_caracteristica` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preferencias_cliente`
--

CREATE TABLE `preferencias_cliente` (
  `id_preferencia` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_tipo_transaccion` int(11) NOT NULL,
  `id_tipo_propiedad` int(11) DEFAULT NULL,
  `presupuesto_maximo` decimal(12,2) DEFAULT NULL,
  `zona_interes` varchar(100) DEFAULT NULL,
  `ambientes_minimos` int(11) DEFAULT NULL,
  `dormitorios_minimos` int(11) DEFAULT NULL,
  `superficie_minima_m2` decimal(8,2) DEFAULT NULL,
  `fecha_estimada_concretar` date DEFAULT NULL,
  `requiere_venta_previa` tinyint(1) DEFAULT 0,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `moneda` varchar(3) NOT NULL DEFAULT 'USD'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propiedades`
--

CREATE TABLE `propiedades` (
  `id_propiedad` int(11) NOT NULL,
  `id_tipo_propiedad` int(11) NOT NULL,
  `id_tipo_transaccion` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `localidad` varchar(100) NOT NULL,
  `precio` decimal(12,2) NOT NULL,
  `moneda` enum('USD','ARS') DEFAULT 'USD',
  `descripcion` text DEFAULT NULL,
  `superficie_total_m2` decimal(8,2) NOT NULL,
  `superficie_cubierta_m2` decimal(8,2) DEFAULT NULL,
  `antiguedad_anos` int(11) DEFAULT 0,
  `ambientes` int(11) DEFAULT NULL,
  `dormitorios` int(11) DEFAULT NULL,
  `banos` int(11) DEFAULT NULL,
  `piso` varchar(10) DEFAULT NULL,
  `departamento_numero` varchar(10) DEFAULT NULL,
  `expensas` decimal(10,2) DEFAULT NULL,
  `apto_profesional` tinyint(1) DEFAULT 0,
  `id_agente_creador` int(11) DEFAULT NULL,
  `id_admin_verificador` int(11) DEFAULT NULL,
  `id_estado_verificacion` int(11) NOT NULL DEFAULT 1,
  `id_estado_comercial` int(11) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_verificacion` datetime DEFAULT NULL,
  `motivo_rechazo` text DEFAULT NULL,
  `id_propietario` int(11) DEFAULT NULL,
  `id_edificio` int(11) DEFAULT NULL,
  `numero_unidad` varchar(30) DEFAULT NULL,
  `capacidad` int(11) NOT NULL DEFAULT 1,
  `id_solicitud` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `propiedades`
--

INSERT INTO `propiedades` (`id_propiedad`, `id_tipo_propiedad`, `id_tipo_transaccion`, `titulo`, `direccion`, `localidad`, `precio`, `moneda`, `descripcion`, `superficie_total_m2`, `superficie_cubierta_m2`, `antiguedad_anos`, `ambientes`, `dormitorios`, `banos`, `piso`, `departamento_numero`, `expensas`, `apto_profesional`, `id_agente_creador`, `id_admin_verificador`, `id_estado_verificacion`, `id_estado_comercial`, `fecha_creacion`, `fecha_verificacion`, `motivo_rechazo`, `id_propietario`, `id_edificio`, `numero_unidad`, `capacidad`, `id_solicitud`) VALUES
(1, 1, 1, 'Casa con jardín en San Isidro', 'Belgrano 450', 'San Isidro', 250000.00, 'USD', 'Casa luminosa con jardín, living comedor amplio y cochera.', 200.00, 145.00, 8, 4, 3, 2, NULL, NULL, 0.00, 0, 4, 2, 3, 1, '2026-10-18 00:06:26', NULL, NULL, 5, NULL, NULL, 5, NULL),
(2, 2, 1, 'Departamento luminoso en Palermo', 'Av. Santa Fe 3200', 'Palermo, CABA', 180000.00, 'USD', 'Departamento de dos ambientes con balcón y buena luz natural.', 85.00, 76.00, 5, 2, 1, 1, NULL, NULL, 45000.00, 0, 4, 2, 3, 1, '2026-10-18 00:06:26', NULL, NULL, 6, NULL, NULL, 2, NULL),
(3, 1, 2, 'Casa con pileta en Tigre', 'Los Ceibos 740', 'Tigre', 1200.00, 'USD', 'Casa amueblada con pileta, galería y espacio verde.', 300.00, 170.00, 10, 5, 3, 2, NULL, NULL, 0.00, 0, 4, 2, 3, 1, '2026-10-18 00:06:26', NULL, NULL, 5, NULL, NULL, 6, NULL),
(4, 1, 1, 'PH reciclado con terraza en Villa Urquiza', 'Mendoza 4820', 'Villa Urquiza, CABA', 169000.00, 'USD', 'PH de tres ambientes reciclado, con terraza propia, parrilla y entrada independiente. A pocas cuadras del subte y de la estación.', 112.00, 78.00, 18, 3, 2, 1, NULL, NULL, 0.00, 0, 4, 7, 3, 1, '2026-10-19 00:02:01', '2026-10-19 00:02:01', NULL, 5, NULL, NULL, 4, NULL),
(5, 1, 1, 'Casa familiar con jardín en Martínez', 'Sáenz Valiente 1840', 'Martínez, Buenos Aires', 289000.00, 'USD', 'Casa de cuatro ambientes con jardín, galería semicubierta y cochera. Ambientes luminosos y buena conexión con accesos y comercios.', 245.00, 168.00, 14, 4, 3, 2, NULL, NULL, 0.00, 0, 4, 7, 3, 1, '2026-10-19 00:02:01', '2026-10-19 00:02:01', NULL, 5, NULL, NULL, 6, NULL),
(6, 1, 2, 'Casa amueblada con pileta en Tigre', 'Los Ceibos 740', 'Tigre, Buenos Aires', 1450.00, 'USD', 'Alquiler temporario de casa amueblada con pileta, galería, parrilla y parque. Ideal para estadías familiares.', 310.00, 176.00, 11, 5, 3, 2, NULL, NULL, 0.00, 0, 4, 7, 3, 1, '2026-10-19 00:02:01', '2026-10-19 00:02:01', NULL, 5, NULL, NULL, 6, NULL),
(7, 2, 1, 'Departamento con balcón en Caballito', 'Av. Pedro Goyena 860', 'Caballito, CABA', 215000.00, 'USD', 'Departamento de tres ambientes con balcón al frente, cocina independiente y espacio para lavarropas. Edificio con ascensor y seguridad.', 96.00, 88.00, 7, 3, 2, 2, NULL, NULL, 98000.00, 0, 4, 7, 3, 1, '2026-10-19 00:02:01', '2026-10-19 00:02:01', NULL, 6, NULL, NULL, 3, NULL),
(8, 3, 1, 'Local a la calle en Belgrano', 'Juramento 2180', 'Belgrano, CABA', 198000.00, 'USD', 'Local comercial en corredor de alto tránsito, con vidriera amplia, salón principal y baño. Apto para distintos rubros.', 78.00, 78.00, 22, 2, 0, 1, NULL, NULL, 0.00, 1, 4, 7, 3, 1, '2026-10-19 00:02:01', '2026-10-19 00:02:01', NULL, 6, NULL, NULL, 2, NULL),
(9, 5, 1, 'Lote arbolado en barrio privado de Escobar', 'Lote 184, Barrio Los Álamos', 'Escobar, Buenos Aires', 72000.00, 'USD', 'Terreno nivelado y arbolado dentro de barrio privado con servicios, espacios verdes y acceso controlado.', 820.00, 0.00, 0, 0, 0, 0, NULL, NULL, 185000.00, 0, 4, 7, 3, 1, '2026-10-19 00:02:01', '2026-10-19 00:02:01', NULL, 5, NULL, NULL, 1, NULL),
(10, 4, 2, 'Oficina luminosa cerca de Plaza San Martín', 'Maipú 480, piso 6', 'Microcentro, CABA', 1350000.00, 'ARS', 'Oficina en alquiler con recepción, dos despachos, sala de reuniones y kitchenette. Edificio con ascensores y control de acceso.', 92.00, 92.00, 16, 4, 0, 1, NULL, NULL, 210000.00, 1, 4, 7, 3, 1, '2026-10-19 00:02:01', '2026-10-19 00:02:01', NULL, 6, NULL, NULL, 5, NULL),
(11, 6, 1, 'Cochera cubierta en Palermo Soho', 'Gurruchaga 1420, subsuelo', 'Palermo, CABA', 26500.00, 'USD', 'Cochera fija cubierta en edificio residencial, con portón automático y acceso cómodo desde la calle.', 14.00, 14.00, 9, 0, 0, 0, NULL, NULL, 18000.00, 0, 4, 7, 3, 1, '2026-10-19 00:02:01', '2026-10-19 00:02:01', NULL, 6, NULL, NULL, 1, NULL),
(12, 1, 2, 'Chalet frente al mar en alquiler', 'Calle 4 n.º 820', 'Mar del Tuyú, Buenos Aires', 950000.00, 'ARS', 'Chalet amueblado a pocas cuadras de la playa, con patio, parrilla y espacio para guardar el auto. Disponible por temporada.', 190.00, 118.00, 24, 4, 3, 2, NULL, NULL, 0.00, 0, 4, 7, 3, 1, '2026-10-19 00:02:01', '2026-10-19 00:02:01', NULL, 5, NULL, NULL, 6, NULL),
(13, 2, 1, 'Departamento con amenities en Vicente López', 'Av. del Libertador 1550, piso 8', 'Vicente López, Buenos Aires', 238000.00, 'USD', 'Departamento de tres ambientes con balcón y vista abierta. Edificio con pileta, SUM, gimnasio y seguridad permanente.', 105.00, 94.00, 4, 3, 2, 2, NULL, NULL, 175000.00, 0, 4, 7, 3, 1, '2026-10-19 00:02:01', '2026-10-19 00:02:01', NULL, 6, NULL, NULL, 3, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propiedades_cerradas`
--

CREATE TABLE `propiedades_cerradas` (
  `id_operacion` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `id_cliente_comprador` int(11) NOT NULL,
  `id_agente_cierre` int(11) NOT NULL,
  `precio_final` decimal(12,2) NOT NULL,
  `comision_total` decimal(10,2) DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT current_timestamp(),
  `id_tipo_transaccion` int(11) DEFAULT NULL,
  `moneda` varchar(3) NOT NULL DEFAULT 'USD'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propiedad_caracteristicas`
--

CREATE TABLE `propiedad_caracteristicas` (
  `id_propiedad` int(11) NOT NULL,
  `id_caracteristica` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propiedad_multimedia`
--

CREATE TABLE `propiedad_multimedia` (
  `id_multimedia` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `url_archivo` varchar(500) NOT NULL,
  `tipo` enum('Imagen','Video') DEFAULT 'Imagen',
  `orden` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `propiedad_multimedia`
--

INSERT INTO `propiedad_multimedia` (`id_multimedia`, `id_propiedad`, `url_archivo`, `tipo`, `orden`) VALUES
(1, 4, 'demo/propiedad-01.jpg', 'Imagen', 1),
(2, 5, 'demo/propiedad-02.jpg', 'Imagen', 1),
(3, 6, 'demo/propiedad-03.jpg', 'Imagen', 1),
(4, 7, 'demo/propiedad-04.jpg', 'Imagen', 1),
(5, 8, 'demo/propiedad-05.jpg', 'Imagen', 1),
(6, 9, 'demo/propiedad-06.jpg', 'Imagen', 1),
(7, 10, 'demo/propiedad-07.jpg', 'Imagen', 1),
(8, 11, 'demo/propiedad-08.jpg', 'Imagen', 1),
(9, 12, 'demo/propiedad-09.jpg', 'Imagen', 1),
(10, 13, 'demo/propiedad-10.jpg', 'Imagen', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propiedad_ofertas`
--

CREATE TABLE `propiedad_ofertas` (
  `id_propiedad` int(11) NOT NULL,
  `id_tipo_transaccion` int(11) NOT NULL,
  `precio` decimal(12,2) NOT NULL CHECK (`precio` > 0),
  `moneda` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `propiedad_ofertas`
--

INSERT INTO `propiedad_ofertas` (`id_propiedad`, `id_tipo_transaccion`, `precio`, `moneda`) VALUES
(1, 1, 250000.00, 'USD'),
(2, 1, 180000.00, 'USD'),
(3, 2, 1200.00, 'USD'),
(4, 1, 169000.00, 'USD'),
(5, 1, 289000.00, 'USD'),
(6, 2, 1450.00, 'USD'),
(7, 1, 215000.00, 'USD'),
(8, 1, 198000.00, 'USD'),
(9, 1, 72000.00, 'USD'),
(10, 2, 1350000.00, 'ARS'),
(11, 1, 26500.00, 'USD'),
(12, 2, 950000.00, 'ARS'),
(13, 1, 238000.00, 'USD');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicaciones`
--

CREATE TABLE `publicaciones` (
  `id_publicacion` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `id_solicitante` int(11) NOT NULL,
  `id_revisor` int(11) DEFAULT NULL,
  `estado` varchar(25) NOT NULL DEFAULT 'Pendiente',
  `observaciones` text DEFAULT NULL,
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_revision` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `publicaciones`
--

INSERT INTO `publicaciones` (`id_publicacion`, `id_propiedad`, `id_solicitante`, `id_revisor`, `estado`, `observaciones`, `fecha_solicitud`, `fecha_revision`) VALUES
(1, 1, 4, 2, 'Publicada', 'Registro de demostración aprobado', '2026-10-18 03:06:26', '2026-10-18 03:06:26'),
(2, 3, 4, 2, 'Publicada', 'Registro de demostración aprobado', '2026-10-18 03:06:26', '2026-10-18 03:06:26'),
(3, 2, 4, 2, 'Publicada', 'Registro de demostración aprobado', '2026-10-18 03:06:26', '2026-10-18 03:06:26'),
(4, 4, 4, 7, 'Publicada', 'Propiedad ficticia aprobada para la demostración.', '2026-10-19 03:02:02', '2026-10-19 03:02:02'),
(5, 5, 4, 7, 'Publicada', 'Propiedad ficticia aprobada para la demostración.', '2026-10-19 03:02:02', '2026-10-19 03:02:02'),
(6, 6, 4, 7, 'Publicada', 'Propiedad ficticia aprobada para la demostración.', '2026-10-19 03:02:02', '2026-10-19 03:02:02'),
(7, 7, 4, 7, 'Publicada', 'Propiedad ficticia aprobada para la demostración.', '2026-10-19 03:02:02', '2026-10-19 03:02:02'),
(8, 8, 4, 7, 'Publicada', 'Propiedad ficticia aprobada para la demostración.', '2026-10-19 03:02:02', '2026-10-19 03:02:02'),
(9, 9, 4, 7, 'Publicada', 'Propiedad ficticia aprobada para la demostración.', '2026-10-19 03:02:02', '2026-10-19 03:02:02'),
(10, 10, 4, 7, 'Publicada', 'Propiedad ficticia aprobada para la demostración.', '2026-10-19 03:02:02', '2026-10-19 03:02:02'),
(11, 11, 4, 7, 'Publicada', 'Propiedad ficticia aprobada para la demostración.', '2026-10-19 03:02:02', '2026-10-19 03:02:02'),
(12, 12, 4, 7, 'Publicada', 'Propiedad ficticia aprobada para la demostración.', '2026-10-19 03:02:02', '2026-10-19 03:02:02'),
(13, 13, 4, 7, 'Publicada', 'Propiedad ficticia aprobada para la demostración.', '2026-10-19 03:02:02', '2026-10-19 03:02:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requisitos_publicacion`
--

CREATE TABLE `requisitos_publicacion` (
  `id_requisito` int(11) NOT NULL,
  `id_tipo_propiedad` int(11) DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `obligatorio` tinyint(1) NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `requisitos_publicacion`
--

INSERT INTO `requisitos_publicacion` (`id_requisito`, `id_tipo_propiedad`, `nombre`, `obligatorio`, `activo`) VALUES
(1, NULL, 'Autorización de comercialización', 1, 1),
(2, NULL, 'Documento que acredita titularidad', 1, 1),
(3, NULL, 'Fotografías principales', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id_reserva` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_agente` int(11) NOT NULL,
  `id_tipo_transaccion` int(11) NOT NULL,
  `id_oferta` int(11) DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `moneda` varchar(3) NOT NULL,
  `fecha_reserva` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_limite` datetime NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `estado_pago` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `medio_pago` varchar(80) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `id_autorizador` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(1, 'Administrador'),
(2, 'Agente'),
(3, 'Cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles_cliente`
--

CREATE TABLE `roles_cliente` (
  `id_cliente` int(11) NOT NULL,
  `tipo_rol` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles_cliente`
--

INSERT INTO `roles_cliente` (`id_cliente`, `tipo_rol`) VALUES
(5, 'Comprador'),
(5, 'Propietario'),
(6, 'Comprador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `schema_version`
--

CREATE TABLE `schema_version` (
  `version` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `schema_version`
--

INSERT INTO `schema_version` (`version`, `fecha`) VALUES
(2, '2026-10-18 01:04:27'),
(3, '2026-10-19 16:24:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_publicacion`
--

CREATE TABLE `solicitudes_publicacion` (
  `id_solicitud` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_tipo_propiedad` int(11) NOT NULL,
  `id_tipo_transaccion` int(11) NOT NULL,
  `id_estado_verificacion` int(11) NOT NULL DEFAULT 2,
  `titulo` varchar(200) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `localidad` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `precio_estimado` decimal(12,2) DEFAULT NULL,
  `superficie_total_m2` decimal(8,2) NOT NULL,
  `ambientes` int(11) DEFAULT NULL,
  `dormitorios` int(11) DEFAULT NULL,
  `banos` int(11) DEFAULT NULL,
  `antiguedad_anos` int(11) DEFAULT 0,
  `motivo_rechazo` text DEFAULT NULL,
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `id_agente` int(11) DEFAULT NULL,
  `moneda` enum('USD','ARS') NOT NULL DEFAULT 'USD'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud_multimedia`
--

CREATE TABLE `solicitud_multimedia` (
  `id_multimedia` int(11) NOT NULL,
  `id_solicitud` int(11) NOT NULL,
  `url_archivo` varchar(500) NOT NULL,
  `tipo` enum('Imagen','Video') NOT NULL DEFAULT 'Imagen',
  `fecha_carga` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas_seguimiento`
--

CREATE TABLE `tareas_seguimiento` (
  `id_tarea` int(11) NOT NULL,
  `id_agente` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_propiedad` int(11) DEFAULT NULL,
  `titulo` varchar(180) NOT NULL,
  `detalle` text DEFAULT NULL,
  `fecha_limite` datetime NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `fecha_completada` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_propiedad`
--

CREATE TABLE `tipos_propiedad` (
  `id_tipo_propiedad` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_propiedad`
--

INSERT INTO `tipos_propiedad` (`id_tipo_propiedad`, `nombre`) VALUES
(1, 'Casa'),
(6, 'Cochera'),
(2, 'Departamento'),
(7, 'Depósito'),
(3, 'Local'),
(4, 'Oficina'),
(5, 'Terreno');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_transaccion`
--

CREATE TABLE `tipos_transaccion` (
  `id_tipo_transaccion` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_transaccion`
--

INSERT INTO `tipos_transaccion` (`id_tipo_transaccion`, `nombre`) VALUES
(2, 'Alquiler'),
(3, 'Alquiler Temporario'),
(1, 'Venta');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `direccion` varchar(255) DEFAULT NULL,
  `localidad` varchar(100) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` varchar(40) DEFAULT NULL,
  `tipo_documento` varchar(20) NOT NULL DEFAULT 'DNI'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `dni`, `telefono`, `email`, `password_hash`, `id_rol`, `activo`, `fecha_registro`, `direccion`, `localidad`, `fecha_nacimiento`, `genero`, `tipo_documento`) VALUES
(1, 'denise', 'roglich', '49315808', '1137742173', 'denise@gmail.com', '$2y$10$mcdusYrQFrkdsbkrIbNYIu2dSmSbXMmnoYGV3yQFE8hAWXkF2pPty', 3, 1, '2026-10-14 20:38:35', NULL, NULL, NULL, NULL, 'DNI'),
(2, 'daniel', 'roglich', '293821923', '1193929321', 'daniel@gmail.com', 'pbkdf2$600000$QrOB8YO6cEGaacKKbEmLrg==$K7Zus80vqPyPDoDW8XoHZdGgBXFzvcRTwi9nwsE6LH0=', 1, 1, '2026-10-17 22:12:11', '', '', NULL, '', 'DNI'),
(3, 'lucia', 'var', '84932923', '1182732922', 'lucia@gmail.com', 'pbkdf2$600000$3ma42fNfr+E2OvF1X6ZgAA==$Yz3ZPWEbCxWjkny7U0L9vutUv3z/llkqhFc2meIbmgI=', 3, 1, '2026-10-17 23:32:29', 'avenida 123', 'munro', '1994-10-13', 'Femenino', 'DNI'),
(4, 'Lucía', 'Fernández', '99000001', '11 5555 1001', 'agente.demo@nuevohogar.local', '$2y$10$v3Rzg28Q72ciEdwzko/ifuU72ce4ygeK8pxn6HNxcObmQxoBDRNGu', 2, 1, '2026-10-18 00:06:25', 'Av. Maipú 1200', 'Vicente López', NULL, NULL, 'DNI'),
(5, 'Juan', 'Pérez', '99000002', '11 5555 1002', 'cliente.demo@nuevohogar.local', 'pbkdf2$600000$TnVldm9Ib2dhcjIwMjYhIQ==$dy7tU4zvIYEHhubI3NscwgwcE4poF2PJgYCXVkweQIw=', 3, 1, '2026-10-18 00:06:25', 'Belgrano 450', 'San Isidro', NULL, NULL, 'DNI'),
(6, 'María', 'López', '99000003', '11 5555 1003', 'cliente2.demo@nuevohogar.local', 'pbkdf2$600000$TnVldm9Ib2dhcjIwMjYhIQ==$dy7tU4zvIYEHhubI3NscwgwcE4poF2PJgYCXVkweQIw=', 3, 1, '2026-10-18 00:06:25', 'Las Heras 820', 'Palermo, CABA', NULL, NULL, 'DNI'),
(7, 'Ana', 'Suárez', '99000000', '11 5555 1000', 'admin.demo@nuevohogar.local', 'pbkdf2$600000$TnVldm9Ib2dhcjIwMjYhIQ==$dy7tU4zvIYEHhubI3NscwgwcE4poF2PJgYCXVkweQIw=', 1, 1, '2026-10-18 00:17:28', 'Av. Maipú 1000', 'Vicente López', NULL, NULL, 'DNI');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visitas`
--

CREATE TABLE `visitas` (
  `id_visita` int(11) NOT NULL,
  `id_propiedad` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_agente` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Programada',
  `resultado` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alquileres`
--
ALTER TABLE `alquileres`
  ADD PRIMARY KEY (`id_alquiler`),
  ADD KEY `idx_alquiler_cliente` (`id_cliente`,`fecha_inicio`),
  ADD KEY `idx_alquiler_estado` (`estado`),
  ADD KEY `fk_alquiler_propiedad` (`id_propiedad`),
  ADD KEY `fk_alquiler_agente` (`id_agente`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `auditoria_cambios`
--
ALTER TABLE `auditoria_cambios`
  ADD PRIMARY KEY (`id_cambio`),
  ADD KEY `id_evento` (`id_evento`),
  ADD KEY `idx_cambio_entidad` (`entidad`,`id_registro`,`id_cambio`);

--
-- Indices de la tabla `caracteristicas`
--
ALTER TABLE `caracteristicas`
  ADD PRIMARY KEY (`id_caracteristica`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `checklist_publicacion`
--
ALTER TABLE `checklist_publicacion`
  ADD PRIMARY KEY (`id_propiedad`,`id_requisito`),
  ADD KEY `id_requisito` (`id_requisito`),
  ADD KEY `id_documento` (`id_documento`),
  ADD KEY `id_revisor` (`id_revisor`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD KEY `id_agente_asignado` (`id_agente_asignado`);

--
-- Indices de la tabla `configuracion_alertas`
--
ALTER TABLE `configuracion_alertas`
  ADD PRIMARY KEY (`clave`),
  ADD KEY `actualizado_por` (`actualizado_por`);

--
-- Indices de la tabla `contactos`
--
ALTER TABLE `contactos`
  ADD PRIMARY KEY (`id_contacto`),
  ADD KEY `id_agente` (`id_agente`),
  ADD KEY `id_propiedad` (`id_propiedad`),
  ADD KEY `idx_contactos_cliente` (`id_cliente`,`fecha`);

--
-- Indices de la tabla `contactos_adicionales_cliente`
--
ALTER TABLE `contactos_adicionales_cliente`
  ADD PRIMARY KEY (`id_contacto`),
  ADD KEY `idx_contactos_cliente_tipo` (`id_cliente`,`tipo`);

--
-- Indices de la tabla `convenios`
--
ALTER TABLE `convenios`
  ADD PRIMARY KEY (`id_convenio`),
  ADD KEY `id_propietario` (`id_propietario`),
  ADD KEY `id_propiedad` (`id_propiedad`);

--
-- Indices de la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `id_propiedad` (`id_propiedad`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `edificios`
--
ALTER TABLE `edificios`
  ADD PRIMARY KEY (`id_edificio`);

--
-- Indices de la tabla `estados_comerciales`
--
ALTER TABLE `estados_comerciales`
  ADD PRIMARY KEY (`id_estado_comercial`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `estados_verificacion`
--
ALTER TABLE `estados_verificacion`
  ADD PRIMARY KEY (`id_estado_verificacion`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `historial_asignaciones`
--
ALTER TABLE `historial_asignaciones`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `agente_anterior` (`agente_anterior`),
  ADD KEY `agente_nuevo` (`agente_nuevo`),
  ADD KEY `id_usuario_cambio` (`id_usuario_cambio`),
  ADD KEY `idx_historial_asignacion_entidad` (`tipo_entidad`,`id_entidad`,`fecha`);

--
-- Indices de la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `id_propiedad` (`id_propiedad`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `estado_anterior` (`estado_anterior`),
  ADD KEY `estado_nuevo` (`estado_nuevo`);

--
-- Indices de la tabla `historial_precios`
--
ALTER TABLE `historial_precios`
  ADD PRIMARY KEY (`id_historial_precio`),
  ADD KEY `id_tipo_transaccion` (`id_tipo_transaccion`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `idx_historial_precio_propiedad` (`id_propiedad`,`fecha`);

--
-- Indices de la tabla `intereses`
--
ALTER TABLE `intereses`
  ADD PRIMARY KEY (`id_cliente`,`id_propiedad`),
  ADD KEY `id_propiedad` (`id_propiedad`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `ofertas_cliente`
--
ALTER TABLE `ofertas_cliente`
  ADD PRIMARY KEY (`id_oferta`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_agente` (`id_agente`),
  ADD KEY `id_tipo_transaccion` (`id_tipo_transaccion`),
  ADD KEY `id_revisor` (`id_revisor`),
  ADD KEY `idx_ofertas_estado_fecha` (`estado`,`fecha_vencimiento`),
  ADD KEY `idx_ofertas_propiedad` (`id_propiedad`,`fecha_creacion`);

--
-- Indices de la tabla `preferencias_caracteristicas`
--
ALTER TABLE `preferencias_caracteristicas`
  ADD PRIMARY KEY (`id_preferencia`,`id_caracteristica`),
  ADD KEY `id_caracteristica` (`id_caracteristica`);

--
-- Indices de la tabla `preferencias_cliente`
--
ALTER TABLE `preferencias_cliente`
  ADD PRIMARY KEY (`id_preferencia`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_tipo_transaccion` (`id_tipo_transaccion`),
  ADD KEY `id_tipo_propiedad` (`id_tipo_propiedad`);

--
-- Indices de la tabla `propiedades`
--
ALTER TABLE `propiedades`
  ADD PRIMARY KEY (`id_propiedad`),
  ADD UNIQUE KEY `uq_unidad` (`id_edificio`,`numero_unidad`),
  ADD UNIQUE KEY `ux_propiedad_solicitud` (`id_solicitud`),
  ADD KEY `id_tipo_propiedad` (`id_tipo_propiedad`),
  ADD KEY `id_tipo_transaccion` (`id_tipo_transaccion`),
  ADD KEY `id_estado_verificacion` (`id_estado_verificacion`),
  ADD KEY `id_estado_comercial` (`id_estado_comercial`),
  ADD KEY `id_agente_creador` (`id_agente_creador`),
  ADD KEY `id_admin_verificador` (`id_admin_verificador`),
  ADD KEY `fk_propietario` (`id_propietario`),
  ADD KEY `idx_catalogo` (`id_estado_verificacion`,`id_estado_comercial`,`localidad`);

--
-- Indices de la tabla `propiedades_cerradas`
--
ALTER TABLE `propiedades_cerradas`
  ADD PRIMARY KEY (`id_operacion`),
  ADD KEY `id_cliente_comprador` (`id_cliente_comprador`),
  ADD KEY `id_agente_cierre` (`id_agente_cierre`),
  ADD KEY `idx_cierre_propiedad` (`id_propiedad`),
  ADD KEY `fk_cierre_tipo` (`id_tipo_transaccion`);

--
-- Indices de la tabla `propiedad_caracteristicas`
--
ALTER TABLE `propiedad_caracteristicas`
  ADD PRIMARY KEY (`id_propiedad`,`id_caracteristica`),
  ADD KEY `id_caracteristica` (`id_caracteristica`);

--
-- Indices de la tabla `propiedad_multimedia`
--
ALTER TABLE `propiedad_multimedia`
  ADD PRIMARY KEY (`id_multimedia`),
  ADD KEY `id_propiedad` (`id_propiedad`);

--
-- Indices de la tabla `propiedad_ofertas`
--
ALTER TABLE `propiedad_ofertas`
  ADD PRIMARY KEY (`id_propiedad`,`id_tipo_transaccion`),
  ADD KEY `id_tipo_transaccion` (`id_tipo_transaccion`);

--
-- Indices de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD PRIMARY KEY (`id_publicacion`),
  ADD KEY `id_propiedad` (`id_propiedad`),
  ADD KEY `id_solicitante` (`id_solicitante`),
  ADD KEY `id_revisor` (`id_revisor`);

--
-- Indices de la tabla `requisitos_publicacion`
--
ALTER TABLE `requisitos_publicacion`
  ADD PRIMARY KEY (`id_requisito`),
  ADD UNIQUE KEY `uq_requisito_tipo_nombre` (`id_tipo_propiedad`,`nombre`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id_reserva`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_agente` (`id_agente`),
  ADD KEY `id_tipo_transaccion` (`id_tipo_transaccion`),
  ADD KEY `id_oferta` (`id_oferta`),
  ADD KEY `id_autorizador` (`id_autorizador`),
  ADD KEY `idx_reservas_estado_limite` (`estado`,`fecha_limite`),
  ADD KEY `idx_reservas_propiedad` (`id_propiedad`,`estado`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `roles_cliente`
--
ALTER TABLE `roles_cliente`
  ADD PRIMARY KEY (`id_cliente`,`tipo_rol`);

--
-- Indices de la tabla `schema_version`
--
ALTER TABLE `schema_version`
  ADD PRIMARY KEY (`version`);

--
-- Indices de la tabla `solicitudes_publicacion`
--
ALTER TABLE `solicitudes_publicacion`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `idx_solicitud_cliente_fecha` (`id_cliente`,`fecha_solicitud`),
  ADD KEY `idx_solicitud_estado` (`id_estado_verificacion`),
  ADD KEY `fk_solicitud_tipo` (`id_tipo_propiedad`),
  ADD KEY `fk_solicitud_operacion` (`id_tipo_transaccion`);

--
-- Indices de la tabla `solicitud_multimedia`
--
ALTER TABLE `solicitud_multimedia`
  ADD PRIMARY KEY (`id_multimedia`),
  ADD KEY `idx_solicitud_multimedia` (`id_solicitud`);

--
-- Indices de la tabla `tareas_seguimiento`
--
ALTER TABLE `tareas_seguimiento`
  ADD PRIMARY KEY (`id_tarea`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_propiedad` (`id_propiedad`),
  ADD KEY `idx_tareas_agente_estado_fecha` (`id_agente`,`estado`,`fecha_limite`);

--
-- Indices de la tabla `tipos_propiedad`
--
ALTER TABLE `tipos_propiedad`
  ADD PRIMARY KEY (`id_tipo_propiedad`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tipos_transaccion`
--
ALTER TABLE `tipos_transaccion`
  ADD PRIMARY KEY (`id_tipo_transaccion`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `visitas`
--
ALTER TABLE `visitas`
  ADD PRIMARY KEY (`id_visita`),
  ADD KEY `id_propiedad` (`id_propiedad`),
  ADD KEY `idx_visita_agente_fecha` (`id_agente`,`fecha_hora`),
  ADD KEY `idx_visita_cliente_fecha` (`id_cliente`,`fecha_hora`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alquileres`
--
ALTER TABLE `alquileres`
  MODIFY `id_alquiler` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `auditoria_cambios`
--
ALTER TABLE `auditoria_cambios`
  MODIFY `id_cambio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `caracteristicas`
--
ALTER TABLE `caracteristicas`
  MODIFY `id_caracteristica` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `contactos`
--
ALTER TABLE `contactos`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `contactos_adicionales_cliente`
--
ALTER TABLE `contactos_adicionales_cliente`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `convenios`
--
ALTER TABLE `convenios`
  MODIFY `id_convenio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `edificios`
--
ALTER TABLE `edificios`
  MODIFY `id_edificio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estados_comerciales`
--
ALTER TABLE `estados_comerciales`
  MODIFY `id_estado_comercial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `estados_verificacion`
--
ALTER TABLE `estados_verificacion`
  MODIFY `id_estado_verificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `historial_asignaciones`
--
ALTER TABLE `historial_asignaciones`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_precios`
--
ALTER TABLE `historial_precios`
  MODIFY `id_historial_precio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ofertas_cliente`
--
ALTER TABLE `ofertas_cliente`
  MODIFY `id_oferta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `preferencias_cliente`
--
ALTER TABLE `preferencias_cliente`
  MODIFY `id_preferencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `propiedades`
--
ALTER TABLE `propiedades`
  MODIFY `id_propiedad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `propiedades_cerradas`
--
ALTER TABLE `propiedades_cerradas`
  MODIFY `id_operacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `propiedad_multimedia`
--
ALTER TABLE `propiedad_multimedia`
  MODIFY `id_multimedia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  MODIFY `id_publicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `requisitos_publicacion`
--
ALTER TABLE `requisitos_publicacion`
  MODIFY `id_requisito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id_reserva` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `solicitudes_publicacion`
--
ALTER TABLE `solicitudes_publicacion`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `solicitud_multimedia`
--
ALTER TABLE `solicitud_multimedia`
  MODIFY `id_multimedia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tareas_seguimiento`
--
ALTER TABLE `tareas_seguimiento`
  MODIFY `id_tarea` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_propiedad`
--
ALTER TABLE `tipos_propiedad`
  MODIFY `id_tipo_propiedad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tipos_transaccion`
--
ALTER TABLE `tipos_transaccion`
  MODIFY `id_tipo_transaccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `visitas`
--
ALTER TABLE `visitas`
  MODIFY `id_visita` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alquileres`
--
ALTER TABLE `alquileres`
  ADD CONSTRAINT `fk_alquiler_agente` FOREIGN KEY (`id_agente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_alquiler_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_alquiler_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`);

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `auditoria_cambios`
--
ALTER TABLE `auditoria_cambios`
  ADD CONSTRAINT `auditoria_cambios_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `auditoria` (`id_evento`);

--
-- Filtros para la tabla `checklist_publicacion`
--
ALTER TABLE `checklist_publicacion`
  ADD CONSTRAINT `checklist_publicacion_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`) ON DELETE CASCADE,
  ADD CONSTRAINT `checklist_publicacion_ibfk_2` FOREIGN KEY (`id_requisito`) REFERENCES `requisitos_publicacion` (`id_requisito`),
  ADD CONSTRAINT `checklist_publicacion_ibfk_3` FOREIGN KEY (`id_documento`) REFERENCES `documentos` (`id_documento`),
  ADD CONSTRAINT `checklist_publicacion_ibfk_4` FOREIGN KEY (`id_revisor`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `clientes_ibfk_2` FOREIGN KEY (`id_agente_asignado`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `configuracion_alertas`
--
ALTER TABLE `configuracion_alertas`
  ADD CONSTRAINT `configuracion_alertas_ibfk_1` FOREIGN KEY (`actualizado_por`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `contactos`
--
ALTER TABLE `contactos`
  ADD CONSTRAINT `contactos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `contactos_ibfk_2` FOREIGN KEY (`id_agente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `contactos_ibfk_3` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`);

--
-- Filtros para la tabla `contactos_adicionales_cliente`
--
ALTER TABLE `contactos_adicionales_cliente`
  ADD CONSTRAINT `contactos_adicionales_cliente_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `convenios`
--
ALTER TABLE `convenios`
  ADD CONSTRAINT `convenios_ibfk_1` FOREIGN KEY (`id_propietario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `convenios_ibfk_2` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`);

--
-- Filtros para la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`),
  ADD CONSTRAINT `documentos_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `historial_asignaciones`
--
ALTER TABLE `historial_asignaciones`
  ADD CONSTRAINT `historial_asignaciones_ibfk_1` FOREIGN KEY (`agente_anterior`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `historial_asignaciones_ibfk_2` FOREIGN KEY (`agente_nuevo`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `historial_asignaciones_ibfk_3` FOREIGN KEY (`id_usuario_cambio`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  ADD CONSTRAINT `historial_estados_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`),
  ADD CONSTRAINT `historial_estados_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `historial_estados_ibfk_3` FOREIGN KEY (`estado_anterior`) REFERENCES `estados_comerciales` (`id_estado_comercial`),
  ADD CONSTRAINT `historial_estados_ibfk_4` FOREIGN KEY (`estado_nuevo`) REFERENCES `estados_comerciales` (`id_estado_comercial`);

--
-- Filtros para la tabla `historial_precios`
--
ALTER TABLE `historial_precios`
  ADD CONSTRAINT `historial_precios_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`),
  ADD CONSTRAINT `historial_precios_ibfk_2` FOREIGN KEY (`id_tipo_transaccion`) REFERENCES `tipos_transaccion` (`id_tipo_transaccion`),
  ADD CONSTRAINT `historial_precios_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `intereses`
--
ALTER TABLE `intereses`
  ADD CONSTRAINT `intereses_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `intereses_ibfk_2` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `ofertas_cliente`
--
ALTER TABLE `ofertas_cliente`
  ADD CONSTRAINT `ofertas_cliente_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`),
  ADD CONSTRAINT `ofertas_cliente_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `ofertas_cliente_ibfk_3` FOREIGN KEY (`id_agente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `ofertas_cliente_ibfk_4` FOREIGN KEY (`id_tipo_transaccion`) REFERENCES `tipos_transaccion` (`id_tipo_transaccion`),
  ADD CONSTRAINT `ofertas_cliente_ibfk_5` FOREIGN KEY (`id_revisor`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `preferencias_caracteristicas`
--
ALTER TABLE `preferencias_caracteristicas`
  ADD CONSTRAINT `preferencias_caracteristicas_ibfk_1` FOREIGN KEY (`id_preferencia`) REFERENCES `preferencias_cliente` (`id_preferencia`),
  ADD CONSTRAINT `preferencias_caracteristicas_ibfk_2` FOREIGN KEY (`id_caracteristica`) REFERENCES `caracteristicas` (`id_caracteristica`);

--
-- Filtros para la tabla `preferencias_cliente`
--
ALTER TABLE `preferencias_cliente`
  ADD CONSTRAINT `preferencias_cliente_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `preferencias_cliente_ibfk_2` FOREIGN KEY (`id_tipo_transaccion`) REFERENCES `tipos_transaccion` (`id_tipo_transaccion`),
  ADD CONSTRAINT `preferencias_cliente_ibfk_3` FOREIGN KEY (`id_tipo_propiedad`) REFERENCES `tipos_propiedad` (`id_tipo_propiedad`);

--
-- Filtros para la tabla `propiedades`
--
ALTER TABLE `propiedades`
  ADD CONSTRAINT `fk_edificio` FOREIGN KEY (`id_edificio`) REFERENCES `edificios` (`id_edificio`),
  ADD CONSTRAINT `fk_propietario` FOREIGN KEY (`id_propietario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `propiedades_ibfk_1` FOREIGN KEY (`id_tipo_propiedad`) REFERENCES `tipos_propiedad` (`id_tipo_propiedad`),
  ADD CONSTRAINT `propiedades_ibfk_2` FOREIGN KEY (`id_tipo_transaccion`) REFERENCES `tipos_transaccion` (`id_tipo_transaccion`),
  ADD CONSTRAINT `propiedades_ibfk_3` FOREIGN KEY (`id_estado_verificacion`) REFERENCES `estados_verificacion` (`id_estado_verificacion`),
  ADD CONSTRAINT `propiedades_ibfk_4` FOREIGN KEY (`id_estado_comercial`) REFERENCES `estados_comerciales` (`id_estado_comercial`),
  ADD CONSTRAINT `propiedades_ibfk_5` FOREIGN KEY (`id_agente_creador`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `propiedades_ibfk_6` FOREIGN KEY (`id_admin_verificador`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `propiedades_cerradas`
--
ALTER TABLE `propiedades_cerradas`
  ADD CONSTRAINT `fk_cierre_tipo` FOREIGN KEY (`id_tipo_transaccion`) REFERENCES `tipos_transaccion` (`id_tipo_transaccion`),
  ADD CONSTRAINT `propiedades_cerradas_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`),
  ADD CONSTRAINT `propiedades_cerradas_ibfk_2` FOREIGN KEY (`id_cliente_comprador`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `propiedades_cerradas_ibfk_3` FOREIGN KEY (`id_agente_cierre`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `propiedad_caracteristicas`
--
ALTER TABLE `propiedad_caracteristicas`
  ADD CONSTRAINT `propiedad_caracteristicas_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`) ON DELETE CASCADE,
  ADD CONSTRAINT `propiedad_caracteristicas_ibfk_2` FOREIGN KEY (`id_caracteristica`) REFERENCES `caracteristicas` (`id_caracteristica`);

--
-- Filtros para la tabla `propiedad_multimedia`
--
ALTER TABLE `propiedad_multimedia`
  ADD CONSTRAINT `propiedad_multimedia_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`) ON DELETE CASCADE;

--
-- Filtros para la tabla `propiedad_ofertas`
--
ALTER TABLE `propiedad_ofertas`
  ADD CONSTRAINT `propiedad_ofertas_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`),
  ADD CONSTRAINT `propiedad_ofertas_ibfk_2` FOREIGN KEY (`id_tipo_transaccion`) REFERENCES `tipos_transaccion` (`id_tipo_transaccion`);

--
-- Filtros para la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD CONSTRAINT `publicaciones_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`),
  ADD CONSTRAINT `publicaciones_ibfk_2` FOREIGN KEY (`id_solicitante`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `publicaciones_ibfk_3` FOREIGN KEY (`id_revisor`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `requisitos_publicacion`
--
ALTER TABLE `requisitos_publicacion`
  ADD CONSTRAINT `requisitos_publicacion_ibfk_1` FOREIGN KEY (`id_tipo_propiedad`) REFERENCES `tipos_propiedad` (`id_tipo_propiedad`);

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`),
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `reservas_ibfk_3` FOREIGN KEY (`id_agente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `reservas_ibfk_4` FOREIGN KEY (`id_tipo_transaccion`) REFERENCES `tipos_transaccion` (`id_tipo_transaccion`),
  ADD CONSTRAINT `reservas_ibfk_5` FOREIGN KEY (`id_oferta`) REFERENCES `ofertas_cliente` (`id_oferta`),
  ADD CONSTRAINT `reservas_ibfk_6` FOREIGN KEY (`id_autorizador`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `roles_cliente`
--
ALTER TABLE `roles_cliente`
  ADD CONSTRAINT `roles_cliente_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`);

--
-- Filtros para la tabla `solicitudes_publicacion`
--
ALTER TABLE `solicitudes_publicacion`
  ADD CONSTRAINT `fk_solicitud_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_solicitud_operacion` FOREIGN KEY (`id_tipo_transaccion`) REFERENCES `tipos_transaccion` (`id_tipo_transaccion`),
  ADD CONSTRAINT `fk_solicitud_tipo` FOREIGN KEY (`id_tipo_propiedad`) REFERENCES `tipos_propiedad` (`id_tipo_propiedad`),
  ADD CONSTRAINT `fk_solicitud_verificacion` FOREIGN KEY (`id_estado_verificacion`) REFERENCES `estados_verificacion` (`id_estado_verificacion`);

--
-- Filtros para la tabla `solicitud_multimedia`
--
ALTER TABLE `solicitud_multimedia`
  ADD CONSTRAINT `fk_solicitud_multimedia` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes_publicacion` (`id_solicitud`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tareas_seguimiento`
--
ALTER TABLE `tareas_seguimiento`
  ADD CONSTRAINT `tareas_seguimiento_ibfk_1` FOREIGN KEY (`id_agente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `tareas_seguimiento_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `tareas_seguimiento_ibfk_3` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);

--
-- Filtros para la tabla `visitas`
--
ALTER TABLE `visitas`
  ADD CONSTRAINT `visitas_ibfk_1` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedades` (`id_propiedad`),
  ADD CONSTRAINT `visitas_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `visitas_ibfk_3` FOREIGN KEY (`id_agente`) REFERENCES `usuarios` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

--
-- PostgreSQL database dump
--

\restrict OTbQRkiXldTBCzZlrXycbicvdqLTEY0giEhMFAoQqdVum2jQAhFXj1FNTJgewSO

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: accesorios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.accesorios (
    id integer NOT NULL,
    producto_id integer NOT NULL,
    subtipo_accesorio character varying(100) NOT NULL,
    talla character varying(20),
    color character varying(50),
    material character varying(100),
    marca character varying(100),
    certificacion character varying(100),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.accesorios OWNER TO postgres;

--
-- Name: accesorios_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.accesorios_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.accesorios_id_seq OWNER TO postgres;

--
-- Name: accesorios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.accesorios_id_seq OWNED BY public.accesorios.id;


--
-- Name: alertas_stock; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.alertas_stock (
    id integer NOT NULL,
    producto_id integer,
    tipo_alerta character varying(20),
    stock_actual integer NOT NULL,
    stock_minimo integer NOT NULL,
    leida boolean DEFAULT false,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.alertas_stock OWNER TO postgres;

--
-- Name: alertas_stock_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.alertas_stock_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.alertas_stock_id_seq OWNER TO postgres;

--
-- Name: alertas_stock_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.alertas_stock_id_seq OWNED BY public.alertas_stock.id;


--
-- Name: bitacora_sistema; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bitacora_sistema (
    id integer NOT NULL,
    usuario_id integer,
    accion character varying(100) NOT NULL,
    tabla_afectada character varying(50),
    registro_id integer,
    detalles jsonb,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.bitacora_sistema OWNER TO postgres;

--
-- Name: bitacora_sistema_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.bitacora_sistema_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bitacora_sistema_id_seq OWNER TO postgres;

--
-- Name: bitacora_sistema_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.bitacora_sistema_id_seq OWNED BY public.bitacora_sistema.id;


--
-- Name: categorias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.categorias (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion text,
    estado boolean DEFAULT true,
    tipo_producto_id integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.categorias OWNER TO postgres;

--
-- Name: categorias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.categorias_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.categorias_id_seq OWNER TO postgres;

--
-- Name: categorias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.categorias_id_seq OWNED BY public.categorias.id;


--
-- Name: clientes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.clientes (
    id integer NOT NULL,
    cedula_rif character varying(20) NOT NULL,
    nombre_completo character varying(200) NOT NULL,
    email character varying(100),
    telefono_principal character varying(15),
    telefono_alternativo character varying(15),
    direccion text,
    fecha_registro date DEFAULT CURRENT_DATE,
    estado boolean DEFAULT true,
    usuario_id integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.clientes OWNER TO postgres;

--
-- Name: clientes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.clientes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.clientes_id_seq OWNER TO postgres;

--
-- Name: clientes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.clientes_id_seq OWNED BY public.clientes.id;


--
-- Name: compras; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.compras (
    id integer NOT NULL,
    codigo_compra character varying(20) NOT NULL,
    proveedor_id integer,
    usuario_id integer,
    subtotal numeric(10,2) NOT NULL,
    iva numeric(10,2) DEFAULT 0,
    total numeric(10,2) NOT NULL,
    estado_compra character varying(20) DEFAULT 'PENDIENTE'::character varying,
    fecha_estimada_entrega date,
    observaciones text,
    activa boolean DEFAULT true,
    notas_incidencia text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.compras OWNER TO postgres;

--
-- Name: compras_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.compras_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.compras_id_seq OWNER TO postgres;

--
-- Name: compras_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.compras_id_seq OWNED BY public.compras.id;


--
-- Name: configuracion_integraciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.configuracion_integraciones (
    clave character varying(80) NOT NULL,
    valor text DEFAULT ''::text NOT NULL,
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.configuracion_integraciones OWNER TO postgres;

--
-- Name: detalle_compras; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.detalle_compras (
    id integer NOT NULL,
    compra_id integer,
    producto_id integer,
    cantidad integer NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.detalle_compras OWNER TO postgres;

--
-- Name: detalle_compras_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.detalle_compras_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.detalle_compras_id_seq OWNER TO postgres;

--
-- Name: detalle_compras_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.detalle_compras_id_seq OWNED BY public.detalle_compras.id;


--
-- Name: detalle_pedidos_online; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.detalle_pedidos_online (
    id integer NOT NULL,
    pedido_id integer,
    producto_id integer,
    cantidad integer NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.detalle_pedidos_online OWNER TO postgres;

--
-- Name: detalle_pedidos_online_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.detalle_pedidos_online_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.detalle_pedidos_online_id_seq OWNER TO postgres;

--
-- Name: detalle_pedidos_online_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.detalle_pedidos_online_id_seq OWNED BY public.detalle_pedidos_online.id;


--
-- Name: detalle_ventas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.detalle_ventas (
    id integer NOT NULL,
    venta_id integer,
    producto_id integer,
    cantidad integer NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.detalle_ventas OWNER TO postgres;

--
-- Name: detalle_ventas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.detalle_ventas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.detalle_ventas_id_seq OWNER TO postgres;

--
-- Name: detalle_ventas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.detalle_ventas_id_seq OWNED BY public.detalle_ventas.id;


--
-- Name: devoluciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.devoluciones (
    id integer NOT NULL,
    codigo_devolucion character varying(20) NOT NULL,
    venta_id integer,
    pedido_id integer,
    cliente_id integer,
    producto_id integer,
    cantidad integer NOT NULL,
    motivo text NOT NULL,
    estado_devolucion character varying(20) DEFAULT 'PENDIENTE'::character varying,
    tipo_reintegro character varying(20),
    observaciones text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.devoluciones OWNER TO postgres;

--
-- Name: devoluciones_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.devoluciones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.devoluciones_id_seq OWNER TO postgres;

--
-- Name: devoluciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.devoluciones_id_seq OWNED BY public.devoluciones.id;


--
-- Name: empresa_configuracion; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.empresa_configuracion (
    id integer NOT NULL,
    nombre_empresa character varying(200) NOT NULL,
    rif character varying(20) NOT NULL,
    direccion text,
    telefono character varying(15),
    email character varying(100),
    iva_porcentaje numeric(5,2) DEFAULT 16.00,
    actualizado_por integer,
    fecha_actualizacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.empresa_configuracion OWNER TO postgres;

--
-- Name: empresa_configuracion_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.empresa_configuracion_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.empresa_configuracion_id_seq OWNER TO postgres;

--
-- Name: empresa_configuracion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.empresa_configuracion_id_seq OWNED BY public.empresa_configuracion.id;


--
-- Name: incidencias_soporte; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.incidencias_soporte (
    id integer NOT NULL,
    codigo_incidencia character varying(20) NOT NULL,
    cliente_id integer,
    usuario_id integer,
    descripcion text NOT NULL,
    urgencia character varying(20) DEFAULT 'media'::character varying,
    modulo_sistema character varying(50),
    estado character varying(20) DEFAULT 'pendiente'::character varying,
    asignado_a integer,
    notas_solucion text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.incidencias_soporte OWNER TO postgres;

--
-- Name: incidencias_soporte_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.incidencias_soporte_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.incidencias_soporte_id_seq OWNER TO postgres;

--
-- Name: incidencias_soporte_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.incidencias_soporte_id_seq OWNED BY public.incidencias_soporte.id;


--
-- Name: metodos_pago; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.metodos_pago (
    id integer NOT NULL,
    nombre character varying(50) NOT NULL,
    descripcion text,
    estado boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.metodos_pago OWNER TO postgres;

--
-- Name: metodos_pago_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.metodos_pago_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.metodos_pago_id_seq OWNER TO postgres;

--
-- Name: metodos_pago_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.metodos_pago_id_seq OWNED BY public.metodos_pago.id;


--
-- Name: movimientos_inventario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.movimientos_inventario (
    id integer NOT NULL,
    producto_id integer,
    tipo_movimiento character varying(20) NOT NULL,
    cantidad integer NOT NULL,
    stock_anterior integer NOT NULL,
    stock_actual integer NOT NULL,
    motivo text,
    referencia character varying(100),
    usuario_id integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.movimientos_inventario OWNER TO postgres;

--
-- Name: movimientos_inventario_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.movimientos_inventario_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.movimientos_inventario_id_seq OWNER TO postgres;

--
-- Name: movimientos_inventario_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.movimientos_inventario_id_seq OWNED BY public.movimientos_inventario.id;


--
-- Name: notificaciones_vendedor; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notificaciones_vendedor (
    id integer NOT NULL,
    pedido_id integer NOT NULL,
    titulo character varying(200) NOT NULL,
    mensaje text,
    tipo character varying(40) DEFAULT 'pedido'::character varying,
    leida boolean DEFAULT false,
    usuario_id integer,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.notificaciones_vendedor OWNER TO postgres;

--
-- Name: notificaciones_vendedor_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notificaciones_vendedor_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notificaciones_vendedor_id_seq OWNER TO postgres;

--
-- Name: notificaciones_vendedor_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notificaciones_vendedor_id_seq OWNED BY public.notificaciones_vendedor.id;


--
-- Name: pedidos_online; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pedidos_online (
    id integer NOT NULL,
    codigo_pedido character varying(20) NOT NULL,
    cliente_id integer,
    subtotal numeric(10,2) NOT NULL,
    iva numeric(10,2) DEFAULT 0,
    total numeric(10,2) NOT NULL,
    estado_pedido character varying(20) DEFAULT 'PENDIENTE'::character varying,
    metodo_pago_id integer,
    direccion_entrega text,
    telefono_contacto character varying(15),
    observaciones text,
    canal_comunicacion character varying(30) DEFAULT 'whatsapp'::character varying,
    tipo_entrega character varying(20) DEFAULT 'tienda'::character varying,
    referencia_pago text,
    vendedor_asignado_id integer,
    fecha_asignacion timestamp without time zone,
    fecha_contacto timestamp without time zone,
    fecha_pago timestamp without time zone,
    intentos_contacto integer DEFAULT 0,
    venta_id integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.pedidos_online OWNER TO postgres;

--
-- Name: pedidos_online_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pedidos_online_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pedidos_online_id_seq OWNER TO postgres;

--
-- Name: pedidos_online_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pedidos_online_id_seq OWNED BY public.pedidos_online.id;


--
-- Name: producto_imagenes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.producto_imagenes (
    id integer NOT NULL,
    producto_id integer NOT NULL,
    imagen_url character varying(500) NOT NULL,
    es_principal boolean DEFAULT false,
    orden integer DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.producto_imagenes OWNER TO postgres;

--
-- Name: producto_imagenes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.producto_imagenes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.producto_imagenes_id_seq OWNER TO postgres;

--
-- Name: producto_imagenes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.producto_imagenes_id_seq OWNED BY public.producto_imagenes.id;


--
-- Name: producto_promociones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.producto_promociones (
    id integer NOT NULL,
    producto_id integer,
    promocion_id integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.producto_promociones OWNER TO postgres;

--
-- Name: producto_promociones_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.producto_promociones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.producto_promociones_id_seq OWNER TO postgres;

--
-- Name: producto_promociones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.producto_promociones_id_seq OWNED BY public.producto_promociones.id;


--
-- Name: producto_proveedor; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.producto_proveedor (
    id integer NOT NULL,
    producto_id integer NOT NULL,
    proveedor_id integer NOT NULL,
    precio_compra numeric(10,2) NOT NULL,
    sku_proveedor character varying(50),
    tiempo_entrega_dias integer,
    es_principal boolean DEFAULT false,
    activo boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.producto_proveedor OWNER TO postgres;

--
-- Name: producto_proveedor_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.producto_proveedor_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.producto_proveedor_id_seq OWNER TO postgres;

--
-- Name: producto_proveedor_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.producto_proveedor_id_seq OWNED BY public.producto_proveedor.id;


--
-- Name: productos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.productos (
    id integer NOT NULL,
    codigo_interno character varying(50) NOT NULL,
    nombre character varying(200) NOT NULL,
    descripcion text,
    categoria_id integer,
    precio_compra numeric(10,2) NOT NULL,
    precio_venta numeric(10,2) NOT NULL,
    stock_actual integer DEFAULT 0,
    stock_minimo integer DEFAULT 5,
    stock_maximo integer DEFAULT 100,
    proveedor_id integer,
    estado boolean DEFAULT true,
    tipo_id integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.productos OWNER TO postgres;

--
-- Name: productos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.productos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.productos_id_seq OWNER TO postgres;

--
-- Name: productos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.productos_id_seq OWNED BY public.productos.id;


--
-- Name: promociones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.promociones (
    id integer NOT NULL,
    nombre character varying(200) NOT NULL,
    descripcion text,
    tipo_promocion character varying(20),
    valor numeric(10,2),
    fecha_inicio date NOT NULL,
    fecha_fin date NOT NULL,
    estado boolean DEFAULT true,
    imagen_url character varying(500),
    tipo_imagen character varying(20) DEFAULT 'auto'::character varying,
    imagen_banco_key character varying(50),
    color_personalizado character varying(20) DEFAULT '#1F9166'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.promociones OWNER TO postgres;

--
-- Name: promociones_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.promociones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.promociones_id_seq OWNER TO postgres;

--
-- Name: promociones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.promociones_id_seq OWNED BY public.promociones.id;


--
-- Name: proveedores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.proveedores (
    id integer NOT NULL,
    razon_social character varying(200) NOT NULL,
    rif character varying(20) NOT NULL,
    persona_contacto character varying(100),
    telefono_principal character varying(15),
    telefono_alternativo character varying(15),
    email character varying(100),
    direccion text,
    productos_suministrados text,
    estado boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.proveedores OWNER TO postgres;

--
-- Name: proveedores_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.proveedores_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.proveedores_id_seq OWNER TO postgres;

--
-- Name: proveedores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.proveedores_id_seq OWNED BY public.proveedores.id;


--
-- Name: repuestos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.repuestos (
    id integer NOT NULL,
    producto_id integer NOT NULL,
    categoria_tecnica character varying(100) NOT NULL,
    marca_compatible character varying(100),
    modelo_compatible character varying(100),
    anio_compatible character varying(50),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.repuestos OWNER TO postgres;

--
-- Name: repuestos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.repuestos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.repuestos_id_seq OWNER TO postgres;

--
-- Name: repuestos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.repuestos_id_seq OWNED BY public.repuestos.id;


--
-- Name: reservas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reservas (
    id integer NOT NULL,
    codigo_reserva character varying(20) NOT NULL,
    cliente_id integer,
    producto_id integer,
    cantidad integer NOT NULL,
    fecha_reserva date NOT NULL,
    fecha_limite date NOT NULL,
    estado_reserva character varying(20) DEFAULT 'ACTIVA'::character varying,
    observaciones text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.reservas OWNER TO postgres;

--
-- Name: reservas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.reservas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reservas_id_seq OWNER TO postgres;

--
-- Name: reservas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reservas_id_seq OWNED BY public.reservas.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id integer NOT NULL,
    nombre character varying(50) NOT NULL,
    descripcion text,
    permisos jsonb,
    estado boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: seguimiento_incidencias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.seguimiento_incidencias (
    id integer NOT NULL,
    incidencia_id integer NOT NULL,
    usuario_id integer,
    accion character varying(100) NOT NULL,
    descripcion text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.seguimiento_incidencias OWNER TO postgres;

--
-- Name: seguimiento_incidencias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.seguimiento_incidencias_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.seguimiento_incidencias_id_seq OWNER TO postgres;

--
-- Name: seguimiento_incidencias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.seguimiento_incidencias_id_seq OWNED BY public.seguimiento_incidencias.id;


--
-- Name: tipos_producto; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tipos_producto (
    id integer NOT NULL,
    nombre character varying(50) NOT NULL,
    descripcion text,
    estado boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.tipos_producto OWNER TO postgres;

--
-- Name: tipos_producto_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tipos_producto_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tipos_producto_id_seq OWNER TO postgres;

--
-- Name: tipos_producto_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tipos_producto_id_seq OWNED BY public.tipos_producto.id;


--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuarios (
    id integer NOT NULL,
    username character varying(50) NOT NULL,
    email character varying(100) NOT NULL,
    password_hash character varying(255) NOT NULL,
    nombre_completo character varying(200) NOT NULL,
    rol_id integer,
    cliente_id integer,
    estado boolean DEFAULT true,
    ultimo_acceso timestamp without time zone,
    intentos_fallidos integer DEFAULT 0,
    bloqueado_hasta timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.usuarios OWNER TO postgres;

--
-- Name: usuarios_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.usuarios_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.usuarios_id_seq OWNER TO postgres;

--
-- Name: usuarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.usuarios_id_seq OWNED BY public.usuarios.id;


--
-- Name: vehiculos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.vehiculos (
    id integer NOT NULL,
    producto_id integer NOT NULL,
    marca character varying(100) NOT NULL,
    modelo character varying(100) NOT NULL,
    anio character varying(4),
    cilindrada character varying(50),
    color character varying(50),
    kilometraje integer DEFAULT 0,
    tipo_vehiculo character varying(50),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.vehiculos OWNER TO postgres;

--
-- Name: vehiculos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.vehiculos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.vehiculos_id_seq OWNER TO postgres;

--
-- Name: vehiculos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.vehiculos_id_seq OWNED BY public.vehiculos.id;


--
-- Name: ventas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ventas (
    id integer NOT NULL,
    codigo_venta character varying(20) NOT NULL,
    cliente_id integer,
    usuario_id integer,
    metodo_pago_id integer,
    subtotal numeric(10,2) NOT NULL,
    iva numeric(10,2) DEFAULT 0,
    total numeric(10,2) NOT NULL,
    estado_venta character varying(20) DEFAULT 'COMPLETADA'::character varying,
    observaciones text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.ventas OWNER TO postgres;

--
-- Name: ventas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ventas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ventas_id_seq OWNER TO postgres;

--
-- Name: ventas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ventas_id_seq OWNED BY public.ventas.id;


--
-- Name: accesorios id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.accesorios ALTER COLUMN id SET DEFAULT nextval('public.accesorios_id_seq'::regclass);


--
-- Name: alertas_stock id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.alertas_stock ALTER COLUMN id SET DEFAULT nextval('public.alertas_stock_id_seq'::regclass);


--
-- Name: bitacora_sistema id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_sistema ALTER COLUMN id SET DEFAULT nextval('public.bitacora_sistema_id_seq'::regclass);


--
-- Name: categorias id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categorias ALTER COLUMN id SET DEFAULT nextval('public.categorias_id_seq'::regclass);


--
-- Name: clientes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes ALTER COLUMN id SET DEFAULT nextval('public.clientes_id_seq'::regclass);


--
-- Name: compras id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.compras ALTER COLUMN id SET DEFAULT nextval('public.compras_id_seq'::regclass);


--
-- Name: detalle_compras id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_compras ALTER COLUMN id SET DEFAULT nextval('public.detalle_compras_id_seq'::regclass);


--
-- Name: detalle_pedidos_online id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_pedidos_online ALTER COLUMN id SET DEFAULT nextval('public.detalle_pedidos_online_id_seq'::regclass);


--
-- Name: detalle_ventas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_ventas ALTER COLUMN id SET DEFAULT nextval('public.detalle_ventas_id_seq'::regclass);


--
-- Name: devoluciones id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.devoluciones ALTER COLUMN id SET DEFAULT nextval('public.devoluciones_id_seq'::regclass);


--
-- Name: empresa_configuracion id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.empresa_configuracion ALTER COLUMN id SET DEFAULT nextval('public.empresa_configuracion_id_seq'::regclass);


--
-- Name: incidencias_soporte id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_soporte ALTER COLUMN id SET DEFAULT nextval('public.incidencias_soporte_id_seq'::regclass);


--
-- Name: metodos_pago id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.metodos_pago ALTER COLUMN id SET DEFAULT nextval('public.metodos_pago_id_seq'::regclass);


--
-- Name: movimientos_inventario id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.movimientos_inventario ALTER COLUMN id SET DEFAULT nextval('public.movimientos_inventario_id_seq'::regclass);


--
-- Name: notificaciones_vendedor id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notificaciones_vendedor ALTER COLUMN id SET DEFAULT nextval('public.notificaciones_vendedor_id_seq'::regclass);


--
-- Name: pedidos_online id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos_online ALTER COLUMN id SET DEFAULT nextval('public.pedidos_online_id_seq'::regclass);


--
-- Name: producto_imagenes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_imagenes ALTER COLUMN id SET DEFAULT nextval('public.producto_imagenes_id_seq'::regclass);


--
-- Name: producto_promociones id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_promociones ALTER COLUMN id SET DEFAULT nextval('public.producto_promociones_id_seq'::regclass);


--
-- Name: producto_proveedor id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_proveedor ALTER COLUMN id SET DEFAULT nextval('public.producto_proveedor_id_seq'::regclass);


--
-- Name: productos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos ALTER COLUMN id SET DEFAULT nextval('public.productos_id_seq'::regclass);


--
-- Name: promociones id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promociones ALTER COLUMN id SET DEFAULT nextval('public.promociones_id_seq'::regclass);


--
-- Name: proveedores id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.proveedores ALTER COLUMN id SET DEFAULT nextval('public.proveedores_id_seq'::regclass);


--
-- Name: repuestos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.repuestos ALTER COLUMN id SET DEFAULT nextval('public.repuestos_id_seq'::regclass);


--
-- Name: reservas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservas ALTER COLUMN id SET DEFAULT nextval('public.reservas_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: seguimiento_incidencias id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seguimiento_incidencias ALTER COLUMN id SET DEFAULT nextval('public.seguimiento_incidencias_id_seq'::regclass);


--
-- Name: tipos_producto id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipos_producto ALTER COLUMN id SET DEFAULT nextval('public.tipos_producto_id_seq'::regclass);


--
-- Name: usuarios id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id SET DEFAULT nextval('public.usuarios_id_seq'::regclass);


--
-- Name: vehiculos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vehiculos ALTER COLUMN id SET DEFAULT nextval('public.vehiculos_id_seq'::regclass);


--
-- Name: ventas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ventas ALTER COLUMN id SET DEFAULT nextval('public.ventas_id_seq'::regclass);


--
-- Data for Name: accesorios; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.accesorios (id, producto_id, subtipo_accesorio, talla, color, material, marca, certificacion, created_at) FROM stdin;
1	1	Chaqueta de cuero	L	Negro,Rojo,Azul	Cuero	lS2	DOT	2026-03-15 20:32:50.314258
\.


--
-- Data for Name: alertas_stock; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.alertas_stock (id, producto_id, tipo_alerta, stock_actual, stock_minimo, leida, created_at) FROM stdin;
\.


--
-- Data for Name: bitacora_sistema; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bitacora_sistema (id, usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address, user_agent, created_at) FROM stdin;
1	1	CREAR_CATEGORIA	categorias	1	{"nombre": "Ropa", "tipo_id": null, "descripcion": "xxx", "tipo_producto": "ACCESORIO"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-15 20:21:45.070399
2	1	CREAR_PRODUCTO	productos	1	{"tipo": "accesorio", "codigo": "AC-449461-311", "nombre": "chaqueta", "stock_inicial": 10}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-15 20:32:50.314258
3	1	ACTUALIZAR_PRODUCTO	productos	1	{"nombre": "chaqueta", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-03-15 20:35:20.42132
4	1	ACTUALIZAR_PRODUCTO	productos	1	{"nombre": "chaqueta", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-03-15 21:05:33.466675
5	1	CREAR_CATEGORIA	categorias	2	{"nombre": "Motor", "tipo_id": 4, "descripcion": "xxx", "tipo_producto": "REPUESTO"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-15 21:17:04.013309
6	1	CREAR_PRODUCTO	productos	2	{"tipo": "repuesto", "codigo": "RP-362881-579", "nombre": "Freno", "stock_inicial": 10}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-15 21:17:38.688799
7	1	ACTUALIZAR_PRODUCTO	productos	2	{"nombre": "Freno", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-03-15 21:18:02.377512
8	1	CREAR_CATEGORIA	categorias	3	{"nombre": "sincronica", "tipo_id": 5, "descripcion": "xxx", "tipo_producto": "MOTO"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-15 21:23:57.317667
9	1	CREAR_PRODUCTO	productos	3	{"tipo": "vehiculo", "codigo": "VH-716677-316", "nombre": "Moto bera", "stock_inicial": 10}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-15 21:24:05.570024
10	1	ACTUALIZAR_PRODUCTO	productos	3	{"nombre": "Moto bera", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-03-15 21:24:55.341017
11	1	CREAR_PRODUCTO	productos	4	{"tipo": "repuesto", "codigo": "RP-178803-983", "nombre": "frenos", "stock_inicial": 10}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-15 21:31:03.410675
12	1	EMAIL_ORDEN_COMPRA	compras	3	{"codigo": "OC20260316C2D5B4", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-03-16 01:51:23.225793
13	1	Actualizó su perfil	usuarios	\N	\N	\N	\N	2026-03-16 03:15:19.600511
14	1	CREAR_USUARIO	usuarios	2	{"rol_id": 3, "usuario_creado": "Raizza"}	::1	\N	2026-03-16 03:19:26.111203
15	2	PEDIDO_DIGITAL	pedidos_online	1	{"canal": "whatsapp", "total": 34.8, "codigo": "PED-20260316-92372", "productos": 1}	\N	\N	2026-03-16 04:01:00.492774
16	2	PEDIDO_DIGITAL	pedidos_online	2	{"canal": "whatsapp", "total": 34.8, "codigo": "PED-20260316-30536", "productos": 1}	\N	\N	2026-03-16 04:42:16.708425
17	1	CREAR_RESERVA	reservas	0	{"total": 34.8, "codigo": "RES-20260316-248"}	\N	\N	2026-03-16 11:05:55.678515
18	1	ACTUALIZAR_RESERVA	reservas	1	{"estado": "ACTIVA", "cliente_id": 1, "producto_id": 3}	\N	\N	2026-03-16 14:50:44.462027
19	1	ACTUALIZAR_RESERVA	reservas	1	{"estado": "ACTIVA", "cliente_id": 1, "producto_id": 3}	\N	\N	2026-03-16 14:50:44.865787
20	1	ACTUALIZAR_RESERVA	reservas	1	{"estado": "ACTIVA", "cliente_id": 1, "producto_id": 3}	\N	\N	2026-03-16 15:04:33.439723
21	1	ACTUALIZAR_RESERVA	reservas	1	{"estado": "ACTIVA", "cliente_id": 1, "producto_id": 3}	\N	\N	2026-03-16 15:04:44.2164
22	1	ACTUALIZAR_RESERVA	reservas	1	{"estado": "ACTIVA", "cliente_id": 1, "producto_id": 3}	\N	\N	2026-03-16 15:05:47.831457
23	1	ACTUALIZAR_RESERVA	reservas	1	{"estado": "ACTIVA", "cliente_id": 1, "producto_id": 3}	\N	\N	2026-03-16 15:16:00.20401
24	1	ACTUALIZAR_INTEGRACIONES	configuracion_integraciones	\N	["whatsapp_number", "whatsapp_enabled", "email_notifications", "email_from", "email_enabled", "telegram_bot_token", "telegram_chat_id", "telegram_username", "telegram_enabled", "internal_notifications_enabled", "auto_assign_vendors"]	\N	\N	2026-03-16 16:44:02.32274
25	1	ACTUALIZAR_INTEGRACIONES	configuracion_integraciones	\N	["whatsapp_number", "whatsapp_enabled", "email_notifications", "email_from", "email_enabled", "telegram_bot_token", "telegram_chat_id", "telegram_username", "telegram_enabled", "internal_notifications_enabled", "auto_assign_vendors"]	\N	\N	2026-03-16 16:44:23.793995
26	1	ACTUALIZAR_RESERVA	reservas	1	{"estado": "ACTIVA", "cliente_id": 1, "producto_id": 3}	\N	\N	2026-03-17 00:26:21.050471
27	1	COMPLETAR_RESERVA	ventas	6	{"total": 34.8, "productos": 1, "codigo_venta": "VEN-20260317-46452", "codigo_reserva": "RES-20260316-248", "metodo_pago_id": "5"}	\N	\N	2026-03-17 00:57:32.348251
28	1	CREAR_RESERVA	reservas	0	{"total": 34.8, "codigo": "RES-20260317-883"}	\N	\N	2026-03-17 00:59:08.894433
29	1	CREAR_RESERVA	reservas	0	{"total": 34.8, "codigo": "RES-20260317-546"}	\N	\N	2026-03-17 01:50:20.113852
30	1	COMPLETAR_RESERVA	ventas	7	{"total": 34.8, "productos": 1, "codigo_venta": "VEN-20260317-46672", "codigo_reserva": "RES-20260317-546", "metodo_pago_id": "1"}	\N	\N	2026-03-17 02:01:15.419984
31	1	CREAR_RESERVA	reservas	0	{"codigo": "RES-20260317-908", "cliente_id": 2, "producto_id": 1}	\N	\N	2026-03-17 02:46:39.437587
32	1	CREAR_RESERVA	reservas	0	{"codigo": "RES-20260317-509", "cliente_id": 2, "producto_id": 1}	\N	\N	2026-03-17 03:54:40.188815
33	1	ACTUALIZAR_INTEGRACIONES	configuracion_integraciones	\N	["whatsapp_number", "whatsapp_enabled", "email_notifications", "email_from", "email_enabled", "telegram_bot_token", "telegram_chat_id", "telegram_username", "telegram_enabled", "internal_notifications_enabled", "auto_assign_vendors"]	\N	\N	2026-03-17 17:29:08.07341
34	1	CREAR_PROMOCION	promociones	1	{"nombre": "oferta"}	\N	\N	2026-03-17 17:38:04.988109
35	1	CREAR_PROMOCION	promociones	2	{"nombre": "oferta de motos"}	\N	\N	2026-03-17 17:48:54.820498
36	1	PEDIDO_DIGITAL	pedidos_online	3	{"canal": "whatsapp", "total": 34.8, "codigo": "PED-20260317-29323", "productos": 1}	\N	\N	2026-03-17 17:53:38.218223
37	1	PEDIDO_DIGITAL	pedidos_online	4	{"canal": "telegram", "total": 34.8, "codigo": "PED-20260317-74178", "productos": 1}	\N	\N	2026-03-17 17:55:15.306391
38	1	ACTUALIZAR_INTEGRACIONES	configuracion_integraciones	\N	["whatsapp_number", "whatsapp_enabled", "email_notifications", "email_from", "email_enabled", "telegram_bot_token", "telegram_chat_id", "telegram_username", "telegram_enabled", "internal_notifications_enabled", "auto_assign_vendors"]	\N	\N	2026-03-17 17:56:58.563152
39	1	PEDIDO_DIGITAL	pedidos_online	5	{"canal": "telegram", "total": 34.8, "codigo": "PED-20260317-09354", "productos": 1}	\N	\N	2026-03-17 17:57:34.695735
40	2	PEDIDO_ESTADO	pedidos_online	2	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 04121304526 | Ref: 0134"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-17 18:02:02.40177
41	2	PEDIDO_ESTADO	pedidos_online	1	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Transferencia | Banco: mercantil | Ref: 01023405 | Monto: Bs 20"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-17 18:02:20.290935
42	1	INHABILITAR_PROMOCION	promociones	2	{"id": 2}	\N	\N	2026-03-17 18:38:07.223979
43	1	HABILITAR_PROMOCION	promociones	2	{"id": 2}	\N	\N	2026-03-17 19:28:14.325608
44	1	PEDIDO_DIGITAL	pedidos_online	6	{"canal": "notificaciones", "total": 34.8, "codigo": "PED-20260318-95419", "productos": 1}	\N	\N	2026-03-18 00:06:45.04888
45	1	PEDIDO_ESTADO	pedidos_online	5	{"to": "INHABILITADO", "from": "PENDIENTE", "notes": "", "action": "toggle_active"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 00:52:32.811178
46	1	PEDIDO_ESTADO	pedidos_online	5	{"to": "PENDIENTE", "from": "INHABILITADO", "notes": "", "action": "toggle_active"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 00:52:39.653549
47	1	PEDIDO_ESTADO	pedidos_online	5	{"to": "INHABILITADO", "from": "PENDIENTE", "notes": "", "action": "toggle_active"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 01:02:51.518708
48	1	PEDIDO_ESTADO	pedidos_online	5	{"to": "PENDIENTE", "from": "INHABILITADO", "notes": "", "action": "toggle_active"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 01:02:58.674719
49	1	PEDIDO_ESTADO	pedidos_online	5	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 04175550246 | Ref: 1234"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 01:37:38.308191
50	1	PEDIDO_ESTADO	pedidos_online	4	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 04175550246 | Ref: 1234"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 02:20:48.804872
51	1	PEDIDO_ESTADO	pedidos_online	5	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 02:29:16.780025
52	1	PEDIDO_ESTADO	pedidos_online	4	{"to": "INHABILITADO", "from": "EN_VERIFICACION", "notes": "", "action": "toggle_active"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 02:42:20.73259
53	1	PEDIDO_ESTADO	pedidos_online	4	{"to": "PENDIENTE", "from": "INHABILITADO", "notes": "", "action": "toggle_active"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 02:42:25.115443
54	1	CREAR_COMPRA	compras	4	{"total": 116, "codigo": "OC202603189D9B52"}	\N	\N	2026-03-18 04:58:24.93648
55	1	EMAIL_ORDEN_COMPRA	compras	4	{"codigo": "OC202603189D9B52", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-03-18 04:58:31.66721
56	1	CAMBIAR_ESTADO_COMPRA	compras	4	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-03-18 05:02:35.091647
57	1	CAMBIAR_ESTADO_COMPRA	compras	4	{"notas": "xxx", "estado": "COMPLETADA"}	\N	\N	2026-03-18 05:02:48.690019
58	1	CREAR_COMPRA	compras	5	{"total": 116, "codigo": "OC2026031864A050"}	\N	\N	2026-03-18 05:05:24.6131
59	1	EMAIL_ORDEN_COMPRA	compras	5	{"codigo": "OC2026031864A050", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-03-18 05:05:29.747656
60	1	CAMBIAR_ESTADO_COMPRA	compras	5	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-03-18 05:05:39.097732
61	1	CAMBIAR_ESTADO_COMPRA	compras	5	{"notas": "xxx", "estado": "COMPLETADA"}	\N	\N	2026-03-18 05:05:47.576118
62	1	ACTUALIZAR_PRODUCTO	productos	1	{"nombre": "chaqueta", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-03-18 05:07:08.891649
63	1	CREAR_PRODUCTO	productos	5	{"tipo": "vehiculo", "codigo": "VH-512430-071", "nombre": "motos", "stock_inicial": 10}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 05:09:50.531873
64	1	PEDIDO_DIGITAL	pedidos_online	7	{"canal": "whatsapp", "total": 23.2, "codigo": "PED-20260318-87219", "productos": 1}	\N	\N	2026-03-18 05:14:07.562607
65	1	PEDIDO_ESTADO	pedidos_online	7	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 05:15:31.601698
66	1	PEDIDO_ESTADO	pedidos_online	7	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 05:15:45.65237
67	1	CREAR_RESERVA	reservas	0	{"total": 23.2, "codigo": "RES-20260318-962"}	\N	\N	2026-03-18 05:17:09.256962
68	1	COMPLETAR_RESERVA	ventas	19	{"total": 23.2, "productos": 1, "codigo_venta": "VEN-20260318-88856", "codigo_reserva": "RES-20260318-962", "metodo_pago_id": "1"}	\N	\N	2026-03-18 05:17:45.05616
69	1	CREAR_PROMOCION	promociones	3	{"nombre": "oferta especial"}	\N	\N	2026-03-18 05:22:35.681995
70	1	CREAR_PROMOCION	promociones	4	{"nombre": "oferta de ejemplo"}	\N	\N	2026-03-18 05:26:25.588267
71	1	INHABILITAR_PROMOCION	promociones	3	{"id": 3}	\N	\N	2026-03-18 05:26:54.892203
72	1	INHABILITAR_PROMOCION	promociones	4	{"id": 4}	\N	\N	2026-03-18 05:27:08.373305
73	1	CREAR_PROMOCION	promociones	5	{"nombre": "oferta de motocicletas"}	\N	\N	2026-03-18 05:29:14.651444
74	2	PEDIDO_DIGITAL	pedidos_online	8	{"canal": "notificaciones", "total": 23.2, "codigo": "PED-20260318-45142", "productos": 1}	\N	\N	2026-03-18 12:29:17.253085
75	2	PEDIDO_ESTADO	pedidos_online	8	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Transferencia | Banco: mercantil | Ref: 01023405 | Monto: Bs 20"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:29:42.399532
76	1	CREAR_USUARIO	usuarios	3	{"rol_id": 2, "usuario_creado": "Cesar1234"}	::1	\N	2026-03-18 12:32:27.434516
77	1	PEDIDO_ASIGNAR_VENDEDOR	pedidos_online	8	{"seller_id": 3}	\N	\N	2026-03-18 12:32:42.097415
78	1	PEDIDO_ASIGNAR_VENDEDOR	pedidos_online	6	{"seller_id": 3}	\N	\N	2026-03-18 12:32:48.321527
79	3	PEDIDO_ESTADO	pedidos_online	8	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:34:16.074467
80	1	PEDIDO_ESTADO	pedidos_online	2	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:35:14.308375
81	1	PEDIDO_ESTADO	pedidos_online	1	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:35:23.421708
82	1	PEDIDO_ESTADO	pedidos_online	3	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:35:38.794007
83	1	PEDIDO_ESTADO	pedidos_online	3	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:35:45.254099
84	1	PEDIDO_ESTADO	pedidos_online	4	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:35:53.083572
85	1	PEDIDO_ESTADO	pedidos_online	4	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:36:02.413993
86	1	PEDIDO_ESTADO	pedidos_online	6	{"to": "INHABILITADO", "from": "PENDIENTE", "notes": "", "action": "toggle_active"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:36:13.860816
87	2	PEDIDO_DIGITAL	pedidos_online	9	{"canal": "notificaciones", "total": 46.38, "codigo": "PED-20260318-98418", "productos": 2}	\N	\N	2026-03-18 12:41:21.101391
88	2	PEDIDO_ESTADO	pedidos_online	9	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0293"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:41:40.723051
89	1	PEDIDO_ASIGNAR_VENDEDOR	pedidos_online	9	{"seller_id": 3}	\N	\N	2026-03-18 12:42:48.804007
90	3	PEDIDO_ESTADO	pedidos_online	9	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-18 12:43:48.568802
91	1	CREAR_USUARIO	usuarios	4	{"rol_id": 6, "usuario_creado": "dylan2912"}	::1	\N	2026-03-22 14:24:15.78738
92	1	CREAR_USUARIO	usuarios	5	{"rol_id": 5, "usuario_creado": "luis"}	::1	\N	2026-03-22 14:25:18.027152
93	2	PEDIDO_DIGITAL	pedidos_online	10	{"canal": "email", "total": 23.2, "codigo": "PED-20260323-64314", "productos": 1}	\N	\N	2026-03-23 01:25:37.997515
94	2	PEDIDO_DIGITAL	pedidos_online	11	{"canal": "telegram", "total": 23.2, "codigo": "PED-20260324-84847", "productos": 1}	\N	\N	2026-03-24 01:35:17.6934
95	6	REGISTRO_USUARIO	usuarios	6	{"email": "juan@gmail.com", "username": "juan"}	::1	\N	2026-03-24 02:58:20.195565
96	7	REGISTRO_USUARIO	usuarios	7	{"email": "gifrank0000@gmail.com", "username": "gi2912frank"}	::1	\N	2026-03-24 03:01:29.077972
97	8	REGISTRO_USUARIO	usuarios	8	{"email": "gifrank@gmail.com", "username": "gifrank0000"}	::1	\N	2026-03-24 03:13:48.082067
98	9	REGISTRO_USUARIO	usuarios	9	{"email": "user@gmail.com", "username": "usuario2912"}	::1	\N	2026-03-24 15:54:42.300117
99	2	PEDIDO_DIGITAL	pedidos_online	12	{"canal": "telegram", "total": 23.2, "codigo": "PED-20260324-30473", "productos": 1}	\N	\N	2026-03-24 16:08:34.481177
100	2	PEDIDO_DIGITAL	pedidos_online	13	{"canal": "email", "total": 23.2, "codigo": "PED-20260324-36596", "productos": 1}	\N	\N	2026-03-24 16:09:22.933549
101	2	PEDIDO_DIGITAL	pedidos_online	14	{"canal": "notificaciones", "total": 23.2, "codigo": "PED-20260324-93611", "productos": 1}	\N	\N	2026-03-24 16:10:16.91911
102	1	PEDIDO_ASIGNAR_VENDEDOR	pedidos_online	14	{"seller_id": 3}	\N	\N	2026-03-24 16:11:04.437899
103	3	PEDIDO_ESTADO	pedidos_online	14	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-24 16:12:13.825101
104	3	PEDIDO_ESTADO	pedidos_online	14	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-24 16:12:24.673274
105	1	CREAR_USUARIO	usuarios	10	{"rol_id": 3, "usuario_creado": "usuario"}	::1	\N	2026-03-24 17:03:43.189927
106	1	BACKUP_DOWNLOAD	sistema	\N	{"archivo": "backup_2026-03-17_18-59-32.sql"}	::1	\N	2026-03-24 17:06:48.83133
107	1	BACKUP_DOWNLOAD	sistema	\N	{"archivo": "backup_2026-03-24_18-06-41.sql"}	::1	\N	2026-03-24 17:10:36.473501
\.


--
-- Data for Name: categorias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.categorias (id, nombre, descripcion, estado, tipo_producto_id, created_at, updated_at) FROM stdin;
1	Ropa	xxx	t	3	2026-03-15 20:21:45.062366	2026-03-15 20:21:45.062366
2	Motor	xxx	t	4	2026-03-15 21:17:04.011118	2026-03-15 21:17:04.011118
3	sincronica	xxx	t	5	2026-03-15 21:23:57.316438	2026-03-15 21:23:57.316438
\.


--
-- Data for Name: clientes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.clientes (id, cedula_rif, nombre_completo, email, telefono_principal, telefono_alternativo, direccion, fecha_registro, estado, usuario_id, created_at, updated_at) FROM stdin;
1	V-6927898	Raizza Marrero	jovita45r@gmail.com	0412-7550246	0412-1304526	venezuela, estado Aragua, ciudad Maracay, municipio Libertador, palo negro Santa Ana. Urbanización jardines los tulipanes calle 3 casa 165	2026-03-15	t	1	2026-03-15 21:37:36.120543	2026-03-15 21:37:36.120543
2	V-6927868	Raizza marrero	jovita45r@gmail.com	04175550246	\N		2026-03-16	t	2	2026-03-16 03:19:26.111203	2026-03-16 03:19:26.111203
3	V-1234567	dylan	dylan@gmail.com	04121234567		xxx	2026-03-18	t	1	2026-03-18 04:33:34.754514	2026-03-18 04:33:34.754514
4	V-30555703	Juan Villegas	juan@gmail.com	0412-7550247	\N	\N	2026-03-24	t	6	2026-03-24 02:58:20.195565	2026-03-24 02:58:20.195565
5	V-30555701	Gifrank	gifrank0000@gmail.com	0412-1304526	\N	\N	2026-03-24	t	7	2026-03-24 03:01:29.077972	2026-03-24 03:01:29.077972
6	J-692786822	Gifrank	gifrank@gmail.com	0417-5550242	\N	\N	2026-03-24	t	8	2026-03-24 03:13:48.082067	2026-03-24 03:13:48.082067
7	V-12345678	Usuario de prueba	user@gmail.com	0412-1304527	\N	\N	2026-03-24	t	9	2026-03-24 15:54:42.300117	2026-03-24 15:54:42.300117
\.


--
-- Data for Name: compras; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.compras (id, codigo_compra, proveedor_id, usuario_id, subtotal, iva, total, estado_compra, fecha_estimada_entrega, observaciones, activa, notas_incidencia, created_at) FROM stdin;
2	OC2026031642FA8C	3	1	20.00	3.20	23.20	COMPLETADA	2026-03-23		t		2026-03-16 01:07:51.539658
1	OC202603163B90A8	1	1	20.00	3.20	23.20	COMPLETADA	2026-03-23		t	Cancelación manual	2026-03-16 00:02:23.070303
3	OC20260316C2D5B4	1	1	20.00	3.20	23.20	COMPLETADA	2026-03-23	xxx	t	falta mas	2026-03-16 01:51:18.739691
4	OC202603189D9B52	1	1	100.00	16.00	116.00	COMPLETADA	2026-03-25		t	xxx	2026-03-18 04:58:24.912412
5	OC2026031864A050	1	1	100.00	16.00	116.00	COMPLETADA	2026-03-26	xxx	t	xxx	2026-03-18 05:05:24.589141
\.


--
-- Data for Name: configuracion_integraciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.configuracion_integraciones (clave, valor, updated_at) FROM stdin;
whatsapp_number	584124617132	2026-03-17 17:56:58.516547+00
whatsapp_enabled	1	2026-03-17 17:56:58.52038+00
email_notifications	2016rojasinversiones@gmail.com	2026-03-17 17:56:58.521245+00
email_from	gifrank0000@gmail.com	2026-03-17 17:56:58.521963+00
email_enabled	1	2026-03-17 17:56:58.522419+00
telegram_bot_token	8673470561:AAEBt8NGDQwohzmVeysU1rHnFHzzxkg0Ung	2026-03-17 17:56:58.522777+00
telegram_chat_id	-1003766374059	2026-03-17 17:56:58.523143+00
telegram_username	Inversiones Rojas	2026-03-17 17:56:58.523469+00
telegram_enabled	1	2026-03-17 17:56:58.560155+00
internal_notifications_enabled	1	2026-03-17 17:56:58.56147+00
auto_assign_vendors	1	2026-03-17 17:56:58.562024+00
\.


--
-- Data for Name: detalle_compras; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.detalle_compras (id, compra_id, producto_id, cantidad, precio_unitario, subtotal, created_at) FROM stdin;
2	1	1	2	10.00	20.00	2026-03-16 00:57:04.644783
3	2	2	1	20.00	20.00	2026-03-16 01:07:51.539658
4	3	2	1	20.00	20.00	2026-03-16 01:51:18.739691
5	4	1	10	10.00	100.00	2026-03-18 04:58:24.927869
6	5	4	10	10.00	100.00	2026-03-18 05:05:24.604477
\.


--
-- Data for Name: detalle_pedidos_online; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.detalle_pedidos_online (id, pedido_id, producto_id, cantidad, precio_unitario, subtotal, created_at) FROM stdin;
1	1	3	1	30.00	30.00	2026-03-16 04:01:00.492774
2	2	3	1	30.00	30.00	2026-03-16 04:42:16.708425
3	3	3	1	30.00	30.00	2026-03-17 17:53:38.218223
4	4	3	1	30.00	30.00	2026-03-17 17:55:15.306391
5	5	3	1	30.00	30.00	2026-03-17 17:57:34.695735
6	6	3	1	30.00	30.00	2026-03-18 00:06:45.04888
7	7	5	1	20.00	20.00	2026-03-18 05:14:07.562607
8	8	5	1	20.00	20.00	2026-03-18 12:29:17.253085
9	9	5	1	20.00	20.00	2026-03-18 12:41:21.101391
10	9	4	1	19.98	19.98	2026-03-18 12:41:21.101391
11	10	5	1	20.00	20.00	2026-03-23 01:25:37.997515
12	11	5	1	20.00	20.00	2026-03-24 01:35:17.6934
13	12	5	1	20.00	20.00	2026-03-24 16:08:34.481177
14	13	5	1	20.00	20.00	2026-03-24 16:09:22.933549
15	14	5	1	20.00	20.00	2026-03-24 16:10:16.91911
\.


--
-- Data for Name: detalle_ventas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.detalle_ventas (id, venta_id, producto_id, cantidad, precio_unitario, subtotal, created_at) FROM stdin;
1	1	1	1	40.00	40.00	2026-03-15 21:37:46.289757
2	2	3	1	30.00	30.00	2026-03-15 23:23:05.389608
3	3	1	1	40.00	40.00	2026-03-15 23:26:41.668385
4	4	1	1	40.00	40.00	2026-03-15 23:52:35.489566
5	5	4	1	19.98	19.98	2026-03-15 23:54:21.224039
6	6	3	1	30.00	30.00	2026-03-17 00:57:32.312688
7	7	3	1	30.00	30.00	2026-03-17 02:01:13.861599
8	8	1	1	40.00	40.00	2026-03-18 04:34:04.998108
9	9	1	1	40.00	40.00	2026-03-18 04:34:28.663668
10	10	1	1	40.00	40.00	2026-03-18 04:34:30.235147
11	11	1	1	40.00	40.00	2026-03-18 04:34:30.37264
12	12	1	1	40.00	40.00	2026-03-18 04:34:30.553596
13	13	1	1	40.00	40.00	2026-03-18 04:34:30.664176
14	14	1	1	40.00	40.00	2026-03-18 04:34:30.816474
15	15	4	1	19.98	19.98	2026-03-18 04:44:23.046914
16	16	4	1	19.98	19.98	2026-03-18 04:50:31.191365
17	17	3	2	30.00	60.00	2026-03-18 04:52:56.849787
18	18	3	3	30.00	90.00	2026-03-18 04:56:05.450469
19	19	5	1	20.00	20.00	2026-03-18 05:17:45.011829
20	20	1	1	40.00	40.00	2026-03-24 01:44:39.069417
\.


--
-- Data for Name: devoluciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.devoluciones (id, codigo_devolucion, venta_id, pedido_id, cliente_id, producto_id, cantidad, motivo, estado_devolucion, tipo_reintegro, observaciones, created_at, updated_at) FROM stdin;
1	DEV-20260317-0001	\N	2	2	3	1	Producto Defectuoso	APROBADO	\N	xxx	2026-03-17 18:01:19.944077	2026-03-17 23:56:11.088862
2	DEV-20260318-0002	\N	1	2	3	1	Garantía	APROBADO	\N	quiero una devolucion	2026-03-18 05:31:41.472824	2026-03-18 05:32:49.092143
3	DEV-20260318-0003	\N	8	2	5	1	Producto Incorrecto	APROBADO	\N	me equivoque	2026-03-18 12:37:13.475537	2026-03-18 12:39:37.267826
4	DEV-20260318-0004	\N	9	2	4	1	Producto Defectuoso	APROBADO	\N		2026-03-18 12:55:59.19203	2026-03-18 12:57:28.185478
5	DEV-20260318-0005	\N	9	2	5	1	Producto Dañado	APROBADO	\N	xxx	2026-03-18 13:04:00.918019	2026-03-18 13:05:24.932598
6	DEV-20260323-0006	\N	10	2	5	1	Producto Defectuoso	APROBADO	\N	xxx	2026-03-23 01:26:33.351041	2026-03-23 01:29:28.268182
7	DEV-20260324-0007	\N	11	2	5	1	Producto Dañado	APROBADO	\N	xxx	2026-03-24 01:37:08.500797	2026-03-24 01:37:54.59403
\.


--
-- Data for Name: empresa_configuracion; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.empresa_configuracion (id, nombre_empresa, rif, direccion, telefono, email, iva_porcentaje, actualizado_por, fecha_actualizacion, fecha_creacion) FROM stdin;
1	INVERSIONES ROJAS 2016 C.A.	J-40888806-8	AV ARAGUA LOCAL NRO 286 SECTOR ANDRES ELOY BLANCO, MARACAY ARAGUA ZONA POSTAL 2102	0243-2343044	2016rojasinversiones@gmail.com	16.00	\N	2026-03-15 18:51:04.166201	2026-03-15 18:51:04.166201
\.


--
-- Data for Name: incidencias_soporte; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.incidencias_soporte (id, codigo_incidencia, cliente_id, usuario_id, descripcion, urgencia, modulo_sistema, estado, asignado_a, notas_solucion, created_at, updated_at) FROM stdin;
1	INC-001	2	2	tengo un error al comprar un producto	alta	Compras / Proveedores	resuelto	4	el problema a sido solventando por nuestros programadores	2026-03-24 15:59:05.672378	2026-03-24 16:01:22.585188
\.


--
-- Data for Name: metodos_pago; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.metodos_pago (id, nombre, descripcion, estado, created_at) FROM stdin;
2	Transferencia	Transferencia bancaria	t	2026-03-15 18:51:04.166201
3	Efectivo	Pago en efectivo	t	2026-03-15 18:51:04.166201
4	Zelle	Transferencia Zelle USD	t	2026-03-15 18:51:04.166201
5	Binance	Billetera movil	t	2026-03-15 21:34:21.516494
1	Pago Movil	Pago móvil interbancario	t	2026-03-15 18:51:04.166201
\.


--
-- Data for Name: movimientos_inventario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.movimientos_inventario (id, producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, motivo, referencia, usuario_id, created_at) FROM stdin;
1	3	SALIDA	1	20	19	Venta V-20260316002305-742	2	1	2026-03-15 23:23:05.389608
2	1	SALIDA	1	10	9	Venta V-20260316002641-835	3	1	2026-03-15 23:26:41.668385
3	1	SALIDA	1	9	8	Venta V-20260316005235-187	4	1	2026-03-15 23:52:35.489566
4	4	SALIDA	1	10	9	Venta V-20260316005421-991	5	1	2026-03-15 23:54:21.224039
5	2	ENTRADA	1	10	11	RECEPCION_COMPRA	OC2026031642FA8C	1	2026-03-16 01:25:12.244494
6	3	SALIDA	1	19	18	Venta desde reserva #RES-20260316-248	\N	1	2026-03-17 00:57:32.312688
7	3	SALIDA	1	18	17	Venta desde reserva #RES-20260317-546	\N	1	2026-03-17 02:01:13.861599
8	3	SALIDA_PEDIDO	1	17	16	Confirmación de pedido	PEDIDO:PED-20260317-09354	1	2026-03-18 02:29:16.780025
9	1	SALIDA	1	8	7	Venta V-20260318053404-274	8	1	2026-03-18 04:34:04.998108
10	1	SALIDA	1	7	6	Venta V-20260318053428-750	9	1	2026-03-18 04:34:28.663668
11	1	SALIDA	1	6	5	Venta V-20260318053430-488	10	1	2026-03-18 04:34:30.235147
12	1	SALIDA	1	5	4	Venta V-20260318053430-346	11	1	2026-03-18 04:34:30.37264
13	1	SALIDA	1	4	3	Venta V-20260318053430-518	12	1	2026-03-18 04:34:30.553596
14	1	SALIDA	1	3	2	Venta V-20260318053430-634	13	1	2026-03-18 04:34:30.664176
15	1	SALIDA	1	2	1	Venta V-20260318053430-909	14	1	2026-03-18 04:34:30.816474
16	4	SALIDA	1	9	8	Venta V-20260318054423-388	15	1	2026-03-18 04:44:23.046914
17	4	SALIDA	1	8	7	Venta V-20260318055031-877	16	1	2026-03-18 04:50:31.191365
18	3	SALIDA	2	16	14	Venta V-20260318055256-665	17	1	2026-03-18 04:52:56.849787
19	3	SALIDA	3	14	11	Venta V-20260318055605-407	18	1	2026-03-18 04:56:05.450469
20	1	ENTRADA	10	1	11	Recepción de compra	compra_id:4	1	2026-03-18 05:02:48.688461
21	4	ENTRADA	10	7	17	Recepción de compra	compra_id:5	1	2026-03-18 05:05:47.538894
22	5	SALIDA_PEDIDO	1	10	9	Confirmación de pedido	PEDIDO:PED-20260318-87219	1	2026-03-18 05:15:45.65237
23	5	SALIDA	1	9	8	Venta desde reserva #RES-20260318-962	\N	1	2026-03-18 05:17:45.011829
24	5	SALIDA_PEDIDO	1	8	7	Confirmación de pedido	PEDIDO:PED-20260318-45142	3	2026-03-18 12:34:16.074467
25	3	SALIDA_PEDIDO	1	11	10	Confirmación de pedido	PEDIDO:PED-20260316-30536	1	2026-03-18 12:35:14.308375
26	3	SALIDA_PEDIDO	1	10	9	Confirmación de pedido	PEDIDO:PED-20260316-92372	1	2026-03-18 12:35:23.421708
27	3	SALIDA_PEDIDO	1	9	8	Confirmación de pedido	PEDIDO:PED-20260317-29323	1	2026-03-18 12:35:45.254099
28	3	SALIDA_PEDIDO	1	8	7	Confirmación de pedido	PEDIDO:PED-20260317-74178	1	2026-03-18 12:36:02.413993
29	4	SALIDA_PEDIDO	1	17	16	Confirmación de pedido	PEDIDO:PED-20260318-98418	3	2026-03-18 12:43:48.568802
30	5	SALIDA_PEDIDO	1	7	6	Confirmación de pedido	PEDIDO:PED-20260318-98418	3	2026-03-18 12:43:48.568802
31	1	SALIDA	1	11	10	Venta V-20260324024439-229	20	1	2026-03-24 01:44:39.069417
32	5	SALIDA_PEDIDO	1	6	5	Confirmación de pedido	PEDIDO:PED-20260324-93611	3	2026-03-24 16:12:24.673274
\.


--
-- Data for Name: notificaciones_vendedor; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notificaciones_vendedor (id, pedido_id, titulo, mensaje, tipo, leida, usuario_id, created_at) FROM stdin;
1	6	Nuevo pedido sin asignar: PED-20260318-95419	Cliente: Raizza Marrero | Total: Bs 34.80 | Tel: +5804127550246 | Pendiente de asignar a un vendedor	PEDIDO_NUEVO	f	\N	2026-03-18 00:06:45.130952+00
2	8	Nuevo pedido sin asignar: PED-20260318-45142	Cliente: Raizza marrero | Total: Bs 23.20 | Tel: +5804127550246 | Pendiente de asignar a un vendedor	PEDIDO_NUEVO	f	\N	2026-03-18 12:29:17.310059+00
3	8	Nuevo pedido asignado	Se te ha asignado el pedido PED-20260318-45142	PEDIDO_ASIGNADO	t	3	2026-03-18 12:32:42.095884+00
4	6	Nuevo pedido asignado	Se te ha asignado el pedido PED-20260318-95419	PEDIDO_ASIGNADO	t	3	2026-03-18 12:32:48.320241+00
6	9	Nuevo pedido sin asignar: PED-20260318-98418	Cliente: Raizza marrero | Total: Bs 46.38 | Tel: +5804127550246 | Pendiente de asignar a un vendedor	PEDIDO_NUEVO	f	\N	2026-03-18 12:41:21.162141+00
7	9	Nuevo pedido asignado	Se te ha asignado el pedido PED-20260318-98418	PEDIDO_ASIGNADO	t	3	2026-03-18 12:42:48.80244+00
8	14	Nuevo pedido sin asignar: PED-20260324-93611	Cliente: Raizza marrero | Total: Bs 23.20 | Tel: +5804121304526 | Pendiente de asignar a un vendedor	PEDIDO_NUEVO	f	\N	2026-03-24 16:10:16.975314+00
9	14	Nuevo pedido asignado	Se te ha asignado el pedido PED-20260324-93611	PEDIDO_ASIGNADO	t	3	2026-03-24 16:11:04.43618+00
\.


--
-- Data for Name: pedidos_online; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pedidos_online (id, codigo_pedido, cliente_id, subtotal, iva, total, estado_pedido, metodo_pago_id, direccion_entrega, telefono_contacto, observaciones, canal_comunicacion, tipo_entrega, referencia_pago, vendedor_asignado_id, fecha_asignacion, fecha_contacto, fecha_pago, intentos_contacto, venta_id, created_at, updated_at) FROM stdin;
5	PED-20260317-09354	1	30.00	4.80	34.80	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 04175550246 | Ref: 1234	telegram	tienda	\N	\N	\N	\N	2026-03-18 02:29:16.780025	0	\N	2026-03-17 17:57:34.695735	2026-03-18 02:29:16.780025
7	PED-20260318-87219	1	20.00	3.20	23.20	CONFIRMADO	1	xxx	+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	whatsapp	domicilio	\N	\N	\N	\N	2026-03-18 05:15:45.65237	0	\N	2026-03-18 05:14:07.562607	2026-03-18 05:15:45.65237
10	PED-20260323-64314	2	20.00	3.20	23.20	PENDIENTE	\N	calle casa	+5804127550246	xxxx	email	domicilio	\N	\N	\N	\N	\N	0	\N	2026-03-23 01:25:37.997515	2026-03-23 01:25:37.997515
11	PED-20260324-84847	2	20.00	3.20	23.20	PENDIENTE	\N		+5804121304526	xxx	telegram	tienda	\N	\N	\N	\N	\N	0	\N	2026-03-24 01:35:17.6934	2026-03-24 01:35:17.6934
8	PED-20260318-45142	2	20.00	3.20	23.20	CONFIRMADO	1		+5804127550246	 | Pago: Transferencia | Banco: mercantil | Ref: 01023405 | Monto: Bs 20	notificaciones	tienda	\N	3	2026-03-18 12:32:42.092839	\N	2026-03-18 12:34:16.074467	0	\N	2026-03-18 12:29:17.253085	2026-03-18 12:34:16.074467
2	PED-20260316-30536	2	30.00	4.80	34.80	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 04121304526 | Ref: 0134	whatsapp	tienda	\N	\N	\N	\N	2026-03-18 12:35:14.308375	0	\N	2026-03-16 04:42:16.708425	2026-03-18 12:35:14.308375
1	PED-20260316-92372	2	30.00	4.80	34.80	CONFIRMADO	1		04127550246	xxx | Pago: Transferencia | Banco: mercantil | Ref: 01023405 | Monto: Bs 20	whatsapp	tienda	\N	\N	\N	\N	2026-03-18 12:35:23.421708	0	\N	2026-03-16 04:01:00.492774	2026-03-18 12:35:23.421708
3	PED-20260317-29323	1	30.00	4.80	34.80	CONFIRMADO	5		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	whatsapp	tienda	\N	\N	\N	\N	2026-03-18 12:35:45.254099	0	\N	2026-03-17 17:53:38.218223	2026-03-18 12:35:45.254099
4	PED-20260317-74178	1	30.00	4.80	34.80	CONFIRMADO	5		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 04175550246 | Ref: 1234 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	telegram	tienda	\N	\N	\N	\N	2026-03-18 12:36:02.413993	0	\N	2026-03-17 17:55:15.306391	2026-03-18 12:36:02.413993
6	PED-20260318-95419	1	30.00	4.80	34.80	INHABILITADO	\N		+5804127550246		notificaciones	tienda	\N	3	2026-03-18 12:32:48.317401	\N	\N	0	\N	2026-03-18 00:06:45.04888	2026-03-18 12:36:13.860816
12	PED-20260324-30473	2	20.00	3.20	23.20	PENDIENTE	\N		+5804121304526	xxx	telegram	tienda	\N	\N	\N	\N	\N	0	\N	2026-03-24 16:08:34.481177	2026-03-24 16:08:34.481177
9	PED-20260318-98418	2	39.98	6.40	46.38	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0293	notificaciones	tienda	\N	3	2026-03-18 12:42:48.799393	\N	2026-03-18 12:43:48.568802	0	\N	2026-03-18 12:41:21.101391	2026-03-18 12:43:48.568802
13	PED-20260324-36596	2	20.00	3.20	23.20	PENDIENTE	\N		+5804121304526	xxx	email	tienda	\N	\N	\N	\N	\N	0	\N	2026-03-24 16:09:22.933549	2026-03-24 16:09:22.933549
14	PED-20260324-93611	2	20.00	3.20	23.20	CONFIRMADO	1		+5804121304526	xxx | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	notificaciones	tienda	\N	3	2026-03-24 16:11:04.432774	\N	2026-03-24 16:12:24.673274	0	\N	2026-03-24 16:10:16.91911	2026-03-24 16:12:24.673274
\.


--
-- Data for Name: producto_imagenes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.producto_imagenes (id, producto_id, imagen_url, es_principal, orden, created_at) FROM stdin;
1	1	/inversiones-rojas/public/img/products/prod_1_1773606770_0.jpg	t	1	2026-03-15 20:32:50.314258
2	2	/inversiones-rojas/public/img/products/prod_2_1773609458_0.jpg	t	1	2026-03-15 21:17:38.688799
3	3	/inversiones-rojas/public/img/products/prod_3_1773609845_0.jpg	t	1	2026-03-15 21:24:05.570024
4	4	/inversiones-rojas/public/img/products/prod_4_1773610263_0.jpg	t	1	2026-03-15 21:31:03.410675
5	5	/inversiones-rojas/public/img/products/prod_5_1773810590_0.png	t	1	2026-03-18 05:09:50.531873
\.


--
-- Data for Name: producto_promociones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.producto_promociones (id, producto_id, promocion_id, created_at) FROM stdin;
1	1	1	2026-03-17 17:38:04.988109
2	3	2	2026-03-17 17:48:54.820498
3	3	3	2026-03-18 05:22:35.681995
4	5	3	2026-03-18 05:22:35.681995
5	4	4	2026-03-18 05:26:25.588267
6	3	5	2026-03-18 05:29:14.651444
\.


--
-- Data for Name: producto_proveedor; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.producto_proveedor (id, producto_id, proveedor_id, precio_compra, sku_proveedor, tiempo_entrega_dias, es_principal, activo, created_at, updated_at) FROM stdin;
1	1	1	10.00	\N	\N	t	t	2026-03-15 20:32:50.314258	2026-03-15 20:32:50.314258
2	2	1	20.00	\N	\N	t	t	2026-03-15 21:17:38.688799	2026-03-15 21:17:38.688799
3	3	1	20.00	\N	\N	t	t	2026-03-15 21:24:05.570024	2026-03-15 21:24:05.570024
4	4	1	10.00	\N	\N	t	t	2026-03-15 21:31:03.410675	2026-03-15 21:31:03.410675
5	5	1	10.00	\N	\N	t	t	2026-03-18 05:09:50.531873	2026-03-18 05:09:50.531873
\.


--
-- Data for Name: productos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.productos (id, codigo_interno, nombre, descripcion, categoria_id, precio_compra, precio_venta, stock_actual, stock_minimo, stock_maximo, proveedor_id, estado, tipo_id, created_at, updated_at) FROM stdin;
2	RP-362881-579	Freno	xxx	2	20.00	30.00	11	4	100	1	f	2	2026-03-15 21:17:38.688799	2026-03-15 21:21:53.066608
3	VH-716677-316	Moto bera	xxx	3	20.00	30.00	7	10	100	1	t	1	2026-03-15 21:24:05.570024	2026-03-18 12:36:02.413993
4	RP-178803-983	frenos	xxx	2	10.00	19.98	16	5	100	1	t	2	2026-03-15 21:31:03.410675	2026-03-18 12:43:48.568802
1	AC-449461-311	chaqueta	cambiado	1	30.00	40.00	10	4	100	1	t	3	2026-03-15 20:32:50.314258	2026-03-18 05:07:08.891649
5	VH-512430-071	motos	xxx	3	10.00	20.00	5	5	100	1	t	1	2026-03-18 05:09:50.531873	2026-03-24 16:12:24.673274
\.


--
-- Data for Name: promociones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.promociones (id, nombre, descripcion, tipo_promocion, valor, fecha_inicio, fecha_fin, estado, imagen_url, tipo_imagen, imagen_banco_key, color_personalizado, created_at, updated_at) FROM stdin;
1	oferta		PORCENTAJE	10.00	2026-03-17	2026-04-16	t	http://localhost/inversiones-rojas/public/img/promotions/promo_1773769084_b683bcfde685.png	manual	\N	#1F9166	2026-03-17 17:38:04.988109	2026-03-17 17:38:04.988109
2	oferta de motos	xxx	PORCENTAJE	10.00	2026-03-17	2026-04-16	t	http://localhost/inversiones-rojas/public/img/promotions/promo_1773769734_1c59d90f654e.jpg	manual	\N	#1F9166	2026-03-17 17:48:54.820498	2026-03-17 19:28:14.315454
3	oferta especial	esta es una oferta especial aprovecha	PORCENTAJE	15.00	2026-03-19	2026-04-18	f	http://localhost/inversiones-rojas/public/img/promotions/promo_1773811355_4074233ab8b9.jpg	manual	\N	#1F9166	2026-03-18 05:22:35.681995	2026-03-18 05:26:54.888908
4	oferta de ejemplo	esta es una oferta de ejemplo	PORCENTAJE	15.00	2026-03-18	2026-04-17	f	http://localhost/inversiones-rojas/public/img/promotions/promo_1773811585_118f24d523e1.png	manual	\N	#1F9166	2026-03-18 05:26:25.588267	2026-03-18 05:27:08.369578
5	oferta de motocicletas	este es un ejemplo de promociones	PORCENTAJE	20.00	2026-03-18	2026-04-17	t	http://localhost/inversiones-rojas/public/img/promotions/promo_1773811754_4848b030643f.jpg	manual	\N	#1F9166	2026-03-18 05:29:14.651444	2026-03-18 05:29:14.651444
\.


--
-- Data for Name: proveedores; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.proveedores (id, razon_social, rif, persona_contacto, telefono_principal, telefono_alternativo, email, direccion, productos_suministrados, estado, created_at, updated_at) FROM stdin;
1	BERA MOTORCYCLES.C.A	J423452335	fermin perez	04124617132	04124617132	jovita45r@gmail.com	venezuela, estado Aragua, ciudad Maracay, municipio Libertador, palo negro Santa Ana. Urbanización jardines los tulipanes calle 3 casa 165	\N	t	2026-03-15 19:57:27.549924	2026-03-15 19:57:27.549924
2	Distribuidor kajasaki	12345678	fermin perez	0412304526	04124617132	jovita45r@gmail.com	venezuela, estado Aragua, ciudad Maracay, municipio Libertador, palo negro Santa Ana. Urbanización jardines los tulipanes calle 3 casa 165	\N	t	2026-03-16 00:34:29.864439	2026-03-16 00:34:29.864439
3	Distribuidora Toro	J-123456789	fermin perez	0412-1234567	04124617132	jovita45r@gmail.com	venezuela, estado Aragua, ciudad Maracay, municipio Libertador, palo negro Santa Ana. Urbanización jardines los tulipanes calle 3 casa 165	\N	t	2026-03-16 01:07:09.880412	2026-03-16 01:07:09.880412
\.


--
-- Data for Name: repuestos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.repuestos (id, producto_id, categoria_tecnica, marca_compatible, modelo_compatible, anio_compatible, created_at) FROM stdin;
1	2	Motor	bera	Br200	2020	2026-03-15 21:17:38.688799
2	4	frenos	bera	Br200	2020	2026-03-15 21:31:03.410675
\.


--
-- Data for Name: reservas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reservas (id, codigo_reserva, cliente_id, producto_id, cantidad, fecha_reserva, fecha_limite, estado_reserva, observaciones, created_at, updated_at) FROM stdin;
1	RES-20260316-248	1	3	1	2026-03-16	2026-03-23	COMPLETADA	Tel: 04127550246 | xxx	2026-03-16 11:05:55.678515	2026-03-17 00:57:32.312688
3	RES-20260317-546	1	3	1	2026-03-17	2026-03-24	COMPLETADA	Tel: 04127550246	2026-03-17 01:50:20.113852	2026-03-17 02:01:13.861599
4	RES-20260317-908	2	1	1	2026-03-17	2026-03-19	PENDIENTE	xxx	2026-03-17 02:46:39.437587	2026-03-18 05:11:26.851987
5	RES-20260317-509	2	1	1	2026-03-17	2026-03-18	PENDIENTE	xxx	2026-03-17 03:54:40.188815	2026-03-18 05:11:30.252258
2	RES-20260317-883	1	3	1	2026-03-17	2026-03-27	PENDIENTE	Tel: 04127550246	2026-03-17 00:59:08.894433	2026-03-18 05:11:36.43662
6	RES-20260318-962	1	5	1	2026-03-18	2026-03-25	COMPLETADA	Tel: 04127550246	2026-03-18 05:17:09.256962	2026-03-18 05:17:45.011829
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.roles (id, nombre, descripcion, permisos, estado, created_at) FROM stdin;
5	Gerente	Gestión de operaciones comerciales	{"modulos": ["ventas", "compras", "inventario", "reportes", "pedidos", "reservas", "promociones", "devoluciones"], "acciones": ["crear", "leer", "actualizar"]}	t	2026-03-16 03:46:09.425632
6	Operador	Gestión de pedidos online	{"modulos": ["pedidos", "reservas", "devoluciones"], "acciones": ["crear", "leer", "actualizar"]}	t	2026-03-16 03:46:09.425632
1	Administrador	Acceso total al sistema	{"modulos": ["todos"], "acciones": ["crear", "leer", "actualizar", "eliminar"]}	t	2026-03-15 18:51:04.166201
2	Vendedor	Gestión de pedidos y clientes	{"modulos": ["ventas", "clientes", "reservas", "promociones", "devoluciones"], "acciones": ["crear", "leer"]}	t	2026-03-15 18:51:04.166201
3	Cliente	Acceso a tienda y bandeja de pedidos	{"modulos": ["pedidos", "reservas", "devoluciones", "perfil"], "acciones": ["crear", "leer", "actualizar"]}	t	2026-03-15 18:51:04.166201
\.


--
-- Data for Name: seguimiento_incidencias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.seguimiento_incidencias (id, incidencia_id, usuario_id, accion, descripcion, created_at) FROM stdin;
1	1	1	asignacion	Incidencia asignada al operador ID 4	2026-03-24 16:00:00.348889
2	1	4	resolucion	Incidencia marcada como resuelta: el problema a sido solventando por nuestros programadores	2026-03-24 16:01:22.585188
\.


--
-- Data for Name: tipos_producto; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tipos_producto (id, nombre, descripcion, estado, created_at) FROM stdin;
1	Vehículo	Motos y vehículos	t	2026-03-15 18:51:04.166201
2	Repuesto	Repuestos y partes	t	2026-03-15 18:51:04.166201
3	Accesorio	Accesorios y equipamiento	t	2026-03-15 18:51:04.166201
4	REPUESTO	\N	t	2026-03-15 21:17:04.003228
5	MOTO	\N	t	2026-03-15 21:23:57.313434
\.


--
-- Data for Name: usuarios; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuarios (id, username, email, password_hash, nombre_completo, rol_id, cliente_id, estado, ultimo_acceso, intentos_fallidos, bloqueado_hasta, created_at, updated_at) FROM stdin;
4	dylan2912	dylan@gmail.com	$2y$10$h2vmQE/B2oALrhjheXlWaeX7RfguxOK0qgrK.0iaAg3DfktdBkz2S	dylan tablante	6	\N	t	2026-03-24 16:00:52.947199	0	\N	2026-03-22 14:24:15.78738	2026-03-22 14:24:15.78738
2	Raizza	jovita45r@gmail.com	$2y$10$QTWwKWl83KVYwRPAUhY/yOnek2tN/Mw.BnD0TyjUcE7HDfciP7Cfy	Raizza marrero	3	2	t	2026-03-24 16:04:52.681922	0	\N	2026-03-16 03:19:26.111203	2026-03-24 15:58:02.750828
6	juan	juan@gmail.com	$2y$10$beI0zQf0aZ3A4EA2LtfJW.9415zxBjY8BNF/VH.fO.ppgPUAqP8EC	Juan Villegas	5	4	t	2026-03-24 02:59:42.825264	0	\N	2026-03-24 02:58:20.195565	2026-03-24 02:58:20.195565
3	Cesar1234	cesar@gmail.com	$2y$10$mdVNNy0OfhhA7c4eadEhZO3F7zR4/Jo.9oj51CJXA8sQTLMp/mZDq	Cesar	2	\N	t	2026-03-24 16:11:41.760016	0	\N	2026-03-18 12:32:27.434516	2026-03-18 12:32:27.434516
8	gifrank0000	gifrank@gmail.com	$2y$10$pLs0pDsnWHu2KYsYW.NiD.9yU1flRpfr2GCivLWQjweOje9rYWzFu	Gifrank	3	6	t	2026-03-24 03:14:12.612147	0	\N	2026-03-24 03:13:48.082067	2026-03-24 03:13:48.082067
1	admin	2016rojasinversiones@gmail.com	$2y$10$ADCcKWtyWz3SDgJLJQ6VnOB0.01HPSBC.hng7kfu.oWhZQnnJFkQi	Administrador	1	\N	t	2026-03-24 16:59:43.107674	0	\N	2026-03-15 18:51:04.166201	2026-03-16 03:15:19.592718
9	usuario2912	user@gmail.com	$2y$10$PAES.cslmGCZGTbBK1KRku4QcrnwDg1yNQLj6..R0FEfhvWIRil5m	Usuario de prueba123	3	7	t	2026-03-24 15:55:03.682055	0	\N	2026-03-24 15:54:42.300117	2026-03-24 17:02:41.677935
5	luis	luis@gmail.com	$2y$10$sa8n7CwH1B1AiX/rN8Sw7.b.SjZXsxUjtnb4VyBNgc5rtQBo8HrBO	luis rondon	5	\N	t	2026-03-22 14:34:02.385847	0	\N	2026-03-22 14:25:18.027152	2026-03-22 14:25:18.027152
10	usuario	user123@gmail.com	$2y$10$tYlYWpdSLU74HD/R2xZd5OBN4foP.9WaZBjCcCInM3zp4jGMdpRpe	Usuario de prueba123	3	7	t	\N	0	\N	2026-03-24 17:03:43.189927	2026-03-24 17:03:43.189927
7	gi2912frank	gifrank0000@gmail.com	$2y$10$PJXSdLWvIgfRNaORUn3Ye.DrkZFAqHkDuK0Pj8cR56oF8ieCLo.5a	Gifrank	5	5	t	2026-03-24 03:01:50.020008	3	2026-03-24 17:11:42	2026-03-24 03:01:29.077972	2026-03-24 03:01:29.077972
\.


--
-- Data for Name: vehiculos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.vehiculos (id, producto_id, marca, modelo, anio, cilindrada, color, kilometraje, tipo_vehiculo, created_at) FROM stdin;
1	3	Bera	SBR	2020	150cc	Rojo,Morada,Azul	120	Moto	2026-03-15 21:24:05.570024
2	5	bera	SBR	2021	200cc	Negro	120	Moto	2026-03-18 05:09:50.531873
\.


--
-- Data for Name: ventas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.ventas (id, codigo_venta, cliente_id, usuario_id, metodo_pago_id, subtotal, iva, total, estado_venta, observaciones, created_at) FROM stdin;
1	V-20260315223746-251	1	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-15 21:37:46.289757
2	V-20260316002305-742	1	1	1	30.00	4.80	34.80	COMPLETADA		2026-03-15 23:23:05.389608
3	V-20260316002641-835	1	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-15 23:26:41.668385
4	V-20260316005235-187	1	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-15 23:52:35.489566
5	V-20260316005421-991	1	1	1	19.98	3.20	23.18	COMPLETADA		2026-03-15 23:54:21.224039
6	VEN-20260317-46452	1	1	5	30.00	4.80	34.80	COMPLETADA	Venta generada desde reserva: RES-20260316-248	2026-03-17 00:57:32.312688
7	VEN-20260317-46672	1	1	1	30.00	4.80	34.80	COMPLETADA	Venta generada desde reserva: RES-20260317-546 | Referencia: 1234	2026-03-17 02:01:13.861599
8	V-20260318053404-274	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:04.998108
9	V-20260318053428-750	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:28.663668
10	V-20260318053430-488	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:30.235147
11	V-20260318053430-346	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:30.37264
12	V-20260318053430-518	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:30.553596
13	V-20260318053430-634	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:30.664176
14	V-20260318053430-909	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:30.816474
15	V-20260318054423-388	1	1	1	19.98	3.20	23.18	COMPLETADA		2026-03-18 04:44:23.046914
16	V-20260318055031-877	1	1	1	19.98	3.20	23.18	COMPLETADA		2026-03-18 04:50:31.191365
17	V-20260318055256-665	3	1	1	60.00	9.60	69.60	COMPLETADA		2026-03-18 04:52:56.849787
18	V-20260318055605-407	3	1	1	90.00	14.40	104.40	COMPLETADA		2026-03-18 04:56:05.450469
19	VEN-20260318-88856	1	1	1	20.00	3.20	23.20	COMPLETADA	Venta generada desde reserva: RES-20260318-962 | Referencia: 1234	2026-03-18 05:17:45.011829
20	V-20260324024439-229	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-24 01:44:39.069417
\.


--
-- Name: accesorios_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.accesorios_id_seq', 1, true);


--
-- Name: alertas_stock_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.alertas_stock_id_seq', 1, false);


--
-- Name: bitacora_sistema_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bitacora_sistema_id_seq', 107, true);


--
-- Name: categorias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.categorias_id_seq', 3, true);


--
-- Name: clientes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.clientes_id_seq', 7, true);


--
-- Name: compras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.compras_id_seq', 5, true);


--
-- Name: detalle_compras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.detalle_compras_id_seq', 6, true);


--
-- Name: detalle_pedidos_online_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.detalle_pedidos_online_id_seq', 15, true);


--
-- Name: detalle_ventas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.detalle_ventas_id_seq', 20, true);


--
-- Name: devoluciones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.devoluciones_id_seq', 7, true);


--
-- Name: empresa_configuracion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.empresa_configuracion_id_seq', 1, true);


--
-- Name: incidencias_soporte_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.incidencias_soporte_id_seq', 1, true);


--
-- Name: metodos_pago_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.metodos_pago_id_seq', 5, true);


--
-- Name: movimientos_inventario_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.movimientos_inventario_id_seq', 32, true);


--
-- Name: notificaciones_vendedor_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notificaciones_vendedor_id_seq', 9, true);


--
-- Name: pedidos_online_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pedidos_online_id_seq', 14, true);


--
-- Name: producto_imagenes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.producto_imagenes_id_seq', 5, true);


--
-- Name: producto_promociones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.producto_promociones_id_seq', 6, true);


--
-- Name: producto_proveedor_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.producto_proveedor_id_seq', 5, true);


--
-- Name: productos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.productos_id_seq', 5, true);


--
-- Name: promociones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.promociones_id_seq', 5, true);


--
-- Name: proveedores_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.proveedores_id_seq', 3, true);


--
-- Name: repuestos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.repuestos_id_seq', 2, true);


--
-- Name: reservas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reservas_id_seq', 6, true);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_seq', 7, true);


--
-- Name: seguimiento_incidencias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.seguimiento_incidencias_id_seq', 2, true);


--
-- Name: tipos_producto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tipos_producto_id_seq', 5, true);


--
-- Name: usuarios_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuarios_id_seq', 10, true);


--
-- Name: vehiculos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.vehiculos_id_seq', 2, true);


--
-- Name: ventas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.ventas_id_seq', 20, true);


--
-- Name: accesorios accesorios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.accesorios
    ADD CONSTRAINT accesorios_pkey PRIMARY KEY (id);


--
-- Name: accesorios accesorios_producto_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.accesorios
    ADD CONSTRAINT accesorios_producto_id_key UNIQUE (producto_id);


--
-- Name: alertas_stock alertas_stock_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.alertas_stock
    ADD CONSTRAINT alertas_stock_pkey PRIMARY KEY (id);


--
-- Name: bitacora_sistema bitacora_sistema_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_sistema
    ADD CONSTRAINT bitacora_sistema_pkey PRIMARY KEY (id);


--
-- Name: categorias categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_pkey PRIMARY KEY (id);


--
-- Name: clientes clientes_cedula_rif_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_cedula_rif_key UNIQUE (cedula_rif);


--
-- Name: clientes clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_pkey PRIMARY KEY (id);


--
-- Name: compras compras_codigo_compra_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.compras
    ADD CONSTRAINT compras_codigo_compra_key UNIQUE (codigo_compra);


--
-- Name: compras compras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.compras
    ADD CONSTRAINT compras_pkey PRIMARY KEY (id);


--
-- Name: configuracion_integraciones configuracion_integraciones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.configuracion_integraciones
    ADD CONSTRAINT configuracion_integraciones_pkey PRIMARY KEY (clave);


--
-- Name: detalle_compras detalle_compras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_compras
    ADD CONSTRAINT detalle_compras_pkey PRIMARY KEY (id);


--
-- Name: detalle_pedidos_online detalle_pedidos_online_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_pedidos_online
    ADD CONSTRAINT detalle_pedidos_online_pkey PRIMARY KEY (id);


--
-- Name: detalle_ventas detalle_ventas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_ventas
    ADD CONSTRAINT detalle_ventas_pkey PRIMARY KEY (id);


--
-- Name: devoluciones devoluciones_codigo_devolucion_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.devoluciones
    ADD CONSTRAINT devoluciones_codigo_devolucion_key UNIQUE (codigo_devolucion);


--
-- Name: devoluciones devoluciones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.devoluciones
    ADD CONSTRAINT devoluciones_pkey PRIMARY KEY (id);


--
-- Name: empresa_configuracion empresa_configuracion_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.empresa_configuracion
    ADD CONSTRAINT empresa_configuracion_pkey PRIMARY KEY (id);


--
-- Name: empresa_configuracion empresa_configuracion_rif_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.empresa_configuracion
    ADD CONSTRAINT empresa_configuracion_rif_key UNIQUE (rif);


--
-- Name: incidencias_soporte incidencias_soporte_codigo_incidencia_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_soporte
    ADD CONSTRAINT incidencias_soporte_codigo_incidencia_key UNIQUE (codigo_incidencia);


--
-- Name: incidencias_soporte incidencias_soporte_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_soporte
    ADD CONSTRAINT incidencias_soporte_pkey PRIMARY KEY (id);


--
-- Name: metodos_pago metodos_pago_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.metodos_pago
    ADD CONSTRAINT metodos_pago_pkey PRIMARY KEY (id);


--
-- Name: movimientos_inventario movimientos_inventario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.movimientos_inventario
    ADD CONSTRAINT movimientos_inventario_pkey PRIMARY KEY (id);


--
-- Name: notificaciones_vendedor notificaciones_vendedor_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notificaciones_vendedor
    ADD CONSTRAINT notificaciones_vendedor_pkey PRIMARY KEY (id);


--
-- Name: pedidos_online pedidos_online_codigo_pedido_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos_online
    ADD CONSTRAINT pedidos_online_codigo_pedido_key UNIQUE (codigo_pedido);


--
-- Name: pedidos_online pedidos_online_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos_online
    ADD CONSTRAINT pedidos_online_pkey PRIMARY KEY (id);


--
-- Name: producto_imagenes producto_imagenes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_imagenes
    ADD CONSTRAINT producto_imagenes_pkey PRIMARY KEY (id);


--
-- Name: producto_promociones producto_promociones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_promociones
    ADD CONSTRAINT producto_promociones_pkey PRIMARY KEY (id);


--
-- Name: producto_proveedor producto_proveedor_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_proveedor
    ADD CONSTRAINT producto_proveedor_pkey PRIMARY KEY (id);


--
-- Name: producto_proveedor producto_proveedor_producto_id_proveedor_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_proveedor
    ADD CONSTRAINT producto_proveedor_producto_id_proveedor_id_key UNIQUE (producto_id, proveedor_id);


--
-- Name: productos productos_codigo_interno_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_codigo_interno_key UNIQUE (codigo_interno);


--
-- Name: productos productos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_pkey PRIMARY KEY (id);


--
-- Name: promociones promociones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promociones
    ADD CONSTRAINT promociones_pkey PRIMARY KEY (id);


--
-- Name: proveedores proveedores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.proveedores
    ADD CONSTRAINT proveedores_pkey PRIMARY KEY (id);


--
-- Name: proveedores proveedores_rif_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.proveedores
    ADD CONSTRAINT proveedores_rif_key UNIQUE (rif);


--
-- Name: repuestos repuestos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.repuestos
    ADD CONSTRAINT repuestos_pkey PRIMARY KEY (id);


--
-- Name: repuestos repuestos_producto_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.repuestos
    ADD CONSTRAINT repuestos_producto_id_key UNIQUE (producto_id);


--
-- Name: reservas reservas_codigo_reserva_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_codigo_reserva_key UNIQUE (codigo_reserva);


--
-- Name: reservas reservas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_pkey PRIMARY KEY (id);


--
-- Name: roles roles_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nombre_key UNIQUE (nombre);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: seguimiento_incidencias seguimiento_incidencias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seguimiento_incidencias
    ADD CONSTRAINT seguimiento_incidencias_pkey PRIMARY KEY (id);


--
-- Name: tipos_producto tipos_producto_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipos_producto
    ADD CONSTRAINT tipos_producto_nombre_key UNIQUE (nombre);


--
-- Name: tipos_producto tipos_producto_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipos_producto
    ADD CONSTRAINT tipos_producto_pkey PRIMARY KEY (id);


--
-- Name: usuarios usuarios_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_email_key UNIQUE (email);


--
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id);


--
-- Name: usuarios usuarios_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_username_key UNIQUE (username);


--
-- Name: vehiculos vehiculos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vehiculos
    ADD CONSTRAINT vehiculos_pkey PRIMARY KEY (id);


--
-- Name: vehiculos vehiculos_producto_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vehiculos
    ADD CONSTRAINT vehiculos_producto_id_key UNIQUE (producto_id);


--
-- Name: ventas ventas_codigo_venta_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_codigo_venta_key UNIQUE (codigo_venta);


--
-- Name: ventas ventas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_pkey PRIMARY KEY (id);


--
-- Name: idx_bitacora_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_bitacora_usuario ON public.bitacora_sistema USING btree (usuario_id);


--
-- Name: idx_incidencias_asignado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_incidencias_asignado ON public.incidencias_soporte USING btree (asignado_a);


--
-- Name: idx_incidencias_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_incidencias_cliente ON public.incidencias_soporte USING btree (cliente_id);


--
-- Name: idx_inventario_producto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_inventario_producto ON public.movimientos_inventario USING btree (producto_id);


--
-- Name: idx_notif_usuario_leida; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_notif_usuario_leida ON public.notificaciones_vendedor USING btree (usuario_id, leida);


--
-- Name: idx_pedidos_canal; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pedidos_canal ON public.pedidos_online USING btree (canal_comunicacion);


--
-- Name: idx_pedidos_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pedidos_cliente ON public.pedidos_online USING btree (cliente_id);


--
-- Name: idx_pedidos_estado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pedidos_estado ON public.pedidos_online USING btree (estado_pedido);


--
-- Name: idx_producto_imagenes_producto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_producto_imagenes_producto ON public.producto_imagenes USING btree (producto_id);


--
-- Name: idx_productos_categoria; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_productos_categoria ON public.productos USING btree (categoria_id);


--
-- Name: idx_seguimiento_incidencia; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_seguimiento_incidencia ON public.seguimiento_incidencias USING btree (incidencia_id);


--
-- Name: idx_usuarios_rol; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_usuarios_rol ON public.usuarios USING btree (rol_id);


--
-- Name: accesorios accesorios_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.accesorios
    ADD CONSTRAINT accesorios_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: alertas_stock alertas_stock_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.alertas_stock
    ADD CONSTRAINT alertas_stock_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id);


--
-- Name: bitacora_sistema bitacora_sistema_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_sistema
    ADD CONSTRAINT bitacora_sistema_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- Name: categorias categorias_tipo_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_tipo_producto_id_fkey FOREIGN KEY (tipo_producto_id) REFERENCES public.tipos_producto(id);


--
-- Name: compras compras_proveedor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.compras
    ADD CONSTRAINT compras_proveedor_id_fkey FOREIGN KEY (proveedor_id) REFERENCES public.proveedores(id);


--
-- Name: compras compras_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.compras
    ADD CONSTRAINT compras_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- Name: detalle_compras detalle_compras_compra_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_compras
    ADD CONSTRAINT detalle_compras_compra_id_fkey FOREIGN KEY (compra_id) REFERENCES public.compras(id);


--
-- Name: detalle_compras detalle_compras_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_compras
    ADD CONSTRAINT detalle_compras_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id);


--
-- Name: detalle_pedidos_online detalle_pedidos_online_pedido_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_pedidos_online
    ADD CONSTRAINT detalle_pedidos_online_pedido_id_fkey FOREIGN KEY (pedido_id) REFERENCES public.pedidos_online(id) ON DELETE CASCADE;


--
-- Name: detalle_pedidos_online detalle_pedidos_online_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_pedidos_online
    ADD CONSTRAINT detalle_pedidos_online_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id);


--
-- Name: detalle_ventas detalle_ventas_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_ventas
    ADD CONSTRAINT detalle_ventas_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id);


--
-- Name: detalle_ventas detalle_ventas_venta_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_ventas
    ADD CONSTRAINT detalle_ventas_venta_id_fkey FOREIGN KEY (venta_id) REFERENCES public.ventas(id);


--
-- Name: devoluciones devoluciones_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.devoluciones
    ADD CONSTRAINT devoluciones_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.clientes(id);


--
-- Name: devoluciones devoluciones_pedido_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.devoluciones
    ADD CONSTRAINT devoluciones_pedido_id_fkey FOREIGN KEY (pedido_id) REFERENCES public.pedidos_online(id);


--
-- Name: devoluciones devoluciones_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.devoluciones
    ADD CONSTRAINT devoluciones_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id);


--
-- Name: devoluciones devoluciones_venta_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.devoluciones
    ADD CONSTRAINT devoluciones_venta_id_fkey FOREIGN KEY (venta_id) REFERENCES public.ventas(id);


--
-- Name: clientes fk_cliente_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT fk_cliente_usuario FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- Name: incidencias_soporte incidencias_soporte_asignado_a_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_soporte
    ADD CONSTRAINT incidencias_soporte_asignado_a_fkey FOREIGN KEY (asignado_a) REFERENCES public.usuarios(id);


--
-- Name: incidencias_soporte incidencias_soporte_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_soporte
    ADD CONSTRAINT incidencias_soporte_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.clientes(id);


--
-- Name: incidencias_soporte incidencias_soporte_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_soporte
    ADD CONSTRAINT incidencias_soporte_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- Name: movimientos_inventario movimientos_inventario_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.movimientos_inventario
    ADD CONSTRAINT movimientos_inventario_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id);


--
-- Name: notificaciones_vendedor notificaciones_vendedor_pedido_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notificaciones_vendedor
    ADD CONSTRAINT notificaciones_vendedor_pedido_id_fkey FOREIGN KEY (pedido_id) REFERENCES public.pedidos_online(id) ON DELETE CASCADE;


--
-- Name: notificaciones_vendedor notificaciones_vendedor_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notificaciones_vendedor
    ADD CONSTRAINT notificaciones_vendedor_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: pedidos_online pedidos_online_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos_online
    ADD CONSTRAINT pedidos_online_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.clientes(id);


--
-- Name: pedidos_online pedidos_online_metodo_pago_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos_online
    ADD CONSTRAINT pedidos_online_metodo_pago_id_fkey FOREIGN KEY (metodo_pago_id) REFERENCES public.metodos_pago(id);


--
-- Name: pedidos_online pedidos_online_vendedor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos_online
    ADD CONSTRAINT pedidos_online_vendedor_id_fkey FOREIGN KEY (vendedor_asignado_id) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: producto_imagenes producto_imagenes_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_imagenes
    ADD CONSTRAINT producto_imagenes_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: producto_promociones producto_promociones_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_promociones
    ADD CONSTRAINT producto_promociones_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id);


--
-- Name: producto_promociones producto_promociones_promocion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_promociones
    ADD CONSTRAINT producto_promociones_promocion_id_fkey FOREIGN KEY (promocion_id) REFERENCES public.promociones(id);


--
-- Name: producto_proveedor producto_proveedor_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_proveedor
    ADD CONSTRAINT producto_proveedor_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: producto_proveedor producto_proveedor_proveedor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_proveedor
    ADD CONSTRAINT producto_proveedor_proveedor_id_fkey FOREIGN KEY (proveedor_id) REFERENCES public.proveedores(id) ON DELETE CASCADE;


--
-- Name: productos productos_categoria_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_categoria_id_fkey FOREIGN KEY (categoria_id) REFERENCES public.categorias(id);


--
-- Name: productos productos_proveedor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_proveedor_id_fkey FOREIGN KEY (proveedor_id) REFERENCES public.proveedores(id);


--
-- Name: productos productos_tipo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_tipo_id_fkey FOREIGN KEY (tipo_id) REFERENCES public.tipos_producto(id);


--
-- Name: repuestos repuestos_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.repuestos
    ADD CONSTRAINT repuestos_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: reservas reservas_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.clientes(id);


--
-- Name: reservas reservas_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id);


--
-- Name: seguimiento_incidencias seguimiento_incidencias_incidencia_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seguimiento_incidencias
    ADD CONSTRAINT seguimiento_incidencias_incidencia_id_fkey FOREIGN KEY (incidencia_id) REFERENCES public.incidencias_soporte(id) ON DELETE CASCADE;


--
-- Name: seguimiento_incidencias seguimiento_incidencias_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seguimiento_incidencias
    ADD CONSTRAINT seguimiento_incidencias_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- Name: usuarios usuarios_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.clientes(id);


--
-- Name: usuarios usuarios_rol_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_rol_id_fkey FOREIGN KEY (rol_id) REFERENCES public.roles(id);


--
-- Name: vehiculos vehiculos_producto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vehiculos
    ADD CONSTRAINT vehiculos_producto_id_fkey FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: ventas ventas_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.clientes(id);


--
-- Name: ventas ventas_metodo_pago_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_metodo_pago_id_fkey FOREIGN KEY (metodo_pago_id) REFERENCES public.metodos_pago(id);


--
-- Name: ventas ventas_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- PostgreSQL database dump complete
--

\unrestrict OTbQRkiXldTBCzZlrXycbicvdqLTEY0giEhMFAoQqdVum2jQAhFXj1FNTJgewSO


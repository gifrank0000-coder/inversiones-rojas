--
-- PostgreSQL database dump
--

\restrict 12VhFCYwxsRdd1D1vw1o28d1gC55UDCHJanlUS5W5yPJAOQ8wf6UueH3m03hhF6

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
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    moneda_factura character varying(3) DEFAULT 'USD'::character varying,
    tasa_cambio numeric(12,4),
    monto_bs numeric(12,2),
    monto_usd numeric(12,2),
    moneda_pago character varying(10) DEFAULT 'USD'::character varying,
    metodo_pago_id integer
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
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    precio_unitario_bs numeric(12,2),
    precio_unitario_usd numeric(12,2)
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
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    precio_unitario_bs numeric(12,2),
    precio_unitario_usd numeric(12,2)
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
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    moneda character varying(10) DEFAULT 'AMBOS'::character varying
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
-- Name: metodos_pago_reservas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.metodos_pago_reservas (
    id integer NOT NULL,
    tipo character varying(50) NOT NULL,
    banco character varying(100),
    cedula character varying(20),
    telefono character varying(15),
    numero_cuenta character varying(50),
    codigo_banco character varying(10),
    estado boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.metodos_pago_reservas OWNER TO postgres;

--
-- Name: metodos_pago_reservas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.metodos_pago_reservas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.metodos_pago_reservas_id_seq OWNER TO postgres;

--
-- Name: metodos_pago_reservas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.metodos_pago_reservas_id_seq OWNED BY public.metodos_pago_reservas.id;


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
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    comprobante_url character varying(500),
    metodo_pago character varying(200)
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
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    moneda_base character varying(3) DEFAULT 'USD'::character varying,
    precio_venta_bs numeric(12,2),
    precio_venta_usd numeric(12,2),
    precio_compra_bs numeric(12,2),
    precio_compra_usd numeric(12,2),
    stock_reservado integer DEFAULT 0
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
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    monto_adelanto numeric(12,2) DEFAULT 0,
    fecha_cuota date,
    monto_restante numeric(12,2) DEFAULT 0,
    estado_pago character varying(20) DEFAULT 'PENDIENTE'::character varying,
    referencia_pago character varying(50),
    metodo_pago character varying(50),
    fecha_pago timestamp without time zone,
    comprobante_url text,
    ip_address character varying(45),
    user_agent text,
    metodo_pago_resto character varying(100),
    referencia_pago_resto character varying(100),
    comprobante_url_resto text,
    monto_pagado_resto numeric(12,2) DEFAULT 0,
    fecha_pago_resto timestamp without time zone,
    subtotal numeric(12,2) DEFAULT 0,
    iva numeric(12,2) DEFAULT 0,
    monto_total numeric(12,2) DEFAULT 0
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
-- Name: tasas_cambio; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tasas_cambio (
    id integer NOT NULL,
    tasa numeric(12,4) NOT NULL,
    moneda_origen character varying(3) DEFAULT 'USD'::character varying,
    moneda_destino character varying(3) DEFAULT 'VES'::character varying,
    fecha_vigencia date NOT NULL,
    usuario_id integer,
    observaciones text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.tasas_cambio OWNER TO postgres;

--
-- Name: tasas_cambio_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tasas_cambio_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tasas_cambio_id_seq OWNER TO postgres;

--
-- Name: tasas_cambio_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tasas_cambio_id_seq OWNED BY public.tasas_cambio.id;


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
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    moneda_pago character varying(3) DEFAULT 'USD'::character varying,
    tasa_cambio numeric(12,4),
    monto_bs numeric(12,2),
    monto_usd numeric(12,2)
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
-- Name: metodos_pago_reservas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.metodos_pago_reservas ALTER COLUMN id SET DEFAULT nextval('public.metodos_pago_reservas_id_seq'::regclass);


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
-- Name: tasas_cambio id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tasas_cambio ALTER COLUMN id SET DEFAULT nextval('public.tasas_cambio_id_seq'::regclass);


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
2	13	CHAQUETA	L	VERDE	TELA IMPERMEABLE	REVIT	DOT	2026-04-28 00:53:44.889882
3	14	CHAQUETA	L	NEGRO	POLICARBONATO	INVICTUS	DOT	2026-04-28 01:15:44.958319
5	16	CHAQUETA	L	NEGRO	CUERO	NORMANDIE	DOT	2026-04-28 01:24:08.990974
4	15	CHAQUETA	L	NEGRO	TELA IMPERMEABLE	ALPINESTAR	DOT	2026-04-28 01:20:59.50949
6	17	CHAQUETA	L	NEGRO	CUERO	DAINESE	DOT	2026-04-28 01:32:18.599535
7	18	CASCO	L	AZUL	FIBRA DE CARBONO	BERA	DOT	2026-04-28 02:04:14.136267
8	19	CASCO	L	BLANCO	FIBRA DE CARBONO	BERA	DOT	2026-04-28 02:07:31.055205
9	20	CASCO	L	NEGRO	FIBRA DE CARBONO	BERA	DOT	2026-04-28 02:11:06.255642
10	21	CASCO	L	ROJO	FIBRA DE CARBONO	BERA	DOT	2026-04-28 02:15:50.1425
11	22	CASCO	L	NEGRO	FIBRA DE CARBONO	BERA	DOT	2026-04-28 02:18:56.746204
12	39	GUANTES	L	NEGRO	TELA	EDGE	DOT	2026-04-28 17:10:33.412373
13	40	GUANTES	L	NEGRO	TELA	ALPINESTAR	DOT	2026-04-28 17:16:04.46817
14	41	GUANTES	L	AZUL	TELA	BERA	DOT	2026-04-28 17:22:47.491098
15	42	GUANTES	L	NEGRO	TELA	BERA	DOT	2026-04-28 17:35:36.540123
16	43	GUANTES	L	NEGRO	CUERO	BERA	DOT	2026-04-28 17:39:14.719324
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
108	1	BACKUP_DOWNLOAD	sistema	\N	{"archivo": "backup_2026-03-24_18-12-10.sql"}	::1	\N	2026-03-24 17:12:18.097326
111	3	Actualizó su perfil	usuarios	\N	\N	\N	\N	2026-03-24 20:47:31.473051
112	2	Actualizó su perfil	usuarios	\N	\N	\N	\N	2026-03-24 20:51:55.739072
109	1	CREAR_CATEGORIA	categorias	4	{"nombre": "Frenos", "tipo_id": 4, "descripcion": "xxx", "tipo_producto": "REPUESTO"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-24 17:31:48.540589
110	1	CREAR_PRODUCTO	productos	6	{"tipo": "repuesto", "codigo": "RP-436764-355", "nombre": "Producto de ejemplo", "stock_inicial": 19}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-24 17:32:25.989444
113	2	PEDIDO_DIGITAL	pedidos_online	15	{"canal": "whatsapp", "total": 23.2, "codigo": "PED-20260326-35744", "productos": 1}	\N	\N	2026-03-26 05:53:24.866167
114	1	PEDIDO_ESTADO	pedidos_online	15	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-26 05:55:09.4468
115	1	PEDIDO_ESTADO	pedidos_online	15	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-26 05:55:19.670453
116	1	PEDIDO_DIGITAL	pedidos_online	16	{"canal": "notificaciones", "total": 23.2, "codigo": "PED-20260326-13084", "productos": 1}	\N	\N	2026-03-26 05:55:50.285861
117	1	PEDIDO_ASIGNAR_VENDEDOR	pedidos_online	16	{"seller_id": 3}	\N	\N	2026-03-26 05:56:16.182239
118	3	PEDIDO_ESTADO	pedidos_online	16	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-26 05:56:56.010696
119	3	PEDIDO_ESTADO	pedidos_online	16	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-26 05:57:04.057415
120	1	CREAR_RESERVA	reservas	0	{"total": 34.8, "codigo": "RES-20260326-214"}	\N	\N	2026-03-26 10:43:08.242909
121	1	PEDIDO_DIGITAL	pedidos_online	18	{"canal": "whatsapp", "total": 34.8, "codigo": "PED-20260326-16321", "productos": 1}	\N	\N	2026-03-26 10:45:26.315394
122	1	PEDIDO_ESTADO	pedidos_online	18	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-26 10:46:18.290063
123	1	PEDIDO_ESTADO	pedidos_online	18	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-26 10:46:25.828177
124	1	COMPLETAR_RESERVA	ventas	21	{"total": 34.8, "productos": 1, "codigo_venta": "VEN-20260326-75609", "codigo_reserva": "RES-20260326-214", "metodo_pago_id": "1"}	\N	\N	2026-03-26 10:47:06.900623
125	1	PEDIDO_DIGITAL	pedidos_online	19	{"canal": "telegram", "total": 34.8, "codigo": "PED-20260326-61309", "productos": 1}	\N	\N	2026-03-26 10:48:08.347823
126	1	PEDIDO_ESTADO	pedidos_online	19	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-26 10:48:34.749282
127	1	PEDIDO_ESTADO	pedidos_online	19	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-26 10:48:41.628643
128	1	INHABILITAR_PROMOCION	promociones	2	{"id": 2}	\N	\N	2026-03-26 10:49:38.240017
129	1	INHABILITAR_PROMOCION	promociones	1	{"id": 1}	\N	\N	2026-03-26 10:49:41.974063
130	1	INHABILITAR_PROMOCION	promociones	5	{"id": 5}	\N	\N	2026-03-26 10:50:06.179502
131	1	CREAR_PRODUCTO	productos	7	{"tipo": "repuesto", "codigo": "RP-147549-159", "nombre": "producto de prueba2", "stock_inicial": 10}	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-26 13:53:30.305694
132	1	CREAR_USUARIO	usuarios	11	{"rol_id": 2, "usuario_creado": "vendedor"}	::1	\N	2026-03-26 15:54:36.962491
133	1	CREAR_USUARIO	usuarios	12	{"rol_id": 6, "usuario_creado": "operador"}	::1	\N	2026-03-26 15:55:17.678196
134	1	CREAR_USUARIO	usuarios	13	{"rol_id": 5, "usuario_creado": "Gerente"}	::1	\N	2026-03-26 15:56:06.060002
135	2	Actualizó su perfil	usuarios	\N	\N	\N	\N	2026-03-26 15:58:32.274502
136	2	Actualizó su perfil	usuarios	\N	\N	\N	\N	2026-03-26 15:58:49.620213
137	1	Actualizó su perfil	usuarios	\N	\N	\N	\N	2026-03-26 16:01:58.904324
138	14	REGISTRO_USUARIO	usuarios	14	{"email": "andred@gmail.com", "username": "andres14"}	::1	\N	2026-03-26 17:22:50.691548
139	1	CREAR_COMPRA	compras	6	{"total": 69.6, "codigo": "OC202603262DC413"}	\N	\N	2026-03-26 17:37:33.052135
140	1	EMAIL_ORDEN_COMPRA	compras	6	{"codigo": "OC202603262DC413", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-03-26 17:37:38.439318
141	1	CAMBIAR_ESTADO_COMPRA	compras	6	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-03-26 17:42:52.794972
142	1	CAMBIAR_ESTADO_COMPRA	compras	6	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-03-26 17:43:17.084527
143	1	CREAR_PROMOCION	promociones	6	{"nombre": "oferta de verano"}	\N	\N	2026-03-26 17:55:24.858014
144	1	PEDIDO_DIGITAL	pedidos_online	20	{"canal": "whatsapp", "total": 23.2, "codigo": "PED-20260326-89559", "productos": 1}	\N	\N	2026-03-26 17:57:54.101449
145	1	PEDIDO_ESTADO	pedidos_online	20	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-28 03:33:23.859898
146	1	PEDIDO_ESTADO	pedidos_online	20	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-03-28 03:33:44.661071
147	15	REGISTRO_USUARIO	usuarios	15	{"email": "gifrank2912@mail.com", "username": "gifrank"}	127.0.0.1	\N	2026-03-28 18:43:23.971097
148	1	HABILITAR_PROMOCION	promociones	5	{"id": 5}	\N	\N	2026-03-28 21:01:46.95826
149	1	INHABILITAR_PROMOCION	promociones	5	{"id": 5}	\N	\N	2026-03-28 21:53:28.290778
150	1	HABILITAR_PROMOCION	promociones	5	{"id": 5}	\N	\N	2026-03-28 21:53:52.534375
151	1	HABILITAR_PROMOCION	promociones	4	{"id": 4}	\N	\N	2026-03-28 21:53:55.678144
152	1	HABILITAR_PROMOCION	promociones	3	{"id": 3}	\N	\N	2026-03-29 00:46:43.833105
153	1	INHABILITAR_PROMOCION	promociones	4	{"id": 4}	\N	\N	2026-03-29 00:48:44.465165
154	1	INHABILITAR_PROMOCION	promociones	3	{"id": 3}	\N	\N	2026-03-29 00:48:50.431766
155	1	HABILITAR_PROMOCION	promociones	4	{"id": 4}	\N	\N	2026-04-01 00:02:18.21333
156	1	INHABILITAR_PROMOCION	promociones	4	{"id": 4}	\N	\N	2026-04-01 00:02:24.572355
157	1	CREAR_PRODUCTO	productos	8	{"tipo": "vehiculo", "codigo": "VH-479331-106", "nombre": "xxx", "stock_inicial": 10}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-04-05 22:39:34.495429
158	1	CREAR_PRODUCTO	productos	9	{"tipo": "vehiculo", "codigo": "VH-422268-306", "nombre": "Moto sbr", "stock_inicial": 10}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0	2026-04-06 13:20:19.665111
159	1	CREAR_RESERVA	reservas	0	{"total": 2320, "codigo": "RES-20260415-745"}	\N	\N	2026-04-15 02:28:36.676127
160	1	COMPLETAR_RESERVA	ventas	26	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260415-09276", "codigo_reserva": "RES-20260415-745", "metodo_pago_id": "1"}	\N	\N	2026-04-15 02:29:29.130744
161	1	COMPLETAR_RESERVA	ventas	27	{"total": 46.4, "productos": 1, "codigo_venta": "VEN-20260415-32437", "codigo_reserva": "RES-20260317-509", "metodo_pago_id": "5"}	\N	\N	2026-04-15 02:29:34.458035
162	1	COMPLETAR_RESERVA	ventas	28	{"total": 46.4, "productos": 1, "codigo_venta": "VEN-20260415-67335", "codigo_reserva": "RES-20260317-908", "metodo_pago_id": "5"}	\N	\N	2026-04-15 02:29:38.89021
163	1	COMPLETAR_RESERVA	ventas	29	{"total": 34.8, "productos": 1, "codigo_venta": "VEN-20260415-25147", "codigo_reserva": "RES-20260317-883", "metodo_pago_id": "5"}	\N	\N	2026-04-15 02:29:42.377607
164	1	CREAR_COMPRA	compras	7	{"total": 23.2, "codigo": "OC20260418A040C0"}	\N	\N	2026-04-18 03:04:29.876652
165	1	EMAIL_ORDEN_COMPRA	compras	7	{"codigo": "OC20260418A040C0", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-04-18 03:04:35.253727
166	1	CREAR_PROMOCION	promociones	7	{"nombre": "oferta nueva"}	\N	\N	2026-04-18 03:09:00.614027
167	1	ACTUALIZAR_PROMOCION	promociones	7	{"nombre": "oferta nueva"}	\N	\N	2026-04-18 03:21:00.381773
168	1	CREAR_COMPRA	compras	8	{"total": 34.8, "codigo": "OC2026041878FC24"}	\N	\N	2026-04-18 04:35:39.627065
169	1	EMAIL_ORDEN_COMPRA	compras	8	{"codigo": "OC2026041878FC24", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-04-18 04:35:43.846806
170	1	CAMBIAR_ESTADO_COMPRA	compras	7	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-18 04:36:00.732553
171	1	CAMBIAR_ESTADO_COMPRA	compras	8	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-18 04:36:05.54805
172	1	CAMBIAR_ESTADO_COMPRA	compras	8	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-18 04:36:10.371149
173	1	CAMBIAR_ESTADO_COMPRA	compras	7	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-18 04:36:15.08644
174	1	CREAR_COMPRA	compras	9	{"total": 23.2, "codigo": "OC20260418BA7C42"}	\N	\N	2026-04-18 04:38:04.503372
175	1	EMAIL_ORDEN_COMPRA	compras	9	{"codigo": "OC20260418BA7C42", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-04-18 04:38:09.184693
176	1	CAMBIAR_ESTADO_COMPRA	compras	9	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-18 04:38:15.459551
177	1	CAMBIAR_ESTADO_COMPRA	compras	9	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-18 04:38:20.511951
178	1	CREAR_COMPRA	compras	10	{"total": 452.4, "codigo": "OC202604189C1509"}	\N	\N	2026-04-18 04:39:14.492332
179	1	EMAIL_ORDEN_COMPRA	compras	10	{"codigo": "OC202604189C1509", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-04-18 04:39:17.540713
180	1	CAMBIAR_ESTADO_COMPRA	compras	10	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-18 04:39:22.81804
181	1	CAMBIAR_ESTADO_COMPRA	compras	10	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-18 04:39:27.962768
182	1	CREAR_RESERVA	reservas	0	{"total": 2320, "codigo": "RES-20260419-597"}	\N	\N	2026-04-19 02:25:26.880499
183	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 46.4, "codigo": "RES-20260419-123", "adelanto": 12}	\N	\N	2026-04-19 15:15:56.051396
184	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 46.4, "codigo": "RES-20260419-760", "adelanto": 12}	\N	\N	2026-04-19 15:18:40.107021
185	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260419-415", "adelanto": 580}	\N	\N	2026-04-19 17:04:38.152983
186	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260419-411", "adelanto": 580}	\N	\N	2026-04-19 17:29:31.538735
187	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260419-731", "adelanto": 580}	\N	\N	2026-04-19 17:30:03.596781
188	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260419-286", "adelanto": 580}	\N	\N	2026-04-19 17:30:20.535594
189	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260419-033", "adelanto": 580}	\N	\N	2026-04-19 20:01:03.568792
190	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260420-875", "adelanto": 580}	\N	\N	2026-04-19 23:30:03.822555
191	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 4640, "codigo": "RES-20260420-217", "adelanto": 1160}	\N	\N	2026-04-19 23:49:15.383027
192	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260420-025", "adelanto": 580}	\N	\N	2026-04-20 05:02:31.261689
193	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260420-924", "adelanto": 580}	\N	\N	2026-04-20 13:18:37.621318
194	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 39.44, "codigo": "RES-20260420-745", "adelanto": 9.86}	\N	\N	2026-04-20 15:24:00.55397
195	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 46.4, "codigo": "RES-20260420-862", "adelanto": 11.6}	\N	\N	2026-04-20 15:51:43.170113
196	1	COMPLETAR_RESERVA	ventas	31	{"total": 46.4, "productos": 1, "codigo_venta": "VEN-20260420-89920", "codigo_reserva": "RES-20260420-862", "metodo_pago_id": "1"}	\N	\N	2026-04-20 16:29:28.811293
197	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260420-075", "adelanto": 580}	\N	\N	2026-04-20 17:43:12.657952
198	1	COMPLETAR_RESERVA	ventas	32	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260420-88217", "codigo_reserva": "RES-20260420-075", "metodo_pago_id": "5"}	\N	\N	2026-04-20 17:45:37.833764
199	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260420-381", "adelanto": 580}	\N	\N	2026-04-20 17:46:20.62419
200	1	COMPLETAR_RESERVA	ventas	33	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260420-72021", "codigo_reserva": "RES-20260420-381", "metodo_pago_id": "5"}	\N	\N	2026-04-20 17:46:55.745305
201	1	COMPLETAR_RESERVA	ventas	34	{"total": 46.4, "productos": 1, "codigo_venta": "VEN-20260420-40694", "codigo_reserva": "RES-20260420-745", "metodo_pago_id": "1"}	\N	\N	2026-04-20 17:47:19.422122
202	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260420-071", "adelanto": 580}	\N	\N	2026-04-20 18:32:53.01452
203	1	COMPLETAR_RESERVA	ventas	35	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260420-94059", "codigo_reserva": "RES-20260420-071", "metodo_pago_id": "5"}	\N	\N	2026-04-20 18:44:03.002289
204	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 39.44, "codigo": "RES-20260420-873", "adelanto": 9.86}	\N	\N	2026-04-20 18:45:38.789932
205	1	COMPLETAR_RESERVA	ventas	36	{"total": 46.4, "productos": 1, "codigo_venta": "VEN-20260420-75960", "codigo_reserva": "RES-20260420-873", "metodo_pago_id": "5"}	\N	\N	2026-04-20 18:46:06.444905
206	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260420-354", "adelanto": 580}	\N	\N	2026-04-20 18:56:04.628697
207	1	COMPLETAR_RESERVA	ventas	37	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260420-46989", "codigo_reserva": "RES-20260420-354", "metodo_pago_id": "5"}	\N	\N	2026-04-20 18:56:23.030091
208	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 23.2, "codigo": "RES-20260420-262", "adelanto": 5.8}	\N	\N	2026-04-20 19:11:20.433349
209	1	COMPLETAR_RESERVA	ventas	38	{"total": 23.2, "productos": 1, "codigo_venta": "VEN-20260420-63722", "codigo_reserva": "RES-20260420-262", "metodo_pago_id": "5"}	\N	\N	2026-04-20 19:12:11.240867
210	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 23.18, "codigo": "RES-20260420-865", "adelanto": 5.8}	\N	\N	2026-04-20 19:30:31.630939
211	1	COMPLETAR_RESERVA	ventas	39	{"total": 23.18, "productos": 1, "codigo_venta": "VEN-20260420-20219", "codigo_reserva": "RES-20260420-865", "metodo_pago_id": "5"}	\N	\N	2026-04-20 19:31:08.416014
212	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 39.44, "codigo": "RES-20260420-788", "adelanto": 9.86}	\N	\N	2026-04-20 19:48:49.716944
213	1	COMPLETAR_RESERVA	ventas	40	{"total": 46.4, "productos": 1, "codigo_venta": "VEN-20260420-81120", "codigo_reserva": "RES-20260420-788", "metodo_pago_id": "1"}	\N	\N	2026-04-20 19:49:58.20574
214	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 39.44, "codigo": "RES-20260420-748", "adelanto": 10}	\N	\N	2026-04-20 19:55:24.174659
215	1	COMPLETAR_RESERVA	ventas	41	{"total": 46.4, "productos": 1, "codigo_venta": "VEN-20260420-16980", "codigo_reserva": "RES-20260420-748", "metodo_pago_id": "5"}	\N	\N	2026-04-20 20:01:40.839325
216	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 39.44, "codigo": "RES-20260420-903", "adelanto": 9.86}	\N	\N	2026-04-20 20:44:53.68226
217	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 39.44, "codigo": "RES-20260420-227", "adelanto": 9.86}	\N	\N	2026-04-20 21:37:39.958561
218	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260420-951", "adelanto": 580}	\N	\N	2026-04-20 21:44:37.638375
219	1	COMPLETAR_RESERVA	ventas	42	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260420-65832", "codigo_reserva": "RES-20260420-951", "metodo_pago_id": "5"}	\N	\N	2026-04-20 21:46:44.940163
220	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 23.2, "codigo": "RES-20260421-855", "adelanto": 5.8}	\N	\N	2026-04-20 22:17:25.145957
221	1	COMPLETAR_RESERVA	ventas	43	{"total": 23.2, "productos": 1, "codigo_venta": "VEN-20260421-36720", "codigo_reserva": "RES-20260421-855", "metodo_pago_id": "3"}	\N	\N	2026-04-20 22:20:14.427644
222	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 39.44, "codigo": "RES-20260421-954", "adelanto": 9.86}	\N	\N	2026-04-20 22:30:33.989839
223	1	CAMBIAR_ESTADO_COMPRA	compras	10	{"notas": "Cancelación manual", "estado": "CANCELADA"}	\N	\N	2026-04-21 00:48:28.161999
224	1	REACTIVAR_VENTA	ventas	43	{"codigo_venta": "VEN-20260421-36720", "estado_nuevo": "COMPLETADA", "estado_anterior": "INHABILITADO"}	\N	\N	2026-04-21 02:13:13.95806
225	1	REACTIVAR_VENTA	ventas	42	{"codigo_venta": "VEN-20260420-65832", "estado_nuevo": "COMPLETADA", "estado_anterior": "INHABILITADO"}	\N	\N	2026-04-21 02:13:26.706775
226	1	REACTIVAR_VENTA	ventas	41	{"codigo_venta": "VEN-20260420-16980", "estado_nuevo": "COMPLETADA", "estado_anterior": "INHABILITADO"}	\N	\N	2026-04-21 02:13:29.38123
227	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260421-846", "adelanto": 580}	\N	\N	2026-04-21 14:12:53.230106
228	1	CREAR_COMPRA	compras	11	{"total": 11600, "codigo": "OC20260421EEBDF3"}	\N	\N	2026-04-21 14:18:54.416423
229	1	EMAIL_ORDEN_COMPRA	compras	11	{"codigo": "OC20260421EEBDF3", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-04-21 14:18:59.542423
230	1	CAMBIAR_ESTADO_COMPRA	compras	11	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-21 14:19:08.907992
231	1	CAMBIAR_ESTADO_COMPRA	compras	11	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-21 14:19:12.527235
232	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260421-057", "adelanto": 580}	\N	\N	2026-04-21 14:30:29.687582
233	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260421-164", "adelanto": 580}	\N	\N	2026-04-21 14:48:24.258153
234	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260421-835", "adelanto": 580}	\N	\N	2026-04-21 14:54:21.775057
235	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260421-416", "adelanto": 580}	\N	\N	2026-04-21 14:59:24.849449
236	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 46.4, "codigo": "RES-20260421-934", "adelanto": 11.6}	\N	\N	2026-04-21 15:02:39.997743
237	1	COMPLETAR_RESERVA	ventas	45	{"total": 46.4, "productos": 1, "codigo_venta": "VEN-20260421-25899", "codigo_reserva": "RES-20260421-934", "metodo_pago_id": "5"}	\N	\N	2026-04-21 15:04:33.416242
238	1	COMPLETAR_RESERVA	ventas	46	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260421-54537", "codigo_reserva": "RES-20260421-835", "metodo_pago_id": "5"}	\N	\N	2026-04-21 15:05:40.656047
239	1	COMPLETAR_RESERVA	ventas	47	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260421-46913", "codigo_reserva": "RES-20260421-057", "metodo_pago_id": "5"}	\N	\N	2026-04-21 15:16:14.571826
240	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260421-078", "adelanto": 580}	\N	\N	2026-04-21 15:24:58.631419
241	1	COMPLETAR_RESERVA	ventas	48	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260421-42572", "codigo_reserva": "RES-20260421-078", "metodo_pago_id": "1"}	\N	\N	2026-04-21 15:25:51.823664
242	1	COMPLETAR_RESERVA	ventas	49	{"total": 46.4, "productos": 1, "codigo_venta": "VEN-20260421-51802", "codigo_reserva": "RES-20260420-227", "metodo_pago_id": "5"}	\N	\N	2026-04-21 15:28:11.718177
243	1	COMPLETAR_RESERVA	ventas	50	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260421-21555", "codigo_reserva": "RES-20260421-846", "metodo_pago_id": "5"}	\N	\N	2026-04-21 15:32:25.015152
244	1	COMPLETAR_RESERVA	ventas	51	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260421-98417", "codigo_reserva": "RES-20260419-286", "metodo_pago_id": "5"}	\N	\N	2026-04-21 15:39:53.925332
245	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 46.4, "codigo": "RES-20260421-668", "adelanto": 11.6}	\N	\N	2026-04-21 16:07:59.11103
246	1	COMPLETAR_RESERVA	ventas	52	{"total": 46.4, "productos": 1, "codigo_venta": "VEN-20260421-20951", "codigo_reserva": "RES-20260421-668", "metodo_pago_id": "5"}	\N	\N	2026-04-21 16:09:01.445871
247	1	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 9280, "codigo": "RES-20260421-661", "adelanto": 2320}	\N	\N	2026-04-21 17:33:21.929728
248	1	PEDIDO_DIGITAL	pedidos_online	21	{"canal": "whatsapp", "total": 92.8, "codigo": "PED-20260421-65746", "productos": 1}	\N	\N	2026-04-21 17:37:13.583503
249	1	PEDIDO_DIGITAL	pedidos_online	22	{"canal": "whatsapp", "total": 4640, "codigo": "PED-20260421-11578", "productos": 1}	\N	\N	2026-04-21 18:10:13.058092
250	1	PEDIDO_DIGITAL	pedidos_online	23	{"canal": "whatsapp", "total": 2320, "codigo": "PED-20260421-10105", "productos": 1}	\N	\N	2026-04-21 18:11:44.227266
251	1	PEDIDO_DIGITAL	pedidos_online	24	{"canal": "telegram", "total": 46.4, "codigo": "PED-20260421-92097", "productos": 1}	\N	\N	2026-04-21 18:13:01.506901
252	1	PEDIDO_DIGITAL	pedidos_online	25	{"canal": "whatsapp", "total": 92.8, "codigo": "PED-20260421-53677", "productos": 1}	\N	\N	2026-04-21 18:27:56.67571
253	1	PEDIDO_DIGITAL	pedidos_online	26	{"canal": "whatsapp", "total": 46.4, "codigo": "PED-20260421-42173", "productos": 1}	\N	\N	2026-04-21 18:29:08.671156
254	1	PEDIDO_DIGITAL	pedidos_online	27	{"canal": "telegram", "total": 46.4, "codigo": "PED-20260421-82922", "productos": 1}	\N	\N	2026-04-21 18:30:06.428296
255	1	PEDIDO_DIGITAL	pedidos_online	28	{"canal": "telegram", "total": 46.4, "codigo": "PED-20260421-23292", "productos": 1}	\N	\N	2026-04-21 18:41:05.091012
256	2	PEDIDO_DIGITAL	pedidos_online	29	{"canal": "email", "total": 46.4, "codigo": "PED-20260421-06200", "productos": 1}	\N	\N	2026-04-21 18:58:18.95439
257	2	PEDIDO_DIGITAL	pedidos_online	30	{"canal": "email", "total": 2320, "codigo": "PED-20260421-30253", "productos": 1}	\N	\N	2026-04-21 21:58:51.886101
258	1	ACTUALIZAR_INTEGRACIONES	configuracion_integraciones	\N	["whatsapp_number", "whatsapp_enabled", "email_notifications", "email_from", "email_enabled", "telegram_bot_token", "telegram_chat_id", "telegram_username", "telegram_enabled", "internal_notifications_enabled", "auto_assign_vendors"]	\N	\N	2026-04-21 22:01:08.651096
259	2	PEDIDO_DIGITAL	pedidos_online	31	{"canal": "email", "total": 2320, "codigo": "PED-20260422-67492", "productos": 1}	\N	\N	2026-04-21 22:06:06.602873
260	2	PEDIDO_DIGITAL	pedidos_online	32	{"canal": "email", "total": 4640, "codigo": "PED-20260422-69541", "productos": 1}	\N	\N	2026-04-21 22:13:04.500257
261	2	PEDIDO_DIGITAL	pedidos_online	33	{"canal": "notificaciones", "total": 46.4, "codigo": "PED-20260422-14882", "productos": 1}	\N	\N	2026-04-21 22:15:36.022547
262	1	PEDIDO_ASIGNAR_VENDEDOR	pedidos_online	33	{"seller_id": 11}	\N	\N	2026-04-21 22:17:47.653374
263	2	PEDIDO_ESTADO	pedidos_online	33	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-21 22:23:45.782855
264	11	PEDIDO_ESTADO	pedidos_online	33	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-21 22:25:22.295108
265	11	CREAR_PROMOCION	promociones	8	{"nombre": "Kenderson"}	\N	\N	2026-04-21 22:27:49.088726
266	1	PEDIDO_DIGITAL	pedidos_online	34	{"canal": "notificaciones", "codigo": "PED-20260424-95516", "estado": "EN_VERIFICACION", "comprobante": true}	\N	\N	2026-04-24 18:42:34.910443
267	2	PEDIDO_DIGITAL	pedidos_online	35	{"canal": "notificaciones", "codigo": "PED-20260424-39776", "estado": "EN_VERIFICACION", "comprobante": true}	\N	\N	2026-04-24 18:51:28.077833
268	2	PEDIDO_ESTADO	pedidos_online	32	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 18:52:00.57602
269	2	PEDIDO_ESTADO	pedidos_online	31	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 18:52:07.452042
270	2	PEDIDO_ESTADO	pedidos_online	30	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 18:52:13.931826
271	2	PEDIDO_ESTADO	pedidos_online	29	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 18:52:20.275597
272	2	PEDIDO_ESTADO	pedidos_online	13	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 18:52:27.438776
273	2	PEDIDO_ESTADO	pedidos_online	12	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 18:52:35.227726
274	2	PEDIDO_ESTADO	pedidos_online	11	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 18:52:40.13895
275	2	PEDIDO_ESTADO	pedidos_online	10	{"to": "EN_VERIFICACION", "from": "PENDIENTE", "notes": "", "action": "upload_proof", "payment_reference": "Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 18:52:47.490191
276	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2320, "codigo": "RES-20260424-195", "adelanto": 580}	\N	\N	2026-04-24 18:53:40.171561
277	1	COMPLETAR_RESERVA	ventas	53	{"total": 9280, "productos": 1, "codigo_venta": "VEN-20260424-39398", "codigo_reserva": "RES-20260421-661", "metodo_pago_id": "5"}	\N	\N	2026-04-24 18:56:50.732293
278	1	COMPLETAR_RESERVA	ventas	54	{"total": 2320, "productos": 1, "codigo_venta": "VEN-20260424-44786", "codigo_reserva": "RES-20260424-195", "metodo_pago_id": "5"}	\N	\N	2026-04-24 18:57:10.39145
279	1	PEDIDO_ASIGNAR_VENDEDOR	pedidos_online	35	{"seller_id": 3}	\N	\N	2026-04-24 18:57:57.556595
280	1	PEDIDO_ESTADO	pedidos_online	35	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 18:58:07.619866
281	1	PEDIDO_ASIGNAR_VENDEDOR	pedidos_online	34	{"seller_id": 11}	\N	\N	2026-04-24 18:58:22.281767
282	11	PEDIDO_ESTADO	pedidos_online	34	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 19:00:09.0917
283	1	PEDIDO_ESTADO	pedidos_online	32	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 20:28:56.427404
284	1	PEDIDO_ESTADO	pedidos_online	31	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 20:28:59.632627
285	1	CAMBIAR_ESTADO_COMPRA	compras	10	{"notas": "Reactivación manual", "estado": "PENDIENTE"}	\N	\N	2026-04-24 20:30:07.141175
286	1	CAMBIAR_ESTADO_COMPRA	compras	10	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-24 20:30:13.445115
287	1	CAMBIAR_ESTADO_COMPRA	compras	10	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-24 20:30:18.419841
288	1	CREAR_COMPRA	compras	12	{"total": 23200, "codigo": "OC20260424A0266A"}	\N	\N	2026-04-24 20:32:17.577496
289	1	EMAIL_ORDEN_COMPRA	compras	12	{"codigo": "OC20260424A0266A", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-04-24 20:32:21.425036
290	1	CAMBIAR_ESTADO_COMPRA	compras	12	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-24 20:32:29.690824
291	1	CAMBIAR_ESTADO_COMPRA	compras	12	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-24 20:32:35.126344
292	1	PEDIDO_ESTADO	pedidos_online	30	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 20:32:54.369294
293	1	PEDIDO_ESTADO	pedidos_online	29	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 20:32:59.458526
294	1	PEDIDO_ESTADO	pedidos_online	13	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 20:33:28.680751
295	1	PEDIDO_ESTADO	pedidos_online	11	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 20:33:32.654354
296	1	PEDIDO_ESTADO	pedidos_online	10	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 20:33:41.8232
297	1	PEDIDO_ESTADO	pedidos_online	12	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 20:33:45.735349
298	1	PEDIDO_ESTADO	pedidos_online	6	{"to": "PENDIENTE", "from": "INHABILITADO", "notes": "", "action": "toggle_active"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 20:33:50.707345
299	1	CREAR_COMPRA	compras	13	{"total": 347.82599999999996, "codigo": "OC2026042451EB8C"}	\N	\N	2026-04-24 20:35:06.799285
300	1	EMAIL_ORDEN_COMPRA	compras	13	{"codigo": "OC2026042451EB8C", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-04-24 20:35:10.002376
301	1	CAMBIAR_ESTADO_COMPRA	compras	13	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-24 20:35:16.493368
302	1	CAMBIAR_ESTADO_COMPRA	compras	13	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-24 20:35:22.554625
303	2	PEDIDO_DIGITAL	pedidos_online	36	{"canal": "notificaciones", "codigo": "PED-20260424-87338", "estado": "EN_VERIFICACION", "comprobante": true}	\N	\N	2026-04-24 20:37:37.073215
304	1	PEDIDO_ASIGNAR_VENDEDOR	pedidos_online	36	{"seller_id": 11}	\N	\N	2026-04-24 20:39:28.762729
305	11	PEDIDO_ESTADO	pedidos_online	36	{"to": "CONFIRMADO", "from": "EN_VERIFICACION", "notes": "", "action": "approve"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-24 20:40:57.125574
306	1	CREAR_PRODUCTO	productos	10	{"tipo": "vehiculo", "codigo": "VH-216881-176", "nombre": "BRF 150cc", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-25 02:48:00.203356
307	1	CREAR_PRODUCTO	productos	11	{"tipo": "vehiculo", "codigo": "VH-171518-303", "nombre": "MOTO BERA DT 200 RR", "stock_inicial": 15}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-25 03:06:38.646929
308	2	CREAR_RESERVA	reservas	0	{"ip": "127.0.0.1", "total": 2088, "codigo": "RES-20260425-351", "adelanto": 522}	\N	\N	2026-04-25 03:49:06.873374
309	1	CREAR_PRODUCTO	productos	12	{"tipo": "repuesto", "codigo": "RP-370307-563", "nombre": "MOTOR CG DE MOTOCICLETA 150CC", "stock_inicial": 15}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-27 01:06:23.348943
310	1	ACTUALIZAR_PRODUCTO	productos	8	{"nombre": "NINJA 1000SX", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-27 14:59:46.162593
311	1	ACTUALIZAR_PRODUCTO	productos	8	{"nombre": "NINJA 1000SX", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-27 18:24:32.217664
312	1	CREAR_PRODUCTO	productos	13	{"tipo": "accesorio", "codigo": "AC-886302-362", "nombre": "CHAQUETA IMPERMEABLE", "stock_inicial": 19}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-28 00:53:44.889882
313	1	CREAR_PRODUCTO	productos	14	{"tipo": "accesorio", "codigo": "AC-415106-080", "nombre": "CHAQUETA INVICTUS", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 01:15:44.958319
314	1	CREAR_CATEGORIA	categorias	5	{"nombre": "Deportiva (Sport)", "tipo_id": 5, "descripcion": "Motocicletas con carenado, postura semi-deportiva y mecánica de altas prestaciones, diseñadas para uso legal en vías públicas combinando deportividad y utilidad diaria.", "tipo_producto": "MOTO"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-28 01:15:46.551637
315	1	CREAR_PRODUCTO	productos	15	{"tipo": "accesorio", "codigo": "AC-029863-550", "nombre": "CHAQUETA", "stock_inicial": 20}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 01:20:59.50949
316	1	CREAR_PRODUCTO	productos	16	{"tipo": "accesorio", "codigo": "AC-315767-930", "nombre": "CHAQUETA NORMANDIE", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 01:24:08.990974
317	1	ACTUALIZAR_PRODUCTO	productos	15	{"nombre": "CHAQUETA ALPINESTAR", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 01:24:40.924457
318	1	CREAR_PRODUCTO	productos	17	{"tipo": "accesorio", "codigo": "AC-740149-217", "nombre": "CHAQUETA  DAINESE", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 01:32:18.599535
319	1	ACTUALIZAR_PRODUCTO	productos	8	{"nombre": "NINJA 1000SX", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 01:42:19.980678
320	1	ACTUALIZAR_PRODUCTO	productos	8	{"nombre": "NINJA 1000SX", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 01:42:46.37611
321	1	ACTUALIZAR_PRODUCTO	productos	8	{"nombre": "NINJA 1000SX", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 01:43:27.679519
322	1	ACTUALIZAR_PRODUCTO	productos	8	{"nombre": "NINJA 1000SX", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 01:43:50.826349
323	1	ACTUALIZAR_PRODUCTO	productos	8	{"nombre": "NINJA 1000SX", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 01:44:03.732221
324	1	CREAR_PRODUCTO	productos	18	{"tipo": "accesorio", "codigo": "AC-637057-943", "nombre": "CASCO BERA THUNDER", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 02:04:14.136267
325	1	ACTUALIZAR_PRODUCTO	productos	8	{"nombre": "NINJA 1000SX", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 02:04:49.090015
326	1	CREAR_PRODUCTO	productos	19	{"tipo": "accesorio", "codigo": "AC-860121-365", "nombre": "CASCO BERA CYCLONE", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 02:07:31.055205
327	1	CREAR_PRODUCTO	productos	20	{"tipo": "accesorio", "codigo": "AC-143640-371", "nombre": "CASCO INTEGRAL", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 02:11:06.255642
328	1	ACTUALIZAR_PRODUCTO	productos	20	{"nombre": "CASCO INTEGRAL", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 02:13:52.989803
329	1	CREAR_PRODUCTO	productos	21	{"tipo": "accesorio", "codigo": "AC-449287-919", "nombre": "CASCO BERA ROAD", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 02:15:50.1425
330	1	CREAR_PRODUCTO	productos	22	{"tipo": "accesorio", "codigo": "AC-598814-874", "nombre": "CASCO BERA ROTULADO", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 02:18:56.746204
331	1	CREAR_PRODUCTO	productos	23	{"tipo": "vehiculo", "codigo": "VH-831630-907", "nombre": "Moto Yamaha YZF-R3", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-28 02:56:26.245159
332	1	ACTUALIZAR_PRODUCTO	productos	9	{"nombre": "Moto SBR", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 02:57:59.73983
333	1	CREAR_PRODUCTO	productos	24	{"tipo": "vehiculo", "codigo": "VH-909153-280", "nombre": "Moto Bera BRZ 250cc", "stock_inicial": 50}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-28 03:47:05.147572
334	1	CREAR_CATEGORIA	categorias	6	{"nombre": "Scooter", "tipo_id": 5, "descripcion": "Moto de paseo mas comoda", "tipo_producto": "MOTO"}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-28 03:52:47.669179
335	1	CREAR_PRODUCTO	productos	25	{"tipo": "vehiculo", "codigo": "VH-137724-175", "nombre": "Moto Cobra 150cc", "stock_inicial": 25}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-28 03:53:23.722132
336	1	CREAR_PRODUCTO	productos	26	{"tipo": "vehiculo", "codigo": "VH-634566-968", "nombre": "Moto GBR 200cc", "stock_inicial": 10}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	2026-04-28 14:01:28.582444
337	1	CREAR_PRODUCTO	productos	27	{"tipo": "repuesto", "codigo": "RP-684517-936", "nombre": "MOTOR DE BERA 200CC", "stock_inicial": 25}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 14:20:37.717397
338	1	CREAR_PRODUCTO	productos	28	{"tipo": "repuesto", "codigo": "RP-202379-707", "nombre": "MOTOR", "stock_inicial": 20}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 14:24:24.763983
339	1	ACTUALIZAR_PRODUCTO	productos	28	{"nombre": "MOTOR DE BERA 250CC", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 14:28:37.628683
340	1	CREAR_PRODUCTO	productos	29	{"tipo": "repuesto", "codigo": "RP-666401-264", "nombre": "FRENO DE DISCO DE MOTO MILAN", "stock_inicial": 20}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 14:33:05.339875
341	1	CREAR_PRODUCTO	productos	30	{"tipo": "repuesto", "codigo": "RP-868840-337", "nombre": "PASTILLA DE FRENO", "stock_inicial": 20}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 14:36:56.880397
342	1	CREAR_PRODUCTO	productos	31	{"tipo": "repuesto", "codigo": "RP-051335-839", "nombre": "DISCO DE FRENO DELANTERO", "stock_inicial": 20}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 14:39:18.628534
343	1	CREAR_PRODUCTO	productos	32	{"tipo": "repuesto", "codigo": "RP-290262-224", "nombre": "SISTEMA DE FRENO DELANTERO", "stock_inicial": 20}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 14:43:09.812249
344	1	CREAR_PRODUCTO	productos	33	{"tipo": "repuesto", "codigo": "RP-468158-383", "nombre": "FRENOS MOTO BERA RUNNER", "stock_inicial": 20}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 14:46:12.592747
345	1	ACTUALIZAR_PRODUCTO	productos	10	{"nombre": "BRF 150cc", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 14:47:02.967063
346	1	CREAR_PRODUCTO	productos	34	{"tipo": "vehiculo", "codigo": "VH-911581-677", "nombre": "CARGUERO 200CC", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 14:54:14.002963
347	1	CREAR_PRODUCTO	productos	35	{"tipo": "vehiculo", "codigo": "VH-957545-975", "nombre": "BERA R1 GBR", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 15:45:05.915709
348	1	ACTUALIZAR_PRODUCTO	productos	35	{"nombre": "MOTO BERA R1 GBR", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 15:48:24.489547
349	1	CREAR_PRODUCTO	productos	36	{"tipo": "vehiculo", "codigo": "VH-358207-449", "nombre": "MOTO BERA MORINI XCAPE", "stock_inicial": 40}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 15:56:24.354402
350	1	ACTUALIZAR_PRODUCTO	productos	35	{"nombre": "MOTO BERA R1 GBR", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 15:56:56.705911
351	1	ACTUALIZAR_PRODUCTO	productos	36	{"nombre": "MOTO BERA MORINI XCAPE", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 16:03:12.939822
352	1	CREAR_PRODUCTO	productos	37	{"tipo": "vehiculo", "codigo": "VH-196180-696", "nombre": "MOTO HONDA CBR", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 16:05:19.051165
353	1	CREAR_PRODUCTO	productos	38	{"tipo": "vehiculo", "codigo": "VH-536259-448", "nombre": "MOTO MORINI STR", "stock_inicial": 30}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 16:10:59.32851
354	1	CREAR_PRODUCTO	productos	39	{"tipo": "accesorio", "codigo": "AC-018656-778", "nombre": "GUANTES PARA MOTO", "stock_inicial": 50}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 17:10:33.412373
355	1	CREAR_PRODUCTO	productos	40	{"tipo": "accesorio", "codigo": "AC-317295-500", "nombre": "GUANTES PARA MOTO ALPINESTAR", "stock_inicial": 50}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 17:16:04.46817
356	1	CREAR_PRODUCTO	productos	41	{"tipo": "accesorio", "codigo": "AC-616205-482", "nombre": "GUANTES DE MOTO CON PROTECCION", "stock_inicial": 50}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 17:22:47.491098
357	1	CREAR_PRODUCTO	productos	42	{"tipo": "accesorio", "codigo": "AC-291148-697", "nombre": "GUANTES PARA MOTO CON PROTECCION", "stock_inicial": 50}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 17:35:36.540123
358	1	CREAR_PRODUCTO	productos	43	{"tipo": "accesorio", "codigo": "AC-864394-087", "nombre": "GUANTES DE CUERO CON PROTECCION", "stock_inicial": 50}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 17:39:14.719324
359	1	CREAR_PRODUCTO	productos	44	{"tipo": "vehiculo", "codigo": "VH-638534-942", "nombre": "BERA SCOOOTER BWS", "stock_inicial": 50}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 17:52:33.336698
360	16	REGISTRO_USUARIO	usuarios	16	{"email": "rafael@gmail.com", "username": "reafel123"}	127.0.0.1	\N	2026-04-28 17:53:56.46195
361	1	ACTUALIZAR_PRODUCTO	productos	44	{"nombre": "MOTO BERA SCOOOTER BWS", "campos_actualizados": ["precio_venta", "precio_compra", "stock_actual", "stock_minimo", "stock_maximo"]}	\N	\N	2026-04-28 17:54:02.551636
362	1	CREAR_PRODUCTO	productos	45	{"tipo": "vehiculo", "codigo": "VH-005429-904", "nombre": "MOTO BERA ADVENTURE", "stock_inicial": 50}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 18:00:23.060304
363	17	REGISTRO_USUARIO	usuarios	17	{"email": "rafa@gmail.com", "username": "rafael123"}	127.0.0.1	\N	2026-04-28 18:04:47.771839
364	1	CREAR_PRODUCTO	productos	46	{"tipo": "vehiculo", "codigo": "VH-358636-883", "nombre": "MOTO BERA  X1", "stock_inicial": 50}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 18:05:15.877288
365	1	CREAR_PRODUCTO	productos	47	{"tipo": "vehiculo", "codigo": "VH-589427-731", "nombre": "MOTO BERA TEZO", "stock_inicial": 50}	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	2026-04-28 18:08:04.555206
366	1	CREAR_COMPRA	compras	14	{"total": 58, "codigo": "OC2026042802191F"}	\N	\N	2026-04-28 18:17:05.995705
367	1	EMAIL_ORDEN_COMPRA	compras	14	{"codigo": "OC2026042802191F", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-04-28 18:17:13.013618
368	1	CAMBIAR_ESTADO_COMPRA	compras	14	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-28 18:18:32.492983
369	1	CAMBIAR_ESTADO_COMPRA	compras	14	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-28 18:18:49.389146
370	1	CREAR_COMPRA	compras	15	{"total": 185.6, "codigo": "OC20260428220928"}	\N	\N	2026-04-28 18:19:52.509911
371	1	EMAIL_ORDEN_COMPRA	compras	15	{"codigo": "OC20260428220928", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-04-28 18:19:56.53229
372	1	CAMBIAR_ESTADO_COMPRA	compras	15	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-28 18:20:07.455148
373	1	CAMBIAR_ESTADO_COMPRA	compras	15	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-28 18:20:21.713731
374	1	CREAR_COMPRA	compras	16	{"total": 290, "codigo": "OC20260428D8791D"}	\N	\N	2026-04-28 18:22:56.40543
375	1	EMAIL_ORDEN_COMPRA	compras	16	{"codigo": "OC20260428D8791D", "email_destino": "jovita45r@gmail.com"}	\N	\N	2026-04-28 18:23:00.178981
376	1	CAMBIAR_ESTADO_COMPRA	compras	16	{"notas": "", "estado": "RECEPCION"}	\N	\N	2026-04-28 18:23:12.127854
377	1	CAMBIAR_ESTADO_COMPRA	compras	16	{"notas": "", "estado": "COMPLETADA"}	\N	\N	2026-04-28 18:23:20.410542
378	1	BACKUP_CLEAN	sistema	\N	{"eliminados": 0, "espacio_liberado": "0 MB"}	127.0.0.1	\N	2026-04-28 19:11:28.42637
379	1	ACTUALIZAR_INTEGRACIONES	configuracion_integraciones	\N	["whatsapp_number", "whatsapp_enabled", "email_notifications", "email_from", "email_enabled", "telegram_bot_token", "telegram_chat_id", "telegram_username", "telegram_enabled", "internal_notifications_enabled", "auto_assign_vendors"]	\N	\N	2026-04-28 19:14:53.741433
380	1	BACKUP_CLEAN	sistema	\N	{"eliminados": 0, "espacio_liberado": "0 MB"}	127.0.0.1	\N	2026-04-28 21:27:50.167092
\.


--
-- Data for Name: categorias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.categorias (id, nombre, descripcion, estado, tipo_producto_id, created_at, updated_at) FROM stdin;
1	Ropa	xxx	t	3	2026-03-15 20:21:45.062366	2026-03-15 20:21:45.062366
2	Motor	xxx	t	4	2026-03-15 21:17:04.011118	2026-03-15 21:17:04.011118
3	sincronica	xxx	t	5	2026-03-15 21:23:57.316438	2026-03-15 21:23:57.316438
4	Frenos	xxx	t	4	2026-03-24 17:31:48.537476	2026-03-24 17:31:48.537476
5	Deportiva (Sport)	Motocicletas con carenado, postura semi-deportiva y mecánica de altas prestaciones, diseñadas para uso legal en vías públicas combinando deportividad y utilidad diaria.	t	5	2026-04-28 01:15:46.548473	2026-04-28 01:15:46.548473
6	Scooter	Moto de paseo mas comoda	t	5	2026-04-28 03:52:47.659167	2026-04-28 03:52:47.659167
\.


--
-- Data for Name: clientes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.clientes (id, cedula_rif, nombre_completo, email, telefono_principal, telefono_alternativo, direccion, fecha_registro, estado, usuario_id, created_at, updated_at) FROM stdin;
1	V-6927898	Raizza Marrero	jovita45r@gmail.com	0412-7550246	0412-1304526	venezuela, estado Aragua, ciudad Maracay, municipio Libertador, palo negro Santa Ana. Urbanización jardines los tulipanes calle 3 casa 165	2026-03-15	t	1	2026-03-15 21:37:36.120543	2026-03-15 21:37:36.120543
3	V-1234567	dylan	dylan@gmail.com	04121234567		xxx	2026-03-18	t	1	2026-03-18 04:33:34.754514	2026-03-18 04:33:34.754514
4	V-30555703	Juan Villegas	juan@gmail.com	0412-7550247	\N	\N	2026-03-24	t	6	2026-03-24 02:58:20.195565	2026-03-24 02:58:20.195565
5	V-30555701	Gifrank	gifrank0000@gmail.com	0412-1304526	\N	\N	2026-03-24	t	7	2026-03-24 03:01:29.077972	2026-03-24 03:01:29.077972
6	J-692786822	Gifrank	gifrank@gmail.com	0417-5550242	\N	\N	2026-03-24	t	8	2026-03-24 03:13:48.082067	2026-03-24 03:13:48.082067
7	V-12345678	Usuario de prueba	user@gmail.com	0412-1304527	\N	\N	2026-03-24	t	9	2026-03-24 15:54:42.300117	2026-03-24 15:54:42.300117
8	J-123456780	fred	cliente@gmail.com	04121304522	0412-1304521	venezuela, estado Aragua, ciudad Maracay	2026-03-24	t	1	2026-03-24 17:26:41.317146	2026-03-24 17:26:41.317146
2	V-6927868	Raizza marrero	jovita45r@gmail.com	04124617132	\N		2026-03-16	t	2	2026-03-16 03:19:26.111203	2026-03-26 15:58:49.618251
10	V-12345600	andres sanchez	andred@gmail.com	0412-1234567	\N	\N	2026-03-26	t	14	2026-03-26 17:22:50.691548	2026-03-26 17:22:50.691548
11	V-30555702	Gifrank	gifrank2912@mail.com	0412-7550246	\N	\N	2026-03-28	t	15	2026-03-28 18:43:23.971097	2026-03-28 18:43:23.971097
12	V-30555709	rafael	rafael@gmail.com	0412-5550246	\N	\N	2026-04-28	t	16	2026-04-28 17:53:56.46195	2026-04-28 17:53:56.46195
13	V-30555708	rafael	rafa@gmail.com	0412-5550246	\N	\N	2026-04-28	t	17	2026-04-28 18:04:47.771839	2026-04-28 18:04:47.771839
\.


--
-- Data for Name: compras; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.compras (id, codigo_compra, proveedor_id, usuario_id, subtotal, iva, total, estado_compra, fecha_estimada_entrega, observaciones, activa, notas_incidencia, created_at, moneda_factura, tasa_cambio, monto_bs, monto_usd, moneda_pago, metodo_pago_id) FROM stdin;
13	OC2026042451EB8C	1	1	299.85	47.98	347.83	COMPLETADA	2026-05-01		t	\N	2026-04-24 20:35:06.792106	USD	1.0000	347.83	347.83	USD	\N
14	OC2026042802191F	1	1	50.00	8.00	58.00	COMPLETADA	2026-05-05	NADA	t		2026-04-28 18:17:05.945324	USD	1.0000	58.00	58.00	USD	\N
2	OC2026031642FA8C	3	1	20.00	3.20	23.20	COMPLETADA	2026-03-23		t		2026-03-16 01:07:51.539658	USD	\N	\N	\N	USD	\N
1	OC202603163B90A8	1	1	20.00	3.20	23.20	COMPLETADA	2026-03-23		t	Cancelación manual	2026-03-16 00:02:23.070303	USD	\N	\N	\N	USD	\N
15	OC20260428220928	1	1	160.00	25.60	185.60	COMPLETADA	2026-05-05		t	\N	2026-04-28 18:19:52.504364	USD	1.0000	185.60	185.60	USD	\N
16	OC20260428D8791D	1	1	250.00	40.00	290.00	COMPLETADA	2026-05-05		t	\N	2026-04-28 18:22:56.40045	USD	1.0000	290.00	290.00	USD	\N
3	OC20260316C2D5B4	1	1	20.00	3.20	23.20	COMPLETADA	2026-03-23	xxx	t	falta mas	2026-03-16 01:51:18.739691	USD	\N	\N	\N	USD	\N
4	OC202603189D9B52	1	1	100.00	16.00	116.00	COMPLETADA	2026-03-25		t	xxx	2026-03-18 04:58:24.912412	USD	\N	\N	\N	USD	\N
5	OC2026031864A050	1	1	100.00	16.00	116.00	COMPLETADA	2026-03-26	xxx	t	xxx	2026-03-18 05:05:24.589141	USD	\N	\N	\N	USD	\N
6	OC202603262DC413	1	1	60.00	9.60	69.60	COMPLETADA	2026-04-02		t	\N	2026-03-26 17:37:32.998457	USD	\N	\N	\N	USD	\N
8	OC2026041878FC24	1	1	30.00	4.80	34.80	COMPLETADA	2026-04-25	xxx	t	\N	2026-04-18 04:35:39.610596	USD	1.0000	34.80	34.80	USD	\N
7	OC20260418A040C0	1	1	20.00	3.20	23.20	COMPLETADA	2026-04-25		t	\N	2026-04-18 03:04:29.810012	USD	1.0000	23.20	23.20	USD	\N
9	OC20260418BA7C42	1	1	20.00	3.20	23.20	COMPLETADA	2026-04-25		t	\N	2026-04-18 04:38:04.497314	USD	1.0000	23.20	23.20	USD	\N
11	OC20260421EEBDF3	1	1	10000.00	1600.00	11600.00	COMPLETADA	2026-04-28		t	\N	2026-04-21 14:18:54.369065	USD	1.0000	11600.00	11600.00	USD	\N
10	OC202604189C1509	1	1	390.00	62.40	452.40	COMPLETADA	2026-04-25	xxx	t	Reactivación manual	2026-04-18 04:39:14.486018	USD	1.0000	452.40	452.40	USD	\N
12	OC20260424A0266A	1	1	20000.00	3200.00	23200.00	COMPLETADA	2026-05-01		t	\N	2026-04-24 20:32:17.570886	USD	1.0000	23200.00	23200.00	USD	\N
\.


--
-- Data for Name: configuracion_integraciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.configuracion_integraciones (clave, valor, updated_at) FROM stdin;
tasa_cambio_dolar	468.51	2026-03-28 20:37:30.485569+00
whatsapp_number	584124617132	2026-04-28 19:14:53.682379+00
whatsapp_enabled	1	2026-04-28 19:14:53.728462+00
email_notifications	inversionesroja123@gmail.com	2026-04-28 19:14:53.729453+00
email_from	gifrank0000@gmail.com	2026-04-28 19:14:53.730382+00
email_enabled	1	2026-04-28 19:14:53.731355+00
telegram_bot_token	8673470561:AAEBt8NGDQwohzmVeysU1rHnFHzzxkg0Ung	2026-04-28 19:14:53.732271+00
telegram_chat_id	-1003766374059	2026-04-28 19:14:53.733158+00
telegram_username	Inversiones Rojas	2026-04-28 19:14:53.733941+00
telegram_enabled	1	2026-04-28 19:14:53.734743+00
internal_notifications_enabled	1	2026-04-28 19:14:53.73556+00
auto_assign_vendors	1	2026-04-28 19:14:53.736513+00
\.


--
-- Data for Name: detalle_compras; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.detalle_compras (id, compra_id, producto_id, cantidad, precio_unitario, subtotal, created_at, precio_unitario_bs, precio_unitario_usd) FROM stdin;
2	1	1	2	10.00	20.00	2026-03-16 00:57:04.644783	\N	\N
3	2	2	1	20.00	20.00	2026-03-16 01:07:51.539658	\N	\N
4	3	2	1	20.00	20.00	2026-03-16 01:51:18.739691	\N	\N
5	4	1	10	10.00	100.00	2026-03-18 04:58:24.927869	\N	\N
6	5	4	10	10.00	100.00	2026-03-18 05:05:24.604477	\N	\N
7	6	3	3	20.00	60.00	2026-03-26 17:37:33.045647	\N	\N
8	7	2	1	20.00	20.00	2026-04-18 03:04:29.871714	20.00	20.00
9	8	5	1	10.00	10.00	2026-04-18 04:35:39.624378	10.00	10.00
10	8	3	1	20.00	20.00	2026-04-18 04:35:39.626124	20.00	20.00
11	9	3	1	20.00	20.00	2026-04-18 04:38:04.501735	20.00	20.00
12	10	1	10	10.00	100.00	2026-04-18 04:39:14.489946	10.00	10.00
13	10	3	10	20.00	200.00	2026-04-18 04:39:14.491284	20.00	20.00
14	10	5	9	10.00	90.00	2026-04-18 04:39:14.491731	10.00	10.00
15	11	9	10	1000.00	10000.00	2026-04-21 14:18:54.411696	1000.00	1000.00
16	12	9	20	1000.00	20000.00	2026-04-24 20:32:17.575726	1000.00	1000.00
17	13	8	15	19.99	299.85	2026-04-24 20:35:06.795887	19.99	19.99
19	14	18	1	50.00	50.00	2026-04-28 18:18:13.085392	50.00	50.00
20	15	2	8	20.00	160.00	2026-04-28 18:19:52.508323	20.00	20.00
21	16	13	5	50.00	250.00	2026-04-28 18:22:56.403924	50.00	50.00
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
16	15	5	1	20.00	20.00	2026-03-26 05:53:24.866167
17	16	5	1	20.00	20.00	2026-03-26 05:55:50.285861
19	18	3	1	30.00	30.00	2026-03-26 10:45:26.315394
20	19	3	1	30.00	30.00	2026-03-26 10:48:08.347823
21	20	5	1	20.00	20.00	2026-03-26 17:57:54.101449
22	21	8	2	40.00	80.00	2026-04-21 17:37:13.583503
23	22	9	2	2000.00	4000.00	2026-04-21 18:10:13.058092
24	23	9	1	2000.00	2000.00	2026-04-21 18:11:44.227266
25	24	8	1	40.00	40.00	2026-04-21 18:13:01.506901
26	25	8	2	40.00	80.00	2026-04-21 18:27:56.67571
27	26	8	1	40.00	40.00	2026-04-21 18:29:08.671156
28	27	8	1	40.00	40.00	2026-04-21 18:30:06.428296
29	28	8	1	40.00	40.00	2026-04-21 18:41:05.091012
30	29	8	1	40.00	40.00	2026-04-21 18:58:18.95439
31	30	9	1	2000.00	2000.00	2026-04-21 21:58:51.886101
32	31	9	1	2000.00	2000.00	2026-04-21 22:06:06.602873
33	32	9	2	2000.00	4000.00	2026-04-21 22:13:04.500257
34	33	8	1	40.00	40.00	2026-04-21 22:15:36.022547
35	34	9	1	2000.00	2000.00	2026-04-24 18:42:34.910443
36	35	3	1	30.00	30.00	2026-04-24 18:51:28.077833
37	36	9	1	2000.00	2000.00	2026-04-24 20:37:37.073215
\.


--
-- Data for Name: detalle_ventas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.detalle_ventas (id, venta_id, producto_id, cantidad, precio_unitario, subtotal, created_at, precio_unitario_bs, precio_unitario_usd) FROM stdin;
1	1	1	1	40.00	40.00	2026-03-15 21:37:46.289757	\N	\N
2	2	3	1	30.00	30.00	2026-03-15 23:23:05.389608	\N	\N
3	3	1	1	40.00	40.00	2026-03-15 23:26:41.668385	\N	\N
4	4	1	1	40.00	40.00	2026-03-15 23:52:35.489566	\N	\N
5	5	4	1	19.98	19.98	2026-03-15 23:54:21.224039	\N	\N
6	6	3	1	30.00	30.00	2026-03-17 00:57:32.312688	\N	\N
7	7	3	1	30.00	30.00	2026-03-17 02:01:13.861599	\N	\N
8	8	1	1	40.00	40.00	2026-03-18 04:34:04.998108	\N	\N
9	9	1	1	40.00	40.00	2026-03-18 04:34:28.663668	\N	\N
10	10	1	1	40.00	40.00	2026-03-18 04:34:30.235147	\N	\N
11	11	1	1	40.00	40.00	2026-03-18 04:34:30.37264	\N	\N
12	12	1	1	40.00	40.00	2026-03-18 04:34:30.553596	\N	\N
13	13	1	1	40.00	40.00	2026-03-18 04:34:30.664176	\N	\N
14	14	1	1	40.00	40.00	2026-03-18 04:34:30.816474	\N	\N
15	15	4	1	19.98	19.98	2026-03-18 04:44:23.046914	\N	\N
16	16	4	1	19.98	19.98	2026-03-18 04:50:31.191365	\N	\N
17	17	3	2	30.00	60.00	2026-03-18 04:52:56.849787	\N	\N
18	18	3	3	30.00	90.00	2026-03-18 04:56:05.450469	\N	\N
19	19	5	1	20.00	20.00	2026-03-18 05:17:45.011829	\N	\N
20	20	1	1	40.00	40.00	2026-03-24 01:44:39.069417	\N	\N
21	21	3	1	30.00	30.00	2026-03-26 10:47:06.805518	\N	\N
22	22	4	1	19.98	19.98	2026-03-26 16:21:34.902141	\N	\N
23	23	3	1	30.00	30.00	2026-03-26 17:34:54.46474	\N	\N
24	24	1	1	40.00	40.00	2026-03-28 19:48:38.6105	\N	\N
25	25	1	1	40.00	40.00	2026-04-12 00:03:09.191329	16000.00	40.00
26	26	9	1	2000.00	2000.00	2026-04-15 02:29:29.109634	\N	\N
27	27	1	1	40.00	40.00	2026-04-15 02:29:34.406774	\N	\N
28	28	1	1	40.00	40.00	2026-04-15 02:29:38.875981	\N	\N
29	29	3	1	30.00	30.00	2026-04-15 02:29:42.364727	\N	\N
30	30	1	1	40.00	40.00	2026-04-18 19:11:10.005036	16000.00	40.00
31	31	8	1	40.00	40.00	2026-04-20 16:29:28.76219	\N	\N
32	32	9	1	2000.00	2000.00	2026-04-20 17:45:37.810049	\N	\N
33	33	9	1	2000.00	2000.00	2026-04-20 17:46:55.735345	\N	\N
34	34	1	1	40.00	40.00	2026-04-20 17:47:19.410836	\N	\N
35	35	9	1	2000.00	2000.00	2026-04-20 18:44:02.959159	\N	\N
36	36	1	1	40.00	40.00	2026-04-20 18:46:06.431323	\N	\N
37	37	9	1	2000.00	2000.00	2026-04-20 18:56:23.00971	\N	\N
38	38	5	1	20.00	20.00	2026-04-20 19:12:11.222188	\N	\N
39	39	4	1	19.98	19.98	2026-04-20 19:31:08.389815	\N	\N
40	40	1	1	40.00	40.00	2026-04-20 19:49:58.180961	\N	\N
41	41	1	1	40.00	40.00	2026-04-20 20:01:40.796543	\N	\N
42	42	9	1	2000.00	2000.00	2026-04-20 21:46:44.924613	\N	\N
43	43	5	1	20.00	20.00	2026-04-20 22:20:14.413711	\N	\N
44	44	1	1	40.00	40.00	2026-04-21 14:21:22.025676	16000.00	40.00
45	45	8	1	40.00	40.00	2026-04-21 15:04:33.358236	\N	\N
46	46	9	1	2000.00	2000.00	2026-04-21 15:05:40.643244	\N	\N
47	47	9	1	2000.00	2000.00	2026-04-21 15:16:14.518132	\N	\N
48	48	9	1	2000.00	2000.00	2026-04-21 15:25:51.809637	\N	\N
49	49	1	1	40.00	40.00	2026-04-21 15:28:11.702716	\N	\N
50	50	9	1	2000.00	2000.00	2026-04-21 15:32:25.000439	\N	\N
51	51	9	1	2000.00	2000.00	2026-04-21 15:39:53.910959	\N	\N
52	52	8	1	40.00	40.00	2026-04-21 16:09:01.433011	\N	\N
53	53	9	4	2000.00	8000.00	2026-04-24 18:56:50.705035	\N	\N
54	54	9	1	2000.00	2000.00	2026-04-24 18:57:10.374055	\N	\N
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
8	DEV-20260326-0008	\N	15	2	5	1	Producto Dañado	APROBADO	\N	xxxxxxx	2026-03-26 17:52:14.342629	2026-03-26 17:53:48.362092
9	DEV-20260415-0009	28	\N	2	1	1	Garantía	APROBADO	\N	xxx	2026-04-15 02:40:23.308997	2026-04-15 02:43:18.318821
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

COPY public.metodos_pago (id, nombre, descripcion, estado, created_at, moneda) FROM stdin;
2	Transferencia	Transferencia bancaria	t	2026-03-15 18:51:04.166201	AMBOS
3	Efectivo	Pago en efectivo	t	2026-03-15 18:51:04.166201	AMBOS
4	Zelle	Transferencia Zelle USD	t	2026-03-15 18:51:04.166201	AMBOS
5	Binance	Billetera movil	t	2026-03-15 21:34:21.516494	AMBOS
1	Pago Movil	Pago móvil interbancario	t	2026-03-15 18:51:04.166201	AMBOS
6	Paypal	metodo onnline	t	2026-03-24 17:27:10.345477	AMBOS
7	Efectivo $	Pago en dólares efectivo	t	2026-03-29 03:41:26.513811	USD
8	Efectivo Bs	Pago en bolívares efectivo	t	2026-03-29 03:41:26.557522	BS
9	pago	01	t	2026-04-20 02:10:33.134665	AMBOS
\.


--
-- Data for Name: metodos_pago_reservas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.metodos_pago_reservas (id, tipo, banco, cedula, telefono, numero_cuenta, codigo_banco, estado, created_at) FROM stdin;
1	pago_movil	Banco de venezuela	30555702	04124617132		0102	t	2026-04-20 02:48:42.122267
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
33	5	SALIDA_PEDIDO	1	5	4	Confirmación de pedido	PEDIDO:PED-20260326-35744	1	2026-03-26 05:55:19.670453
34	5	SALIDA_PEDIDO	1	4	3	Confirmación de pedido	PEDIDO:PED-20260326-13084	3	2026-03-26 05:57:04.057415
35	3	SALIDA_PEDIDO	1	7	6	Confirmación de pedido	PEDIDO:PED-20260326-16321	1	2026-03-26 10:46:25.828177
36	3	SALIDA	1	6	5	Venta desde reserva #RES-20260326-214	\N	1	2026-03-26 10:47:06.805518
37	3	SALIDA_PEDIDO	1	5	4	Confirmación de pedido	PEDIDO:PED-20260326-61309	1	2026-03-26 10:48:41.628643
38	4	SALIDA	1	16	15	Venta V-20260326172134-622	22	1	2026-03-26 16:21:34.902141
39	3	SALIDA	1	4	3	Venta V-20260326183454-135	23	1	2026-03-26 17:34:54.46474
40	3	ENTRADA	3	3	6	Recepción de compra	compra_id:6	1	2026-03-26 17:43:17.080599
41	5	SALIDA_PEDIDO	1	3	2	Confirmación de pedido	PEDIDO:PED-20260326-89559	1	2026-03-28 03:33:44.661071
42	1	SALIDA	1	10	9	Venta V-20260328204838-480	24	1	2026-03-28 19:48:38.6105
43	1	SALIDA	1	9	8	Venta V-20260412020309-823	25	1	2026-04-12 00:03:09.191329
44	9	SALIDA	1	10	9	Venta desde reserva #RES-20260415-745	\N	1	2026-04-15 02:29:29.109634
45	1	SALIDA	1	8	7	Venta desde reserva #RES-20260317-509	\N	1	2026-04-15 02:29:34.406774
46	1	SALIDA	1	7	6	Venta desde reserva #RES-20260317-908	\N	1	2026-04-15 02:29:38.875981
47	3	SALIDA	1	6	5	Venta desde reserva #RES-20260317-883	\N	1	2026-04-15 02:29:42.364727
48	5	ENTRADA	1	2	3	Recepción de compra	compra_id:8	1	2026-04-18 04:36:10.363859
49	3	ENTRADA	1	5	6	Recepción de compra	compra_id:8	1	2026-04-18 04:36:10.368722
50	2	ENTRADA	1	11	12	Recepción de compra	compra_id:7	1	2026-04-18 04:36:15.083451
51	3	ENTRADA	1	6	7	Recepción de compra	compra_id:9	1	2026-04-18 04:38:20.47293
52	1	ENTRADA	10	6	16	Recepción de compra	compra_id:10	1	2026-04-18 04:39:27.958252
53	3	ENTRADA	10	7	17	Recepción de compra	compra_id:10	1	2026-04-18 04:39:27.960632
54	5	ENTRADA	9	3	12	Recepción de compra	compra_id:10	1	2026-04-18 04:39:27.96223
55	1	SALIDA	1	16	15	Venta V-20260418211110-613	30	1	2026-04-18 19:11:10.005036
56	8	SALIDA	1	10	9	Venta desde reserva #RES-20260420-862	\N	1	2026-04-20 16:29:28.76219
57	9	SALIDA	1	9	8	Venta desde reserva #RES-20260420-075	\N	1	2026-04-20 17:45:37.810049
58	9	SALIDA	1	8	7	Venta desde reserva #RES-20260420-381	\N	1	2026-04-20 17:46:55.735345
59	1	SALIDA	1	15	14	Venta desde reserva #RES-20260420-745	\N	1	2026-04-20 17:47:19.410836
60	9	SALIDA	1	7	6	Venta desde reserva #RES-20260420-071	\N	1	2026-04-20 18:44:02.959159
61	1	SALIDA	1	14	13	Venta desde reserva #RES-20260420-873	\N	1	2026-04-20 18:46:06.431323
62	9	SALIDA	1	6	5	Venta desde reserva #RES-20260420-354	\N	1	2026-04-20 18:56:23.00971
63	5	SALIDA	1	12	11	Venta desde reserva #RES-20260420-262	\N	1	2026-04-20 19:12:11.222188
64	4	SALIDA	1	15	14	Venta desde reserva #RES-20260420-865	\N	1	2026-04-20 19:31:08.389815
65	1	SALIDA	1	13	12	Venta desde reserva #RES-20260420-788	\N	1	2026-04-20 19:49:58.180961
66	1	SALIDA	1	12	11	Venta desde reserva #RES-20260420-748	\N	1	2026-04-20 20:01:40.796543
67	9	SALIDA	1	5	4	Venta desde reserva #RES-20260420-951	\N	1	2026-04-20 21:46:44.924613
68	5	SALIDA	1	10	10	Venta desde reserva #RES-20260421-855	\N	1	2026-04-20 22:20:14.413711
69	9	ENTRADA	10	4	14	Recepción de compra	compra_id:11	1	2026-04-21 14:19:12.522929
70	1	SALIDA	1	11	10	Venta V-20260421162122-669	44	1	2026-04-21 14:21:22.025676
71	8	SALIDA	1	8	8	Venta desde reserva #RES-20260421-934	\N	1	2026-04-21 15:04:33.358236
72	9	SALIDA	1	10	10	Venta desde reserva #RES-20260421-835	\N	1	2026-04-21 15:05:40.643244
73	9	SALIDA	1	10	10	Venta desde reserva #RES-20260421-057	\N	1	2026-04-21 15:16:14.518132
74	9	SALIDA	1	9	9	Venta desde reserva #RES-20260421-078	\N	1	2026-04-21 15:25:51.809637
75	1	SALIDA	1	10	10	Venta desde reserva #RES-20260420-227	\N	1	2026-04-21 15:28:11.702716
76	9	SALIDA	1	9	9	Venta desde reserva #RES-20260421-846	\N	1	2026-04-21 15:32:25.000439
77	9	SALIDA	1	9	9	Venta desde reserva #RES-20260419-286	\N	1	2026-04-21 15:39:53.910959
78	8	SALIDA	1	7	7	Venta desde reserva #RES-20260421-668	\N	1	2026-04-21 16:09:01.433011
79	8	SALIDA_PEDIDO	1	7	6	Confirmación de pedido	PEDIDO:PED-20260422-14882	11	2026-04-21 22:25:22.295108
80	9	SALIDA	4	4	4	Venta desde reserva #RES-20260421-661	\N	1	2026-04-24 18:56:50.705035
81	9	SALIDA	1	4	4	Venta desde reserva #RES-20260424-195	\N	1	2026-04-24 18:57:10.374055
82	3	SALIDA_PEDIDO	1	17	16	Confirmación de pedido	PEDIDO:PED-20260424-39776	1	2026-04-24 18:58:07.619866
83	9	SALIDA_PEDIDO	1	4	3	Confirmación de pedido	PEDIDO:PED-20260424-95516	11	2026-04-24 19:00:09.0917
84	9	SALIDA_PEDIDO	2	3	1	Confirmación de pedido	PEDIDO:PED-20260422-69541	1	2026-04-24 20:28:56.427404
85	9	SALIDA_PEDIDO	1	1	0	Confirmación de pedido	PEDIDO:PED-20260422-67492	1	2026-04-24 20:28:59.632627
86	1	ENTRADA	10	10	20	Recepción de compra	compra_id:10	1	2026-04-24 20:30:18.410185
87	3	ENTRADA	10	16	26	Recepción de compra	compra_id:10	1	2026-04-24 20:30:18.412282
88	5	ENTRADA	9	11	20	Recepción de compra	compra_id:10	1	2026-04-24 20:30:18.41819
89	9	ENTRADA	20	0	20	Recepción de compra	compra_id:12	1	2026-04-24 20:32:35.125259
90	9	SALIDA_PEDIDO	1	20	19	Confirmación de pedido	PEDIDO:PED-20260421-30253	1	2026-04-24 20:32:54.369294
91	8	SALIDA_PEDIDO	1	6	5	Confirmación de pedido	PEDIDO:PED-20260421-06200	1	2026-04-24 20:32:59.458526
92	5	SALIDA_PEDIDO	1	20	19	Confirmación de pedido	PEDIDO:PED-20260324-36596	1	2026-04-24 20:33:28.680751
93	5	SALIDA_PEDIDO	1	19	18	Confirmación de pedido	PEDIDO:PED-20260324-84847	1	2026-04-24 20:33:32.654354
94	5	SALIDA_PEDIDO	1	18	17	Confirmación de pedido	PEDIDO:PED-20260323-64314	1	2026-04-24 20:33:41.8232
95	5	SALIDA_PEDIDO	1	17	16	Confirmación de pedido	PEDIDO:PED-20260324-30473	1	2026-04-24 20:33:45.735349
96	8	ENTRADA	15	5	20	Recepción de compra	compra_id:13	1	2026-04-24 20:35:22.55223
97	9	SALIDA_PEDIDO	1	19	18	Confirmación de pedido	PEDIDO:PED-20260424-87338	11	2026-04-24 20:40:57.125574
98	18	ENTRADA	1	30	31	Recepción de compra	compra_id:14	1	2026-04-28 18:18:49.386414
99	2	ENTRADA	8	12	20	Recepción de compra	compra_id:15	1	2026-04-28 18:20:21.712343
100	13	ENTRADA	5	19	24	Recepción de compra	compra_id:16	1	2026-04-28 18:23:20.409493
\.


--
-- Data for Name: notificaciones_vendedor; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notificaciones_vendedor (id, pedido_id, titulo, mensaje, tipo, leida, usuario_id, created_at) FROM stdin;
\.


--
-- Data for Name: pedidos_online; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pedidos_online (id, codigo_pedido, cliente_id, subtotal, iva, total, estado_pedido, metodo_pago_id, direccion_entrega, telefono_contacto, observaciones, canal_comunicacion, tipo_entrega, referencia_pago, vendedor_asignado_id, fecha_asignacion, fecha_contacto, fecha_pago, intentos_contacto, venta_id, created_at, updated_at, comprobante_url, metodo_pago) FROM stdin;
5	PED-20260317-09354	1	30.00	4.80	34.80	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 04175550246 | Ref: 1234	telegram	tienda	\N	\N	\N	\N	2026-03-18 02:29:16.780025	0	\N	2026-03-17 17:57:34.695735	2026-03-18 02:29:16.780025	\N	\N
7	PED-20260318-87219	1	20.00	3.20	23.20	CONFIRMADO	1	xxx	+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	whatsapp	domicilio	\N	\N	\N	\N	2026-03-18 05:15:45.65237	0	\N	2026-03-18 05:14:07.562607	2026-03-18 05:15:45.65237	\N	\N
8	PED-20260318-45142	2	20.00	3.20	23.20	CONFIRMADO	1		+5804127550246	 | Pago: Transferencia | Banco: mercantil | Ref: 01023405 | Monto: Bs 20	notificaciones	tienda	\N	3	2026-03-18 12:32:42.092839	\N	2026-03-18 12:34:16.074467	0	\N	2026-03-18 12:29:17.253085	2026-03-18 12:34:16.074467	\N	\N
2	PED-20260316-30536	2	30.00	4.80	34.80	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 04121304526 | Ref: 0134	whatsapp	tienda	\N	\N	\N	\N	2026-03-18 12:35:14.308375	0	\N	2026-03-16 04:42:16.708425	2026-03-18 12:35:14.308375	\N	\N
1	PED-20260316-92372	2	30.00	4.80	34.80	CONFIRMADO	1		04127550246	xxx | Pago: Transferencia | Banco: mercantil | Ref: 01023405 | Monto: Bs 20	whatsapp	tienda	\N	\N	\N	\N	2026-03-18 12:35:23.421708	0	\N	2026-03-16 04:01:00.492774	2026-03-18 12:35:23.421708	\N	\N
3	PED-20260317-29323	1	30.00	4.80	34.80	CONFIRMADO	5		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	whatsapp	tienda	\N	\N	\N	\N	2026-03-18 12:35:45.254099	0	\N	2026-03-17 17:53:38.218223	2026-03-18 12:35:45.254099	\N	\N
4	PED-20260317-74178	1	30.00	4.80	34.80	CONFIRMADO	5		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 04175550246 | Ref: 1234 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	telegram	tienda	\N	\N	\N	\N	2026-03-18 12:36:02.413993	0	\N	2026-03-17 17:55:15.306391	2026-03-18 12:36:02.413993	\N	\N
9	PED-20260318-98418	2	39.98	6.40	46.38	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0293	notificaciones	tienda	\N	3	2026-03-18 12:42:48.799393	\N	2026-03-18 12:43:48.568802	0	\N	2026-03-18 12:41:21.101391	2026-03-18 12:43:48.568802	\N	\N
14	PED-20260324-93611	2	20.00	3.20	23.20	CONFIRMADO	1		+5804121304526	xxx | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	notificaciones	tienda	\N	3	2026-03-24 16:11:04.432774	\N	2026-03-24 16:12:24.673274	0	\N	2026-03-24 16:10:16.91911	2026-03-24 16:12:24.673274	\N	\N
15	PED-20260326-35744	2	20.00	3.20	23.20	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	whatsapp	tienda	\N	\N	\N	\N	2026-03-26 05:55:19.670453	0	\N	2026-03-26 05:53:24.866167	2026-03-26 05:55:19.670453	\N	\N
16	PED-20260326-13084	1	20.00	3.20	23.20	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	notificaciones	tienda	\N	3	2026-03-26 05:56:16.174407	\N	2026-03-26 05:57:04.057415	0	\N	2026-03-26 05:55:50.285861	2026-03-26 05:57:04.057415	\N	\N
18	PED-20260326-16321	1	30.00	4.80	34.80	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	whatsapp	tienda	\N	\N	\N	\N	2026-03-26 10:46:25.828177	0	\N	2026-03-26 10:45:26.315394	2026-03-26 10:46:25.828177	\N	\N
19	PED-20260326-61309	1	30.00	4.80	34.80	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	telegram	tienda	\N	\N	\N	\N	2026-03-26 10:48:41.628643	0	\N	2026-03-26 10:48:08.347823	2026-03-26 10:48:41.628643	\N	\N
20	PED-20260326-89559	1	20.00	3.20	23.20	CONFIRMADO	1		+5804127550246	 | Pago: Pago Móvil | Banco: banesco | Tel: 0412556783 | Ref: 1234	whatsapp	tienda	\N	\N	\N	\N	2026-03-28 03:33:44.661071	0	\N	2026-03-26 17:57:54.101449	2026-03-28 03:33:44.661071	\N	\N
21	PED-20260421-65746	1	80.00	12.80	92.80	PENDIENTE	\N		+5804127550246		whatsapp	tienda	\N	\N	\N	\N	\N	0	\N	2026-04-21 17:37:13.583503	2026-04-21 17:37:13.583503	\N	\N
22	PED-20260421-11578	1	4000.00	640.00	4640.00	PENDIENTE	\N		+5804127550246		whatsapp	tienda	\N	\N	\N	\N	\N	0	\N	2026-04-21 18:10:13.058092	2026-04-21 18:10:13.058092	\N	\N
23	PED-20260421-10105	1	2000.00	320.00	2320.00	PENDIENTE	\N		+5804127550246		whatsapp	tienda	\N	\N	\N	\N	\N	0	\N	2026-04-21 18:11:44.227266	2026-04-21 18:11:44.227266	\N	\N
24	PED-20260421-92097	1	40.00	6.40	46.40	PENDIENTE	\N		+5804127550246		telegram	tienda	\N	\N	\N	\N	\N	0	\N	2026-04-21 18:13:01.506901	2026-04-21 18:13:01.506901	\N	\N
25	PED-20260421-53677	1	80.00	12.80	92.80	PENDIENTE	\N		+5804127550246		whatsapp	tienda	\N	\N	\N	\N	\N	0	\N	2026-04-21 18:27:56.67571	2026-04-21 18:27:56.67571	\N	\N
26	PED-20260421-42173	1	40.00	6.40	46.40	PENDIENTE	\N		+5804127550246		whatsapp	tienda	\N	\N	\N	\N	\N	0	\N	2026-04-21 18:29:08.671156	2026-04-21 18:29:08.671156	\N	\N
27	PED-20260421-82922	1	40.00	6.40	46.40	PENDIENTE	\N		+5804127550246		telegram	tienda	\N	\N	\N	\N	\N	0	\N	2026-04-21 18:30:06.428296	2026-04-21 18:30:06.428296	\N	\N
28	PED-20260421-23292	1	40.00	6.40	46.40	PENDIENTE	\N		+5804127550246		telegram	tienda	\N	\N	\N	\N	\N	0	\N	2026-04-21 18:41:05.091012	2026-04-21 18:41:05.091012	\N	\N
33	PED-20260422-14882	2	40.00	6.40	46.40	CONFIRMADO	\N		+5804127550246	 | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134	notificaciones	tienda	\N	11	2026-04-21 22:17:47.65068	\N	2026-04-21 22:25:22.295108	0	\N	2026-04-21 22:15:36.022547	2026-04-21 22:25:22.295108	\N	\N
32	PED-20260422-69541	2	4000.00	640.00	4640.00	CONFIRMADO	\N		+5804127550246	 | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134	email	tienda	\N	\N	\N	\N	2026-04-24 20:28:56.427404	0	\N	2026-04-21 22:13:04.500257	2026-04-24 20:28:56.427404	\N	\N
35	PED-20260424-39776	2	30.00	4.80	34.80	CONFIRMADO	\N		+5804127550246		notificaciones	tienda	1234	3	2026-04-24 18:57:57.553332	\N	2026-04-24 18:58:07.619866	0	\N	2026-04-24 18:51:28.077833	2026-04-24 18:58:07.619866	/public/uploads/pedidos_comprobantes/ped_69ebbbb013e991.23561933.jpeg	pago_movil - Banco de venezuela
34	PED-20260424-95516	1	2000.00	320.00	2320.00	CONFIRMADO	\N		+5804127550246		notificaciones	tienda	\N	11	2026-04-24 18:58:22.277583	\N	2026-04-24 19:00:09.0917	0	\N	2026-04-24 18:42:34.910443	2026-04-24 19:00:09.0917	/public/uploads/pedidos_comprobantes/ped_69ebb99ae0cb72.95735635.jpeg	pago_movil - Banco de venezuela
31	PED-20260422-67492	2	2000.00	320.00	2320.00	CONFIRMADO	\N		+5804127550246	 | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134	email	tienda	\N	\N	\N	\N	2026-04-24 20:28:59.632627	0	\N	2026-04-21 22:06:06.602873	2026-04-24 20:28:59.632627	\N	\N
30	PED-20260421-30253	2	2000.00	320.00	2320.00	CONFIRMADO	\N		+5804127550246	 | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134	email	tienda	\N	\N	\N	\N	2026-04-24 20:32:54.369294	0	\N	2026-04-21 21:58:51.886101	2026-04-24 20:32:54.369294	\N	\N
29	PED-20260421-06200	2	40.00	6.40	46.40	CONFIRMADO	\N		+5804127550246	 | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134	email	tienda	\N	\N	\N	\N	2026-04-24 20:32:59.458526	0	\N	2026-04-21 18:58:18.95439	2026-04-24 20:32:59.458526	\N	\N
13	PED-20260324-36596	2	20.00	3.20	23.20	CONFIRMADO	\N		+5804121304526	xxx | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134	email	tienda	\N	\N	\N	\N	2026-04-24 20:33:28.680751	0	\N	2026-03-24 16:09:22.933549	2026-04-24 20:33:28.680751	\N	\N
11	PED-20260324-84847	2	20.00	3.20	23.20	CONFIRMADO	\N		+5804121304526	xxx | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134	telegram	tienda	\N	\N	\N	\N	2026-04-24 20:33:32.654354	0	\N	2026-03-24 01:35:17.6934	2026-04-24 20:33:32.654354	\N	\N
10	PED-20260323-64314	2	20.00	3.20	23.20	CONFIRMADO	\N	calle casa	+5804127550246	xxxx | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134	email	domicilio	\N	\N	\N	\N	2026-04-24 20:33:41.8232	0	\N	2026-03-23 01:25:37.997515	2026-04-24 20:33:41.8232	\N	\N
12	PED-20260324-30473	2	20.00	3.20	23.20	CONFIRMADO	\N		+5804121304526	xxx | Pago: Pago Móvil | Banco: Banesco | Tel: 04121304526 | Ref: 0134	telegram	tienda	\N	\N	\N	\N	2026-04-24 20:33:45.735349	0	\N	2026-03-24 16:08:34.481177	2026-04-24 20:33:45.735349	\N	\N
6	PED-20260318-95419	1	30.00	4.80	34.80	PENDIENTE	\N		+5804127550246		notificaciones	tienda	\N	3	2026-03-18 12:32:48.317401	\N	\N	0	\N	2026-03-18 00:06:45.04888	2026-04-24 20:33:50.707345	\N	\N
36	PED-20260424-87338	2	2000.00	320.00	2320.00	CONFIRMADO	\N		+5804127550246		notificaciones	tienda	1234	11	2026-04-24 20:39:28.758081	\N	2026-04-24 20:40:57.125574	0	\N	2026-04-24 20:37:37.073215	2026-04-24 20:40:57.125574	/public/uploads/pedidos_comprobantes/ped_69ebd4911305d6.72179700.jpeg	pago_movil - Banco de venezuela
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
6	8	/inversiones-rojas/public/img/products/prod_8_1775428774_0.jpg	t	1	2026-04-05 22:39:34.495429
7	9	/inversiones-rojas/public/img/products/prod_9_1775481619_0.png	t	1	2026-04-06 13:20:19.665111
8	10	/inversiones-rojas/public/img/products/prod_10_1777085280_0.jpg	t	1	2026-04-25 02:48:00.203356
9	11	/inversiones-rojas/public/img/products/prod_11_1777086398_0.jpg	t	1	2026-04-25 03:06:38.646929
10	12	/inversiones-rojas/public/img/products/prod_12_1777251983_0.webp	t	1	2026-04-27 01:06:23.348943
11	13	/inversiones-rojas/public/img/products/prod_13_1777337624_0.jpg	t	1	2026-04-28 00:53:44.889882
12	14	/inversiones-rojas/public/img/products/prod_14_1777338944_0.jpg	t	1	2026-04-28 01:15:44.958319
13	15	/inversiones-rojas/public/img/products/prod_15_1777339259_0.webp	t	1	2026-04-28 01:20:59.50949
14	16	/inversiones-rojas/public/img/products/prod_16_1777339449_0.webp	t	1	2026-04-28 01:24:08.990974
15	17	/inversiones-rojas/public/img/products/prod_17_1777339938_0.webp	t	1	2026-04-28 01:32:18.599535
16	18	/inversiones-rojas/public/img/products/prod_18_1777341854_0.webp	t	1	2026-04-28 02:04:14.136267
17	19	/inversiones-rojas/public/img/products/prod_19_1777342051_0.webp	t	1	2026-04-28 02:07:31.055205
18	20	/inversiones-rojas/public/img/products/prod_20_1777342266_0.webp	t	1	2026-04-28 02:11:06.255642
19	21	/inversiones-rojas/public/img/products/prod_21_1777342550_0.webp	t	1	2026-04-28 02:15:50.1425
20	22	/inversiones-rojas/public/img/products/prod_22_1777342736_0.webp	t	1	2026-04-28 02:18:56.746204
21	23	/inversiones-rojas/public/img/products/prod_23_1777344986_0.webp	t	1	2026-04-28 02:56:26.245159
22	24	/inversiones-rojas/public/img/products/prod_24_1777348025_0.png	t	1	2026-04-28 03:47:05.147572
23	25	/inversiones-rojas/public/img/products/prod_25_1777348403_0.png	t	1	2026-04-28 03:53:23.722132
24	26	/inversiones-rojas/public/img/products/prod_26_1777384888_0.png	t	1	2026-04-28 14:01:28.582444
25	26	/inversiones-rojas/public/img/products/prod_26_1777384888_1.png	f	2	2026-04-28 14:01:28.582444
26	27	/inversiones-rojas/public/img/products/prod_27_1777386037_0.webp	t	1	2026-04-28 14:20:37.717397
27	28	/inversiones-rojas/public/img/products/prod_28_1777386264_0.webp	t	1	2026-04-28 14:24:24.763983
28	29	/inversiones-rojas/public/img/products/prod_29_1777386785_0.webp	t	1	2026-04-28 14:33:05.339875
29	30	/inversiones-rojas/public/img/products/prod_30_1777387016_0.webp	t	1	2026-04-28 14:36:56.880397
30	31	/inversiones-rojas/public/img/products/prod_31_1777387158_0.webp	t	1	2026-04-28 14:39:18.628534
31	32	/inversiones-rojas/public/img/products/prod_32_1777387389_0.webp	t	1	2026-04-28 14:43:09.812249
32	33	/inversiones-rojas/public/img/products/prod_33_1777387572_0.webp	t	1	2026-04-28 14:46:12.592747
33	34	/inversiones-rojas/public/img/products/prod_34_1777388054_0.png	t	1	2026-04-28 14:54:14.002963
34	35	/inversiones-rojas/public/img/products/prod_35_1777391105_0.jpg	t	1	2026-04-28 15:45:05.915709
35	36	/inversiones-rojas/public/img/products/prod_36_1777391784_0.png	t	1	2026-04-28 15:56:24.354402
36	37	/inversiones-rojas/public/img/products/prod_37_1777392319_0.jpg	t	1	2026-04-28 16:05:19.051165
37	38	/inversiones-rojas/public/img/products/prod_38_1777392659_0.webp	t	1	2026-04-28 16:10:59.32851
38	39	/inversiones-rojas/public/img/products/prod_39_1777396233_0.webp	t	1	2026-04-28 17:10:33.412373
39	40	/inversiones-rojas/public/img/products/prod_40_1777396564_0.jpg	t	1	2026-04-28 17:16:04.46817
40	41	/inversiones-rojas/public/img/products/prod_41_1777396967_0.jpg	t	1	2026-04-28 17:22:47.491098
41	42	/inversiones-rojas/public/img/products/prod_42_1777397736_0.webp	t	1	2026-04-28 17:35:36.540123
42	43	/inversiones-rojas/public/img/products/prod_43_1777397954_0.webp	t	1	2026-04-28 17:39:14.719324
43	44	/inversiones-rojas/public/img/products/prod_44_1777398753_0.jpg	t	1	2026-04-28 17:52:33.336698
44	45	/inversiones-rojas/public/img/products/prod_45_1777399223_0.png	t	1	2026-04-28 18:00:23.060304
45	46	/inversiones-rojas/public/img/products/prod_46_1777399515_0.png	t	1	2026-04-28 18:05:15.877288
46	47	/inversiones-rojas/public/img/products/prod_47_1777399684_0.png	t	1	2026-04-28 18:08:04.555206
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
7	1	6	2026-03-26 17:55:24.858014
9	3	7	2026-04-18 03:21:00.381773
10	1	8	2026-04-21 22:27:49.088726
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
6	6	4	20.00	\N	\N	t	t	2026-03-24 17:32:25.989444	2026-03-24 17:32:25.989444
7	7	1	10.00	\N	\N	t	t	2026-03-26 13:53:30.305694	2026-03-26 13:53:30.305694
11	11	1	1500.00	\N	\N	t	t	2026-04-25 03:06:38.646929	2026-04-25 03:06:38.646929
12	12	1	60.00	\N	\N	t	t	2026-04-27 01:06:23.348943	2026-04-27 01:06:23.348943
13	13	1	50.00	\N	\N	t	t	2026-04-28 00:53:44.889882	2026-04-28 00:53:44.889882
14	14	3	70.00	\N	\N	t	t	2026-04-28 01:15:44.958319	2026-04-28 01:15:44.958319
15	15	2	65.00	\N	\N	t	t	2026-04-28 01:20:59.50949	2026-04-28 01:20:59.50949
16	16	2	100.00	\N	\N	t	t	2026-04-28 01:24:08.990974	2026-04-28 01:24:08.990974
17	17	3	110.00	\N	\N	t	t	2026-04-28 01:32:18.599535	2026-04-28 01:32:18.599535
18	18	1	30.00	\N	\N	t	t	2026-04-28 02:04:14.136267	2026-04-28 02:04:14.136267
8	8	1	19.99	\N	\N	f	t	2026-04-05 22:39:34.495429	2026-04-05 22:39:34.495429
19	8	5	8000.00	\N	\N	t	t	2026-04-28 02:04:49.090015	2026-04-28 02:04:49.090015
20	19	1	30.00	\N	\N	t	t	2026-04-28 02:07:31.055205	2026-04-28 02:07:31.055205
21	20	1	30.00	\N	\N	t	t	2026-04-28 02:11:06.255642	2026-04-28 02:11:06.255642
22	21	1	40.00	\N	\N	t	t	2026-04-28 02:15:50.1425	2026-04-28 02:15:50.1425
23	22	1	30.00	\N	\N	t	t	2026-04-28 02:18:56.746204	2026-04-28 02:18:56.746204
24	23	6	4200.00	\N	\N	t	t	2026-04-28 02:56:26.245159	2026-04-28 02:56:26.245159
9	9	1	1000.00	\N	\N	t	t	2026-04-06 13:20:19.665111	2026-04-06 13:20:19.665111
25	24	1	700.00	\N	\N	t	t	2026-04-28 03:47:05.147572	2026-04-28 03:47:05.147572
26	25	1	500.00	\N	\N	t	t	2026-04-28 03:53:23.722132	2026-04-28 03:53:23.722132
27	26	1	1000.00	\N	\N	t	t	2026-04-28 14:01:28.582444	2026-04-28 14:01:28.582444
28	27	1	160.00	\N	\N	t	t	2026-04-28 14:20:37.717397	2026-04-28 14:20:37.717397
29	28	1	190.00	\N	\N	t	t	2026-04-28 14:24:24.763983	2026-04-28 14:24:24.763983
30	29	1	20.00	\N	\N	t	t	2026-04-28 14:33:05.339875	2026-04-28 14:33:05.339875
31	30	1	15.00	\N	\N	t	t	2026-04-28 14:36:56.880397	2026-04-28 14:36:56.880397
32	31	1	25.00	\N	\N	t	t	2026-04-28 14:39:18.628534	2026-04-28 14:39:18.628534
33	32	1	30.00	\N	\N	t	t	2026-04-28 14:43:09.812249	2026-04-28 14:43:09.812249
34	33	1	20.00	\N	\N	t	t	2026-04-28 14:46:12.592747	2026-04-28 14:46:12.592747
10	10	1	1000.00	\N	\N	t	t	2026-04-25 02:48:00.203356	2026-04-25 02:48:00.203356
35	34	1	2000.00	\N	\N	t	t	2026-04-28 14:54:14.002963	2026-04-28 14:54:14.002963
36	35	1	4000.00	\N	\N	t	t	2026-04-28 15:45:05.915709	2026-04-28 15:45:05.915709
37	36	1	5000.00	\N	\N	t	t	2026-04-28 15:56:24.354402	2026-04-28 15:56:24.354402
38	37	1	2000.00	\N	\N	t	t	2026-04-28 16:05:19.051165	2026-04-28 16:05:19.051165
39	38	1	3000.00	\N	\N	t	t	2026-04-28 16:10:59.32851	2026-04-28 16:10:59.32851
40	39	1	15.00	\N	\N	t	t	2026-04-28 17:10:33.412373	2026-04-28 17:10:33.412373
41	40	1	20.00	\N	\N	t	t	2026-04-28 17:16:04.46817	2026-04-28 17:16:04.46817
42	41	1	25.00	\N	\N	t	t	2026-04-28 17:22:47.491098	2026-04-28 17:22:47.491098
43	42	1	20.00	\N	\N	t	t	2026-04-28 17:35:36.540123	2026-04-28 17:35:36.540123
44	43	1	30.00	\N	\N	t	t	2026-04-28 17:39:14.719324	2026-04-28 17:39:14.719324
45	44	1	1000.00	\N	\N	t	t	2026-04-28 17:52:33.336698	2026-04-28 17:52:33.336698
46	45	1	1000.00	\N	\N	t	t	2026-04-28 18:00:23.060304	2026-04-28 18:00:23.060304
47	46	1	1200.00	\N	\N	t	t	2026-04-28 18:05:15.877288	2026-04-28 18:05:15.877288
48	47	1	1100.00	\N	\N	t	t	2026-04-28 18:08:04.555206	2026-04-28 18:08:04.555206
\.


--
-- Data for Name: productos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.productos (id, codigo_interno, nombre, descripcion, categoria_id, precio_compra, precio_venta, stock_actual, stock_minimo, stock_maximo, proveedor_id, estado, tipo_id, created_at, updated_at, moneda_base, precio_venta_bs, precio_venta_usd, precio_compra_bs, precio_compra_usd, stock_reservado) FROM stdin;
7	RP-147549-159	producto de prueba2	xxx	4	10.00	10.01	10	4	100	1	f	2	2026-03-26 13:53:30.305694	2026-03-26 13:53:46.042766	USD	\N	\N	\N	\N	0
14	AC-415106-080	CHAQUETA INVICTUS	Esta chaqueta textil de alta calidad combina seguridad CE con un diseño agresivo. Cuenta con ventilación, membrana impermeable, forro térmico extraíble y detalles reflectantes para máxima visibilidad.	1	70.00	80.00	30	5	100	3	t	3	2026-04-28 01:15:44.958319	2026-04-28 01:15:44.958319	USD	32000.00	80.00	28000.00	70.00	0
16	AC-315767-930	CHAQUETA NORMANDIE	Diseño clásico con protección de alto nivel.\r\nCuero premium de alta resistencia, elegante y duradera para rodar con máximo estilo.	1	100.00	120.00	30	5	100	2	t	3	2026-04-28 01:24:08.990974	2026-04-28 01:24:08.990974	USD	48000.00	120.00	40000.00	100.00	0
15	AC-029863-550	CHAQUETA ALPINESTAR	Máxima protección deportiva, alta visibilidad y diseño aerodinámico.\nResistente, ventilada y ergonómica; ideal para rodar con seguridad y estilo profesional.	1	65.00	80.00	20	5	100	2	t	3	2026-04-28 01:20:59.50949	2026-04-28 01:24:40.924457	USD	32000.00	80.00	26000.00	65.00	0
1	AC-449461-311	chaqueta	cambiado	1	30.00	40.00	20	4	100	1	f	3	2026-03-15 20:32:50.314258	2026-04-28 01:27:33.605192	USD	\N	\N	\N	\N	2
17	AC-740149-217	CHAQUETA  DAINESE	Protección deportiva de élite y diseño ergonómico.\r\nResistencia superior y estilo profesional para una conducción segura y de alto rendimiento.	1	110.00	130.00	30	5	100	3	t	3	2026-04-28 01:32:18.599535	2026-04-28 01:32:18.599535	USD	52000.00	130.00	44000.00	110.00	0
23	VH-831630-907	Moto Yamaha YZF-R3	Motocicleta deportiva de alta gama con motor bicilíndrico 321cc refrigerado por líquido, ABS, perfecta para carretera y pista.	5	4200.00	5498.99	30	5	97	6	t	1	2026-04-28 02:56:26.245159	2026-04-28 02:56:26.245159	USD	2199596.00	5498.99	1680000.00	4200.00	0
9	VH-422268-306	Moto SBR	moto sencilla	3	1000.00	2000.00	18	5	100	1	t	1	2026-04-06 13:20:19.665111	2026-04-28 02:57:59.73983	USD	800000.00	2000.00	400000.00	1000.00	0
8	VH-479331-106	NINJA 1000SX	La mejor de ambos mundos.Deportiva de larga distancia con electrónica de última generación y motor de 1,043cc.	5	8000.00	15900.00	200	100	300	5	t	1	2026-04-05 22:39:34.495429	2026-04-28 02:04:49.090015	USD	6360000.00	15900.00	3200000.00	8000.00	0
13	AC-886302-362	CHAQUETA IMPERMEABLE	Protección ligera, ajustable y reflectante contra lluvia y viento. Máxima visibilidad y seguridad garantizada en carretera.	1	50.00	70.00	24	5	100	1	t	3	2026-04-28 00:53:44.889882	2026-04-28 18:23:20.408848	USD	28000.00	70.00	20000.00	50.00	0
24	VH-909153-280	Moto Bera BRZ 250cc	Motocicleta tipo naked de 250cc, ideal para ciudad y carretera. Motor de 1 cilindro 4 tiempos enfriado por aceite, con una velocidad máxima de 145 km/h y un rendimiento excelente de hasta 2.5 litros por cada 100 km.	3	700.00	1700.00	50	30	100	1	t	1	2026-04-28 03:47:05.147572	2026-04-28 03:47:05.147572	USD	680000.00	1700.00	280000.00	700.00	0
19	AC-860121-365	CASCO BERA CYCLONE	Casco abierto con visor transparente amplio para ciudad, diseño dinámico y máxima visibilidad.	1	30.00	40.00	30	5	100	1	t	3	2026-04-28 02:07:31.055205	2026-04-28 02:07:31.055205	USD	16000.00	40.00	12000.00	30.00	0
20	AC-143640-371	CASCO INTEGRAL	Versatilidad en negro mate con doble visor integrado.\nDiseño resistente con ventilación de alto flujo para máxima protección en todo terreno.	1	30.00	40.00	30	5	100	1	t	3	2026-04-28 02:11:06.255642	2026-04-28 02:13:52.989803	USD	16000.00	40.00	12000.00	30.00	0
21	AC-449287-919	CASCO BERA ROAD	Diseño aventurero, alta resistencia y ventilación eficiente.\r\nProtección superior con estilo agresivo, ideal para rutas exigentes y máxima seguridad.	1	40.00	50.00	30	5	100	1	t	3	2026-04-28 02:15:50.1425	2026-04-28 02:15:50.1425	USD	20000.00	50.00	16000.00	40.00	0
22	AC-598814-874	CASCO BERA ROTULADO	Diseño aerodinámico con visor anti-rayas, protección UV y ventilación optimizada para máxima seguridad y estilo.	1	30.00	40.00	30	5	100	1	t	3	2026-04-28 02:18:56.746204	2026-04-28 02:18:56.746204	USD	16000.00	40.00	12000.00	30.00	0
25	VH-137724-175	Moto Cobra 150cc	Motocicleta tipo scooter urbano de 150cc, práctica y económica para la ciudad.	6	500.00	1000.00	25	5	100	1	t	1	2026-04-28 03:53:23.722132	2026-04-28 03:53:23.722132	USD	400000.00	1000.00	200000.00	500.00	0
26	VH-634566-968	Moto GBR 200cc	xxx	3	1000.00	2000.00	10	5	100	1	t	1	2026-04-28 14:01:28.582444	2026-04-28 14:01:28.582444	USD	800000.00	2000.00	400000.00	1000.00	0
27	RP-684517-936	MOTOR DE BERA 200CC	Motor de repuesto 4 tiempos de alta durabilidad, diseñado para restaurar el rendimiento óptimo de tu motocicleta.	2	160.00	200.00	25	5	100	1	t	2	2026-04-28 14:20:37.717397	2026-04-28 14:20:37.717397	USD	80000.00	200.00	64000.00	160.00	0
28	RP-202379-707	MOTOR DE BERA 250CC	Motor Bera de alto rendimiento y durabilidad superior con acabado premium.\nDiseño eficiente para una potencia estable y larga vida útil en cualquier ruta.	2	190.00	210.00	20	5	100	1	t	2	2026-04-28 14:24:24.763983	2026-04-28 14:28:37.628683	USD	84000.00	210.00	76000.00	190.00	0
29	RP-666401-264	FRENO DE DISCO DE MOTO MILAN	Sistema de freno Bera completo: máxima potencia, seguridad y respuesta inmediata.\r\nKit de alto rendimiento listo para instalar, optimizado para una frenada eficiente.	4	20.00	30.00	20	5	100	1	t	2	2026-04-28 14:33:05.339875	2026-04-28 14:33:05.339875	USD	12000.00	30.00	8000.00	20.00	0
30	RP-868840-337	PASTILLA DE FRENO	Pastillas de alto rendimiento para frenado seguro, compatibles con Bera, Honda y más.	4	15.00	25.00	20	5	100	1	t	2	2026-04-28 14:36:56.880397	2026-04-28 14:36:56.880397	USD	10000.00	25.00	6000.00	15.00	0
31	RP-051335-839	DISCO DE FRENO DELANTERO	Disco de freno ondulado y ranurado para máxima disipación de calor y frenada potente.	4	25.00	40.00	20	5	100	1	t	2	2026-04-28 14:39:18.628534	2026-04-28 14:39:18.628534	USD	16000.00	40.00	10000.00	25.00	0
32	RP-290262-224	SISTEMA DE FRENO DELANTERO	Sistema de freno delantero completo con bomba y caliper para una frenada potente y segura.	4	30.00	40.00	20	5	100	1	t	2	2026-04-28 14:43:09.812249	2026-04-28 14:43:09.812249	USD	16000.00	40.00	12000.00	30.00	0
11	VH-171518-303	MOTO BERA DT 200 RR	MOTO	3	1500.00	1800.00	14	5	100	1	t	1	2026-04-25 03:06:38.646929	2026-04-25 03:06:38.646929	USD	720000.00	1800.00	600000.00	1500.00	0
10	VH-216881-176	BRF 150cc	xxx	3	1000.00	1400.00	30	10	100	1	t	1	2026-04-25 02:48:00.203356	2026-04-28 14:47:02.967063	USD	560000.00	1400.00	400000.00	1000.00	0
3	VH-716677-316	Moto bera	xxx	3	20.00	30.00	26	10	100	1	f	1	2026-03-15 21:24:05.570024	2026-04-27 00:49:47.667946	USD	\N	\N	\N	\N	0
5	VH-512430-071	motos	xxx	3	10.00	20.00	16	5	100	1	f	1	2026-03-18 05:09:50.531873	2026-04-27 00:50:01.446587	USD	\N	\N	\N	\N	0
4	RP-178803-983	frenos	xxx	2	10.00	19.98	14	5	100	1	f	2	2026-03-15 21:31:03.410675	2026-04-27 00:55:06.877487	USD	\N	\N	\N	\N	0
12	RP-370307-563	MOTOR CG DE MOTOCICLETA 150CC	Motor CG 150cc de alto rendimiento: la solución ideal para renovar tu moto con potencia fiable, máxima durabilidad y mantenimiento sencillo. Calidad y eficiencia garantizadas para tu día a día1	2	60.00	90.00	15	5	100	1	t	2	2026-04-27 01:06:23.348943	2026-04-27 01:06:23.348943	USD	36000.00	90.00	24000.00	60.00	0
2	RP-362881-579	Freno	xxx	2	20.00	30.00	20	4	100	1	f	2	2026-03-15 21:17:38.688799	2026-04-28 18:20:21.711343	USD	\N	\N	\N	\N	0
6	RP-436764-355	Producto de ejemplo	ejemplo	4	20.00	39.99	19	15	100	4	f	2	2026-03-24 17:32:25.989444	2026-03-26 06:22:25.200804	USD	\N	\N	\N	\N	0
33	RP-468158-383	FRENOS MOTO BERA RUNNER	Disco de freno perforado de alta resistencia con centro rojo para ventilación óptima y estilo deportivo.	4	20.00	30.00	20	5	100	1	t	2	2026-04-28 14:46:12.592747	2026-04-28 14:46:12.592747	USD	12000.00	30.00	8000.00	20.00	0
34	VH-911581-677	CARGUERO 200CC	Vehículo de carga de tres ruedas con cabina y cajón resistente, ideal para transporte logístico eficiente.	3	2000.00	3500.00	30	5	100	1	t	1	2026-04-28 14:54:14.002963	2026-04-28 14:54:14.002963	USD	1400000.00	3500.00	800000.00	2000.00	0
35	VH-957545-975	MOTO BERA R1 GBR	Motocicleta deportiva de alto rendimiento con diseño aerodinámico, frenos de disco y suspensión premium.	5	4000.00	6000.00	30	5	100	1	t	1	2026-04-28 15:45:05.915709	2026-04-28 15:56:56.705911	USD	2400000.00	6000.00	1600000.00	4000.00	0
36	VH-358207-449	MOTO BERA MORINI XCAPE	Motocicleta Adventure de 649cc con frenos Brembo y diseño ergonómico para todo terreno.\nPotencia, confort y tecnología italiana ideal para rutas largas y aventuras fuera de ruta.	5	5000.00	7000.00	40	5	100	1	t	1	2026-04-28 15:56:24.354402	2026-04-28 16:03:12.939822	USD	2800000.00	7000.00	2000000.00	5000.00	0
37	VH-196180-696	MOTO HONDA CBR	Motocicleta deportiva ágil y eficiente, perfecta para el uso diario y carretera.\r\nCalidad Honda con diseño aerodinámico y excelente rendimiento de combustible.	3	2000.00	3000.00	30	5	100	1	t	1	2026-04-28 16:05:19.051165	2026-04-28 16:05:19.051165	USD	1200000.00	3000.00	800000.00	2000.00	0
38	VH-536259-448	MOTO MORINI STR	Motocicleta scrambler de 650cc con diseño retro-moderno, versátil para asfalto y tierra.\r\nEstética impecable con componentes de alta gama para una conducción ágil y sofisticada.	5	3000.00	5500.00	30	5	100	1	t	1	2026-04-28 16:10:59.32851	2026-04-28 16:10:59.32851	USD	2200000.00	5500.00	1200000.00	3000.00	0
39	AC-018656-778	GUANTES PARA MOTO	Guantes reforzados con protección de nudillos y palma antideslizante para máxima seguridad.\r\nDiseño transpirable y ergonómico que garantiza confort y un agarre superior al conducir.	1	15.00	20.00	50	5	100	1	t	3	2026-04-28 17:10:33.412373	2026-04-28 17:10:33.412373	USD	8000.00	20.00	6000.00	15.00	0
40	AC-317295-500	GUANTES PARA MOTO ALPINESTAR	Guantes deportivos premium con protección de nudillos en carbono y malla ultra transpirable.\r\nDiseño de cuero resistente y ergonómico, optimizado para máxima seguridad y flujo de aire.	1	20.00	30.00	50	5	100	1	t	3	2026-04-28 17:16:04.46817	2026-04-28 17:16:04.46817	USD	12000.00	30.00	8000.00	20.00	0
41	AC-616205-482	GUANTES DE MOTO CON PROTECCION	Guantes de moto azules reforzados con protección rígida en nudillos y dedos.\r\nTela transpirable con cierre de velcro ajustable para mayor confort y seguridad.	1	25.00	35.00	50	5	100	1	t	3	2026-04-28 17:22:47.491098	2026-04-28 17:22:47.491098	USD	14000.00	35.00	10000.00	25.00	0
42	AC-291148-697	GUANTES PARA MOTO CON PROTECCION	Guantes de moto con protección rígida en nudillos y dedos, y palma antideslizante.\r\nIdeales para una conducción segura, con materiales transpirables y ajuste personalizable.	1	20.00	30.00	50	5	100	1	t	3	2026-04-28 17:35:36.540123	2026-04-28 17:35:36.540123	USD	12000.00	30.00	8000.00	20.00	0
43	AC-864394-087	GUANTES DE CUERO CON PROTECCION	Guantes de cuero de alta resistencia con protecciones rígidas en nudillos y dedos.\r\nDiseño ergonómico y duradero que ofrece máxima seguridad y un agarre firme al conducir.	1	30.00	40.00	50	5	100	1	t	3	2026-04-28 17:39:14.719324	2026-04-28 17:39:14.719324	USD	16000.00	40.00	12000.00	30.00	0
44	VH-638534-942	MOTO BERA SCOOOTER BWS	Scooter urbano deportivo, ágil y eficiente, diseñado para una conducción cómoda en la ciudad.	6	1000.00	1700.00	50	5	100	1	t	1	2026-04-28 17:52:33.336698	2026-04-28 17:54:02.551636	USD	680000.00	1700.00	400000.00	1000.00	0
45	VH-005429-904	MOTO BERA ADVENTURE	Scooter tipo adventure con diseño reforzado, defensas de protección y frenos de disco de alto rendimiento.\r\nIdeal para quienes buscan un vehículo urbano resistente, versátil y con estilo deportivo.	6	1000.00	1500.00	50	5	100	1	t	1	2026-04-28 18:00:23.060304	2026-04-28 18:00:23.060304	USD	600000.00	1500.00	400000.00	1000.00	0
46	VH-358636-883	MOTO BERA  X1	Motocicleta semiautomática de diseño deportivo y ligero, ideal para traslados urbanos eficientes.\r\nCuenta con freno de disco delantero y rines de aleación para un manejo seguro y moderno.	6	1200.00	2000.00	50	5	100	1	t	1	2026-04-28 18:05:15.877288	2026-04-28 18:05:15.877288	USD	800000.00	2000.00	480000.00	1200.00	0
47	VH-589427-731	MOTO BERA TEZO	Scooter eléctrico Bera Tezo 6G: Potencia de 3000W, diseño ergonómico y cero emisiones.\r\nLa solución urbana perfecta para un transporte silencioso, eficiente y moderno.	6	1100.00	1900.00	50	5	100	1	t	1	2026-04-28 18:08:04.555206	2026-04-28 18:08:04.555206	USD	760000.00	1900.00	440000.00	1100.00	0
18	AC-637057-943	CASCO BERA THUNDER	Diseño aerodinámico, máxima resistencia y ventilación eficiente.\r\nProtección superior con estilo deportivo para una conducción segura y cómoda.	1	30.00	50.00	31	5	100	1	t	3	2026-04-28 02:04:14.136267	2026-04-28 18:18:49.385066	USD	20000.00	50.00	12000.00	30.00	0
\.


--
-- Data for Name: promociones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.promociones (id, nombre, descripcion, tipo_promocion, valor, fecha_inicio, fecha_fin, estado, imagen_url, tipo_imagen, imagen_banco_key, color_personalizado, created_at, updated_at) FROM stdin;
2	oferta de motos	xxx	PORCENTAJE	10.00	2026-03-17	2026-04-16	f	http://localhost/inversiones-rojas/public/img/promotions/promo_1773769734_1c59d90f654e.jpg	manual	\N	#1F9166	2026-03-17 17:48:54.820498	2026-03-26 10:49:38.237869
1	oferta		PORCENTAJE	10.00	2026-03-17	2026-04-16	f	http://localhost/inversiones-rojas/public/img/promotions/promo_1773769084_b683bcfde685.png	manual	\N	#1F9166	2026-03-17 17:38:04.988109	2026-03-26 10:49:41.934825
6	oferta de verano	xxxx	PORCENTAJE	15.00	2026-03-26	2026-04-25	t	http://localhost/inversiones-rojas/public/img/promotions/promo_1774547724_5abe65f014bc.png	manual	\N	#1F9166	2026-03-26 17:55:24.858014	2026-03-26 17:55:24.858014
5	oferta de motocicletas	este es un ejemplo de promociones	PORCENTAJE	20.00	2026-03-18	2026-04-17	t	http://localhost/inversiones-rojas/public/img/promotions/promo_1773811754_4848b030643f.jpg	manual	\N	#1F9166	2026-03-18 05:29:14.651444	2026-03-28 21:53:52.530803
3	oferta especial	esta es una oferta especial aprovecha	PORCENTAJE	15.00	2026-03-19	2026-04-18	f	http://localhost/inversiones-rojas/public/img/promotions/promo_1773811355_4074233ab8b9.jpg	manual	\N	#1F9166	2026-03-18 05:22:35.681995	2026-03-29 00:48:50.429615
4	oferta de ejemplo	esta es una oferta de ejemplo	PORCENTAJE	15.00	2026-03-18	2026-04-17	f	http://localhost/inversiones-rojas/public/img/promotions/promo_1773811585_118f24d523e1.png	manual	\N	#1F9166	2026-03-18 05:26:25.588267	2026-04-01 00:02:24.570211
7	oferta nueva		PORCENTAJE	15.00	2026-04-18	2026-05-18	t	http://localhost/inversiones-rojas/public/img/promotions/promo_1776482460_66f2faec6f86.jpg	manual	\N	#1F9166	2026-04-18 03:09:00.614027	2026-04-18 03:21:00.381773
8	Kenderson		DESCUENTO	15.00	2026-05-21	2026-05-28	t	\N	\N	\N	#1F9166	2026-04-21 22:27:49.088726	2026-04-21 22:27:49.088726
\.


--
-- Data for Name: proveedores; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.proveedores (id, razon_social, rif, persona_contacto, telefono_principal, telefono_alternativo, email, direccion, productos_suministrados, estado, created_at, updated_at) FROM stdin;
1	BERA MOTORCYCLES.C.A	J423452335	fermin perez	04124617132	04124617132	jovita45r@gmail.com	venezuela, estado Aragua, ciudad Maracay, municipio Libertador, palo negro Santa Ana. Urbanización jardines los tulipanes calle 3 casa 165	\N	t	2026-03-15 19:57:27.549924	2026-03-15 19:57:27.549924
2	Distribuidor kajasaki	12345678	fermin perez	0412304526	04124617132	jovita45r@gmail.com	venezuela, estado Aragua, ciudad Maracay, municipio Libertador, palo negro Santa Ana. Urbanización jardines los tulipanes calle 3 casa 165	\N	t	2026-03-16 00:34:29.864439	2026-03-16 00:34:29.864439
3	Distribuidora Toro	J-123456789	fermin perez	0412-1234567	04124617132	jovita45r@gmail.com	venezuela, estado Aragua, ciudad Maracay, municipio Libertador, palo negro Santa Ana. Urbanización jardines los tulipanes calle 3 casa 165	\N	t	2026-03-16 01:07:09.880412	2026-03-16 01:07:09.880412
4	Toro	J-123456780	Pablo Hernadez	04121304522	04121304521	Pablo@gmail.com	Aragua	\N	t	2026-03-24 17:29:57.083033	2026-03-24 17:29:57.083033
5	Kawasaki Motors Inc.	J123456701	agente de kawasaki	0412-1345069	0412-1345060	customerservice@kawasaki.ca	Canadian Kawasaki Motors Inc.\r\n101 Thermos Road\r\nToronto, ON M1L 4W8	\N	t	2026-04-28 01:40:59.258043	2026-04-28 01:40:59.258043
6	Yamaha Motor Corporation	J692789300	agente de venta	0412-1234569	0212-3849203	jovita45r@gmail.com	venezuela, estado Aragua, ciudad Maracay, municipio Libertador, palo negro Santa Ana. Urbanización jardines los tulipanes calle 3 casa 165	\N	t	2026-04-28 02:42:55.957676	2026-04-28 02:42:55.957676
\.


--
-- Data for Name: repuestos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.repuestos (id, producto_id, categoria_tecnica, marca_compatible, modelo_compatible, anio_compatible, created_at) FROM stdin;
1	2	Motor	bera	Br200	2020	2026-03-15 21:17:38.688799
2	4	frenos	bera	Br200	2020	2026-03-15 21:31:03.410675
3	6	frenos Especiales	bera	Sbr 200	2020	2026-03-24 17:32:25.989444
4	7	xxx	bera	br200	2020	2026-03-26 13:53:30.305694
5	12	MOTOR	BERA	150CC	2022	2026-04-27 01:06:23.348943
6	27	MOTOR	BERA	200	2024	2026-04-28 14:20:37.717397
7	28	MOTOR	BERA	250	2025	2026-04-28 14:24:24.763983
8	29	FRENOS	BERA	BERA MILAN	2020	2026-04-28 14:33:05.339875
9	30	FRENOS	BERA	BERA,HONDA ,JAGUAR	2022	2026-04-28 14:36:56.880397
10	31	FRENOS	BERA	BERA	2024	2026-04-28 14:39:18.628534
11	32	FRENOS	BERA	SBR	2023	2026-04-28 14:43:09.812249
12	33	FRENOS	BERA	BERA	2020	2026-04-28 14:46:12.592747
\.


--
-- Data for Name: reservas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reservas (id, codigo_reserva, cliente_id, producto_id, cantidad, fecha_reserva, fecha_limite, estado_reserva, observaciones, created_at, updated_at, monto_adelanto, fecha_cuota, monto_restante, estado_pago, referencia_pago, metodo_pago, fecha_pago, comprobante_url, ip_address, user_agent, metodo_pago_resto, referencia_pago_resto, comprobante_url_resto, monto_pagado_resto, fecha_pago_resto, subtotal, iva, monto_total) FROM stdin;
25	RES-20260420-862	1	8	1	2026-04-20	2026-04-27	COMPLETADA	\N	2026-04-20 15:51:43.170113	2026-04-20 16:29:28.76219	11.60	2026-04-27	34.80	PENDIENTE	0745	pago_movil	\N	/public/uploads/reservas/reserva_20260420175143_5e33a52502430d88.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
1	RES-20260316-248	1	3	1	2026-03-16	2026-03-23	COMPLETADA	Tel: 04127550246 | xxx	2026-03-16 11:05:55.678515	2026-03-17 00:57:32.312688	0.00	\N	0.00	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	\N	\N	0.00	\N	0.00	0.00	0.00
3	RES-20260317-546	1	3	1	2026-03-17	2026-03-24	COMPLETADA	Tel: 04127550246	2026-03-17 01:50:20.113852	2026-03-17 02:01:13.861599	0.00	\N	0.00	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	\N	\N	0.00	\N	0.00	0.00	0.00
26	RES-20260420-075	1	9	1	2026-04-20	2026-04-27	COMPLETADA	\N	2026-04-20 17:43:12.657952	2026-04-20 17:45:37.810049	580.00	2026-04-27	1740.00	PENDIENTE	xxx	pago_movil	\N	/public/uploads/reservas/reserva_20260420194312_9b208eef36dd12b3.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
27	RES-20260420-381	1	9	1	2026-04-20	2026-04-27	COMPLETADA	\N	2026-04-20 17:46:20.62419	2026-04-20 17:46:55.735345	580.00	2026-04-27	1740.00	PENDIENTE	1112	pago_movil	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
21	RES-20260420-745	1	1	1	2026-04-20	2026-04-27	COMPLETADA	Tel: 0412-7550246	2026-04-20 15:24:00.55397	2026-04-20 17:47:19.410836	9.86	2026-04-27	29.58	PENDIENTE	1112		\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
20	RES-20260420-924	1	9	1	2026-04-20	2026-04-27	CANCELADA	Tel: 04127550246	2026-04-20 13:18:37.621318	2026-04-20 17:47:48.574219	580.00	2026-04-27	1740.00	PENDIENTE	0123		\N	/public/uploads/reservas/reserva_20260420151837_77ff4bd0_Screenshot_20260315_123128_Instagram.jpg.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
19	RES-20260420-025	1	9	1	2026-04-20	2026-04-27	CANCELADA	Tel: 04127550246	2026-04-20 05:02:31.261689	2026-04-20 17:47:52.942598	580.00	2026-04-27	1740.00	PENDIENTE	12312		\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
18	RES-20260420-217	1	9	2	2026-04-20	2026-04-27	CANCELADA	Tel: 04127550246 | xxx	2026-04-19 23:49:15.383027	2026-04-20 17:47:57.27477	1160.00	2026-04-26	3480.00	PENDIENTE	AP20260414DF46C4	\N	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
17	RES-20260420-875	1	9	1	2026-04-20	2026-04-27	CANCELADA	Tel: 04127550246 | xxx	2026-04-19 23:30:03.822555	2026-04-20 17:48:00.186662	580.00	2026-04-25	1740.00	PENDIENTE	AP202604191867A3	\N	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
16	RES-20260419-033	1	9	1	2026-04-19	2026-04-26	CANCELADA	Tel: 04127550246	2026-04-19 20:01:03.568792	2026-04-20 17:48:04.124739	580.00	2026-04-25	1740.00	PENDIENTE	AP202604326631D7	\N	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
30	RES-20260420-354	1	9	1	2026-04-20	2026-04-27	COMPLETADA	\N	2026-04-20 18:56:04.628697	2026-04-20 18:56:23.00971	580.00	2026-04-27	1740.00	PENDIENTE		Binance	\N	/public/uploads/reservas/reserva_20260420205604_903edd74a9f4fc2b.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
14	RES-20260419-731	1	9	1	2026-04-19	2026-04-26	CANCELADA	Tel: 04127550246 | xxx	2026-04-19 17:30:03.596781	2026-04-20 17:48:41.073393	580.00	2026-04-25	1740.00	PENDIENTE	AP2026041FD34F77	\N	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
13	RES-20260419-411	1	9	1	2026-04-19	2026-04-26	CANCELADA	Tel: 04127550246	2026-04-19 17:29:31.538735	2026-04-20 17:48:44.55152	580.00	2026-04-25	1740.00	PENDIENTE	AP2026045EEB38DD	\N	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
12	RES-20260419-415	1	9	1	2026-04-19	2026-04-26	CANCELADA	Tel: 04127550246 | xxx	2026-04-19 17:04:38.152983	2026-04-20 17:48:49.338101	580.00	2026-04-25	1740.00	PENDIENTE	AP20260490C78AB5	\N	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
11	RES-20260419-760	1	1	1	2026-04-19	2026-04-26	CANCELADA	Tel: 0412-7550246 | xxx	2026-04-19 15:18:40.107021	2026-04-20 17:48:52.438966	12.00	2026-04-26	34.40	PENDIENTE	AP202604C2F1CB4D	\N	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
10	RES-20260419-123	1	1	1	2026-04-19	2026-04-26	CANCELADA	Tel: 0412-7550246 | xxx	2026-04-19 15:15:56.051396	2026-04-20 17:48:55.460472	12.00	2026-04-26	34.40	PENDIENTE	AP202604E9E6D260	\N	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
6	RES-20260318-962	1	5	1	2026-03-18	2026-03-25	COMPLETADA	Tel: 04127550246	2026-03-18 05:17:09.256962	2026-03-18 05:17:45.011829	0.00	\N	0.00	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	\N	\N	0.00	\N	0.00	0.00	0.00
7	RES-20260326-214	1	3	1	2026-03-26	2026-04-02	COMPLETADA	Tel: 04127550246 | xxx	2026-03-26 10:43:08.242909	2026-03-26 10:47:06.805518	0.00	\N	0.00	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	\N	\N	0.00	\N	0.00	0.00	0.00
8	RES-20260415-745	1	9	1	2026-04-15	2026-04-22	COMPLETADA	Tel: 04127550246 | xxx	2026-04-15 02:28:36.676127	2026-04-15 02:29:29.109634	0.00	\N	0.00	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	\N	\N	0.00	\N	0.00	0.00	0.00
5	RES-20260317-509	2	1	1	2026-03-17	2026-03-18	COMPLETADA	xxx	2026-03-17 03:54:40.188815	2026-04-15 02:29:34.406774	0.00	\N	0.00	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	\N	\N	0.00	\N	0.00	0.00	0.00
4	RES-20260317-908	2	1	1	2026-03-17	2026-03-19	COMPLETADA	xxx	2026-03-17 02:46:39.437587	2026-04-15 02:29:38.875981	0.00	\N	0.00	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	\N	\N	0.00	\N	0.00	0.00	0.00
2	RES-20260317-883	1	3	1	2026-03-17	2026-03-27	COMPLETADA	Tel: 04127550246	2026-03-17 00:59:08.894433	2026-04-15 02:29:42.364727	0.00	\N	0.00	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	\N	\N	0.00	\N	0.00	0.00	0.00
9	RES-20260419-597	1	9	1	2026-04-19	2026-04-26	CANCELADA	Tel: 04127550246 | xxx	2026-04-19 02:25:26.880499	2026-04-20 17:48:58.272393	0.00	\N	0.00	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	\N	\N	0.00	\N	0.00	0.00	0.00
28	RES-20260420-071	1	9	1	2026-04-20	2026-04-27	COMPLETADA	\N	2026-04-20 18:32:53.01452	2026-04-20 18:44:02.959159	580.00	2026-04-27	1740.00	PENDIENTE			\N	/public/uploads/reservas/reserva_20260420203253_0a2e8c3d7f2a9aca.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
29	RES-20260420-873	1	1	1	2026-04-20	2026-04-27	COMPLETADA	\N	2026-04-20 18:45:38.789932	2026-04-20 18:46:06.431323	9.86	2026-04-27	29.58	PENDIENTE			\N	/public/uploads/reservas/reserva_20260420204538_0b0bc347ce506f64.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
31	RES-20260420-262	1	5	1	2026-04-20	2026-04-27	COMPLETADA	\N	2026-04-20 19:11:20.433349	2026-04-20 19:12:11.222188	5.80	2026-04-27	17.40	PENDIENTE	123456	pago_movil	\N	/public/uploads/reservas/reserva_20260420211120_90314041bd5999d5.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	0.00	0.00	0.00
15	RES-20260419-286	1	9	1	2026-04-19	2026-04-26	COMPLETADA	Tel: 04127550246 | xxx	2026-04-19 17:30:20.535594	2026-04-21 15:39:53.910959	580.00	2026-04-26	1740.00	PENDIENTE	AP2026041ADEEEBB	\N	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	1740.00	2026-04-21 15:39:53.910959	0.00	0.00	0.00
32	RES-20260420-865	1	4	1	2026-04-20	2026-04-27	COMPLETADA	xxx	2026-04-20 19:30:31.630939	2026-04-20 19:31:08.389815	5.80	2026-04-27	17.38	PENDIENTE	0000	pago_movil	\N	/public/uploads/reservas/reserva_20260420213031_85e09c096d1ff46e.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	17.38	2026-04-20 19:31:08.389815	0.00	0.00	0.00
38	RES-20260421-855	1	5	1	2026-04-21	2026-04-27	CANCELADA	\N	2026-04-20 22:17:25.145957	2026-04-21 00:35:17.49964	5.80	2026-04-27	17.40	PENDIENTE	1234	pago_movil	\N	/public/uploads/reservas/reserva_20260421001725_c928be3f8d2afc7d.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Efectivo		\N	17.40	2026-04-20 22:20:14.413711	20.00	3.20	23.20
33	RES-20260420-788	1	1	1	2026-04-20	2026-04-27	COMPLETADA	\N	2026-04-20 19:48:49.716944	2026-04-20 19:49:58.180961	9.86	2026-04-27	29.58	PENDIENTE	1234567	pago_movil	\N	/public/uploads/reservas/reserva_20260420214849_07940d1f87746a6a.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Pago Movil	1234	/public/uploads/reservas_comprobantes/comprobante_69e683662ecb07.41197081_WhatsApp_Image_2026-04-20_at_11_27_45_AM.jpeg	29.58	2026-04-20 19:49:58.180961	34.00	5.44	39.44
41	RES-20260421-057	1	9	1	2026-04-21	2026-04-28	COMPLETADA	\N	2026-04-21 14:30:29.687582	2026-04-21 15:16:14.518132	580.00	2026-04-28	1740.00	PENDIENTE	AP2026048B2383D1	pago_movil	\N	/public/uploads/reservas/reserva_20260421163029_a27e1dc89e98b18b.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	1740.00	2026-04-21 15:16:14.518132	2000.00	320.00	2320.00
37	RES-20260420-951	1	9	1	2026-04-20	2026-04-27	CANCELADA	\N	2026-04-20 21:44:37.638375	2026-04-21 00:48:14.289142	580.00	2026-04-27	1740.00	PENDIENTE	7283	pago_movil	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	1740.00	2026-04-20 21:46:44.924613	2000.00	320.00	2320.00
34	RES-20260420-748	1	1	1	2026-04-20	2026-04-27	CANCELADA	\N	2026-04-20 19:55:24.174659	2026-04-21 01:02:55.902914	10.00	2026-04-27	29.44	PENDIENTE	1234567	pago_movil	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	29.44	2026-04-20 20:01:40.796543	34.00	5.44	39.44
46	RES-20260421-078	1	9	1	2026-04-21	2026-04-28	COMPLETADA	\N	2026-04-21 15:24:58.631419	2026-04-21 15:25:51.809637	580.00	2026-04-28	1740.00	PENDIENTE	135	pago_movil	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Pago Movil	1234	\N	1740.00	2026-04-21 15:25:51.809637	2000.00	320.00	2320.00
39	RES-20260421-954	8	1	1	2026-04-21	2026-04-27	CANCELADA	\N	2026-04-20 22:30:33.989839	2026-04-20 23:44:02.01273	9.86	2026-04-27	29.58	PENDIENTE	999	pago_movil	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	34.00	5.44	39.44
45	RES-20260421-934	1	8	1	2026-04-21	2026-04-28	COMPLETADA	\N	2026-04-21 15:02:39.997743	2026-04-21 15:04:33.358236	11.60	2026-04-28	34.80	PENDIENTE	0000	pago_movil	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	34.80	2026-04-21 15:04:33.358236	40.00	6.40	46.40
44	RES-20260421-416	1	9	1	2026-04-21	2026-04-28	CANCELADA	\N	2026-04-21 14:59:24.849449	2026-04-21 15:05:04.970633	580.00	2026-04-28	1740.00	PENDIENTE	123	pago_movil	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	2000.00	320.00	2320.00
43	RES-20260421-835	1	9	1	2026-04-21	2026-04-28	COMPLETADA	\N	2026-04-21 14:54:21.775057	2026-04-21 15:05:40.643244	580.00	2026-04-28	1740.00	PENDIENTE	1234	pago_movil	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	1740.00	2026-04-21 15:05:40.643244	2000.00	320.00	2320.00
42	RES-20260421-164	1	9	1	2026-04-21	2026-04-28	CANCELADA	\N	2026-04-21 14:48:24.258153	2026-04-21 15:15:56.384282	580.00	2026-04-28	1740.00	PENDIENTE	AP202604443F1120	pago_movil	\N	/public/uploads/reservas/reserva_20260421164824_8c3bf7aa86805638.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	2000.00	320.00	2320.00
36	RES-20260420-227	8	1	1	2026-04-20	2026-04-27	COMPLETADA	\N	2026-04-20 21:37:39.958561	2026-04-21 15:28:11.702716	9.86	2026-04-27	29.58	PENDIENTE	1234213	pago_movil	\N	/public/uploads/reservas/reserva_20260420233739_95cfeb1a0162a4cd.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	29.58	2026-04-21 15:28:11.702716	34.00	5.44	39.44
40	RES-20260421-846	1	9	1	2026-04-21	2026-04-28	COMPLETADA	\N	2026-04-21 14:12:53.230106	2026-04-21 15:32:25.000439	580.00	2026-04-28	1740.00	PENDIENTE	AP202604CDE0854D	pago_movil	\N	/public/uploads/reservas/reserva_20260421161253_bd0e8ec4b6d46462.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	1740.00	2026-04-21 15:32:25.000439	2000.00	320.00	2320.00
35	RES-20260420-903	8	1	1	2026-04-20	2026-04-27	CANCELADA	\N	2026-04-20 20:44:53.68226	2026-04-21 15:39:36.603526	9.86	2026-04-27	29.58	PENDIENTE	12340000	pago_movil	\N	/public/uploads/reservas/reserva_20260420224453_f68c7c60476ad52d.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	34.00	5.44	39.44
47	RES-20260421-668	1	8	1	2026-04-21	2026-04-28	COMPLETADA	\N	2026-04-21 16:07:59.11103	2026-04-21 16:09:01.433011	11.60	2026-04-28	34.80	PENDIENTE	4444	pago_movil	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	34.80	2026-04-21 16:09:01.433011	40.00	6.40	46.40
48	RES-20260421-661	8	9	4	2026-04-21	2026-04-28	COMPLETADA	\N	2026-04-21 17:33:21.929728	2026-04-24 18:56:50.705035	2320.00	2026-04-28	6960.00	PENDIENTE	1111	pago_movil	\N	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	6960.00	2026-04-24 18:56:50.705035	8000.00	1280.00	9280.00
49	RES-20260424-195	1	9	1	2026-04-24	2026-05-01	COMPLETADA	\N	2026-04-24 18:53:40.171561	2026-04-24 18:57:10.374055	580.00	2026-05-01	1740.00	PENDIENTE	1111	pago_movil	\N	/public/uploads/reservas/reserva_20260424205340_bb7fd188aa173693.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	Binance		\N	1740.00	2026-04-24 18:57:10.374055	2000.00	320.00	2320.00
50	RES-20260425-351	1	11	1	2026-04-25	2026-04-30	PENDIENTE	\N	2026-04-25 03:49:06.873374	2026-04-25 03:49:06.873374	522.00	2026-04-30	1566.00	PENDIENTE	AP2026044250FE8F	pago_movil	\N	/public/uploads/reservas/reserva_20260425054906_63b925a25a08e6dc.jpeg	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0	\N	\N	\N	0.00	\N	1800.00	288.00	2088.00
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
-- Data for Name: tasas_cambio; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tasas_cambio (id, tasa, moneda_origen, moneda_destino, fecha_vigencia, usuario_id, observaciones, created_at) FROM stdin;
1	35.5000	USD	VES	2026-03-29	\N	Tasa inicial	2026-03-29 03:08:21.128379
2	400.0000	USD	VES	2026-03-29	\N		2026-03-29 04:21:28.792968
3	400.0000	USD	VES	2026-04-18	\N		2026-04-18 03:05:47.049125
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
1	admin	2016rojasinversiones@gmail.com	$2y$10$HF7xC5J29.6bff/tTxEmEeUdqzMBl98IvXmIglzPqtvWCKDGHXuru	Administrador	1	\N	t	2026-04-28 18:35:19.08851	0	\N	2026-03-15 18:51:04.166201	2026-03-26 16:01:58.898283
2	Raizza	jovita45r@gmail.com	$2y$10$GevUcOwR06Be6O/Vl9caHO/3sTUx9KDpa/LYCxY14oqx6C6zDkmiG	Raizza Jovita marrero	3	2	t	2026-04-25 03:48:11.950308	0	\N	2026-03-16 03:19:26.111203	2026-03-26 15:58:49.541496
6	juan	juan@gmail.com	$2y$10$beI0zQf0aZ3A4EA2LtfJW.9415zxBjY8BNF/VH.fO.ppgPUAqP8EC	Juan Villegas	6	\N	t	2026-03-24 02:59:42.825264	3	2026-03-26 17:07:35	2026-03-24 02:58:20.195565	2026-03-26 15:51:34.079865
8	gifrank0000	gifrank@gmail.com	$2y$10$pLs0pDsnWHu2KYsYW.NiD.9yU1flRpfr2GCivLWQjweOje9rYWzFu	Gifrank	3	6	t	2026-03-24 03:14:12.612147	0	\N	2026-03-24 03:13:48.082067	2026-03-24 03:13:48.082067
3	Cesar1234	cesar@gmail.com	$2y$10$mdVNNy0OfhhA7c4eadEhZO3F7zR4/Jo.9oj51CJXA8sQTLMp/mZDq	Cesar	2	\N	t	2026-03-26 05:56:39.24191	3	2026-04-21 15:24:45	2026-03-18 12:32:27.434516	2026-03-24 20:48:35.963993
9	usuario2912	user@gmail.com	$2y$10$PAES.cslmGCZGTbBK1KRku4QcrnwDg1yNQLj6..R0FEfhvWIRil5m	Usuario de prueba123	3	7	t	2026-03-24 15:55:03.682055	0	\N	2026-03-24 15:54:42.300117	2026-03-24 17:02:41.677935
12	operador	operador@gmail.com	$2y$10$AHlVUkkvtr3thF345U5p7eQjJxy9v1itLG3WN1sJCgmIjLLAI2eOq	operador	6	\N	t	\N	0	\N	2026-03-26 15:55:17.678196	2026-03-26 15:55:17.678196
10	usuario	user123@gmail.com	$2y$10$tYlYWpdSLU74HD/R2xZd5OBN4foP.9WaZBjCcCInM3zp4jGMdpRpe	Usuario de prueba123	3	7	t	\N	0	\N	2026-03-24 17:03:43.189927	2026-03-24 17:03:43.189927
5	luis	luis@gmail.com	$2y$10$sa8n7CwH1B1AiX/rN8Sw7.b.SjZXsxUjtnb4VyBNgc5rtQBo8HrBO	luis rondon	5	\N	t	2026-03-24 20:04:13.087895	2	\N	2026-03-22 14:25:18.027152	2026-03-22 14:25:18.027152
4	dylan2912	dylan@gmail.com	$2y$10$h2vmQE/B2oALrhjheXlWaeX7RfguxOK0qgrK.0iaAg3DfktdBkz2S	dylan tablante	6	\N	t	2026-03-24 20:03:38.645328	2	\N	2026-03-22 14:24:15.78738	2026-03-22 14:24:15.78738
14	andres14	andred@gmail.com	$2y$10$IpPaSyBM4rdlEaKJ9i1gvuXA1ovUwDy.TyuZst.EKbEWf/phfWVAi	andres sanchez	3	10	f	\N	0	\N	2026-03-26 17:22:50.691548	2026-04-21 13:11:56.258952
16	reafel123	rafael@gmail.com	$2y$10$AD.yeR1LzfX6YgP.aYLczO6h0o0FWkS4zDH4eqgMtJgvI/.PrEcH.	rafael	3	12	t	2026-04-28 17:56:01.991351	0	\N	2026-04-28 17:53:56.46195	2026-04-28 17:53:56.46195
17	rafael123	rafa@gmail.com	$2y$10$5N03mvFUUUlyTHQ.0yk00.Izx.OdMf15uJ.Wzqe5y28Rtvo8oOfre	rafael	3	13	t	\N	0	\N	2026-04-28 18:04:47.771839	2026-04-28 18:04:47.771839
7	gi2912frank	gifrank0000@gmail.com	$2y$10$PJXSdLWvIgfRNaORUn3Ye.DrkZFAqHkDuK0Pj8cR56oF8ieCLo.5a	Gifrank	5	5	t	2026-03-24 03:01:50.020008	0	\N	2026-03-24 03:01:29.077972	2026-03-24 03:01:29.077972
13	Gerente	Gerente@gmail.com	$2y$10$qT3YS1t5c1m9NjOMdaAueOn3SbqX/ScO/Mejv3jYNQIjROXqjCbkG	Gerente	5	\N	t	2026-04-21 13:12:41.142892	0	\N	2026-03-26 15:56:06.060002	2026-04-21 13:12:10.980041
11	vendedor	Vendedor@gmail.com	$2y$10$PC7rbo0kSYwOBJvlE0Pp3OJ0oF/2U6EDI8a8emlbBC3Dp5jxALJ32	Vendedor	2	\N	t	2026-04-24 20:40:17.581498	0	\N	2026-03-26 15:54:36.962491	2026-03-26 15:54:36.962491
15	gifrank	gifrank2912@mail.com	$2y$10$cKIdLeB3lMNh6fGERc3mGeXvFe3l/GySvfTBHlxIB4yUpeov9jb/i	Gifrank	3	11	t	2026-03-28 18:53:53.957614	0	\N	2026-03-28 18:43:23.971097	2026-03-28 18:43:23.971097
\.


--
-- Data for Name: vehiculos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.vehiculos (id, producto_id, marca, modelo, anio, cilindrada, color, kilometraje, tipo_vehiculo, created_at) FROM stdin;
1	3	Bera	SBR	2020	150cc	Rojo,Morada,Azul	120	Moto	2026-03-15 21:24:05.570024
2	5	bera	SBR	2021	200cc	Negro	120	Moto	2026-03-18 05:09:50.531873
6	11	BERA	BR 200	2025	200	AZUL	0	Moto	2026-04-25 03:06:38.646929
3	8	kawasaki	1000SX	2024	1043cc 	Gris / Negro	295	Moto	2026-04-05 22:39:34.495429
7	23	Yamaha	YZF-R3	2024	321cc	Azul/Blanco	21	Moto	2026-04-28 02:56:26.245159
4	9	bera	BR200	2020	200cc	rojo	120	Moto	2026-04-06 13:20:19.665111
8	24	Bera	BRZ	2024	250cc	Naranja	140	Moto	2026-04-28 03:47:05.147572
9	25	Bera	Cobra 150cc	2021	150cc	Rojo	90	Moto	2026-04-28 03:53:23.722132
10	26	bera	GBR	2025	 200cc	Rojo/Negro	120	Moto	2026-04-28 14:01:28.582444
5	10	Bera	SBR	2020	150cc	morada	120	Moto	2026-04-25 02:48:00.203356
11	34	BERA	BERA	2024	200CC	NEGRO	0	Moto	2026-04-28 14:54:14.002963
12	35	BERA	GBR	2025	600CC	AMARILLO	0	Moto	2026-04-28 15:45:05.915709
13	36	BERA	XCAPE	2026	650CC	ROJO	0	Moto	2026-04-28 15:56:24.354402
14	37	HONDA	CBR	2025	250CC	NEGRO	0	Moto	2026-04-28 16:05:19.051165
15	38	BERA	STR	2025	600CC	PLATEADA	0	Moto	2026-04-28 16:10:59.32851
16	44	BERA	BWS	2024	150CC	NEGRO	0	Moto	2026-04-28 17:52:33.336698
17	45	BERA	BWS	2025	200CC	BLANCO	0	Moto	2026-04-28 18:00:23.060304
18	46	BERA	X1	2025	150CC	BLANCO	0	Moto	2026-04-28 18:05:15.877288
19	47	BERA	TEZO	2023	150CC	AZUL	0	Moto	2026-04-28 18:08:04.555206
\.


--
-- Data for Name: ventas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.ventas (id, codigo_venta, cliente_id, usuario_id, metodo_pago_id, subtotal, iva, total, estado_venta, observaciones, created_at, moneda_pago, tasa_cambio, monto_bs, monto_usd) FROM stdin;
2	V-20260316002305-742	1	1	1	30.00	4.80	34.80	COMPLETADA		2026-03-15 23:23:05.389608	USD	\N	\N	\N
3	V-20260316002641-835	1	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-15 23:26:41.668385	USD	\N	\N	\N
4	V-20260316005235-187	1	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-15 23:52:35.489566	USD	\N	\N	\N
5	V-20260316005421-991	1	1	1	19.98	3.20	23.18	COMPLETADA		2026-03-15 23:54:21.224039	USD	\N	\N	\N
6	VEN-20260317-46452	1	1	5	30.00	4.80	34.80	COMPLETADA	Venta generada desde reserva: RES-20260316-248	2026-03-17 00:57:32.312688	USD	\N	\N	\N
7	VEN-20260317-46672	1	1	1	30.00	4.80	34.80	COMPLETADA	Venta generada desde reserva: RES-20260317-546 | Referencia: 1234	2026-03-17 02:01:13.861599	USD	\N	\N	\N
8	V-20260318053404-274	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:04.998108	USD	\N	\N	\N
9	V-20260318053428-750	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:28.663668	USD	\N	\N	\N
10	V-20260318053430-488	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:30.235147	USD	\N	\N	\N
11	V-20260318053430-346	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:30.37264	USD	\N	\N	\N
12	V-20260318053430-518	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:30.553596	USD	\N	\N	\N
13	V-20260318053430-634	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:30.664176	USD	\N	\N	\N
14	V-20260318053430-909	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-18 04:34:30.816474	USD	\N	\N	\N
15	V-20260318054423-388	1	1	1	19.98	3.20	23.18	COMPLETADA		2026-03-18 04:44:23.046914	USD	\N	\N	\N
16	V-20260318055031-877	1	1	1	19.98	3.20	23.18	COMPLETADA		2026-03-18 04:50:31.191365	USD	\N	\N	\N
17	V-20260318055256-665	3	1	1	60.00	9.60	69.60	COMPLETADA		2026-03-18 04:52:56.849787	USD	\N	\N	\N
18	V-20260318055605-407	3	1	1	90.00	14.40	104.40	COMPLETADA		2026-03-18 04:56:05.450469	USD	\N	\N	\N
19	VEN-20260318-88856	1	1	1	20.00	3.20	23.20	COMPLETADA	Venta generada desde reserva: RES-20260318-962 | Referencia: 1234	2026-03-18 05:17:45.011829	USD	\N	\N	\N
20	V-20260324024439-229	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-03-24 01:44:39.069417	USD	\N	\N	\N
21	VEN-20260326-75609	1	1	1	30.00	4.80	34.80	COMPLETADA	Venta generada desde reserva: RES-20260326-214 | Referencia: 1234	2026-03-26 10:47:06.805518	USD	\N	\N	\N
22	V-20260326172134-622	3	1	6	19.98	3.20	23.18	COMPLETADA		2026-03-26 16:21:34.902141	USD	\N	\N	\N
23	V-20260326183454-135	3	1	1	30.00	4.80	34.80	COMPLETADA		2026-03-26 17:34:54.46474	USD	\N	\N	\N
24	V-20260328204838-480	3	1	5	40.00	6.40	46.40	COMPLETADA		2026-03-28 19:48:38.6105	USD	\N	\N	\N
25	V-20260412020309-823	8	1	1	40.00	6.40	46.40	COMPLETADA		2026-04-12 00:03:09.191329	BS	400.0000	18560.00	46.40
26	VEN-20260415-09276	1	1	1	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260415-745 | Referencia: 3123214	2026-04-15 02:29:29.109634	USD	\N	\N	\N
27	VEN-20260415-32437	2	1	5	40.00	6.40	46.40	COMPLETADA	Venta generada desde reserva: RES-20260317-509	2026-04-15 02:29:34.406774	USD	\N	\N	\N
28	VEN-20260415-67335	2	1	5	40.00	6.40	46.40	COMPLETADA	Venta generada desde reserva: RES-20260317-908	2026-04-15 02:29:38.875981	USD	\N	\N	\N
29	VEN-20260415-25147	1	1	5	30.00	4.80	34.80	COMPLETADA	Venta generada desde reserva: RES-20260317-883	2026-04-15 02:29:42.364727	USD	\N	\N	\N
30	V-20260418211110-613	8	1	1	40.00	6.40	46.40	COMPLETADA		2026-04-18 19:11:10.005036	BS	400.0000	18560.00	46.40
31	VEN-20260420-89920	1	1	1	40.00	6.40	46.40	COMPLETADA	Venta generada desde reserva: RES-20260420-862 | Referencia: 1234 | Comprobantes: /public/uploads/reservas_comprobantes/comprobante_69e65468be8756.43596340_WhatsApp_Image_2026-04-20_at_11_27_45_AM.jpeg	2026-04-20 16:29:28.76219	USD	\N	\N	\N
32	VEN-20260420-88217	1	1	5	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260420-075 | Comprobantes: /public/uploads/reservas_comprobantes/comprobante_69e66641c836c5.89989199_WhatsApp_Image_2026-04-20_at_11_27_45_AM.jpeg	2026-04-20 17:45:37.810049	USD	\N	\N	\N
33	VEN-20260420-72021	1	1	5	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260420-381 | Comprobantes: /public/uploads/reservas_comprobantes/comprobante_69e6668fb46c91.77424365_WhatsApp_Image_2026-04-20_at_11_27_45_AM.jpeg	2026-04-20 17:46:55.735345	USD	\N	\N	\N
34	VEN-20260420-40694	1	1	1	40.00	6.40	46.40	COMPLETADA	Venta generada desde reserva: RES-20260420-745 | Referencia: 1234	2026-04-20 17:47:19.410836	USD	\N	\N	\N
35	VEN-20260420-94059	1	1	5	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260420-071 | Comprobantes: /public/uploads/reservas_comprobantes/comprobante_69e673f2ee0552.23189493_WhatsApp_Image_2026-04-20_at_11_27_45_AM.jpeg	2026-04-20 18:44:02.959159	USD	\N	\N	\N
36	VEN-20260420-75960	1	1	5	40.00	6.40	46.40	COMPLETADA	Venta generada desde reserva: RES-20260420-873	2026-04-20 18:46:06.431323	USD	\N	\N	\N
37	VEN-20260420-46989	1	1	5	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260420-354	2026-04-20 18:56:23.00971	USD	\N	\N	\N
38	VEN-20260420-63722	1	1	5	20.00	3.20	23.20	COMPLETADA	Venta generada desde reserva: RES-20260420-262	2026-04-20 19:12:11.222188	USD	\N	\N	\N
39	VEN-20260420-20219	1	1	5	19.98	3.20	23.18	COMPLETADA	Venta generada desde reserva: RES-20260420-865	2026-04-20 19:31:08.389815	USD	\N	\N	\N
40	VEN-20260420-81120	1	1	1	40.00	6.40	46.40	COMPLETADA	Venta generada desde reserva: RES-20260420-788 | Referencia: 1234 | Comprobantes: /public/uploads/reservas_comprobantes/comprobante_69e683662ecb07.41197081_WhatsApp_Image_2026-04-20_at_11_27_45_AM.jpeg	2026-04-20 19:49:58.180961	USD	\N	\N	\N
1	V-20260315223746-251	1	1	1	40.00	6.40	46.40	INHABILITADO		2026-03-15 21:37:46.289757	USD	\N	\N	\N
43	VEN-20260421-36720	1	1	3	20.00	3.20	23.20	COMPLETADA	Venta generada desde reserva: RES-20260421-855	2026-04-20 22:20:14.413711	USD	\N	\N	\N
42	VEN-20260420-65832	1	1	5	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260420-951	2026-04-20 21:46:44.924613	USD	\N	\N	\N
41	VEN-20260420-16980	1	1	5	40.00	6.40	46.40	COMPLETADA	Venta generada desde reserva: RES-20260420-748	2026-04-20 20:01:40.796543	USD	\N	\N	\N
44	V-20260421162122-669	3	1	1	40.00	6.40	46.40	COMPLETADA		2026-04-21 14:21:22.025676	BS	400.0000	18560.00	46.40
45	VEN-20260421-25899	1	1	5	40.00	6.40	46.40	COMPLETADA	Venta generada desde reserva: RES-20260421-934	2026-04-21 15:04:33.358236	USD	\N	\N	\N
46	VEN-20260421-54537	1	1	5	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260421-835	2026-04-21 15:05:40.643244	USD	\N	\N	\N
47	VEN-20260421-46913	1	1	5	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260421-057	2026-04-21 15:16:14.518132	USD	\N	\N	\N
48	VEN-20260421-42572	1	1	1	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260421-078 | Referencia: 1234	2026-04-21 15:25:51.809637	USD	\N	\N	\N
49	VEN-20260421-51802	8	1	5	40.00	6.40	46.40	COMPLETADA	Venta generada desde reserva: RES-20260420-227	2026-04-21 15:28:11.702716	USD	\N	\N	\N
50	VEN-20260421-21555	1	1	5	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260421-846	2026-04-21 15:32:25.000439	USD	\N	\N	\N
51	VEN-20260421-98417	1	1	5	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260419-286	2026-04-21 15:39:53.910959	USD	\N	\N	\N
52	VEN-20260421-20951	1	1	5	40.00	6.40	46.40	COMPLETADA	Venta generada desde reserva: RES-20260421-668	2026-04-21 16:09:01.433011	USD	\N	\N	\N
53	VEN-20260424-39398	8	1	5	8000.00	1280.00	9280.00	COMPLETADA	Venta generada desde reserva: RES-20260421-661	2026-04-24 18:56:50.705035	USD	\N	\N	\N
54	VEN-20260424-44786	1	1	5	2000.00	320.00	2320.00	COMPLETADA	Venta generada desde reserva: RES-20260424-195	2026-04-24 18:57:10.374055	USD	\N	\N	\N
\.


--
-- Name: accesorios_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.accesorios_id_seq', 16, true);


--
-- Name: alertas_stock_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.alertas_stock_id_seq', 1, false);


--
-- Name: bitacora_sistema_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bitacora_sistema_id_seq', 380, true);


--
-- Name: categorias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.categorias_id_seq', 6, true);


--
-- Name: clientes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.clientes_id_seq', 13, true);


--
-- Name: compras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.compras_id_seq', 16, true);


--
-- Name: detalle_compras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.detalle_compras_id_seq', 21, true);


--
-- Name: detalle_pedidos_online_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.detalle_pedidos_online_id_seq', 37, true);


--
-- Name: detalle_ventas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.detalle_ventas_id_seq', 54, true);


--
-- Name: devoluciones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.devoluciones_id_seq', 9, true);


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

SELECT pg_catalog.setval('public.metodos_pago_id_seq', 9, true);


--
-- Name: metodos_pago_reservas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.metodos_pago_reservas_id_seq', 1, true);


--
-- Name: movimientos_inventario_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.movimientos_inventario_id_seq', 100, true);


--
-- Name: notificaciones_vendedor_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notificaciones_vendedor_id_seq', 19, true);


--
-- Name: pedidos_online_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pedidos_online_id_seq', 36, true);


--
-- Name: producto_imagenes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.producto_imagenes_id_seq', 46, true);


--
-- Name: producto_promociones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.producto_promociones_id_seq', 10, true);


--
-- Name: producto_proveedor_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.producto_proveedor_id_seq', 48, true);


--
-- Name: productos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.productos_id_seq', 47, true);


--
-- Name: promociones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.promociones_id_seq', 8, true);


--
-- Name: proveedores_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.proveedores_id_seq', 6, true);


--
-- Name: repuestos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.repuestos_id_seq', 12, true);


--
-- Name: reservas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reservas_id_seq', 50, true);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_seq', 7, true);


--
-- Name: seguimiento_incidencias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.seguimiento_incidencias_id_seq', 2, true);


--
-- Name: tasas_cambio_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tasas_cambio_id_seq', 3, true);


--
-- Name: tipos_producto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tipos_producto_id_seq', 5, true);


--
-- Name: usuarios_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuarios_id_seq', 17, true);


--
-- Name: vehiculos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.vehiculos_id_seq', 19, true);


--
-- Name: ventas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.ventas_id_seq', 54, true);


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
-- Name: metodos_pago_reservas metodos_pago_reservas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.metodos_pago_reservas
    ADD CONSTRAINT metodos_pago_reservas_pkey PRIMARY KEY (id);


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
-- Name: tasas_cambio tasas_cambio_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tasas_cambio
    ADD CONSTRAINT tasas_cambio_pkey PRIMARY KEY (id);


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
-- Name: idx_reservas_fecha_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_reservas_fecha_cliente ON public.reservas USING btree (fecha_reserva, cliente_id);


--
-- Name: idx_reservas_ip; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_reservas_ip ON public.reservas USING btree (ip_address) WHERE (ip_address IS NOT NULL);


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
-- Name: compras compras_metodo_pago_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.compras
    ADD CONSTRAINT compras_metodo_pago_id_fkey FOREIGN KEY (metodo_pago_id) REFERENCES public.metodos_pago(id);


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

\unrestrict 12VhFCYwxsRdd1D1vw1o28d1gC55UDCHJanlUS5W5yPJAOQ8wf6UueH3m03hhF6


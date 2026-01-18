-- =============================================
-- BASE DE DATOS: INVERSIONES_ROJAS
-- PROYECTO SOCIOTECNOLÓGICO - INFORMÁTICA
-- =============================================

-- Crear la base de datos (ejecutar esto primero en psql o pgAdmin)
-- CREATE DATABASE inversiones_rojas;

-- Conectar a la base de datos primero
-- \c inversiones_rojas;

-- =============================================
-- TABLAS MAESTRAS
-- =============================================

-- Tabla de categorías de productos
CREATE TABLE categorias (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de proveedores
CREATE TABLE proveedores (
    id SERIAL PRIMARY KEY,
    razon_social VARCHAR(200) NOT NULL,
    rif VARCHAR(20) UNIQUE NOT NULL,
    persona_contacto VARCHAR(100),
    telefono_principal VARCHAR(15),
    telefono_alternativo VARCHAR(15),
    email VARCHAR(100),
    direccion TEXT,
    productos_suministrados TEXT,
    estado BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de métodos de pago
CREATE TABLE metodos_pago (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    estado BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de clientes
CREATE TABLE clientes (
    id SERIAL PRIMARY KEY,
    cedula_rif VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(200) NOT NULL,
    email VARCHAR(100),
    telefono_principal VARCHAR(15),
    telefono_alternativo VARCHAR(15),
    direccion TEXT,
    fecha_registro DATE DEFAULT CURRENT_DATE,
    estado BOOLEAN DEFAULT TRUE,
    usuario_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLAS DE USUARIOS Y SEGURIDAD
-- =============================================

-- Tabla de roles de usuario
CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    descripcion TEXT,
    permisos JSONB,
    estado BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios del sistema
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(200) NOT NULL,
    rol_id INTEGER REFERENCES roles(id),
    cliente_id INTEGER REFERENCES clientes(id),
    estado BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP,
    intentos_fallidos INTEGER DEFAULT 0,
    bloqueado_hasta TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ahora agregamos la foreign key a clientes
ALTER TABLE clientes ADD CONSTRAINT fk_cliente_usuario 
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id);

-- =============================================
-- TABLAS DE PRODUCTOS E INVENTARIO
-- =============================================

-- Tabla de productos
CREATE TABLE productos (
    id SERIAL PRIMARY KEY,
    codigo_interno VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    categoria_id INTEGER REFERENCES categorias(id),
    precio_compra DECIMAL(10,2) NOT NULL,
    precio_venta DECIMAL(10,2) NOT NULL,
    stock_actual INTEGER DEFAULT 0,
    stock_minimo INTEGER DEFAULT 5,
    stock_maximo INTEGER DEFAULT 100,
    proveedor_id INTEGER REFERENCES proveedores(id),
    estado BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para control de inventario
CREATE TABLE movimientos_inventario (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER REFERENCES productos(id),
    tipo_movimiento VARCHAR(20) NOT NULL CHECK (tipo_movimiento IN ('ENTRADA', 'SALIDA', 'AJUSTE')),
    cantidad INTEGER NOT NULL,
    stock_anterior INTEGER NOT NULL,
    stock_actual INTEGER NOT NULL,
    motivo TEXT,
    referencia VARCHAR(100),
    usuario_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLAS DE VENTAS Y PEDIDOS
-- =============================================

-- Tabla de ventas
CREATE TABLE ventas (
    id SERIAL PRIMARY KEY,
    codigo_venta VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INTEGER REFERENCES clientes(id),
    usuario_id INTEGER REFERENCES usuarios(id),
    metodo_pago_id INTEGER REFERENCES metodos_pago(id),
    subtotal DECIMAL(10,2) NOT NULL,
    iva DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    estado_venta VARCHAR(20) DEFAULT 'COMPLETADA' CHECK (estado_venta IN ('PENDIENTE', 'COMPLETADA', 'CANCELADA')),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de detalles de venta
CREATE TABLE detalle_ventas (
    id SERIAL PRIMARY KEY,
    venta_id INTEGER REFERENCES ventas(id),
    producto_id INTEGER REFERENCES productos(id),
    cantidad INTEGER NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de pedidos online
CREATE TABLE pedidos_online (
    id SERIAL PRIMARY KEY,
    codigo_pedido VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INTEGER REFERENCES clientes(id),
    subtotal DECIMAL(10,2) NOT NULL,
    iva DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    estado_pedido VARCHAR(20) DEFAULT 'PENDIENTE' CHECK (estado_pedido IN ('PENDIENTE', 'CONFIRMADO', 'PROCESANDO', 'COMPLETADO', 'CANCELADO')),
    metodo_pago_id INTEGER REFERENCES metodos_pago(id),
    direccion_entrega TEXT,
    telefono_contacto VARCHAR(15),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de detalles de pedidos online
CREATE TABLE detalle_pedidos_online (
    id SERIAL PRIMARY KEY,
    pedido_id INTEGER REFERENCES pedidos_online(id),
    producto_id INTEGER REFERENCES productos(id),
    cantidad INTEGER NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLAS DE RESERVAS Y PROMOCIONES
-- =============================================

-- Tabla de reservas
CREATE TABLE reservas (
    id SERIAL PRIMARY KEY,
    codigo_reserva VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INTEGER REFERENCES clientes(id),
    producto_id INTEGER REFERENCES productos(id),
    cantidad INTEGER NOT NULL,
    fecha_reserva DATE NOT NULL,
    fecha_limite DATE NOT NULL,
    estado_reserva VARCHAR(20) DEFAULT 'ACTIVA' CHECK (estado_reserva IN ('ACTIVA', 'COMPLETADA', 'CANCELADA', 'VENCIDA')),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de promociones
CREATE TABLE promociones (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    tipo_promocion VARCHAR(20) CHECK (tipo_promocion IN ('DESCUENTO', '2X1', 'PORCENTAJE')),
    valor DECIMAL(10,2),
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de relación productos-promociones
CREATE TABLE producto_promociones (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER REFERENCES productos(id),
    promocion_id INTEGER REFERENCES promociones(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLAS DE COMPRAS Y DEVOLUCIONES
-- =============================================

-- Tabla de compras a proveedores
CREATE TABLE compras (
    id SERIAL PRIMARY KEY,
    codigo_compra VARCHAR(20) UNIQUE NOT NULL,
    proveedor_id INTEGER REFERENCES proveedores(id),
    usuario_id INTEGER REFERENCES usuarios(id),
    subtotal DECIMAL(10,2) NOT NULL,
    iva DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    estado_compra VARCHAR(20) DEFAULT 'PENDIENTE' CHECK (estado_compra IN ('PENDIENTE', 'RECIBIDA', 'CANCELADA')),
    fecha_estimada_entrega DATE,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de detalles de compra
CREATE TABLE detalle_compras (
    id SERIAL PRIMARY KEY,
    compra_id INTEGER REFERENCES compras(id),
    producto_id INTEGER REFERENCES productos(id),
    cantidad INTEGER NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de devoluciones
CREATE TABLE devoluciones (
    id SERIAL PRIMARY KEY,
    codigo_devolucion VARCHAR(20) UNIQUE NOT NULL,
    venta_id INTEGER REFERENCES ventas(id),
    pedido_id INTEGER REFERENCES pedidos_online(id),
    cliente_id INTEGER REFERENCES clientes(id),
    producto_id INTEGER REFERENCES productos(id),
    cantidad INTEGER NOT NULL,
    motivo TEXT NOT NULL,
    estado_devolucion VARCHAR(20) DEFAULT 'PENDIENTE' CHECK (estado_devolucion IN ('PENDIENTE', 'APROBADA', 'RECHAZADA', 'COMPLETADA')),
    tipo_reintegro VARCHAR(20) CHECK (tipo_reintegro IN ('EFECTIVO', 'CAMBIO', 'CREDITO')),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLAS DE AUDITORÍA Y REPORTES
-- =============================================

-- Tabla de bitácora del sistema
CREATE TABLE bitacora_sistema (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER REFERENCES usuarios(id),
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50),
    registro_id INTEGER,
    detalles JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de alertas de stock
CREATE TABLE alertas_stock (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER REFERENCES productos(id),
    tipo_alerta VARCHAR(20) CHECK (tipo_alerta IN ('STOCK_BAJO', 'STOCK_AGOTADO')),
    stock_actual INTEGER NOT NULL,
    stock_minimo INTEGER NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- DATOS INICIALES
-- =============================================

-- Insertar roles del sistema
INSERT INTO roles (nombre, descripcion, permisos) VALUES
('Administrador', 'Acceso completo al sistema', '{"modulos": ["todos"], "acciones": ["crear", "leer", "actualizar", "eliminar"]}'),
('Gerente', 'Gestión de operaciones comerciales', '{"modulos": ["ventas", "compras", "inventario", "reportes", "pedidos", "reservas", "promociones", "devoluciones"], "acciones": ["crear", "leer", "actualizar"]}'),
('Vendedor', 'Atención al cliente y ventas', '{"modulos": ["ventas", "clientes", "reservas", "promociones", "devoluciones"], "acciones": ["crear", "leer"]}'),
('Operador', 'Gestión de pedidos online', '{"modulos": ["pedidos", "reservas", "devoluciones"], "acciones": ["crear", "leer", "actualizar"]}'),
('Cliente', 'Acceso al portal de clientes', '{"modulos": ["pedidos", "reservas", "devoluciones", "perfil"], "acciones": ["crear", "leer", "actualizar"]}');

-- Insertar métodos de pago
INSERT INTO metodos_pago (nombre, descripcion) VALUES
('Efectivo', 'Pago en efectivo'),
('Transferencia', 'Transferencia bancaria'),
('Pago Móvil', 'Pago a través de pago móvil'),
('Tarjeta Débito', 'Pago con tarjeta de débito'),
('Tarjeta Crédito', 'Pago con tarjeta de crédito');

-- Insertar categorías iniciales
INSERT INTO categorias (nombre, descripcion) VALUES
('Motocicletas', 'Vehículos de dos ruedas motorizados'),
('Cascos', 'Cascos de seguridad integrales y abatibles'),
('Aceites', 'Aceites y lubricantes para motor'),
('Repuestos', 'Repuestos y accesorios para motos'),
('Herramientas', 'Herramientas y equipos de mantenimiento');

-- Insertar proveedores de prueba
INSERT INTO proveedores (razon_social, rif, persona_contacto, telefono_principal, email) VALUES
('Motovehiculos C.A.', 'J-123456789', 'Carlos Rodríguez', '0243-5551001', 'ventas@motovehiculos.com'),
('Seguridad Total S.A.', 'J-987654321', 'Ana Martínez', '0243-5551002', 'info@seguridadtotal.com'),
('Lubricantes Premium', 'J-456789123', 'Roberto Sánchez', '0243-5551003', 'contacto@lubricantespremium.com');

-- Insertar usuario administrador inicial (password: admin123)
INSERT INTO usuarios (username, email, password_hash, nombre_completo, rol_id) VALUES
('admin', 'admin@inversionesrojas.com', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 1);

-- Insertar productos de prueba
INSERT INTO productos (codigo_interno, nombre, descripcion, categoria_id, precio_compra, precio_venta, stock_actual, proveedor_id) VALUES
('MOTO-001', 'Bera BR 200', 'Motocicleta 200cc color rojo', 1, 35000.00, 42000.00, 5, 1),
('CASCO-001', 'Casco Integral LS2', 'Casco integral color negro talla M', 2, 150.00, 250.00, 15, 2),
('ACEITE-001', 'Aceite Motul 5100', 'Aceite semisintético 10W40 1L', 3, 12.00, 18.00, 50, 3),
('REP-001', 'Kit Cadena', 'Kit cadena y sprockets', 4, 45.00, 75.00, 10, 1),
('HERR-001', 'Kit Herramientas', 'Juego de herramientas básicas', 5, 80.00, 120.00, 8, 1);

-- Insertar clientes de prueba (sin usuario primero)
INSERT INTO clientes (cedula_rif, nombre_completo, email, telefono_principal, direccion) VALUES
('V-12345678', 'Juan Pérez', 'juan.perez@email.com', '0412-1234567', 'Av. Principal, Maracay'),
('V-87654321', 'María García', 'maria.garcia@email.com', '0414-7654321', 'Sector Andrés Eloy Blanco'),
('J-123456780', 'Motocenter C.A.', 'ventas@motocenter.com', '0243-5551234', 'Zona Industrial, Maracay');

-- =============================================
-- FUNCIONES Y TRIGGERS
-- =============================================

-- Función para actualizar timestamp automáticamente
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers para actualizar updated_at
CREATE TRIGGER update_productos_updated_at BEFORE UPDATE ON productos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_clientes_updated_at BEFORE UPDATE ON clientes FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_proveedores_updated_at BEFORE UPDATE ON proveedores FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_usuarios_updated_at BEFORE UPDATE ON usuarios FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_pedidos_updated_at BEFORE UPDATE ON pedidos_online FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_reservas_updated_at BEFORE UPDATE ON reservas FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_promociones_updated_at BEFORE UPDATE ON promociones FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_devoluciones_updated_at BEFORE UPDATE ON devoluciones FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Función para generar códigos automáticos
CREATE OR REPLACE FUNCTION generar_codigo_venta()
RETURNS TRIGGER AS $$
BEGIN
    NEW.codigo_venta := 'VEN-' || TO_CHAR(CURRENT_DATE, 'YYYYMMDD') || '-' || LPAD(NEXTVAL('ventas_id_seq')::TEXT, 4, '0');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER generar_codigo_venta_trigger BEFORE INSERT ON ventas FOR EACH ROW EXECUTE FUNCTION generar_codigo_venta();

-- Función similar para pedidos
CREATE OR REPLACE FUNCTION generar_codigo_pedido()
RETURNS TRIGGER AS $$
BEGIN
    NEW.codigo_pedido := 'PED-' || TO_CHAR(CURRENT_DATE, 'YYYYMMDD') || '-' || LPAD(NEXTVAL('pedidos_online_id_seq')::TEXT, 4, '0');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER generar_codigo_pedido_trigger BEFORE INSERT ON pedidos_online FOR EACH ROW EXECUTE FUNCTION generar_codigo_pedido();

-- Función para actualizar stock después de venta
CREATE OR REPLACE FUNCTION actualizar_stock_venta()
RETURNS TRIGGER AS $$
DECLARE
    stock_actual_producto INTEGER;
BEGIN
    -- Obtener stock actual
    SELECT stock_actual INTO stock_actual_producto FROM productos WHERE id = NEW.producto_id;
    
    -- Actualizar stock del producto
    UPDATE productos 
    SET stock_actual = stock_actual - NEW.cantidad,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.producto_id;
    
    -- Registrar movimiento de inventario
    INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, referencia)
    VALUES (
        NEW.producto_id,
        'SALIDA',
        NEW.cantidad,
        stock_actual_producto,
        stock_actual_producto - NEW.cantidad,
        'VENTA-' || NEW.venta_id
    );
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER actualizar_stock_venta_trigger AFTER INSERT ON detalle_ventas FOR EACH ROW EXECUTE FUNCTION actualizar_stock_venta();

-- Función para crear usuario automáticamente al registrar cliente
CREATE OR REPLACE FUNCTION crear_usuario_cliente()
RETURNS TRIGGER AS $$
DECLARE
    nuevo_usuario_id INTEGER;
    username_base VARCHAR;
    contador INTEGER := 1;
BEGIN
    -- Solo si el cliente tiene email y no tiene usuario asignado
    IF NEW.email IS NOT NULL AND NEW.usuario_id IS NULL THEN
        -- Generar username base (primera parte del email)
        username_base := LOWER(SPLIT_PART(NEW.email, '@', 1));
        
        -- Verificar si el username ya existe y generar uno único
        WHILE EXISTS (SELECT 1 FROM usuarios WHERE username = username_base) LOOP
            username_base := LOWER(SPLIT_PART(NEW.email, '@', 1)) || contador;
            contador := contador + 1;
        END LOOP;
        
        -- Crear usuario para el cliente (password temporal: cliente123)
        INSERT INTO usuarios (username, email, password_hash, nombre_completo, rol_id, cliente_id)
        VALUES (
            username_base,
            NEW.email,
            '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- cliente123
            NEW.nombre_completo,
            5, -- ID del rol Cliente
            NEW.id
        ) RETURNING id INTO nuevo_usuario_id;
        
        -- Actualizar el cliente con el ID del usuario
        NEW.usuario_id := nuevo_usuario_id;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger para crear usuario automáticamente al insertar cliente
CREATE TRIGGER crear_usuario_cliente_trigger 
    BEFORE INSERT ON clientes 
    FOR EACH ROW 
    EXECUTE FUNCTION crear_usuario_cliente();

-- =============================================
-- VISTAS ÚTILES
-- =============================================

-- Vista para reporte de productos con stock bajo
CREATE OR REPLACE VIEW vista_stock_bajo AS
SELECT 
    p.id,
    p.codigo_interno,
    p.nombre,
    p.stock_actual,
    p.stock_minimo,
    c.nombre as categoria,
    pr.razon_social as proveedor
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
WHERE p.stock_actual <= p.stock_minimo AND p.estado = true;

-- Vista para reporte de ventas diarias
CREATE OR REPLACE VIEW vista_ventas_diarias AS
SELECT 
    DATE(v.created_at) as fecha,
    COUNT(*) as total_ventas,
    SUM(v.total) as ingreso_total,
    AVG(v.total) as promedio_venta,
    COUNT(DISTINCT v.cliente_id) as clientes_atendidos
FROM ventas v
WHERE v.estado_venta = 'COMPLETADA'
GROUP BY DATE(v.created_at);

-- Vista para el portal del cliente
CREATE OR REPLACE VIEW vista_portal_cliente AS
SELECT 
    c.id as cliente_id,
    c.cedula_rif,
    c.nombre_completo,
    c.email,
    c.telefono_principal,
    u.username,
    u.estado as usuario_activo,
    COUNT(DISTINCT v.id) as total_compras,
    COUNT(DISTINCT po.id) as total_pedidos_online,
    COALESCE(SUM(v.total), 0) as monto_total_compras
FROM clientes c
LEFT JOIN usuarios u ON c.usuario_id = u.id
LEFT JOIN ventas v ON c.id = v.cliente_id AND v.estado_venta = 'COMPLETADA'
LEFT JOIN pedidos_online po ON c.id = po.cliente_id AND po.estado_pedido = 'COMPLETADO'
GROUP BY c.id, c.cedula_rif, c.nombre_completo, c.email, c.telefono_principal, u.username, u.estado;

-- =============================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- =============================================

-- Índices para búsquedas frecuentes
CREATE INDEX idx_productos_nombre ON productos(nombre);
CREATE INDEX idx_productos_categoria ON productos(categoria_id);
CREATE INDEX idx_clientes_cedula ON clientes(cedula_rif);
CREATE INDEX idx_ventas_fecha ON ventas(created_at);
CREATE INDEX idx_pedidos_estado ON pedidos_online(estado_pedido);
CREATE INDEX idx_inventario_producto ON movimientos_inventario(producto_id);
CREATE INDEX idx_bitacora_usuario ON bitacora_sistema(usuario_id);
CREATE INDEX idx_usuarios_rol ON usuarios(rol_id);

-- =============================================
-- MENSAJE FINAL
-- =============================================

DO $$ 
BEGIN
    RAISE NOTICE '✅ BASE DE DATOS INVERSIONES_ROJAS CREADA EXITOSAMENTE';
    RAISE NOTICE '📊 Tablas creadas: 18';
    RAISE NOTICE '👥 Usuarios creados: 4 (admin + 3 clientes)';
    RAISE NOTICE '📦 Productos creados: 5';
    RAISE NOTICE '🔐 Contraseña admin: admin123';
    RAISE NOTICE '🔐 Contraseña clientes: cliente123';
END $$;
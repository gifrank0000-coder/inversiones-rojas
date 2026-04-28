# Sistema de Imágenes Mejorado - Documentación

## Cambios Realizados

### 1. API Backend (api/update_product.php)
✅ **Agregado soporte para subida de imágenes adicionales**
- Nueva sección de procesamiento de imágenes (`// ── Procesar imágenes adicionales si existen ──`)
- Manejo automático del orden de imágenes (incrementa desde el máximo existente)
- Validación completa de archivos (tipo MIME, tamaño máximo 5MB)
- Determinación automática de imagen principal (solo si no existen imágenes)
- Respuesta con lista de imágenes subidas (`imagenes_subidas`) y advertencias (`file_warnings`)

**Características:**
```php
// Código clave:
$stmt_max_orden = $conn->prepare("SELECT COALESCE(MAX(orden), 0) as max_orden FROM producto_imagenes WHERE producto_id = ?");
$es_principal = ($total_imgs == 0 && $i === 0) ? 'true' : 'false'; // Solo principal si no hay imágenes
$orden = $max_orden + $i + 1; // Orden secuencial
```

### 2. Vista de Detalles (app/views/layouts/product_detail.php)

#### 2a. UI para Subida de Imágenes
✅ **Botón para agregar imágenes** (solo para usuarios logueados)
- Ubicación: Debajo de thumbnails de imágenes existentes
- Botón: "Agregar imágenes" con icono
- Input file oculto para selección múltiple

```html
<?php if ($usuario_logueado): ?>
    <div class="image-upload-section">
        <button type="button" class="btn btn-outline" id="addImagesBtn">
            <i class="fas fa-plus"></i> Agregar imágenes
        </button>
        <input type="file" id="additionalImages" accept="image/*" multiple>
        <div id="uploadStatus"></div>
    </div>
<?php endif; ?>
```

#### 2b. Funciones JavaScript

**setupImageUpload()** - Inicializa el manejador de eventos
- Click en botón abre selector de archivos
- Change event dispara validación y upload

**uploadAdditionalImages(files)** - Procesa la subida
- Validación de tipos MIME
- Validación de tamaño máximo (5MB)
- Envío a `api/update_product.php` con FormData
- Feedback visual del estado
- Recarga automática al completar

**showUploadStatus(message, type)** - Muestra mensajes
- Tipos: 'info', 'success', 'error'
- Estilos CSS específicos por tipo
- Display/hide automático

#### 2c. Estilos CSS

```css
.image-upload-section {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
}

.upload-status {
    padding: 12px;
    border-radius: 6px;
    font-weight: 500;
    text-align: center;
}

.upload-status.success {
    background: #d4edda; /* Verde */
    color: #155724;
    border: 1px solid #c3e6cb;
}

.upload-status.error {
    background: #f8d7da; /* Rojo */
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.upload-status.info {
    background: #d1ecf1; /* Azul */
    color: #0c5460;
    border: 1px solid #bee5eb;
}
```

## Flujo Completo

### Crear Producto con Múltiples Imágenes
1. Usuario va a **inventario.php** → Nuevo Producto
2. Selecciona tipo (Vehículo/Repuesto/Accesorio)
3. Sube imágenes iniciales en `image-uploader`
4. Primera imagen se marca como principal (`es_principal = true`)
5. Resto quedan como secundarias (`es_principal = false`)
6. Orden asignada automáticamente (1, 2, 3, ...)

### Agregar Imágenes a Producto Existente
1. Usuario ve detalle en **product_detail.php**
2. Si está logueado, ve botón "Agregar imágenes"
3. Selecciona 1-6 imágenes adicionales
4. Sistema valida:
   - Tipo MIME (JPEG, PNG, GIF, WebP)
   - Tamaño < 5MB
   - Máximo 6 por upload
5. Envía a `api/update_product.php`
6. API procesa:
   - Crea directorio si no existe
   - Genera nombres únicos
   - Inserta en `producto_imagenes`
   - Calcula orden siguiente
   - Retorna resultado
7. Si éxito: recarga página para mostrar nuevas imágenes
8. Si error: muestra mensaje en UI

### Ver Imágenes en Detalle
1. product_detail.php carga todas las imágenes de `producto_imagenes`
2. Ordena por `es_principal DESC` (principal primero)
3. Muestra imagen principal en grande
4. Thumbnails como navegación
5. Click en thumbnail cambia la imagen principal
6. Scroll si hay más de 8 imágenes

## Consideraciones de Seguridad

✅ **Implementado:**
- Validación de tipo MIME con `finfo_open()`
- Validación de tamaño máximo (5MB)
- Nombres de archivo únicos con timestamp
- Uso de rutas relativas (`dirname(__DIR__)`)
- Preparación de declaraciones SQL
- Limitación de 6 imágenes por upload
- Autenticación requerida para usuario logueado

## Testing

### Verificar Funcionamiento
```bash
# 1. Crear producto con 2-3 imágenes
# Verificar en BD:
SELECT * FROM producto_imagenes WHERE producto_id = X ORDER BY orden;

# 2. En product_detail.php de ese producto
# - Ver botón "Agregar imágenes" (si logueado)
# - Agregar 2-3 imágenes más
# - Verificar que aparecen con orden secuencial

# 3. Verificar orden en BD:
SELECT COUNT(*) FROM producto_imagenes WHERE producto_id = X;
# Debe incrementar

# 4. Verificar principal:
SELECT producto_id, es_principal FROM producto_imagenes WHERE producto_id = X;
# Debe haber solo 1 con es_principal = true
```

## Base de Datos - Schema Existente

```sql
CREATE TABLE producto_imagenes (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER NOT NULL,
    imagen_url TEXT NOT NULL,
    es_principal BOOLEAN DEFAULT false,
    orden INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);
```

**Campos utilizados:**
- `producto_id` - Referencia al producto
- `imagen_url` - URL completa: `/inversiones-rojas/public/img/products/filename`
- `es_principal` - TRUE solo para imagen principal
- `orden` - Número secuencial (1, 2, 3, ...)
- `created_at` - Timestamp de creación

## Próximas Mejoras Sugeridas (Opcional)

1. **Galerías en edición de producto**: Permitir reordenar o eliminar imágenes existentes
2. **Previsualización antes de confirmar**: Mostrar preview de las nuevas imágenes antes de subir
3. **Eliminación de imágenes**: API para borrar imágenes específicas
4. **Crop/Resize**: Redimensionar imágenes en servidor antes de guardar
5. **Watermark**: Agregar logo a todas las imágenes
6. **Compresión**: Comprimir imágenes para optimizar almacenamiento

## Archivos Modificados

- `api/update_product.php` - Procesamiento de imágenes adicionales
- `app/views/layouts/product_detail.php` - UI y lógica de carga

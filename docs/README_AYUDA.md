# Centro de Ayuda - Inversiones Rojas

## Descripción
La nueva sección "Ayuda" en Configuración permite a los usuarios descargar manuales del sistema según su rol.

## Estructura de Manuales

### Manuales Disponibles
- **Manual de Usuario Básico**: Disponible para todos los usuarios
- **Manual del Vendedor**: Solo para usuarios con rol de vendedor
- **Manual del Administrador**: Solo para administradores
- **Manual de Backup y Restauración**: Solo para administradores
- **Manual de Integraciones**: Solo para administradores

## Ubicación de Archivos
Los manuales se almacenan en: `/docs/manuales/`

### Archivos Esperados
- `manual_usuario_basico.pdf`
- `manual_vendedor.pdf`
- `manual_administrador.pdf`
- `manual_backup.pdf`
- `manual_integraciones.pdf`

## Funcionalidades

### Detección de Roles
El sistema detecta automáticamente el rol del usuario logueado y muestra solo los manuales correspondientes.

### Descarga de Manuales
- Los botones de descarga intentan abrir el PDF en una nueva pestaña
- Si el archivo no existe, muestra un mensaje de error amigable
- Incluye indicadores de carga durante la descarga

### Información de Contacto
La sección incluye información de soporte técnico con:
- Email de soporte
- Teléfono de contacto
- Horario de atención

## Personalización

### Agregar Nuevos Manuales
1. Colocar el archivo PDF en `/docs/manuales/`
2. Agregar entrada en el array `$manuales` en `configuracion.php`
3. Especificar roles permitidos y colores de icono

### Modificar Información de Contacto
Editar la sección "support-options" en el HTML para cambiar:
- Email de soporte
- Teléfono
- Horario de atención

## Notas Técnicas

### Seguridad
- Los archivos se sirven desde el directorio público
- No hay restricciones de acceso (todos los usuarios autenticados pueden ver la sección)

### Responsive Design
- La interfaz se adapta a dispositivos móviles
- Los manuales se muestran en grid responsive

### Notificaciones
- Sistema de notificaciones toast para feedback de usuario
- Mensajes de éxito y error durante descargas
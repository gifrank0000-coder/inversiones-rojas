# Configurar HTTPS (desarrollo y producción)

Este documento explica cómo habilitar HTTPS para el proyecto `inversiones-rojas` en entornos locales (XAMPP) usando `mkcert` y cómo hacer la configuración mínima en Apache. Incluye también una nota rápida para producción (Let's Encrypt).

IMPORTANTE: No actives la constante `FORCE_HTTPS` en `config/config.php` hasta comprobar que `https://` funciona correctamente en tu servidor.

## 1) Requisitos
- XAMPP (Apache) instalado y funcionando
- `mkcert` (recomendado para desarrollo local) o acceso para instalar certificados (vía Let's Encrypt/ACME en producción)

## 2) Pasos rápidos (Windows + XAMPP + mkcert)

1. Instala `mkcert` (opcional con Chocolatey):

```powershell
choco install mkcert -y
mkcert -install
```

2. Crea un directorio para tus certificados de desarrollo (por ejemplo `C:\xampp\apache\conf\ssl`) y genera un certificado para `localhost`:

```powershell
mkdir C:\xampp\apache\conf\ssl
mkcert -cert-file C:\xampp\apache\conf\ssl\inversiones-rojas.crt -key-file C:\xampp\apache\conf\ssl\inversiones-rojas.key localhost 127.0.0.1 ::1
```

3. Edita (o crea) un VirtualHost para HTTPS. Puedes usar el archivo de ejemplo en `config/apache/httpd-ssl-inversiones.conf` y adaptarlo a tus rutas. Asegúrate de que Apache carga `httpd-ssl.conf` o incluyes este archivo manualmente.

4. Habilita `mod_rewrite` y `mod_headers` en Apache (revisa `httpd.conf`) y asegúrate de que `AllowOverride All` está activado para la carpeta del proyecto, para que `.htaccess` funcione.

5. Reinicia Apache desde el panel de XAMPP.

6. Prueba en el navegador:

```
https://localhost/inversiones-rojas/
```

Si el certificado fue creado con `mkcert` y se instaló en el store de confianza, el navegador debe aceptar el certificado (sin advertencias).

## 3) Comprobaciones (curl / PowerShell)

Comprobar redirección HTTP → HTTPS:

```powershell
curl -I http://localhost/inversiones-rojas/
```

Comprobar cabeceras HTTPS (HSTS):

```powershell
curl -I --insecure https://localhost/inversiones-rojas/
```

También puedes abrir `https://localhost/inversiones-rojas/tests/check_https.php` (archivo incluido en este repo) para recibir un JSON con información sobre si la petición se considera segura.

## 4) Producción (Let's Encrypt)

- En servidores Linux, usa `certbot` para solicitar certificados y configurar Apache automáticamente.
- En Windows/IIS puedes usar `win-acme`.
- Después de instalar el certificado, asegura el VirtualHost 443 y entonces activa `FORCE_HTTPS` en `config/config.php`.

## 5) Seguridad adicional
- Añade la cabecera HSTS sólo cuando HTTPS esté correctamente configurado (para evitar bloquear el sitio si algo falla).
- Mantén certificados actualizados (Let's Encrypt renueva cada 90 días; mkcert no es para producción).

---
Si quieres, te genero los comandos exactos para tu versión de XAMPP y edito el archivo de Apache (`httpd-ssl.conf` o `httpd-vhosts.conf`) con las rutas de certificados generados.

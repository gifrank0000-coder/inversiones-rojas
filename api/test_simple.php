<?php
// test_final.php en la carpeta principal
?>
<!DOCTYPE html>
<html>
<head>
    <title>Prueba Final de Venta</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        button { padding: 10px 20px; margin: 5px; border: none; cursor: pointer; border-radius: 5px; }
        .btn-1 { background: #4CAF50; color: white; }
        .btn-2 { background: #2196F3; color: white; }
        .btn-3 { background: #9C27B0; color: white; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto; }
        .success { background: #d4edda; padding: 10px; border-radius: 5px; }
        .error { background: #f8d7da; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>Prueba Final del Sistema de Ventas</h2>
    
    <div style="margin-bottom: 20px;">
        <button class="btn-1" onclick="probarVentaSimple()">Prueba SIMPLE (sin cliente)</button>
        <button class="btn-2" onclick="probarVentaConCliente()">Prueba con Cliente ID: 1</button>
        <button class="btn-3" onclick="probarVentaConProductoReal()">Prueba con Producto ID: 1</button>
    </div>
    
    <div>
        <h3>Verificar:</h3>
        <button onclick="verificarTabla()">Ver estructura de tabla 'ventas'</button>
        <button onclick="verificarProductos()">Ver productos disponibles</button>
    </div>
    
    <div id="resultado" style="margin-top: 20px;"></div>

    <script>
    function verificarTabla() {
        fetch('/inversiones-rojas/api/verificar_tabla.php')
            .then(r => r.json())
            .then(data => {
                document.getElementById('resultado').innerHTML = 
                    '<h3>Estructura de tabla VENTAS:</h3>' +
                    '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            });
    }
    
    function verificarProductos() {
        fetch('/inversiones-rojas/api/verificar_productos.php')
            .then(r => r.json())
            .then(data => {
                document.getElementById('resultado').innerHTML = 
                    '<h3>Productos disponibles:</h3>' +
                    '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            });
    }
    
    function probarVentaSimple() {
        const datos = {
            cliente_id: "",  // Vacío = sin cliente
            metodo_pago_id: "1",
            subtotal: "50.00",
            iva: "8.00",
            total: "58.00",
            productos: [
                { id: "1", price: "50.00", quantity: "1" }
            ]
        };
        enviarVenta(datos);
    }
    
    function probarVentaConCliente() {
        const datos = {
            cliente_id: "1",  // Número simple
            metodo_pago_id: "3",
            subtotal: "100.00",
            iva: "16.00",
            total: "116.00",
            productos: [
                { id: "1", price: "100.00", quantity: "1" }
            ]
        };
        enviarVenta(datos);
    }
    
    function probarVentaConProductoReal() {
        // Primero verificar qué productos existen
        fetch('/inversiones-rojas/api/verificar_productos.php')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.productos && data.productos.length > 0) {
                    const producto = data.productos[0]; // Tomar el primer producto
                    const datos = {
                        cliente_id: "1",
                        metodo_pago_id: "1",
                        subtotal: producto.precio_venta.toString(),
                        iva: (producto.precio_venta * 0.16).toFixed(2),
                        total: (producto.precio_venta * 1.16).toFixed(2),
                        productos: [
                            { 
                                id: producto.id.toString(), 
                                price: producto.precio_venta.toString(), 
                                quantity: "1" 
                            }
                        ]
                    };
                    enviarVenta(datos);
                } else {
                    alert('No hay productos en la base de datos');
                }
            });
    }
    
    function enviarVenta(datos) {
        document.getElementById('resultado').innerHTML = 
            '<p>Enviando datos...</p>' +
            '<pre>' + JSON.stringify(datos, null, 2) + '</pre>';
        
        fetch('/inversiones-rojas/api/procesar_venta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datos)
        })
        .then(response => {
            console.log('Status:', response.status);
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error('No es JSON:', text);
                    return { success: false, message: 'Respuesta no es JSON', raw: text };
                }
            });
        })
        .then(data => {
            let html = '<h3>Respuesta del Servidor:</h3>';
            html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            
            if (data.success) {
                html += `<div class="success">
                    <strong>¡Venta Exitosa!</strong><br>
                    Código: ${data.codigo_venta}<br>
                    ID Venta: ${data.venta_id}<br>
                    Total: $${data.total}
                </div>`;
            } else {
                html += `<div class="error">
                    <strong>Error:</strong> ${data.message}<br>
                    ${data.error ? 'Detalle: ' + data.error : ''}
                </div>`;
            }
            
            document.getElementById('resultado').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('resultado').innerHTML = 
                '<div class="error">' +
                '<strong>Error de conexión:</strong> ' + error.message + '</div>';
            console.error('Error:', error);
        });
    }
    </script>
</body>
</html>
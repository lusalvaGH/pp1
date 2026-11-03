# DeliStock — Sistema de Gestión de Stock y Ventas para Fiambrería

Sistema web ligero y robusto para gestión de stock y ventas de una fiambrería, desarrollado con **HTML5, CSS3, JavaScript nativo (Vanilla JS), PHP nativo y MySQL (XAMPP)**, sin dependencias complejas ni frameworks pesados.

---

## 🚀 Cómo abrir y probar el sistema

1. Asegurate de tener **Apache** y **MySQL** iniciados en el panel de control de **XAMPP**.
2. Abrí tu navegador web e ingresá a:
   ```text
   http://localhost/delistock/
   ```
3. Credenciales de acceso por defecto:
   - **Usuario:** `admin`
   - **Contraseña:** `admin123`

> **Nota:** La base de datos `delistock` y sus tablas se inicializan automáticamente al ingresar por primera vez. También tenés el archivo `database.sql` disponible para importarlo manualmente en phpMyAdmin si lo requerís.

---

## 📌 Correspondencia con la Documentación Teórica

### 1. Diagrama de Casos de Uso
- **Alta de Productos**: En *Gestión de Productos > Alta de Producto* (valida obligatoriedad, tipos de datos y nombres duplicados).
- **Listado de Productos**: En *Gestión de Productos > Listado de Productos* (con buscador en tiempo real, estado de stock y acciones).
- **Modificación de Productos**: Botón *Modificar* en la tabla de productos (permite actualizar nombre, precio y stock con validaciones).
- **Eliminación de Productos**: Botón *Eliminar* con modal de confirmación y protección ante borrado de productos con ventas históricas.
- **Registrar Venta**: En *Gestión de Ventas > Registrar Venta* (selector de productos, validación de stock disponible, carrito dinámico y cálculo de totales).
- **Actualización de Stock (`<<include>>`)**: Al confirmar la venta, el sistema descuenta automáticamente el stock en una transacción atómica segura.
- **Listado de Ventas**: En *Gestión de Ventas > Historial de Ventas* (muestra número de venta, fecha/hora, administrador responsable, resumen y modal de detalle).

### 2. Diagrama de Navegabilidad
- **Inicio del sistema (Login)** $\rightarrow$ **Menú Principal (Productos o Ventas)**.
- **Menú Principal**:
  - $\rightarrow$ **Gestión de Productos (ABM)**: Alta de Productos $\leftrightarrow$ Listado de Productos $\rightarrow$ Modificación / Eliminación.
  - $\rightarrow$ **Gestión de Ventas**: Registro de Ventas $\leftrightarrow$ Listado de Ventas (Historial).

### 3. Modelo de Dominio (Base de Datos)
- `administrador` (`idAdministrador`, `nombre`, `usuario`, `contrasena`)
- `producto` (`idProducto`, `nombre`, `precio`, `stock`)
- `venta` (`idVenta`, `idAdministrador`, `fecha`, `total`)
- `detalle_venta` (`idDetalleVenta`, `idVenta`, `idProducto`, `cantidad`, `precioUnitario`)

---

## 📁 Estructura del Proyecto

```text
tp.pp1/
├── index.php           # Interfaz HTML principal (SPA según el diagrama de navegabilidad)
├── conexion.php        # Conexión PDO con auto-creación e inicialización de la BD
├── database.sql        # Script SQL con estructura relacional y productos de muestra
├── css/
│   └── styles.css      # Estilos visuales con estética de fiambrería gourmet y componentes
├── js/
│   └── app.js          # Control de navegación, modales, llamadas fetch y validaciones de flujos
└── api/
    ├── auth.php        # Login, logout y estado de sesión
    ├── productos.php   # Endpoints de consulta y ABM de productos
    └── ventas.php      # Endpoint transaccional de registro de venta y consulta de historial
```

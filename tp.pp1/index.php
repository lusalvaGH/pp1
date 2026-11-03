<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DeliStock - Sistema de Gestión de Stock y Ventas para Fiambrería</title>
  <!-- Estilos del Sistema divididos por módulos -->
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/componentes.css">
  <link rel="stylesheet" href="css/vistas.css">
</head>
<body>

  <!-- Toast de Notificaciones Flotantes -->
  <div id="toast-container"></div>

  <!-- Barra de Navegación Superior -->
  <header class="navbar">
    <div class="nav-brand" onclick="app.navTo('menu')">
      <div class="brand-icon">🧀</div>
      <div>
        <h1>DeliStock</h1>
      </div>
      <span>Fiambrería &amp; Embutidos</span>
    </div>
    <div class="nav-user" id="navUserSection" style="display: none;">
      <div class="user-badge">
        <span>👤</span>
        <strong id="loggedUserName">Administrador</strong>
      </div>
      <button class="btn-nav-logout" onclick="app.logout()">Cerrar Sesión</button>
    </div>
  </header>

  <main class="container">

    <!-- ====================================================================
         1. VISTA: INICIO DEL SISTEMA (LOGIN)
         ==================================================================== -->
    <section id="view-login" class="view-section active">
      <div class="login-wrapper">
        <div class="login-card">
          <div class="login-icon">🥖</div>
          <h2 class="login-title">DeliStock</h2>
          <p class="login-subtitle">Sistema de Gestión de Stock y Ventas de Fiambrería</p>

          <form id="formLogin" onsubmit="app.handleLogin(event)">
            <div class="form-group">
              <label for="loginUser">Usuario</label>
              <input type="text" id="loginUser" class="form-control" placeholder="Ej: admin" value="admin" required autofocus>
            </div>
            <div class="form-group">
              <label for="loginPass">Contraseña</label>
              <input type="password" id="loginPass" class="form-control" placeholder="••••••••" value="admin123" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem;">
              Ingresar al Sistema
            </button>
          </form>

          <div style="margin-top: 1.5rem; font-size: 0.8rem; color: #64748b; background: #f8fafc; padding: 0.75rem; border-radius: 6px; border: 1px solid #e2e8f0;">
            Credenciales por defecto: <strong>admin</strong> / <strong>admin123</strong>
          </div>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         2. VISTA: MENÚ PRINCIPAL
         ==================================================================== -->
    <section id="view-menu" class="view-section">
      <div class="dashboard-header">
        <h2>Menú Principal</h2>
        <p>Seleccione el módulo que desea gestionar</p>
      </div>

      <div class="modules-grid">
        <!-- Módulo 1: Gestión de Productos (ABM) -->
        <div class="module-card">
          <div>
            <div class="module-icon">📦</div>
            <h3>Gestión de Productos</h3>
            <p>Control de inventario de fiambrería: alta de nuevos fiambres y quesos, consulta de stock, actualización de precios y bajas.</p>
          </div>
          <div class="module-actions">
            <button class="btn btn-primary btn-block" onclick="app.navTo('productos', 'listado')">
              📋 Listado de Productos
            </button>
            <button class="btn btn-secondary btn-block" onclick="app.navTo('productos', 'alta')">
              ➕ Alta de Producto
            </button>
          </div>
        </div>

        <!-- Módulo 2: Gestión de Ventas -->
        <div class="module-card">
          <div>
            <div class="module-icon">🧾</div>
            <h3>Gestión de Ventas</h3>
            <p>Registro de operaciones en mostrador con descuento automático de stock, cálculo de importes e historial de ventas.</p>
          </div>
          <div class="module-actions">
            <button class="btn btn-primary btn-block" onclick="app.navTo('ventas', 'registro')">
              🛒 Registrar Venta
            </button>
            <button class="btn btn-secondary btn-block" onclick="app.navTo('ventas', 'listado')">
              📜 Historial de Ventas
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         3. VISTA: GESTIÓN DE PRODUCTOS (ABM)
         ==================================================================== -->
    <section id="view-productos" class="view-section">
      <div class="section-topbar">
        <div class="section-title-wrap">
          <span style="font-size: 1.5rem;">📦</span>
          <h2>Gestión de Productos</h2>
          <span class="badge-indicator">ABM de Inventario</span>
        </div>
        <button class="btn btn-secondary" onclick="app.navTo('menu')">
          ⬅ Volver al Menú Principal
        </button>
      </div>

      <!-- Sub-Pestañas -->
      <div class="nav-tabs">
        <button class="tab-btn active" id="tabBtnProdList" onclick="app.switchProductTab('listado')">
          📋 Listado de Productos
        </button>
        <button class="tab-btn" id="tabBtnProdAlta" onclick="app.switchProductTab('alta')">
          ➕ Alta de Producto
        </button>
      </div>

      <!-- Contenido Tab: Listado -->
      <div id="subview-prod-listado">
        <div class="table-card">
          <div class="table-toolbar">
            <div class="search-input-wrap">
              <span class="search-icon">🔍</span>
              <input type="text" id="inputSearchProd" class="form-control" placeholder="Buscar fiambre, queso, embutido..." oninput="app.filterProducts()">
            </div>
            <button class="btn btn-primary btn-sm" onclick="app.switchProductTab('alta')">
              ➕ Nuevo Producto
            </button>
          </div>

          <div class="table-responsive">
            <table id="tableProducts">
              <thead>
                <tr>
                  <th style="width: 70px;">ID</th>
                  <th>Nombre del Producto</th>
                  <th style="width: 140px;">Precio Unit.</th>
                  <th style="width: 120px;">Stock</th>
                  <th style="width: 140px;">Estado</th>
                  <th style="width: 160px; text-align: center;">Acciones</th>
                </tr>
              </thead>
              <tbody id="tbodyProducts">
                <!-- Se llena dinámicamente -->
              </tbody>
            </table>
          </div>

          <!-- Mensaje Flujo Alternativo A1 (Sin productos) -->
          <div id="emptyProductsMsg" class="empty-state" style="display: none;">
            <div class="empty-icon">🧀</div>
            <h4>No existen productos registrados</h4>
            <p>Comience agregando fiambres, quesos o embutidos al catálogo.</p>
            <button class="btn btn-primary" onclick="app.switchProductTab('alta')">
              ➕ Ir a Alta de Producto
            </button>
          </div>
        </div>
      </div>

      <!-- Contenido Tab: Formulario Alta de Producto -->
      <div id="subview-prod-alta" style="display: none;">
        <div class="table-card" style="max-width: 650px; margin: 0 auto; padding: 2rem;">
          <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem; color: var(--primary);">Alta de Producto</h3>
          <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
            Complete los datos para registrar un nuevo producto en el catálogo de la fiambrería.
          </p>

          <form id="formAltaProducto" onsubmit="app.handleCreateProduct(event)">
            <div class="form-group">
              <label for="altaNombre">Nombre del Producto *</label>
              <input type="text" id="altaNombre" class="form-control" placeholder="Ej: Jamón Cocido Selección (kg)" required>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="altaPrecio">Precio Unitario ($) *</label>
                <input type="number" step="0.01" min="0.01" id="altaPrecio" class="form-control" placeholder="Ej: 8500.00" required>
              </div>
              <div class="form-group">
                <label for="altaStock">Cantidad en Stock *</label>
                <input type="number" step="1" min="0" id="altaStock" class="form-control" placeholder="Ej: 20" required>
              </div>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
              <button type="submit" class="btn btn-primary">
                💾 Registrar Producto
              </button>
              <button type="button" class="btn btn-secondary" onclick="app.switchProductTab('listado')">
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         4. VISTA: GESTIÓN DE VENTAS
         ==================================================================== -->
    <section id="view-ventas" class="view-section">
      <div class="section-topbar">
        <div class="section-title-wrap">
          <span style="font-size: 1.5rem;">🧾</span>
          <h2>Gestión de Ventas</h2>
          <span class="badge-indicator">Mostrador &amp; Facturación</span>
        </div>
        <button class="btn btn-secondary" onclick="app.navTo('menu')">
          ⬅ Volver al Menú Principal
        </button>
      </div>

      <!-- Sub-Pestañas -->
      <div class="nav-tabs">
        <button class="tab-btn active" id="tabBtnVentaReg" onclick="app.switchSaleTab('registro')">
          🛒 Registrar Venta
        </button>
        <button class="tab-btn" id="tabBtnVentaList" onclick="app.switchSaleTab('listado')">
          📜 Listado de Ventas (Historial)
        </button>
      </div>

      <!-- Subvista 1: Formulario Registrar Venta -->
      <div id="subview-venta-registro">
        <div class="sales-layout">
          <!-- Columna Izquierda: Búsqueda y Pesaje de Producto -->
          <div class="sales-panel">
            <h3><span>🥖</span> Buscar y Pesar Producto</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.8rem;">
              Escriba para filtrar productos y especifique el peso o cantidad.
            </p>

            <!-- 1. Buscador en tiempo real mientras escribe -->
            <div class="form-group" style="margin-bottom: 0.5rem;">
              <div class="search-input-wrap" style="max-width: 100%;">
                <span class="search-icon">🔍</span>
                <input type="text" id="saleSearchInput" class="form-control" placeholder="Escriba para buscar: ej. Jamón, Queso, Salame..." oninput="app.onSaleSearchInput()" autocomplete="off">
              </div>
            </div>

            <!-- Lista de resultados en tiempo real -->
            <div id="saleSearchResults" class="sale-search-results">
              <!-- Cargado dinámicamente -->
            </div>

            <!-- 2. Configuración de Peso del Producto Seleccionado -->
            <div id="productSaleConfig" class="product-selector-box" style="display: none; margin-top: 0.75rem;">
              <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.6rem;">
                <div>
                  <strong id="saleSelectedName" style="font-size: 1.05rem; color: #1e293b; display: block;">Producto</strong>
                  <span id="saleSelectedStock" style="font-size: 0.82rem; color: var(--text-muted);">Stock disponible: 0 kg</span>
                </div>
                <div style="text-align: right;">
                  <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Precio por kg:</span>
                  <strong id="saleSelectedPrice" style="color: var(--primary); font-size: 1.15rem;">$0.00</strong>
                </div>
              </div>

              <!-- Ingreso de Peso en gramos y kilos -->
              <div class="form-group" style="margin-bottom: 0.5rem;">
                <label for="inputPesoVenta" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                  <span>Peso o cantidad:</span>
                  <span style="font-weight: normal; color: var(--text-muted); font-size: 0.78rem;">Ej: 250 g, 500 g, 1 kg, 1,250 kg</span>
                </label>
                <input type="text" id="inputPesoVenta" class="form-control" placeholder="Escriba el peso (ej: 250 g o 1,250 kg)" oninput="app.onWeightInputChanged()" autocomplete="off" style="font-size: 1.05rem; font-weight: 700;">
              </div>

              <!-- Atajos Rápidos de Peso (frecuentes en fiambrería) -->
              <div style="margin-bottom: 0.65rem;">
                <span style="font-size: 0.76rem; font-weight: 600; color: #475569; display: block; margin-bottom: 0.35rem;">Atajos de peso:</span>
                <div class="quick-weight-chips">
                  <button type="button" class="btn-chip" onclick="app.setQuickWeight('100 g')">100 g</button>
                  <button type="button" class="btn-chip" onclick="app.setQuickWeight('150 g')">150 g</button>
                  <button type="button" class="btn-chip" onclick="app.setQuickWeight('200 g')">200 g</button>
                  <button type="button" class="btn-chip" onclick="app.setQuickWeight('250 g')">250 g</button>
                  <button type="button" class="btn-chip" onclick="app.setQuickWeight('500 g')">500 g</button>
                  <button type="button" class="btn-chip" onclick="app.setQuickWeight('1 kg')">1 kg</button>
                  <button type="button" class="btn-chip" onclick="app.setQuickWeight('1,250 kg')">1,250 kg</button>
                </div>
              </div>

              <!-- Cálculo en vivo automático -->
              <div class="live-calc-box">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span style="font-size: 0.82rem; color: #166534;">
                    Peso: <strong id="calcWeightText">0 g</strong>
                  </span>
                  <span style="font-size: 0.82rem; color: #166534;">
                    Subtotal: <strong id="calcSubtotalText" style="color: var(--primary); font-size: 1.15rem;">$0.00</strong>
                  </span>
                </div>
              </div>

              <button type="button" class="btn btn-accent btn-block" style="margin-top: 0.85rem;" onclick="app.addToCart()">
                ➕ Agregar a la Venta
              </button>
            </div>
          </div>

          <!-- Columna Derecha: Detalle de Venta (Carrito) -->
          <div class="sales-panel">
            <h3><span>🛒</span> Detalle de la Operación</h3>
            
            <div class="table-responsive">
              <table class="cart-table">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th style="width: 100px;">Precio/kg</th>
                    <th style="width: 95px;">Peso/Cant.</th>
                    <th style="width: 100px;">Subtotal</th>
                    <th style="width: 40px;"></th>
                  </tr>
                </thead>
                <tbody id="tbodyCart">
                  <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                      No hay productos agregados a la venta.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="cart-total-box">
              <span class="total-label">Total a Cobrar:</span>
              <span class="total-amount" id="cartTotalText">$0.00</span>
            </div>

            <div class="sales-actions-row">
              <button type="button" class="btn btn-primary" style="flex: 2;" onclick="app.confirmSale()">
                ✔ Confirmar Venta
              </button>
              <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="app.cancelSale()">
                ✖ Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Subvista 2: Listado de Ventas (Historial) -->
      <div id="subview-venta-listado" style="display: none;">
        <div class="table-card">
          <div class="table-toolbar">
            <h3 style="font-size: 1.1rem; color: #1e293b;">Historial de Operaciones Registradas</h3>
            <button class="btn btn-primary btn-sm" onclick="app.switchSaleTab('registro')">
              🛒 Registrar Nueva Venta
            </button>
          </div>

          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th style="width: 90px;">N° Venta</th>
                  <th style="width: 170px;">Fecha y Hora</th>
                  <th>Administrador</th>
                  <th>Productos Vendidos</th>
                  <th style="width: 130px;">Total</th>
                  <th style="width: 110px; text-align: center;">Detalle</th>
                </tr>
              </thead>
              <tbody id="tbodySales">
                <!-- Se llena dinámicamente -->
              </tbody>
            </table>
          </div>

          <!-- Mensaje Flujo Alternativo A1 (Sin ventas) -->
          <div id="emptySalesMsg" class="empty-state" style="display: none;">
            <div class="empty-icon">🧾</div>
            <h4>No existen ventas registradas</h4>
            <p>Aún no se ha realizado ninguna transacción en el sistema.</p>
            <button class="btn btn-primary" onclick="app.switchSaleTab('registro')">
              🛒 Registrar Primera Venta
            </button>
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- ====================================================================
       MODAL 1: MODIFICACIÓN DE PRODUCTO (Caso de Uso 3)
       ==================================================================== -->
  <div id="modalEditProduct" class="modal-overlay">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Modificar Producto</h3>
        <button class="modal-close-btn" onclick="app.closeModal('modalEditProduct')">&times;</button>
      </div>
      <form id="formEditProduct" onsubmit="app.handleUpdateProduct(event)">
        <div class="modal-body">
          <input type="hidden" id="editIdProducto">

          <div class="form-group">
            <label for="editNombre">Nombre del Producto *</label>
            <input type="text" id="editNombre" class="form-control" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="editPrecio">Precio Unitario ($) *</label>
              <input type="number" step="0.01" min="0.01" id="editPrecio" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="editStock">Cantidad en Stock *</label>
              <input type="number" step="1" min="0" id="editStock" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="app.closeModal('modalEditProduct')">
            Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            💾 Guardar Cambios
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ====================================================================
       MODAL 2: CONFIRMACIÓN DE ELIMINACIÓN DE PRODUCTO (Caso de Uso 4)
       ==================================================================== -->
  <div id="modalDeleteProduct" class="modal-overlay">
    <div class="modal-card" style="max-width: 440px;">
      <div class="modal-header">
        <h3 style="color: var(--danger);">Eliminar Producto</h3>
        <button class="modal-close-btn" onclick="app.closeModal('modalDeleteProduct')">&times;</button>
      </div>
      <div class="modal-body">
        <p style="margin-bottom: 0.75rem;">
          ¿Está seguro de que desea eliminar el siguiente producto del sistema?
        </p>
        <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 1rem; border-radius: 8px;">
          <strong id="deleteProdNombre" style="color: #991b1b; font-size: 1.05rem;">Producto</strong>
          <div style="font-size: 0.85rem; color: #64748b; margin-top: 0.25rem;">
            ID: <span id="deleteProdId">0</span> | Stock actual: <span id="deleteProdStock">0</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="app.closeModal('modalDeleteProduct')">
          Cancelar
        </button>
        <button type="button" class="btn btn-danger" onclick="app.confirmDeleteProduct()">
          🗑 Sí, Eliminar
        </button>
      </div>
    </div>
  </div>

  <!-- ====================================================================
       MODAL 3: DETALLE DE VENTA
       ==================================================================== -->
  <div id="modalSaleDetail" class="modal-overlay">
    <div class="modal-card" style="max-width: 600px;">
      <div class="modal-header">
        <h3>Detalle de Venta #<span id="detailSaleId"></span></h3>
        <button class="modal-close-btn" onclick="app.closeModal('modalSaleDetail')">&times;</button>
      </div>
      <div class="modal-body">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.9rem; color: var(--text-muted);">
          <div>Fecha: <strong id="detailSaleFecha" style="color: #1e293b;"></strong></div>
          <div>Atendido por: <strong id="detailSaleAdmin" style="color: #1e293b;"></strong></div>
        </div>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Producto</th>
                <th style="width: 70px;">Cant.</th>
                <th style="width: 110px;">P. Unit</th>
                <th style="width: 110px;">Subtotal</th>
              </tr>
            </thead>
            <tbody id="tbodySaleDetailItems">
              <!-- Renglones de la venta -->
            </tbody>
          </table>
        </div>

        <div class="cart-total-box" style="margin-top: 1rem; padding: 0.75rem 1rem;">
          <span class="total-label" style="font-size: 0.95rem;">Total de la Venta:</span>
          <span class="total-amount" id="detailSaleTotal" style="font-size: 1.35rem;">$0.00</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="app.closeModal('modalSaleDetail')">
          Cerrar
        </button>
      </div>
    </div>
  </div>

  <!-- Pie de página -->
  <footer>
    <strong>DeliStock</strong> &mdash; Sistema de Gestión de Stock y Ventas para Fiambrería &bull; Trabajo Práctico de Diseño de Software &bull; 2026
  </footer>

  <!-- Scripts de la Aplicación divididos por módulos para fácil lectura -->
  <script src="js/app.js"></script>
  <script src="js/ui.js"></script>
  <script src="js/auth.js"></script>
  <script src="js/productos.js"></script>
  <script src="js/ventas.js"></script>
</body>
</html>

/**
 * ============================================================================
 * DeliStock - Módulo de Gestión de Productos
 * Archivo: js/productos.js
 * Controla el catálogo de fiambres y quesos (Listar, Alta, Modificar, Eliminar)
 * ============================================================================
 */

// Carga la lista de productos desde la base de datos MySQL
app.loadProducts = async function() {
  const data = await this.enviarPeticion('api/productos.php?action=listar');
  if (data.success) {
    this.products = data.productos;
    this.renderProductsTable(this.products);
  }
};

// Dibuja la tabla del catálogo de productos en pantalla
app.renderProductsTable = function(lista) {
  const tabla = document.getElementById('tableProducts');
  const cuerpoTabla = document.getElementById('tbodyProducts');
  const mensajeVacio = document.getElementById('emptyProductsMsg');

  if (!cuerpoTabla) return;
  cuerpoTabla.innerHTML = '';

  if (!lista || lista.length === 0) {
    if (tabla) tabla.style.display = 'none';
    if (mensajeVacio) mensajeVacio.style.display = 'block';
    return;
  }

  if (tabla) tabla.style.display = 'table';
  if (mensajeVacio) mensajeVacio.style.display = 'none';

  for (let i = 0; i < lista.length; i++) {
    const p = lista[i];
    const stock = Number(p.stock);

    let claseBadge = 'badge-success';
    let textoBadge = 'En Stock';
    if (stock === 0) {
      claseBadge = 'badge-danger';
      textoBadge = 'Sin Stock';
    } else if (stock <= 10) {
      claseBadge = 'badge-warning';
      textoBadge = 'Stock Bajo';
    }

    const fila = document.createElement('tr');
    fila.innerHTML = `
      <td style="font-weight: 700; color: #64748b;">#${p.idProducto}</td>
      <td><div style="font-weight: 700; color: #1e293b;">${this.escapeHtml(p.nombre)}</div></td>
      <td style="font-weight: 700; color: var(--primary);">$${this.formatCurrency(p.precio)}</td>
      <td><strong>${this.formatWeight(stock)}</strong></td>
      <td><span class="badge ${claseBadge}">${textoBadge}</span></td>
      <td style="text-align: center;">
        <div style="display: inline-flex; gap: 0.4rem;">
          <button class="btn btn-secondary btn-sm" onclick="app.openEditProductModal(${p.idProducto})">✏ Modificar</button>
          <button class="btn btn-danger btn-sm" onclick="app.openDeleteProductModal(${p.idProducto})">🗑</button>
        </div>
      </td>
    `;
    cuerpoTabla.appendChild(fila);
  }
};

// Filtra la tabla de productos mientras el usuario escribe en el buscador del catálogo
app.filterProducts = function() {
  const input = document.getElementById('inputSearchProd');
  const texto = (input ? input.value : '').toLowerCase().trim();

  if (!texto) {
    this.renderProductsTable(this.products);
    return;
  }

  const filtrados = this.products.filter(function(p) {
    return p.nombre.toLowerCase().includes(texto);
  });
  this.renderProductsTable(filtrados);
};

// Registra un nuevo producto en la base de datos
app.handleCreateProduct = async function(event) {
  event.preventDefault();

  const nombre = document.getElementById('altaNombre').value.trim();
  const precio = parseFloat(document.getElementById('altaPrecio').value);
  const stock = parseFloat(document.getElementById('altaStock').value);

  const data = await this.enviarPeticion('api/productos.php?action=crear', {
    nombre: nombre,
    precio: precio,
    stock: stock
  });

  if (data.success) {
    this.showToast(data.message, 'success');
    document.getElementById('formAltaProducto').reset();
    await this.loadProducts();
    this.switchProductTab('listado');
  } else {
    this.showToast(data.message, 'error');
  }
};

// Abre el modal para modificar un producto
app.openEditProductModal = function(id) {
  const p = this.products.find(item => item.idProducto === id);
  if (!p) return;

  document.getElementById('editIdProducto').value = p.idProducto;
  document.getElementById('editNombre').value = p.nombre;
  document.getElementById('editPrecio').value = p.precio;
  document.getElementById('editStock').value = p.stock;

  this.openModal('modalEditProduct');
  document.getElementById('editNombre').focus();
};

// Guarda los cambios del producto modificado
app.handleUpdateProduct = async function(event) {
  event.preventDefault();

  const idProducto = parseInt(document.getElementById('editIdProducto').value, 10);
  const nombre = document.getElementById('editNombre').value.trim();
  const precio = parseFloat(document.getElementById('editPrecio').value);
  const stock = parseFloat(document.getElementById('editStock').value);

  const data = await this.enviarPeticion('api/productos.php?action=actualizar', {
    idProducto: idProducto,
    nombre: nombre,
    precio: precio,
    stock: stock
  });

  if (data.success) {
    this.showToast(data.message, 'success');
    this.closeModal('modalEditProduct');
    await this.loadProducts();
  } else {
    this.showToast(data.message, 'error');
  }
};

// Abre la confirmación para eliminar un producto
app.openDeleteProductModal = function(id) {
  const p = this.products.find(item => item.idProducto === id);
  if (!p) return;

  this.pendingDeleteProductId = id;
  document.getElementById('deleteProdNombre').textContent = p.nombre;
  document.getElementById('deleteProdId').textContent = p.idProducto;
  document.getElementById('deleteProdStock').textContent = p.stock;

  this.openModal('modalDeleteProduct');
};

// Elimina definitivamente el producto
app.confirmDeleteProduct = async function() {
  if (!this.pendingDeleteProductId) return;

  const data = await this.enviarPeticion('api/productos.php?action=eliminar', {
    idProducto: this.pendingDeleteProductId
  });

  if (data.success) {
    this.showToast(data.message, 'success');
    this.closeModal('modalDeleteProduct');
    this.pendingDeleteProductId = null;
    await this.loadProducts();
  } else {
    this.showToast(data.message, 'error');
    this.closeModal('modalDeleteProduct');
  }
};

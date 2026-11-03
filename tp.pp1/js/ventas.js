/**
 * ============================================================================
 * DeliStock - Módulo de Ventas y Cobro
 * Archivo: js/ventas.js
 * Controla el mostrador: buscador ágil, pesaje, carrito e historial de ventas
 * ============================================================================
 */

// ----------------------------------------------------------------------------
// 1. Buscador y Pesaje de Productos en el Mostrador
// ----------------------------------------------------------------------------
app.populateSaleSelector = function() {
  this.selectedSaleProduct = null;
  this.currentSaleWeightKg = 0;

  const inputBuscador = document.getElementById('saleSearchInput');
  if (inputBuscador) inputBuscador.value = '';

  const cajaConfig = document.getElementById('productSaleConfig');
  if (cajaConfig) cajaConfig.style.display = 'none';

  this.renderSaleSearchResults(this.products);
};

app.onSaleSearchInput = function() {
  const input = document.getElementById('saleSearchInput');
  const texto = (input ? input.value : '').toLowerCase().trim();

  if (!texto) {
    this.renderSaleSearchResults(this.products);
    return;
  }

  const filtrados = this.products.filter(function(p) {
    return p.nombre.toLowerCase().includes(texto);
  });
  this.renderSaleSearchResults(filtrados);
};

app.renderSaleSearchResults = function(lista) {
  const contenedor = document.getElementById('saleSearchResults');
  if (!contenedor) return;

  contenedor.innerHTML = '';

  if (!lista || lista.length === 0) {
    contenedor.innerHTML = `
      <div style="padding: 1rem; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
        No se encontraron productos coincidentes.
      </div>
    `;
    return;
  }

  for (let i = 0; i < lista.length; i++) {
    const p = lista[i];
    const estaSeleccionado = this.selectedSaleProduct && this.selectedSaleProduct.idProducto === p.idProducto;
    const sinStock = p.stock <= 0;

    const itemDiv = document.createElement('div');
    itemDiv.className = 'sale-search-item' + (estaSeleccionado ? ' selected' : '');
    if (sinStock) {
      itemDiv.style.opacity = '0.6';
    }

    itemDiv.innerHTML = `
      <div>
        <div class="item-title">${this.escapeHtml(p.nombre)}</div>
        <div class="item-meta">
          ${sinStock ? '<span style="color: var(--danger); font-weight: 700;">Agotado</span>' : 'Stock: <strong>' + this.formatWeight(p.stock) + '</strong>'}
        </div>
      </div>
      <div class="item-price">
        $${this.formatCurrency(p.precio)}
        <span style="font-size: 0.72rem; display: block; color: var(--text-muted); font-weight: normal;">por kg</span>
      </div>
    `;

    if (!sinStock) {
      itemDiv.onclick = () => this.selectSaleProduct(p.idProducto);
    } else {
      itemDiv.onclick = () => this.showToast('Producto sin stock disponible.', 'warning');
    }

    contenedor.appendChild(itemDiv);
  }
};

app.selectSaleProduct = function(id) {
  const p = this.products.find(item => item.idProducto === id);
  if (!p) return;

  this.selectedSaleProduct = p;

  const cajaConfig = document.getElementById('productSaleConfig');
  if (cajaConfig) cajaConfig.style.display = 'block';

  document.getElementById('saleSelectedName').textContent = p.nombre;
  document.getElementById('saleSelectedPrice').textContent = '$' + this.formatCurrency(p.precio);
  document.getElementById('saleSelectedStock').textContent = 'Stock disponible: ' + this.formatWeight(p.stock);

  // Valor sugerido por defecto: 250 g
  const input = document.getElementById('inputPesoVenta');
  if (input) {
    input.value = '250 g';
    this.onWeightInputChanged();
    input.focus();
    input.select();
  }

  this.onSaleSearchInput();
};

// Asigna un peso rápido (100 g, 200 g, 250 g, 1 kg...)
app.setQuickWeight = function(pesoTexto) {
  const input = document.getElementById('inputPesoVenta');
  if (input) {
    input.value = pesoTexto;
    this.onWeightInputChanged();
    input.focus();
  }
};

// Convierte lo que el usuario escribe a kilogramos numéricos
app.parseWeight = function(texto) {
  if (!texto) return 0;

  let limpio = texto.toLowerCase().trim().replace(',', '.');
  let numero = parseFloat(limpio.replace(/[^0-9.]/g, ''));
  if (isNaN(numero) || numero <= 0) return 0;

  // Si tiene 'g' (ej: "250g"), lo divide por 1000 para pasarlo a kilos
  if (limpio.includes('g') && !limpio.includes('k')) {
    return numero / 1000;
  }

  // Si tiene 'k' (ej: "1.5 kg"), ya está en kilos
  if (limpio.includes('k')) {
    return numero;
  }

  // Si solo escribió números:
  // Si puso 15 o más (ej: 100, 250, 500) son gramos
  if (numero >= 15) {
    return numero / 1000;
  }

  // Si puso menos de 15 (ej: 0.5, 1, 2) son kilos
  return numero;
};

// Actualiza en vivo el texto del peso y el subtotal calculado
app.onWeightInputChanged = function() {
  if (!this.selectedSaleProduct) return;

  const input = document.getElementById('inputPesoVenta');
  const kilos = this.parseWeight(input ? input.value : '');
  this.currentSaleWeightKg = kilos;

  const textoPeso = document.getElementById('calcWeightText');
  const textoSubtotal = document.getElementById('calcSubtotalText');

  if (kilos <= 0) {
    textoPeso.textContent = 'Ingrese peso válido';
    textoSubtotal.textContent = '$0.00';
    return;
  }

  const subtotal = kilos * this.selectedSaleProduct.precio;
  textoPeso.textContent = this.formatWeight(kilos);
  textoSubtotal.textContent = '$' + this.formatCurrency(subtotal);
};

// ----------------------------------------------------------------------------
// 2. Carrito de Compras
// ----------------------------------------------------------------------------
app.addToCart = function() {
  if (!this.selectedSaleProduct) {
    this.showToast('Primero seleccione un producto.', 'warning');
    return;
  }

  const kilos = this.currentSaleWeightKg;
  if (!kilos || kilos <= 0) {
    this.showToast('Ingrese un peso o cantidad válida.', 'warning');
    const input = document.getElementById('inputPesoVenta');
    if (input) input.focus();
    return;
  }

  const p = this.selectedSaleProduct;
  const itemExistente = this.cart.find(item => item.idProducto === p.idProducto);
  const cantidadTotal = (itemExistente ? itemExistente.cantidad : 0) + kilos;

  // Validamos si hay suficiente stock
  if (cantidadTotal > p.stock) {
    this.showToast('Stock insuficiente para "' + p.nombre + '". Disponible: ' + this.formatWeight(p.stock), 'error');
    return;
  }

  if (itemExistente) {
    itemExistente.cantidad = Math.round(cantidadTotal * 1000) / 1000;
    itemExistente.subtotal = Math.round(itemExistente.cantidad * p.precio * 100) / 100;
  } else {
    this.cart.push({
      idProducto: p.idProducto,
      nombre: p.nombre,
      precioUnitario: p.precio,
      cantidad: Math.round(kilos * 1000) / 1000,
      subtotal: Math.round(kilos * p.precio * 100) / 100
    });
  }

  this.renderCart();
  this.showToast('Agregado: ' + p.nombre + ' (' + this.formatWeight(kilos) + ')', 'success');
};

app.removeFromCart = function(id) {
  this.cart = this.cart.filter(item => item.idProducto !== id);
  this.renderCart();
};

app.cancelSale = function() {
  if (this.cart.length === 0) {
    this.showToast('No hay ninguna venta en curso.', 'warning');
    return;
  }
  if (confirm('¿Desea cancelar la venta actual? Se vaciará el carrito.')) {
    this.cart = [];
    this.renderCart();
    this.populateSaleSelector();
    this.showToast('Venta cancelada.', 'warning');
  }
};

app.renderCart = function() {
  const cuerpoTabla = document.getElementById('tbodyCart');
  const textoTotal = document.getElementById('cartTotalText');

  if (!cuerpoTabla) return;
  cuerpoTabla.innerHTML = '';

  if (this.cart.length === 0) {
    cuerpoTabla.innerHTML = `
      <tr>
        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
          No hay productos agregados a la venta.
        </td>
      </tr>
    `;
    if (textoTotal) textoTotal.textContent = '$0.00';
    return;
  }

  let sumaTotal = 0;

  for (let i = 0; i < this.cart.length; i++) {
    const item = this.cart[i];
    const subtotal = item.subtotal !== undefined ? item.subtotal : (item.cantidad * item.precioUnitario);
    sumaTotal += subtotal;

    const fila = document.createElement('tr');
    fila.innerHTML = `
      <td><div style="font-weight: 700; color: #1e293b;">${this.escapeHtml(item.nombre)}</div></td>
      <td>$${this.formatCurrency(item.precioUnitario)}</td>
      <td><strong style="color: #0f172a;">${this.formatWeight(item.cantidad)}</strong></td>
      <td style="font-weight: 700; color: var(--primary);">$${this.formatCurrency(subtotal)}</td>
      <td style="text-align: right;">
        <button class="btn btn-secondary btn-sm" onclick="app.removeFromCart(${item.idProducto})" title="Quitar">✕</button>
      </td>
    `;
    cuerpoTabla.appendChild(fila);
  }

  if (textoTotal) {
    textoTotal.textContent = '$' + this.formatCurrency(sumaTotal);
  }
};

// Confirma la venta y descuenta el stock en la base de datos
app.confirmSale = async function() {
  if (this.cart.length === 0) {
    this.showToast('Agregue productos al carrito antes de cobrar.', 'warning');
    return;
  }

  let total = 0;
  for (let i = 0; i < this.cart.length; i++) {
    total += (this.cart[i].cantidad * this.cart[i].precioUnitario);
  }

  if (!confirm('¿Desea confirmar y registrar la venta por un total de $' + this.formatCurrency(total) + '?')) {
    return;
  }

  const itemsParaGuardar = this.cart.map(function(item) {
    return {
      idProducto: item.idProducto,
      cantidad: item.cantidad
    };
  });

  const data = await this.enviarPeticion('api/ventas.php?action=registrar', {
    items: itemsParaGuardar
  });

  if (data.success) {
    this.showToast(data.message, 'success');
    this.cart = [];
    this.renderCart();
    await this.loadProducts(); // Actualiza el stock descontado
    this.populateSaleSelector();
    this.switchSaleTab('listado'); // Abre el historial para ver la venta registrada
  } else {
    this.showToast(data.message, 'error');
    await this.loadProducts();
    this.populateSaleSelector();
  }
};

// ----------------------------------------------------------------------------
// 3. Historial de Ventas (Listado y Detalle)
// ----------------------------------------------------------------------------
app.loadSales = async function() {
  const data = await this.enviarPeticion('api/ventas.php?action=listar');
  if (data.success) {
    this.sales = data.ventas;
    this.renderSalesTable(this.sales);
  }
};

app.renderSalesTable = function(lista) {
  const cuerpoTabla = document.getElementById('tbodySales');
  const mensajeVacio = document.getElementById('emptySalesMsg');
  const tabla = cuerpoTabla ? cuerpoTabla.closest('table') : null;

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
    const v = lista[i];
    const itemsTexto = v.detalles.map(d => `${d.nombreProducto} (${this.formatWeight(d.cantidad)})`).join(', ');

    const fila = document.createElement('tr');
    fila.innerHTML = `
      <td style="font-weight: 700; color: #64748b;">#${v.idVenta}</td>
      <td style="font-size: 0.88rem; color: #334155;">${this.formatDateTime(v.fecha)}</td>
      <td><span style="font-weight: 600;">${this.escapeHtml(v.nombreAdministrador)}</span></td>
      <td style="font-size: 0.9rem; color: #475569; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${this.escapeHtml(itemsTexto)}">
        ${this.escapeHtml(itemsTexto)}
      </td>
      <td style="font-weight: 800; color: var(--primary); font-size: 1.05rem;">$${this.formatCurrency(v.total)}</td>
      <td style="text-align: center;">
        <button class="btn btn-secondary btn-sm" onclick="app.openSaleDetailModal(${v.idVenta})">👁 Ver</button>
      </td>
    `;
    cuerpoTabla.appendChild(fila);
  }
};

app.openSaleDetailModal = function(id) {
  const sale = this.sales.find(s => s.idVenta === id);
  if (!sale) {
    this.showToast('Venta no encontrada.', 'error');
    return;
  }

  document.getElementById('detailSaleId').textContent = sale.idVenta;
  document.getElementById('detailSaleFecha').textContent = this.formatDateTime(sale.fecha);
  document.getElementById('detailSaleAdmin').textContent = sale.nombreAdministrador;
  document.getElementById('detailSaleTotal').textContent = '$' + this.formatCurrency(sale.total);

  const cuerpoDetalle = document.getElementById('tbodySaleDetailItems');
  if (!cuerpoDetalle) return;
  cuerpoDetalle.innerHTML = '';

  for (let i = 0; i < sale.detalles.length; i++) {
    const d = sale.detalles[i];
    const fila = document.createElement('tr');
    fila.innerHTML = `
      <td><strong>${this.escapeHtml(d.nombreProducto)}</strong></td>
      <td><strong>${this.formatWeight(d.cantidad)}</strong></td>
      <td>$${this.formatCurrency(d.precioUnitario)}</td>
      <td style="font-weight: 700; color: var(--primary);">$${this.formatCurrency(d.subtotal)}</td>
    `;
    cuerpoDetalle.appendChild(fila);
  }

  this.openModal('modalSaleDetail');
};

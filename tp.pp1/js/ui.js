/**
 * ============================================================================
 * DeliStock - Módulo de Interfaz de Usuario (UI)
 * Archivo: js/ui.js
 * Maneja modales, avisos (toast), navegación entre pantallas y formatos
 * ============================================================================
 */

// ----------------------------------------------------------------------------
// 1. Modales (Ventanas Emergentes)
// ----------------------------------------------------------------------------
app.openModal = function(idModal) {
  const modal = document.getElementById(idModal);
  if (modal) {
    modal.classList.add('active');
  }
};

app.closeModal = function(idModal) {
  const modal = document.getElementById(idModal);
  if (modal) {
    modal.classList.remove('active');
  }
};

// ----------------------------------------------------------------------------
// 2. Avisos Flotantes (Toast)
// ----------------------------------------------------------------------------
app.showToast = function(mensaje, tipo = 'success') {
  const contenedor = document.getElementById('toast-container');
  if (!contenedor) return;

  const cartel = document.createElement('div');
  cartel.className = 'toast toast-' + tipo;

  let icono = '✔';
  if (tipo === 'error') icono = '✖';
  if (tipo === 'warning') icono = '⚠';

  cartel.innerHTML = `
    <div style="display: flex; align-items: center; gap: 0.5rem;">
      <span style="font-size: 1.1rem;">${icono}</span>
      <span>${mensaje}</span>
    </div>
    <span class="toast-close">&times;</span>
  `;

  cartel.onclick = function() {
    cartel.remove();
  };

  contenedor.appendChild(cartel);

  setTimeout(function() {
    if (cartel.parentElement) {
      cartel.remove();
    }
  }, 4000);
};

// ----------------------------------------------------------------------------
// 3. Navegación entre Pantallas
// ----------------------------------------------------------------------------
app.navTo = function(vista, subvista = null) {
  // Si no hay usuario logueado, forzamos a ir a Login
  if (!this.user && vista !== 'login') {
    vista = 'login';
  }

  // Ocultamos todas las secciones
  const secciones = document.querySelectorAll('.view-section');
  secciones.forEach(function(sec) {
    sec.classList.remove('active');
  });

  // Mostramos la vista solicitada
  const pantalla = document.getElementById('view-' + vista);
  if (pantalla) {
    pantalla.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  // Si va a Productos o Ventas, cargamos los datos correspondientes
  if (vista === 'productos') {
    this.loadProducts().then(() => {
      if (subvista === 'alta') {
        this.switchProductTab('alta');
      } else {
        this.switchProductTab('listado');
      }
    });
  } else if (vista === 'ventas') {
    this.loadProducts().then(() => {
      this.populateSaleSelector();
      if (subvista === 'listado') {
        this.switchSaleTab('listado');
      } else {
        this.switchSaleTab('registro');
      }
    });
  }
};

// Pestañas de Productos
app.switchProductTab = function(pestaña) {
  const botonAlta = document.getElementById('tabBtnProdAlta');
  const botonListado = document.getElementById('tabBtnProdList');
  const vistaAlta = document.getElementById('subview-prod-alta');
  const vistaListado = document.getElementById('subview-prod-listado');

  if (pestaña === 'alta') {
    if (botonAlta) botonAlta.classList.add('active');
    if (botonListado) botonListado.classList.remove('active');
    if (vistaAlta) vistaAlta.style.display = 'block';
    if (vistaListado) vistaListado.style.display = 'none';
    const form = document.getElementById('formAltaProducto');
    if (form) form.reset();
    const inputNombre = document.getElementById('altaNombre');
    if (inputNombre) inputNombre.focus();
  } else {
    if (botonListado) botonListado.classList.add('active');
    if (botonAlta) botonAlta.classList.remove('active');
    if (vistaListado) vistaListado.style.display = 'block';
    if (vistaAlta) vistaAlta.style.display = 'none';
    this.loadProducts();
  }
};

// Pestañas de Ventas
app.switchSaleTab = function(pestaña) {
  const botonListado = document.getElementById('tabBtnVentaList');
  const botonRegistro = document.getElementById('tabBtnVentaReg');
  const vistaListado = document.getElementById('subview-venta-listado');
  const vistaRegistro = document.getElementById('subview-venta-registro');

  if (pestaña === 'listado') {
    if (botonListado) botonListado.classList.add('active');
    if (botonRegistro) botonRegistro.classList.remove('active');
    if (vistaListado) vistaListado.style.display = 'block';
    if (vistaRegistro) vistaRegistro.style.display = 'none';
    this.loadSales();
  } else {
    if (botonRegistro) botonRegistro.classList.add('active');
    if (botonListado) botonListado.classList.remove('active');
    if (vistaRegistro) vistaRegistro.style.display = 'block';
    if (vistaListado) vistaListado.style.display = 'none';
    this.populateSaleSelector();
  }
};

// ----------------------------------------------------------------------------
// 4. Formateadores y Utilidades
// ----------------------------------------------------------------------------
app.formatWeight = function(valor) {
  const n = Number(valor);
  if (isNaN(n) || n <= 0) return '0 kg';
  if (n < 1) {
    return Math.round(n * 1000) + ' g';
  }
  if (n % 1 !== 0) {
    return n.toLocaleString('es-AR', { minimumFractionDigits: 3, maximumFractionDigits: 3 }) + ' kg';
  }
  return n + ' kg';
};

app.formatCurrency = function(valor) {
  return Number(valor).toLocaleString('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

app.formatDateTime = function(textoFecha) {
  if (!textoFecha) return '-';
  const fecha = new Date(textoFecha.replace(' ', 'T'));
  if (isNaN(fecha.getTime())) return textoFecha;
  return fecha.toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

app.escapeHtml = function(texto) {
  if (!texto) return '';
  return String(texto)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
};

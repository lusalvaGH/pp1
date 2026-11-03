/**
 * ============================================================================
 * DeliStock - Archivo Principal de JavaScript
 * Archivo: js/app.js
 * Define el objeto central "app", el estado global y la inicialización.
 * Los demás módulos añaden sus funcionalidades sobre este objeto:
 *   - js/ui.js        -> Modales, avisos (toast), navegación y formatos
 *   - js/auth.js      -> Sesión de usuario (login y logout)
 *   - js/productos.js -> Catálogo de productos (CRUD y buscador)
 *   - js/ventas.js    -> Mostrador, pesaje, carrito e historial
 * ============================================================================
 */

const app = {
  // Variables en memoria para guardar el estado de la aplicación
  user: null,                   // Datos del administrador logueado
  products: [],               // Catálogo de productos en stock
  sales: [],                  // Historial de ventas
  cart: [],                   // Carrito actual de la venta en curso
  selectedSaleProduct: null,  // Producto seleccionado en el mostrador
  currentSaleWeightKg: 0,     // Peso calculado en kilogramos
  pendingDeleteProductId: null, // ID del producto a eliminar

  // Función general para comunicarse con el servidor (PHP)
  async enviarPeticion(url, datos = null) {
    try {
      let opciones = {};
      if (datos) {
        opciones = {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(datos)
        };
      }
      const respuesta = await fetch(url, opciones);
      return await respuesta.json();
    } catch (error) {
      console.error('Error de conexión:', error);
      return { success: false, message: 'No se pudo conectar con el servidor.' };
    }
  },

  // Inicialización de la aplicación
  async init() {
    // Al abrir la página, comprobamos si ya hay una sesión activa
    await this.checkAuth();
  }
};

// Iniciar la aplicación automáticamente cuando el HTML termine de cargar
document.addEventListener('DOMContentLoaded', function() {
  app.init();
});

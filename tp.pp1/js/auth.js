/**
 * ============================================================================
 * DeliStock - Módulo de Autenticación y Usuarios
 * Archivo: js/auth.js
 * Controla el inicio de sesión, verificación de credenciales y cierre de sesión
 * ============================================================================
 */

// Verifica si el usuario ya tiene sesión abierta en el navegador
app.checkAuth = async function() {
  const data = await this.enviarPeticion('api/auth.php?action=check');
  if (data.authenticated) {
    this.user = data.admin;
    this.actualizarBarraUsuario();
    this.navTo('menu');
  } else {
    this.navTo('login');
  }
};

// Iniciar sesión con usuario y contraseña
app.handleLogin = async function(event) {
  event.preventDefault();

  const usuario = document.getElementById('loginUser').value.trim();
  const contrasena = document.getElementById('loginPass').value.trim();

  const data = await this.enviarPeticion('api/auth.php?action=login', {
    usuario: usuario,
    contrasena: contrasena
  });

  if (data.success) {
    this.user = data.admin;
    this.actualizarBarraUsuario();
    this.showToast(data.message, 'success');
    this.navTo('menu');
  } else {
    this.showToast(data.message, 'error');
  }
};

// Cerrar sesión
app.logout = async function() {
  await this.enviarPeticion('api/auth.php?action=logout');
  this.user = null;
  this.actualizarBarraUsuario();
  this.showToast('Sesión cerrada correctamente.', 'success');
  this.navTo('login');
};

// Muestra u oculta el nombre del usuario en el encabezado
app.actualizarBarraUsuario = function() {
  const seccion = document.getElementById('navUserSection');
  const textoNombre = document.getElementById('loggedUserName');
  if (!seccion) return;

  if (this.user) {
    seccion.style.display = 'flex';
    if (textoNombre) textoNombre.textContent = this.user.nombre || this.user.usuario;
  } else {
    seccion.style.display = 'none';
  }
};

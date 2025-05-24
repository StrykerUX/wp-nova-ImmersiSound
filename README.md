# Nova Sound FX para WordPress

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.0%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Plugin profesional de WordPress para agregar efectos de sonido inmersivos a tu sitio web mediante selectores CSS y transiciones de página.

## 🎵 Características

### Mapeo de Sonidos CSS
- **Solo Clases e IDs**: Asigna sonidos a elementos usando selectores de clase (`.ejemplo`) o ID (`#ejemplo`)
- **Múltiples Eventos**: Soporta hover, click, active, focus, blur, mouseenter, mouseleave, mousedown, mouseup
- **Control de Volumen**: Ajuste individual de volumen para cada sonido
- **Retraso Personalizable**: Configura retrasos antes de reproducir el sonido

### Transiciones de Página
- **Sonidos de Entrada/Salida**: Reproduce sonidos al entrar o salir de páginas
- **Patrones de URL**: Usa wildcards (*) o expresiones regulares
- **Sistema de Prioridades**: Define qué sonidos tienen precedencia
- **Transiciones Suaves**: Overlay visual durante las transiciones

### Controles de Usuario
- **Widget Personalizable**: Tres estilos (minimal, floating, embedded)
- **Preferencias Persistentes**: Guarda configuraciones en localStorage
- **Accesibilidad**: Respeta `prefers-reduced-motion`
- **Modo Móvil**: Opción para habilitar/deshabilitar en dispositivos móviles

### Integración con Gutenberg
- **Bloque de Controles**: Agrega el widget de control fácilmente
- **Bloque de Botón con Sonido**: Crea botones con efectos de sonido personalizados

## 📦 Instalación

1. Descarga el plugin desde GitHub
2. Sube la carpeta `nova-sound-fx` a `/wp-content/plugins/`
3. Activa el plugin desde el panel de WordPress
4. Ve a **Nova Sound FX** en el menú de administración

## 🚀 Uso Rápido

### Agregar un Mapeo de Sonido

1. Ve a **Nova Sound FX → Mapeo de Sonidos CSS**
2. Haz clic en "Agregar Nuevo Mapeo"
3. Ingresa el selector CSS (ej: `.mi-boton`, `#header`)
4. Selecciona el evento (hover, click, etc.)
5. Elige un sonido de tu biblioteca
6. Ajusta volumen y retraso
7. Guarda el mapeo

### Usar el Shortcode de Controles

```php
[nova_sound_fx_controls style="minimal" position="bottom-right" theme="light"]
```

**Parámetros:**
- `style`: minimal, floating, embedded
- `position`: top-left, top-right, bottom-left, bottom-right
- `theme`: light, dark
- `show_volume`: yes, no
- `show_save`: yes, no

### Configurar Transiciones de Página

1. Ve a **Nova Sound FX → Transiciones de Página**
2. Configura sonidos globales o por URL específica
3. Usa patrones como:
   - `/404` - Página 404
   - `*/shop/*` - Todas las páginas de tienda
   - `regex:.*\.pdf$` - Enlaces a PDFs

## 🛠️ Configuración Avanzada

### API JavaScript

El plugin expone una API global para desarrolladores:

```javascript
// Reproducir un sonido
window.NovaSoundFX.play(url, options);

// Control de volumen
window.NovaSoundFX.setVolume(50);
window.NovaSoundFX.mute();
window.NovaSoundFX.unmute();

// Guardar preferencias
window.NovaSoundFX.savePreferences();

// Recargar mapeos dinámicamente
window.NovaSoundFX.reload();
```

### Hooks y Filtros

```php
// Filtrar configuraciones antes de guardar
add_filter('nova_sound_fx_settings', 'mi_funcion');

// Acción después de guardar un mapeo
add_action('nova_sound_fx_mapping_saved', 'mi_callback');

// Modificar datos de sonido antes de enviar al frontend
add_filter('nova_sound_fx_sound_data', 'modificar_datos');
```

## 🔧 Requisitos

- WordPress 5.0 o superior
- PHP 7.0 o superior
- Navegador con soporte para Web Audio API
- jQuery (incluido en WordPress)

## 🐛 Solución de Problemas

### Los sonidos no se reproducen
1. Verifica que los sonidos estén habilitados en la configuración
2. Asegúrate de que el navegador permita la reproducción automática
3. Revisa la consola del navegador para errores
4. Confirma que los archivos de audio existen y son accesibles

### Los selectores CSS no funcionan
- Solo se permiten clases (`.clase`) e IDs (`#id`)
- No uses selectores de elementos (div, p, etc.)
- Verifica que los elementos existan en la página
- Usa el modo preview para ver elementos marcados

### Conflictos con otros plugins
- Desactiva el modo preview si no eres administrador
- Ajusta la prioridad de carga de scripts
- Contacta soporte con detalles del conflicto

## 📝 Changelog

### v1.0.0 (2024)
- Lanzamiento inicial con todas las características principales
- Soporte completo para mapeo CSS (solo clases e IDs)
- Sistema de transiciones de página
- Widget de controles personalizables
- Integración con Gutenberg
- API JavaScript pública

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este plugin está licenciado bajo GPL v2 o posterior.

## 👨‍💻 Desarrollado por

Plugin desarrollado con más de 12 años de experiencia en WordPress, siguiendo las mejores prácticas y estándares de codificación.

## 🙏 Agradecimientos

- WordPress Community
- Web Audio API Contributors
- Todos los beta testers

---

**¿Necesitas ayuda?** Abre un issue en GitHub o contacta a soporte@tu-sitio.com

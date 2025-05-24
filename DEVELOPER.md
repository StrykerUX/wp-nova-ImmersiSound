# Guía del Desarrollador - Nova Sound FX

## Arquitectura del Plugin

Nova Sound FX sigue la arquitectura estándar de plugins de WordPress con un enfoque orientado a objetos.

### Estructura de Directorios

```
nova-sound-fx/
├── admin/               # Assets y funcionalidad del admin
│   ├── css/            # Estilos del panel de administración
│   └── js/             # Scripts del panel de administración
├── includes/           # Clases principales del plugin
├── languages/          # Archivos de traducción
├── public/             # Assets y funcionalidad del frontend
│   ├── css/           # Estilos del frontend
│   └── js/            # Scripts del frontend
├── nova-sound-fx.php   # Archivo principal del plugin
└── uninstall.php      # Script de desinstalación
```

### Clases Principales

- **Nova_Sound_FX**: Clase principal que inicializa el plugin
- **Nova_Sound_FX_Admin**: Maneja toda la funcionalidad del admin
- **Nova_Sound_FX_Public**: Maneja la funcionalidad del frontend
- **Nova_Sound_FX_Shortcodes**: Gestiona los shortcodes
- **Nova_Sound_FX_Ajax**: Maneja las peticiones AJAX
- **Nova_Sound_FX_Utils**: Funciones de utilidad
- **Nova_Sound_FX_Blocks**: Integración con Gutenberg

## Base de Datos

### Tablas Personalizadas

#### nova_sound_fx_css_mappings
```sql
- id (mediumint)
- css_selector (varchar 255)
- event_type (varchar 50)
- sound_id (mediumint)
- volume (int)
- delay (int)
- created_at (datetime)
```

#### nova_sound_fx_transitions
```sql
- id (mediumint)
- url_pattern (varchar 255)
- transition_type (varchar 50)
- sound_id (mediumint)
- volume (int)
- priority (int)
- created_at (datetime)
```

## API JavaScript

### Objeto Global NovaSoundFX

```javascript
window.NovaSoundFX = {
    // Reproducir sonido
    play: function(url, options) {},
    
    // Control de volumen
    setVolume: function(volume) {},
    mute: function() {},
    unmute: function() {},
    
    // Estado
    isMuted: function() {},
    getVolume: function() {},
    
    // Preferencias
    savePreferences: function() {},
    
    // Recarga dinámica
    reload: function() {}
}
```

### Eventos Personalizados

```javascript
// Preferencias guardadas
$(document).on('nova-sound-fx:preferences-saved', function(event, preferences) {
    console.log('Nuevas preferencias:', preferences);
});

// Sonido reproducido
$(document).on('nova-sound-fx:sound-played', function(event, data) {
    console.log('Sonido reproducido:', data);
});
```

## Hooks y Filtros

### Acciones (Actions)

```php
// Después de guardar un mapeo CSS
do_action('nova_sound_fx_mapping_saved', $mapping_id, $data);

// Después de guardar una transición
do_action('nova_sound_fx_transition_saved', $transition_id, $data);

// Antes de reproducir un sonido (frontend)
do_action('nova_sound_fx_before_play', $sound_id, $context);
```

### Filtros (Filters)

```php
// Modificar configuraciones antes de guardar
$settings = apply_filters('nova_sound_fx_settings', $settings);

// Modificar datos de mapeo antes de guardar
$mapping_data = apply_filters('nova_sound_fx_mapping_data', $data);

// Modificar selectores CSS permitidos
$allowed = apply_filters('nova_sound_fx_allowed_selectors', $pattern);

// Modificar tipos de evento disponibles
$events = apply_filters('nova_sound_fx_event_types', $default_events);
```

## Desarrollo de Extensiones

### Crear un Addon

```php
// my-nova-addon.php
class My_Nova_Addon {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Verificar que Nova Sound FX esté activo
        if (!class_exists('Nova_Sound_FX')) {
            return;
        }
        
        // Agregar nuevo tipo de evento
        add_filter('nova_sound_fx_event_types', array($this, 'add_custom_events'));
        
        // Agregar campos personalizados
        add_action('nova_sound_fx_mapping_fields', array($this, 'add_custom_fields'));
    }
    
    public function add_custom_events($events) {
        $events['double-click'] = __('Doble Click', 'my-addon');
        $events['long-press'] = __('Presión Larga', 'my-addon');
        return $events;
    }
}

new My_Nova_Addon();
```

### Integración con Temas

```php
// functions.php del tema
class My_Theme_Nova_Integration {
    
    public function __construct() {
        add_action('after_setup_theme', array($this, 'setup'));
    }
    
    public function setup() {
        // Agregar soporte
        add_theme_support('nova-sound-fx');
        
        // Personalizar carga de scripts
        add_filter('nova_sound_fx_load_scripts', array($this, 'conditional_load'));
        
        // Agregar sonidos predeterminados del tema
        add_action('nova_sound_fx_default_sounds', array($this, 'register_theme_sounds'));
    }
    
    public function conditional_load($load) {
        // Solo cargar en páginas específicas
        if (is_page_template('interactive-template.php')) {
            return true;
        }
        return $load;
    }
    
    public function register_theme_sounds() {
        // Registrar sonidos del tema
        nova_sound_fx_register_sound(array(
            'id' => 'theme-click',
            'url' => get_template_directory_uri() . '/sounds/click.mp3',
            'title' => 'Theme Click Sound'
        ));
    }
}
```

## AJAX Endpoints

### Admin AJAX

```javascript
// Obtener biblioteca de sonidos
$.ajax({
    url: nova_sound_fx_admin.ajax_url,
    type: 'POST',
    data: {
        action: 'nova_sound_fx_get_sound_library',
        nonce: nova_sound_fx_admin.nonce
    },
    success: function(response) {
        console.log(response.data);
    }
});

// Guardar mapeo CSS
$.ajax({
    url: nova_sound_fx_admin.ajax_url,
    type: 'POST',
    data: {
        action: 'nova_sound_fx_save_css_mapping',
        nonce: nova_sound_fx_admin.nonce,
        css_selector: '.mi-clase',
        event_type: 'click',
        sound_id: 123,
        volume: 80,
        delay: 0
    }
});
```

### Public AJAX

```javascript
// Obtener sonidos para el frontend
$.ajax({
    url: novaSoundFX.ajaxUrl,
    type: 'POST',
    data: {
        action: 'nova_sound_fx_get_sounds',
        nonce: novaSoundFX.nonce
    }
});
```

## Guía de Estilo de Código

### PHP

- Seguir [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Usar PHPDoc para todas las funciones y clases
- Prefijo `nova_sound_fx_` para funciones globales
- Validar y sanitizar todas las entradas

```php
/**
 * Guardar configuración de sonido
 *
 * @param array $data Datos del sonido
 * @return int|WP_Error ID del sonido o error
 */
function nova_sound_fx_save_sound($data) {
    // Validar datos
    if (empty($data['url'])) {
        return new WP_Error('missing_url', __('URL requerida', 'nova-sound-fx'));
    }
    
    // Sanitizar
    $clean_data = array(
        'url' => esc_url_raw($data['url']),
        'title' => sanitize_text_field($data['title']),
        'volume' => absint($data['volume'])
    );
    
    // Guardar
    return wp_insert_post($clean_data);
}
```

### JavaScript

- Usar closures para evitar contaminar el scope global
- Documentar con JSDoc
- Manejar errores apropiadamente

```javascript
/**
 * Módulo de gestión de sonidos
 * @namespace NovaSoundManager
 */
(function($) {
    'use strict';
    
    const NovaSoundManager = {
        /**
         * Inicializar módulo
         * @returns {void}
         */
        init: function() {
            this.bindEvents();
            this.loadSounds();
        },
        
        /**
         * Cargar sonidos desde el servidor
         * @returns {Promise}
         */
        loadSounds: async function() {
            try {
                const response = await $.ajax({
                    url: novaSoundFX.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'nova_sound_fx_get_sounds',
                        nonce: novaSoundFX.nonce
                    }
                });
                
                return response.data;
            } catch (error) {
                console.error('Error loading sounds:', error);
                return [];
            }
        }
    };
    
    // Inicializar cuando DOM esté listo
    $(document).ready(() => NovaSoundManager.init());
    
})(jQuery);
```

## Testing

### Unit Tests

```php
// tests/test-nova-sound-fx.php
class Test_Nova_Sound_FX extends WP_UnitTestCase {
    
    public function setUp() {
        parent::setUp();
        // Activar plugin
        activate_plugin('nova-sound-fx/nova-sound-fx.php');
    }
    
    public function test_tables_created() {
        global $wpdb;
        $table = $wpdb->prefix . 'nova_sound_fx_css_mappings';
        $this->assertEquals($table, $wpdb->get_var("SHOW TABLES LIKE '$table'"));
    }
    
    public function test_save_mapping() {
        $data = array(
            'css_selector' => '.test-button',
            'event_type' => 'click',
            'sound_id' => 123,
            'volume' => 80,
            'delay' => 0
        );
        
        $result = Nova_Sound_FX::save_css_mapping($data);
        $this->assertNotFalse($result);
    }
}
```

### Ejecutar Tests

```bash
# PHPUnit
composer test

# JavaScript
npm test

# Linting
composer check-cs
npm run lint
```

## Depuración

### Habilitar Modo Debug

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('NOVA_SOUND_FX_DEBUG', true);
```

### Logs Personalizados

```php
// Usar la utilidad de logging
Nova_Sound_FX_Utils::log('Mensaje de debug', 'info');
Nova_Sound_FX_Utils::log($data_array, 'debug');
Nova_Sound_FX_Utils::log('Error crítico', 'error');
```

### Console Debugging (JavaScript)

```javascript
// Habilitar modo debug
window.NOVA_SOUND_FX_DEBUG = true;

// Los logs aparecerán en la consola
if (window.NOVA_SOUND_FX_DEBUG) {
    console.log('[Nova Sound FX]', 'Debug info', data);
}
```

## Optimización

### Caché de Sonidos

```php
// Implementar caché para consultas frecuentes
function nova_sound_fx_get_cached_mappings() {
    $cache_key = 'nova_sound_fx_mappings';
    $mappings = wp_cache_get($cache_key);
    
    if (false === $mappings) {
        $mappings = Nova_Sound_FX::get_css_mappings();
        wp_cache_set($cache_key, $mappings, '', HOUR_IN_SECONDS);
    }
    
    return $mappings;
}
```

### Lazy Loading de Assets

```javascript
// Cargar sonidos solo cuando se necesiten
class LazyAudioLoader {
    constructor() {
        this.loaded = new Map();
    }
    
    async load(url) {
        if (this.loaded.has(url)) {
            return this.loaded.get(url);
        }
        
        const audio = new Audio(url);
        await audio.load();
        this.loaded.set(url, audio);
        
        return audio;
    }
}
```

## Seguridad

### Validación de Entrada

```php
// Siempre validar selectores CSS
if (!Nova_Sound_FX_Utils::validate_css_selector($selector)) {
    wp_die(__('Selector CSS inválido', 'nova-sound-fx'));
}

// Verificar capacidades
if (!current_user_can('manage_options')) {
    wp_die(__('Permisos insuficientes', 'nova-sound-fx'));
}

// Verificar nonces
check_ajax_referer('nova_sound_fx_admin', 'nonce');
```

### Escape de Salida

```php
// Siempre escapar datos antes de mostrar
echo esc_html($user_input);
echo esc_attr($attribute);
echo esc_url($url);
echo esc_js($javascript_data);
```

## Recursos

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Web Audio API Documentation](https://developer.mozilla.org/en-US/docs/Web/API/Web_Audio_API)
- [WordPress Security Best Practices](https://developer.wordpress.org/plugins/security/)

---

¿Preguntas? Abre un issue en GitHub o contacta al equipo de desarrollo.

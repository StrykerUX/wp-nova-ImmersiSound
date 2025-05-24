# Guía de Instalación - Nova Sound FX

## Requisitos Previos

Antes de instalar Nova Sound FX, asegúrate de que tu sistema cumple con los siguientes requisitos:

- **WordPress**: 5.0 o superior
- **PHP**: 7.0 o superior
- **MySQL**: 5.6 o superior / MariaDB 10.0 o superior
- **Navegador**: Chrome 66+, Firefox 60+, Safari 11+, Edge 79+
- **Memoria PHP**: Al menos 64MB (128MB recomendado)

## Instalación Manual

### Paso 1: Descargar el Plugin

1. Descarga el archivo ZIP del plugin desde GitHub
2. O clona el repositorio:
   ```bash
   git clone https://github.com/tu-usuario/nova-sound-fx.git
   ```

### Paso 2: Subir a WordPress

#### Opción A: Desde el Panel de WordPress

1. Ve a **Plugins → Añadir nuevo** en tu panel de WordPress
2. Haz clic en **Subir plugin**
3. Selecciona el archivo ZIP descargado
4. Haz clic en **Instalar ahora**
5. Activa el plugin cuando se complete la instalación

#### Opción B: Vía FTP/SFTP

1. Descomprime el archivo ZIP
2. Sube la carpeta `nova-sound-fx` a `/wp-content/plugins/`
3. Ve a **Plugins** en tu panel de WordPress
4. Busca "Nova Sound FX" y haz clic en **Activar**

### Paso 3: Verificar la Instalación

1. Después de activar, deberías ver "Nova Sound FX" en el menú de administración
2. Ve a **Nova Sound FX → Configuración**
3. Verifica que todas las pestañas se carguen correctamente

## Instalación con Composer

Si usas Composer en tu proyecto WordPress:

```bash
composer require nova/sound-fx
```

## Configuración Inicial

### 1. Subir Archivos de Audio

1. Ve a **Medios → Añadir nuevo**
2. Sube tus archivos de audio (MP3, WAV)
3. Organiza tus sonidos con categorías descriptivas

### 2. Crear tu Primer Mapeo

1. Ve a **Nova Sound FX → Mapeo de Sonidos CSS**
2. Haz clic en **Agregar Nuevo Mapeo**
3. Configura:
   - **Selector CSS**: `.mi-boton` o `#mi-id`
   - **Evento**: Click, Hover, etc.
   - **Sonido**: Selecciona de tu biblioteca
   - **Volumen**: Ajusta según necesidad

### 3. Agregar Controles de Usuario

Agrega el shortcode en cualquier página o entrada:

```
[nova_sound_fx_controls style="minimal" position="bottom-right"]
```

O usa el bloque de Gutenberg "Nova Sound FX Controls"

## Solución de Problemas de Instalación

### Error: "El plugin no se ha podido activar"

**Causa**: Versión de PHP incompatible
**Solución**: Actualiza PHP a 7.0 o superior

### Error: "Tabla no encontrada"

**Causa**: Las tablas no se crearon correctamente
**Solución**: 
1. Desactiva y reactiva el plugin
2. Si persiste, ejecuta manualmente:

```sql
CREATE TABLE wp_nova_sound_fx_css_mappings (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    css_selector varchar(255) NOT NULL,
    event_type varchar(50) NOT NULL,
    sound_id mediumint(9) NOT NULL,
    volume int(3) DEFAULT 100,
    delay int(5) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY css_selector (css_selector),
    KEY event_type (event_type)
);

CREATE TABLE wp_nova_sound_fx_transitions (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    url_pattern varchar(255) NOT NULL,
    transition_type varchar(50) NOT NULL,
    sound_id mediumint(9) NOT NULL,
    volume int(3) DEFAULT 100,
    priority int(3) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY url_pattern (url_pattern),
    KEY transition_type (transition_type)
);
```

### Los sonidos no se cargan

**Verificar**:
1. Los archivos de audio son accesibles (prueba la URL directamente)
2. CORS está configurado correctamente si usas CDN
3. Los archivos no superan el límite de subida de PHP

## Configuración de Permisos

Asegúrate de que WordPress tenga permisos de escritura en:
- `/wp-content/uploads/` (para archivos de audio)
- `/wp-content/plugins/nova-sound-fx/` (para actualizaciones)

## Integración con Temas

### Añadir soporte en tu tema

En `functions.php`:

```php
// Añadir soporte para Nova Sound FX
add_theme_support('nova-sound-fx');

// Cargar sonidos solo en ciertas páginas
add_filter('nova_sound_fx_load_scripts', function($load) {
    if (is_page('contacto')) {
        return true;
    }
    return $load;
});
```

## Optimización del Rendimiento

### 1. Comprimir archivos de audio

- Usa MP3 a 128kbps para efectos cortos
- Limita la duración a menos de 5 segundos
- Considera usar formato OGG para mejor compresión

### 2. Lazy Loading

El plugin carga sonidos bajo demanda, pero puedes precargar sonidos críticos:

```javascript
// En tu tema o plugin personalizado
document.addEventListener('DOMContentLoaded', function() {
    if (window.NovaSoundFX) {
        window.NovaSoundFX.preload([
            'url-del-sonido-1.mp3',
            'url-del-sonido-2.mp3'
        ]);
    }
});
```

## Actualización

### Actualización Automática

El plugin soporta actualizaciones automáticas de WordPress. Cuando haya una nueva versión:
1. Ve a **Plugins**
2. Verás una notificación de actualización
3. Haz clic en **Actualizar ahora**

### Actualización Manual

1. Desactiva el plugin actual
2. Elimina la carpeta antigua `/wp-content/plugins/nova-sound-fx/`
3. Sube la nueva versión
4. Activa el plugin

**Nota**: Tus configuraciones y mapeos se conservarán en la base de datos.

## Desinstalación

Si necesitas desinstalar completamente el plugin:

1. Ve a **Plugins**
2. Desactiva Nova Sound FX
3. Haz clic en **Eliminar**

Esto eliminará:
- Todos los archivos del plugin
- Las tablas de la base de datos
- Las opciones guardadas

**Importante**: Los archivos de audio subidos permanecerán en tu biblioteca de medios.

## Soporte

Si encuentras problemas durante la instalación:

1. Revisa los logs de error de WordPress
2. Activa `WP_DEBUG` para más información
3. Abre un issue en GitHub con:
   - Versión de WordPress
   - Versión de PHP
   - Mensaje de error completo
   - Pasos para reproducir el problema

---

¡Gracias por instalar Nova Sound FX! 🎵

# Nova Sound FX - Developer Documentation

## Plugin Architecture

The Nova Sound FX plugin follows WordPress coding standards and uses an object-oriented approach with the following main classes:

### Core Classes

1. **Nova_Sound_FX** - Main plugin class that initializes all components
2. **Nova_Sound_FX_Admin** - Handles all admin functionality
3. **Nova_Sound_FX_Public** - Handles frontend functionality
4. **Nova_Sound_FX_Shortcodes** - Manages shortcode rendering
5. **Nova_Sound_FX_Ajax** - Handles all AJAX requests
6. **Nova_Sound_FX_Utils** - Utility functions and helpers
7. **Nova_Sound_FX_Blocks** - Gutenberg block integration

## Database Schema

### Table: {prefix}_nova_sound_fx_css_mappings

| Field | Type | Description |
|-------|------|-------------|
| id | mediumint(9) | Primary key |
| css_selector | varchar(255) | CSS selector string |
| event_type | varchar(50) | Event type (hover, click, etc.) |
| sound_id | mediumint(9) | WordPress attachment ID |
| volume | int(3) | Volume level (0-100) |
| delay | int(5) | Delay in milliseconds |
| created_at | datetime | Creation timestamp |

### Table: {prefix}_nova_sound_fx_transitions

| Field | Type | Description |
|-------|------|-------------|
| id | mediumint(9) | Primary key |
| url_pattern | varchar(255) | URL pattern with wildcards or regex |
| transition_type | varchar(50) | Type (enter, exit, both) |
| sound_id | mediumint(9) | WordPress attachment ID |
| volume | int(3) | Volume level (0-100) |
| priority | int(3) | Priority for pattern matching |
| created_at | datetime | Creation timestamp |

## JavaScript API

### Global Object: window.NovaSoundFX

```javascript
// Play a sound
window.NovaSoundFX.play(soundUrl, {
    volume: 0.5,      // 0-1
    element: element  // Optional DOM element for visual feedback
});

// Volume control
window.NovaSoundFX.setVolume(50);     // 0-100
window.NovaSoundFX.getVolume();       // Returns current volume

// Mute control
window.NovaSoundFX.mute();
window.NovaSoundFX.unmute();
window.NovaSoundFX.isMuted();         // Returns boolean

// Save preferences
window.NovaSoundFX.savePreferences();
```

### Events

```javascript
// Preferences saved
jQuery(document).on('nova-sound-fx:preferences-saved', function(e, preferences) {
    console.log('New preferences:', preferences);
});

// Sound played
jQuery(document).on('nova-sound-fx:sound-played', function(e, data) {
    console.log('Sound played:', data.url);
});
```

## PHP Hooks

### Filters

```php
// Modify CSS mappings before output
add_filter('nova_sound_fx_css_mappings', function($mappings) {
    // Modify mappings
    return $mappings;
});

// Modify transitions before output
add_filter('nova_sound_fx_transitions', function($transitions) {
    // Modify transitions
    return $transitions;
});

// Modify sound data before frontend output
add_filter('nova_sound_fx_sound_data', function($data) {
    // Modify data
    return $data;
});

// Customize default user preferences
add_filter('nova_sound_fx_default_preferences', function($preferences) {
    $preferences['volume'] = 75;
    return $preferences;
});

// Modify supported mime types
add_filter('nova_sound_fx_mime_types', function($types) {
    $types[] = 'audio/aac';
    return $types;
});
```

### Actions

```php
// After CSS mapping saved
do_action('nova_sound_fx_mapping_saved', $mapping_id, $data);

// After CSS mapping deleted
do_action('nova_sound_fx_mapping_deleted', $mapping_id);

// After transition saved
do_action('nova_sound_fx_transition_saved', $transition_id, $data);

// After transition deleted
do_action('nova_sound_fx_transition_deleted', $transition_id);

// After settings saved
do_action('nova_sound_fx_settings_saved', $settings);

// When usage is tracked
do_action('nova_sound_fx_usage_tracked', $event_data);
```

## AJAX Endpoints

### Admin Endpoints (require admin capabilities)

- `nova_sound_fx_save_css_mapping` - Save CSS mapping
- `nova_sound_fx_delete_css_mapping` - Delete CSS mapping
- `nova_sound_fx_save_transition` - Save page transition
- `nova_sound_fx_delete_transition` - Delete page transition
- `nova_sound_fx_get_sound_library` - Get all sounds
- `nova_sound_fx_save_settings` - Save plugin settings

### Public Endpoints

- `nova_sound_fx_get_sounds` - Get sounds for frontend
- `nova_sound_fx_log_error` - Log JavaScript errors
- `nova_sound_fx_track_usage` - Track sound usage

## Creating Custom Integrations

### Example: WooCommerce Integration

```php
// Add sound to add-to-cart button
add_action('init', function() {
    if (!class_exists('Nova_Sound_FX')) {
        return;
    }
    
    // Add mapping for WooCommerce buttons
    Nova_Sound_FX::save_css_mapping(array(
        'css_selector' => '.add_to_cart_button',
        'event_type' => 'click',
        'sound_id' => 123, // Your sound ID
        'volume' => 80,
        'delay' => 0
    ));
});
```

### Example: Custom Sound Trigger

```javascript
// Trigger sound on custom event
document.addEventListener('my-custom-event', function(e) {
    if (window.NovaSoundFX) {
        window.NovaSoundFX.play('/path/to/sound.mp3', {
            volume: 0.7,
            element: e.target
        });
    }
});
```

## Gutenberg Blocks

### Sound Button Block

```
<!-- wp:nova-sound-fx/sound-button {
    "text":"Play Sound",
    "soundId":123,
    "volume":80,
    "align":"center"
} /-->
```

### Sound Controls Block

```
<!-- wp:nova-sound-fx/sound-controls {
    "style":"floating",
    "position":"bottom-right",
    "theme":"dark",
    "showVolume":true,
    "showSave":true
} /-->
```

## Performance Optimization

### Preloading

The plugin automatically preloads the first 5 sounds. To modify:

```javascript
// In nova-sound-fx-public.js
preloadSounds() {
    // Modify the slice value to preload more/fewer sounds
    const soundUrls = Object.values(this.sounds).slice(0, 10);
}
```

### Lazy Loading

Sounds are loaded on-demand using the Web Audio API. To implement custom caching:

```javascript
// Custom cache implementation
const soundCache = new Map();

function loadSoundWithCache(url) {
    if (soundCache.has(url)) {
        return Promise.resolve(soundCache.get(url));
    }
    
    return fetch(url)
        .then(response => response.arrayBuffer())
        .then(buffer => {
            soundCache.set(url, buffer);
            return buffer;
        });
}
```

## Security Considerations

1. All AJAX requests use nonces for security
2. Capability checks are performed for admin actions
3. Input is sanitized using WordPress functions
4. CSS selectors are validated before storage
5. File uploads use WordPress Media Library

## Debugging

### Enable Debug Mode

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('NOVA_SOUND_FX_DEBUG', true);
```

### JavaScript Console

```javascript
// Enable verbose logging
window.novaSoundFXDebug = true;
```

### Log Files

- Error log: `/wp-content/nova-sound-fx-errors.log`
- Debug log: `/wp-content/nova-sound-fx-debug.log`

## Browser Compatibility

The plugin uses feature detection and provides fallbacks:

```javascript
// Web Audio API with fallback
if (window.AudioContext || window.webkitAudioContext) {
    // Use Web Audio API
} else {
    // Fallback to HTML5 Audio
}
```

## Contributing

1. Follow WordPress Coding Standards
2. Use proper sanitization and escaping
3. Add inline documentation
4. Test on multiple browsers
5. Ensure accessibility compliance

## License

GPL v2 or later - Same as WordPress

## Support

For bug reports and feature requests, please use the GitHub issue tracker.

# Nova Sound FX - WordPress Plugin

Add immersive sound effects to your WordPress website with CSS selectors and page transitions.

## Features

- **CSS-based Sound Mapping**: Assign sound effects to any element using CSS selectors
- **Event Support**: Trigger sounds on hover, click, focus, blur, and more
- **Page Transitions**: Add entry and exit sounds to pages with URL pattern matching
- **User Preferences**: Allow visitors to control volume and save their preferences
- **Media Library Integration**: Uses WordPress media library for sound management
- **Performance Optimized**: Uses Web Audio API with HTML5 Audio fallback
- **Accessibility**: Respects prefers-reduced-motion and includes mute controls
- **Mobile Support**: Optional mobile sound effects with optimized performance

## Installation

1. Upload the `nova-sound-fx` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Nova Sound FX' in the WordPress admin menu

## Usage

### 1. Upload Sounds

- Go to Nova Sound FX → Sound Library
- Click "Upload New Sound" to add MP3 or WAV files
- Sounds are stored in your WordPress Media Library

### 2. CSS Sound Mapping

Map sounds to elements using CSS selectors:

1. Go to Nova Sound FX → CSS Sound Mapping
2. Click "Add New Mapping"
3. Enter a CSS selector (e.g., `.button`, `#submit-form`)
4. Choose an event type (hover, click, focus, etc.)
5. Select a sound and adjust volume/delay

### 3. Page Transitions

Add sounds when users navigate between pages:

1. Go to Nova Sound FX → Page Transitions
2. Set global entry/exit sounds (optional)
3. Add URL-specific patterns for custom sounds:
   - Use wildcards: `*/about/*`
   - Use regex: `regex:.*\.pdf$`
   - Specific pages: `/404`

### 4. User Controls Shortcode

Add a control widget for users to adjust sound settings:

```
[nova_sound_fx_controls]
```

Shortcode attributes:
- `style`: minimal, floating, or embedded (default: minimal)
- `position`: top-left, top-right, bottom-left, bottom-right (default: bottom-right)
- `theme`: light or dark (default: light)
- `show_volume`: yes or no (default: yes)
- `show_save`: yes or no (default: yes)

Example:
```
[nova_sound_fx_controls style="floating" position="top-right" theme="dark"]
```

## JavaScript API

The plugin exposes a global JavaScript API:

```javascript
// Play a sound
window.NovaSoundFX.play(soundUrl, options);

// Set volume (0-100)
window.NovaSoundFX.setVolume(50);

// Mute/unmute
window.NovaSoundFX.mute();
window.NovaSoundFX.unmute();

// Check mute status
const isMuted = window.NovaSoundFX.isMuted();

// Get current volume
const volume = window.NovaSoundFX.getVolume();

// Save user preferences
window.NovaSoundFX.savePreferences();
```

## Settings

Configure global options in Nova Sound FX → Settings:

- **Enable Sounds**: Master switch for all sound effects
- **Default Volume**: Initial volume level (0-100)
- **Enable on Mobile**: Allow sounds on mobile devices
- **Respect Accessibility**: Disable for users with reduced motion preference
- **Preview Mode**: Test sounds as admin before enabling for all users

## Database Tables

The plugin creates two custom tables:

- `{prefix}_nova_sound_fx_css_mappings`: Stores CSS selector sound mappings
- `{prefix}_nova_sound_fx_transitions`: Stores page transition sounds

## Browser Compatibility

- Chrome 45+
- Firefox 40+
- Safari 9+
- Edge 12+
- Mobile browsers with Web Audio API support

## Performance Considerations

- Sounds are preloaded using Web Audio API for instant playback
- First 5 sounds are automatically preloaded
- Larger sound libraries may impact initial page load
- Recommended: Use compressed MP3 files under 500KB

## Troubleshooting

**Sounds not playing:**
- Check browser console for errors
- Ensure sounds are enabled in settings
- Verify browser autoplay policies
- Test with preview mode first

**Performance issues:**
- Reduce number of simultaneous sounds
- Use smaller, compressed audio files
- Disable sounds on mobile devices
- Limit preloading for large libraries

## Developer Hooks

### Filters

```php
// Modify sound data before output
add_filter('nova_sound_fx_sound_data', 'my_custom_function');

// Customize user preferences
add_filter('nova_sound_fx_default_preferences', 'my_preferences');
```

### Actions

```php
// After sound mapping saved
do_action('nova_sound_fx_mapping_saved', $mapping_id, $data);

// After transition saved
do_action('nova_sound_fx_transition_saved', $transition_id, $data);
```

## Changelog

### 1.0.0
- Initial release
- CSS sound mapping
- Page transitions
- User preference controls
- WordPress Media Library integration

## License

GPL v2 or later

## Credits

Developed with ❤️ for immersive web experiences.

Icons from Feather Icons (https://feathericons.com/)

<?php
/**
 * Shortcodes functionality
 */
class Nova_Sound_FX_Shortcodes {
    
    private $version;
    
    public function __construct($version) {
        $this->version = $version;
    }
    
    /**
     * Render controls shortcode
     * [nova_sound_fx_controls style="minimal|floating|embedded" position="top-left|top-right|bottom-left|bottom-right" theme="light|dark"]
     */
    public function render_controls($atts) {
        $settings = get_option('nova_sound_fx_settings', array());
        
        // Check if sounds are enabled
        if (empty($settings['enable_sounds'])) {
            return '';
        }
        
        // Check preview mode
        if (!empty($settings['preview_mode']) && !current_user_can('manage_options')) {
            return '';
        }
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'style' => 'minimal',
            'position' => 'bottom-right',
            'theme' => 'light',
            'show_volume' => 'yes',
            'show_save' => 'yes'
        ), $atts, 'nova_sound_fx_controls');
        
        // Generate unique ID for this instance
        $widget_id = 'nova-sound-fx-controls-' . uniqid();
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr($widget_id); ?>" 
             class="nova-sound-fx-controls nova-style-<?php echo esc_attr($atts['style']); ?> nova-position-<?php echo esc_attr($atts['position']); ?> nova-theme-<?php echo esc_attr($atts['theme']); ?>"
             data-style="<?php echo esc_attr($atts['style']); ?>"
             data-position="<?php echo esc_attr($atts['position']); ?>">
            
            <div class="nova-controls-wrapper">
                <!-- Mute/Unmute Button -->
                <button class="nova-control-btn nova-mute-btn" title="<?php esc_attr_e('Toggle Sound', 'nova-sound-fx'); ?>">
                    <svg class="nova-icon nova-icon-volume-on" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                    <svg class="nova-icon nova-icon-volume-off" style="display:none;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <line x1="23" y1="9" x2="17" y2="15"></line>
                        <line x1="17" y1="9" x2="23" y2="15"></line>
                    </svg>
                </button>
                
                <?php if ($atts['show_volume'] === 'yes'): ?>
                <!-- Volume Slider -->
                <div class="nova-volume-container">
                    <input type="range" 
                           class="nova-volume-slider" 
                           min="0" 
                           max="100" 
                           value="<?php echo esc_attr($settings['default_volume'] ?? 50); ?>"
                           title="<?php esc_attr_e('Volume', 'nova-sound-fx'); ?>">
                    <span class="nova-volume-value"><?php echo esc_html($settings['default_volume'] ?? 50); ?>%</span>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_save'] === 'yes'): ?>
                <!-- Save Button -->
                <button class="nova-control-btn nova-save-btn" title="<?php esc_attr_e('Save Preferences', 'nova-sound-fx'); ?>">
                    <svg class="nova-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                </button>
                <?php endif; ?>
                
                <!-- Expand/Collapse for minimal style -->
                <?php if ($atts['style'] === 'minimal'): ?>
                <button class="nova-control-btn nova-expand-btn" title="<?php esc_attr_e('Expand Controls', 'nova-sound-fx'); ?>">
                    <svg class="nova-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <circle cx="12" cy="12" r="8"></circle>
                        <circle cx="12" cy="12" r="12"></circle>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Notification -->
            <div class="nova-notification" style="display:none;">
                <span class="nova-notification-text"></span>
            </div>
        </div>
        
        <style>
        /* Base styles for controls */
        .nova-sound-fx-controls {
            --nova-primary: #007cba;
            --nova-bg: #ffffff;
            --nova-text: #1e1e1e;
            --nova-border: #ddd;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            z-index: 9999;
        }
        
        .nova-sound-fx-controls.nova-theme-dark {
            --nova-bg: #1e1e1e;
            --nova-text: #ffffff;
            --nova-border: #444;
        }
        
        /* Minimal style */
        .nova-sound-fx-controls.nova-style-minimal {
            position: fixed;
        }
        
        .nova-sound-fx-controls.nova-style-minimal .nova-controls-wrapper {
            background: var(--nova-bg);
            border: 1px solid var(--nova-border);
            border-radius: 25px;
            padding: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .nova-sound-fx-controls.nova-style-minimal.nova-collapsed .nova-volume-container,
        .nova-sound-fx-controls.nova-style-minimal.nova-collapsed .nova-save-btn {
            display: none;
        }
        
        /* Floating style */
        .nova-sound-fx-controls.nova-style-floating {
            position: fixed;
            background: var(--nova-bg);
            border: 1px solid var(--nova-border);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            min-width: 280px;
        }
        
        .nova-sound-fx-controls.nova-style-floating .nova-controls-wrapper {
            display: grid;
            gap: 12px;
        }
        
        /* Embedded style */
        .nova-sound-fx-controls.nova-style-embedded {
            background: var(--nova-bg);
            border: 1px solid var(--nova-border);
            border-radius: 8px;
            padding: 12px;
            display: inline-block;
        }
        
        .nova-sound-fx-controls.nova-style-embedded .nova-controls-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* Position styles */
        .nova-sound-fx-controls.nova-position-top-left {
            top: 20px;
            left: 20px;
        }
        
        .nova-sound-fx-controls.nova-position-top-right {
            top: 20px;
            right: 20px;
        }
        
        .nova-sound-fx-controls.nova-position-bottom-left {
            bottom: 20px;
            left: 20px;
        }
        
        .nova-sound-fx-controls.nova-position-bottom-right {
            bottom: 20px;
            right: 20px;
        }
        
        /* Control buttons */
        .nova-control-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: var(--nova-text);
        }
        
        .nova-control-btn:hover {
            background: rgba(0,124,186,0.1);
            color: var(--nova-primary);
        }
        
        .nova-control-btn:active {
            transform: scale(0.95);
        }
        
        .nova-icon {
            width: 20px;
            height: 20px;
        }
        
        /* Volume container */
        .nova-volume-container {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        
        .nova-volume-slider {
            -webkit-appearance: none;
            appearance: none;
            height: 4px;
            background: var(--nova-border);
            border-radius: 2px;
            outline: none;
            min-width: 100px;
            cursor: pointer;
        }
        
        .nova-volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            background: var(--nova-primary);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .nova-volume-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
        }
        
        .nova-volume-slider::-moz-range-thumb {
            width: 16px;
            height: 16px;
            background: var(--nova-primary);
            border-radius: 50%;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        
        .nova-volume-slider::-moz-range-thumb:hover {
            transform: scale(1.2);
        }
        
        .nova-volume-value {
            font-size: 12px;
            color: var(--nova-text);
            min-width: 35px;
            text-align: right;
        }
        
        /* Notification */
        .nova-notification {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 10px;
            background: var(--nova-bg);
            border: 1px solid var(--nova-border);
            border-radius: 6px;
            padding: 8px 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            white-space: nowrap;
        }
        
        .nova-notification.nova-show {
            animation: novaSlideIn 0.3s ease;
        }
        
        @keyframes novaSlideIn {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .nova-sound-fx-controls.nova-style-floating {
                min-width: 240px;
            }
            
            .nova-volume-slider {
                min-width: 80px;
            }
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
}

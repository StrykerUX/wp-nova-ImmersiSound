<?php
/**
 * Admin functionality - Versión Mejorada
 * Con correcciones para mostrar audios y evitar conflictos de URL
 */
class Nova_Sound_FX_Admin {
    
    private $version;
    
    public function __construct($version) {
        $this->version = $version;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Nova ImmersiSound', 'nova-sound-fx'),
            __('Nova ImmersiSound', 'nova-sound-fx'),
            'manage_options',
            'nova-sound-fx',
            array($this, 'render_admin_page'),
            'dashicons-format-audio',
            30
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap nova-sound-fx-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php 
            // Only show support banner if user accepted it in setup
            $settings = get_option('nova_sound_fx_settings', array());
            if (!empty($settings['show_admin_banner']) && !empty($settings['terms_accepted'])): 
            ?>
            <!-- Support Banner (User Consented) -->
            <div class="nova-support-banner" id="nova-support-banner">
                <button class="nova-dismiss-banner" onclick="dismissSupportBanner()" title="<?php _e('Dismiss', 'nova-sound-fx'); ?>">×</button>
                <div class="nova-support-content">
                    <div class="nova-support-icon">
                        <span class="coffee-icon">☕</span>
                    </div>
                    <div class="nova-support-text">
                        <h3><?php _e('You\'re Supporting Nova ImmersiSound!', 'nova-sound-fx'); ?></h3>
                        <p><?php _e('Thank you for being part of our supporter community. Your contribution keeps this plugin free and updated!', 'nova-sound-fx'); ?></p>
                    </div>
                    <div class="nova-support-action">
                        <a href="https://buymeacoffee.com/imstryker" target="_blank" class="nova-support-button">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                <line x1="6" y1="1" x2="6" y2="4"></line>
                                <line x1="10" y1="1" x2="10" y2="4"></line>
                                <line x1="14" y1="1" x2="14" y2="4"></line>
                            </svg>
                            <?php _e('Buy Me a Coffee', 'nova-sound-fx'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <script>
            function dismissSupportBanner() {
                document.getElementById('nova-support-banner').style.display = 'none';
                // Save dismissal for this session
                sessionStorage.setItem('nova_banner_dismissed', 'true');
            }
            // Check if already dismissed this session
            if (sessionStorage.getItem('nova_banner_dismissed')) {
                document.getElementById('nova-support-banner').style.display = 'none';
            }
            </script>
            <?php endif; ?>
            
            <div class="nav-tab-wrapper">
                <a href="#sound-library" class="nav-tab nav-tab-active" data-tab="sound-library">
                    <?php _e('Biblioteca de Sonidos', 'nova-sound-fx'); ?>
                </a>
                <a href="#css-mapping" class="nav-tab" data-tab="css-mapping">
                    <?php _e('Mapeo de Sonidos CSS', 'nova-sound-fx'); ?>
                </a>
                <a href="#page-transitions" class="nav-tab" data-tab="page-transitions">
                    <?php _e('Transiciones de Página', 'nova-sound-fx'); ?>
                </a>
                <a href="#settings" class="nav-tab" data-tab="settings">
                    <?php _e('Configuración', 'nova-sound-fx'); ?>
                </a>
                <a href="#personalization" class="nav-tab" data-tab="personalization">
                    <?php _e('Personalización', 'nova-sound-fx'); ?>
                </a>
                <a href="#support" class="nav-tab" data-tab="support">
                    <?php _e('Soporte', 'nova-sound-fx'); ?>
                    <?php if (!empty($settings['supporter_status']) && $settings['supporter_status'] === 'active'): ?>
                        <span style="background: #11ba82; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;">ACTIVO</span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="tab-content">
                <!-- Sound Library Tab -->
                <div id="sound-library" class="tab-pane active">
                    <h2><?php _e('Biblioteca de Sonidos', 'nova-sound-fx'); ?></h2>
                    <p><?php _e('Sube y administra tus efectos de sonido. Formatos soportados: MP3, WAV', 'nova-sound-fx'); ?></p>
                    
                    <div class="sound-upload-section">
                        <button id="nova-upload-sound" class="button button-primary">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Subir Nuevo Sonido', 'nova-sound-fx'); ?>
                        </button>
                        
                        <button id="refresh-sound-library" class="button">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Actualizar Biblioteca', 'nova-sound-fx'); ?>
                        </button>
                        
                        <div class="sound-categories">
                            <label><?php _e('Filtrar por categoría:', 'nova-sound-fx'); ?></label>
                            <select id="sound-category-filter">
                                <option value=""><?php _e('Todas las Categorías', 'nova-sound-fx'); ?></option>
                                <option value="hover"><?php _e('Efectos Hover', 'nova-sound-fx'); ?></option>
                                <option value="click"><?php _e('Efectos Click', 'nova-sound-fx'); ?></option>
                                <option value="transition"><?php _e('Transiciones de Página', 'nova-sound-fx'); ?></option>
                                <option value="notification"><?php _e('Notificaciones', 'nova-sound-fx'); ?></option>
                                <option value="ambient"><?php _e('Ambiente', 'nova-sound-fx'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="sound-library-grid" class="sound-grid">
                        <!-- Los elementos de sonido se cargarán aquí vía AJAX -->
                    </div>
                </div>
                
                <!-- CSS Mapping Tab -->
                <div id="css-mapping" class="tab-pane">
                    <h2><?php _e('Mapeo de Sonidos CSS', 'nova-sound-fx'); ?></h2>
                    <p><?php _e('Asigna efectos de sonido a clases CSS e IDs HTML con diferentes eventos', 'nova-sound-fx'); ?></p>
                    
                    <div class="nova-info-box">
                        <span class="dashicons dashicons-info"></span>
                        <p><?php _e('Solo puedes usar selectores de clase (.ejemplo) o ID (#ejemplo). No se permiten selectores de elementos o atributos.', 'nova-sound-fx'); ?></p>
                    </div>
                    
                    <button id="add-css-mapping" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Agregar Nuevo Mapeo', 'nova-sound-fx'); ?>
                    </button>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 25%"><?php _e('Selector CSS', 'nova-sound-fx'); ?></th>
                                <th style="width: 15%"><?php _e('Evento', 'nova-sound-fx'); ?></th>
                                <th style="width: 25%"><?php _e('Sonido', 'nova-sound-fx'); ?></th>
                                <th style="width: 10%"><?php _e('Volumen', 'nova-sound-fx'); ?></th>
                                <th style="width: 10%"><?php _e('Retraso (ms)', 'nova-sound-fx'); ?></th>
                                <th style="width: 15%"><?php _e('Acciones', 'nova-sound-fx'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="css-mappings-list">
                            <!-- Los mapeos se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Page Transitions Tab -->
                <div id="page-transitions" class="tab-pane">
                    <h2><?php _e('Transiciones de Página', 'nova-sound-fx'); ?></h2>
                    <p><?php _e('Configura sonidos para entradas y salidas de páginas', 'nova-sound-fx'); ?></p>
                    
                    <div class="transition-settings">
                        <h3><?php _e('Transiciones Globales', 'nova-sound-fx'); ?></h3>
                        <div class="global-transitions">
                            <div class="transition-row">
                                <label><?php _e('Sonido de Entrada de Página por Defecto:', 'nova-sound-fx'); ?></label>
                                <select id="global-entry-sound" class="sound-select">
                                    <option value=""><?php _e('Ninguno', 'nova-sound-fx'); ?></option>
                                </select>
                                <input type="range" id="global-entry-volume" min="0" max="100" value="50">
                                <span class="volume-value">50%</span>
                            </div>
                            <div class="transition-row">
                                <label><?php _e('Sonido de Salida de Página por Defecto:', 'nova-sound-fx'); ?></label>
                                <select id="global-exit-sound" class="sound-select">
                                    <option value=""><?php _e('Ninguno', 'nova-sound-fx'); ?></option>
                                </select>
                                <input type="range" id="global-exit-volume" min="0" max="100" value="50">
                                <span class="volume-value">50%</span>
                            </div>
                        </div>
                        
                        <h3><?php _e('Transiciones Específicas por URL', 'nova-sound-fx'); ?></h3>
                        <button id="add-url-transition" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Agregar Patrón de URL', 'nova-sound-fx'); ?>
                        </button>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Patrón de URL', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Tipo', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Sonido', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Volumen', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Prioridad', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Acciones', 'nova-sound-fx'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="url-transitions-list">
                                <!-- Las transiciones URL se cargarán aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings" class="tab-pane">
                    <h2><?php _e('Configuración', 'nova-sound-fx'); ?></h2>
                    
                    <form id="nova-sound-fx-settings">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="enable-sounds"><?php _e('Habilitar Sonidos', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="enable-sounds" name="enable_sounds" value="1" checked>
                                    <p class="description"><?php _e('Interruptor maestro para habilitar/deshabilitar todos los efectos de sonido', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="default-volume"><?php _e('Volumen por Defecto', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="range" id="default-volume" name="default_volume" min="0" max="100" value="50">
                                    <span class="volume-value">50%</span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="mobile-enabled"><?php _e('Habilitar en Móviles', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="mobile-enabled" name="mobile_enabled" value="1">
                                    <p class="description"><?php _e('Habilitar efectos de sonido en dispositivos móviles', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="respect-prefers-reduced-motion"><?php _e('Respetar Configuración de Accesibilidad', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="respect-prefers-reduced-motion" name="respect_prefers_reduced_motion" value="1" checked>
                                    <p class="description"><?php _e('Deshabilitar sonidos cuando el usuario tiene activado prefers-reduced-motion', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="preview-mode"><?php _e('Modo Vista Previa', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="preview-mode" name="preview_mode" value="1">
                                    <p class="description"><?php _e('Habilitar sonidos solo para administradores (para pruebas)', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="show-visual-effects"><?php _e('Mostrar Efectos Visuales', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="show-visual-effects" name="show_visual_effects" value="1" checked>
                                    <p class="description"><?php _e('Mostrar ondas visuales cuando se reproducen sonidos. Útil para debugging pero puede ser molesto para usuarios finales.', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Guardar Configuración', 'nova-sound-fx'); ?></button>
                        </p>
                    </form>
                </div>
                
                <!-- Personalization Tab -->
                <div id="personalization" class="tab-pane">
                    <h2><?php _e('Personalización', 'nova-sound-fx'); ?></h2>
                    <p><?php _e('Personaliza la apariencia y diseño de todos los elementos del plugin', 'nova-sound-fx'); ?></p>
                    
                    <form id="personalization-form">
                        <!-- Sección de Diseño General -->
                        <h3><?php _e('Diseño General', 'nova-sound-fx'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="border-radius"><?php _e('Border Radius Global', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="border-radius" name="border_radius" value="12" min="0" max="50" class="small-text"> px
                                    <p class="description"><?php _e('Controla el redondeo de esquinas para todos los elementos (popups, botones, modales, etc.)', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Sección del Botón Flotante -->
                        <h3><?php _e('Botón Flotante', 'nova-sound-fx'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="floating-button-bg"><?php _e('Color de Fondo', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="floating-button-bg" name="floating_button_bg" value="#007cba">
                                    <p class="description"><?php _e('Color de fondo del botón flotante de audio', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="floating-button-icon"><?php _e('Color del Icono', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="floating-button-icon" name="floating_button_icon" value="#ffffff">
                                    <p class="description"><?php _e('Color del icono dentro del botón flotante', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="floating-button-size"><?php _e('Tamaño', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <label><input type="radio" name="floating_button_size" value="small" /> <?php _e('Pequeño (40px)', 'nova-sound-fx'); ?></label><br>
                                    <label><input type="radio" name="floating_button_size" value="medium" checked /> <?php _e('Mediano (56px)', 'nova-sound-fx'); ?></label><br>
                                    <label><input type="radio" name="floating_button_size" value="large" /> <?php _e('Grande (72px)', 'nova-sound-fx'); ?></label>
                                    <p class="description"><?php _e('Tamaño del botón flotante de control de audio', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Sección de Notificación de Audio Pendiente -->
                        <h3><?php _e('Notificación de Audio Pendiente', 'nova-sound-fx'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="pending-notification-enabled"><?php _e('Mostrar Notificación', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="pending-notification-enabled" name="pending_notification_enabled" value="1" checked>
                                    <p class="description"><?php _e('Mostrar notificación cuando el audio necesita reactivación después de recargar la página', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="pending-notification-position"><?php _e('Posición', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <select id="pending-notification-position" name="pending_notification_position">
                                        <option value="top"><?php _e('Arriba', 'nova-sound-fx'); ?></option>
                                        <option value="bottom"><?php _e('Abajo', 'nova-sound-fx'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Posición de la notificación en la pantalla', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Colores del Gradiente', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="pending-gradient-1" name="pending_notification_gradient_1" value="#4f46e5">
                                    <input type="color" id="pending-gradient-2" name="pending_notification_gradient_2" value="#7c3aed">
                                    <p class="description"><?php _e('Colores del fondo con gradiente de la notificación', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="pending-text-color"><?php _e('Color del Texto', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="pending-text-color" name="pending_notification_text_color" value="#ffffff">
                                    <p class="description"><?php _e('Color del texto de la notificación', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="pending-border-color"><?php _e('Color del Borde', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="pending-border-color" name="pending_notification_border_color" value="rgba(255,255,255,0.3)" class="regular-text">
                                    <p class="description"><?php _e('Color del borde de la notificación (soporta rgba)', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Botón "Activar Ahora"', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <div>
                                            <label><?php _e('Fondo:', 'nova-sound-fx'); ?></label><br>
                                            <input type="text" id="pending-button-bg" name="pending_notification_button_bg" value="rgba(255,255,255,0.2)" class="small-text">
                                        </div>
                                        <div>
                                            <label><?php _e('Texto:', 'nova-sound-fx'); ?></label><br>
                                            <input type="color" id="pending-button-text" name="pending_notification_button_text" value="#ffffff">
                                        </div>
                                        <div>
                                            <label><?php _e('Borde:', 'nova-sound-fx'); ?></label><br>
                                            <input type="text" id="pending-button-border" name="pending_notification_button_border" value="rgba(255,255,255,0.3)" class="small-text">
                                        </div>
                                    </div>
                                    <p class="description"><?php _e('Colores del botón de activación', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="pending-backdrop-filter"><?php _e('Efecto Blur (Backdrop Filter)', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="pending-backdrop-filter" name="pending_notification_backdrop_filter" value="1" checked>
                                    <p class="description"><?php _e('Aplicar efecto de desenfoque al fondo de la notificación', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Guardar Personalización', 'nova-sound-fx'); ?></button>
                        </p>
                    </form>
                </div>
                
                <!-- Support Tab -->
                <div id="support" class="tab-pane">
                    <h2><?php _e('Apoya el Desarrollo', 'nova-sound-fx'); ?></h2>
                    
                    <!-- Main Support Section -->
                    <div style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #f7f9fc 0%, #fff 100%); border-radius: 12px; margin: 20px 0;">
                        <h3 style="font-size: 28px; color: #333; margin-bottom: 20px;">
                            ☕ <?php _e('¿Te gusta Nova ImmersiSound?', 'nova-sound-fx'); ?>
                        </h3>
                        <p style="font-size: 18px; color: #666; line-height: 1.8; max-width: 600px; margin: 0 auto 30px;">
                            <?php _e('Este plugin es 100% gratuito y siempre lo será. Si te ha ayudado en tu proyecto, considera invitarme un café para mantenerlo actualizado.', 'nova-sound-fx'); ?>
                        </p>
                        
                        <a href="https://buymeacoffee.com/imstryker" target="_blank" class="button" style="background: linear-gradient(135deg, #11ba82 0%, #0ea968 100%); color: white; border: none; padding: 16px 40px; font-size: 18px; font-weight: bold; border-radius: 30px; text-decoration: none; display: inline-block; box-shadow: 0 4px 15px rgba(17, 186, 130, 0.3);">
                            ☕ <?php _e('Invitarme un Café', 'nova-sound-fx'); ?>
                        </a>
                        
                        <p style="margin-top: 20px; font-size: 14px; color: #999;">
                            <?php _e('Tu apoyo significa mucho para mí ❤️', 'nova-sound-fx'); ?>
                        </p>
                    </div>
                    
                    <!-- Why Support Section -->
                    <div style="margin-top: 40px;">
                        <h3 style="font-size: 20px; margin-bottom: 20px;"><?php _e('¿Por qué apoyar?', 'nova-sound-fx'); ?></h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div style="background: #f0f8ff; padding: 20px; border-radius: 8px; border-left: 4px solid #007cba;">
                                <h4 style="margin-top: 0; color: #007cba;">💻 <?php _e('Desarrollo Continuo', 'nova-sound-fx'); ?></h4>
                                <p style="color: #666; margin: 10px 0 0;">
                                    <?php _e('Tu apoyo me permite dedicar tiempo a mejorar el plugin, corregir bugs y agregar nuevas características que la comunidad solicita.', 'nova-sound-fx'); ?>
                                </p>
                            </div>
                            
                            <div style="background: #f0fff4; padding: 20px; border-radius: 8px; border-left: 4px solid #11ba82;">
                                <h4 style="margin-top: 0; color: #11ba82;">🎆 <?php _e('Mantenerlo Gratuito', 'nova-sound-fx'); ?></h4>
                                <p style="color: #666; margin: 10px 0 0;">
                                    <?php _e('Con tu ayuda, Nova ImmersiSound puede seguir siendo gratuito para todos, sin versiones premium ni funciones bloqueadas.', 'nova-sound-fx'); ?>
                                </p>
                            </div>
                            
                            <div style="background: #fff9f0; padding: 20px; border-radius: 8px; border-left: 4px solid #ff9800;">
                                <h4 style="margin-top: 0; color: #ff9800;">✨ <?php _e('Soporte Personal', 'nova-sound-fx'); ?></h4>
                                <p style="color: #666; margin: 10px 0 0;">
                                    <?php _e('Los supporters reciben respuestas prioritarias a sus preguntas y pueden sugerir nuevas funcionalidades directamente.', 'nova-sound-fx'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Impact Section -->
                    <div style="margin-top: 40px; padding: 30px; background: #f7f9fc; border-radius: 12px;">
                        <h3 style="margin-top: 0;"><?php _e('Tu Impacto', 'nova-sound-fx'); ?></h3>
                        <p style="font-size: 16px; color: #666; line-height: 1.6;">
                            <?php _e('Cada café que recibes es más que una donación. Es un voto de confianza, una palmada en la espalda que dice "sigue adelante". Como desarrollador independiente, tu apoyo no solo financia el hosting y las herramientas, sino que también me motiva a seguir mejorando este plugin que tanto amor ha recibido de la comunidad.', 'nova-sound-fx'); ?>
                        </p>
                        <p style="font-size: 16px; color: #666; line-height: 1.6; margin-top: 15px;">
                            <strong><?php _e('Cada café cuenta. Cada supporter importa. Gracias por considerar apoyar mi trabajo.', 'nova-sound-fx'); ?></strong>
                        </p>
                    </div>
                    
                    <!-- Alternative Support -->
                    <div style="margin-top: 40px; text-align: center; padding: 20px; border-top: 1px solid #ddd;">
                        <h4><?php _e('Otras formas de apoyar', 'nova-sound-fx'); ?></h4>
                        <p style="color: #666; margin: 10px 0;">
                            <?php _e('Si no puedes donar, puedes ayudar de otras maneras:', 'nova-sound-fx'); ?>
                        </p>
                        <ul style="text-align: left; max-width: 500px; margin: 20px auto; color: #666;">
                            <li>⭐ <?php _e('Deja una reseña de 5 estrellas en WordPress.org', 'nova-sound-fx'); ?></li>
                            <li>📢 <?php _e('Comparte el plugin con otros desarrolladores', 'nova-sound-fx'); ?></li>
                            <li>🐛 <?php _e('Reporta bugs o sugiere mejoras en GitHub', 'nova-sound-fx'); ?></li>
                            <li>🌍 <?php _e('Traduce el plugin a tu idioma', 'nova-sound-fx'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal for CSS Mapping -->
        <div id="css-mapping-modal" class="nova-modal" style="display:none;">
            <div class="nova-modal-content">
                <span class="nova-modal-close">&times;</span>
                <h2><?php _e('Agregar Mapeo de Sonido CSS', 'nova-sound-fx'); ?></h2>
                <form id="css-mapping-form">
                    <input type="hidden" id="mapping-id" value="">
                    <table class="form-table">
                        <tr>
                            <th><label for="css-selector"><?php _e('Selector CSS', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="text" id="css-selector" class="regular-text" placeholder=".mi-clase, #mi-id">
                                <div id="css-selector-feedback" class="nova-form-feedback"></div>
                                <p class="description"><?php _e('Ingresa selectores de clase (.clase) o ID (#id). Puedes usar múltiples selectores separados por comas.', 'nova-sound-fx'); ?></p>
                                <div id="selector-preview" style="display:none;"></div>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="event-type"><?php _e('Tipo de Evento', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="event-type" class="regular-text">
                                    <option value="hover"><?php _e('Hover (Pasar el mouse)', 'nova-sound-fx'); ?></option>
                                    <option value="click"><?php _e('Click', 'nova-sound-fx'); ?></option>
                                    <option value="active"><?php _e('Active (Click sostenido)', 'nova-sound-fx'); ?></option>
                                    <option value="focus"><?php _e('Focus (Enfocar elemento)', 'nova-sound-fx'); ?></option>
                                    <option value="blur"><?php _e('Blur (Desenfocar elemento)', 'nova-sound-fx'); ?></option>
                                    <option value="mouseenter"><?php _e('Mouse Enter (Entrar con mouse)', 'nova-sound-fx'); ?></option>
                                    <option value="mouseleave"><?php _e('Mouse Leave (Salir con mouse)', 'nova-sound-fx'); ?></option>
                                    <option value="mousedown"><?php _e('Mouse Down (Presionar botón del mouse)', 'nova-sound-fx'); ?></option>
                                    <option value="mouseup"><?php _e('Mouse Up (Soltar botón del mouse)', 'nova-sound-fx'); ?></option>
                                </select>
                                <p class="description"><?php _e('Selecciona cuándo se debe reproducir el sonido', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mapping-sound"><?php _e('Efecto de Sonido', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="mapping-sound" class="sound-select regular-text">
                                    <option value=""><?php _e('Selecciona un sonido', 'nova-sound-fx'); ?></option>
                                </select>
                                <button type="button" id="preview-mapping-sound" class="button"><?php _e('Vista Previa', 'nova-sound-fx'); ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mapping-volume"><?php _e('Volumen', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="range" id="mapping-volume" min="0" max="100" value="100">
                                <span class="volume-value">100%</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mapping-delay"><?php _e('Retraso (ms)', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="number" id="mapping-delay" min="0" max="5000" value="0" class="small-text">
                                <p class="description"><?php _e('Retraso antes de reproducir el sonido (en milisegundos)', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Opciones Visuales', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="show-visual-effect" checked>
                                    <?php _e('Mostrar efecto visual al reproducir sonido', 'nova-sound-fx'); ?>
                                </label>
                                <p class="description"><?php _e('Agrega una animación visual cuando se reproduce el sonido', 'nova-sound-fx'); ?></p>
                                <br>
                                <label>
                                    <input type="checkbox" id="show-speaker-icon" checked>
                                    <?php _e('Mostrar icono de bocina', 'nova-sound-fx'); ?>
                                </label>
                                <p class="description"><?php _e('Muestra un pequeño icono de bocina en elementos con sonido', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Guardar Mapeo', 'nova-sound-fx'); ?></button>
                        <button type="button" class="button nova-modal-cancel"><?php _e('Cancelar', 'nova-sound-fx'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Modal for URL Transitions -->
        <div id="url-transition-modal" class="nova-modal" style="display:none;">
            <div class="nova-modal-content">
                <span class="nova-modal-close">&times;</span>
                <h2><?php _e('Agregar Transición de URL', 'nova-sound-fx'); ?></h2>
                <form id="url-transition-form">
                    <input type="hidden" id="transition-id" value="">
                    <table class="form-table">
                        <tr>
                            <th><label for="url-pattern"><?php _e('Patrón de URL', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="text" id="url-pattern" class="regular-text" placeholder="/404, */contacto/*, regex:.*\.pdf$">
                                <p class="description"><?php _e('Usa * para comodines, prefijo con regex: para patrones regex', 'nova-sound-fx'); ?></p>
                                <p class="description"><?php _e('Ejemplos: /404 (página 404), */shop/* (todas las páginas de tienda), regex:.*\.pdf$ (enlaces PDF)', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-type"><?php _e('Tipo de Transición', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="transition-type" class="regular-text">
                                    <option value="enter"><?php _e('Entrada de Página', 'nova-sound-fx'); ?></option>
                                    <option value="exit"><?php _e('Salida de Página', 'nova-sound-fx'); ?></option>
                                    <option value="both"><?php _e('Ambas', 'nova-sound-fx'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-sound"><?php _e('Efecto de Sonido', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="transition-sound" class="sound-select regular-text">
                                    <option value=""><?php _e('Selecciona un sonido', 'nova-sound-fx'); ?></option>
                                </select>
                                <button type="button" id="preview-transition-sound" class="button"><?php _e('Vista Previa', 'nova-sound-fx'); ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-volume"><?php _e('Volumen', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="range" id="transition-volume" min="0" max="100" value="100">
                                <span class="volume-value">100%</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-priority"><?php _e('Prioridad', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="number" id="transition-priority" min="0" max="100" value="0" class="small-text">
                                <p class="description"><?php _e('Los patrones con mayor prioridad tienen precedencia sobre los de menor prioridad', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Guardar Transición', 'nova-sound-fx'); ?></button>
                        <button type="button" class="button nova-modal-cancel"><?php _e('Cancelar', 'nova-sound-fx'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if ($hook !== 'toplevel_page_nova-sound-fx') {
            return;
        }
        
        wp_enqueue_style(
            'nova-sound-fx-admin',
            NOVA_SOUND_FX_PLUGIN_URL . 'admin/css/nova-sound-fx-admin.css',
            array(),
            $this->version
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_nova-sound-fx') {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_script(
            'nova-sound-fx-admin',
            NOVA_SOUND_FX_PLUGIN_URL . 'admin/js/nova-sound-fx-admin.js',
            array('jquery', 'wp-mediaelement'),
            $this->version,
            true
        );
        
        wp_localize_script('nova-sound-fx-admin', 'nova_sound_fx_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_sound_fx_admin'),
            'strings' => array(
                'confirm_delete' => __('¿Estás seguro de que deseas eliminar esto?', 'nova-sound-fx'),
                'error' => __('Ocurrió un error. Por favor intenta de nuevo.', 'nova-sound-fx'),
                'saved' => __('¡Configuración guardada exitosamente!', 'nova-sound-fx')
            )
        ));
    }
    
    /**
     * AJAX: Get CSS mappings
     */
    public function ajax_get_css_mappings() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $mappings = Nova_Sound_FX::get_css_mappings();
        wp_send_json_success($mappings);
    }
    
    /**
     * AJAX: Get transitions
     */
    public function ajax_get_transitions() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $transitions = Nova_Sound_FX::get_transitions();
        wp_send_json_success($transitions);
    }
    
    /**
     * AJAX: Save CSS mapping
     */
    public function ajax_save_css_mapping() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $data = array(
            'css_selector' => sanitize_text_field($_POST['css_selector']),
            'event_type' => sanitize_text_field($_POST['event_type']),
            'sound_id' => intval($_POST['sound_id']),
            'volume' => intval($_POST['volume']),
            'delay' => intval($_POST['delay']),
            'show_visual_effect' => isset($_POST['show_visual_effect']) ? 1 : 0,
            'show_speaker_icon' => isset($_POST['show_speaker_icon']) ? 1 : 0
        );
        
        // Validar que el selector sea una clase o ID
        if (!preg_match('/^[#.][\w-]+(\s*,\s*[#.][\w-]+)*$/', $data['css_selector'])) {
            wp_send_json_error(array('message' => __('El selector CSS debe ser una clase (.clase) o ID (#id)', 'nova-sound-fx')));
            return;
        }
        
        // Si hay un ID, actualizar; si no, crear nuevo
        if (!empty($_POST['id'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'nova_sound_fx_css_mappings';
            
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => intval($_POST['id'])),
                array('%s', '%s', '%d', '%d', '%d', '%d', '%d'),
                array('%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => __('Mapeo CSS actualizado exitosamente', 'nova-sound-fx')));
            } else {
                wp_send_json_error(array('message' => __('Error al actualizar el mapeo CSS', 'nova-sound-fx')));
            }
        } else {
            if (Nova_Sound_FX::save_css_mapping($data)) {
                wp_send_json_success(array('message' => __('Mapeo CSS guardado exitosamente', 'nova-sound-fx')));
            } else {
                wp_send_json_error(array('message' => __('Error al guardar el mapeo CSS', 'nova-sound-fx')));
            }
        }
    }
    
    /**
     * AJAX: Delete CSS mapping
     */
    public function ajax_delete_css_mapping() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $id = intval($_POST['id']);
        
        if (Nova_Sound_FX::delete_css_mapping($id)) {
            wp_send_json_success(array('message' => __('Mapeo CSS eliminado exitosamente', 'nova-sound-fx')));
        } else {
            wp_send_json_error(array('message' => __('Error al eliminar el mapeo CSS', 'nova-sound-fx')));
        }
    }
    
    /**
     * AJAX: Save transition
     */
    public function ajax_save_transition() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $data = array(
            'url_pattern' => sanitize_text_field($_POST['url_pattern']),
            'transition_type' => sanitize_text_field($_POST['transition_type']),
            'sound_id' => intval($_POST['sound_id']),
            'volume' => intval($_POST['volume']),
            'priority' => intval($_POST['priority'])
        );
        
        // Si hay un ID, actualizar; si no, crear nuevo
        if (!empty($_POST['id'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'nova_sound_fx_transitions';
            
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => intval($_POST['id'])),
                array('%s', '%s', '%d', '%d', '%d'),
                array('%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => __('Transición actualizada exitosamente', 'nova-sound-fx')));
            } else {
                wp_send_json_error(array('message' => __('Error al actualizar la transición', 'nova-sound-fx')));
            }
        } else {
            if (Nova_Sound_FX::save_transition($data)) {
                wp_send_json_success(array('message' => __('Transición guardada exitosamente', 'nova-sound-fx')));
            } else {
                wp_send_json_error(array('message' => __('Error al guardar la transición', 'nova-sound-fx')));
            }
        }
    }
    
    /**
     * AJAX: Delete transition
     */
    public function ajax_delete_transition() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $id = intval($_POST['id']);
        
        if (Nova_Sound_FX::delete_transition($id)) {
            wp_send_json_success(array('message' => __('Transición eliminada exitosamente', 'nova-sound-fx')));
        } else {
            wp_send_json_error(array('message' => __('Error al eliminar la transición', 'nova-sound-fx')));
        }
    }
    
    /**
     * AJAX: Get sound library
     */
    public function ajax_get_sound_library() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => array('audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/mp3'),
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_wp_attached_file',
                    'value' => '.mp3',
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wp_attached_file',
                    'value' => '.wav',
                    'compare' => 'LIKE'
                )
            )
        );
        
        // Forzar actualización si se solicita
        if (!empty($_POST['refresh'])) {
            clean_post_cache(0);
        }
        
        $query = new WP_Query($args);
        $sounds = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $sounds[] = array(
                    'id' => $id,
                    'title' => get_the_title(),
                    'url' => wp_get_attachment_url($id),
                    'mime_type' => get_post_mime_type($id),
                    'date' => get_the_date('j M Y', $id)
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($sounds);
    }
    
    /**
     * AJAX: Get settings
     */
    public function ajax_get_settings() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $settings = get_option('nova_sound_fx_settings', array(
            'enable_sounds' => true,
            'default_volume' => 50,
            'mobile_enabled' => false,
            'respect_prefers_reduced_motion' => true,
            'preview_mode' => false,
            'show_visual_effects' => true,
            'border_radius' => 12,
            'floating_button_bg' => '#007cba',
            'floating_button_icon' => '#ffffff',
            'floating_button_size' => 'medium',
            'pending_notification_enabled' => true,
            'pending_notification_position' => 'top',
            'pending_notification_gradient_1' => '#4f46e5',
            'pending_notification_gradient_2' => '#7c3aed',
            'pending_notification_text_color' => '#ffffff',
            'pending_notification_border_color' => 'rgba(255,255,255,0.3)',
            'pending_notification_button_bg' => 'rgba(255,255,255,0.2)',
            'pending_notification_button_text' => '#ffffff',
            'pending_notification_button_border' => 'rgba(255,255,255,0.3)',
            'pending_notification_backdrop_filter' => true,
            'bmc_widget_enabled' => false,
            'bmc_widget_position' => 'Right',
            'bmc_widget_pages' => 'all'
        ));
        
        wp_send_json_success($settings);
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $settings = array(
            'enable_sounds' => isset($_POST['enable_sounds']) ? true : false,
            'default_volume' => intval($_POST['default_volume']),
            'mobile_enabled' => isset($_POST['mobile_enabled']) ? true : false,
            'respect_prefers_reduced_motion' => isset($_POST['respect_prefers_reduced_motion']) ? true : false,
            'preview_mode' => isset($_POST['preview_mode']) ? true : false,
            'show_visual_effects' => isset($_POST['show_visual_effects']) ? true : false,
            'border_radius' => isset($_POST['border_radius']) ? intval($_POST['border_radius']) : 12,
            'floating_button_bg' => isset($_POST['floating_button_bg']) ? sanitize_hex_color($_POST['floating_button_bg']) : '#007cba',
            'floating_button_icon' => isset($_POST['floating_button_icon']) ? sanitize_hex_color($_POST['floating_button_icon']) : '#ffffff',
            'floating_button_size' => isset($_POST['floating_button_size']) ? sanitize_text_field($_POST['floating_button_size']) : 'medium',
            'pending_notification_enabled' => isset($_POST['pending_notification_enabled']) ? true : false,
            'pending_notification_position' => isset($_POST['pending_notification_position']) ? sanitize_text_field($_POST['pending_notification_position']) : 'top',
            'pending_notification_gradient_1' => isset($_POST['pending_notification_gradient_1']) ? sanitize_hex_color($_POST['pending_notification_gradient_1']) : '#4f46e5',
            'pending_notification_gradient_2' => isset($_POST['pending_notification_gradient_2']) ? sanitize_hex_color($_POST['pending_notification_gradient_2']) : '#7c3aed',
            'pending_notification_text_color' => isset($_POST['pending_notification_text_color']) ? sanitize_hex_color($_POST['pending_notification_text_color']) : '#ffffff',
            'pending_notification_border_color' => isset($_POST['pending_notification_border_color']) ? sanitize_text_field($_POST['pending_notification_border_color']) : 'rgba(255,255,255,0.3)',
            'pending_notification_button_bg' => isset($_POST['pending_notification_button_bg']) ? sanitize_text_field($_POST['pending_notification_button_bg']) : 'rgba(255,255,255,0.2)',
            'pending_notification_button_text' => isset($_POST['pending_notification_button_text']) ? sanitize_hex_color($_POST['pending_notification_button_text']) : '#ffffff',
            'pending_notification_button_border' => isset($_POST['pending_notification_button_border']) ? sanitize_text_field($_POST['pending_notification_button_border']) : 'rgba(255,255,255,0.3)',
            'pending_notification_backdrop_filter' => isset($_POST['pending_notification_backdrop_filter']) ? true : false,
            'bmc_widget_enabled' => isset($_POST['bmc_widget_enabled']) ? true : false,
            'bmc_widget_position' => isset($_POST['bmc_widget_position']) ? sanitize_text_field($_POST['bmc_widget_position']) : 'Right',
            'bmc_widget_pages' => isset($_POST['bmc_widget_pages']) ? sanitize_text_field($_POST['bmc_widget_pages']) : 'all'
        );
        
        update_option('nova_sound_fx_settings', $settings);
        
        wp_send_json_success(array('message' => __('Configuración guardada exitosamente', 'nova-sound-fx')));
    }
}

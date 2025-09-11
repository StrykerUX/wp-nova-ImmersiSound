<?php
/**
 * Setup Wizard for Nova ImmersiSound
 * Handles initial configuration and terms acceptance
 */
class Nova_Sound_FX_Setup {
    
    private $version;
    
    public function __construct($version = '1.1.0') {
        $this->version = $version;
        $this->init();
    }
    
    /**
     * Initialize setup wizard
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_setup_page'), 5);
        add_action('admin_init', array($this, 'handle_setup_submission'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_setup_assets'));
        
        // Redirect after activation
        add_action('admin_init', array($this, 'maybe_redirect_to_setup'));
    }
    
    /**
     * Add hidden setup page
     */
    public function add_setup_page() {
        add_submenu_page(
            null, // Hidden from menu
            __('Nova ImmersiSound - Setup', 'nova-sound-fx'),
            __('Setup Wizard', 'nova-sound-fx'),
            'manage_options',
            'nova-sound-fx-setup',
            array($this, 'render_setup_page')
        );
    }
    
    /**
     * Maybe redirect to setup
     */
    public function maybe_redirect_to_setup() {
        // Don't redirect on certain pages
        if (wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Check if we should redirect - ALWAYS redirect on activation
        if (get_transient('nova_sound_fx_activation_redirect')) {
            delete_transient('nova_sound_fx_activation_redirect');
            // Always redirect to setup wizard
            wp_safe_redirect(admin_url('admin.php?page=nova-sound-fx-setup'));
            exit;
        }
    }
    
    /**
     * Handle setup form submission
     */
    public function handle_setup_submission() {
        if (!isset($_POST['nova_setup_nonce']) || !wp_verify_nonce($_POST['nova_setup_nonce'], 'nova_setup_action')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $settings = get_option('nova_sound_fx_settings', array());
        
        if (isset($_POST['accept_recommended'])) {
            // User clicked "ACTIVATE PLUGIN" - save settings and go to thank you screen
            $settings['enable_sounds'] = true;
            $settings['save_user_preferences'] = true;
            $settings['show_support_widget'] = true;
            $settings['show_admin_banner'] = true;
            $settings['show_support_links'] = true;
            $settings['terms_accepted'] = true;
            $settings['setup_complete'] = true;
            $settings['setup_date'] = current_time('mysql');
            $settings['supporter_status'] = 'active';
            
            // Enable all features
            $settings['mobile_enabled'] = true;
            $settings['show_visual_feedback'] = true;
            $settings['show_speaker_icon'] = false; // User didn't like these
            
            update_option('nova_sound_fx_settings', $settings);
            update_option('nova_sound_fx_setup_complete', true);
            
            // Redirect to thank you screen (step 2)
            wp_safe_redirect(admin_url('admin.php?page=nova-sound-fx-setup&step=thank-you'));
            exit;
            
        } elseif (isset($_POST['continue_to_plugin'])) {
            // User clicked continue from thank you page
            wp_safe_redirect(admin_url('admin.php?page=nova-sound-fx&setup=complete&welcome=1'));
            exit;
            
        } elseif (isset($_POST['support_plugin'])) {
            // User clicked support button - open in new tab via JavaScript
            // This is handled via JavaScript to open in new tab
        }
    }
    
    /**
     * Enqueue setup wizard assets
     */
    public function enqueue_setup_assets($hook) {
        if ($hook !== 'admin_page_nova-sound-fx-setup') {
            return;
        }
        
        // Add inline styles for setup wizard
        wp_add_inline_style('wp-admin', $this->get_setup_styles());
    }
    
    /**
     * Get setup wizard styles
     */
    private function get_setup_styles() {
        return '
        .nova-setup-wrapper {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .nova-setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .nova-setup-header h1 {
            color: white;
            font-size: 32px;
            margin: 0;
            font-weight: 600;
        }
        .nova-setup-header p {
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            margin-top: 10px;
        }
        .nova-setup-content {
            padding: 40px;
        }
        .nova-features-list {
            background: #f7f9fc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .nova-features-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .nova-features-list li {
            padding: 10px 0;
            padding-left: 30px;
            position: relative;
            font-size: 15px;
            color: #333;
        }
        .nova-features-list li:before {
            content: "✅";
            position: absolute;
            left: 0;
        }
        .nova-terms-checkbox {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .nova-terms-checkbox label {
            display: flex;
            align-items: flex-start;
            cursor: pointer;
            font-size: 14px;
        }
        .nova-terms-checkbox input[type="checkbox"] {
            margin-right: 10px;
            margin-top: 2px;
        }
        .nova-terms-link {
            color: #667eea;
            text-decoration: underline;
            font-size: 12px;
        }
        .nova-setup-buttons {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .nova-btn-primary {
            background: linear-gradient(135deg, #11ba82 0%, #0ea968 100%);
            color: white;
            border: none;
            padding: 18px 50px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 15px;
        }
        .nova-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(17, 186, 130, 0.4);
        }
        .nova-btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .nova-small-text {
            font-size: 11px;
            color: #999;
            text-align: center;
            margin-top: 20px;
        }
        .nova-recommended-badge {
            background: #28a745;
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 4px;
            margin-left: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }
        ';
    }
    
    /**
     * Render setup wizard page
     */
    public function render_setup_page() {
        $step = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : 'terms';
        
        if ($step === 'thank-you') {
            $this->render_thank_you_screen();
        } else {
            $this->render_terms_screen();
        }
    }
    
    /**
     * Render terms acceptance screen (Step 1)
     */
    private function render_terms_screen() {
        ?>
        <div class="nova-setup-wrapper">
            <div class="nova-setup-header">
                <h1>🎵 ¡Bienvenido a Nova ImmersiSound! 🎵</h1>
                <p>Tu plugin está casi listo. Solo necesitamos tu permiso para brindarte la mejor experiencia.</p>
            </div>
            
            <div class="nova-setup-content">
                <form method="post" action="">
                    <?php wp_nonce_field('nova_setup_action', 'nova_setup_nonce'); ?>
                    
                    <div class="nova-features-list">
                        <p><strong>Con tu autorización, activaremos:</strong></p>
                        <ul>
                            <li>Configuración automática optimizada de sonidos</li>
                            <li>Guardado de preferencias de audio (volumen, estado)</li>
                            <li>Efectos inmersivos y visuales</li>
                            <li>Widget opcional de apoyo al desarrollador</li>
                            <li>Actualizaciones y mejoras continuas</li>
                        </ul>
                    </div>
                    
                    <div class="nova-terms-checkbox">
                        <label>
                            <input type="checkbox" id="accept_terms" checked="checked" />
                            <span>
                                Acepto los <a href="#" class="nova-terms-link" onclick="showTerms(); return false;">términos y condiciones</a> 
                                y la configuración recomendada del plugin. Entiendo que puedo modificar estas opciones en cualquier momento desde la configuración.
                            </span>
                        </label>
                    </div>
                    
                    <div class="nova-setup-buttons">
                        <button type="submit" name="accept_recommended" class="nova-btn-primary" id="accept_btn">
                            ACTIVAR PLUGIN
                            <span class="nova-recommended-badge">✓ ACEPTAR Y CONTINUAR</span>
                        </button>
                    </div>
                    
                    <p class="nova-small-text">
                        Al continuar, el plugin utilizará cookies y localStorage para mejorar tu experiencia. 
                        <a href="#" onclick="showPrivacy(); return false;" style="color: #999;">Más información</a>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Hidden Terms Modal -->
        <div id="nova-terms-modal" style="display: none;">
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 99998;" onclick="hideTerms()"></div>
            <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 500px; max-height: 70vh; overflow-y: auto; z-index: 99999;">
                <h2>Términos y Condiciones</h2>
                <p><strong>Uso de Datos:</strong></p>
                <ul>
                    <li>El plugin almacena preferencias de audio en el navegador (localStorage)</li>
                    <li>No se envían datos a servidores externos sin tu consentimiento</li>
                    <li>Las preferencias se pueden borrar en cualquier momento</li>
                </ul>
                
                <p><strong>Widget de Soporte (Opcional):</strong></p>
                <ul>
                    <li>Si se activa, mostrará un enlace a Buy Me a Coffee</li>
                    <li>Es completamente opcional y se puede desactivar</li>
                    <li>No afecta la funcionalidad del plugin</li>
                </ul>
                
                <p><strong>Cookies y Almacenamiento:</strong></p>
                <ul>
                    <li>Se usan cookies para recordar el consentimiento de audio</li>
                    <li>localStorage guarda volumen y preferencias</li>
                    <li>Todo cumple con GDPR y normativas de privacidad</li>
                </ul>
                
                <button onclick="hideTerms()" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Entendido</button>
            </div>
        </div>
        
        <script>
        function showTerms() {
            document.getElementById('nova-terms-modal').style.display = 'block';
        }
        function hideTerms() {
            document.getElementById('nova-terms-modal').style.display = 'none';
        }
        function showPrivacy() {
            alert('Política de Privacidad:\n\n• Usamos localStorage para guardar tus preferencias\n• Las cookies mantienen tu sesión activa\n• No compartimos datos con terceros\n• Puedes borrar todos los datos desde la configuración');
        }
        
        // Enable/disable primary button based on checkbox
        document.getElementById('accept_terms').addEventListener('change', function() {
            document.getElementById('accept_btn').disabled = !this.checked;
            if (!this.checked) {
                document.getElementById('accept_btn').style.opacity = '0.5';
            } else {
                document.getElementById('accept_btn').style.opacity = '1';
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render thank you screen (Step 2)
     */
    private function render_thank_you_screen() {
        ?>
        <div class="nova-setup-wrapper">
            <div class="nova-setup-header" style="background: linear-gradient(135deg, #11ba82 0%, #0ea968 100%);">
                <h1 style="color: white;">✨ ¡Plugin Activado con Éxito! ✨</h1>
                <p style="color: rgba(255,255,255,0.95);">Nova ImmersiSound está listo para usar</p>
            </div>
            
            <div class="nova-setup-content" style="text-align: center;">
                <div style="margin: 30px 0;">
                    <div style="font-size: 72px; margin-bottom: 20px;">🎉</div>
                    <h2 style="color: #333; margin-bottom: 15px;">¡Gracias por usar Nova ImmersiSound!</h2>
                    <p style="font-size: 18px; color: #666; line-height: 1.6; max-width: 500px; margin: 0 auto;">
                        Tu sitio web ahora tiene superpoderes de audio. 
                        Disfruta creando experiencias inmersivas para tus visitantes.
                    </p>
                </div>
                
                <!-- Support Reminder Section -->
                <div style="background: #f7f9fc; border-radius: 12px; padding: 30px; margin: 30px 0;">
                    <h3 style="color: #666; margin-top: 0;">☕ Este Plugin es 100% Gratuito</h3>
                    <p style="color: #666; line-height: 1.6; margin: 15px 0;">
                        Nova ImmersiSound es y siempre será gratuito. Si te resulta útil y 
                        ahorras tiempo con él, considera apoyar su desarrollo continuo.
                    </p>
                    <p style="color: #999; font-size: 14px; margin: 15px 0;">
                        Tu apoyo me permite seguir mejorando el plugin y crear más herramientas gratuitas para la comunidad.
                    </p>
                    
                    <div style="margin-top: 25px;">
                        <a href="https://buymeacoffee.com/imstryker" 
                           target="_blank" 
                           class="button" 
                           style="background: #FFDD00; color: #333; border: none; padding: 12px 25px; font-weight: bold; margin-right: 10px;"
                           onclick="window.open(this.href, '_blank'); return false;">
                            ☕ Invitarme un Café
                        </a>
                        <button type="button" 
                                onclick="window.location.href='<?php echo admin_url('admin.php?page=nova-sound-fx&welcome=1'); ?>'"
                                class="button button-primary" 
                                style="padding: 12px 25px;">
                            Continuar al Plugin →
                        </button>
                    </div>
                </div>
                
                <p style="color: #999; font-size: 13px; margin-top: 20px;">
                    También puedes apoyar dejando una ⭐⭐⭐⭐⭐ reseña en WordPress.org
                </p>
            </div>
        </div>
        <?php
    }
}

// Initialize if in admin
if (is_admin()) {
    new Nova_Sound_FX_Setup();
}
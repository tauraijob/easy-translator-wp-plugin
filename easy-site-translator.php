<?php
/**
 * Plugin Name: WebDev Easy Site Translator
 * Description: Adds free Google Translate widget to your WordPress site with Shona support
 * Version: 1.0
 * Author: Tau
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('EST_VERSION', '1.0.0');
define('EST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add nonce verification for forms
function est_verify_nonce($nonce_name) {
    if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonce_name)) {
        wp_die('Security check failed');
    }
}

// Add capability checking
function est_verify_capability() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
}

// Sanitize input data
function est_sanitize_input($data) {
    if (is_array($data)) {
        return array_map('est_sanitize_input', $data);
    }
    return sanitize_text_field($data);
}

// Add Google Translate script
function est_add_translate_script() {
    $included_langs = get_option('est_included_languages', ['en', 'sn', 'zu', 'xh', 'af']);
    if (!is_array($included_langs)) {
        $included_langs = explode(',', $included_langs);
    }
    $languages = implode(',', $included_langs);
    $default_lang = get_option('est_default_language', 'auto');
    ?>
    <div id="google_translate_element"></div>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: '<?php echo esc_js($default_lang); ?>',
                includedLanguages: '<?php echo esc_js($languages); ?>',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false,
                multilanguagePage: true,
                gaTrack: false,
                cookieFlags: 'SameSite=None;Secure'
            }, 'google_translate_element');
        }

        // Function to restore the translation after page load
        function restoreTranslation() {
            var getCookie = function(name) {
                var match = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
                return match ? match[2] : null;
            };
            
            var googtrans = getCookie('googtrans');
            if (googtrans) {
                setTimeout(function() {
                    var langSelect = document.querySelector('.goog-te-combo');
                    if (langSelect) {
                        var lang = googtrans.split('/')[2];
                        langSelect.value = lang;
                        langSelect.dispatchEvent(new Event('change'));
                    }
                }, 1000);
            }
        }

        // Call restore function when page loads
        window.addEventListener('load', restoreTranslation);

        // Add custom language labels
        window.addEventListener('load', function() {
            setTimeout(function() {
                var select = document.querySelector('.goog-te-combo');
                if (select) {
                    Array.from(select.options).forEach(function(option) {
                        if (option.value === 'sn') {
                            option.text = 'Shona';
                        }
                    });
                }
            }, 1000);
        });
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <?php
}
add_action('wp_footer', 'est_add_translate_script');

// Add custom CSS to style the widget
function est_add_custom_css() {
    $position = get_option('est_widget_position', 'bottom-right');
    $positions = [
        'bottom-right' => ['bottom: 20px;', 'right: 20px;'],
        'bottom-left' => ['bottom: 20px;', 'left: 20px;'],
        'top-right' => ['top: 20px;', 'right: 20px;'],
        'top-left' => ['top: 20px;', 'left: 20px;']
    ];
    
    $pos = $positions[$position] ?? $positions['bottom-right'];
    ?>
    <style>
        #google_translate_element {
            position: fixed;
            <?php echo esc_html($pos[0] . $pos[1]); ?>
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .goog-te-gadget {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }
        
        .goog-te-gadget-simple {
            border: 1px solid #ddd !important;
            padding: 8px !important;
            border-radius: 4px !important;
            background-color: white !important;
            cursor: pointer !important;
        }
        
        .goog-te-gadget-simple:hover {
            border-color: #999 !important;
        }
        
        /* Hide Google Translate attribution */
        .goog-te-gadget-simple .goog-te-menu-value span:last-child,
        .goog-te-gadget-simple img {
            display: none;
        }

        /* Hide the Google Translate banner */
        .goog-te-banner-frame {
            display: none !important;
        }
        
        body {
            top: 0 !important;
        }
        
        /* Ensure translated content is visible in admin areas */
        .wp-admin .goog-te-combo {
            height: auto !important;
            visibility: visible !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'est_add_custom_css');

// Add cookie settings to ensure translation persistence
function est_add_cookie_settings() {
    header('Set-Cookie: googtrans=/auto/auto; SameSite=None; Secure');
}
add_action('init', 'est_add_cookie_settings');

// Enable translation in admin area
function est_enable_admin_translation() {
    if (is_admin()) {
        add_action('admin_footer', 'est_add_translate_script');
        add_action('admin_head', 'est_add_custom_css');
    }
}
add_action('init', 'est_enable_admin_translation');

// Use these functions in your form handling:
add_action('admin_post_est_save_settings', function() {
    est_verify_nonce('est_settings_nonce');
    est_verify_capability();
    
    $data = est_sanitize_input($_POST);
    // Process your data here
    
    wp_redirect(admin_url('admin.php?page=est-settings&updated=true'));
    exit;
});

// Add settings page
function est_add_settings_page() {
    add_options_page(
        'Easy Site Translator Settings',
        'Site Translator',
        'manage_options',
        'est-settings',
        'est_render_settings_page'
    );
}
add_action('admin_menu', 'est_add_settings_page');

// Render settings page
function est_render_settings_page() {
    est_verify_capability();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('est_options');
            do_settings_sections('est-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function est_register_settings() {
    register_setting('est_options', 'est_widget_position', 'est_sanitize_input');
    register_setting('est_options', 'est_default_language', 'est_sanitize_input');
    register_setting('est_options', 'est_included_languages', 'est_sanitize_input');

    add_settings_section(
        'est_main_section',
        'Widget Settings',
        'est_section_callback',
        'est-settings'
    );

    add_settings_field(
        'est_widget_position',
        'Widget Position',
        'est_position_callback',
        'est-settings',
        'est_main_section'
    );

    add_settings_field(
        'est_default_language',
        'Default Language',
        'est_default_language_callback',
        'est-settings',
        'est_main_section'
    );

    add_settings_field(
        'est_included_languages',
        'Included Languages',
        'est_languages_callback',
        'est-settings',
        'est_main_section'
    );
}
add_action('admin_init', 'est_register_settings');

// Add the missing callback functions after est_register_settings()

// Section callback
function est_section_callback() {
    echo '<p>Customize how the translator widget appears and functions on your site.</p>';
}

// Position field callback
function est_position_callback() {
    $position = get_option('est_widget_position', 'bottom-right');
    ?>
    <select name="est_widget_position" id="est_widget_position">
        <option value="bottom-right" <?php selected($position, 'bottom-right'); ?>>Bottom Right</option>
        <option value="bottom-left" <?php selected($position, 'bottom-left'); ?>>Bottom Left</option>
        <option value="top-right" <?php selected($position, 'top-right'); ?>>Top Right</option>
        <option value="top-left" <?php selected($position, 'top-left'); ?>>Top Left</option>
    </select>
    <?php
}

// Default language field callback
function est_default_language_callback() {
    $default_lang = get_option('est_default_language', 'auto');
    ?>
    <select name="est_default_language" id="est_default_language">
        <option value="auto" <?php selected($default_lang, 'auto'); ?>>Auto Detect</option>
        <option value="en" <?php selected($default_lang, 'en'); ?>>English</option>
        <option value="sn" <?php selected($default_lang, 'sn'); ?>>Shona</option>
        <option value="zu" <?php selected($default_lang, 'zu'); ?>>Zulu</option>
        <option value="xh" <?php selected($default_lang, 'xh'); ?>>Xhosa</option>
        <option value="af" <?php selected($default_lang, 'af'); ?>>Afrikaans</option>
    </select>
    <?php
}

// Included languages field callback
function est_languages_callback() {
    $included_langs = get_option('est_included_languages', ['en', 'sn', 'zu', 'xh', 'af']);
    if (!is_array($included_langs)) {
        $included_langs = explode(',', $included_langs);
    }
    
    $available_languages = [
        'en' => 'English',
        'sn' => 'Shona',
        'zu' => 'Zulu',
        'xh' => 'Xhosa',
        'af' => 'Afrikaans',
        'ny' => 'Nyanja',
        'st' => 'Sotho',
        'sw' => 'Swahili',
        'ar' => 'Arabic',
        'fr' => 'French',
        'pt' => 'Portuguese',
        'es' => 'Spanish'
    ];
    
    foreach ($available_languages as $code => $name) {
        ?>
        <label style="display: block; margin-bottom: 5px;">
            <input type="checkbox" 
                   name="est_included_languages[]" 
                   value="<?php echo esc_attr($code); ?>"
                   <?php checked(in_array($code, $included_langs)); ?>>
            <?php echo esc_html($name); ?>
        </label>
        <?php
    }
}

// Add shortcode support
function est_translate_shortcode($atts) {
    ob_start();
    est_add_translate_script();
    return ob_get_clean();
}
add_shortcode('site_translator', 'est_translate_shortcode');

// Add browser language detection
function est_detect_browser_language() {
    if (!isset($_COOKIE['googtrans'])) {
        $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        $supported_langs = ['en', 'sn', 'zu', 'xh', 'af', 'ny', 'st', 'sw', 'ar', 'fr', 'pt', 'es'];
        
        if (in_array($browser_lang, $supported_langs)) {
            setcookie('googtrans', '/auto/' . $browser_lang, time() + (86400 * 30), '/', '', true, true);
        }
    }
}
add_action('init', 'est_detect_browser_language', 1);

// Add translation usage tracking
function est_track_translation() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery !== 'undefined') {
            jQuery('.goog-te-combo').on('change', function() {
                if (typeof gtag === 'function') {
                    gtag('event', 'translate', {
                        'event_category': 'Site Translator',
                        'event_label': this.value
                    });
                }
            });
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'est_track_translation'); 
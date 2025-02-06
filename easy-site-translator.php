<?php
/*
Plugin Name: WebDev Easy Site Translator
Description: Adds free Google Translate widget to your WordPress site with Shona support
Version: 1.0
Author: Tau
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add Google Translate script
function est_add_translate_script() {
    ?>
    <div id="google_translate_element"></div>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'auto',
                includedLanguages: 'en,sn,zu,xh,af,ny,st,sw,ar,fr,pt,es', // Shona (sn) and other African languages
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
    ?>
    <style>
        #google_translate_element {
            position: fixed;
            bottom: 20px;
            right: 20px;
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
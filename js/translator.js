jQuery(document).ready(function($) {
    const languageSelect = $('#est-language-select');
    let currentLanguage = 'en';

    // Style the language selector
    $('#est-language-selector').css({
        'position': 'fixed',
        'bottom': '20px',
        'right': '20px',
        'z-index': '1000',
        'background': 'white',
        'padding': '10px',
        'border-radius': '5px',
        'box-shadow': '0 2px 5px rgba(0,0,0,0.2)'
    });

    // Handle language change
    languageSelect.on('change', function() {
        const newLanguage = $(this).val();
        if (newLanguage === currentLanguage) return;

        translatePage(newLanguage);
    });

    function translatePage(targetLang) {
        // Get all text nodes
        const textNodes = document.evaluate(
            '//text()[normalize-space(.)!=""]',
            document.body,
            null,
            XPathResult.UNORDERED_NODE_SNAPSHOT_TYPE,
            null
        );

        // Translate each text node
        for (let i = 0; i < textNodes.snapshotLength; i++) {
            const node = textNodes.snapshotItem(i);
            
            // Skip if node is in a script or style tag
            if (node.parentElement.tagName === 'SCRIPT' || 
                node.parentElement.tagName === 'STYLE') {
                continue;
            }

            // Use Google Translate API
            translateText(node.textContent, targetLang)
                .then(translatedText => {
                    node.textContent = translatedText;
                });
        }

        currentLanguage = targetLang;
    }

    async function translateText(text, targetLang) {
        try {
            const response = await $.ajax({
                url: 'https://translation.googleapis.com/language/translate/v2',
                method: 'POST',
                data: {
                    q: text,
                    target: targetLang,
                    key: estSettings.googleApiKey
                }
            });

            return response.data.translations[0].translatedText;
        } catch (error) {
            console.error('Translation error:', error);
            return text;
        }
    }
}); 
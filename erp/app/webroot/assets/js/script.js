// In your view file or in a separate JavaScript file
function translate() {
    // Logic to fetch the field value and translate it to Hindi
    var fieldName = $(this).data('field');
    var fieldValue = $('[name="' + fieldName + '"]').val();

    // Send fieldValue to translation API and update the input field with the translated text
    // Example:
    // $.post('translation-api-url', {text: fieldValue, targetLanguage: 'hi'}, function(response) {
    //     $('[name="' + fieldName + '"]').val(response.translatedText);
    // });
}

jQuery(document).ready(function ($) {
    // Filtreleme formunun gönderimini ele al
    $('#filter-form input[type=radio], #filter-form input[type=checkbox], #filter-form input[type=number]').change(function () {
        // Filtreleme formunu REST API ile gönder
        $.ajax({
            type: 'POST',
            url: my_ajax_object.ajax_url,
            data: {
                action: 'my_custom_endpoint', // Endpoint adı
                nonce: my_ajax_object.nonce,
                form_data: $('#filter-form').serialize()
            },
            success: function (response) {
                // Konsol çıktısı
                console.log(response);

                // Sonuçları gösteren alan güncellemesi
                $('#category-filter-results').html(response);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                // Hata durumunda konsol çıktısı 
                console.error('AJAX Error:', textStatus, errorThrown);
            }
        });
    });
});

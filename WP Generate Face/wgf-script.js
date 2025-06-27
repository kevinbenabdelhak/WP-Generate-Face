jQuery(function($){
    $('#wgf-generate-face').on('click', function(e){
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#wgf-face-message').html('Génération en cours...');

        $.post(wgf_ajax.ajax_url, {
            action: 'wgf_generate_face',
            nonce: wgf_ajax.nonce
        }, function(response){
            if(response.success){
                $('#wgf-face-message').html('<span style="color:green">' + response.data.message + '<br><img src="' + response.data.image_url + '" style="max-width:100px; vertical-align:middle" /></span>');
                // On recharge la page pour voir la nouvelle image dans la médiathèque
                location.reload();
            } else {
                $('#wgf-face-message').html('<span style="color:red">' + response.data + '</span>');
            }
            $btn.prop('disabled', false);
        });
    });
});
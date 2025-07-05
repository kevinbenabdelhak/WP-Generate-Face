jQuery(function($){
    $('#wgf-generate-face').on('click', function(e){
        e.preventDefault();
        var $btn = $(this);
        var count = parseInt($('#wgf-image-count').val()) || 1;
        var current = 0;

        $btn.prop('disabled', true);
        $('#wgf-face-message').text('');
        if ($('#wgf-generated-images').length === 0) {
            $('#wgf-face-message').after('<div id="wgf-generated-images" style="margin-top:10px;"></div>');
        }
        $('#wgf-generated-images').empty();

        function generateOneImage() {
            if (current >= count) {
                $('#wgf-face-message').text('Toutes les images ont été générées.');
                $btn.prop('disabled', false);
                setTimeout(function() {
                    location.reload();
                }, 1000); 
                return;
            }

            current++;
            $.post(wgf_ajax.ajax_url, {
                action: 'wgf_generate_face',
                nonce: wgf_ajax.nonce
            }).done(function(response) {
                if(response.success) {
                    $('#wgf-face-message').text('Image ' + current + '/' + count + ' générée');
                    $('#wgf-generated-images').append(
                        '<div style="display:inline-block; margin:5px; text-align:center;">' +
                        '<img src="' + response.data.image_url + '" style="max-width:100px; vertical-align:middle;" />' +
                        '</div>'
                    );
                } else {
                    $('#wgf-face-message').text('Erreur : ' + response.data);
                }
            }).fail(function(){
                $('#wgf-face-message').text('Erreur lors de la génération de l\'image ' + current);
            }).always(function(){
                setTimeout(generateOneImage, 2000); // attendre 2 secondes avant la prochaine image
            });
        }

        generateOneImage();
    });
});
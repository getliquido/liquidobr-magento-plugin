require(
    ['jquery', 'jquery/ui'],
    function (jQuery) {

        jQuery(document).ready(function () {

            jQuery("body").trigger("processStop");

            jQuery('.payin-method-link-brl').click(function () {
                jQuery("body").trigger("processStart");
            });

        });

    }
);
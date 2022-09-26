require(
    ['jquery', 'jquery/ui'],
    function (jQuery) {

        jQuery(document).ready(function () {

            jQuery("body").trigger("processStop");

            jQuery('#boleto-customer-form').submit(function () {

                console.log(jQuery(this).valid());

                if (jQuery(this).valid()) {
                    jQuery(this).find(':submit').attr('disabled', 'disabled');
                    jQuery("body").trigger("processStart");
                }
            });

        });

    }
);
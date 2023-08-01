require(
    ['jquery', 'jquery/ui'],
    function($) {
        $(document).ready(function(){
            $('#establishments').on('change', function(){
                var demovalue = $(this).val(); 
                console.log("Change");
                console.log(demovalue);
                $("div.myDiv").hide();
                $("#show"+demovalue).show();
            });
        });
})
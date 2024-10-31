(function($){
    $(document).ready(function(){
        const $input = $('.seamless_datetime');
        if(window.acf){
            $input.datetimepicker({
                dateFormat: 'yy-mm-dd',
                timeFormat: 'HH:mm:ss',
            });
            if( $('body > #ui-datepicker-div').exists() ) {
                $('body > #ui-datepicker-div').wrap('<div class="acf-ui-datepicker" />');
            }
        } else {
            $input.datetimepicker({
                format: 'Y-m-d H:i'
            });
        }
    });
})(jQuery);
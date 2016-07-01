/* globals jQuery, soWidgetsAdmin */

jQuery(function($){

    $('.dapper-so-widget-toggle-active button').click( function(){
        var $$ = $(this),
            s = $$.data('status'),
            $w = $$.closest('.dapper-so-widget');

        if(s) {
            $w.addClass('dapper-so-widget-is-active').removeClass('dapper-so-widget-is-inactive');
        }
        else {
            $w.removeClass('dapper-so-widget-is-active').addClass('dapper-so-widget-is-inactive');
        }

        // Lets send an ajax request.
        $.post(
            soWidgetsAdmin.toggleUrl,
            {
                'widget' : $w.data('id'),
                'active' : s
            },
            function(data){
                // $sw.find('.dashicons-yes').clearQueue().fadeIn('fast').delay(750).fadeOut('fast');
            }
        );

    } );

    //  Fill in the missing header images
    $('.dapper-so-widget-banner').each( function(){
        var $$ = $(this),
            $img = $$.find('img');

        if( !$img.length ) {
            // Create an SVG image as a placeholder icon
            var pattern = Trianglify({
                width: 128,
                height: 128,
                variance : 1,
                cell_size: 32,
                seed: $$.data('seed')
            });

            $$.append( pattern.svg() );
        }
        else {
            if( $img.width() > 128 ) {
                // Deal with wide banner images
                $img.css('margin-left', -($img.width()-128)/2 );
            }
        }
    } );

    // Lets implement the search
    var widgetSearch = function(){
        var q = $(this).val().toLowerCase();

        if( q === '' ) {
            $('.dapper-so-widget-wrap').show();
        }
        else {
            $('.dapper-so-widget').each( function(){
                var $$ = $(this);

                if( $$.find('h3').html().toLowerCase().indexOf(q) > -1 ) {
                    $$.parent().show();
                }
                else {
                    $$.parent().hide();
                }
            } );
        }
    };
    $('#dapper-sow-widget-search input').on( {
        keyup: widgetSearch,
        search: widgetSearch
    });

    $(window).resize(function() {
        var $descriptions = $('.dapper-so-widget-text').css('height', 'auto');
        var largestHeight = 0;

        $descriptions.each(function () {
            largestHeight = Math.max(largestHeight, $(this).height()  );
        });

        $descriptions.each(function () {
            $(this).css('height', largestHeight);
        });

    }).resize();

    // Handle the tabs
    $('#dapper-sow-widgets-page .page-nav a').click(function(e){
        e.preventDefault();
        var $$ = $(this);
        var href = $$.attr('href');

        var $li = $$.closest('li');
        $('#dapper-sow-widgets-page .page-nav li').not($li).removeClass('active');
        $li.addClass('active');

        switch( href ) {
            case '#all' :
                $('.dapper-so-widget-wrap').show();
                break;

            case '#enabled' :
                $('.dapper-so-widget-wrap').hide();
                $('.dapper-so-widget-wrap .dapper-so-widget-is-active').each(function(){ $(this).closest('.dapper-so-widget-wrap').show(); });
                $('.dapper-so-widget-wrap .dapper-so-widget-is-inactive').each(function(){ $(this).closest('.dapper-so-widget-wrap').hide(); });
                break;

            case '#disabled' :
                $('.dapper-so-widget-wrap .dapper-so-widget-is-active').each(function(){ $(this).closest('.dapper-so-widget-wrap').hide(); });
                $('.dapper-so-widget-wrap .dapper-so-widget-is-inactive').each(function(){ $(this).closest('.dapper-so-widget-wrap').show(); });
                break;
        }

        $(window).resize();
    });

    // Finally enable css3 animations on the widgets list
    $('#widgets-list').addClass('dapper-so-animated');
});
//Bind events to the page
jQuery(document).ready(function (jQuery) {
	
	jQuery('.collapse').on('click', function () {
        jQuery(this).siblings('.grid-div').toggleClass( "hidden collapse-content-show" );
        jQuery(this).children(".dashicons").toggleClass('collapse-open')
    });
	
	
	
	jQuery( "#delete_log" ).click(function() {
	  jQuery.ajax({
        type: "GET",
        url: myAjax.ajaxurl,
        data: 'action=delete_log',
        success: function (html)
        {
			if(html == 'success'){
				location.reload();				
			}
        }
    });
	});
});
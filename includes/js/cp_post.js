var WPSetThumbnailHTML, WPSetThumbnailID, WPRemoveThumbnail;

(function($){

WPSetThumbnailHTML = function(html){
	var postID = getQuerystring('post_id', html);
	var thisSelector = '#postimagediv-'+postID;
	var thisFormSelector = '#postuploadform-'+postID;
	$(thisFormSelector).hide();
	$('.inside', thisSelector).html(html);
};

WPSetThumbnailID = function(id){
	var field = $('input[value=_thumbnail_id]', '#list-table');
	if ( field.size() > 0 ) {
		$('#meta\\[' + field.attr('id').match(/[0-9]+/) + '\\]\\[value\\]').text(id);
	}
};

WPRemoveThumbnail = function(nonce){
	var postID = getQuerystring('post_id', html);
	$.post(ajaxurl, {
		action:"set-post-thumbnail", post_id: postID, thumbnail_id: -1, _ajax_nonce: nonce, cookie: encodeURIComponent(document.cookie)
	}, function(str){
		if ( str == '0' ) {
			alert( setPostThumbnailL10n.error );
		} else {
			WPSetThumbnailHTML(str);
		}
	}
	);
};

function getQuerystring(key, html, default_) {
  if ( default_==null ) default_="";
  key = key.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regex = new RegExp("[\\?&]"+key+"=([^&#]*)");
  var qs = regex.exec(html);
  if ( qs == null )
    return default_;
  else
    return qs[1];
}

})(jQuery);
/* global tinymce */
(function() {
	tinymce.create('tinymce.plugins.wpcheckout', {

		init : function(ed, url) {
			var t = this;

			t.url = url;
			t.editor = ed;
			t._createWpcktButtons();
			// override wordpress _hideButtons()
			t._hideButtons(ed);

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('...');
			ed.addCommand('WP_Checkout', function() {
				if ( tinymce.isIE )
					ed.selection.moveToBookmark( ed.wpCheckoutBookmark );

				var el = ed.selection.getNode(),
				    attrs,
					strRequest = "";

				// Check if the `wp.media` and wpcktShortcode API exists.
				if ( typeof wp === 'undefined' || ! wp.media || typeof wpcktShortcode === 'undefined'){
					return;
				}

				// Make sure we've selected a checkout node.
				if ( ed.dom.getAttrib(el, 'class').indexOf('wp-checkout') == -1 )
					return;

				var imageAttrs = ed.dom.getAttrib( el, 'title' );
				
				attrs = wpcktShortcode.GetAttrs(imageAttrs);
				
				for (var key in attrs) {
					if (attrs[key] != '') strRequest += key+'='+escape(attrs[key])+'&';
				}			
				

				t._openEditor(strRequest);
				
			});
			
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('...');
			ed.addCommand('WP_Checkout_Remove', function(h) {
                var node = ed.selection.getNode();
					ed.dom.remove(node);
					ed.execCommand('mceRepaint');	
			  
			});

			ed.onInit.add(function(ed) {
				// iOS6 doesn't show the buttons properly on click, show them on 'touchstart'
				if ( 'ontouchstart' in window ) {
					ed.dom.events.add(ed.getBody(), 'touchstart', function(e){
						var target = e.target;

						if ( target.nodeName == 'IMG' && ed.dom.hasClass(target, 'wp-checkout') ) {
							ed.selection.select(target);
							ed.dom.events.cancel(e);
							ed.plugins.wordpress._hideButtons();
							ed.plugins.wordpress._showButtons(target, 'wp_checkoutbtns');
						}
					});
				}
			});

			ed.onMouseDown.add(function(ed, e) {
				if ( e.target.nodeName == 'IMG' && ed.dom.hasClass(e.target, 'wp-checkout') ) {
					ed.plugins.wordpress._hideButtons();
					ed.plugins.wordpress._showButtons(e.target, 'wp_checkoutbtns');
				}
			});

			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = t._do_wpckt_label(o.content);
			});

			ed.onPostProcess.add(function(ed, o) {
				if (o.get)
					o.content = t._get_wpckt_label(o.content);
			});
		},

		_do_wpckt_label : function(co) {
			return co.replace(/\[wp_checkout([^\]]*)\]/g, function(a,b){
				
			  
			  var attrs = wpcktShortcode.GetAttrs(tinymce.DOM.encode(b));
			  var labelWidth = attrs['width'] || "";
			  var labelWidthUnit = attrs['width_unit'] || "";
			  var labelAlign = attrs['align'] || "";
			  var imgClass = "";
			  
			  labelAlign = labelAlign.replace( /&quot;/g, '' );
			  
			  switch(labelAlign) {
                 case "left":
				     imgClass = "alignleft";
				     break;
                 case "right":
				     imgClass = "alignright";
				     break;
                 case "center":
				     imgClass = "aligncenter";
				     break;
			  }
				
			  return '<img '
			         +'src="'
					 +tinymce.baseURL+'/../../../wp-content/plugins/wordpress-checkout/includes/tinymce/wpcheckout/img/t.gif" '
					 +'class="wp-checkout mceItem '+imgClass+'" '
					 +'title="wp_checkout'+tinymce.DOM.encode(b)+'"'
					 +'style="width:'+labelWidth.replace( /&quot;/g, '' )+labelWidthUnit.replace( /&quot;/g, '' )+'"'
					 + '/>';
			});
		},

		_get_wpckt_label : function(co) {

			function getAttr(s, n) {
				n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
				return n ? tinymce.DOM.decode(n[1]) : '';
			}

			return co.replace(/(?:<p[^>]*>)*(<img[^>]+>)(?:<\/p>)*/g, function(a,im) {
				var cls = getAttr(im, 'class');

				if ( cls.indexOf('wp-checkout') != -1 )
					return '<p>['+tinymce.trim(getAttr(im, 'title'))+']</p>';

				return a;
			});
		},
        
		// create DOM buttons funtion
		_createWpcktButtons : function() {
			var t = this, 
			    ed = tinymce.activeEditor, 
				DOM = tinymce.DOM, 
				editButton, 
				dellButton, 
				isRetina;
				
            // check if button container already exist
			if ( DOM.get('wp_checkoutbtns') )
				return;

			isRetina = ( window.devicePixelRatio && window.devicePixelRatio > 1 ) || // WebKit, Opera
				( window.matchMedia && window.matchMedia('(min-resolution:130dpi)').matches ); // Firefox, IE10, Opera
            
			// add button container
			DOM.add(document.body, 'div', {
				id : 'wp_checkoutbtns',
				style : 'display:none'
			});

			editButton = DOM.add('wp_checkoutbtns', 'img', {
				src : isRetina ? t.url+'/img/wp_checkout_label_edit_2x.png' : t.url+'/img/wp_checkout_label_edit.png',
				id : 'wp_editwpckt',
				width : '24',
				height : '24',
				title : ed.getLang('wpckt.edit')
			});

			//tinymce.dom.Event.add( editButton, 'mousedown', function() {
			editButton.onmousedown = function(e) {														   
				var ed = tinymce.activeEditor;
				ed.wpCheckoutBookmark = ed.selection.getBookmark('simple');
				ed.execCommand('WP_Checkout');
				ed.plugins.wordpress._hideButtons();
			};

			dellButton = DOM.add('wp_checkoutbtns', 'img', {
				src : isRetina ? t.url+'/img/delete-2x.png' : t.url+'/img/delete.png',
				id : 'wp_delwpckt',
				width : '24',
				height : '24',
				title : ed.getLang('wpckt.delete')
			});

			//tinymce.dom.Event.add(dellButton, 'mousedown', function(e) {
			dellButton.onmousedown = function(e) {														
				var ed = tinymce.activeEditor, el = ed.selection.getNode();

				if ( el.nodeName == 'IMG' && ed.dom.hasClass(el, 'wp-checkout') ) {
					ed.dom.remove(el);

					ed.execCommand('mceRepaint');
					ed.dom.events.cancel(e);
				}

				ed.plugins.wordpress._hideButtons();
			};
		},

		_hideButtons : function(ed) {
			var defaultHideButtons = ed.plugins.wordpress._hideButtons;
			ed.plugins.wordpress._hideButtons = function(){
			   defaultHideButtons();
			   var DOM = tinymce.DOM;
			   DOM.hide( DOM.select('#wp_checkoutbtns') );
			}
		},
		
		_updateLabel : function() {
			var defaultHideButtons = ed.plugins.wordpress._hideButtons;
			ed.plugins.wordpress._hideButtons = function(){
			   defaultHideButtons();
			   var DOM = tinymce.DOM;
			   DOM.hide( DOM.select('#wp_checkoutbtns') );
			}
		},		
		
		_openEditor : function(attrs){
			
			var ed = tinymce.activeEditor, url = this.url, el = ed.selection.getNode(), vp, H, W, cls = el.className;

			if ( cls.indexOf('mceItem') == -1 || cls.indexOf('wp-checkout') == -1 )
				return;

			vp = tinymce.DOM.getViewPort();
			H = 700 < (vp.h - 70) ? 680 : vp.h - 70;
			W = 700 < vp.w ? 700 : vp.w;
			
			ed.windowManager.open({
				//file: url + '/editimage.html',
				file: tinymce.baseURL+'/../../../wp-admin/media-upload.php?chromeless=1&post_id=70&tab=wpckt&'+attrs,
				width: W+'px',
				height: H+'px',
				inline: true
			});					
			
		},
		
		_closeEditor : function(e){
			var ed = top.tinymce.activeEditor,
			    id = 'mceModalBlocker';
				ed.windowManager.close(null, id);
		},	

		getInfo : function() {
			return {
				longname : 'WP Checkput Shortcode Label',
				author : 'Alain Gonzalez',
				authorurl : 'http://wordpress-checkout.com',
				infourl : '',
				version : '1.0'
			};
		}
	});

	tinymce.PluginManager.add('wpcheckout', tinymce.plugins.wpcheckout);
})();

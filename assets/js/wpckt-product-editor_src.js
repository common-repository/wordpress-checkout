/**
 * Wordpress Checkout 
 * Plugin URI: http://wordpress-checkout.com/
 *
*/

var wpcktShortcode = wpcktShortcode || {};

(function($){	       
	
	       $(document).ready(function(){
			   
			    // Update shortcode after loading.
				wpcktShortcode.Update(); 
      
	             // Open media lightbox showing wp checkout 
				 $('.wpckt-add-product').on( 'click', function( event ) {
					var $this = $(this),
						editor = $this.data('editor'),
	  					options = {
	  						frame:    'post',
	  						state:    'iframe:wpckt',
	  						title:    'Insert Product',
	  						multiple: true
	  					};
	  
	  				event.preventDefault();
	  
	  				// Remove focus from the `.insert-media` button.
	  				// Prevents Opera from showing the outline of the button
	  				// above the modal.
	  				//
	  				// See: http://core.trac.wordpress.org/ticket/22445
	  				$this.blur();	  

	  				wp.media.editor.open( editor, options );

	  			});	
				

				 
				 // Update sections
				 $('.wpckt-section').each(function(index,elem){
					 
     				 var $this = $(this);
					 if ($this.is(":checked")){
						 if (!$this.hasClass('product')){
						   wpcktShortcode.Update($this);
						 } else {
						   wpcktShortcode.Update();
						 }
					 }
					 
				 });				
				
				// Insert Shortcode to content				
				$('.wpckt-insert-product').on( 'click', function( event ) {
					event.preventDefault();
					var wp = parent.wp;
					if ( $('.wpckt-section').not('.product').is(":checked") ){		
						
						var urlVars = getUrlVars();						
						
						wpcktSettings.action = "wpckt_editor_insert_shortcode";
						wpcktSettings.code = wpcktShortcode.Render();
						if ( typeof urlVars['post_id'] != "undefined" ){
						  wpcktSettings.post_id = urlVars['post_id'];	
						}
						wpcktAjaxAdmin( wpcktSettings, function(response){
								  if (typeof response != "undefined"){									
									  wpcktShortcode.Insert();      
									  return; 
								  }
							}
						);						
						
				        
					} else if (wpcktShortcode.Validate()){
                        wpcktShortcode.Insert();    
					} 
				});
				
				
				// Updating Shortcode in content				
				$('.wpckt-update-product').on( 'click', function( event ) {
					event.preventDefault();

                        wpcktShortcode.Insert(true);    

				});
				
								
				// Handling click of form elemnts 
				 $('.wpckt-sc').on( 'blur', function( event ) {
					 
					 $this = $(this);
					 if ($this.hasClass('format')){						 
						 if ( IsNumeric( $this.val().replace(',','.') ) ){
							AddSpinner($this);
							wpcktSettings.action = "wpckt_format";
							wpcktSettings.types = $this.prop("class");
							wpcktSettings.amount = $this.val();					
							wpcktAjaxAdmin( wpcktSettings, function(response){
									  if (typeof response != "undefined"){									
										  $this.val(response.value);
										  wpcktShortcode.Update();
										  HideSpinners();
										  return; 
									  }
								}
							);	
						 } else {
							 $this.val("");
							 return;
						 }
					 } else {
						 wpcktShortcode.Update();
					 }					 

					 					 
				 }).on( 'click', function() {
					 
					 var $this = $(this);
					 var $checked = $this.is(":checked");
					 var prodDisable = false;
					 
					 // If section
					 if ($this.hasClass('wpckt-section')){						 
						 if (!$this.hasClass('product')){
	  						prodDisable = true;
	  						$(".product-table").addClass("wpckt-opaque");
							wpcktShortcode.Update($this); 
	  					 } else {
							 // Is product
	  						$(".product-table").removeClass("wpckt-opaque");
							wpcktShortcode.Update();													
	  					 }
						 $('.enabled').prop('disabled', prodDisable);
						 return true;
				     }				 
					 
					 $this.removeClass("wpckt-wrong-field");
					 
					 wpcktShortcode.Update();
					
			     });

												
				
			});
			

			wpcktShortcode = {
				
				settings : {
					  start : '[wp_checkout',
					  end : ']',
					  shortcodeFields : '.wpckt-sc',
					  shortcodeTextArea : '#wpckt_shortcode'
				},
				
				code : [],				
			
				Update : function (field){
					this.code = [];
					if(typeof field != "undefined"){						
						this.Populate(field);
					} else {
					    var that = this; 
					    $.each( $(this.settings.shortcodeFields),function(){
							var field = $(this);                 
	                        that.Populate(field);
						});
					}
					this.Render();				
				},
				
				Populate : function(field){
					if (field.is(':checkbox')){
						if (field.is(':checked')) this.code[field.prop('name')] = field.prop('value');
						else this.code[field.prop('name')] = "";
					} else if (field.is(':radio')){
						if (field.is(':checked')) this.code['section'] = field.prop('value');
					} else {
						var value = field.prop('value');
						this.code[field.prop('name')] = value.replace(/"/g, '\'');
					}
				},
				
				Render : function(){
					var finalCode = this.settings.start;
					for (var key in this.code) {
						if (this.code[key] != '') finalCode += ' '+key+'="'+this.code[key]+'"';
					}
					finalCode += this.settings.end;					
					$(this.settings.shortcodeTextArea).val(finalCode);
					return finalCode;
				},				
		
				
				Validate : function(){
					$.each($(".required"),function(index,value){
						var $this = $(this);
						if ($this.prop("value") == "")  { 
						   $this.addClass("wpckt-wrong-field").prop("placeholder","required");
						}
						$this.on("click", function( event ) {
		  					event.preventDefault();
		  					$(this).removeClass("wpckt-wrong-field");
		  				});
					});
					if ($(".wpckt-wrong-field").size() > 0 ) return false;
					else return true;
						
				}, 				
	

                // Based on wp.media.editor.insert: /wp-includes/js/media-editor.js
				Insert: function(exist) {					
					var win = (window.dialogArguments || opener || parent || top ) ,
					    tinymce = win.tinymce,
					    mce = typeof(tinymce) != 'undefined',
						QTags = win.QTags,
						qt = typeof(QTags) != 'undefined',						
						wpActiveEditor = win.wpActiveEditor,
						ed,
						h = this.Render();								

					// Delegate to the global `send_to_editor` if it exists.
					// This attempts to play nice with any themes/plugins that have
					// overridden the insert functionality.
					if ( window.send_to_editor )
						return window.send_to_editor.apply( this, arguments );
		
					if ( ! wpActiveEditor ) {
						if ( mce && tinymce.activeEditor ) {
							ed = tinymce.activeEditor;
							wpActiveEditor = window.wpActiveEditor = ed.id;
						} else if ( !qt ) {
							return false;
						}
					} else if ( mce ) {
						if ( tinymce.activeEditor && (tinymce.activeEditor.id == 'mce_fullscreen' || tinymce.activeEditor.id == 'wp_mce_fullscreen') )
							ed = tinymce.activeEditor;
						else
							ed = tinymce.get(wpActiveEditor);
					}				
		
					if ( ed && !ed.isHidden() ) {
						
						if (exist){
							ed.execCommand('WP_Checkout_Remove');
						}
						// restore caret position on IE
						if ( tinymce.isIE && ed.windowManager.insertimagebookmark )
							ed.selection.moveToBookmark(ed.windowManager.insertimagebookmark);
		
						if ( h.indexOf('[wp_checkout') !== -1 ) {
						  if ( ed.plugins.wpcheckout )
								  h = ed.plugins.wpcheckout._do_wpckt_label(h);
						} 
		
						ed.execCommand('mceInsertContent', false, h);
					} else if ( qt ) {
						QTags.insertContent(h);
					} else {
						document.getElementById(wpActiveEditor).value += h;
					}
					
					if (exist){
					  ed.windowManager.close(window);
					} else {                          
					  var mediaEditor = win.wp.media.editor.get();
					  mediaEditor.close();
					}
		
				},

				
				GetAttrs : function( text ) {
					// From wp.shortcode" /wp-includes/js/shortcode.js
					var named   = [],
						//numeric = [],
						pattern, match;
		
					// This regular expression is reused from `shortcode_parse_atts()`
					// in `wp-includes/shortcodes.php`.
					//
					// Capture groups:
					//
					// 1. An attribute name, that corresponds to...
					// 2. a value in double quotes.
					// 3. An attribute name, that corresponds to...
					// 4. a value in single quotes.
					// 5. An attribute name, that corresponds to...
					// 6. an unquoted value.
					// 7. A numeric attribute in double quotes.
					// 8. An unquoted numeric attribute.
					pattern = /(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/g;
		
					// Map zero-width spaces to actual spaces.
					text = text.replace( /[\u00a0\u200b]/g, ' ' );
		
					// Match and normalize attributes.
					while ( (match = pattern.exec( text )) ) {
						if ( match[1] ) {
							named[ match[1].toLowerCase() ] = match[2];
						} else if ( match[3] ) {
							named[ match[3].toLowerCase() ] = match[4];
						} else if ( match[5] ) {
							named[ match[5].toLowerCase() ] = match[6];
						} /* else if ( match[7] ) {
							numeric.push( match[7] );
						} else if ( match[8] ) {
							numeric.push( match[8] );
						}*/
					}
		
					return  named;
						
				}
				
			};
		
			function wpcktAjaxAdmin(settings, callBack){
				$.post(
					settings.ajaxUrl,				
	                settings,
					callBack,
					"json"
				);		 
	        }
			
			function AddSpinner ($this){
				var $spinner = $this.siblings('.wpckt-spinner');
				if ($spinner.size() == 0){
				   $this.after('<div class="wpckt-spinner"></div>');
				} else {
					$spinner.show();
				}
			}
			
			function HideSpinners(){
				$('.wpckt-spinner').hide();
			}			
			
			function IsNumeric(input){
			    return (input - 0) == input && (input+'').replace(/^\s+|\s+$/g, "").length > 0;
			}
			
			function getUrlVars()
			{
				var vars = [], hash;
				var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
				for(var i = 0; i < hashes.length; i++)
				{
					hash = hashes[i].split('=');					
					vars[hash[0]] = hash[1];
				}
				return vars;
			}
							
					
}(jQuery));

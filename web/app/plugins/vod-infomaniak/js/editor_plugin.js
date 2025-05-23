/**
 * Regroupement de fonctions JS permettant d'utiliser le plugin VOD et VOD2
 *
 * @author infomaniak
 * @link http://statslive.infomaniak.ch/vod/api/
 * @version 1.2
 * @copyright infomaniak.ch
 *
 */

 //Centrer l'overlay
 //jQuery("#dialog-vod-form").dialog( "option", {'position' : 'center'} );

function toggleTextFields() {
	const fixedRadio = document.getElementById('fixed');
	const textFields = document.getElementById('textFields');

	if (fixedRadio.checked) {
		textFields.classList.remove('hidden');
	}else{
		textFields.classList.add('hidden');
	}
}

//Fonction permettant de cacher l'overlay de configuration
Vod_dialogOpen = function () {
    if (jQuery('#dialog-player')) {
        var iPlayerCode = parseInt(jQuery('#dialog-player').val(),10);
        jQuery('#vod-up').html("");
        if(jQuery('#player_'+iPlayerCode+'_height')){
            jQuery("#dialog-vod-form").dialog('open');
        	jQuery("#dialog-url-input").value = "";
        	jQuery("#dialog-slide-header").removeClass("selected");
        	jQuery("#dialog-slide").hide();
        	Vod_setPlayerOptions();
        	Vod_selectTab(2);
            return true;
        }
    }
    jQuery('#dialog-vod-logout').dialog({modal: true});
    jQuery("#dialog-vod-logout").dialog( "option", {'position' : 'center'} );
}

Vod_setPlayerOptions = function (){
    var iPlayerCode = parseInt(jQuery('#dialog-player').val(),10);
    if(iPlayerCode > 0 && jQuery('#player_'+iPlayerCode+'_height')){
        jQuery('#dialog-width-input').val(jQuery('#player_'+iPlayerCode+'_width').val());
        jQuery('#dialog-height-input').val(jQuery('#player_'+iPlayerCode+'_height').val());

        if(parseInt(jQuery('#player_'+iPlayerCode+'_stretch').val(),10) == 1){
            jQuery('#dialog-stretch').attr('checked', true);
        }else{
            jQuery('#dialog-stretch').attr('checked', false);
        }

        if(parseInt(jQuery('#player_'+iPlayerCode+'_autoload').val(),10) == 1){
            jQuery('#dialog-autostart').attr('checked', true);
        }else{
            jQuery('#dialog-autostart').attr('checked', false);
        }

        if(parseInt(jQuery('#player_'+iPlayerCode+'_loop').val(),10) == 1){
            jQuery('#dialog-loop').attr('checked', true);
        }else{
            jQuery('#dialog-loop').attr('checked', false);
        }
        return true;
    }
}

//Fonction permettant de naviguer entre les onglets
Vod_selectTab = function (iTabCode) {
	if (jQuery("#dialog-slide-header").hasClass("selected") ) {
		jQuery("#dialog-slide-header").removeClass("selected");
		jQuery("#dialog-slide").slideUp('fast');
	}

	jQuery('.tabSelected').each(function(index, oTab){
		jQuery('#'+oTab.id).removeClass("tabSelected");
		jQuery('#'+oTab.id).fadeOut();
	});

	if(jQuery('#dialog-tab'+iTabCode)){
		jQuery('#dialog-tab'+iTabCode).fadeIn();
		jQuery('#dialog-tab'+iTabCode).addClass("tabSelected");
	}

	if(parseInt(iTabCode,10) == 2){
		if(jQuery('#vod_infomaniak_search_videos'))jQuery('#vod_infomaniak_search_videos').focus();
	}else if(parseInt(iTabCode,10) == 4){
		if(jQuery('#vod_infomaniak_search_playlists'))jQuery('#vod_infomaniak_search_playlists').focus();
	}
	if(parseInt(iTabCode,10) == 3){
		jQuery('#vodUploadVideo').hide();
		jQuery('#vodEncodeVideo').hide();					
		jQuery('#vodUploadLoader').hide();
		jQuery('#vod-up').append("");
		jQuery('#encodeProgress').html("");
	}
};

//Fonction permettant d'afficher ou non les options d'integration
Vod_dialogToggleSlider = function(){
	if ( !jQuery("#dialog-slide-header").hasClass("selected") ) {
		jQuery("#dialog-slide-header").addClass("selected");
		jQuery('.tabSelected').slideUp();
		jQuery("#dialog-slide").slideDown();
	} else {
		jQuery("#dialog-slide-header").removeClass("selected");
		jQuery("#dialog-slide").slideUp();
		jQuery('.tabSelected').slideDown();
	}
};

Vod_selectVideo = function (oElement, sUrl,sToken,iFolder) {
	jQuery('#dialog-url-input').val( sUrl );
	jQuery('#dialog-token').val( "" );
	if( sToken != "" ){
		jQuery('#dialog-token').val( iFolder );
	}
	jQuery('.vod_element_selected').each(function(index, oElement){jQuery(oElement).removeClass('vod_element_selected')});
	jQuery(oElement).addClass("vod_element_selected");
	Vod_dialogToggleSlider();
};

//Fonction permettant de cacher l'overlay de configuration
Vod_dialogClose = function () {
	jQuery("#dialog-vod-form").dialog("close");
};

sVodUploadParameters = "";

Vod_importVideo = function () {

	if ( jQuery('#uploadSelectFolder').val() > 0 ){
		jQuery('#vodUploadVideo').show();
		jQuery('#vodUploadLoader').show();
		jQuery('#vod-up').append("");	

		jQuery('#vodImportVideoByUrl').hide();
		jQuery('#vodEncodeVideo').hide();
		jQuery('#VodEncodeS1, #VodEncodeS2, #VodEncodeS3').removeClass('stepEncodeOK');

		jQuery.ajax({
			url: jQuery("#url_ajax_import_video").val(),
			cache: false,
			processData: false,
			data: "iFolder="+jQuery('#uploadSelectFolder').val(),
			success: function(sToken){
				try {
					jQuery('#dialog-tabs').tabs( "disable", [0,1,2,3] );
					sVodUploadParameters = sToken;
					//flashUpload( sToken );
					newsUpload( sToken );
				}catch( e ){
					alert('ERROR : '+e);
				}
			}
		});
	} else {
		alert("select a folder !");
		jQuery('#dialog-tabs').tabs( "enable", [0,1,2,3] );
		jQuery('#vodUploadVideo').hide();
	}
};

newsUpload = function (sKey) {
	jQuery('#vod-up').html('<form id="direct_vod_upload" action="" enctype="multipart/form-data" method="post" ><input type="file" name="iKfile" id="iKfile" /><input type="button" class="button" value="Upload" id="but_upload" onclick="vodInitUpload();"><input type="hidden" id="tokenUpload" name="tokenUpload" value="'+sKey+'"/></form>');
	jQuery('#vodUploadLoader').hide();

};

checkAuth = function () {
	if (jQuery("#bNeedAuth").prop('checked')) {
		jQuery("#authLine").show();
	} else {
		jQuery("#authLine").hide();
	}
};


downloadFromURL = function () {
	jQuery("#vodImportLoader").show();
	jQuery('#vodImportButton').hide();

	jQuery.ajax({
			url: jQuery("#url_ajax_import_video").val().replace("vodimportvideo", "vodimportvideofromurl"),
			cache: false,
			processData: false,
			data: "iFolder="+jQuery('#uploadSelectFolder').val()+"&url="+jQuery("#sUrl").val(),
			success: function(video){
				try {
					if (video == 'error'){
						alert('unable to retrieve this file (4xx)');
						jQuery("#vodImportLoader").hide();
						jQuery('#vodImportButton').show();
					}else{
						jQuery("#vodEncodeVideo").show();
						setTimeout(vodCheckDispoPostImport, 5000,video,jQuery('#uploadSelectFolder').val(),1);
					}
				}catch( e ){
					alert('ERROR : '+e);
				}
			}
		});
};

vod_importPopup = function () {		
	if (jQuery('#version').val() == 2){
		jQuery('#vodUploadVideo').hide();
		jQuery('#vodEncodeVideo').hide();

		if ( jQuery('#uploadSelectFolder').val() > 0 ){
			jQuery('#vodImportVideoByUrl').show();
		}else{
			alert("select a folder !");
		}

	}else{
		var height = 550;
		var width = 1024;
		var top = (screen.height - height) / 2;
		var left = (screen.width - width) / 2;
		window.open('<?php echo $actionurl; ?>&sAction=popupImport&iFolderCode=' + jQuery("#uploadSelectFolder").val(), 'importTool' + jQuery("#uploadSelectFolder").val(),
			config = 'height=' + height + ', width=' + width + ', top=' + top + ', left=' + left + ', toolbar=no, menubar=no, scrollbars=no, resizable=no, location=no, directories=no, status=no'
		);
	}
};

checkURL = function () {
	var url = jQuery('#sUrl').val();
	if (url.indexOf("http://") == -1 && url.indexOf("https://") == -1 && url.indexOf("ftp://") == -1) {
		jQuery('#sUrl').val("https://"+url);
		}
};

   	
vodInitUpload = function(){
	var sKey=jQuery("#tokenUpload").val();
	var fd = new FormData();
	var oFile = jQuery('#iKfile');
	jQuery('#vod-up').html('Upload : <progress id="progressVod"></progress><span id="progressVodPercent"></span>');
	var files = oFile[0].files;

	// Check file selected or not
	if(files.length > 0 ){
		fd.append('iKfile',files[0]);

		jQuery.ajax({
			url: 'https://api.vod2.infomaniak.com/v1soap/soap/upload?key='+sKey,
			type: 'post',
			data: fd,
			contentType: false,
			processData: false,
			xhr: function() {
				var xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener("progress", function(evt) {
					if (evt.lengthComputable) {
						var percentComplete = Math.floor((evt.loaded / evt.total) * 100);
						document.querySelector('#progressVod').value = evt.loaded;
						document.querySelector('#progressVod').max = evt.total;		
						jQuery('#progressVodPercent').html(	" "+ percentComplete + " %" );	                
						if (percentComplete >= 100){
							//parfois un peu long entre step 100% et step readyState=4, du coup, on affiche qu'il se passe quelque chose
							jQuery('#vodEncodeVideo').show();
							jQuery('#VodEncodeS1').addClass('stepEncodeOK');
						}
					}
				}, false);
				xhr.onreadystatechange = function (e) {
					if (this.readyState == 4) {
						response = JSON.parse(this.responseText);
						const media = response.medias;
						for (x in media) {   
							vodEndUpload(x,sKey);
						}
					}
				}
	        return xhr;
			}
		});
	}else{
		alert("Please select a file.");
	}
}

vodEndUpload = function(file,sKey){
	jQuery('#vodEncodeVideo').show();
	jQuery('#VodEncodeS1').addClass('stepEncodeOK');
	jQuery.ajax({
		url: jQuery("#url_ajax_import_video").val().replace("vodimportvideo", "vodimportvideoending"),
		cache: false,
		processData: false,
		data: "iFolder="+jQuery('#uploadSelectFolder').val()+"&file="+file+"&sToken="+sKey+"&nonce="+customData.nonce,
		success: function(sToken){
			try {				
				jQuery('#VodEncodeS2').addClass('stepEncodeOK');
				jQuery('#encodeVodLoader').show();
				setTimeout(vodCheckDispoPostUpload, 5000,file,sKey,jQuery('#uploadSelectFolder').val(),1);
			}catch( e ){
				alert('ERROR : '+e);
			}
		}
	});
}



vodSynchroAjax = function(iPage,iTotalCounter){
		if (iPage == 0){
			jQuery('#synchroButton').prop('disabled', true);
			jQuery('#synchrapidButton').prop('disabled', true);
			jQuery('#importProgress').html("En cours ... ");
		}
		jQuery.ajax({
		url: jQuery("#url_ajax_synchro_video").val(),
		cache: false,
		processData: false,
		data: "iTotalCounter="+iTotalCounter+"&iPage="+iPage+"&nonce="+customData.nonce,
		dataType: "json",
		success: function(result){
			try {
				var iCur = (result.iLot*(result.iPage+1));
				var iTotal = result.iTotalCounter;
				if (iCur > iTotal){
					iCur = iTotal;
					jQuery('#importProgress').html("Importation terminee ... "+iCur+" / "+result.iTotalCounter+" (100%)");
					jQuery('#iTotalVideo').html(result.iTotalCounter);
					jQuery('#synchroButton').prop('disabled', false);
					jQuery('#synchrapidButton').prop('disabled', false);

				}else{
					var iPercent = Math.floor(iCur / result.iTotalCounter * 100);
					jQuery('#importProgress').html("En cours ... "+iCur+" / "+result.iTotalCounter+" ( ~"+iPercent+"%)");
					setTimeout(vodSynchroAjax, 2000,(iPage+1),result.iTotalCounter);
				}
			}catch( e ){
				alert('ERROR : '+e);
			}
		}
	});
}

vodCheckDispoPostImport = function(sUid,iFolder){
	jQuery.ajax({
		url: jQuery("#url_ajax_import_video").val().replace("vodimportvideo", "vodgetmediastate"),
		cache: false,
		processData: false,
		data: "file="+sUid+"&iFolder="+iFolder+"&nonce="+customData.nonce,
		dataType: "json",
		success: function(result){
			try {
				jQuery('#downloadProgress').html("");
				switch(result.state){
					case 'DELETED':
						alert('unable to retrieve this file (deleted)');
						jQuery('#vodImportLoader').hide();
						jQuery('#vodEncodeVideo').hide();
						jQuery('#vodImportButton').show();
					break;
					case 'UPLOAD':
					case 'START':
						if(parseInt(result.uploadProgress)>=0 && parseInt(result.uploadProgress)<=100 && result.progress == false) {
							jQuery('#vodImportLoader').show();
							jQuery('#downloadProgress').html(" (~"+result.uploadProgress+"%)");
						}else{
							jQuery('#VodEncodeS1').addClass('stepEncodeOK');
							jQuery('#VodEncodeS2').addClass('stepEncodeOK');
							jQuery('#vodImportLoader').hide();
							jQuery('#vodEncodeVideo').show();
							jQuery('#encodeStep').html("Encodage en cours");
							if(parseInt(result.progress)>=0 && parseInt(result.progress)<=100) {
								jQuery('#encodeProgress').html(" (~"+result.progress+"%)");
							}else{
								jQuery('#encodeProgress').html("");
							}
						}
						setTimeout(vodCheckDispoPostImport, 2500,sUid,iFolder,1);
					break;
					case 'OK':
						jQuery('#encodeStep').html("Encodage termine");
						jQuery('#VodEncodeS3').addClass('stepEncodeOK');
						jQuery('#notaDispo').addClass('stepEncodeOK');
						jQuery('#encodeProgress').html("");
						jQuery('#encodeVodLoader').hide();
						jQuery('#vodImportButton').show();
						jQuery('#dialog-url-input').val( result.sServerCode );	//to do avant ligne du dessus ??
						setTimeout(refreshImportTab, 2500);
					break;
				}
			}catch(e){
				alert('ERROR : '+e);
			}
		}
	});
}
 
refreshImportTab = function(){
	jQuery.ajax({
		type: "post", url: "admin-ajax.php", data: { action: 'importvod'},
		success: function (html) {
			jQuery("#tabImport").html(html);
		}
	});
}

vodCheckDispoPostUpload = function(file,sKey,folder,counter){
	jQuery.ajax({
		url: jQuery("#url_ajax_import_video").val().replace("vodimportvideo", "vodimportvideodispo"),
		cache: false,
		processData: false,
		data: "iFolder="+folder+"&file="+file+"&sToken="+sKey+"&iCounter="+counter+"&nonce="+customData.nonce,
		dataType: "json",
		success: function(result){
			try {
				switch(result.state){
					case 'UPLOAD':
					case 'START':
						setTimeout(vodCheckDispoPostUpload, 2000,file,sKey,folder,counter+1);
						jQuery('#encodeStep').html("Encodage en cours");								
						if(parseInt(result.progress)>=0 && parseInt(result.progress)<=100) {
							jQuery('#encodeProgress').html(" (~"+result.progress+"%)");
						}else{
							jQuery('#encodeProgress').html("");
						}
					break;
					case 'OK':
						jQuery('#encodeStep').html("Encodage termine");
						jQuery('#VodEncodeS3').addClass('stepEncodeOK');
						jQuery('#notaDispo').addClass('stepEncodeOK');
						jQuery('#encodeProgress').html("");
						jQuery('#encodeVodLoader').hide();
						jQuery('#dialog-url-input').val( result.sServerCode );	//to do avant ligne du dessus ??
						setTimeout(refreshImportTab, 2500);
					break;
				}
			}catch( e ){
				alert('ERROR : '+e);
			}
		}
	});
}

changeFolderUp1 = function(){
	jQuery('#vodUploadVideo').hide();
	jQuery('#vodUploadLoader').hide();
	jQuery('#vodEncodeVideo').hide();
	jQuery('#vod-up').html("");
};

//Fonction permettant la validation du formulaire suivant les options choisis
Vod_dialogValid = function () {
	var url = jQuery("#dialog-url-input").val();
	if ( url == null || url == '' ){
		alert('Veuillez saisir une adresse de vidéo valide.');
	}else{
		if( jQuery('#dialog-tabs').tabs('option', 'selected') == 0 || jQuery('#dialog-tabs').tabs('option', 'selected') == 2 || jQuery('#dialog-tabs').tabs('option', 'selected') == 3) {
			alert("Vous devez selectionner une vidéo à ajouter.");
//		} else if ( !jQuery("#dialog-slide-header").hasClass('selected') && jQuery('#dialog-token').val()=="" ) {
		} else if (false) {
			var text = "[vod]" + url + "[/vod]";
		} else {
			//Il y a des options d'integration
			var width = jQuery("#dialog-width-input").val();
			var height = jQuery("#dialog-height-input").val();
			var playerDefault = jQuery("#dialog-player-default").val();
			var player = jQuery("#dialog-player").val();
			var tokenFolder = jQuery('#dialog-token').val();
			var version= jQuery('#version').val();
			if (version == 2){
				jQuery('#dialog-slide-loading').show();
				var text = "[vod version='2'";
				jQuery.ajax({
					url: jQuery("#url_ajax_share_link").val(),
					cache: false,
					processData: false,
					data: "iPlayer="+player+"&sVideo="+url,
					success: function(sToken){
						try {
							if( width != '' ){
								text += " width='"+width+"'";
							}
							if( height != '' ){
								text += " height='"+height+"'";
							}
							if( player != playerDefault ){
								text += " player='"+player+"'";
							}
							if( tokenFolder != '' ){
								text += " tokenfolder='"+tokenFolder+"'";
							}
							if( jQuery("#dialog-slide-header").hasClass('selected') ){
								//Celles qu'on ajoute à chaque fois
								var stretch = jQuery("#dialog-stretch").attr('checked') ? 1 : 0;
								var autostart = jQuery("#dialog-autostart").attr('checked') ? 1 : 0;
								var loop = jQuery("#dialog-loop").attr('checked') ? 1 : 0;
								text += " stretch='"+ parseInt(stretch)+"'";
								text += " autoplay='"+ parseInt(autostart)+"'";
								text += " loop='"+ parseInt(loop)+"'";
							}
							if (jQuery('input[name="formatPlayer"]:checked').attr('id') == 'responsive'){
								text += " responsive='1'";
							}							
							text += ']' + url + "[/vod]";	

							if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
								ed.focus();
								jQuery('#dialog-slide-loading').hide();
								if (tinymce.isIE){
									ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
								}
								ed.execCommand('mceInsertContent', false, text);
							} else{
								edInsertContent(edCanvas, text);
							}
							Vod_dialogClose();
						}catch( e ){
							alert('ERROR : '+e);
						}
					}
				});
			}else{
				var text = '[vod';
				if( width != '' ){
					text += " width='"+width+"'";
				}
				if( height != '' ){
					text += " height='"+height+"'";
				}
				if( player != playerDefault ){
					text += " player='"+player+"'";
				}
				if( tokenFolder != '' ){
					text += " tokenfolder='"+tokenFolder+"'";
				}
				if( jQuery("#dialog-slide-header").hasClass('selected') ){
					//Celles qu'on ajoute à chaque fois
					var stretch = jQuery("#dialog-stretch").attr('checked') ? 1 : 0;
					var autostart = jQuery("#dialog-autostart").attr('checked') ? 1 : 0;
					var loop = jQuery("#dialog-loop").attr('checked') ? 1 : 0;
					text += " stretch='"+ parseInt(stretch)+"'";
					text += " autoplay='"+ parseInt(autostart)+"'";
					text += " loop='"+ parseInt(loop)+"'";
				}
				text += ']' + url + "[/vod]";

				if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
					ed.focus();
					if (tinymce.isIE){
						ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
					}
					ed.execCommand('mceInsertContent', false, text);
				}else{
					edInsertContent(edCanvas, text);
				}	
				Vod_dialogClose();			
			}
		}
	}
};

//Fonction execute a l'initialisation du tinyMCE
(function() {
	try{
		tinymce.create('tinymce.plugins.vodplugin', {
			init : function(ed, url){
				jQuery('#dialog-vod-form').dialog({
					
		            title: 'Ajout d\'une video de la VOD',
		            closeOnEscape : true,
		            resizable: false,
		            autoOpen: false,
		            width: 750,
		            modal: true,
		            closeText: "",
		            buttons: {
		                "Ajouter": function() {
		                    var bValid = true;
		                    if ( bValid ) {
		                        Vod_dialogValid();
		                    }
		                },
		                "Annuler": function() {
		                    Vod_dialogClose();
		                }
		            },
		        });

				jQuery('#dialog-tabs').tabs({
					show: function(event, ui) {
						//On reinit le dossier d'upload lors d'un changement de tab
						if( jQuery('#dialog-tabs').tabs('option', 'selected') == 2 ){
							jQuery('#uploadSelectFolder').val(-1);
							Vod_importVideo();
						}
						//On switch le menu d'implementation et le bouton Ajouter
						if( jQuery('#dialog-tabs').tabs('option', 'selected') == 0 || jQuery('#dialog-tabs').tabs('option', 'selected') == 2 || jQuery('#dialog-tabs').tabs('option', 'selected') == 3){
							jQuery('.ui-dialog-buttonpane button').eq(0).button('disable');
							jQuery('#dialog-config').slideUp();
							jQuery("#dialog-search-input-video").focus();
						}else{
							jQuery('.ui-dialog-buttonpane button').eq(0).button('enable');
							jQuery('#dialog-config').slideDown();
							jQuery("#dialog-url-input").focus();
						}
						jQuery("#dialog-vod-form").dialog( "option", {'position' : 'center'} );
					}
				});

				ed.addButton('vodplugin', {
					title : 'Inserer VOD',
					image: url + "/../img/videofile.png",
					onclick : function() {
						Vod_dialogOpen();
					}
				});
			}
		});
		tinymce.PluginManager.add('vodplugin', tinymce.plugins.vodplugin);
	} catch(e) {
	}
})();

<?php
	/**
	 * Fichier de callback utilisé comme interface du daemon VOD.
	 * Cela permet d'avoir immediatement accès aux vidéos qui viennent d'etre envoyés sur l'espace VOD.
	 * En cas de problemes ou de questions, veuillez contacter support-vod-wordpress@infomaniak.ch
	 *
	 * @author Infomaniak Media team
	 * @link http://infomaniak.com
	 * @version 1.5.7
	 * @copyright infomaniak.com
	 *
	 */

	$content = file_get_contents("php://input");
	parse_str($content, $output);
	$aOptions = get_option('vod_infomaniak');

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
		$aOptions = get_option('vod_infomaniak');

		if ($aOptions['vod_api_callbackKey'] == $_REQUEST['key']) {

			if (get_option( 'vod_api_version', false ) == 2) {
			
				$db = new EasyVod_db();

				$iVideo = intval(sanitize_key($output['iFileCode']));
				$iFolder = intval(sanitize_key($output['iFolderCode']));
				$sFileName = sanitize_file_name($output['sFileName']);
				$sServerCode = sanitize_text_field($output['sFileServerCode']);
				//$sHash_key = sanitize_text_field($output['hash_key']);
				$sFolderUuid = sanitize_text_field($output['sFolderUuid']);
				$sExtension = sanitize_text_field($output['streams'][1]['sExtension']);
				
				/*if ($output['action'] == 'process_start'){
					global $wpdb;
	
					$db = new EasyVod_db();
					$wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "vod_upload" . " SET `sState`='START',`iVideo` = %s WHERE `sToken`=%s", $sServerCode, $sHash_key));
				}*/

				if ($output['action'] == 'process_ok'){
					global $wpdb;
					$db = new EasyVod_db();
			
//					$wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "vod_upload" . " SET `sState`='OK' WHERE `sToken`=%s AND `iVideo`=%s", $sHash_key,$sServerCode));

					$oFolder = $this->db->getFolder($iFolder);
					$sPath = "/".$oFolder->sPath;

					$iDuration = intval(sanitize_key($output['iDuration']));
					$dUpload = date("Y-m-d H:i:s", strtotime(sanitize_text_field($output['dDateUpload'])));

					$oldVideo = $db->get_videos_byCodes($sServerCode, $iFolder);

					if (!empty($oldVideo)) {
						foreach ($oldVideo as $video) {
							$db->delete_video($video->iVideo);
						}
					}

					$db->insert_video($iVideo, $iFolder, $sFileName, $sServerCode, $sPath, $sExtension, $iDuration, $dUpload, $sFolderUuid, sanitize_text_field($output['streams'][1]['sFileUrl']), sanitize_text_field($output['streams'][1]['sPreviewUrl']),'');
				}
			}else{

				$db = new EasyVod_db();
				$iVideo = intval(sanitize_key($_POST['iFileCode']));
				$iFolder = intval(sanitize_key($_POST['iFolderCode']));
				$sFileName = sanitize_file_name($_POST['sFileName']);
				$sServerCode = sanitize_text_field($_POST['sFileServerCode']);

				if (empty($iVideo) || empty($iFolder)) {
					echo(__("Probleme avec les parametres"));
				}
				$oFolder = $db->getFolder($iFolder);
				if (empty($oFolder) || empty($oFolder->sName)) {
					echo(__("Dossier inconnu"));
				}

				$path_tmp = explode('/redirect/' . $aOptions['vod_api_id'] . "/", sanitize_text_field($_POST['files'][0]['sFileUrl']));
				$sPath = "/" . dirname($path_tmp[1]) . "/";
				$sExtension = strtoupper(sanitize_text_field($_POST['files'][0]['sExtension']));
				$iDuration = intval(sanitize_key($_POST['iDuration']));
				$dUpload = date("Y-m-d H:i:s", strtotime(sanitize_text_field($_POST['dDateUpload'])));

				$oldVideo = $db->get_videos_byCodes($sServerCode, $iFolder);
				if (!empty($oldVideo)) {
					foreach ($oldVideo as $video) {
						$db->delete_video($video->iVideo);
					}
				}

				$db->insert_video($iVideo, $iFolder, $sFileName, $sServerCode, $sPath, $sExtension, $iDuration, $dUpload);

				if (!empty($_POST['sInfo'])) {
					$sParamInfo = sanitize_text_field($_POST['sInfo']);
					if (strpos($sParamInfo, "wp_upload_post_") !== false) {
						$sToken = str_replace("wp_upload_post_", "", $sParamInfo);
						$db->update_upload($sToken, $iVideo);
					}
				}
			}
		}else{
			die ("bad key");
		}
	echo esc_html(file_get_contents("php://input"));
	die();
?>
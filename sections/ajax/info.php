<?
//no authorization because this page needs to be accessed to get the authkey

//authorize(true);

//calculate ratio --Gwindow
//returns 0 for DNE and -1 for infiinity, because we dont want strings being returned for a numeric value in our java
$Ratio = 0;
if($LoggedUser['BytesUploaded'] == 0 && $LoggedUser['BytesDownloaded'] == 0) {
	$Ratio = 0;
} elseif($LoggedUser['BytesDownloaded'] == 0) {
	$Ratio = -1;
} else {
	$Ratio = number_format(max($LoggedUser['BytesUploaded']/$LoggedUser['BytesDownloaded']-0.005,0), 2); //Subtract .005 to floor to 2 decimals
}

$MyNews = $LoggedUser['LastReadNews'];
$CurrentNews = $Cache->get_value('news_latest_id');
if ($CurrentNews === false) {
        $DB->query("SELECT ID FROM news ORDER BY Time DESC LIMIT 1");
        if ($DB->record_count() == 1) {
                list($CurrentNews) = $DB->next_record();
        } else {
                $CurrentNews = -1;
        }
        $Cache->cache_value('news_latest_id', $CurrentNews, 0);
}


$NewMessages = $Cache->get_value('inbox_new_'.$LoggedUser['ID']);
if ($NewMessages === false) {
        $DB->query("SELECT COUNT(UnRead) FROM pm_conversations_users WHERE UserID='".$LoggedUser['ID']."' AND UnRead = '1' AND InInbox = '1'");
        list($NewMessages) = $DB->next_record();
        $Cache->cache_value('inbox_new_'.$LoggedUser['ID'], $NewMessages, 0);
}

if (check_perms('site_torrents_notify')) {
        $NewNotifications = $Cache->get_value('notifications_new_'.$LoggedUser['ID']);
        if ($NewNotifications === false) {
                $DB->query("SELECT COUNT(UserID) FROM users_notify_torrents WHERE UserID='$LoggedUser[ID]' AND UnRead='1'");
                list($NewNotifications) = $DB->next_record();
                /* if($NewNotifications && !check_perms('site_torrents_notify')) {
                        $DB->query("DELETE FROM users_notify_torrents WHERE UserID='$LoggedUser[ID]'");
                        $DB->query("DELETE FROM users_notify_filters WHERE UserID='$LoggedUser[ID]'");
                } */
                $Cache->cache_value('notifications_new_'.$LoggedUser['ID'], $NewNotifications, 0);
        }
}

print json_encode(
	array(
		'status' => 'success',
		'response' => array(
			'username' => $LoggedUser['Username'],
			'id' => (int) $LoggedUser['ID'],
			'authkey'=> $LoggedUser['AuthKey'],
			'passkey'=> $LoggedUser['torrent_pass'],
			'notifications' => array(
				'messages'=> (int) $NewMessages,
				'notifications' => (int) $NewNotifications
			),
			'userstats' => array(
				'uploaded' => (int) $LoggedUser['BytesUploaded'],
				'downloaded' => (int) $LoggedUser['BytesDownloaded'],
				'ratio' => (float) $Ratio,
				'requiredratio' => (float) $LoggedUser['RequiredRatio'],
				'class' => $ClassLevels[$LoggedUser['Class']]['Name']
			),
		)
	)
);

?>

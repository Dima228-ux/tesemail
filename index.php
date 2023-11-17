<?php
require_once 'db\DbConnector.php';

// php -r "require 'index.php'; getNewEmail();" - вызов для крона

/**
 * @throws Exception
 */
function getNewEmail()
{
    set_time_limit(4000);

    $hostname = '{imap.mail.ru:993/imap/ssl}INBOX';
    $username = 'roma.artovskiy@mail.ru';
    $password = 'fZciJXpt1ww2cTzsngpD';

    $data = '';
    /* try to connect */
    $inbox = imap_open($hostname, $username, $password) or die('Cannot connect to Mail: ' . imap_last_error());

    // search and get unseen emails, function will return email ids
    $emails = imap_search($inbox, 'NEW');

    if ($emails == false) {
        echo 'No new emails';
        exit();
    }

    foreach ($emails as $mail) {

        //imap_setflag_full($inbox, $mail, '\\seen');

        $headerInfo = imap_headerinfo($inbox, $mail);
        $answer = getDomainThems($headerInfo->subject);

        if ($answer === false) {
            echo 'Error parse';
            continue;
        }
        $date = new DateTime($headerInfo->date);
        $date->setTimezone(new DateTimeZone("Europe/Moscow"));

        $data .= "('{$answer[1]}', '{$answer[0]}', '{$date->format('Y-m-d')}', '{$date->format('H:i:s')}'),";


    }

    imap_expunge($inbox);
    imap_close($inbox);

    echo insertNewEmail($data);
    exit();
}

/**
 * @param $subject
 * @return array|false
 */
function getDomainThems($subject)
{

    if (strlen(trim($subject)) < 1) {
        return false;
    }

    $pos = mb_strpos($subject, ' - ');
    if ($pos < 3) {
      return false;
    }
    $thems = substr(substr($subject, $pos, strlen($subject)), 2);
    $domen = substr($subject, 0, $pos);

    return [$thems, $domen];
}

/**
 * @param $data
 * @return bool|mysqli_result
 */
function insertNewEmail($data)
{
    if (empty($data)) {
        return false;
    }

    $data = substr($data, 0, -1);
    $sql = "INSERT INTO `email` (`domen`, `subject`, `date_send`, `time_send` ) VALUES {$data} ";

    $db = DbConnector::getConnection();
    $result = $db->query($sql);

    return $result;
}
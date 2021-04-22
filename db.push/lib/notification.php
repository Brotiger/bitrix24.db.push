<?php

namespace db\push;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;

class Notification{
    
    private static $db = "db_push";

    public function getAll(){
        $thisUserChats = self::getChatArrID(); //Получаем список чатов в которых есть текущий пользователь
        $lastNotify = self::getNotify($thisUserChats); //Получаем список последних сообщений в различных чатах
        return $lastNotify;
    }

    private function getChatArrID(){
        global $USER, $DB;

        $sqlQuery = "SELECT CHAT_ID FROM b_im_relation WHERE USER_ID = ".$USER->GetID();
        $queryResult = $DB->Query($sqlQuery);

        $result = [];

        while ($sqlResult = $queryResult->GetNext()){
            $result[] = $sqlResult['CHAT_ID'];
        }

        return $result;
    }

    private function getNotify($thisUserChats){
        global $USER, $DB;

        $result = [];
        
        foreach($thisUserChats as $chatId){
            $sqlQuery = "SELECT ID, AUTHOR_ID, MESSAGE FROM b_im_message WHERE CHAT_ID = ".$chatId." AND NOTIFY_READ = 'N' ORDER BY DATE_CREATE DESC LIMIT 1";
            $queryResult = $DB->Query($sqlQuery);

            if($message = $queryResult->GetNext()){
                if(self::alreadySent($message['ID'])) continue;
                $author = self::getUserById($message['AUTHOR_ID']);

                if($author['NAME'] && $author['LAST_NAME']){
                    $authorName = $author['NAME']." ".$author['LAST_NAME'];
                }else if($author['LOGIN']){
                    $authorName = $author['LOGIN'];
                }else{
                    $authorName = 'Bitrix 24';
                }

                $icon = $author['FILE_NAME'] != null ? '/upload/resize_cache/'.$author['SUBDIR'].'/100_100_2/'.$author['FILE_NAME'] : '/upload/db_push/bitrix24.png';

                $result[] = [
                    'message' => self::filterMessage($message['MESSAGE']),
                    'authorName' => $authorName,
                    'icon' => $icon
                ];
            }
            self::setSent($message['ID']);
        }
        return $result;
    }

    private function setSent($id){
        global $USER, $DB;
        
        $fields = array(
            "user_id" => $USER->GetID(),
            "notify_id" => $id
        );

        $DB->Insert(
            self::$db,
            $fields
        );
    }

    private function alreadySent($id){
        global $USER, $DB;
        $sqlQuery = "SELECT COUNT(*) as count FROM db_push WHERE user_id = ".$USER->GetID()." AND notify_id = ".$id;
        $queryResult = $DB->Query($sqlQuery)->GetNext();
        
        if($queryResult['count'] == 0){
            return false;
        }

        return true;
    }

    private function getUserById($id){
        global $DB;
        $sqlQuery = "SELECT NAME, LAST_NAME, LOGIN, SUBDIR, FILE_NAME FROM b_user LEFT JOIN b_file ON PERSONAL_PHOTO = b_file.ID WHERE b_user.ID = ".$id;
        $queryResult = $DB->Query($sqlQuery);
        $user = $queryResult->GetNext();
        $user['PHOTO'] = $user['PERSONAL_PHOTO'] != null ? self::getUserIconById($user['PERSONAL_PHOTO']): '/upload/db_push/bitrix24.png';
        return $user;
    }

    private function filterMessage($message){

        $patterns = [
            '(.*)\[USER=\d*\](.*)\[/USER\](.*)' => '$1$2$3',
            '(.*)&quot;\[URL=http.*\](.*)\[/URL\]&quot;(.*)' => '$1 - $2$3',
        ];

        foreach($patterns as $pattern => $replace){
            if(preg_match("~$pattern~", $message)){
                $result = preg_replace("~$pattern~", $replace, $message);
                return $result;
            }
        }
        return $message;
    }
}
?>
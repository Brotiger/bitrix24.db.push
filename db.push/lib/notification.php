<?php

namespace db\push;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;

class Notification{
    
    private static $db = "db_push";

    public function getAll(){
        $thisUserChats = self::getChatArrID(); //Получаем список чатов в которых есть текущий пользователь
        $lastNotify = self::getLastMessageInChats($thisUserChats); //Получаем список последних сообщений в различных чатах
        $lastNotify = array_merge($lastNotify, self::getLastTasks($thisUserChats));
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

    private function getLastMessageInChats($thisUserChats){
        global $USER, $DB;

        $result = [];
        
        foreach($thisUserChats as $chatId){
            $sqlQuery = "SELECT ID, AUTHOR_ID, MESSAGE FROM b_im_message WHERE CHAT_ID = ".$chatId." AND AUTHOR_ID != ".$USER->GetID()." AND NOTIFY_READ = 'N' ORDER BY DATE_CREATE DESC LIMIT 1";
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

                $result[] = [
                    'message' => self::filterMessage($message['MESSAGE']),
                    'authorName' => $authorName,
                    'icon' => $author['PHOTO'],
                ];
            }
            self::setSent($message['ID']);
        }
        return $result;
    }

    private function getLastTasks($thisUserChats){
        global $USER, $DB;

        $result = [];
        
        foreach($thisUserChats as $chatId){
            $sqlQuery = "SELECT ID, AUTHOR_ID, MESSAGE FROM b_im_message WHERE CHAT_ID = ".$chatId." AND NOTIFY_MODULE = 'tasks' ORDER BY DATE_CREATE DESC LIMIT 1";
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

                $result[] = [
                    'message' => self::filterMessage($message['MESSAGE']),
                    'authorName' => $authorName,
                    'icon' => $author['PHOTO'],
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
        $sqlQuery = "SELECT * FROM b_user WHERE ID = ".$id;
        $queryResult = $DB->Query($sqlQuery);
        $user = $queryResult->GetNext();
        $user['PHOTO'] = $user['PERSONAL_PHOTO'] != null ? self::getUserIconById($user['PERSONAL_PHOTO']): '/upload/db_push/bitrix24.png';
        return $user;
    }

    private function getUserIconById($id){
        global $DB;
        $sqlQuery = "SELECT * FROM b_file WHERE ID = ".$id;
        $queryResult = $DB->Query($sqlQuery);
        $photo = $queryResult->GetNext();
        $photo_path = '/upload/resize_cache/'.$photo['SUBDIR'].'/100_100_2/'.$photo['ORIGINAL_NAME'];
        return $photo_path;
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
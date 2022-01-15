<?php

session_start();
require('dbconnect.php');

if (isset($_SESSION['id'])) {
    $id = $_REQUEST['id'];

    //投稿を検査する
    $message = $db->prepare('select * from posts where id = ?');
    $message->execute([
        $id
    ]);
    $message = $message->fetch();

    if ($message['member_id'] == $_SESSION['id']) {
        //削除する
        $del = $db->prepare('delete from posts where id = ?');
        $del->execute([
            $id
        ]);
    }

    header('Location: index.php');
    exit();
}
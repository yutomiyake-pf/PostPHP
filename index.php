<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css" />
</head>

<body>

<?php

session_start();
require('dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {//ログインしている
    $_SESSION['time'] = time();

    $members = $db->prepare('select * from members where id = ?');
    $members->execute([
        $_SESSION['id']
    ]);
    $member = $members->fetch();

} else {
    header('Location: login.php');exit();//ログインしていない
}

//投稿処理
if (!empty($_POST)) {
    if ($_POST['message'] != "") {
        $message = $db->prepare('insert into posts set member_id = ?, message = ?, reply_post_id = ?, created = NOW()');
        $message->execute([
            $member['id'],
            $_POST['message'],
            $_POST['reply_post_id']
        ]);

        header('Location: index.php'); exit();
    }
}

//投稿取得
//$posts = $db->query('select m.name, m.picture, p.* from members m, posts p where m.id = p.member_id order by p.created desc');
$page = $_REQUEST['page'];
if ($page === "") {
    $page = 1;
}

$page = max($page, 1);

//最終ページを取得
$counts = $db->query('select count(*) as cnt from posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;

$posts = $db->prepare('select m.name, m.picture, p.* from members m, posts p where m.id = p.member_id order by p.created desc limit ?, 5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();



//返信の場合
if (isset($_REQUEST['res'])) {
    $response = $db->prepare('select m.name, m.picture, p.* from members m, posts p where m.id = p.member_id and p.id = ? order by p.created desc');
    $response->execute([$_REQUEST['res']]);

    $table = $response->fetch();
    $message = '@' . $table['name'] . ' ' . $table['message'];
}

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES);
}
?>
<div id="wrap">
  <div id="head">
    <h1>ひとこと掲示板</h1>
  </div>
  <div id="content">
    <div style="text-align: right;"><a href="logout.php">ログアウト</a></div>
    <form action="" method="post">
        <dl>
            <dt><?php echo h($member['name']); ?>さんメッセージをどうぞ</dt>
            <dd>
                <textarea name="message" id="" cols="50" rows="5"><?php echo h($message); ?></textarea>
                <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>">
            </dd>
        </dl>
        <div>
            <input type="submit" value="投稿する">
        </div>
    </form>

    <?php foreach ($posts as $post): ?>
    <div class="msg">
        <img src="member_picture/<?php echo h($post['picture']); ?>" alt="写真" width="48" height="48">
        [<a href="index.php?res=<?php echo h($post['id']); ?>">RE</a>]
        <p><?php echo h($post['message']); ?><span class="name">(<?php echo h($post['name']); ?>)</span></p>
        <p class="day">
            <a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
            <?php if ($post['reply_post_id']): ?>
                <a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">返信元のメッセージ</a>
            <?php endif; ?>

            <?php if ($_SESSION['id'] == $post['member_id']): ?>
                <a href="delete.php?id=<?php echo h($post['id']); ?>" style="color: #F33;">削除</a>
            <?php endif; ?>
        </p>
    </div>
    <?php endforeach; ?>

    <ul class="paging">
        <?php if($page > 1): ?>
            <li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
        <?php else: ?>
            <li>前のページへ</li>
        <?php endif; ?>

        <?php if ($page < $maxPage): ?>
            <li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
        <?php else: ?>
            <li>次のページへ</li>
        <?php endif; ?>
    </ul>
  </div>

</div>
</body>
</html>

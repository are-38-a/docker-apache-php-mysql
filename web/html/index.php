<?php
/**
 * 入力が空白でないか確認する
 *
 * @param string $str
 * @return bool
 */
function is_not_space(?string $str): bool {
    $str = preg_replace("/( |　)/", "", $str);
    if ($str == "") {
        return FALSE;
    } else {
        return TRUE;
    }
}

/**
 * DBに接続する
 *
 * @return PDO
 */
function connectDB() {
    try {
        $dsn = 'mysql:host=mysql;dbname=test_db;charset=utf8';
        $pdo = new PDO($dsn, 'test_user', 'test_password');
        return $pdo;
    } catch (PDOException $e) {
        echo $e->getMessage();
        return null;
    }
}

/**
 * テーブルがなければ作成する
 *
 * @param PDO $db
 * @param string $TABLE_NAME
 * @return bool
 */
function createTableForBBS(PDO $pdo, $TABLE_NAME): bool {
    try {
        //ダブルクオーテーションで囲うと変数が自動的に展開される
        $sql = "CREATE TABLE IF NOT EXISTS $TABLE_NAME
            (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(32),
                comment TEXT,
                date DATETIME,
                password VARCHAR(32)
            );";
        $stmt = $pdo->query($sql);
        return true;
    } catch (PDOExeption $e) {
        echo $e->getMessage();
        return false;
    }
}

/**
 * 書き込みを読み込んで表示
 * 
 * @param PDO $db
 * @param string $TABLE_NAME
 * @return void
 */
function fetchPosts(PDO $pdo, $TABLE_NAME){
    $sql = "SELECT * FROM $TABLE_NAME";
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    foreach ($results as $row) {
        echo $row['id'] . ' ';
        echo "名前:" . $row['name'] . " ";
        echo "日時:" . $row['date'] . '<br>';
        echo $row['comment'] . '<br>';
        echo "<hr>";
    }
}

/**
 * 書き込みを投稿してエラーメッセージを返却する
 *
 * @param PDO $db
 * @param string $TABLE_NAME
 * @param array $POST
 * @return string
 */
function post(PDO $pdo, string $TABLE_NAME, $name, $password, $comment): string {
    //submitされたがコメントが空のときの処理
    if (!is_not_space($comment)) {
        return "コメントを入力してください";
    }

    //変数に格納
    if (!is_not_space(($name))) {
        $name = "名無しさん";
    }

    if (!is_not_space($password)) {
        $password = "0000";
    }

    $date = date("Y-m-d H:i:s");

    //新規投稿
    $sql = $pdo->prepare
        ("INSERT INTO $TABLE_NAME (name, comment, date, password) 
        VALUES (:name, :comment, :date, :password)");
    $sql->bindParam(':name', $name, PDO::PARAM_STR);
    $sql->bindParam(':comment', $comment, PDO::PARAM_STR);
    $sql->bindParam(':date', $date, PDO::PARAM_STR);
    $sql->bindParam(':password', $password, PDO::PARAM_STR);
    $sql->execute();
    return "書き込み完了";
}

function updatePost(PDO $pdo, string $TABLE_NAME, $name, $password, $comment, $id): string {
    //書き換え用の処理
    if (!is_not_space($comment)) {
        return "コメントを入力してください";
    }

    //変数に格納
    if (!is_not_space(($name))) {
        $name = "名無しさん";
    }

    if (!is_not_space($password)) {
        $password = "0000";
    }

    $sql = $pdo->prepare
        ("UPDATE $TABLE_NAME 
        SET name=:name,comment=:comment,password=:password WHERE id=:id");
    $sql->bindParam(':name', $name, PDO::PARAM_STR);
    $sql->bindParam(':comment', $comment, PDO::PARAM_STR);
    $sql->bindParam(':password', $password, PDO::PARAM_STR);
    $sql->bindParam(':id', $id, PDO::PARAM_STR);
    $sql->execute();
    return "編集完了";
}

/**
 * 編集状態にする
 *
 * @param PDO $pdo
 * @param string $TABLE_NAME
 * @param array $POST
 * @return void
 */
function preEdit(PDO $pdo, string $TABLE_NAME, $id, $password) {    
    //指定idの書き込みを取得
    $sql = "SELECT id, name, comment, password FROM $TABLE_NAME WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindparam(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetch();

    if (!$results) {
        return ["mes" => "その番号の書き込みは存在しません。"];
    }
     
    if ($results["password"] != $password) {
        return ["mes" => "パスワードが不正です。"];
    }

    return["mes" => "編集可能です",
            "comment_edit" => $results["comment"],
            "name_edit" => $results["name"],
            "id_edit" => $id];
}

/**
 * 投稿を削除する
 *
 * @param PDO $pdo
 * @param string $TABLE_NAME
 * @param $id
 * @param $password
 * @return string
 */
function deletePost(PDO $pdo, string $TABLE_NAME, $id, $password): string{
    //パスワードが一致するか確かめる
    $sql = "SELECT id, password FROM $TABLE_NAME WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindparam(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetch();

    if (!$results) {
        return "その番号の書き込みは存在しません";
    }
    if ($results["password"] != $password) {
        return "パスワードが不正です";
    }
    
    //パスワードがあっていれば削除
    $sql = "delete from $TABLE_NAME where id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return "削除しました";
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>TestBBS</title>
</head>

<body>
    <h1>掲示板</h1>
    <?php
        $errMsg = "";
        $mes = "";
        $TABLE_NAME = "test_bbs";
        $isEdit = FALSE;
        $db = connectDB();
        createTableForBBS($db, $TABLE_NAME);

        if (isset($_POST["submit"]) && !isset($_POST["id"])) {
            $errMsg = post($db, $TABLE_NAME, $_POST["name"], $_POST["password"], $_POST["comment"]);
        }
        if (isset($_POST["submit"]) && isset($_POST["id"])) {
            $errMsg = updatePost($db, $TABLE_NAME, $_POST["name"], $_POST["password"], $_POST["comment"], $_POST["id"]);
        }
        if (isset($_POST["delete"])) {
            $errMsg = deletePost($db, $TABLE_NAME, $_POST["id"], $_POST["password"]);
        }
        if (isset($_POST["edit"])) {
            $res = preEdit($db, $TABLE_NAME, $_POST["id"], $_POST["password"]);
            $mes = $res["mes"];
    
            if($mes == "編集可能です") {
                $isEdit = TRUE;
                $comment_edit = $res["comment_edit"];
                $name_edit = $res["name_edit"];
                $id_edit = $res["id_edit"];
            }
    
            $password_edit = $_POST["password"];
        }
        echo "<span style='color:red'><p>$errMsg</p></span>";
        fetchPosts($db, $TABLE_NAME);
    ?>
 
<?php if($isEdit): ?>
    <div>
        <form action="" method="post">
            <p>パスワードを指定しない場合「0000」に設定されます</p>
            <p>名前:<input type="text" name="name" value="<?= $name_edit ?>">
            パスワード:<input type="text" name="password" value="<?= $password_edit ?>"></p>
            <p>コメント:</p>
            <textarea name="comment" rows="8" cols="40"><?= $comment_edit ?></textarea>
            <input type="hidden" name="id" value="<?= $id_edit ?>">
            <input type="submit" name="submit">
        </form>
    </div>
<?php else: ?>
    <div>
        <form action="" method="post">
            <p>パスワードを指定しない場合「0000」に設定されます</p>
            <p>名前:<input type="text" name="name">
            パスワード:<input type="text" name="password"></p>
            <p>コメント:</p>
            <textarea name="comment" rows="8" cols="40"></textarea>
            <input type="submit" name="submit">
        </form>
    </div>
<?php endif; ?>    

    <div>
        <form action="" method="post">
            <p>番号:<input type="number" name="id">
            パスワード:<input type="text" name="password">
            <input type="submit" name="edit" value="編集">
            <input type="submit" name="delete" value="削除"></p>
        </form>
        <span style="color:red"><?php echo "<p>$mes</p>" ?></span>
    </div>
</body>
</html>
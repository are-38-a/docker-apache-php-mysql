
<?php 
    //DB接続
    try {
        $dsn = 'mysql:host=mysql;dbname=test_db;charset=utf8';
        $db = new PDO($dsn, 'test_user', 'test_password');
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
    }
    //テーブルがなければ作成する
    try {
        $sql = "CREATE TABLE IF NOT EXISTS test_bbs
        (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(32),
            comment TEXT,
            date DATETIME,
            password VARCHAR(32)
        );";
        $stmt = $db->query($sql);
    } catch (PDOExeption $e) {
        echo $e->getMessage();
        exit;
    }

    
    //$sql = 'SELECT version();';
    //$contact = $db->prepare($sql);
    //$contact->execute();
    //$result = $contact->fetchAll(PDO::FETCH_ASSOC);
    //var_dump($result);
?>


<?php
session_start();
require_once('../db.inc.php');

//若沒有輸入欄位，exit()
if($_POST['name']==='' || $_POST['email']==='' || $_POST['password']===''){
    echo "please enter info";
    exit();
}


try{
    $pdo->beginTransaction();

    $hash = md5( rand(0,1000) );
    $email = $_POST['email'];
    $password = $_POST['password'];


    //先查看Vendors是否有已經註冊過的廠商信箱
    $checksql = "SELECT `vEmail`, `vPassword`, `vId`
    FROM `vendors`
    WHERE `vEmail` = '$email'";
    $check = $pdo->query($checksql);
    if($check->rowCount()>0){
        echo "該信箱已經註冊過";
        exit();
    }

    //配對看看是否有同樣email + pwd的工作人員帳號存在

    $checksql = "SELECT `vaId` FROM `vendorAdmins` WHERE `vaPassword` = ? AND `vaEmail` = ?";
    $stmtc = $pdo->prepare($checksql);
    $checkparam = [
        sha1($_POST['password']),
        $email
    ];
    $stmtc->execute($checkparam);
    if($stmtc->rowCount() > 0){
        echo "有相同的帳號存在，請重新輸入";
        exit();
    }


    //------輸入廠商資訊------
    $sqlVendor = "INSERT INTO `vendors`(`vName`,`vActive`, `vVerify`, `vEmail`, `vPassword`, `vHash`)
                    VALUES(?,'active', ?, ?, ?, ?)";
    $stmtVendor = $pdo->prepare($sqlVendor);
    $arrParamVendor = [ 
        $_POST['name'], 
        date('Y-m-d H:i:s'),
        $email,
        $password,
        $hash
    ];
    $stmtVendor->execute($arrParamVendor);

    if($stmtVendor->rowCount()>0){

        //寄mail
        sendMail($email, $_POST['name'], $hash);

        //加入 session
        $pdo->commit();

        $_SESSION['userId'] = $currentAdmin;
        $_SESSION['email'] = $email;
        $_SESSION['vendor'] = $currentVendor;            

        echo "success";
    }
}catch(Exception $err){
    $pdo->rollback();
    echo "失敗： ".$err->getMessage();
}


//send mail

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendMail($email, $vName, $hash){

    // Load Composer's autoloader
    require '../vendor/autoload.php';

    // Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                    // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'radu000rider@gmail.com';                     // SMTP username
        $mail->Password   = 'lvknxoyjwwlyyjnb';                               // SMTP password
        $mail->SMTPSecure = 'tls';         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = 587;                                    // TCP port to connect to
        $mail->CharSet="UTF-8"; //for Chinese
        $mail->SMTPDebug = 0; //stops sending debug info & allows header refresh
        
        //Recipients
        $mail->setFrom($email, $vName, 0);
        $mail->addAddress($email, $_POST['name'], 0);     // Add a recipient

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'onepeace通知訊息';
        $mail->Body    = "
            $vName 您好， <br>
            您於 onepeace 申請了 $vName 廠商帳號 <br>
            請點擊連結設以驗證您的帳號： <a href='http://localhost:8080/Project/vendors/register_verify.php?hash=$hash&email={$email}'>http://localhost:8080/Project/vendors/register_verify.php</a> <br>
            $vName <br>
            此信為自動發出，請勿回覆";
        $mail->AltBody = "$vName 您好，您於 onepeace 申請了 $vName 廠商帳號，請點擊連結以驗證您的帳號：http://localhost:8080/Project/vendors/register_verify.php?hash=$hash&email={$email}";

        $mail->send();
        // echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        exit();
    }
}

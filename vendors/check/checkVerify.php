<?php


if($arrGetInfo['vVerify']!=='verified'){
    $vVerifyTime = new DateTime($arrGetInfo['vVerify']);
    $vVerifyLeft = $vVerifyTime->diff(new DateTime());
    
    if( $vVerifyLeft->days > 10){
        echo "請驗證公司信箱以繼續操作";
        exit();
    }
}

if($arrGetInfo['vaVerify']!=='verified'){
    $vaVerifyTime = new DateTime($arrGetInfo['vaVerify']);
    $vaVerifyLeft = $vaVerifyTime->diff(new DateTime());
    
    if($vaVerifyLeft->days > 10){
        echo "請驗證您的信箱以繼續操作";
        exit();
    }
}
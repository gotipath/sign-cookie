<?php
$EXPIRE_IN = 120; // gen tokens expiring in XX seconds
$PATH_PREFIX = "/"; // allow everything under this path
$EDGE_DOMAIN = "nscdn.nusratech.com"; // allow requests to this domain only
$SECRET = "my-secret"; // CDN secret key

function sign($resource, $secret_key)
{
    $hmac_str = hash_hmac('sha256', $resource, $secret_key);
    return "0" . substr($hmac_str, 0, 20);
}

function createSignedCookie($url_prefix, $expires_at, $secret_key)
{
    $etime = date('YmdHis', $expires_at); //etime End time (not valid after this time)
    $resource = "URL={$url_prefix}:Expires={$etime}";
    $policy = base64_encode($resource);
    $signature = sign($policy, $secret_key);
    $signedCookie = array(
        "ss_policy" => $policy,
        "ss_signature" => $signature
    );
    return $signedCookie;
}
$url_prefix = "{$EDGE_DOMAIN}{$PATH_PREFIX}";
$expires_at = time() + $EXPIRE_IN; //$expires_at = 1662164413; // 2022-09-03

$signedCookieCustomPolicy = createSignedCookie($url_prefix, $expires_at, $SECRET);

foreach ($signedCookieCustomPolicy as $name => $value) {
    setcookie($name, $value, $expires_at, $PATH_PREFIX, $EDGE_DOMAIN, false, false);
}

//ss_policy=VVJMPW5zY2RuLm51c3JhdGVjaC5jb20vOkV4cGlyZXM9MjAyMjA4MjMxMzE5NTU%3D; Path=/; Domain=nscdn.nusratech.com; Expires=Tue, 23 Aug 2022 13:20:59 GMT; 
//ss_signature=02a92e1859a59ed90b4ac; Path=/; Domain=nscdn.nusratech.com; Expires=Tue, 23 Aug 2022 13:20:59 GMT;

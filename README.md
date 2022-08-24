# Gotipath Signed Cookies

Gotipath signed cookies allow you to control who can access your content when you don't want to change your current URLs or when you want to provide access to multiple restricted files, for example, all of the files in the subscribers' area of a website. This topic explains the considerations when using signed cookies and describes how to set signed cookies using custom policies.

- How signed cookies work
- When does Gotipath check the expiration time in a signed cookie?
- Setting signed cookies using a custom policy
- Sample code with PHP

### How signed cookies work

Here's an overview of how you configure signed cookies and how Gotipath responds when a user submits a request that contains a signed cookie.

1. Specify secret key to generate the encoded string can be viewed at the bottom of the Access Control tab by clicking the Show button.
2. Prepare your application to send two Set-Cookie headers to the viewer. (Each Set-Cookie header can contain only one name-value pair, and a Gotipath signed cookie requires two name-value pairs.) You must send the Set-Cookie headers to the viewer before the viewer requests your private content. If you set a short expiration time on the cookie, you might also want to send two more Set-Cookie headers in response to subsequent requests, so that the user continues to have access.
3. A user signs in to your website and either pays for content or meets some other requirement for access.
4. Your application returns the Set-Cookie headers in the response, and the viewer stores the name-value pairs.
5. The user requests a file.
    
    The user's browser or other viewer gets the name-value pairs from step 4 and adds them to the request in a Cookie header. This is the signed cookie.
    
6. Gotipath uses the secret key to validate the signature in the signed cookie and to confirm that the cookie hasn't been tampered with. If the signature is invalid, the request is rejected.
    
    If the signature in the cookie is valid, Gotipath looks at the policy statement in the cookie to confirm that the request is still valid. For example, as you specified a start time and end time for the cookie, Gotipath confirms that the user is trying to access your content during the time period that you want to allow access.
    
    If the request meets the requirements in the policy statement, Gotipath serves your content as it does for content that isn't restricted: it determines whether the file is already in the edge cache, and forwards the request to the origin if necessary, and returns the file to the user.
    

### When does Gotipath check the expiration time in a signed cookie?

To determine whether a signed cookie is still valid, Gotipath checks the expiration time in the cookie at the time of the HTTP request. If a client begins to download a large file immediately before the expiration time, the download should complete even if the expiration time passes during the download. If the TCP connection drops and the client tries to restart the download after the expiration time passes, the download will fail.

### Setting signed cookies using a custom policy

To set a signed cookie that uses a custom policy, complete the following steps

Program your application to send two Set-Cookie headers to approved viewers. You need two Set-Cookie headers because each Set-Cookie header can contain only one name-value pair, and a Gotipath signed cookie requires two name-value pairs. The name-value pairs are: ss_policy and ss_signature. The values must be present on the viewer before a user makes the first request for a file that you want to control access to.

The names of cookie attributes are case-sensitive. 

Line breaks are included only to make the attributes more readable.

```jsx
Set-Cookie: 
ss_policy=*base64 encoded version of the policy statement*; 
Path=*directory path*;
Domain=*domain name*; 
Secure; 

Set-Cookie: 
ss_signature=*hashed and signed version of the policy statement*; 
Path=*directory path*;
Domain=*domain name*; 
Secure; 
```

<aside>
ℹ️ **Secure**
Requires that the viewer encrypt cookies before sending a request. We recommend that you send the Set-Cookie header over an HTTPS connection to ensure that the cookie attributes are protected from man-in-the-middle attacks.

**HttpOnly**
Requires that the viewer send the cookie only in HTTP or HTTPS requests.

</aside>

Example Set-Cookie headers for one signed cookie when you're using the domain name that is associated with your distribution in the URLs for your files:

```jsx
Set-Cookie: ss_policy=VVJMPW5zY2RuLm51c3JhdGVjaC5jb20vOkV4cGlyZXM9MjAyMjA4MjMxMzE5NTU%3D; Path=/; Domain=nscdn.nusratech.com; Expires=Tue, 23 Aug 2022 13:20:59 GMT; 
Set-Cookie: ss_signature=02a92e1859a59ed90b4ac; Path=/; Domain=nscdn.nusratech.com; Expires=Tue, 23 Aug 2022 13:20:59 GMT;
```

### Example Creating Signed-Cookie with PHP

This example is based on PHP, it may vary in different programming languages.

```php
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
```
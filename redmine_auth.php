<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$client = new GuzzleHttp\Client([
    'base_uri' => REDMINE_URL,
    'timeout'  => HTTP_TIMEOUT,
]);

$realm = 'Restricted area';

//
// CHECK ACCESS
//

// White list first :
// accept 192.168.1.66 and black list 192.
if (!ipMatch($IP_WHITE_LIST, $_SERVER['REMOTE_ADDR'])) {
    if (ipMatch($IP_BLACK_LIST, $_SERVER['REMOTE_ADDR'])) {
        unauthorizedTemplate();
        exit;
    }

    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        basicAuth($realm);
    } else {
        try {
            $client->request('GET', '/projects.json', ['auth' => [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']]]);
        } catch (Exception $e) {
            sleep (3);
            basicAuth($realm);
        }
    }
}

//
// Deliver file content
//

// Change server values
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['CONTEXT_DOCUMENT_ROOT'] . $_SERVER['REDIRECT_URL'];
$_SERVER['SCRIPT_NAME'] = $_SERVER['REDIRECT_URL'];

if (is_dir($_SERVER['SCRIPT_FILENAME'])) {
    $_SERVER['SCRIPT_FILENAME'] .= 'index.html';
    $_SERVER['SCRIPT_NAME'] = $_SERVER['REDIRECT_URL'] . 'index.html';
}

if (file_exists($_SERVER['SCRIPT_FILENAME'])) {
    echo file_get_contents($_SERVER['SCRIPT_FILENAME']);
} else {
    header('HTTP/1.0 404 Not Found');
?>
<html>
<head>
    <title>404 Not Found</title>
</head>
<body>
    <h1>Not Found</h1>
    <p>The requested URL <?= $_SERVER['SCRIPT_NAME'] ?> was not found on this server.</p>
</body>
</html>
<?php
    exit;
}

//
// FUNCTIONS
//

function basicAuth($realm)
{
    header('WWW-Authenticate: Basic realm="' . $realm . '"');
    unauthorizedTemplate();
    exit;
}

function unauthorizedTemplate()
{
    header('HTTP/1.0 401 Unauthorized');
?>
<html><head>
<title>401 Unauthorized</title>
</head><body>
<h1>Unauthorized</h1>
<p>This server could not verify that you
are authorized to access the document
requested.  Either you supplied the wrong
credentials (e.g., bad password), or your
browser doesn't understand how to supply
the credentials required.</p>
</body></html>
<?php
}

function ipMatch(array $ipList, $ipToCheck) {
    foreach ($ipList as $ip) {
        if (preg_match('/^'.preg_quote($ip).'/',$ipToCheck)) {
            return true;
            break;
        }
    }
    return false;
}

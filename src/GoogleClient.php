<?php

namespace Dywily\Gaw;

use Google\Client;

class GoogleClient
{
    // 实例对象不可以声明类型
    private static $client;

    private function __construct()
    {
    }

    private function __clone(): void
    {
    }

    public static function getInstance($accountConfig): Client
    {
        if (!(static::$client instanceof Client)) {
            static::$client = GoogleClient::initClient($accountConfig);
        }
        return static::$client;
    }

    /**
     * @param $accountConfig
     * @return Client
     * @throws \Google\Exception
     */
    public static function initClient($accountConfig)
    {
        $authPath = $accountConfig['auth_path'];
        $credentialFile = $authPath . '/' . $accountConfig['credential_file'];
        $tokenFile = $authPath . '/' . $accountConfig['token_file'];
        if (!file_exists($credentialFile)) {
            header('HTTP/1.1 403 Forbidden');
            exit(1);
        }
        $client = new Client();
        $client->setApplicationName($accountConfig['project_name']);
        $client->setScopes(
            [
                'https://mail.google.com/',
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/gmail.modify',
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/gmail.labels',
            ]
        );
        $client->setAuthConfig($credentialFile);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        if (file_exists($tokenFile)) {
            $accessToken = json_decode(file_get_contents($tokenFile), true);
            $client->setAccessToken($accessToken);
        } else if ($authCode = $_GET["code"]) {
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            if (array_key_exists('error', $accessToken)) {
                throw new \Exception(join(', ', $accessToken));
            }
            if (!file_exists(dirname($tokenFile))) {
                mkdir(dirname($tokenFile), 0700, true);
            }
            file_put_contents($tokenFile, json_encode($client->getAccessToken()));
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            $res = [];
            $cr = $client->getRefreshToken();
            if ($cr) {
                $res = $client->fetchAccessTokenWithRefreshToken($cr);
            }
            if ((!$cr) || (!empty($res['error']))) {
                file_exists($tokenFile) and unlink($tokenFile);
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                header("Location: " . $authUrl);
                exit(1);
            }
        }
        return $client;
    }
}

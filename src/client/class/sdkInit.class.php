<?php
namespace SDK;
class sdkprojet
{
    public function __construct()
    {

    }

    private const CLIENT_ID = '654dezbe521bec2fds265gerf752de82dd';
    private const CLIENT_SECRET = 'q2svv6er1v6er1252c81b6d6578d2';

    private const FACEBOOK_IDCLIENT = '614828080278778';
    private const FACEBOOK_SECRET_ID = '665fc7da4a80e1a2590ca5c73ec09c5f';
    private const FACEBOOK_TOKEN_URL = "https://graph.facebook.com/v13.0/oauth/access_token";
    private const FACEBOOK_URLAPI = "https://graph.facebook.com/v13.0/me?fields=last_name,first_name,email";
    private const FACEBOOK_REDIRECT = "https://localhost/fb_oauth_success";

    private const DISCORD_IDCLIENT = "998329749602570243";
    private const DISCORD_SECRET_ID = "6jbNG2Ehsp9fgj8gVSwzO6zenjuDjgPw";
    private const DISCORD_TOKEN_URL = "https://discordapp.com/api/v6/oauth2/token";
    private const DISCORD_URLAPI = "https://discord.com/api/users/@me";
    private const DISCORD_REDIRECT= "https://localhost/success_url_redirection";

    private const TWITTER_IDCLIENT = "OgFaJjieH6lLfd9kBqTccheud";
    private const TWITTER_SECRET_ID = "W4T449O5X1jTZgjGAdXcVPXtOeKrwxlxDLSO1fhthphWSuSd2x";
    private const TWITTER_TOKEN_URL = "https://api.twitter.com/oauth2/token";
    private const TWITTER_URLAPI = "https://api.twitter.com/oauth/authorize";
    private const TWITTER_REDIRECT = "https://localhost/twitter_oauth_success";


    public function login(): void
    {
        
        $queryParams = http_build_query([
            "state"=>bin2hex(random_bytes(16)),
            "client_id"=> self::CLIENT_ID,
            "scope"=>"profile",
            "response_type"=>"code",
            "redirect_uri"=>"http://localhost:8081/oauth_success",
        ]);
        echo "
        <form method=\"POST\" action=\"/oauth_success\">
            <input type=\"text\" name=\"username\"/>
            <input type=\"password\" name=\"password\"/>
            <input type=\"submit\" value=\"Login\"/>
        </form>
    ";
        $facebookQp = http_build_query([
            "state"=>bin2hex(random_bytes(16)),
            "client_id"=> self::FACEBOOK_IDCLIENT,
            "scope"=>"public_profile,email",
            "redirect_uri"=> self::FACEBOOK_REDIRECT,
        ]);

        $discordQp = http_build_query([
            "state"=>bin2hex(random_bytes(16)),
            "client_id"=> self::DISCORD_IDCLIENT,
            "scope"=>"identify",
            "response_type" => "code",
            "redirect_uri"=> self::DISCORD_REDIRECT,
        ]);

        $twitterQp = http_build_query([
            "state"=>bin2hex(random_bytes(16)),
            "client_id"=> self::TWITTER_IDCLIENT,
            "scope"=>"user:read:email",
            "response_type" => "code",
            "redirect_uri"=> self::TWITTER_REDIRECT,
        ]);


        echo "<a href=\"http://localhost:8080/auth?$queryParams\">Login with Oauth-Server</a><br>";
        echo "<a href=\"https://www.facebook.com/v13.0/dialog/oauth?$facebookQp\">Login with Facebook</a><br>";
        echo "<a href=\"https://discord.com/api/oauth2/authorize?$discordQp\">Login with Discord</a><br>";
        echo "<a href=\"https://twitter.com/i/oauth2/authorize?{$twitterQp}\">Login with Twitter</a><br>";

    }

    function callback()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            ["username"=> $username, "password" => $password] = $_POST;
            $specifParams = [
                "grant_type" => "password",
                "username" => $username,
                "password" => $password,
            ];
        } else {
            ["code"=> $code, "state" => $state] = $_GET;
            $specifParams = [
                "grant_type" => "authorization_code",
                "code" => $code
            ];
        }
        $queryParams = http_build_query(array_merge(
            $specifParams,
            [
                "redirect_uri" => "http://localhost:8081/oauth_success",
                "client_id" => self::CLIENT_ID,
                "client_secret" => self::CLIENT_SECRET,
            ]
        ));
        $response = file_get_contents("http://server:8080/token?{$queryParams}");
        if (!$response) {
            echo $http_response_header;
            return;
        }
        ["access_token" => $token] = json_decode($response, true);


        $context = stream_context_create([
            "http"=>[
                "header"=>"Authorization: Bearer {$token}"
            ]
        ]);
        $response = file_get_contents("http://server:8080/me", false, $context);
        if (!$response) {
            echo $http_response_header;
            return;
        }
        var_dump(json_decode($response, true));
    }

    public function app_callback($app)
    {
        switch($app) {
            case "fb":
                $token = $this->getFbToken(self::FACEBOOK_TOKEN_URL, self::FACEBOOK_IDCLIENT, self::FACEBOOK_SECRET_ID);
                $apiURL = self::FACEBOOK_URLAPI;
                $headers = [
                    "Authorization: Bearer $token",
                ];
                break;
                case "twitter":
                    $token = $this->getTwitchToken(self::TWITTER_TOKEN_URL, self::TWITTER_IDCLIENT, self::TWITTER_SECRET_ID);
                    $apiURL = self::TWITTER_URLAPI;
                    $headers = [
                        "Authorization: Bearer $token",
                        "Client-ID: " . self::TWITTER_IDCLIENT
                    ];
                    break;
            case "discord":
                $token = $this->getDiscordToken(self::DISCORD_TOKEN_URL, self::DISCORD_IDCLIENT, self::DISCORD_SECRET_ID);
                $apiURL = self::DISCORD_URLAPI;
                $headers = [
                    "Authorization: Bearer $token",
                ];
                break;
            default:
                return;
        }

        $user = $this->getUser($apiURL, $headers);
        var_dump($user);
    }

    public function getUser($apiURL, $headers)
    {
        $context = stream_context_create([
            "http"=>[
                "header"=>$headers,
            ]
        ]);

        $response = file_get_contents($apiURL, false, $context);
        if (!$response) {
            var_dump($http_response_header);
            return;
        }

        return json_decode($response, true);
    }

    public function getFbToken($baseUrl, $clientId, $clientSecret)
    {
        ["code"=> $code, "state" => $state] = $_GET;
        $queryParams = http_build_query([
            "client_id"=> $clientId,
            "client_secret"=> $clientSecret,
            "redirect_uri"=> self::FACEBOOK_REDIRECT,
            "code"=> $code,
            "grant_type"=>"authorization_code",
        ]);

        $url = $baseUrl . "?{$queryParams}";
        $response = file_get_contents($url);

        if (!$response) {
            var_dump($http_response_header);
            return;
        }
        ["access_token" => $token] = json_decode($response, true);

        return $token;
    }

    public function getDiscordToken($baseUrl, $clientId, $clientSecret)
    {
        ["code"=> $code, "state" => $state] = $_GET;

        $postData = http_build_query(
            [
                "client_id"=> $clientId,
                "client_secret"=> $clientSecret,
                "redirect_uri"=> self::DISCORD_REDIRECT,
                "code"=> $code,
                "grant_type"=>"authorization_code",
            ]
        );

        $opts = ['http' =>
            [
                'method'  => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData,
            ]
        ];

        $context  = stream_context_create($opts);
        $response = file_get_contents($baseUrl, false, $context);

        if (!$response) {
            var_dump($http_response_header);
            return;
        }

        ["access_token" => $token] = json_decode($response, true);
        return $token;
    }

    function getTwitchToken($baseUrl, $clientId, $clientSecret)
    {
        ["code"=> $code, "state" => $state] = $_GET;
        $postData = http_build_query(
            [
                "client_id"=> $clientId,
                "client_secret"=> $clientSecret,
                "redirect_uri"=> self::DISCORD_REDIRECT,
                "code"=> $code,
                "grant_type"=>"authorization_code"
        ]);

        $opts = ['http' =>
            [
                'method'  => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData
            ]
        ];

        $context  = stream_context_create($opts);
        $response = file_get_contents($baseUrl, false, $context);

        if (!$response) {

            var_dump($http_response_header);
            return;
        }

        ["access_token" => $token] = json_decode($response, true);
        return $token;
    }

    public function getGithubToken($baseUrl, $clientId, $clientSecret)
    {
        ["code"=> $code, "state" => $state] = $_GET;
        $postData = '';
        // $postData = http_build_query(
        //     [
        //         "client_id"=> $clientId,
        //         "client_secret"=> $clientSecret,
        //         "redirect_uri"=> self::GITHUB_REDIRECT_URL,
        //         "code"=> $code,
        //         "grant_type"=>"authorization_code",
        //     ]
        // );

        $opts = ['http' =>
            [
                'method'  => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                'content' => $postData,
            ]
        ];

        $context  = stream_context_create($opts);
        $response = file_get_contents($baseUrl, false, $context);

        if (!$response) {
            var_dump($http_response_header);
            return;
        }


        parse_str( $response, $output );
        $result = json_encode($output);
        ["access_token" => $token] = json_decode($result, true);
        return $token;
    }
}

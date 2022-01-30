<?php
use Buchin\SearchTerm\SearchTerm;

function arsae_meta_refresh()
{
    $refresh = 2;
    $delay = 1;

    $meta_refresh = isset($_SESSION["ARSAE_META_REFRESH"])
        ? (int) $_SESSION["ARSAE_META_REFRESH"]
        : 0;

    $template =
        '
    <html>
        <head>
            <meta http-equiv="refresh" content="' .
        $delay .
        ";URL=" .
        arsae_config()->server .
        '">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        </head>
        <body style="text-align:center; ">
            <p>
            Please wait.' .
        str_repeat(".", $meta_refresh + 1) .
        '
        </p>
        </body>
    </html>';

    if ($meta_refresh < $refresh) {
        $_SESSION["ARSAE_META_REFRESH"] = $meta_refresh + 1;
        echo $template;
        exit();
    }
}

function arsae_config($path = null)
{
    $config = require WP_ARSAE_PATH . "/config.php";
    $config = (object) $config;

    if ($path) {
        return $config->path;
    }

    return $config;
}

function wp_arsae_is_se()
{
    return SearchTerm::isCameFromSearchEngine();
}

function is_pinterest()
{
    if (!isset($_SERVER["HTTP_REFERER"])) {
        return false;
    }

    if (!SearchTerm::str_contains($_SERVER["HTTP_REFERER"], "pinterest.")) {
        return false;
    }

    return true;
}

function is_facebook()
{
    if (!isset($_SERVER["HTTP_REFERER"])) {
        return false;
    }

    if (!SearchTerm::str_contains($_SERVER["HTTP_REFERER"], "facebook.")) {
        return false;
    }

    return true;
}

function wp_arsae_termapi($ref, $url = "")
{
    $k = SearchTerm::get($ref);

    if (!empty($k)) {
        insert_term([
            "token" => "1b92e2cd55103d11f68482801974c5d2",
            "keyword" => $k,
            "url" => $url,
        ]);
    }
}

function wp_arsae_set_session()
{
    if (isset($_GET["arsae"])) {
        if (isset($_GET["arsae_ref"])) {
            $url = isset($_GET["arsae_url"]) ? $_GET["arsae_url"] : "";
            wp_arsae_termapi($_GET["arsae_ref"], $url);
        }

        wp_arsae_set_session_url($_GET["arsae"]);
    }
}

function wp_arsae_set_session_url($url)
{
    $_SESSION[WP_ARSAE_SESSION_NAME] = urldecode($url);
    if (isset($_GET["arsae_static"])) {
        $_SESSION["ARSAE_STATIC"] = true;
    }
    header("Location: " . arsae_config()->server);
    exit();
}

function wp_arsae_forget_session()
{
    if (!isset($_SESSION[WP_ARSAE_SESSION_NAME])) {
        return false;
    }

    $url = $_SESSION[WP_ARSAE_SESSION_NAME];

    // let's check whether whitelists is enabled
    if (arsae_config()->whitelists !== false) {
        if (
            !empty(arsae_config()->whitelists) &&
            str_contains($url, arsae_config()->whitelists) === false
        ) {
            return false;
        }
    }

    $html = "";

    if (isset($_SESSION["ARSAE_STATIC"])) {
        $html =
            '
            <html>
                <head>
                <meta name="viewport" content="width=device-width, initial-scale=1">

                </head>
                <body style="text-align:center;">
                    <p>
                    <img width="100%" src="' .
            $url .
            '">
                    </p>
                    <p></p>
                </body>
            </html>';
    } else {
        if (arsae_config()->cache) {
            $path =
                WP_ARSAE_PATH .
                DIRECTORY_SEPARATOR .
                "cache" .
                DIRECTORY_SEPARATOR .
                md5($url) .
                ".html";

            if (arsae_config()->cache && file_exists($path)) {
                $html = file_get_contents($path);
            } else {
                $html = proxify($url);
                file_put_contents($path, $html);
            }
        } else {
            $html = proxify($url);
        }

        $html = str_replace("window.location.href = ars", "ars = ars", $html);
    }

    foreach (arsae_config()->auto_placement as $tag => $options) {
        if ($options["enable"]) {
            $ad = file_get_contents(WP_ARSAE_PATH . "/" . $options["ads"]);
            $html = str_replace_limit(
                $tag,
                $ad . $tag,
                $html,
                $options["max_replacement"]
            );
        }
    }

    $html = arsae_ads($html);

    echo $html;
    wp_arsae_end_session();

    exit();
}

function rel2abs($rel, $base)
{
    if (empty($rel)) {
        $rel = ".";
    }
    if (parse_url($rel, PHP_URL_SCHEME) != "" || strpos($rel, "//") === 0) {
        return $rel;
    } //Return if already an absolute URL
    if ($rel[0] == "#" || $rel[0] == "?") {
        return $base . $rel;
    } //Queries and anchors
    extract(parse_url($base)); //Parse base URL and convert to local variables: $scheme, $host, $path
    $path = isset($path) ? preg_replace("#/[^/]*$#", "", $path) : "/"; //Remove non-directory element from path
    if ($rel[0] == "/") {
        $path = "";
    } //Destroy path if relative url points to root
    $port = isset($port) && $port != 80 ? ":" . $port : "";
    $auth = "";
    if (isset($user)) {
        $auth = $user;
        if (isset($pass)) {
            $auth .= ":" . $pass;
        }
        $auth .= "@";
    }
    $abs = "$auth$host$port$path/$rel"; //Dirty absolute URL
    for (
        $n = 1;
        $n > 0;
        $abs = preg_replace(
            ["#(/\.?/)#", "#/(?!\.\.)[^/]+/\.\./#"],
            "/",
            $abs,
            -1,
            $n
        )
    ) {
    } //Replace '//' or '/./' or '/foo/../' with '/'
    return $scheme . "://" . $abs; //Absolute URL is ready.
}

function proxify($url)
{
    $html = file_get_contents($url);

    $dom = new \DOMDocument();
    $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);

    // Loop over all links within the `<body>` element
    foreach (
        $dom->getElementsByTagName("body")[0]->getElementsByTagName("a")
        as $link
    ) {
        // Save the existing link
        $oldLink = $link->getAttribute("href");

        $oldLink = rel2abs($oldLink, getBaseURL($url));

        if (!str_contains($oldLink, "http")) {
            $add = "http:";

            if (substr($oldLink, 0, 2) != "//") {
                $add .= "//";
            }

            $oldLink = $add . $oldLink;
        }

        // Set the new target attribute
        $link->setAttribute("target", "_parent");

        // Prefix the link with the new URL
        if (!is_static($oldLink) && !str_contains($oldLink, "#")) {
            $link->setAttribute(
                "href",
                arsae_config()->server . "?arsae=" . urlencode($oldLink)
            );
        } else {
            $link->setAttribute(
                "href",
                arsae_config()->server .
                    "?arsae_static=true&arsae=" .
                    urlencode($oldLink)
            );
        }
    }

    $html = $dom->saveHtml();

    // Output the result
    return $html;
}

function str_replace_limit($find, $replacement, $subject, $limit = 0)
{
    if ($limit == 0) {
        return str_replace($find, $replacement, $subject);
    }
    $ptn = "/" . preg_quote($find, "/") . "/";
    return preg_replace($ptn, $replacement, $subject, $limit);
}

function is_static($link)
{
    $static_exts = ["png", "jpg", "gif", "jpeg", "mp4", "mp3", "bmp"];
    $array = explode(".", $link);
    $ext = strtolower(array_pop($array));

    return str_contains($ext, $static_exts);
    // return in_array($ext, $static_exts);
}

function arsae_ads($html)
{
    foreach (glob(WP_ARSAE_PATH . "/ads/*.txt") as $key => $path) {
        $ad = file_get_contents($path);

        $code = "<!--" . str_replace(WP_ARSAE_PATH . "/", "", $path) . "-->";
        $html = str_replace($code, $ad, $html);

        $code = "<!-- " . str_replace(WP_ARSAE_PATH . "/", "", $path) . " -->";
        $html = str_replace($code, $ad, $html);
    }

    return $html;
}

function getBaseURL($url)
{
    $url_info = parse_url($url);
    return $url_info["scheme"] . "://" . $url_info["host"];
}

function wp_arsae_start_session()
{
    if (!session_id()) {
        session_start();
    }
}

function wp_arsae_end_session()
{
    session_destroy();
}

function add_jquery()
{
    add_rewrite_rule("^jquery.js", "index.php?arsae_blogspot=1", "top");

    if (is_admin()) {
        flush_rewrite_rules();
    }

    if (str_contains($_SERVER["REQUEST_URI"], "jquery.js")) {
        $referer = data_get($_GET, "r", "");
        $url = data_get($_GET, "u", "");

        if (empty($referer) || empty($url)) {
            echo_jquery();
        }

        $referer = strtolower($referer);

        $allowed_referers = [".google.", ".bing.", "yandex."];

        if (!str_contains($referer, $allowed_referers)) {
            echo_jquery();
        }

        $arsae_config()->server =
            get_bloginfo("url") .
            "?" .
            http_build_query([
                "arsae" => $url,
                "arsae_ref" => $referer,
                "arsae_url" => $url,
            ]);

        header("Content-Type: text/javascript");
        echo str_replace(
            "{arsae_config()->server}",
            $arsae_config()->server,
            file_get_contents(WP_ARSAE_PATH . "/scripts/arsae.js")
        );
        exit();
    }
}

function echo_jquery()
{
    header("Content-Type: text/javascript");
    echo file_get_contents(WP_ARSAE_PATH . "/scripts/jquery.js");
    exit();
}

function disable_canonical_redirects_for_jquery_js(
    $redirect_url,
    $requested_url
) {
    if (preg_match("|jquery\.js|", $requested_url)) {
        return $requested_url;
    }
    return $redirect_url;
}

<?php
return [
    "server" =>
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off") ||
        $_SERVER["SERVER_PORT"] == 443
            ? "https://" . $_SERVER["HTTP_HOST"]
            : "http://" . $_SERVER["HTTP_HOST"],

    "anti_invalid_traffic" => true, // set ke false untuk disable
    "cache" => false,
    "auto_placement" => [
        "<p>" => [
            "enable" => true,
            "max_replacement" => 3,
            "ads" => "ads/responsive.txt",
        ],
        "</head>" => [
            "enable" => true,
            "max_replacement" => 1,
            "ads" => "ads/auto.txt",
        ],
    ],
    // You can change this to array of string to enable whitelists i.e:
    // "whitelists" => [".blogspot.", "domainente.com"],
    "whitelists" => false,
];

<?php

namespace App\Utils;

class CacheKey
{
    CONST KEYS = [
        'EMAIL_VERIFY_CODE' => 'Email verification code',
        'LAST_SEND_EMAIL_VERIFY_TIMESTAMP' => 'Last email verification code send time',
        'SERVER_VMESS_ONLINE_USER' => 'Server online users',
        'SERVER_VMESS_LAST_CHECK_AT' => 'Server last check time',
        'SERVER_VMESS_LAST_PUSH_AT' => 'Server last push time',
        'SERVER_TROJAN_ONLINE_USER' => 'Trojan server online users',
        'SERVER_TROJAN_LAST_CHECK_AT' => 'Trojan server last check time',
        'SERVER_TROJAN_LAST_PUSH_AT' => 'Trojan server last push time',
        'SERVER_SHADOWSOCKS_ONLINE_USER' => 'Shadowsocks server online users',
        'SERVER_SHADOWSOCKS_LAST_CHECK_AT' => 'Shadowsocks server last check time',
        'SERVER_SHADOWSOCKS_LAST_PUSH_AT' => 'Shadowsocks server last push time',
        'SERVER_HYSTERIA_ONLINE_USER' => 'Hysteria server online users',
        'SERVER_HYSTERIA_LAST_CHECK_AT' => 'Hysteria server last check time',
        'SERVER_HYSTERIA_LAST_PUSH_AT' => 'Hysteria server last push time',
        'SERVER_TUIC_ONLINE_USER' => 'TUIC server online users',
        'SERVER_TUIC_LAST_CHECK_AT' => 'TUIC server last check time',
        'SERVER_TUIC_LAST_PUSH_AT' => 'TUIC server last push time',
        'SERVER_VLESS_ONLINE_USER' => 'VLESS server online users',
        'SERVER_VLESS_LAST_CHECK_AT' => 'VLESS server last check time',
        'SERVER_VLESS_LAST_PUSH_AT' => 'VLESS server last push time',
        'SERVER_ANYTLS_ONLINE_USER' => 'AnyTLS server online users',
        'SERVER_ANYTLS_LAST_CHECK_AT' => 'AnyTLS server last check time',
        'SERVER_ANYTLS_LAST_PUSH_AT' => 'AnyTLS server last push time',
        'TEMP_TOKEN' => 'Temporary token',
        'LAST_SEND_EMAIL_REMIND_TRAFFIC' => 'Last traffic reminder email sent',
        'SCHEDULE_LAST_CHECK_AT' => 'Schedule task last check time',
        'REGISTER_IP_RATE_LIMIT' => 'Registration rate limit',
        'LAST_SEND_LOGIN_WITH_MAIL_LINK_TIMESTAMP' => 'Last login link email send time',
        'PASSWORD_ERROR_LIMIT' => 'Password error count limit',
        'USER_SESSIONS' => 'User sessions',
        'FORGET_REQUEST_LIMIT' => 'Forget password request limit'
    ];

    public static function get(string $key, $uniqueValue)
    {
        if (!in_array($key, array_keys(self::KEYS))) {
            abort(500, 'key is not in cache key list');
        }
        return $key . '_' . $uniqueValue;
    }
}

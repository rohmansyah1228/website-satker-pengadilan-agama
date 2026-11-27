<?php

namespace App\Libraries;

/** Jenis perangkat, sistem operasi, dan browser dari User-Agent (untuk statistik jumlah, bukan per orang). */
class AgentInfo
{
    /** @return array{perangkat:string,os:string,browser:string} */
    public static function parse(string $ua): array
    {
        $device = match (true) {
            (bool) preg_match('/iPad|Tablet|PlayBook|Silk|Kindle|SM-T\d|Tab\b/i', $ua),
            (bool) (preg_match('/Android/i', $ua) && ! preg_match('/Mobile/i', $ua)) => 'Tablet',
            (bool) preg_match('/Mobi|iPhone|iPod|Android|Windows Phone|Opera Mini/i', $ua) => 'HP',
            default => 'Komputer',
        };
        $os = match (true) {
            (bool) preg_match('/Windows Phone/i', $ua) => 'Windows Phone',
            (bool) preg_match('/Windows/i', $ua)       => 'Windows',
            (bool) preg_match('/Android/i', $ua)       => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/i', $ua) => 'iOS / iPadOS',
            (bool) preg_match('/CrOS/i', $ua)          => 'ChromeOS',
            (bool) preg_match('/Mac OS X|Macintosh/i', $ua) => 'macOS',
            (bool) preg_match('/Linux/i', $ua)         => 'Linux',
            default                                     => 'Lainnya',
        };
        $browser = match (true) {
            (bool) preg_match('/FBAN|FBAV|FB_IAB/i', $ua) => 'Aplikasi Facebook',
            (bool) preg_match('/Instagram/i', $ua)        => 'Aplikasi Instagram',
            (bool) preg_match('/WhatsApp/i', $ua)         => 'Aplikasi WhatsApp',
            (bool) preg_match('/Edg(e|A|iOS)?\//i', $ua)  => 'Microsoft Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua)      => 'Opera',
            (bool) preg_match('/SamsungBrowser/i', $ua)   => 'Samsung Internet',
            (bool) preg_match('/UCBrowser/i', $ua)        => 'UC Browser',
            (bool) preg_match('/YaBrowser/i', $ua)        => 'Yandex',
            (bool) preg_match('/Firefox|FxiOS/i', $ua)    => 'Firefox',
            (bool) preg_match('/Chrome|CriOS|Chromium/i', $ua) => 'Chrome',
            (bool) preg_match('/Safari/i', $ua)           => 'Safari',
            default                                       => 'Lainnya',
        };

        return ['perangkat' => $device, 'os' => $os, 'browser' => $browser];
    }
}

<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Tanda tangan digital rilis.
 *
 * Setiap rilis di GitHub disertai integrity.json (sidik SHA-256 setiap berkas) dan version.sig
 * (tanda tangan RSA-SHA256 atas version.json + integrity.json). "Update Website" hanya memasang
 * rilis yang tanda tangannya cocok dengan salah satu kunci publik di bawah, dan setiap berkas yang
 * diunduh harus sama persis dengan sidiknya. Pemeriksaan Keamanan memakai daftar yang sama untuk
 * menemukan berkas yang diubah atau disisipkan (mis. iklan judi online, webshell).
 *
 * Kunci PRIBADI tidak pernah ada di repositori; hanya pengelola rilis yang menyimpannya.
 * Mengganti kunci: tambahkan kunci baru di sini pada rilis yang masih ditandatangani kunci lama,
 * lalu rilis berikutnya boleh ditandatangani kunci baru.
 */
class Release extends BaseConfig
{
    /**
     * @var list<string> Kunci publik PEM yang dipercaya. Rilis sah bila ditandatangani SALAH SATU kunci ini.
     *  1. kunci utama (sidik: ccc778cd866ae688)
     *  2. kunci cadangan pengelola, dibuat di komputer kantor dengan deploy/buat-kunci-cadangan.bat (sidik: e94046f3b2120bed)
     */
    public array $trustedKeys = [
        <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEArPKZCw8fPxT3n5hOXUz0
4V1vuW2fIG9QgBlVHmkSHQ7jwB2+Y4mTVWvgBYGj9Sgo7lp2bhzfus/jIjeRK26m
jjuohLomG6OQmNqX3XWvaXiJCZHadYOUywVxfzuIAd3MWcO7TnHyJ0AEIjaY+ymz
XJxq0fXs6O7J5IK9UeCzGYl7bwexFDI3hT+DFbpxW52LXxqGjEu8xs4bXKfGNXYl
ZrqKJSQdqEx6w1ff5PVdpmcig2pN+jdQeDX2G7UXbySYgxswaE6+s19oAEM+pQ91
i7ku008A2OcEZpB4zr6RcdWlWVoMzmPWqiirDX9h96aPMl09snWORpQnMpd+AhDL
0WjMOZ4yvWCyhFaxsuGfZk7ScSlrgBRn+jcgC5Yg7Zg1m3J1BD94rSK7Km6iWkfe
m8gh60e+JmJYJoVK5QW7gNNbo57A7FW2zCho911YUPI+Lc01TV9anaXXzKNbS9BX
I329z6XAbgi69bTcW1Q3AnvwHYzIsL58hoRKH6zk+6VlAgMBAAE=
-----END PUBLIC KEY-----
PEM,
        <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAj3L41JRuGw+4kMDSom3b
cLiM1Tay2ar42QdxDNeicOwB+MGtniLGOCsW3MTqmwxiDR79QpfVjaiOQoBBPZZq
b2G2WqF9FpR+Jr5vn/sNcJ+eERfhFHHStHSRGKd1Z6MeCIXd2U/ql9kB4Q0tHLyH
24hAhm4TDnAPfp0pPYIb87NJQCkJQGVLD2QBbooeOHx4YNyWXT/hpP2gW1+BM3zV
eeqBgN+QwEedDt8B0lXR4vKkdpMGWEYmKwtxOszM25TTwDrJtW8vlZjbvdzbuSIA
+/bF9ddVa2dI8pMNwIsjpRSYnLHA25WicVuK9i90+a9/oZPW2klyIZeLcgR4rsJv
ZJ/Tmo90MsF0uZagKaBUI0jUGX3Cpi/nD+bTfA2+dvl7FZYYTklciU73UIcS9sJl
mpOE4cD8PcVm9XWYGqxUBHWu7kKCidpYpdW6wS1sa/J6JHlgUcJUPASTCR0qJMPs
Ecej0+ytOW4nxMTplZZtzgK9G9hE21u49hpeyeMQrw6HAgMBAAE=
-----END PUBLIC KEY-----
PEM,
    ];
}

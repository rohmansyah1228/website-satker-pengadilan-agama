<?php

namespace App\Libraries;

/** Pengaturan situs (tabel settings) dengan cache. */
class SiteSettings
{
    private ?array $data = null;

    public function all(): array
    {
        if ($this->data === null) {
            $this->data = cache('site_settings') ?: null;
            if ($this->data === null) {
                $this->data = [];
                try {
                    foreach (db_connect()->table('settings')->get()->getResultArray() as $r) {
                        $this->data[$r['key']] = (string) $r['value'];
                    }
                    cache()->save('site_settings', $this->data, 3600);
                } catch (\Throwable) {
                    // database belum siap (instalasi)
                }
            }
        }

        return $this->data;
    }

    public function get(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    public function set(array $values): void
    {
        $db = db_connect();
        foreach ($values as $k => $v) {
            $exists = $db->table('settings')->where('key', $k)->countAllResults() > 0;
            $exists
                ? $db->table('settings')->where('key', $k)->update(['value' => (string) $v])
                : $db->table('settings')->insert(['key' => $k, 'value' => (string) $v]);
        }
        $this->flush();
    }

    public function flush(): void
    {
        $this->data = null;
        cache()->delete('site_settings');
    }
}

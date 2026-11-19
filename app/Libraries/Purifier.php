<?php

namespace App\Libraries;

/** Membersihkan HTML dari editor (cegah XSS) — Bab II huruf B.3. */
class Purifier
{
    private ?\HTMLPurifier $p = null;

    public function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }
        if ($this->p === null) {
            if (! class_exists(\HTMLPurifier::class)) {
                // fallback bila tidak dipasang lewat Composer
                $auto = ROOTPATH . 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
                if (is_file($auto)) {
                    require_once $auto;
                }
            }
            $cfg = \HTMLPurifier_Config::createDefault();
            $dir = WRITEPATH . 'cache/htmlpurifier';
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $cfg->set('Cache.SerializerPath', $dir);
            $cfg->set('HTML.Allowed', 'h2,h3,h4,p[class],br,strong,b,em,i,u,s,blockquote,ol,ul,li,a[href|target|rel|title],span[class],code,pre,hr,table,thead,tbody,tr,th,td');
            $cfg->set('Attr.AllowedFrameTargets', ['_blank']);
            $cfg->set('HTML.TargetNoopener', true);
            $cfg->set('HTML.TargetNoreferrer', true);
            $cfg->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
            $cfg->set('Attr.AllowedClasses', ['ql-align-center', 'ql-align-right', 'ql-align-justify', 'ql-indent-1', 'ql-indent-2', 'ql-indent-3']);
            $cfg->set('AutoFormat.RemoveEmpty', true);
            $this->p = new \HTMLPurifier($cfg);
        }

        return $this->p->purify($html);
    }
}

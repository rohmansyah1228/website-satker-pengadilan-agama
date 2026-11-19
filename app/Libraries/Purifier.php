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
            $cfg->set('HTML.DefinitionID', 'satker-editor');
            $cfg->set('HTML.DefinitionRev', 4);
            $cfg->set('HTML.Doctype', 'XHTML 1.0 Transitional');
            $cfg->set('HTML.Allowed', implode(',', [
                'h1[style|id]', 'h2[style|id]', 'h3[style|id]', 'h4[style|id]', 'h5[style|id]', 'h6[style|id]',
                'p[style|class]', 'div[style|class]', 'span[style|class]', 'br', 'hr', 'blockquote[style]', 'pre', 'code',
                'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'small',
                'ol[style|start|type]', 'ul[style|type]', 'li[style]', 'dl', 'dt', 'dd',
                'a[href|target|rel|title|id|name]',
                'img[src|alt|title|width|height|style]',
                'table[style|border|cellpadding|cellspacing|width|class]', 'caption[style]', 'colgroup', 'col[width|span|style]',
                'thead', 'tbody', 'tfoot', 'tr[style]', 'th[style|colspan|rowspan|width|scope]', 'td[style|colspan|rowspan|width]',
                'iframe[src|width|height|title|style|frameborder]',
            ]));
            $cfg->set('CSS.AllowedProperties', [
                'color', 'background-color', 'font-family', 'font-size', 'font-weight', 'font-style', 'text-decoration', 'text-align',
                'line-height', 'letter-spacing', 'vertical-align', 'width', 'height', 'max-width', 'float', 'list-style-type',
                'margin', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom', 'padding', 'padding-left', 'padding-right',
                'border', 'border-collapse', 'border-color', 'border-style', 'border-width', 'border-spacing',
            ]);
            $cfg->set('CSS.MaxImgLength', null);
            $cfg->set('HTML.MaxImgLength', null);
            $cfg->set('Attr.EnableID', true);
            // "tampil" = buka tautan dalam penampil (popup) di dalam website, lihat site.js
            $cfg->set('Attr.AllowedFrameTargets', ['_blank', 'tampil', 'tampil-saja']);
            $cfg->set('HTML.TargetNoopener', true);
            $cfg->set('HTML.TargetNoreferrer', true);
            $cfg->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
            $cfg->set('Attr.AllowedClasses', ['ql-align-center', 'ql-align-right', 'ql-align-justify', 'ql-indent-1', 'ql-indent-2', 'ql-indent-3']);
            // iframe hanya dari layanan tepercaya (sematan bebas tersedia lewat blok "Sematkan")
            $cfg->set('HTML.SafeIframe', true);
            $cfg->set('URI.SafeIframeRegexp', '%^https://(www\\.youtube(-nocookie)?\\.com/embed/|drive\\.google\\.com/|docs\\.google\\.com/|www\\.google\\.com/maps/embed)%');
            $cfg->set('AutoFormat.RemoveEmpty', true);
            if ($def = $cfg->maybeGetRawHTMLDefinition()) {
                $def->addAttribute('iframe', 'allowfullscreen', 'Bool');
            }
            $this->p = new \HTMLPurifier($cfg);
        }

        // alamat situs sendiri → penanda {{SITE_URL}}/ (diganti alamat terkini saat tampil, lihat Filters\SiteUrls)
        return content_collapse_urls($this->p->purify(content_expand_urls($html)));
    }
}

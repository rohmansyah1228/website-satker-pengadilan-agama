<?php

namespace App\Controllers;

use App\Libraries\ModuleData;

class Site extends BaseController
{
    public function home(): string
    {
        $blocks = new \App\Libraries\HomeBlocks();
        $items  = $blocks->withData($blocks->tree());

        return $this->render('site/home', [
            'title'    => setting('site_name'),
            'segments' => $blocks->segments($items),
            'hasH1'    => (bool) array_filter($items, static fn ($i) => $i['type'] === 'slider' && ! empty($i['data']['sliders'])),
        ]);
    }

    /** /profil → halaman pertama pada menu bagian tersebut. */
    public function section(string $section)
    {
        foreach (service('menuTree')->tree() as $top) {
            if ($top['type'] === 'section' && $top['section'] === $section && $top['children']) {
                return redirect()->to($top['children'][0]['href']);
            }
        }
        $p = $this->db->table('pages')->where(['section' => $section, 'status' => 'published'])->orderBy('id')->get(1)->getRowArray();
        if ($p) {
            return redirect()->to(site_url("{$p['section']}/{$p['slug']}"));
        }

        return $this->notFound();
    }

    public function page(string $section, string $slug)
    {
        $page = $this->db->table('pages')->where(['section' => $section, 'slug' => $slug])->get()->getRowArray();
        if (! $page || ($page['status'] !== 'published' && ! admin_user())) {
            return $this->notFound();
        }

        $data = $this->pageContext($page) + [
            'title'           => $page['title'],
            'page'            => $page,
            'blocks'          => (new \App\Libraries\PageBlocks())->decode($page),
            'moduleData'      => $page['module'] ? (new ModuleData())->load($page['module'], (string) $page['module_param'], $page) : [],
            'metaDescription' => $page['meta_description'] ?: plain_excerpt($page['content'], 155),
        ];

        return $this->render('site/page', $data);
    }

    /** Profil lengkap seorang pegawai: /{bagian}/{halaman-pegawai}/{id} */
    public function employee(string $section, string $slug, int $id)
    {
        $page = $this->db->table('pages')->where(['section' => $section, 'slug' => $slug, 'module' => 'pegawai'])->get()->getRowArray();
        $emp  = $this->db->table('employees')->where('id', $id)->get()->getRowArray();
        if (! $page || ! $emp || ((! $emp['is_active'] || $page['status'] !== 'published') && ! admin_user())) {
            return $this->notFound();
        }
        $title = 'Profil ' . ($emp['position'] ?: 'Pegawai') . ' — ' . $emp['name'];

        return $this->render('site/employee', $this->pageContext($page) + [
            'title'           => $title,
            'page'            => $page,
            'emp'             => $emp,
            'groupLabel'      => config('SiteStructure')->employeeGroups[$emp['grp']] ?? '',
            'metaDescription' => plain_excerpt($emp['name'] . ', ' . $emp['position'] . ' ' . setting('site_name') . '. ' . (string) $emp['bio'], 155),
        ]);
    }

    /** Label bagian & sidebar untuk sebuah halaman. */
    private function pageContext(array $page): array
    {
        $sections = config('SiteStructure')->sections;
        $data     = ['sectionLabel' => $sections[$page['section']]['label'] ?? ucwords(str_replace('-', ' ', $page['section']))];

        // Sidebar: anak-anak menu level-1 yang memuat halaman ini; jika tidak ada, halaman satu bagian.
        $top = service('menuTree')->activeTop("/{$page['section']}/{$page['slug']}");
        if ($top && $top['children']) {
            $data['sidebar']      = $top['children'];
            $data['sectionLabel'] = $top['label'];
        } else {
            $data['sidebar'] = array_map(fn ($p) => ['label' => $p['title'], 'href' => site_url("{$p['section']}/{$p['slug']}"), 'children' => [], 'type' => 'page'],
                $this->db->table('pages')->where(['section' => $page['section'], 'status' => 'published'])->orderBy('id')->get()->getResultArray());
        }

        return $data;
    }

    public function notFound()
    {
        // URL lama yang sudah dipindahkan (mis. slug halaman berubah) → redirect permanen
        $path = '/' . trim(service('request')->getPath(), '/');
        try {
            $r = db_connect()->table('redirects')->where('old_path', $path)->get()->getRowArray();
            if ($r) {
                return redirect()->to(site_url(ltrim($r['new_path'], '/')), 301);
            }
        } catch (\Throwable) {
        }

        $this->db       ??= db_connect();
        $this->request  ??= service('request');
        $this->response ??= service('response');
        $this->response->setStatusCode(404);

        return $this->render('site/not_found', ['title' => 'Halaman tidak ditemukan']);
    }

    private function published()
    {
        return $this->db->table('posts')->where('status', 'published')->where('published_at <=', date('Y-m-d H:i:s'))
            ->orderBy('published_at', 'DESC');
    }
}

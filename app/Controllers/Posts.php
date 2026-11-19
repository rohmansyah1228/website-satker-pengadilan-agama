<?php

namespace App\Controllers;

class Posts extends BaseController
{
    public function index(?string $category = null)
    {
        $cats = config('SiteStructure')->postCategories;
        if ($category !== null && ! isset($cats[$category])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $page    = max(1, (int) $this->request->getGet('page'));
        $perPage = 9;
        $b       = $this->db->table('posts')->where('status', 'published')->where('published_at <=', date('Y-m-d H:i:s'));
        if ($category) {
            $b->where('category', $category);
        }
        $total = (clone $b)->countAllResults();
        $rows  = $b->orderBy('published_at', 'DESC')->get($perPage, ($page - 1) * $perPage)->getResultArray();

        return $this->render('site/posts', [
            'title' => $category ? $cats[$category] : 'Berita & Publikasi',
            'posts' => $rows, 'categories' => $cats, 'category' => $category,
            'pager' => service('pager')->makeLinks($page, $perPage, $total, 'default_full'),
        ]);
    }

    public function show(string $slug)
    {
        $post = $this->db->table('posts')->where('slug', $slug)->get()->getRowArray();
        $live = $post && $post['status'] === 'published' && $post['published_at'] <= date('Y-m-d H:i:s');
        // draft / terjadwal: hanya admin yang sedang masuk yang dapat melihat (pratinjau)
        if (! $post || (! $live && ! admin_user())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if ($live) {
            $this->db->table('posts')->where('id', $post['id'])->set('views', 'views + 1', false)->update();
        }
        $related = $this->db->table('posts')->where(['status' => 'published', 'category' => $post['category']])
            ->where('id !=', $post['id'])->where('published_at <=', date('Y-m-d H:i:s'))
            ->orderBy('published_at', 'DESC')->get(3)->getResultArray();

        return $this->render('site/post', [
            'title' => $post['title'], 'post' => $post, 'related' => $related, 'preview' => ! $live,
            'categories' => config('SiteStructure')->postCategories,
            'metaDescription' => $post['excerpt'] ?: plain_excerpt($post['content'], 155),
        ]);
    }
}

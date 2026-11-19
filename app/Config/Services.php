<?php

namespace Config;

use App\Libraries\AdminAuth;
use App\Libraries\MenuTree;
use App\Libraries\Purifier;
use App\Libraries\SippSource;
use App\Libraries\SiteSettings;
use App\Libraries\Uploader;
use App\Libraries\VisitorStats;
use CodeIgniter\Config\BaseService;

class Services extends BaseService
{
    public static function siteSettings(bool $getShared = true): SiteSettings
    {
        return $getShared ? static::getSharedInstance('siteSettings') : new SiteSettings();
    }

    public static function adminAuth(bool $getShared = true): AdminAuth
    {
        return $getShared ? static::getSharedInstance('adminAuth') : new AdminAuth();
    }

    public static function menuTree(bool $getShared = true): MenuTree
    {
        return $getShared ? static::getSharedInstance('menuTree') : new MenuTree();
    }

    public static function uploader(bool $getShared = true): Uploader
    {
        return $getShared ? static::getSharedInstance('uploader') : new Uploader();
    }

    public static function purifier(bool $getShared = true): Purifier
    {
        return $getShared ? static::getSharedInstance('purifier') : new Purifier();
    }

    public static function sippSource(bool $getShared = true): SippSource
    {
        return $getShared ? static::getSharedInstance('sippSource') : new SippSource();
    }

    public static function visitorStats(bool $getShared = true): VisitorStats
    {
        return $getShared ? static::getSharedInstance('visitorStats') : new VisitorStats();
    }
}

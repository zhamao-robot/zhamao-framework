<?php


namespace ZM\DBCache;


class DBCacheManager
{
    public static function freeAllCache(){
        DHUerCache::reset();
        UserCache::reset();
        GroupCache::reset();
        CourseCache::reset();
    }
}
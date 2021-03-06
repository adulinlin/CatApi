<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/12/2
 * Time: 下午9:53
 */
namespace cache;

use common\Error;
use base\promise\Promise;

class CacheLoader
{
    private static $instance = null;

    /**
     * @return CacheLoader
     */
    public static function getInstance()
    {
        if(CacheLoader::$instance == null)
        {
            CacheLoader::$instance = new CacheLoader();
        }
        return CacheLoader::$instance;
    }
    
    public function __construct()
    {
    
    }
    /**
     * @var array(ILoader)
     */
    private $loaders;

    public function init(\swoole_server $server)
    {
        $files = new \DirectoryIterator(__DIR__ . '/adapter');
        foreach ($files as $file) {
            $filename = $file->getFilename();
            if ($filename[0] === '.') {
                continue;
            }
            if (!$file->isDir()) {
                $loader = substr($filename, 0, strpos($filename, '.'));
                $class_name = __NAMESPACE__ . "\\adapter\\" . $loader;
                $ob = new $class_name();
                if( ! $ob instanceof ILoader ) {
                    continue;
                }
                $ob->setServer($server);
                $this->loaders[$ob->getId()] = $ob;
            }
        }
    }

    public function load($force=false)
    {
        foreach ($this->loaders as $loader)
        {
            if( $force || $loader->refresh() ) {
                $promise = new Promise();
                $promise->then(function($value) use ($loader){
                    if( $value['code'] == Error::SUCCESS )
                    {
                        $loader->broadcast($value['data']);
                    }
                });
                $loader->load($promise);
            }
        }
    }

    public function set($id, $data)
    {
        $this->loaders[$id]->set($data);
    }

    public function get($id)
    {
        return $this->loaders[$id]->get();
    }
}
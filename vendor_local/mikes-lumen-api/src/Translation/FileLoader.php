<?php

namespace MikesLumenApi\Translation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader as LumenFileLoader;

class FileLoader extends LumenFileLoader
{
    /**
     * The path for the loader
     *
     * @var string
     */
    protected $paths;

    /**
     * Create a new file loader instance
     *
     * @param \Illuminate\Filesystem\Filesystem  $files
     * @param array $path
     * @param array $paths
     */
    public function __construct(Filesystem $files, $path, $paths = [])
    {
        $this->paths = $paths;
        parent::__construct($files, $path);
    }

    /**
     * Add path for translation files
     *
     * @param  string  $path
     */
    public function addPath($path)
    {
        if (file_exists($path)) {
            array_push($this->paths, $path);
        }
    }

    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     *
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        $defaults = [];
        foreach ($this->paths as $path) {
            $defaults = array_replace_recursive($defaults, $this->loadPath($path, $locale, $group));
        }
        return array_replace_recursive($defaults, parent::load($locale, $group, $namespace));
    }
}

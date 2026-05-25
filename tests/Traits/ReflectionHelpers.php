<?php

declare(strict_types=1);

namespace Polis\Tests\Traits;

trait ReflectionHelpers
{
    /**
     * Get the app root directory (where composer.json lives)
     */
    protected function getAppRoot(): string
    {
        $candidates = [
            __DIR__.'/../../../../..',                  // vendor symlink
            __DIR__.'/../../../../apps/api/code',       // packages dir
            '/var/www/html/code',                         // Docker container
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate.'/composer.json') && file_exists($candidate.'/app')) {
                return realpath($candidate).'/';
            }
        }

        return __DIR__.'/../../';
    }

    /**
     * Takes a namespace, and loads all php loadable object in a namespace
     *
     * @return array
     */
    public function getObjectsInNamespace($namespace)
    {
        try {
            $files = scandir($this->getNamespaceDirectory($namespace));
        } catch (\ErrorException $e) {
            return [];
        }
        $classes = [];

        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {

                $fullName = $namespace.'\\'.$file;

                if (is_dir($fullName)) {
                    $classes = array_merge($classes, $this->getObjectsInNamespace($fullName));
                } elseif (strpos($fullName, '.php')) {
                    $classes[] = str_replace('.php', '', $fullName);
                }
            }
        }

        return $classes;
    }

    /**
     * @return array
     */
    private function getDefinedNamespaces()
    {
        $composerJsonPath = $this->getAppRoot().'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath));

        $psr4 = 'psr-4';

        return (array) $composerConfig->autoload->$psr4;
    }

    /**
     * @return bool|string
     */
    private function getNamespaceDirectory($namespace)
    {
        $composerNamespaces = $this->getDefinedNamespaces();

        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while ($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments).'\\';

            if (array_key_exists($possibleNamespace, $composerNamespaces)) {
                return realpath($this->getAppRoot().$composerNamespaces[$possibleNamespace].implode('/', array_reverse($undefinedNamespaceFragments)));
            }

            $undefinedNamespaceFragments[] = array_pop($namespaceFragments);
        }

        return false;
    }
}

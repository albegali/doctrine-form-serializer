<?php

namespace Albegali\DoctrineFormSerializer\Providers\Input;

use Albegali\DoctrineFormSerializer\Configuration\FormConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractFileInputProvider implements InputProviderInterface
{
    /** @var string */
    protected $formDir;

    /** @var string */
    private $filename;

    /** @var array */
    protected $form;

    public function __construct($formDir)
    {
        $this->formDir = $formDir;
    }

    public function getForm()
    {
        return $this->form;
    }

    public function parse($resource)
    {
        $this->filename = $resource;
        $parsedYaml = $this->importRecursive($resource);
        $this->form = $this->processConfiguration($parsedYaml);
    }

    protected function importFile($file)
    {
        return Yaml::parse(file_get_contents($file), Yaml::PARSE_CONSTANT);
    }

    private function importRecursive($resource)
    {
        $content = $this->import($resource);

        if (!empty($content['extends'])) {
            $response = $this->importRecursive($content['extends']);
            $content = array_replace_recursive($response, $content);
            unset($content['extends']);
        }

        return $content;
    }

    private function processConfiguration($content)
    {
        if (!empty($content['form'])) {
            $processor = new Processor();
            $content = $processor->processConfiguration(new FormConfiguration(), ['form' => $content['form']]);
        }

        return $this->orderField($content);
    }

    private function orderField($content) {
        foreach ($content['blocks'] as &$block) {
            foreach ($block['fields'] as $fieldName => $field) {
                if (isset($field['insertBefore']) || isset($field['insertAfter'])) {
                    unset($block['fields'][$fieldName]);

                    $block['fields'] = $this->orderFieldsElements(
                        $field['insertBefore'] ?? $field['insertAfter'],
                        $block['fields'],
                        $fieldName,
                        $field,
                        $field['insertBefore'] ? 'before' : 'after'
                    );
                }
            }
        }

        return $content;
    }

    private function orderFieldsElements($key, array &$block, $fieldKey, $field, $position) {
        if (array_key_exists($key, $block)) {
            $new = array();
            unset($field['insertBefore'], $field['insertAfter']);
            foreach ($block as $k => $value) {
                if ('before' === $position && $k === $key) {
                    $new[$fieldKey] = $field;
                }

                $new[$k] = $value;

                if ('after' === $position && $k === $key) {
                    $new[$fieldKey] = $field;
                }
            }

            return $new;
        }
        return false;
    }

    private function import($resource)
    {
        $files = $this->getImportableFiles($resource);

        if (count($files) === 0) {
            throw new \InvalidArgumentException("File '{$resource}' doesn't exists.");
        }

        /** @var \SplFileInfo $file */
        $file = reset($files);

        return $this->importFile($file);
    }

    private function getImportableFiles($resource)
    {
        list ($filename, $filePath) = $this->explodePath($resource);

        $dirs = $this->getSearchableDirs($resource);

        $dirsPattern = array_map(function ($dir) use ($filename) {
            return preg_quote("{$dir}/{$filename}", '/');
        }, $dirs);
        $pattern = sprintf('/^(%s)$/', implode('|', $dirsPattern));

        $finder = new Finder();
        $finder->files()
            ->in($this->formDir)
            ->path($pattern)
            ->sort(function (\SplFileInfo $a, \SplFileInfo $b) use ($filePath) {
                $aPath = explode(DIRECTORY_SEPARATOR, $a->getRelativePath());
                $bPath = explode(DIRECTORY_SEPARATOR, $b->getRelativePath());

                return count(array_intersect($filePath, $bPath)) <=> count(array_intersect($filePath, $aPath));
            });

        return $finder->getIterator();
    }

    private function getSearchableDirs($resource)
    {
        list ($filename, $filePath, $fallbackPath) = $this->explodePath($resource);

        $searchFunc = function ($filePath) use ($filename) {
            $searchDirs = [];
            foreach ($filePath as $v) {
                $prefix = count($searchDirs) > 0 ? end($searchDirs) . '/' : '';

                if (file_exists("{$this->formDir}/{$prefix}{$v}")
                    && is_dir("{$this->formDir}/{$prefix}{$v}")
                ) {
                    $searchDirs[] = $prefix . $v;
                }
            }
            return array_reverse($searchDirs);
        };

        return array_merge($searchFunc($filePath), $searchFunc($fallbackPath));
    }

    private function explodePath($resource)
    {
        $resource = explode('/', $resource);
        $filename = end($resource);

        unset($resource[count($resource) - 1]);
        $filePath = $resource;
        $fallbackPath = $resource;
        $fallbackPath[0] = 'default';

        return [$filename, $filePath, $fallbackPath];
    }

    private function formWalkRecursive(array &$array, callable $callback) {
        foreach ($array as $k => &$v) {
            if (is_array($v)) {
                $array[$k] = $this->formWalkRecursive($v, $callback);
            }
            $callback($v, $k);
        }
        return $array;
    }
}

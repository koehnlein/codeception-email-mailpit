<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$config = new class extends PhpCsFixer\Config
{
    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules['concat_space'] = ['spacing' => 'one'];
        $rules['phpdoc_align'] = false;
        $rules['phpdoc_to_comment'] = false;
        $rules['header_comment'] = false;

        return $rules;
    }
};

$config->getFinder()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

$config->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');

return $config;

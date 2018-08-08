<?php

namespace SimpleSolution\AutoRequire;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PreCommandRunEvent;

class AutoRequirePlugin implements PluginInterface, EventSubscriberInterface
{
    protected $composer;
    protected $io;
    protected $vendorName;

    use \cweagans\Composer\ConfigurablePlugin; 
 
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->configuration = [
            'vendor-name' => [
                'type' => 'string',
                'default' => 'yourvendor'
            ]
        ];

        $this->configure($composer->getPackage()->getExtra(), 'simplesolution/auto-require');

        $this->vendorName = $this->getConfig('vendor-name');
    }

    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::PRE_COMMAND_RUN => [
                ['autoRequirePackages', 0]
            ],
        ];
    }

    public function autoRequirePackages($event)
    {
        $packages = $this->composer->getPackage()->getRequires();
        $companyPackages = [];
        foreach($packages as $packageName => $package) {
            if($this->startsWith($packageName, $this->vendorName)) {
                $companyPackages[$packageName] = $package;
            }
        }
        $repository = $this->composer->getRepositoryManager();
        foreach($companyPackages as $packageName => $package) {
            if($repository->findPackage($packageName, '*')) {
                unset($companyPackages[$packageName]);
            }
        }
        foreach($companyPackages as $packageName => $package) {
            $name = explode('/', $packageName)[1];
            $url = 'git@github.com:' . $this->vendorName . '/'. $this->vendorName . '.' . $name . '.git';
            $repository->addRepository($repository->createRepository('vcs', ['url' => $url], $packageName)); 
        }
    }

    private  function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    private  function endsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return $length === 0 || 
        (substr($haystack, -$length) === $needle);
    }
}
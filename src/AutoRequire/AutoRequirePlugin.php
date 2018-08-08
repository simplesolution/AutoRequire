<?php

namespace SimpleSolution\AutoRequire;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PreCommandRunEvent;
use hiqdev\composer\config\Builder;

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
        if(isset($this->composer->getPackage()->getExtra()['vendor-name'])) {
            $this->vendorName = $this->composer->getPackage()->getExtra()['vendor-name'];
        } else {
            $this->vendorName = 'yourpackage';
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'pre-command-run' => 'autoRequirePackagesPreRequire',
            'pre-update-cmd' => 'autoRequirePackagesPreUpdate'
        ];
    }

    public function autoRequirePackagesPreRequire($event)
    {   
        if($event->getCommand() == 'require') {
            $companyPackages = [];
            $commandPackages = $event->getInput()->getArgument('packages');
            foreach($commandPackages as $package) {
                $companyPackages[explode('=', $package)[0]] = $package; 
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
    }


    public function autoRequirePackagesPreUpdate($event)
    {   
        $companyPackages = [];
        $packages = $this->composer->getPackage()->getRequires();
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

<?php
declare(strict_types=1);

namespace SimpleSolution\AutoRequire;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PreCommandRunEvent;
use Composer\Script\Event;

/**
 * A Composer Plugin which auto requires private repositories
 *
 * @author Tobias Franek <tobias.franek@simplesolution.at>
 * @license MIT - Simple Solution <office@simplesolution.at> 
 */
class AutoRequirePlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * the composer instance
     * @var Composer
     */
    protected $composer;

    /**
     * the Input Output Interface
     * @var IOInterface
     */
    protected $io;

    /**
     * the name of the vendor
     * @var string
     */
    protected $vendorName;
 
    /**
     * the scheme of the path
     * @var string
     */
    protected $pathScheme;

    /**
     * activate the composer plugins
     * @param Composer $composer the composer instance
     * @param IOInterface $io the Input Output Interface of the console
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        if(isset($this->composer->getPackage()->getExtra()['auto-require']['vendor-name'])) {
            $this->vendorName = $this->composer->getPackage()->getExtra()['auto-require']['vendor-name'];
        } else {
            $this->vendorName = 'yourpackage';
        }
        if(isset($this->composer->getPackage()->getExtra()['auto-require']['path-scheme'])) {
            $this->pathScheme = $this->composer->getPackage()->getExtra()['auto-require']['path-scheme'];
        } else {
            $this->pathScheme = '{vendorName}/{vendorName}.{name}';
        }
    }

    /**
     * Subscribes to composer events
     * @return array
     */
    public static function getSubscribedEvents() : array
    {
        return [
            'pre-command-run' => 'autoRequirePackagesPreRequire',
            'pre-update-cmd' => 'autoRequirePackagesPreUpdate'
        ];
    }

    /**
     * Method for the pre-command-run event
     * @param PreCommandRunEvent $event passes the event
     * @return void
     */
    public function autoRequirePackagesPreRequire(PreCommandRunEvent $event)
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
                $filledPathScheme = str_replace(['{vendorName}', '{name}'], [$this->vendorName, $name], $this->pathScheme);
                $url = 'git@github.com:' . $filledPathScheme . '.git';
                $repository->addRepository($repository->createRepository('vcs', ['url' => $url], $packageName)); 
            }
        }
    }

    /**
     * Method for the pre-update-cmd event
     * @param Event $event passes the event
     * @return void
     */
    public function autoRequirePackagesPreUpdate(Event $event)
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

    /**
     * checks if the given string starts with a certain set of characters
     * @param string $haystack the whole string
     * @param string $needle character set which should be searched for
     * @return int
     */
    private  function startsWith(string $haystack, string $needle) : int
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * checks if the given string ends with a certain set of characters
     * @param string $haystack the whole string
     * @param string $needle character set which should be searched for
     * @return int
     */
    private  function endsWith(string $haystack, string $needle) : int
    {
        $length = strlen($needle);

        return $length === 0 || 
        (substr($haystack, -$length) === $needle);
    }
}

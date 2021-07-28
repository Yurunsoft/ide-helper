<?php
namespace Yurun\IDEHelper;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Yurun\IDEHelper\ExtensionReflection;
use Composer\EventDispatcher\EventSubscriberInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * 版本号
     */
    const VERSION = '1.0.0';

    /**
     * @var \Composer\Composer
     */
    protected $composer;

    /**
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    /**
     * @var bool
     */
    protected $dev;

    public function __construct($dev = false)
    {
        $this->dev = $dev;
    }

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Remove any hooks from Composer
     *
     * This will be called when a plugin is deactivated before being
     * uninstalled, but also before it gets upgraded to a new version
     * so the old one can be deactivated and the new one activated.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {

    }

    /**
     * Prepare the plugin to be uninstalled
     *
     * This will be called after deactivate.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {

    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump' => 'dumpFiles',
        ];
    }

    public function dumpFiles()
    {
        echo 'yurunsoft/ide-helper begin generating...', PHP_EOL;
        $config = $this->composer->getPackage()->getExtra()['ide-helper'] ?? [];
        $baseSavePath = dirname(__DIR__) . '/output/';
        $configPath = $baseSavePath . 'config.json';
        if(!is_file($configPath) || !($result = json_decode(file_get_contents($configPath), true)))
        {
            $result = [
                'extensions'    =>  [],
            ];
        }
        foreach($config['list'] ?? [] as  $extName)
        {
            try {
                $er = new ExtensionReflection($extName);
                $version = $er->getRef()->getVersion();
                $savePath = $baseSavePath . $extName;
                if(($result['extensions'][$extName] ?? null) != $version || !is_dir($savePath) || ($result['version'] ?? null) !== static::VERSION)
                {
                    echo 'Generating ', $extName, '...', PHP_EOL;
                    if(is_dir($savePath))
                    {
                        $this->deleteDir($savePath);
                    }
                    $er->save($savePath);
                    $result['extensions'][$extName] = $version;
                }
            } catch(\ReflectionException $re) {
                echo $extName, ' not found', PHP_EOL;
            }
        }
        $result['version'] = static::VERSION;
        file_put_contents($configPath, json_encode($result, JSON_PRETTY_PRINT));
        echo 'yurunsoft/ide-helper Complete!', PHP_EOL;
    }

    /**
     * 递归删除目录及目录中所有文件
     *
     * @param string $dir
     * @return boolean
     */
    private function deleteDir($dir)
    {
        $dh = opendir($dir);
        while ($file = readdir($dh))
        {
            if('.' !== $file && '..' !== $file)
            {
                $fullpath = $dir . '/' . $file;
                if(is_dir($fullpath))
                {
                    $this->deleteDir($fullpath);
                }
                else
                {
                    unlink($fullpath);
                }
            }
        }
        closedir($dh);
        return rmdir($dir);
    }

}
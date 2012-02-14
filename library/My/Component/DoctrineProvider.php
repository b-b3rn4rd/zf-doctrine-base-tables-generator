<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * DoctrineProvider class responsible for executing doctrine cli commands using
 * Zend_Tool
 *
 * @package    Zend_Application
 * @subpackage Resource
 * @author     Bernard Baltrusaitis <bernard@runawaylover.info>
 */
class My_Component_DoctrineProvider extends Zend_Tool_Project_Provider_Abstract
implements Zend_Tool_Framework_Provider_Pretendable
{
    /**
     * Executes Doctrine cli commands
     * 
     * @param string $command doctrine command name
     * @param string $environment zend application environment
     * @param string|null $options doctrine command options
     * @return null
     */
    public function generate($command, $environment = 'development', $options = null)
    {
        $resource = $this->_getDoctrineResource($environment);
        $cli = new Doctrine_Cli($resource->getOptions());
        $cli->registerTaskClass('My_Doctrine_Task_BuildTable');
        $cli->run($this->_assembleDoctrineArguments());
    }

    /**
     * Bootstraps application and returns Doctrine Resource instnace
     * 
     * @param string $environment environment name
     * @return Zend_Application_Resource_Resource 
     */
    private function _getDoctrineResource($environment)
    {
        /* @var $projectProfile Zend_Tool_Project_Profile */
        $projectProfile = $this->_loadProfileRequired();
        
        $applicationConfigFile = $projectProfile
            ->search('ApplicationConfigFile');
        
        $applicationDirectory  = $projectProfile
            ->search('ApplicationDirectory');
        
        define('APPLICATION_PATH', 
            $applicationDirectory->getPath());
        
        $applicationOptions = array();
        $applicationOptions['config'] = $applicationConfigFile->getPath();

        $application = new Zend_Application($environment,
            $applicationOptions);
        
        $application->bootstrap();
        
        return $application->getBootstrap()
            ->getPluginResource('Doctrine');
    }
    
    /**
     * Assembles arguments for Doctrine_Cli
     * 
     * @return array array of arguments for Doctrine_Cli 
     */
    private function _assembleDoctrineArguments()
    {
        $arguments = array(
            0 => 'doctrine',
            1 => $this->_registry->getRequest()
            ->getProviderParameter('command'));
        
        if (($options = $this->_registry->getRequest()
            ->getProviderParameter('options'))) {
            $options   = explode(' ', $options);
            $arguments = array_merge($arguments, $options);
        }
        
        return $arguments;
    }    
}
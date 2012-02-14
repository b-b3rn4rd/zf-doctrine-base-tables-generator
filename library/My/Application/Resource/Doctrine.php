<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Class responsible for initialising Doctrine connection
 *
 * @package    Zend_Tool
 * @subpackage Provider
 * @author     Bernard Baltrusaitis <bernard@runawaylover.info>
 */
class My_Application_Resource_Doctrine extends Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        /* @var $manager Doctrine_Manager */
        $manager = Doctrine_Manager::getInstance();
        
        $manager->setAttribute(Doctrine_Core::ATTR_AUTOLOAD_TABLE_CLASSES, true);
        $manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING,
            Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $manager->setAttribute(Doctrine_core::ATTR_TABLE_CLASS_FORMAT, 
            'Model_Table_%s');
        
        $options     = $this->getOptions();
        $connections = $options['connections'];

        if (is_array($connections)) {
            foreach($connections as $name => $connection) {
                $manager->connection(sprintf('mysql://%s:%s@%s/%s',
                    $connection['username'],
                    $connection['password'],
                    $connection['hostname'],
                    $connection['database']
                    ), $name);
            }
        }
        
        return $manager;
    }
}

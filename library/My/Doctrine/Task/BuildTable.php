<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * BuildTable class is Doctrine_Cli task responsible for base tables generation
 * Zend_Tool
 *
 * @package    Doctrine
 * @subpackage Task
 * @author     Bernard Baltrusaitis <bernard@runawaylover.info>
 */
class My_Doctrine_Task_BuildTable extends Doctrine_Task
{
    /**
     * Task description
     * 
     * @var array 
     */
    public $description = 'Creates base table classes for the current table';
    
    /**
     * Storage of optional arguments
     * 
     * @var array 
     */
    public $optionalArguments = array(
        'depth'     => 'How deeply to build join methods',
        'uninstall' => 'True to remove tables'
    );
        
    public function __construct($dispatcher = null)
    {
        $this->setTaskName('build-tables');
        parent::__construct($dispatcher);
    }

    /**
     * Executes this task
     * 
     * @return null 
     */
    public function execute()
    {
        $depth     = $this->getArgument('depth', 3);
        $uninstall = $this->getArgument('uninstall', false);

        $generator = new My_Doctrine_Table_Generator($this->dispatcher);
        $generator->generate(array(
            'depth'     => $depth,
            'uninstall' => $uninstall
        ));
        
        if ($uninstall) {
            $this->notify('Base tables have been uninstalled');
        } else {
            $this->notify('Base tables have been generated and installed');
        }
    }
}

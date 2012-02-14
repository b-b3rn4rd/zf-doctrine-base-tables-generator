<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 * Class generates and installs or uninstalls base Tables
 *
 * @author     Ilya Sabelnikov <fruit.dev@gmail.com>
 * @author     Bernard Baltrusaitis <bernard@runawaylover.info>
 * @package    Doctrine
 * @subpackage Table
 */
class My_Doctrine_Table_Generator
{
    /**
     * Model name with classPrefix
     * 
     * @var string
     */
    protected $_modelName = null;
    
    /**
     * Model name without classPrefix
     * 
     * @var string
     */
    protected $_realModelName = null;
    
    /**
     * Storage of methods docs
     *   
     * @var array 
     */
    protected $_methodDocs = array();
    
    /**
     * Storage of methods patterns
     *  
     * @var array 
     */
    protected $_callableDocs = array();
    
    /**
     * 
     * @var array 
     */
    protected $_generateCustomPHPDoc = array();
    
    /**
     *
     * @var Doctrine_Cli 
     */
    protected $_dispatcher = null;
    
    /**
     * Storage of generator params
     * 
     * @var array 
     */
    protected $_params = array();
    
    /**
     * @var Doctrine_Table
     */
    protected $_table = null;
    
    /**
     * 
     * @param Doctrine_Cli $dispatcher 
     */
    public function __construct(Doctrine_Cli $dispatcher)
    {
        $this->_dispatcher = $dispatcher;
    }
    
    /**
     * Get dispatcher
     * 
     * @return Doctrine_Cli 
     */
    public function getDispatcher()
    {
        return $this->_dispatcher;
    }
    
    /**
     * Get model name with classPrefix
     * 
     * @return string model name 
     */
    public function getModelName()
    {
        return $this->_modelName;
    }
    
    /**
     * Get concatenated base table name
     * classPrefix.tableClassFormat.baseClassPrefix.%modelName%
     * 
     * @return string base table name
     */
    public function getFullBaseTableName()
    {
        $modelsOptions = $this->getDispatcher()
            ->getConfigValue('generate_models_options');
        
        $tableName = sprintf($modelsOptions['tableClassFormat'], 
            $modelsOptions['baseClassPrefix'] . $this->getRealModelName());
        
        return $modelsOptions['classPrefix'] . $tableName;
    }
    
    /**
     * Get concatenated table name
     * classPrefix.tableClassFormat.%modelName%
     * 
     * @return string table name 
     */
    public function getFullTableName()
    {
        $modelsOptions = $this->getDispatcher()
            ->getConfigValue('generate_models_options');
        
        $tableName = sprintf($modelsOptions['tableClassFormat'], 
            $this->getRealModelName());
        
        return $modelsOptions['classPrefix'] . $tableName;
    }    

    /**
     * Get model name without classPrefix
     * 
     * @return string model name
     */
    public function getRealModelName()
    {
        return $this->_realModelName;
    }
    
    /**
     * Get current table object
     * 
     * @return Doctrine_Table 
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Set generator params
     * 
     * @param array $params generator params
     * @return null
     */
    public function setParams(array $params)
    {
        $this->_params = array_merge(array(
                'depth'     => 2,
                'uninstall' => false,
                'minified'  => false,
            ),
            $params);
    }
    
    /**
     * Set model name with classPrefix
     * 
     * @param string $modelName model name
     * @return My_Doctrine_Table_Generator 
     */
    public function setModelName($modelName)
    {
        $this->_modelName = $modelName;
        return $this;
    }
    
    /**
     * Set model name without classPrefix
     * 
     * @param string $realModelName model name
     * @return My_Doctrine_Table_Generator 
     */
    public function setRealModelName($realModelName)
    {
        $modelsOptions = $this->getDispatcher()
            ->getConfigValue('generate_models_options');
        
        $realModelName = str_replace($modelsOptions['classPrefix'], 
                '',  $realModelName);
        
        $this->_realModelName = $realModelName;
        return $this;
    }
    
    /**
     * Set given table
     * 
     * @param Doctrine_Table $table given table
     * @return My_Doctrine_Table_Generator 
     */
    public function setTable(Doctrine_Table $table)
    {
        $this->_table = $table;
        return $this;
    }    

    /**
     * Generates classes and templates in cache.
     *
     * @param array The parameters
     * @return string The data to put in configuration cache
     */
    public function generate(array $params = null)
    {
        $modelsOptions = $this->getDispatcher()
            ->getConfigValue('generate_models_options');
        
        if (is_array($params)) {
            $this->setParams($params);
        }
        
        // create a form class for every Doctrine class
        foreach ($this->_loadModels() as $model) {
            $this->setModelName($model)
                ->setRealModelName($model) 
                ->setTable(Doctrine_Core::getTable($this->getModelName()));

            $this->_generateCustomPHPDoc = array();

            if ($this->_params['uninstall']) {
                $this->_setupTable(false);
                continue;
            }

            $baseDir  = $this->getDispatcher()
                ->getConfigValue('models_path');
            
            $baseDir .= '/Table/Base/'; 
            
            if (!is_dir($baseDir)) {
                mkdir($baseDir);
            }

            $filename = $baseDir
                . $this->getRealModelName()
                . $modelsOptions['suffix'];


            $this->_methodDocs   = array();
            $this->_callableDocs = array();
            
            $this->buildRelationPhpdocs($model, $this->_params['depth']);
            
            $content = $this->_renderTemplate($modelsOptions['baseTableTemplate']);

            file_put_contents($filename, $content);

            $this->_setupTable();
        }
    }

    /**
     * Filter out models that have disabled generation of form classes
     *
     * @return array $models Array of models to generate forms for
     */
    public function filterModels($models)
    {
        foreach ($models as $key => $model) {
            /**
             * Skip Translation tables
             */
            if (Doctrine_Core::getTable($model)->isGenerator()) {
                unset($models[$key]);

                continue;
            }
        }

        return $models;
    }

    /**
     * Converts $params array into string
     * 
     * @param array $params
     * @return string converted $params 
     */
    public function inline(array $params)
    {
        $string = '';

        foreach ($params as $k => $v) {
            $string .= ( $string == '' ? '' : ',') . "{$k}={$v}";
        }

        return $string;
    }

    /**
     * Generates phpdocs relations
     * 
     * @param string $model model name
     * @param int $depth relations depth
     * @param string $viaModel vide model name
     * @param string $aliasFrom table alias
     * @param string $alias
     * @return null 
     */
    public function buildRelationPhpdocs($model, $depth, $viaModel = '', 
        $aliasFrom = '^', $alias = '')
    {
        $modelBuilder       = null;
        $modelLevels        = 0;
        $viaModelMethodPart = '';
        $build              = array();
        
        if (!empty($viaModel)) {
            $viaModelMethodPart = sprintf('Via%s', $viaModel);
        } else {
            $modelBuilder = $model;
            $modelLevels  = $depth;
            $build        = array();
        }

        if (!empty($alias)) {
            $alias .= '_';
        }

        $levelAliases = array();
        $table = Doctrine_Core::getTable($model);

        /* @var $relation Doctrine_Relation */
        $relations = $table->getRelations();

        foreach ($relations as $relationAlias => $relation) {
            $methodPart = Doctrine_Inflector::classify($relation->getAlias());

            $firstChars = 1;

            /**
             * do not dublicate alias inside joins
             */
            do {
                $relationName = ucfirst($relation->getAlias());

                $relationName[$firstChars - 1] = strtoupper($relationName[$firstChars - 1]);

                if (!$relation->isOneToOne() && $relationAlias != 'Translation') {
                    $relationName .= 'S';
                }

                $aliasOn = $alias . strtolower(preg_replace('/[a-z]/', '', $relationName));

                $firstChars++;
            } while (array_key_exists($aliasOn, $levelAliases));

            $levelAliases[$aliasOn] = true;

            if ($table->hasTemplate('I18n')) {
                $this->_callableDocs[$m] = $this->inline(array(
                        'm'  => $m = sprintf('withLeftJoinOnTranslation%s', $viaModelMethodPart),
                        'o'  => $aliasOn,
                        'f'  => $aliasFrom,
                        'ra' => $relation->getAlias(),
                        'c'  => 'buildLeftI18n',
                    ));

                $this->_callableDocs[$m] = $this->inline(array(
                        'm'  => $m = sprintf('withInnerJoinOnTranslation%s', $viaModelMethodPart),
                        'o'  => $aliasOn,
                        'f'  => $aliasFrom,
                        'ra' => $relation->getAlias(),
                        'c'  => 'buildInnerI18n',
                    ));

                $relationPath = ltrim(str_replace('And', '.', substr($viaModelMethodPart, 3)) . '.Translation', '.');

                $this->_methodDocs['translation_joins']["withLeftJoinOnTranslation{$viaModelMethodPart}"] = array(
                    'aliasOn'      => $aliasOn,
                    'joinType'     => 'LEFT',
                    'relationPath' => $relationPath,
                );

                $this->_methodDocs['translation_joins']["withInnerJoinOnTranslation{$viaModelMethodPart}"] = array(
                    'aliasOn'      => $aliasOn,
                    'joinType'     => 'INNER',
                    'relationPath' => $relationPath,
                );

                if ($relationAlias == 'Translation') {
                    $aliasOn    .= 's';
                    $methodPart .= 's';
                }
            } elseif (
                !$relation->isOneToOne()
                &&
                ($modelLevels == $depth)
                &&
                null == $relation->offsetGet('refTable')
            ) {
                $getDql = array(
                    'o'  => "{$aliasOn}_cnt",
                    'f'  => '^',
                    'ra' => $relation->getAlias(),
                    'rf' => $relation->getForeign(),
                    'rl' => $relation->getLocal(),
                    'rc' => $relation->getClass(),
                    'ca' => sprintf('%s_count', Doctrine_Inflector::tableize($relation->getAlias())),
                );

                if ($relation->getTable()->hasTemplate('SoftDelete')) {
                    $getDql['s'] = $relation->getTable()->getTemplate('SoftDelete')->getOption('name');
                }

                /**
                 * Joins
                 */
                $this->_callableDocs[$m] = $this->inline(
                        array_merge(
                            array(
                                'm' => $m = "addSelect{$methodPart}CountAsJoin",
                                'c' => 'buildAddSelectCountAsJoin',
                            ),
                            $getDql
                        )
                );

                $this->_methodDocs['add_counts_join'][$m] = array(
                    'relationAlias'  => $getDql['o'],
                    'countFieldName' => $getDql['ca'],
                    'relationName'   => $getDql['ra'],
                    'relationColumn' => $getDql['rf'],
                );

                /**
                 * Sub-Select
                 */
                $this->_callableDocs[$m] = $this->inline(
                        array_merge(
                            array(
                                'm' => $m = "addSelect{$methodPart}CountAsSubSelect",
                                'c' => 'buildAddSelectCountAsSubSelect',
                            ),
                            $getDql
                        )
                );

                $this->_methodDocs['add_counts_subselect'][$m] = array(
                    'relationAlias'  => $getDql['o'],
                    'countFieldName' => $getDql['ca'],
                    'relationName'   => $getDql['ra'],
                    'relationColumn' => $getDql['rf'],
                );
            }

            $this->_callableDocs[$m] = $this->inline(array(
                    'm'  => $m = "withLeftJoinOn{$methodPart}{$viaModelMethodPart}",
                    'o'  => $aliasOn,
                    'f'  => $aliasFrom,
                    'ra' => $relation->getAlias(),
                    'c'  => 'buildLeft',
                ));

            $this->_callableDocs[$m] = $this->inline(array(
                    'm'  => $m = "withInnerJoinOn{$methodPart}{$viaModelMethodPart}",
                    'o'  => $aliasOn,
                    'f'  => $aliasFrom,
                    'ra' => $relation->getAlias(),
                    'c'  => 'buildInner',
                ));

            $relationPath = ltrim(str_replace('And', '.', substr($viaModelMethodPart, 3)) . ".{$methodPart}", '.');

            $this->_methodDocs['joins']["withLeftJoinOn{$methodPart}{$viaModelMethodPart}"] = array(
                'aliasOn'      => $aliasOn,
                'relationPath' => $relationPath,
                'joinType'     => 'LEFT',
            );

            $this->_methodDocs['joins']["withInnerJoinOn{$methodPart}{$viaModelMethodPart}"] = array(
                'aliasOn'      => $aliasOn,
                'relationPath' => $relationPath,
                'joinType'     => 'INNER',
            );

            # do not generate a ciclic joins
            if ($modelBuilder === $relation->getClass()) {
                continue;
            }

            # do not generate joins further than Translation table
            if ('Translation' == $relationName) {
                continue;
            }

            if (0 != $depth) {
                --$depth;

                $this->buildRelationPhpdocs(
                    $relation->getTable()->getClassnameToReturn(),
                    $depth,
                    empty($viaModel) ? $methodPart : sprintf('%sAnd%s', $viaModel, $methodPart),
                    $aliasOn,
                    $aliasOn
                );

                ++$depth;
            }
        }
    }

    /**
     * Register method by pattern for each table column
     *
     * @param string $pattern
     * @param string $buildMethod
     * @return array
     */
    public function getPHPDocByPattern($pattern, $buildMethod = null)
    {
        $columns = $this->getTable()->getColumnNames();

        if (null !== $buildMethod) {
            foreach ($columns as $columnName) {
                $m = sprintf($pattern, Doctrine_Inflector::classify($columnName));

                $this->_callableDocs[$m] = $this->inline(array(
                        'm' => $m,
                        'n' => $columnName,
                        'c' => $buildMethod,
                    ));

                $this->_generateCustomPHPDoc[$m] = true;
            }
        }

        if ($this->isMinified()) {
            return array();
        }

        return array_combine(
            $columns,
            array_map(
                function($columnName) use ($pattern, $columns) {
                    return sprintf($pattern, Doctrine_Inflector::classify($columnName));
                },
                $columns
            )
        );
    }

    /**
     * Get array of collable docs
     * 
     * @return array 
     */
    public function getCallableDocs()
    {
        $result = array();

        foreach ($this->_generateCustomPHPDoc as $method => $isUsed) {
            $result[$method] = $this->_callableDocs[$method];
        }

        return $result;
    }

    /**
     * Get phpdoc by given $category name
     * 
     * @param string $category
     * @return string phpdoc content 
     */
    public function getPHPDocByCategory($category)
    {
        if (!isset($this->_methodDocs[$category])) {
            return array();
        }

        foreach ($this->_methodDocs[$category] as $method => $params) {
            $this->_generateCustomPHPDoc[$method] = true;
        }

        if ($this->isMinified()) {
            return array();
        }

        return $this->_methodDocs[$category];
    }

    /**
     * Checks if generation is minified
     * 
     * @return boolean true if minified 
     */
    public function isMinified()
    {
        return isset($this->_params['minified']) && $this->_params['minified'];
    }
    
    /**
     * Get coolection of classes
     * 
     * @return array 
     */
    public function getCollectionClass()
    {
        return Doctrine_Manager::getInstance()
            ->getAttribute(Doctrine::ATTR_COLLECTION_CLASS);
    }
    
    /**
     * Loads all Doctrine builders.
     *
     * @return array array of models
     */
    private function _loadModels()
    {
        Doctrine_Core::loadModels($this->getDispatcher()->getConfigValue('models_path'), 
            Doctrine_Core::MODEL_LOADING_AGGRESSIVE);
        $models = Doctrine_Core::getLoadedModels();
        $models = Doctrine_Core::initializeModels($models);
        $models = Doctrine_Core::filterInvalidModels($models);

        return $this->filterModels($models);
    }
    
    /**
     * Installs or removes base table for active table
     *  
     * @param boolean $install true to install table
     * @return null 
     */
    private function _setupTable($install = true)
    {
        $modelsOptions = $this->getDispatcher()
            ->getConfigValue('generate_models_options');
        
        $modelsPath = $this->getDispatcher()
            ->getConfigValue('models_path');
        
        $filename = "{$modelsPath}/Table/{$this->getRealModelName()}{$modelsOptions['suffix']}";
        
        if (is_file($filename)) {
            $content = file_get_contents($filename);
            $content = $this->_replaceParentClass($content, $install);
            $content = $this->_replaceReturnClassName($content, $install);

            file_put_contents($filename, $content);
        }
    }
    
    /**
     * Replaces parent class in given content
     * 
     * @param string $content table content
     * @param boolean $install true to install
     * @return string table content with replaced parent class
     */
    private function _replaceParentClass($content, $install = true)
    {
        $pattern = "/class(\s+){$this->getFullTableName()}(\s+)extends(\s+)\w+/ms";
        
        if ($install) {
            $replace = "class\\1{$this->getFullTableName()}\\2extends\\3{$this->getFullBaseTableName()}";
        } else {
            $replace = "class\\1{$this->getFullTableName()}\\2extends\\3My_Doctrine_Table_Scoped";
        }
        
        $content = preg_replace($pattern, $replace, $content, 1, $count);
        
        return $content;            
    }
    
    /**
     * Replaces return object in given content
     * 
     * @param string $content table content
     * @param boolean $install true to install
     * @return string table content with replaced return object 
     */
    private function _replaceReturnClassName($content, $install = true)
    {
        if ($install) {
            $pattern = "/@return(\s+)object(\s+){$this->getFullTableName()}/ms";
            $replace = "@return\\1{$this->getFullBaseTableName()}";
        } else {
            $pattern = "/@return(\s+){$this->getFullBaseTableName()}/ms";
            $replace = "@return object\\1{$this->getFullTableName()}";
        }
        
        $content = preg_replace($pattern, $replace, $content, 1);
        
        return $content;
    }
    
    /**
     * Rendrers given template
     * 
     * @param string $filename template
     * @return string parsed template
     * @throws Exception if given template doesn't exists
     */
    private function _renderTemplate($filename)
    {
        if (!is_file($filename)) {
            throw new Exception(
                sprintf('Table template `%s` doesn\'t exists', $filename));
        }
            
        $modelsOptions = $this->getDispatcher()
            ->getConfigValue('generate_models_options');
        
        ob_start();
        require $filename;
        $content = ob_get_clean();
        
        return str_replace(array('[?php', '[?=', '?]'), 
            array('<?php', '<?php echo', '?>'), 
            $content);
    }
}
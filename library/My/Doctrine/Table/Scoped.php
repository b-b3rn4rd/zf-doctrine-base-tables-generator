<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 * This class extends Doctrine_Table class and redirects __call method based on
 * generated base table class parameters.
 *
 * @author     Ilya Sabelnikov <fruit.dev@gmail.com>
 * @author     Bernard Baltrusaitis <bernard@runawaylover.info>
 * @package    Doctrine
 * @subpackage Table
 */
class My_Doctrine_Table_Scoped extends Doctrine_Table
{
    /**
     * Reads generated base table doc and finds called method to apply additional
     * params to run the method forward
     *
     * @param string  $method     Method that was called
     * @param array   $arguments  Arguments that was passed with call
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        # Late static bindings in action
        $generatedBaseTableClass = new ReflectionClass(static::getGenericTableName());

        $phpdoc = $generatedBaseTableClass->getDocComment();

        $searchKey = "m={$method},";
        $pos = strpos($phpdoc, $searchKey);

        if (false === $pos) {
            return parent::__call($method, $arguments);
        }

        # calculation where in PHPDoc is located desired method
        $break = strpos($phpdoc, PHP_EOL, $pos);
        $searchKeyLenght = strlen($searchKey);
        $stringParams = substr(
                $phpdoc, $pos + $searchKeyLenght, $break - ($pos + $searchKeyLenght) - 1
        );

        # create handy array to work with parsed parameters
        $params = array();
        foreach (explode(',', $stringParams) as $row) {
            list($key, $value) = explode('=', $row);
            $params[$key] = $value;
        }

        # $params["c"] is a string representation of method to call
        # with parsed params
        return call_user_func_array(
            array($this, $params['c']), # callable
            array_merge(array($params), $arguments) # arguments
        );
    }

    /**
     * Add specific JOIN on Translation
     *
     * @param array           $params     List of parameters
     * @param string          $joinType   Join type: innerJoin/leftJoin
     * @param Doctrine_Query  $q          Query to apply condition
     * @param null|string     $culture    Culture lang to use in WITH clause
     *
     * @throws InvalidArgumentException
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildJoinI18n(array $params, $joinType, Doctrine_Query $q, $culture = null)
    {
        $params['f'] = $params['f'] == '^' ? $q->getRootAlias() : $params['f'];

        if (!is_string($culture)) {
            throw new InvalidArgumentException('Invalid variable $culture value');
        }

        $q->{$joinType}(sprintf(
                    "{$params['f']}.{$params['ra']} {$params['o']} WITH {$params['o']}.lang = %s",
                    $q->getConnection()->quote($culture)));

        return $this;
    }

    /**
     * Adds INNER JOIN on Translation table
     *
     * @param array           $params   List of parameters
     * @param Doctrine_Query  $q        Query to apply condition
     * @param null|string     $culture  Culture lang to use in WITH clause
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildInnerI18n(array $params, Doctrine_Query $q, $culture = null)
    {
        return $this->buildJoinI18n($params, 'innerJoin', $q, $culture);
    }

    /**
     * Adds LEFT JOIN on Translation table
     *
     * @param array           $params   List of parameters
     * @param Doctrine_Query  $q        Query to apply condition
     * @param null|string     $culture  Culture lang to use in WITH clause
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildLeftI18n(array $params, Doctrine_Query $q, $culture = null)
    {
        return $this->buildJoinI18n($params, 'leftJoin', $q, $culture);
    }

    /**
     * Adds specific JOIN to the query
     *
     * @param array           $params     List of parameters
     * @param string          $joinType   Join type: innerJoin/leftJoin
     * @param Doctrine_Query  $q          Query to apply condition
     * @param string          $with       Additional WITH expression to add to the JOIN
     * @param array           $args       List of arguments used in WITH expression
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildJoin(array $params, $joinType, Doctrine_Query $q, $with = null, 
        array $args = array())
    {
        $params['f'] = $params['f'] == '^' ? $q->getRootAlias() : $params['f'];

        $q->$joinType(
            "{$params['f']}.{$params['ra']} {$params['o']}" .
            (!$with ? '' : " WITH {$with}"),
            $args);

        return $this;
    }

    /**
     * Adds INNER JOIN clause to the query
     *
     * @param array           $params   List of parameters
     * @param Doctrine_Query  $q        Query to apply condition
     * @param string          $with     Additional WITH expression to add to the JOIN
     * @param array           $args     List of arguments used in WITH expression
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildInner(array $params, Doctrine_Query $q, $with = null, 
        array $args = array())
    {
        return $this->buildJoin($params, 'innerJoin', $q, $with, $args);
    }

    /**
     * Adds LEFT JOIN clause to the query
     *
     * @param array           $params   List of parameters
     * @param Doctrine_Query  $q        Query to apply condition
     * @param string          $with     Additional WITH expression to add to the JOIN
     * @param array           $args     List of arguments used in WITH expression
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildLeft(array $params, Doctrine_Query $q, $with = null, 
        array $args = array())
    {
        return $this->buildJoin($params, 'leftJoin', $q, $with, $args);
    }

    /**
     * Adds AND expression to the WHERE clause with params taken from parsed PHPDoc
     *
     * @param array           $params   List of parameters
     * @param Doctrine_Query  $q        Query to apply condition
     * @param scalar          $value    Argument used in expression
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildAndWhere(array $params, Doctrine_Query $q, $value)
    {
        $value = is_bool($value) ? $q->getConnection()->convertBooleans($value) : $value;

        $q->andWhere("{$q->getRootAlias()}.{$params['n']} = ?", $value);

        return $this;
    }

    /**
     * Adds AND IN expression to the WHERE clause with params taken from parsed PHPDoc
     *
     * @param array           $params   List of parameters
     * @param Doctrine_Query  $q        Query to apply condition
     * @param array           $values   List of arguments passed to condition
     * @param boolean         $not      Whether to insert NOT to the clause
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildAndWhereIn(array $params, Doctrine_Query $q, array $values, 
        $not = false)
    {
        $q->andWhereIn("{$q->getRootAlias()}.{$params['n']}", $values, $not);

        return $this;
    }

    /**
     * Adds OR expression the WHERE clause
     *
     * @param array           $params   List of parameters
     * @param Doctrine_Query  $q        Query to apply condition
     * @param scalar          $value    Argument used in expression
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildOrWhere(array $params, Doctrine_Query $q, $value)
    {
        $value = is_bool($value) ? $q->getConnection()->convertBooleans($value) : $value;

        $q->orWhere("{$q->getRootAlias()}.{$params['n']} = ?", $value);

        return $this;
    }

    /**
     * Adds OR IN expression to the WHERE clause with params taken from parsed PHPDoc
     *
     * @param array           $params   List of parameters
     * @param Doctrine_Query  $q        Query to apply condition
     * @param array           $values   List of arguments passed to condition
     * @param boolean         $not      Whether to insert NOT to the clause
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildOrWhereIn(array $params, Doctrine_Query $q, array $values, 
        $not = false)
    {
        $q->orWhereIn("{$q->getRootAlias()}.{$params['n']}", $values, $not);

        return $this;
    }

    /**
     * Adds COUNT expression as JOIN on table
     *
     * @param array           $params   List of parameters
     * @param Doctrine_Query  $q        Query to apply condition
     * @param string          $with     Additional WITH expression to add to the JOIN
     * @param array           $args     List of arguments used in WITH expression
     *
     * @throws LogicException
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildAddSelectCountAsJoin(array $params, Doctrine_Query $q, 
        $with = null, array $args = array())
    {
        if (0 < count($q->getDqlPart('groupby'))) {
            throw new LogicException(sprintf(
                    'You could not mix many GROUPBY when retrieving COUNT as a JOIN'
            ));
        }

        $this->buildLeft($params, $q, $with, $args);

        $q->addSelect("COUNT({$params['o']}.{$params['rf']}) as {$params['ca']}")
            ->addGroupBy("{$params['o']}.{$params['rf']}");

        # In case table with SoftDelete behavior
        if (isset($params['s'])) {
            $q->addWhere("{$params['o']}.{$params['s']} IS NULL");
        }

        return $this;
    }

    /**
     * Builds sub-query witch selects COUNT from a specific table
     *
     * @param array           $params     List of parameters
     * @param Doctrine_Query  $q          Query to apply condition
     * @param string          $andWhere   Additional AND expression to add to the JOIN
     * @param array           $args       List of arguments used in AND expression
     *
     * @return Doctrine_Query
     */
    protected function buildGetCountDqlAsSubSelect(array $params, Doctrine_Query $q, 
        $andWhere = null, array $args = array())
    {
        $subQuery = new Doctrine_Query($q->getConnection());
        $subQuery->isSubquery(true);

        $subQuery->addFrom("{$params['rc']} {$params['o']}")
            ->addSelect("COUNT({$params['o']}.{$params['rf']})")
            ->addWhere(
                "{$q->getRootAlias()}.{$params['rl']} = {$params['o']}.{$params['rf']}" .
                (!$andWhere ? '' : " AND {$andWhere}"),
                $args
            );

        # In case table with SoftDelete behavior
        if (isset($params['s'])) {
            $subQuery->addWhere("{$params['o']}.{$params['s']} IS NULL");
        }

        return $subQuery;
    }

    /**
     * Adds COUNT expression as sub-query
     *
     * @param array           $params   List of parameters
     * @param Doctrine_Query  $q        Query to apply condition
     * @param string          $andWhere Additional WITH expression to add to the JOIN
     * @param array           $args     List of arguments used in WITH expression
     *
     * @return Doctrine_Table_Scoped
     */
    protected function buildAddSelectCountAsSubselect(array $params, Doctrine_Query $q, 
        $andWhere = null, array $args = array())
    {
        $subQuery = $this->buildGetCountDqlAsSubselect($params, $q, $andWhere, $args);

        $q->addSelect("({$subQuery->getDql()}) AS {$params['ca']}");

        return $this;
    }

}

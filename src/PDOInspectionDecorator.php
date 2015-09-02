<?php

namespace Dnoegel\DatabaseInspection;

use PDO;

class PDOInspectionDecorator extends \PDO
{

    /**
     * @var SqlProblemInspector
     */
    private $sqlParser;

    private $innerPDO;

    public function __construct(\Pdo $pdo)
    {
        $this->innerPDO = $pdo;
    }

    /**
     * Inject the SQL problem inspector to call to inspect queryy
     * @param SqlProblemInspector $inspector
     */
    public function setProblemInspector(SqlProblemInspector $inspector)
    {
        $this->sqlParser = $inspector;
    }

    #
    # Overrides of the original PDO object
    # in order to inspect the queries
    #

    public function prepare($statement, $options = array())
    {
        $this->sqlParser->inspect($statement);
        return $this->innerPDO->prepare($statement, $options);
    }


    public function query()
    {
        // remove empty constructor params list if it exists
        $args = func_get_args();

        $this->sqlParser->inspect($args[0]);

        return call_user_func_array(array($this->innerPDO, 'query'), $args);
    }


    #
    # Overrides of the original PDO object
    # in order to "decorate" it  - no inspection here
    #

    public function beginTransaction()
    {
        return $this->innerPDO->beginTransaction();
    }

    public function commit()
    {
        return $this->innerPDO->commit();
    }

    public function rollBack()
    {
        return $this->innerPDO->rollBack();
    }

    public function inTransaction()
    {
        return $this->innerPDO->inTransaction();
    }

    public function setAttribute($attribute, $value)
    {
        $this->innerPDO->setAttribute($attribute, $value);
    }

    public function exec($statement)
    {
        return $this->innerPDO->exec($statement);
    }

    public function lastInsertId($name = null)
    {
        return $this->innerPDO->lastInsertId($name);
    }

    public function errorCode()
    {
        return $this->innerPDO->errorCode();
    }

   public function errorInfo()
    {
        return $this->innerPDO->errorInfo();
    }

    public function getAttribute($attribute)
    {
        return $this->innerPDO->getAttribute($attribute);
    }

    public function quote($string, $parameter_type = PDO::PARAM_STR)
    {
        return $this->innerPDO->quote($string, $parameter_type);
    }

    public static function getAvailableDrivers()
    {
        parent::getAvailableDrivers();
    }
}
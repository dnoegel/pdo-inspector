<?php

namespace Dnoegel\DatabaseInspection;

use PHPSQLParser\PHPSQLParser;
use Dnoegel\DatabaseInspection\RouteProvider\ServerRouteProvider;
use Dnoegel\DatabaseInspection\Trace\DebugTrace;

/**
 * Class SqlProblemInspector will parse and analyze any given SQL query.
 *
 * @package Dnoegel\DatabaseInspection
 */
class SqlProblemInspector
{
    /**
     * @var PHPSQLParser
     */
    private $sqlParser;

    private $queryWhitelist = [
    ];

    /**
     * @var Storage\Storage
     */
    private $storage;

    /**
     * @var Trace\Trace
     */
    private $traceProvider;

    private $separateIssues;

    /**
     * @var RouteProvider\RouteProvider
     */
    private $routeProvider;

    /**
     * @param Storage\Storage $storage Storage engine for storing the findings
     * @param RouteProvider\RouteProvider $routeProvider Service that provides meaningful routes for the current request
     * @param Trace\Trace $traceProvider Service that provide stack traces
     * @param bool|false $separateIssues Will separate "most probably problem" and "perhaps problem" to separate folders
     */
    public function __construct(Storage\Storage $storage, RouteProvider\RouteProvider $routeProvider = null, Trace\Trace $traceProvider = null, $separateIssues = false)
    {
        $this->sqlParser = new PHPSQLParser();

        $this->storage = $storage;
        $this->traceProvider = $traceProvider ?: new DebugTrace();
        $this->routeProvider = $routeProvider ?: new ServerRouteProvider();

        $this->separateIssues = $separateIssues;
        $this->queryWhitelist = $this->storage->listDocuments('whitelist');
    }

    /**
     * Will inspect the given $sql and write problems to the storage
     *
     * @param $sql
     */
    public function inspect($sql)
    {
        $result = $this->sqlParser->parse($sql);

        $normalizedSql = $this->getNormalizedSql($result);
        $hash = $this->getQueryHash($normalizedSql);

        if (in_array($hash, $this->queryWhitelist)) {
            return;
        }

        $problems = $this->recursivelyGetProblems($result);

        if (empty($problems)) {
            return;
        }
        
        list($code, $trace) = $this->traceProvider->getTraceData();

        $document = $this->mightBeFalsePositive($problems) ? 'issues' : 'problem';

        $this->storage->addDocument($document, $hash, [
                'route' => $this->routeProvider->getRoute(),
                'problems' => $problems,
                'code' => $code,
                'sql' => $sql,
                'trace' => $trace,
                'normalized' => $normalizedSql
            ]
        );

    }

    /**
     * Get a list of problems from the parsed sql
     *
     * @param $array
     * @return array
     */
    private function recursivelyGetProblems($array)
    {
        $problems = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $problems = array_merge($this->recursivelyGetProblems($value), $problems);
            } else {
                // we consider constant expressions as problems
                if ($key == 'expr_type' && $value == 'const') {
                    $problems[] = $array;
                }
            }
        }

        return $problems;
    }

    /**
     * If "ignoreSimpleValues" is configured and the problems array contains only numerics / null the problems will be stored
     * as "ignored".
     *
     * @param $problems
     * @return bool
     */
    private function mightBeFalsePositive($problems)
    {
        if (!$this->separateIssues) {
            return false;
        }

        $allSimple = true;
        foreach ($problems as $problem) {
            if ($problem['base_expr'] !== "NULL" && $problem['base_expr'] !== NULL && !is_numeric($problem['base_expr'])) {
                $allSimple = false;
            }
        }
        return $allSimple;
    }

    /**
     * Normalize a given parsed sql query
     *
     * @param $result
     * @return mixed
     */
    private function getNormalizedSql($result)
    {
        $normalized = $result;
        $this->recursiveNormalizeQuery($normalized);
        return $normalized;
    }


    /**
     * Normalize the query, so that the same query with other values will get the same hash.
     * Once a constant expression was detected in a certain nesting level, all "base_expr"
     * from this level to the top will be NORMALIZED, in order to remove the concrete constant
     * value from the SQL hash.
     * The internal flag "tainted" represents this information.
     *
     * @param $array
     * @return mixed
     */
    private function recursiveNormalizeQuery(&$array)
    {
        $tainted = false;

        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $tainted = $this->recursiveNormalizeQuery($value);
            } else {
                if ($key == 'expr_type' && $value == 'in-list') {
                    $tainted = true;
                    $array['sub_tree'] = 'NORMALIZED';
                } else {
                    if ($key == 'expr_type' && $value == 'const') {
                        $tainted = true;
                    }
                }
            }
        }

        if ($tainted) {
            if (array_key_exists('base_expr', $array)) {
                $array['base_expr'] = 'NORMALIZED';
            }
            if (array_key_exists('sub_tree', $array)) {
//                $array['sub_tree'] = 'NORMALIZED';
            }
        }

        return $tainted;
    }

    /**
     * @param $normalizedSql
     * @return string
     */
    private function getQueryHash($normalizedSql)
    {
        $hash = md5(json_encode($normalizedSql));
        return $hash;
    }
}
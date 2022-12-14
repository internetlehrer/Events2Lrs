<?php

namespace ILIAS\Plugins\Events2Lrs\DI\Database;

use Countable;
use ilException;
use ilDatabaseException;
use ILIAS\Plugins\Events2Lrs\DI\Container;
use ilPDOStatement;

use function array_merge;
use function array_key_exists;
use function array_keys;
use function array_unshift;
use function count;
use function func_get_args;
use function func_num_args;
use function implode;
use function is_array;
use function is_object;
use function key;
use function strtoupper;


/**
 * QueryBuilder class is responsible to dynamically create SQL queries.
 */
class QueryBuilder
{
    public const SELECT = 0;
    public const DELETE = 1;
    public const STATE_DIRTY = 0;
    public const STATE_CLEAN = 1;

    public const SQL_PARTS_DEFAULTS = [
        'select'   => [],
        'distinct' => false,
        'from'     => [],
        'join'     => [],
        'set'      => [],
        'where'    => null,
        'groupBy'  => [],
        'having'   => null,
        'orderBy'  => [],
        'values'   => [],
    ];

    /**
     * The array of SQL parts collected.
     *
     * @var array $sqlParts
     */
    public $sqlParts;

    /**
     * The complete SQL string for this query.
     *
     * @var string|null
     */
    public $sql;

    /**
     * The query parameters.
     *
     * @var list<mixed>|array<string, mixed>
     */
    public $params = [];

    /**
     * The parameter type map of this query.
     *
     * @var array<int, int|string|null>|array<string, int|string|null>
     */
    public $paramTypes = [];

    /**
     * The type of query this is. Can be select or delete.
     *
     * @var int
     */
    public $type = self::SELECT;

    /**
     * The state of the query object. Can be dirty or clean.
     *
     * @var int
     */
    public $state = self::STATE_CLEAN;

    /**
     * @return ilPDOStatement|bool|int|void
     * @throws ilException
     */
    final public function execute() : ilPDOStatement
    {
        $DIC = new Container();

        $method = $this->type ? 'manipulate' : 'query';

        return $DIC->database()->$method($this->getSQL());
    }


    /**
     * Gets the type of the currently built query.
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Gets the state of this query builder instance.
     *
     * @return int Either QueryBuilder::$STATE_DIRTY or QueryBuilder::$STATE_CLEAN.
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Executes an SQL query (SELECT) and returns a Result.
     *
     * @return ilPDOStatement
     * @throws ilDatabaseException
     * @throws ilException
     */
    /*
    public function executeQuery(): ilPDOStatement
    {
        #return $this->connection->executeQuery($this->getSQL(), $this->params, $this->paramTypes);
        return parent::query($this->getSQL());
    }
    */

    /**
     * Executes an SQL statement and returns the number of affected rows.
     *
     * Should be used for INSERT, UPDATE and DELETE
     *
     * @return int The number of affected rows.
     *
     * @throws ilException
     */
    /*
    public function executeStatement(): int
    {
        #return $this->connection->executeStatement($this->getSQL(), $this->params, $this->paramTypes);
        return parent::manipulate($this->getSQL());
    }
    */

    /**
     * Gets the complete SQL string formed by the current specifications of this QueryBuilder.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *     echo $qb->getSQL(); // SELECT u FROM User u
     * </code>
     *
     * @return string The SQL query string.
     * @throws ilException
     */
    public function getSQL(): ?string
    {
        if ($this->sql !== null && $this->state === self::STATE_CLEAN) {
            return $this->sql;
        }

        switch ($this->type) {

            case self::DELETE:
                $sql = $this->getSQLForDelete();
                break;

            case self::SELECT:
            default:
                $sql = $this->getSQLForSelect();
                break;
        }

        $this->state = self::STATE_CLEAN;
        $this->sql   = $sql;

        return $sql;
    }

    /**
     * Sets a query parameter for the query being constructed.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter('user_id', 1);
     * </code>
     *
     * @param int|string $key Parameter position or name
     * @param mixed $value Parameter value
     * @param int|string|Type|null $type Parameter type
     *
     * @return self This QueryBuilder instance.
     */
    public function setParameter($key, $value, $type = null) : self
    {
        if ($type !== null) {
            $this->paramTypes[$key] = $type;
        }

        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Sets a collection of query parameters for the query being constructed.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.id = :user_id1 OR u.id = :user_id2')
     *         ->setParameters(array(
     *             'user_id1' => 1,
     *             'user_id2' => 2
     *         ));
     * </code>
     *
     * @param list<mixed>|array<string, mixed>                                     $params Parameters to set
     * @param array<int, int|string|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return $this This QueryBuilder instance.
     */
    public function setParameters(array $params, array $types = []) : self
    {
        $this->paramTypes = $types;
        $this->params     = $params;

        return $this;
    }

    /**
     * Gets all defined query parameters for the query being constructed indexed by parameter index or name.
     *
     * @return list<mixed>|array<string, mixed> The currently defined query parameters
     */
    public function getParameters(): array
    {
        return $this->params;
    }

    /**
     * Gets a (previously set) query parameter of the query being constructed.
     *
     * @param mixed $key The key (index or name) of the bound parameter.
     *
     * @return mixed The value of the bound parameter.
     */
    public function getParameter($key) : ?string
    {
        return $this->params[$key] ?? null;
    }

    /**
     * Gets all defined query parameter types for the query being constructed indexed by parameter index or name.
     *
     * @return array<int, int|string|null>|array<string, int|string|Type|null> The currently defined
     *                                                                              query parameter types
     */
    public function getParameterTypes(): array
    {
        return $this->paramTypes;
    }

    /**
     * Gets a (previously set) query parameter type of the query being constructed.
     *
     * @param int|string $key The key of the bound parameter type
     *
     * @return int|string|null The value of the bound parameter type
     */
    public function getParameterType($key)
    {
        return $this->paramTypes[$key] ?? null;
    }

    /**
     * Either appends to or replaces a single, generic query part.
     *
     * The available parts are: 'select', 'from', 'set', 'where',
     * 'groupBy', 'having' and 'orderBy'.
     *
     * @param string $sqlPartName
     * @param mixed  $sqlPart
     * @param bool   $append
     *
     * @return $this This QueryBuilder instance.
     */
    public function add(string $sqlPartName, $sqlPart, bool $append = false): self
    {
        $isArray    = is_array($sqlPart);
        $isMultiple = is_array($this->sqlParts[$sqlPartName]);

        if ($isMultiple && ! $isArray) {
            $sqlPart = [$sqlPart];
        }

        $this->state = self::STATE_DIRTY;

        if ($append) {
            if (
                $sqlPartName === 'orderBy'
                || $sqlPartName === 'groupBy'
                || $sqlPartName === 'select'
                || $sqlPartName === 'set'
            ) {
                foreach ($sqlPart as $part) {
                    $this->sqlParts[$sqlPartName][] = $part;
                }
            } elseif ($isArray && is_array($sqlPart[key($sqlPart)])) {
                $key                                  = key($sqlPart);
                $this->sqlParts[$sqlPartName][$key][] = $sqlPart[$key];
            } elseif ($isMultiple) {
                $this->sqlParts[$sqlPartName][] = $sqlPart;
            } else {
                $this->sqlParts[$sqlPartName] = $sqlPart;
            }

            return $this;
        }

        $this->sqlParts[$sqlPartName] = $sqlPart;

        return $this;
    }

    /**
     * Adds DISTINCT to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id')
     *         ->distinct()
     *         ->from('users', 'u')
     * </code>
     *
     * @return $this This QueryBuilder instance.
     */
    public function distinct(): self
    {
        $this->sqlParts['distinct'] = true;

        return $this;
    }

    /**
     * Adds an item that is to be returned in the query result.
     *
     * USING AN ARRAY ARGUMENT IS DEPRECATED. Pass each value as an individual argument.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id')
     *         ->addSelect('p.id')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'u.id = p.user_id');
     * </code>
     *
     * @param string|string[]|null $select The selection expression. USING AN ARRAY OR NULL IS DEPRECATED.
     *                                     Pass each value as an individual argument.
     *
     * @return $this This QueryBuilder instance.
     */
    public function addSelect(?string $select = null/*, string ...$selects*/) : self
    {
        $this->type = self::SELECT;

        if ($select === null) {
            return $this;
        }

        $selects = func_get_args();

        return $this->add('select', $selects, true);
    }

    /**
     * Creates and adds a query root corresponding to the table identified by the
     * given alias, forming a cartesian product with any existing query roots.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id')
     *         ->from('users', 'u')
     * </code>
     *
     * @param string      $from  The table.
     * @param string|null $alias The alias of the table.
     *
     * @return $this This QueryBuilder instance.
     */
    public function from(string $from, ?string $alias = null): self
    {
        return $this->add('from', [
            'table' => $from,
            'alias' => $alias,
        ], true);
    }

    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->join('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function join(string $fromAlias, string $join, string $alias, ?string $condition = null) : self
    {
        return $this->innerJoin($fromAlias, $join, $alias, $condition);
    }

    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->innerJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join The table name to join.
     * @param string $alias The alias of the join table.
     * @param string|null $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function innerJoin(string $fromAlias, string $join, string $alias, ?string $condition = null) : self
    {
        return $this->add('join', [
            $fromAlias => [
                'joinType'      => 'inner',
                'joinTable'     => $join,
                'joinAlias'     => $alias,
                'joinCondition' => $condition,
            ],
        ], true);
    }

    /**
     * Creates and adds a left join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function leftJoin(string $fromAlias, string $join, string $alias, ?string $condition = null)  : self
    {
        return $this->add('join', [
            $fromAlias => [
                'joinType'      => 'left',
                'joinTable'     => $join,
                'joinAlias'     => $alias,
                'joinCondition' => $condition,
            ],
        ], true);
    }

    /**
     * Creates and adds a right join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->rightJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function rightJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->add('join', [
            $fromAlias => [
                'joinType'      => 'right',
                'joinTable'     => $join,
                'joinAlias'     => $alias,
                'joinCondition' => $condition,
            ],
        ], true);
    }

    /**
     * Sets a new value for a column in a bulk update query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->update('counters', 'c')
     *         ->set('c.value', 'c.value + 1')
     *         ->where('c.id = ?');
     * </code>
     *
     * @param string $key   The column to set.
     * @param string $value The value, expression, placeholder, etc.
     *
     * @return $this This QueryBuilder instance.
     */
    public function set($key, $value)
    {
        return $this->add('set', $key . ' = ' . $value, true);
    }

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('c.value')
     *         ->from('counters', 'c')
     *         ->where('c.id = ?');
     *
     *     // You can optionally programatically build and/or expressions
     *     $qb = $conn->createQueryBuilder();
     *
     *     $or = $qb->expr()->orx();
     *     $or->add($qb->expr()->eq('c.id', 1));
     *     $or->add($qb->expr()->eq('c.id', 2));
     *
     *     $qb->update('counters', 'c')
     *         ->set('c.value', 'c.value + 1')
     *         ->where($or);
     * </code>
     *
     * @param mixed $predicates The restriction predicates.
     *
     * @return $this This QueryBuilder instance.
     */
    public function where($predicates)
    {
        if (! (func_num_args() === 1 && $predicates instanceof CompositeExpression)) {
            $predicates = CompositeExpression::and(...func_get_args());
        }

        return $this->add('where', $predicates);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.username LIKE ?')
     *         ->andWhere('u.is_active = 1');
     * </code>
     *
     * @see where()
     *
     * @param mixed $where The query restrictions.
     *
     * @return $this This QueryBuilder instance.
     */
    public function andWhere($where)
    {
        $args  = func_get_args();
        $where = $this->getQueryPart('where');

        if ($where instanceof CompositeExpression && $where->getType() === CompositeExpression::TYPE_AND) {
            $where = $where->with(...$args);
        } else {
            array_unshift($args, $where);
            $where = CompositeExpression::and(...$args);
        }

        return $this->add('where', $where, true);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->where('u.id = 1')
     *         ->orWhere('u.id = 2');
     * </code>
     *
     * @see where()
     *
     * @param mixed $where The WHERE statement.
     *
     * @return $this This QueryBuilder instance.
     */
    public function orWhere(string $where) : self
    {
        $args  = func_get_args();
        $where = $this->getQueryPart('where');

        if ($where instanceof CompositeExpression && $where->getType() === CompositeExpression::TYPE_OR) {
            $where = $where->with(...$args);
        } else {
            array_unshift($args, $where);
            $where = CompositeExpression::or(...$args);
        }

        return $this->add('where', $where, true);
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * USING AN ARRAY ARGUMENT IS DEPRECATED. Pass each value as an individual argument.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->groupBy('u.id');
     * </code>
     *
     * @param string $groupBy The grouping expression. USING AN ARRAY IS DEPRECATED.
     *                                 Pass each value as an individual argument.
     *
     * @return $this This QueryBuilder instance.
     */
    public function groupBy(string $groupBy) : self
    {
        if (is_array($groupBy) && count($groupBy) === 0) {
            return $this;
        }

        $groupBy = is_array($groupBy) ? $groupBy : func_get_args();

        return $this->add('groupBy', $groupBy, false);
    }

    /**
     * Adds a grouping expression to the query.
     *
     * USING AN ARRAY ARGUMENT IS DEPRECATED. Pass each value as an individual argument.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->groupBy('u.lastLogin')
     *         ->addGroupBy('u.createdAt');
     * </code>
     *
     * @param string|string[] $groupBy The grouping expression. USING AN ARRAY IS DEPRECATED.
     *                                 Pass each value as an individual argument.
     *
     * @return $this This QueryBuilder instance.
     */
    public function addGroupBy($groupBy/*, string ...$groupBys*/)
    {
        if (is_array($groupBy) && count($groupBy) === 0) {
            return $this;
        }

        $groupBy = is_array($groupBy) ? $groupBy : func_get_args();

        return $this->add('groupBy', $groupBy, true);
    }

    /**
     * Specifies values for an insert query indexed by column names.
     * Replaces any previous values, if any.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->insert('users')
     *         ->values(
     *             array(
     *                 'name' => '?',
     *                 'password' => '?'
     *             )
     *         );
     * </code>
     *
     * @param mixed[] $values The values to specify for the insert query indexed by column names.
     *
     * @return $this This QueryBuilder instance.
     */
    public function values(array $values)
    {
        return $this->add('values', $values);
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return $this This QueryBuilder instance.
     */
    public function orderBy(string $sort, ?string $order = null)  : self
    {
        return $this->add('orderBy', $sort . ' ' . ($order ?? 'ASC'), false);
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return $this This QueryBuilder instance.
     */
    public function addOrderBy(string $sort, ?string $order = null) : self
    {
        return $this->add('orderBy', $sort . ' ' . ($order ?? 'ASC'), true);
    }

    /**
     * Gets a query part by its name.
     *
     * @param string $queryPartName
     *
     * @return mixed
     */
    public function getQueryPart($queryPartName)
    {
        return $this->sqlParts[$queryPartName];
    }

    /**
     * Gets all query parts.
     *
     * @return mixed[]
     */
    public function getQueryParts(): array
    {
        return $this->sqlParts;
    }

    /**
     * Resets SQL parts.
     *
     * @param string[]|null $queryPartNames
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetQueryParts(?array $queryPartNames = null) : self
    {
        if ($queryPartNames === null) {
            $queryPartNames = array_keys($this->sqlParts);
        }

        foreach ($queryPartNames as $queryPartName) {
            $this->resetQueryPart($queryPartName);
        }

        return $this;
    }

    /**
     * Resets a single SQL part.
     *
     * @param string $queryPartName
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetQueryPart(string $queryPartName) : self
    {
        $this->sqlParts[$queryPartName] = self::SQL_PARTS_DEFAULTS[$queryPartName];

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * @return string
     * @throws ilException
     */
    private function getSQLForSelect(): string
    {
        $query = 'SELECT ' . ($this->sqlParts['distinct'] ? 'DISTINCT ' : '') .
            implode(', ', $this->sqlParts['select']);

        $query .= ($this->sqlParts['from'] ? ' FROM ' . implode(', ', $this->getFromClauses()) : '')
            . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '')
            . ($this->sqlParts['groupBy'] ? ' GROUP BY ' . implode(', ', $this->sqlParts['groupBy']) : '')
            . ($this->sqlParts['having'] !== null ? ' HAVING ' . ((string) $this->sqlParts['having']) : '')
            . ($this->sqlParts['orderBy'] ? ' ORDER BY ' . implode(', ', $this->sqlParts['orderBy']) : '');

        return $query;
    }

    /**
     * @return string[]
     *
     * @throws ilException
     */
    private function getFromClauses(): array
    {
        $fromClauses  = [];
        $knownAliases = [];
        #var_dump($this->sqlParts['from']); exit;
        $this->sqlParts['from'] = !array_key_exists(0, $this->sqlParts['from']) ? [$this->sqlParts['from']] : $this->sqlParts['from'];
        // Loop through all FROM clauses
        foreach ($this->sqlParts['from'] as $from) {
            if ($from['alias'] === null) {
                $tableSql       = $from['table'];
                $tableReference = $from['table'];
            } else {
                $tableSql       = $from['table'] . ' ' . $from['alias'];
                $tableReference = $from['alias'];
            }

            $knownAliases[$tableReference] = true;

            $fromClauses[$tableReference] = $tableSql . $this->getSQLForJoins($tableReference, $knownAliases);
        }

        #$this->verifyAllAliasesAreKnown($knownAliases);

        return $fromClauses;
    }

    /**
     * @param array<string,true> $knownAliases
     *
     * @throws ilException
     */
    private function verifyAllAliasesAreKnown(array $knownAliases): void
    {
        #var_dump($this->sqlParts['join']); exit;
        foreach ($this->sqlParts['join'] as $fromAlias => $joins) {
            if (! isset($knownAliases[$fromAlias])) {
                throw new ilException("The given alias '" . $fromAlias . "' is not part of " .
                    'any FROM or JOIN clause table. The currently registered ' .
                    'aliases are: ' . implode(', ', array_keys($knownAliases)) . '.');
            }
        }
    }

    /**
     * Converts this instance into a DELETE string in SQL.
     */
    private function getSQLForDelete(): string
    {
        $table = $this->sqlParts['from']['table']
            . ($this->sqlParts['from']['alias'] ? ' ' . $this->sqlParts['from']['alias'] : '');

        return 'DELETE FROM ' . $table
            . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '');
    }

    /**
     * Gets a string representation of this QueryBuilder which corresponds to
     * the final SQL query being constructed.
     *
     * @return string The string representation of this QueryBuilder.
     * @throws ilException
     */
    public function __toString()
    {
        return $this->getSQL();
    }

    /**
     * @param string $fromAlias
     * @param array<string,true> $knownAliases
     *
     * @return string
     * @throws ilException
     */
    private function getSQLForJoins(string $fromAlias, array &$knownAliases): string
    {
        $sql = '';

        if (isset($this->sqlParts['join'][$fromAlias])) {
            foreach ($this->sqlParts['join'][$fromAlias] as $join) {
                if (array_key_exists($join['joinAlias'], $knownAliases)) {
                    throw new ilException("The given alias '" . $fromAlias . "' is not unique " .
                        'any FROM or JOIN clause table. The currently registered ' .
                        'aliases are: ' . implode(', ', array_keys($knownAliases)) . '.');
                }

                $sql .= ' ' . strtoupper($join['joinType'])
                    . ' JOIN ' . $join['joinTable'] . ' ' . $join['joinAlias'];
                if ($join['joinCondition'] !== null) {
                    $sql .= ' ON ' . $join['joinCondition'];
                }

                $knownAliases[$join['joinAlias']] = true;
            }

            foreach ($this->sqlParts['join'][$fromAlias] as $join) {
                $sql .= $this->getSQLForJoins($join['joinAlias'], $knownAliases);
            }
        }

        return $sql;
    }

    /**
     * Deep clone of all expression objects in the SQL parts.
     *
     * @return void
     */
    /*
    public function __clone()
    {
        foreach ($this->sqlParts as $part => $elements) {
            if (is_array($this->sqlParts[$part])) {
                foreach ($this->sqlParts[$part] as $idx => $element) {
                    if (! is_object($element)) {
                        continue;
                    }

                    $this->sqlParts[$part][$idx] = clone $element;
                }
            } elseif (is_object($elements)) {
                $this->sqlParts[$part] = clone $elements;
            }
        }

        foreach ($this->params as $name => $param) {
            if (! is_object($param)) {
                continue;
            }

            $this->params[$name] = clone $param;
        }
    }
    */

    public function initHelpers()
    {
        // TODO: Implement initHelpers() method.
    }

    public function nextId($table_name)
    {
        // TODO: Implement nextId() method.
    }
}





/**
 * Composite expression is responsible to build a group of similar expression.
 */
class CompositeExpression implements Countable
{
    /**
     * Constant that represents an AND composite expression.
     */
    public const TYPE_AND = 'AND';

    /**
     * Constant that represents an OR composite expression.
     */
    public const TYPE_OR = 'OR';

    /**
     * The instance type of composite expression.
     *
     * @var string
     */
    private $type;

    /**
     * Each expression part of the composite expression.
     *
     * @var self[]|string[]
     */
    private $parts = [];

    /**
     * @internal Use the and() / or() factory methods.
     *
     * @param string          $type  Instance type of composite expression.
     * @param self[]|string[] $parts Composition of expressions to be joined on composite expression.
     */
    public function __construct($type, array $parts = [])
    {
        $this->type = $type;

        $this->addMultiple($parts);

    }

    /**
     * @param self|string $part
     * @param self|string ...$parts
     */
    public static function and($part, ...$parts): self
    {
        return new self(self::TYPE_AND, array_merge([$part], $parts));
    }

    /**
     * @param self|string $part
     * @param self|string ...$parts
     */
    public static function or($part, ...$parts): self
    {
        return new self(self::TYPE_OR, array_merge([$part], $parts));
    }

    /**
     * Adds multiple parts to composite expression.
     *
     * @deprecated This class will be made immutable. Use with() instead.
     *
     * @param self[]|string[] $parts
     *
     * @return CompositeExpression
     */
    public function addMultiple(array $parts = [])
    {
        foreach ($parts as $part) {
            $this->add($part);
        }

        return $this;
    }

    /**
     * Adds an expression to composite expression.
     *
     * @deprecated This class will be made immutable. Use with() instead.
     *
     * @param mixed $part
     *
     * @return CompositeExpression
     */
    public function add($part)
    {
        if ($part === null) {
            return $this;
        }

        if ($part instanceof self && count($part) === 0) {
            return $this;
        }

        $this->parts[] = $part;

        return $this;
    }

    /**
     * Returns a new CompositeExpression with the given parts added.
     *
     * @param self|string $part
     * @param self|string ...$parts
     */
    public function with($part, ...$parts): self
    {
        $that = clone $this;

        $that->parts = array_merge($that->parts, [$part], $parts);

        return $that;
    }

    /**
     * Retrieves the amount of expressions on composite expression.
     *
     * @return int
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * Retrieves the string representation of this composite expression.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->count() === 1) {
            return (string) $this->parts[0];
        }

        return '(' . implode(') ' . $this->type . ' (', $this->parts) . ')';
    }

    /**
     * Returns the type of this composite expression (AND/OR).
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}

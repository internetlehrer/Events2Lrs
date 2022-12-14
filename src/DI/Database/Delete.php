<?php

namespace ILIAS\Plugins\Events2Lrs\DI\Database;

class Delete extends QueryBuilder
{
    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain table.
     *
     * <code>
     *     $sql = $DIC->database()
     *         ->delete('users', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter(':user_id', 1)
     *         ->getSQL;
     * </code>
     *
     * @param string|null $delete The table whose rows are subject to the deletion.
     * @param string|null $alias The table alias used in the constructed query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function __construct(?string $delete = null, ?string $alias = null)
    {
        $this->type = self::DELETE;

        if ($delete === null) {

            return $this;

        }

        return $this->add('from', [
            'table' => $delete,
            'alias' => $alias,
        ]);
    }
}
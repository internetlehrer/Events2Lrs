<?php

namespace ILIAS\Plugins\Events2Lrs\DI\Database;

class Select extends QueryBuilder
{
    /**
     * Specifies an item that is to be returned in the query result.
     * Replaces any previously specified selections, if any.
     *
     * Pass each value as an individual argument.
     *
     * <code>
     *     $sql = $DIC->database()
     *         ->select('u.id', 'p.id')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'p', 'u.id = p.user_id')
     *         ->getSQL;
     *
     *      $DIC->database()->query($sql);
     * </code>
     *
     * @param string|string[]|null $select The selection expression. USING AN ARRAY OR NULL IS DEPRECATED.
     *                                     Pass each value as an individual argument.
     *
     * @return self This QueryBuilder instance.
     */
    public function __construct(?string $select = null) {

        $this->type = self::SELECT;

        if ($select === null) {

            return $this;

        }

        $selects = func_get_args();

        return $this->add('select', $selects);

    }

}
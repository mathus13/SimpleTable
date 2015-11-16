<?php

namespace Ethereal\Db;

use Ethereal\Db\TableInterface;

interface RowInterface
{

    public function __construct($data, TableInterface $table);

    /**
     * Gets the value of table.
     *
     * @return mixed
     */
    public function getTable();

    public function save();

    /**
     * Gets the value of data.
     *
     * @return mixed
     */
    public function getData();

    public function toArray();
}

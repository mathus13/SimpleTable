<?php
/**
 * \Ethereal\Db\TableInterface
 */
namespace Ethereal\Db;

use Ethereal\Db as Db;
use Ethereal\Db\Row as Row;

interface TableInterface
{

    public function __construct(Db $db, $table = null);

    public function getTable();

    public function getConnection();

    public function select($cols = '*');

    public function insert(array $data);

    public function update(array $data, $where = array());

    public function getAll();

    public function fetchAll($sql);

    public function delete(array $where);

    public function query($sql, $bind = array());

    public function save(RowInterface $row);

    public function create($data = array());
}

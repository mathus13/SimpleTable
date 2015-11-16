<?php
/**
 * \Ethereal\Db\Table
 */
namespace Ethereal\Db;

use Ethereal\Db as Db;
use Ethereal\Db\InvalidTableException;
use Ethereal\Db\Row as Row;

/**
 * \Ethereal\Db\Table
 * Generic table class modeled with Doctrine DBAL
 * @author Shawn Barratt
 *
 */
class Table implements TableInterface
{
    protected $db;
    protected $table;
    protected $rowClass = 'Ethereal\Db\Row';

    public function __construct(Db $db, $table = null)
    {
        $this->db = $db;
        if ($table) {
            $this->table = $table;
        }
        $rows = $this->db->executeQuery('SHOW TABLES LIKE ?', array($this->table));
        if (!$rows) {
            throw new InvalidTableException("Table {$this->table} does not exist in {$this->db->getDatabase()} on {$this->db->getHost()}");
        }
    }

    /**
     * get DBAL query builder
     * @return Doctrine\DBAL\QueryBuilder
     * @see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/query-builder.html
     */
    protected function qb()
    {
        return $this->db->createQueryBuilder();
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getConnection()
    {
        return $this->db;
    }

    public function select($cols = '*')
    {
        return $this->qb()->select($cols)->from($this->table);
    }

    public function insert(array $data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update(array $data, $where = array())
    {
        $update =  $this->qb()->update($this->table);
        foreach ($data as $k => $v) {
            $update->set(
                $this->db->quoteIdentifier($k),
                $this->db->quote($v)
            );
        }
        $x = 0;
        foreach ($where as $stmt) {
            $update->where($stmt);
        }
        error_log($update);
        return $this->db->executeUpdate($update);
    }

    public function getAll()
    {
        return $this->fetchAll("select * from {$this->table}");
    }

    public function fetchAll($sql)
    {
        $rows = array();
        if (is_string($sql)) {
            $res = $this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : array();
            foreach ($res as $data) {
                $rows[] = new $this->rowClass($data, $this);
            }
        } elseif ($sql instanceof \Doctrine\DBAL\Query\QueryBuilder) {
            if ($res = $sql->execute()->fetchAll()) {
                foreach ($res as $key => $data) {
                    $rows[] = new $this->rowClass($data, $this);
                }
            }
        } else {
            throw new \Exception("Unexpected object: ".get_class($sql));
        }
        return $rows;
    }

    public function delete(array $where)
    {
        return $this->db->delete($this->table, $where);
    }

    public function query($sql, $bind = array())
    {
        $stmt = $this->db->prepare($sql);
        foreach ($bind as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        return $stmt->execute();
    }

    public function save(RowInterface $row)
    {
        $key = $this->getPrimaryKey();
        if ($row->{$key}) {
            return $this->update($row->getData, array($key => $row->{$key}));
        }
        return $this->insert($row->getData());
    }

    public function create($data = array())
    {
        $class = $this->rowClass;
        return new $class($data, $this);
    }

    protected function getPrimaryKey()
    {
        $query = $this->db->fetchAll("SHOW KEYS FROM `{$this->table}` WHERE Key_name = 'PRIMARY'");
        $row = $query;
        return $row[0]['Column_name'];
    }
}

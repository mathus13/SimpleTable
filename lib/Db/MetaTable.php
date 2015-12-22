<?php
namespace Ethereal\Db;

use Ethereal\Db\Table;
use Ramsey\Uuid\Uuid;

class MetaTable extends Table implements TableInterface
{
    protected $columns = array();

    protected function getSearchQB()
    {
        $search = $this->qb()->select('clients.*, GROUP_CONCAT(md_name,\'::\',md_value separator \'||\') AS meta_data');
        $search->from($this->table, 'clients')
            ->leftJoin('clients', $this->meta_table, 'md', 'md.ref_id = clients.id');
        return $search;
    }

    public function search(array $params)
    {
        $data = array();
        $search = $this->getSearchQB();
        foreach ($params as $k => $v) {
            switch ($k) {
                case 'term':
                    $search->where(
                        $this->qb()->expr()->orX(
                            $this->qb()->expr()->like('title', "'%{$v}%'"),
                            $this->qb()->expr()->like('md_value', "'%{$v}%'")
                        )
                    );
                    break;
                case 'id':
                case 'created_date':
                case 'created_by':
                    $search->where("clients.{$k} = :{$k}");
                    $search->setParameter(":{$k}", $v);
                    break;
                case 'title':
                    $search->where("title LIKE :title");
                    $search->setParameter(':title', "%{$v}%");
                    break;
                case 'active':
                    $search->where("active = :active");
                    $search->setParameter(':active', "$v");
                    break;
                default:
                    $search->where(
                        $this->qb()->expr()->andX(
                            $this->qb()->expr()->eq('md_name', "'$k'"),
                            $this->qb()->expr()->like('md_value', "'%{$v}%'")
                        )
                    );
            }
        }
        $search->groupBy('clients.id');
        $search->orderBy('created_date');
        return $this->fetchAll($search);
    }

    public function getAllActive()
    {
        return $this->search(array('active' => 1));
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function find($id)
    {
        $rows = $this->search(array($this->getPrimaryKey() => $id));
        if (count($rows) == 0) {
            return false;
        }
        return $rows[0];
    }

    public function save(\Ethereal\Db\RowInterface $row)
    {
        $core = array();
        $meta = array();
        foreach ($row->getData() as $k => $v) {
            if ($k == 'meta_data') {
                continue;
            } elseif (in_array($k, $this->getColumns())) {
                $core[$k] = $v;
            } else {
                $meta[$k] = $v;
            }
        }
        $key = $this->getPrimaryKey();
        if (isset($core[$key])) {
            $this->updateMetadata($row->{$key}, $meta);
            return $this->update($core, array("{$key} = {$this->db->quote($row->{$key})}"));
        }
        $core[$key] = $id = \Ramsey\Uuid\Uuid::uuid4();
        $core['created_date'] = date('Y-m-d H:i:s');
        $this->insert($core);
        $this->updateMetadata($id, $meta);
        $row->{$key} = $id;
        return $id;
    }

    private function updateMetadata($id, $meta)
    {
        $this->db->delete($this->meta_table, array('ref_id' => $id));
        if (empty($meta)) {
            return;
        }
        $data = array();
        $sql = "INSERT INTO {$this->meta_table} (ref_id, md_name, md_value) VALUES";
        $x = 0;
        foreach ($meta as $key => $val) {
            if ($x > 0) {
                $sql .= ',';
            }
            $sql .= " (:ref{$x}, :name{$x}, :value{$x})";
            $data[":ref{$x}"] = $id;
            $data[":name{$x}"] = $key;
            $data[":value{$x}"] = $val;
            $x++;
        }
        $this->db->prepare($sql)->execute($data);
    }  
}

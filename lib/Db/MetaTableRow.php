<?php

namespace Ethereal\Db;

use Ethereal\Db\MetaTable;
use Ethereal\Db\Row;
use Ethereal\Db\RowInterface;

class MetaTableRow extends Row implements RowInterface
{

    public function __construct($data, \Ethereal\Db\TableInterface $table)
    {
        if (!$table instanceof MetaTable) {
            throw new Exception('Table must be of type Metatable');
        }
        foreach (explode('||', $data['meta_data']) as $meta) {
            if (strpos($meta, '::') === false) {
                continue;
            }
            list($key, $value) = explode('::', $meta);
            if (in_array($key, $table->getColumns())) {
                continue;
            }
            $data[$key] = $value;
        }
        unset($data['meta_data']);
        parent::__construct($data, $table);
    }
}

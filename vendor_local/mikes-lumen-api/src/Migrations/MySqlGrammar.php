<?php

namespace MikesLumenApi\Migrations;

use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlGrammarBase;
use Illuminate\Support\Fluent;

class MySqlGrammar extends MySqlGrammarBase
{
    public static function typeBinaryUuid(Fluent $column)
    {
        return "binary({$column->length})";
    }

    protected function getDoctrineColumnType($type)
    {
        $type = strtolower($type);
        if ($type === 'binaryuuid') {
            return \Doctrine\DBAL\Types\Type::getType('binary');
        }

        return parent::getDoctrineColumnType($type);
    }
}

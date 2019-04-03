<?php

namespace MikesLumenApi\Migrations;

use DB;

trait MigrationTrait
{

    /**
     * @param $table
     * @param $column
     * @param string $after
     *
     * @deprecated
     */
    public function addUuidField($table, $column, $after = '')
    {
        if ($column == 'id') {
            DB::statement("ALTER TABLE {$table} ADD {$column} BINARY(16) FIRST");
            DB::statement("ALTER TABLE {$table} ADD PRIMARY KEY ({$column})");
        } else {
            if ($after != '') {
                DB::statement("ALTER TABLE {$table} ADD {$column} BINARY(16) AFTER {$after}");
            } else {
                DB::statement("ALTER TABLE {$table} ADD {$column} BINARY(16)");
            }
        }
    }

    /**
     * @param $table
     * @param $translateTable
     * @param $column
     * @param string $options
     *
     * @deprecated
     */
    public function relateTranslationTable($table, $translateTable, $column, $options = 'ON DELETE CASCADE')
    {
        DB::statement("ALTER TABLE {$translateTable} MODIFY {$column} BINARY(16) NOT NULL");
        DB::statement("ALTER TABLE {$translateTable} ADD CONSTRAINT UNIQUE ({$column}, locale)");
        DB::statement("ALTER TABLE {$translateTable} ADD FOREIGN KEY fk__{$table}__{$translateTable} ({$column}) REFERENCES {$table}(id) {$options}");
    }

    /**
     * @param $table
     * @param $relationTable
     * @param $column
     * @param string $options
     *
     * @deprecated
     */
    public function relateTable($table, $relationTable, $column, $options = 'ON DELETE CASCADE')
    {
        DB::statement("ALTER TABLE {$table} MODIFY {$column} BINARY(16) NOT NULL");
        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT fk__{$table}__{$relationTable} FOREIGN KEY fk__{$table}__{$relationTable} ({$column}) REFERENCES {$relationTable}(id) {$options}");
    }

    /**
     * Deprecated
     */
    public function getCustomerSchema()
    {
        return $this->getCustomSchema();
    }

    /**
     * @return \Illuminate\Database\Schema\Builder
     */
    public function getCustomSchema()
    {
        $connection = DB::connection();
        $connection->setSchemaGrammar(new MySqlGrammar());
        $schema = $connection->getSchemaBuilder();

        $schema->blueprintResolver(function ($table, $callback) {
            return new Blueprint($table, $callback);
        });

        return $schema;
    }

    public function addIndex($table, $column)
    {
        DB::statement("ALTER TABLE {$table} ADD INDEX ix__{$table}__${column} ({$column})");
    }

    public function addUniqueConstraint($table, $column)
    {
        DB::statement("ALTER TABLE {$table} ADD UNIQUE ux__{$table}__${column} ({$column})");
    }
}

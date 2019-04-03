<?php

namespace MikesLumenApi\Migrations;

use Illuminate\Database\Schema\Blueprint as BlueprintBase;

class Blueprint extends BlueprintBase
{
    /**
     * @return \Illuminate\Support\Fluent
     */
    public function id()
    {
        $ret = $this->addColumn("binaryUuid", "id", ['length' => 16, 'fixed' => true]);
        $this->primary('id');
        return $ret;
    }

    /**
     * @param string $column column name
     * @return \Illuminate\Support\Fluent
     */
    public function binaryUuid(string $column)
    {
        return $this->addColumn("binaryUuid", $column, ['length' => 16, 'fixed' => true]);
    }

    public function relateTable($columns, $table, $keyName = null)
    {
        $keyName = $keyName ?? "fk__{$this->getTable()}__{$table}";
        return parent::foreign($columns, $keyName)->references('id')->on($table)->onDelete('cascade');
    }

    public function unrelateTable($columns, $table, $keyName = null, $isDropIndex = true)
    {
        $keyName = $keyName ?? "fk__{$this->getTable()}__{$table}";

        $output = $this->dropForeign($keyName);
        if ($isDropIndex) {
            $output = $this->dropIndex($keyName);
        }
        return $output;
    }

    public function unique($columns, $name = null, $algorithm = null)
    {
        if ($name === null) {
            if (is_array($columns)) {
                $columnName = implode('__', $columns);
            } else {
                $columnName = $columns;
            }
            $name = "ux__{$this->getTable()}__{$columnName}";
        }
        return parent::unique($columns, $name, $algorithm);
    }

    public function index($columns, $name = null, $algorithm = null)
    {
        if ($name === null) {
            if (is_array($columns)) {
                $columnName = implode('__', $columns);
            } else {
                $columnName = $columns;
            }
            $name = "ix__{$this->getTable()}__{$columnName}";
        }
        return parent::index($columns, $name, $algorithm);
    }

    public function translations($column, $table, $uxName = null, $fkName = null)
    {
        $this->binaryUuid($column);
        $this->string('locale', 16)->index();
        if ($uxName === null) {
            $this->unique([$column, 'locale']);
        } else {
            $this->unique([$column, 'locale'], $uxName);
        }
        if ($fkName === null) {
            $keyName = "fk__{$table}__{$this->getTable()}";
        } else {
            $keyName = $fkName;
        }
        parent::foreign($column, $keyName)->references('id')->on($table)->onDelete('cascade');
    }

    /**
     * @return void
     */
    public function audits()
    {
        $this->binaryUuid('created_by')->after('created_at');
        $this->binaryUuid('updated_by')->after('updated_at');
        $this->binaryUuid('approved_by')->after('updated_by')->nullable();
        $this->tinyInteger('approval_status')->after('approved_by')->nullable();
    }

    /**
     * @return void
     */
    public function dropAudits()
    {
        $this->dropColumn(['created_by', 'updated_by', 'approved_by', 'approval_status']);
    }
}

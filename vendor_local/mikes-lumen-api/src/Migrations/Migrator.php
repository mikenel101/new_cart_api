<?php

namespace MikesLumenApi\Migrations;

use Illuminate\Database\Migrations\Migrator as LaravelMigrator;

class Migrator extends LaravelMigrator
{
    const SKIP_MIGRATION = 'SKIP_MIGRATION';

    /**
     * Run "up" a migration instance.
     *
     * @param  string  $file
     * @param  int     $batch
     * @param  bool    $pretend
     * @return void
     */
    protected function runUp($file, $batch, $pretend)
    {
        $file = $this->getMigrationName($file);

        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolve($file);

        if ($pretend) {
            return $this->pretendToRun($migration, 'up');
        }

        $this->runMigration($migration, 'up');

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        if ($this->migrationOutput != self::SKIP_MIGRATION) {
            $this->repository->log($file, $batch);
            $this->note("<info>Migrated:</info> {$file}");
        }
    }

    /**
     * Run a migration inside a transaction if the database supports it.
     *
     * @param  object  $migration
     * @param  string  $method
     * @return void
     */
    protected function runMigration($migration, $method)
    {
        $name = $migration->getConnection();
        $connection = $this->resolveConnection($name);
        $self = $this;
        $callback = function () use ($migration, $method, $self) {
            $self->migrationOutput = $migration->$method();
        };
        $grammar = $this->getSchemaGrammar($connection);
        $grammar->supportsSchemaTransactions()
                    ? $connection->transaction($callback)
                    : $callback();
    }
}

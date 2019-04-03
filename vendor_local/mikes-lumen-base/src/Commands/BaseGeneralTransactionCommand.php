<?php

namespace MikesLumenBase\Commands;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use MikesLumenBase\Utils\Consumer;

class BaseGeneralTransactionCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:transaction {action}';

    /**
     * @var string
     */
    protected $description = 'Operations for general transactions: "app:transaction listen" listens repair events published on transaction failed. "app:transaction stats" shows the number of failed records in transactions. "app:transaction repair" repairs the failed records for limited number of records.';

    /**
     * @var string
     */
    protected $name;

    /**
     * The name of the publisher servce.
     *
     * @var string
     */
    protected $publisher = 'general';

    /**
     * @var array
     */
    protected $transactions;


    /**
     * BaseConsumeCommand constructor.
     */
    public function __construct()
    {
        if (!$this->name) {
            $this->name = getenv('APP_NAME', self::class);
        }

        $this->container = new Container;

        parent::__construct();
    }

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'listen':
                $this->listen();
                break;
            case 'stats':
                $this->stats();
                break;
            case 'repair':
                $this->repair();
                break;
            default:
                $this->error('Please specify an action.');
                break;
        }
    }

    protected function listen()
    {
        $listen = [];
        foreach ($this->transactions as $transaction) {
            $key = app($transaction)->getRepairEventKey();
            $listen[$key] = $transaction . '@onRepairEvent';
        }

        $consumer = new Consumer($this->name, $this->publisher, $listen);
        $consumer->handle();
    }

    protected function getTransactionName($transaction)
    {
        $tmp = explode('\\', $transaction);
        return end($tmp);
    }

    protected function stats()
    {
        foreach ($this->transactions as $transaction) {
            $staleCount = app($transaction)->staleCount();
            \Log::info('[' . $this->getTransactionName($transaction) . '] staleCount=' . $staleCount);
        }
    }

    protected function repair()
    {
        foreach ($this->transactions as $transaction) {
            $staleCount = app($transaction)->staleCount();
            $repairedCount = app($transaction)->repairAll();
            \Log::info('[' . $this->getTransactionName($transaction) . '] repairedCount=' . $repairedCount . ' staleCount=' . $staleCount);
        }
    }
}

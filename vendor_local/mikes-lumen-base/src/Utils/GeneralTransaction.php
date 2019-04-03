<?php

namespace MikesLumenBase\Utils;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use MikesLumenRepository\Helpers\UuidHelper;

class GeneralTransaction
{
    const ROLLFORWARD = 'rollforward';
    const DO_NOTHING = 'do_nothing';
    const QUERY_LIMIT = 100;
    const STALE_SECONDS = 300;
    const PUBLISH_DELAY_DEFAULT = 60;

    protected $name;
    protected $statusField = 'status';
    protected $statusBefore;
    protected $statusProcessing;
    protected $statusAfter;
    protected $updatedAtField = 'updated_at';

    protected $publisher = 'general';

    /**
     * BaseConsumeCommand constructor.
     */
    public function __construct()
    {
        if (!$this->name) {
            throw new \Exception("Please specify name");
        }
        if (!isset($this->statusBefore)) {
            throw new \Exception("Please specify statusBefore");
        }
        if (!isset($this->statusProcessing)) {
            throw new \Exception("Please specify statusProcessing");
        }
        if (!isset($this->statusAfter)) {
            throw new \Exception("Please specify statusAfter");
        }
        if (!$this->model()) {
            throw new \Exception("Please specify model()");
        }
    }

    protected function model()
    {
        return null;
    }

    /**
     * TODO for english comment
     * トランザクション処理を実行する。
     * 処理が始まると、 $model に対して、 $statusField で指定したフィールドの値を $statusProcessing に変更する。
     * 処理が正常に終了するとその値を $statusAfter に変更する。
     *
     * トランザクション処理で例外が発生すると、復旧処理(downメソッド)がメッセージングを通して呼ばれる。
     * 復旧処理は指数関数的な間隔をおいて復旧が完了するまで何回も呼ばれる。
     * $context を変更すると、復旧処理においてその変更が反映される。
     * ただし、メッセージング自体が機能していないと、$contextは初期化される。
     *
     * トランザクション処理で例外を内部で補足して明示的に返すと、復旧処理は行われずにステータスをもとに戻す。
     * これはバリデーションエラーをupメソッド内で行うときなどに利用される。
     *
     * @param  Model  &$model    トランザクションのステータスを記録するモデル
     * @param  array  &$context  復旧処理に渡せる情報
     * @return void | \Throwable 例外を明示的に返すと、復旧処理は行われずにステータスをもとに戻す
     */
    protected function up(Model &$model, array &$context)
    {
    }

   /**
     * TODO for english comment
     * 復旧処理を実行する。
     * トランザクション処理(upメソッド)で例外が発生すると、メッセージングを通してこの処理が呼ばれる。
     *
     * 復旧処理は指数関数的な間隔をおいて復旧が完了するまで何回も呼ばれる。
     * したがって、レコードが何度も作成され続けるというようなことを避けるため、
     * 何回も呼ばれる可能性があるので内部の個々の処理の冪等性(処理を繰り返しても結果が同じになること)を必ず確認する。
     *
     * このメソッドをオーバーライドしない場合は、復旧処理が行われず、$model のステータス($statusField)も $statusProcessing のままになる。
     * この場合は、復旧およびステータスの変更は手動で行うことになる。
     *
     * このメソッドで何も返さない場合は、復旧処理後のステータスは $statusBefore に変更される (ロールバック)。
     * このメソッドでROLLFORWARDを返す場合は、復旧処理後のステータスは $statusAfter に変更される (ロールフォワード )。
     *
     * $contextはトランザクション処理から渡される情報で、復旧処理内でさらに変更可能で、復旧処理が失敗した場合に次の復旧処理に渡される。
     *
     * @param  Model  &$model    トランザクションのステータスを記録するモデル
     * @param  array  &$context  復旧処理に渡せる情報
     * @return void | string     復旧処理のパタン
     *
     */
    protected function down(Model &$model, array &$context)
    {
        return self::DO_NOTHING;
    }

    protected function getPublishDelay()
    {
        return getenv('AMQP_PUBLISH_DELAY_DEFAULT') ? getenv('AMQP_PUBLISH_DELAY_DEFAULT') : self::PUBLISH_DELAY_DEFAULT;
    }

    public function commit(Model &$model, ?array $input = null)
    {
        $originalStatus = $model[$this->statusField];
        $this->updateStatus($model, $this->statusProcessing);

        $context = [];
        if ($input) {
            $context['input'] = $input;
        }

        $norescue = false;
        try {
            $err = $this->up($model, $context);
            if ($err) {
                $norescue = true;
                throw $err;
            }

            $this->updateStatus($model, $this->statusAfter);
        } catch (\Throwable $e) {
            unset($context['input']);
            if ($norescue) {
                try {
                    $this->updateStatus($model, $originalStatus);
                } catch (\Throwable $e) {
                    app('publisher')->publish($this->getRepairEventKey(), [
                        'id' => UuidHelper::toHex($model->id),
                        'context' => $context
                    ], ['delay' => $this->getPublishDelay()]);
                    throw $e;
                }
                throw $e;
            }
            app('publisher')->publish($this->getRepairEventKey(), [
                'id' => UuidHelper::toHex($model->id),
                'context' => $context
            ], ['delay' => $this->getPublishDelay()]);
            throw $e;
        }
    }

    protected function input($context)
    {
        return isset($context['input']) ? $context['input'] : [];
    }

    public function getRepairEventKey()
    {
        return "{$this->publisher}.{$this->name}.repair";
    }

    protected function updateStatus(Model &$model, $status)
    {
        if ($model[$this->statusField] != $status) {
            $model->fill([$this->statusField => $status]);
            $model->save();
        }
    }

    public function onRepairEvent(array &$payload)
    {
        $id = $payload['id'];
        $context = &$payload['context'];

        $model = $this->model()::find($id, ['*']);
        if (!$model || !$this->isProcessing($model)) {
            return;
        }

        $this->repair($model, $context);
    }

    public function repair(Model &$model, array &$context)
    {
        $repairMode = $this->down($model, $context);
        if ($repairMode == self::DO_NOTHING) {
            return;
        } elseif ($repairMode == self::ROLLFORWARD) {
            $this->updateStatus($model, $this->statusAfter);
        } else {
            $this->updateStatus($model, $this->statusBefore);
        }
    }

    protected function isProcessing(Model $model)
    {
        return $model[$this->statusField] == $this->statusProcessing;
    }

    public function repairAll()
    {
        $models = app($this->model())->newQuery()
            ->where($this->statusField, $this->statusProcessing)
            ->where($this->updatedAtField, '<', DB::raw("FROM_UNIXTIME({$this->getStaleAt()})"))
            ->take(self::QUERY_LIMIT)
            ->get();

        foreach ($models as $model) {
            \Log::info('[Reparing ' . $this->getModelName() . '] id=' . $model->getUuidAttribute('id'));
            $context = [];
            $this->repair($model, $context);
        }

        return count($models);
    }

    protected function getModelName()
    {
        $tmp = explode('\\', $this->model());
        return end($tmp);
    }

    public function staleCount()
    {
        return app($this->model())->newQuery()
            ->where($this->statusField, $this->statusProcessing)
            ->where($this->updatedAtField, '<', DB::raw("FROM_UNIXTIME({$this->getStaleAt()})"))
            ->count();
    }

    protected function getStaleAt()
    {
        return time() - self::STALE_SECONDS;
    }
}

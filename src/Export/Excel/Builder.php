<?php

namespace QT\Foundation\Export\Excel;

use QT\Foundation\Model;
use QT\GraphQL\Resolver;
use Box\Spout\Common\Type;
use Illuminate\Support\Arr;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

class Builder
{
    /**
     * 选中的要导出字段
     *
     * @var array
     */
    protected $selectedColumns = [];

    /**
     * resolver中select的字段
     * [
     *     'id' => true,
     *     'name' => true,
     *     'relation' => [
     *         'id' => true,
     *         'foreign_id' => true,
     *     ]
     * ]
     *
     * @var array
     */
    protected $selection = [];

    /**
     * 上报间隔
     *
     * @var int
     */
    protected $interval = 3;

    /**
     * 执行总数
     *
     * @var int
     */
    protected $count = 0;

    /**
     * 上次上报进度时间
     *
     * @var int
     */
    protected $reportAt = 0;

    /**
     * 默认加载的scope
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * Builder Construct
     *
     * @param array $selectedColumns
     * @param array $exportColumns
     * @param array $aliasColumns
     * @param array $filters
     */
    public function __construct(
        array $selectedColumns,
        array $exportColumns,
        array $aliasColumns = [],
        protected array $filters = []
    ) {
        foreach ($exportColumns as $column => $name) {
            Arr::set($this->exportColumns, $column, $name);
        }

        foreach ($selectedColumns as $column) {
            // 检查字段是否在可导出范围内
            if (!Arr::has($this->exportColumns, $column)) {
                continue;
            }
            // 添加select字段
            Arr::set($this->selection, $column, true);

            $name = Arr::get($this->exportColumns, $column);
            // 优先使用前端传递的别名
            $this->selectedColumns[$column] = Arr::get($aliasColumns, $column, $name);
        }
    }

    public function export(Resolver $resolver)
    {
        $path   = tempnam('/tmp', 'export-');
        $writer = WriterFactory::createFromType(Type::XLSX);
        $model  = $resolver->getModelQuery()->getModel();

        try {
            $writer->openToFile($path);
            $writer->addRow($this->wrap($this->buildColumnName()));

            // 获取format的回调与字典
            $handlers = $model instanceof Model ? $model->getExportHandler() : [];

            foreach ($this->getExportData($resolver) as $data) {
                $writer->addRow($this->wrap($this->formatRow($data, $handlers)));
            }

            $writer->close();
        } catch (\Throwable$e) {
            // 防止excel生成失败时没有清理残留文件
            if (file_exists($path)) {
                @unlink($path);
            }

            throw $e;
        }

        return $path;
    }

    /**
     * 生成表头
     *
     * @return array
     */
    protected function buildColumnName(): array
    {
        return $this->selectedColumns;
    }

    /**
     * 获取导出数据
     *
     * @return \Iterable
     */
    protected function getExportData(Resolver $resolver, $limit = 1000, $offset = 0)
    {
        $query = $resolver->generateSql($this->selection, $this->filters);

        do {
            $models = (clone $query)->forPage(++$offset, $limit)->get();

            foreach ($models as $model) {
                yield $model;
            }

            $this->reportGenerateProgress($models->count());
        } while ($models->isNotEmpty());
    }

    /**
     * 格式化行数据
     *
     * @param Model|array $data 数据源
     * @param array $handlers format回调or字典
     * @return array
     */
    protected function formatRow($data, $handlers)
    {
        if ($data instanceof Model) {
            $data = $data->setExportHandler($handlers)
                ->formatData($this->selection);
        }

        $row = [];
        foreach ($this->selectedColumns as $column) {
            $row[] = Arr::get($data, $column);
        }

        return $row;
    }

    /**
     * 上报导出进度
     *
     * @param int @progress
     */
    protected function reportGenerateProgress($progress)
    {
        // 缓存当前新增进度
        $this->count += $progress;
        // 检查离上次上报时间的间隔
        if ($this->reportAt === 0 || time() > $this->reportAt + $this->interval) {
            // TODO 触发事件
            // emit('progress')

            $this->count    = 0;
            $this->reportAt = time();
        }
    }

    /**
     * 包装成Excel Row
     *
     * @param array $row
     */
    private function wrap(array $row): Row
    {
        return WriterEntityFactory::createRowFromArray($row);
    }
}

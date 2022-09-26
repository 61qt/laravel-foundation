<?php

namespace QT\Foundation\Export;

use Iterator;
use Throwable;
use QT\Foundation\Model;
use QT\GraphQL\Resolver;
use Box\Spout\Common\Type;
use Illuminate\Support\Arr;
use Box\Spout\Common\Entity\Row;
use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Options\CursorOption;
use Box\Spout\Writer\WriterInterface;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

/**
 * Excel生成器
 * 
 * @package QT\Foundation\Export
 */
class ExcelGenerator
{
    /**
     * Excel Writer
     *
     * @var WriterInterface
     */
    protected $writer;

    /**
     * 文件临时储存路径
     *
     * @var string
     */
    protected $temporaryPath;

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
     * 选中的要导出字段
     *
     * @var array
     */
    protected $selectedColumns = [];

    /**
     * 导出时页码
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * 一次性查询数量
     *
     * @var int
     */
    protected $limit = 1000;

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
     * 进度上报回调
     *
     * @var callable
     */
    protected $reportCallback;

    /**
     * ExcelGenerator Construct
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
        protected array $filters = [],
        protected array $orderBy = []
    ) {
        $columns = [];
        foreach ($exportColumns as $column => $name) {
            Arr::set($columns, $column, $name);
        }

        foreach ($selectedColumns as $column) {
            // 检查字段是否在可导出范围内
            if (!Arr::has($columns, $column)) {
                continue;
            }
            // 添加select字段
            Arr::set($this->selection, $column, true);

            $name = Arr::get($columns, $column);
            // 优先使用前端传递的别名
            $this->selectedColumns[$column] = Arr::get($aliasColumns, $column, $name);
        }
    }

    /**
     * 设置Excel写入类
     *
     * @param WriterInterface $writer
     */
    public function setWriter(WriterInterface $writer)
    {
        $this->writer = $writer;
    }

    /**
     * 获取Excel写入类
     *
     * @return WriterInterface
     */
    public function getWriter(): WriterInterface
    {
        if (!empty($this->writer)) {
            return $this->writer;
        }

        return $this->writer = WriterFactory::createFromType(Type::XLSX);
    }

    /**
     * 获取临时文件目录
     *
     * @return string
     */
    public function getTemporaryPath(): string
    {
        if (!empty($this->temporaryPath)) {
            return $this->temporaryPath;
        }

        return $this->temporaryPath = tempnam('/tmp', 'export-');
    }

    /**
     * 从Resolver导出数据
     *
     * @param Resolver $resolver
     * @param Context $context
     * @return string
     */
    public function export(Resolver $resolver, Context $context): string
    {
        $path   = $this->getTemporaryPath();
        $model  = $resolver->getModelQuery()->getModel();
        $writer = $this->getWriter();

        try {
            $writer->openToFile($path);

            $this->buildColumnName($writer);
            // 获取format的回调与字典
            $handlers = $model instanceof Model ? $model->getExportHandler() : [];

            foreach ($this->getExportData($resolver, $context) as $data) {
                $writer->addRow($this->wrap($this->formatRow($data, $handlers)));

                $this->reportGenerateProgress(1);
            }

            $writer->close();
        } catch (Throwable $e) {
            // 防止excel生成失败时没有清理残留文件
            if (file_exists($path)) {
                @unlink($path);
            }

            throw $e;
        }

        return $path;
    }

    /**
     * 生成首行表头
     *
     * @param WriterInterface $writer
     * @return void
     */
    protected function buildColumnName(WriterInterface $writer)
    {
        $writer->addRow($this->wrap($this->selectedColumns));
    }

    /**
     * 获取导出数据
     *
     * @param Resolver $resolver
     * @param Context $context
     * @return Iterator
     */
    protected function getExportData(Resolver $resolver, Context $context): Iterator
    {
        return $resolver->export($context, $this->getExportOption(), $this->selection);
    }

    /**
     * 获取导出选项
     *
     * @return CursorOption
     */
    protected function getExportOption(): CursorOption
    {
        return new CursorOption([
            'filters' => $this->filters,
            'limit'   => $this->limit,
            'offset'  => $this->offset,
            'orderBy' => $this->orderBy,
        ]);
    }

    /**
     * 格式化行数据
     *
     * @param Model|array $data 数据源
     * @param array $handlers format回调or字典
     * @return array
     */
    protected function formatRow($data, $handlers): array
    {
        if ($data instanceof Model) {
            $data = $data->setExportHandler($handlers)
                ->formatData($this->selection);
        }

        $row = [];
        foreach (array_keys($this->selectedColumns) as $column) {
            $row[] = Arr::get($data, $column);
        }

        return $row;
    }

    /**
     * 注册任务进度上报事件
     *
     * @param callable $reportCallback
     */
    public function registerReportProgressCallback(callable $reportCallback)
    {
        $this->reportCallback = $reportCallback;

        return $this;
    }

    /**
     * 上报导出进度
     *
     * @param integer $progress
     * @return void
     */
    protected function reportGenerateProgress(int $progress)
    {
        // 缓存当前新增进度
        $this->count += $progress;
        // 检查离上次上报时间的间隔
        if (time() >= $this->reportAt && !empty($this->reportCallback)) {
            call_user_func($this->reportCallback, $this->count);

            $this->count    = 0;
            $this->reportAt = time() + $this->interval;
        }
    }

    /**
     * 包装成Excel Row
     *
     * @param array $row
     */
    protected function wrap(array $row): Row
    {
        return WriterEntityFactory::createRowFromArray($row);
    }
}

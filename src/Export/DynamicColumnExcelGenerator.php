<?php

namespace App\Utils\Export;

use Iterator;
use QT\GraphQL\Resolver;
use Illuminate\Support\Arr;
use QT\GraphQL\Contracts\Context;
use QT\Foundation\Export\ExcelGenerator;

abstract class DynamicColumnExcelGenerator extends ExcelGenerator
{
    /**
     * 动态列
     * 把Type的getExportColumns内对应的字段替换掉
     * 从一个字段变为多个字段,比如导出考试,每一场考试参加考试的科目都不一致
     * 这时候把动态列变为多个真正列,保证根据入参的不同导出的列也不一致
     *
     * @var string
     */
    protected $dynamicColumn;

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
        array $filters = []
    ) {
        $offset = array_search($this->dynamicColumn, $selectedColumns);
        // 检查是否有选中动态列导出,如果有,就把动态列展开
        if ($offset !== false) {
            $columns = [];
            foreach ($this->expandDynamicColumns($filters) as $column => $displayName) {
                array_push($columns, $column);

                $exportColumns[$column] = $displayName;
            }

            $selectedColumns = array_insert($selectedColumns, $offset, $columns);
        }

        parent::__construct($selectedColumns, $exportColumns, $aliasColumns, $filters);
    }

    /**
     * 展开动态列
     *
     * @param array $filters
     * @param iterable
     */
    abstract protected function expandDynamicColumns(array $filters): iterable;

    /**
     * 获取导出数据
     *
     * @return Iterator
     */
    protected function getExportData(Resolver $resolver, Context $context): Iterator
    {
        $selection = $this->selection;

        Arr::forget($selection, $this->dynamicColumn);

        return $resolver->export($context, $this->getExportOption(), $selection);
    }
}

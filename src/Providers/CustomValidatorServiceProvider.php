<?php

namespace QT\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use QT\Foundation\Validator\ExtendValidator;

class CustomValidatorServiceProvider extends ServiceProvider
{
    protected $extends = [
        ExtendValidator::class => [
            'extend' => [
                'phone_number'         => 'phoneNumber',
                'id_number'            => 'idNumber',
                'limit_decimal'        => 'limitDecimal',
                'math_pe_time'         => 'mathPeTime',
                'greater_than_equal'   => 'greaterThanEqual',
                'telephone'            => 'telephone',
                'alphabet_num'         => 'alphabetNum',
                'alphabet_dash'        => 'alphabetDash',
                'is_chinese'           => 'isChinese',
                'alpha_dash_dot'       => 'validateAlphaDashAndMiddleDot',
                'alpha_null'           => 'validateAlphaNull',
                'student_no'           => 'validateStudentNo',
                'html_content_length'  => 'validateHtmlContentLength',
                'preschool_student_no' => 'validatePreschoolStudentNo',
                'isbn'                 => 'validateISBN',
            ],
            'replacer' => [
                'html_content_length' => 'htmlContentLengthMsg',
                'greater_than_equal'  => 'greaterThanEqualMsg',
                'date_format'         => 'dateFormatMsg',
            ],
        ],
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        foreach ($this->extends as $class => $methods) {
            foreach ($methods as $method => $functions) {
                foreach ($functions as $rule => $function) {
                    Validator::{$method}($rule, "{$class}@{$function}");
                }
            }
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}

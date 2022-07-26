<?php

namespace QT\Foundation\Validator;

use Illuminate\Validation\Validator;

class ExtendValidator
{
    public function phoneNumber($attribute, $value, $params, $validator)
    {
        $value = intval($value);
        return $value >= 13000000000 && $value <= 19999999999;
    }

    public function telephone($attribute, $value, $params, $validator)
    {
        // 3+{7,8} | 4+{7,8} | {7,8} | 13000000000 ~ 19999999999
        return preg_match('/^1[3-9]\d{9}$|^\d{7,8}$|^0\d{2,3}-\d{7,8}$/', $value) === 1;
    }

    // 身份证号码校验
    public function idNumber($attribute, $value, $params, Validator $validator)
    {
        if (preg_match('/^[1-8]\d{5}(19|20)\d{2}(0[1-9]|1[0-2])[0-3]\d{4}(\d|x|X)$/', $value) !== 1) {
            return false;
        }

        $date = substr($value, 6, 8);

        return date('Ymd', strtotime($date)) === $date;
    }

    public function limitDecimal($attribute, $value, $params, $validator)
    {
        $regex = sprintf('/^[0-9]+(\.[0-9]{1,%d})?$/', $params[0]);

        return preg_match($regex, $value);
    }

    public function mathPeTime($attribute, $value, $params, $validator)
    {
        if ($value == 0) {
            return true;
        }

        $regex = '/^([0-9]|[0-5][0-9])(\.[0-5]|\.[0-5][0-9])?$/';
        return preg_match($regex, $value);
    }

    public function greaterThanEqual($attribute, $value, $params, $validator)
    {
        // 兼容graphQL的传参
        $other = request()->input('variables.input.' . $params[0]) === null ? request()->input($params[0]) : request()->input('variables.input.' . $params[0]);

        return intval($value) >= intval($other);
    }

    public function dateFormatMsg($message, $attribute, $rule, $params)
    {
        $format = $params[0];

        $date = date($format);

        return str_replace(':format', "{$format}({$date})", $message);
    }

    public function greaterThanEqualMsg($message, $attribute, $rule, $params)
    {
        return str_replace(':field', trans("validation.attributes.{$params[0]}"), $message);
    }

    // 字母和数字匹配
    public function alphabetNum($attribute, $value, $params, $validator)
    {
        return preg_match('/^[0-9a-zA-Z]+$/', $value) === 1;
    }

    // 中英文、数字和斜杠匹配
    public function alphabetDash($attribute, $value, $params, $validator)
    {
        return preg_match('/^[\pL\pM\pN\/]+$/u', $value) === 1;
    }

    // 密码
    public function password($attribute, $value, $params, $validator)
    {
        return preg_match('/^[0-9a-zA-Z\d\x!$#%]+$/', $value) === 1;
    }

    // 严格密码,至少需要满足3种格式,英文字母大小写,数字,ascii特殊符号(!"#$%&'()*+,-./:;<=>?@[\]^_`{|}~)
    public function strictPassword($attribute, $value, $params, $validator)
    {
        if (preg_match('/^[\w\x21-\x2f\x3a-\x40\x5b-\x60\x7b-\x7e]{8,32}$/', $value) !== 1) {
            return false;
        }

        $count = 0;
        $rules = [
            '/[\d]/',
            '/[a-z]/',
            '/[A-Z]/',
            '/[\x21-\x2f\x3a-\x40\x5b-\x60\x7b-\x7e]/',
        ];

        // 分开校验，避免正则表达式太长
        foreach ($rules as $rule) {
            if (preg_match($rule, $value) === 1) {
                $count++;
            }
        }

        return $count >= 3;
    }

    public function isChinese($attribute, $value, $params, $validator)
    {
        return preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $value) === 1;
    }

    public function validateAlphaDashAndMiddleDot($attribute, $value)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        // https://www.compart.com/en/unicode/U+00B7
        return preg_match('/^[·a-zA-Z\d\p{Han}]+$/u', $value) > 0;
    }

    // ConvertEmptyStringsToNull中间件将""转换成null，需要特殊处理
    public function validateAlphaNull($attribute, $value)
    {
        if ($value === null) {
            return true;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        // https://www.compart.com/en/unicode/U+00B7
        return preg_match('/^[\pL\pM\pN]+$/u', $value) > 0;
    }

    // 匹配中小学学籍号
    public function validateStudentNo($attribute, $value, $params, $validator)
    {
        return preg_match('/^[GL][0-9a-zA-Z]{18}$/', $value) === 1;
    }

    // 检查去除html标签后内容的长度
    public function validateHtmlContentLength($attribute, $value, $params, $validator)
    {
        if (empty($params[0])) {
            return true;
        }

        return !(mb_strlen(strip_tags($value)) > $params[0]);
    }

    public function htmlContentLengthMsg($message, $attribute, $rule, $params)
    {
        return str_replace(':max', $params[0], $message);
    }

    // 匹配学前学籍号
    public function validatePreschoolStudentNo($attribute, $value, $params, $validator)
    {
        return preg_match('/^LG[0-9a-zA-Z]{18}$/', $value) === 1;
    }

    // 匹配ISBN
    public function validateISBN($attribute, $value, $params, $validator)
    {
        return preg_match('/^[\d]{12}[\dX]$/', $value) === 1;
    }
}

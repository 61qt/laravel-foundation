<?php

namespace QT\Foundation\Validator;

use Illuminate\Validation\Validator;

class ExtendValidator
{
    /**
     * 判断是否手机号
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function phoneNumber(string $attribute, mixed $value): bool
    {
        $value = intval($value);

        return $value >= 13000000000 && $value <= 19999999999;
    }

    /**
     * 判断是否手机号或固话
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function telephone(string $attribute, mixed $value): bool
    {
        // 3+{7,8} | 4+{7,8} | {7,8} | 13000000000 ~ 19999999999
        return $this->pregMatchWithString('/^1[3-9]\d{9}$|^\d{7,8}$|^0\d{2,3}-\d{7,8}$/', $value);
    }

    /**
     * 身份证号码校验
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @param Validator $validator
     * @return bool
     */
    public function idNumber(string $attribute, mixed $value, array $params, Validator $validator): bool
    {
        if (preg_match('/^[1-8]\d{5}(19|20)\d{2}(0[1-9]|1[0-2])[0-3]\d{4}(\d|x|X)$/', $value) !== 1) {
            return false;
        }

        $date = substr($value, 6, 8);

        return date('Ymd', strtotime($date)) === $date;
    }

    /**
     * 限制小数
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool
     */
    public function limitDecimal(string $attribute, mixed $value, array $params): bool
    {
        $regex = sprintf('/^[0-9]+(\.[0-9]{1,%d})?$/', $params[0]);

        return $this->pregMatchWithString($regex, $value);
    }

    /**
     * 匹配体育秒表时间
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function mathPeTime(string $attribute, mixed $value): bool
    {
        if (is_numeric($value) && intval($value) === 0) {
            return true;
        }

        $regex = '/^([0-9]|[0-5][0-9])(\.[0-5]|\.[0-5][0-9])?$/';

        return $this->pregMatchWithString($regex, $value);
    }

    /**
     * 日期校验返回错误信息
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array $params
     * @return string
     */
    public function dateFormatMsg(string $message, string $attribute, string $rule, array $params): string
    {
        $format = $params[0];

        $date = date($format);

        return str_replace(':format', "{$format}({$date})", $message);
    }

    /**
     * 判断是否大于等于
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool
     */
    public function greaterThanEqual(string $attribute, mixed $value, array $params): bool
    {
        // 兼容graphQL的传参
        $other = request()->input('variables.input.' . $params[0]) === null ? request()->input($params[0]) : request()->input('variables.input.' . $params[0]);

        return intval($value) >= intval($other);
    }

    /**
     * 大于等于返回错误信息
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array $params
     * @return string
     */
    public function greaterThanEqualMsg(string $message, string $attribute, string $rule, array $params): string
    {
        return str_replace(':field', trans("validation.attributes.{$params[0]}"), $message);
    }

    /**
     * 字母和数字匹配
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function alphabetNum(string $attribute, mixed $value): bool
    {
        return $this->pregMatchWithString('/^[0-9a-zA-Z]+$/', $value);
    }

    /**
     * PCRE模式所有字符集、数字和斜杠匹配
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function alphabetDash(string $attribute, mixed $value): bool
    {
        return $this->pregMatchWithString('/^[\pL\pM\pN\/]+$/u', $value);
    }

    /**
     * 只能由中英文、数字和“·”组成",一般用于名字校验
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function validateAlphaDashAndMiddleDot(string $attribute, mixed $value): bool
    {
        // https://www.compart.com/en/unicode/U+00B7
        return $this->pregMatchWithString('/^[·a-zA-Z\d\p{Han}]+$/u', $value);
    }

    /**
     * 校验是否为空，ConvertEmptyStringsToNull中间件将""转换成null，需要特殊处理
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function validateAlphaNull(string $attribute, mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        // https://www.compart.com/en/unicode/U+00B7
        return $this->pregMatchWithString('/^[\pL\pM\pN]+$/u', $value);
    }

    /**
     * 是否中文
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function isChinese(string $attribute, mixed $value): bool
    {
        return $this->pregMatchWithString("/^[\x{4e00}-\x{9fa5}]+$/u", $value);
    }

    /**
     * 简单密码匹配
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function password(string $attribute, mixed $value): bool
    {
        return $this->pregMatchWithString('/^[0-9a-zA-Z\x!$#%]+$/', $value);
    }

    /**
     *  严格密码,至少需要满足3种格式,英文字母大小写,数字,ascii特殊符号(!"#$%&'()*+,-./:;<=>?@[\]^_`{|}~)
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function strictPassword(string $attribute, mixed $value): bool
    {
        if (!$this->pregMatchWithString('/^[\w\x21-\x2f\x3a-\x40\x5b-\x60\x7b-\x7e]{8,32}$/', $value)) {
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

    /**
     * 匹配中小学学籍号
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function validateStudentNo(string $attribute, mixed $value): bool
    {
        return $this->pregMatchWithString('/^[GL]\d{17}[\dX]$/i', $value);
    }

    /**
     * 匹配学前学籍号
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function validatePreschoolStudentNo(string $attribute, mixed $value): bool
    {
        return $this->pregMatchWithString('/^LG\d{17}[\dX]$/i', $value);
    }

    /**
     * 检查去除html标签后内容的长度
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool
     */
    public function validateHtmlContentLength(string $attribute, mixed $value, array $params): bool
    {
        if (empty($params[0])) {
            return true;
        }

        return !(mb_strlen(strip_tags($value)) > $params[0]);
    }

    /**
     * 检查去除html标签后内容返回错误信息
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array $params
     * @return string
     */
    public function htmlContentLengthMsg(string $message, string $attribute, string $rule, array $params): string
    {
        return str_replace(':max', $params[0], $message);
    }

    /**
     * 限制小数位的报错提示
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array $params
     * @return string
     */
    public function limitDecimalMsg(string $message, string $attribute, string $rule, array $params): string
    {
        return str_replace(':max', $params[0], $message);
    }

    /**
     * 匹配ISBN
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function validateISBN(string $attribute, mixed $value): bool
    {
        return $this->pregMatchWithString('/^[\d]{12}[\dX]$/', $value);
    }

    /**
     * 校验是否是http或https的url
     *
     * @param string $attribute
     * @param string|int $value
     * @return bool
     */
    public function validateHttpUrl(string $attribute, string|int $value): bool
    {
        $patterns = [
            '/^https?:\/\/',                     // http or https
            '([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+',  // 域名部分
            '[a-zA-Z]{2,}',                      // 顶级域名(.com,.cn 等)
            '(:[0-9]{1,5})?',                    // 可选的端口号
            '(\/[^\s]*)?',                       // 路径
            '(\?[^\s]*)?',                       // 可选的查询字符串
            '(\#[^\s]*)?$/',                     // 可选的片段
        ];

        return preg_match(implode('', $patterns), $value) === 1;
    }

    /**
     * 正则校验表达式，排除非字符串的情况
     *
     * @param string $pattern
     * @param mixed $subject
     * @param array|null $matches
     * @param int $flags
     * @param int $offset
     * @return bool
     */
    protected function pregMatchWithString(
        string $pattern,
        mixed $subject,
        array &$matches = null,
        int $flags = 0,
        int $offset = 0
    ): bool {
        if (!is_scalar($subject) || is_bool($subject)) {
            return false;
        }

        return preg_match($pattern, $subject, $matches, $flags, $offset) === 1;
    }
}

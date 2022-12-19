<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Translation\Translator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Contracts\Validation\Factory;
use QT\Foundation\Validator\ExtendValidator;
use Illuminate\Validation\Factory as ValidationFactory;

class ExtendValidatorTest extends TestCase
{
    protected $phones = [
        [
            'phone'  => 13800138000,
            'result' => true,
        ],
        [
            'phone'  => '13800138001',
            'result' => true,
        ],
        [
            'phone'  => '21234567890',
            'result' => false,
        ],
        [
            'phone'  => null,
            'result' => false,
        ],
        [
            'phone'  => 1234567890,
            'result' => false,
        ],
        [
            'phone'  => 12345678901,
            'result' => false,
        ],
        [
            'phone'  => '13800 138000',
            'result' => false,
        ],
        [
            'phone'  => ' 13800 138000 ',
            'result' => false,
        ],
        [
            'phone'  => '138a00138000',
            'result' => false,
        ],
        [
            'phone'  => '138001380001',
            'result' => false,
        ],
        [
            'phone'  => '13800+1380001',
            'result' => false,
        ],
        [
            'phone'  => '13800一1380001',
            'result' => false,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testArrayToKey()
    {
        $values = [
            [
                'first'  => [1, 2, 3],
                'second' => [1, 2, 3],
                'result' => true,
            ],
            [
                'first'  => [1.0, 2.0, 3.0],
                'second' => [1, 2, 3],
                'result' => true,
            ],
            [
                'first'  => [1.0, 2.0, 3.09],
                'second' => [1, 2, 3],
                'result' => false,
            ],
            [
                'first'  => [12, 34],
                'second' => [1, 2, 34],
                'result' => false,
            ],
            [
                'first'  => ['aa', 'bb', 'cc'],
                'second' => ['a' => 'aa', 'bb', 'c' => 'cc'],
                'result' => true,
            ],
            [
                'first'  => ['aa', 'bb', 'cc'],
                'second' => ['a' => 'aab', 'bcc'],
                'result' => false,
            ],
            [
                'first'  => ["a\t", 'b', 'c'],
                'second' => ['a', "\tb", 'c'],
                'result' => true,
            ],
            [
                'first'  => ['a\t', 'b', 'c'],
                'second' => ['a', "\tb", 'c'],
                'result' => false,
            ],
            [
                'first'  => ['a', 'b', 'c' => ''],
                'second' => ['a', 'b' => '', 'b'],
                'result' => false,
            ],
            [
                'first'  => ['a', 'b', 'c' => '', 'd'],
                'second' => ['a', 'b', 'd'],
                'result' => false,
            ],
            [
                'first'  => ['a', 'b', 'c' => ''],
                'second' => ['a', 'b', 'c' => null],
                'result' => true,
            ],
            [
                'first'  => ['a', 'b' => '', 'c' => 'c'],
                'second' => ['a', 'b' => null, 'c' => 'c'],
                'result' => true,
            ],
            [
                'first'  => ['a', 'b' => 'null', 'c' => 'c'],
                'second' => ['a', 'b' => null, 'c' => 'c'],
                'result' => false,
            ],
            [
                'first'  => ['a', 'b', 1],
                'second' => ['a', 'b', '1'],
                'result' => true,
            ],
            [
                'first'  => ['a', 'b', 1.0],
                'second' => ['a', 'b', '1.0'],
                'result' => false,
            ],
            [
                'first'  => ['a', 'b', 0x539],
                'second' => ['a', 'b', 1337],
                'result' => true,
            ],
            [
                'first'  => ['a', 'b', 0x539],
                'second' => ['a', 'b', '1337'],
                'result' => true,
            ],
            [
                'first'  => ['a', 'b', 0x539],
                'second' => ['a', 'b', '0x539'],
                'result' => false,
            ],
            [
                'first'  => ['一', '二', '三'],
                'second' => ['一', '二', '三'],
                'result' => true,
            ],
            [
                'first'  => ['一二', '二三', '三四'],
                'second' => ['一', '二二三', '三四'],
                'result' => false,
            ],
            [
                'first'  => ['-', '=', '+'],
                'second' => ['-', '=', '+'],
                'result' => true,
            ],
            [
                'first'  => ["''", '=', '+'],
                'second' => ['"', '=', '+'],
                'result' => false,
            ],
        ];

        foreach ($values as $value) {
            $this->assertTrue((array_to_key($value['first']) === array_to_key($value['second'])) === $value['result']);
        }
    }

    public function testValidationFactory()
    {
        $factory = new ValidationFactory(new Translator(new ArrayLoader(), 'cn'));

        $this->assertInstanceOf(Factory::class, $factory);

        return $factory;
    }

    /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testTelephone(Factory $factory)
    {
        $factory->extend('telephone', [new ExtendValidator(), 'telephone']);
        $telephones = [
            ['phone' => '1234567', 'result' => true],
            ['phone' => '12345678', 'result' => true],
            ['phone' => '123456789', 'result' => false],
            ['phone' => '022-1234567', 'result' => true],
            ['phone' => '020-12345678', 'result' => true],
            ['phone' => '020-123456789', 'result' => false],
            ['phone' => '0221234567', 'result' => false],
            ['phone' => '020123456789', 'result' => false],
            ['phone' => '022-o234567', 'result' => false],
            ['phone' => '020-12345', 'result' => false],
            ['phone' => '020_12345', 'result' => false],
            ['phone' => '020——12345', 'result' => false],
        ];
        $telephones = array_merge($this->phones, $telephones);

        foreach ($telephones as $telephone) {
            $validator = $factory->make($telephone, [
                'phone' => 'telephone',
            ]);

            $this->assertTrue($validator->fails() !== $telephone['result']);
        }
    }

    /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testPhoneNumber(Factory $factory)
    {
        $factory->extend('phone_number', [new ExtendValidator(), 'phoneNumber']);

        $values = [
            [
                'phone'  => 13800138000,
                'result' => true,
            ],
            [
                'phone'  => '13800138001',
                'result' => true,
            ],
            [
                'phone'  => '21234567890',
                'result' => false,
            ],
            [
                'phone'  => null,
                'result' => false,
            ],
            [
                'phone'  => 1234567890,
                'result' => false,
            ],
            [
                'phone'  => 12345678901,
                'result' => false,
            ],
            [
                'phone'  => ' 13800138000 ',
                'result' => true,
            ],
            [
                'phone'  => '13800 138000',
                'result' => false,
            ],
            [
                'phone'  => ' 13800 138000 ',
                'result' => false,
            ],
            [
                'phone'  => '138a00138000',
                'result' => false,
            ],
            [
                'phone'  => '138001380001',
                'result' => false,
            ],
            [
                'phone'  => '13800+1380001',
                'result' => false,
            ],
            [
                'phone'  => '13800一1380001',
                'result' => false,
            ],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'phone' => 'phone_number',
            ]);

            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

    /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testIdNumber(Factory $factory)
    {
        $factory->extend('id_number', [new ExtendValidator(), 'idNumber']);

        $values = [
            [
                'id_number' => '110101199003079577',
                'result'    => true,
            ],
            [
                'id_number' => '11010119900307707X',
                'result'    => true,
            ],
            [
                'id_number' => '321002192608174373',
                'result'    => true,
            ],
            [
                'id_number' => '42010220131307991X',
                'result'    => false,
            ],
            [
                'id_number' => '42010220110307991x',
                'result'    => true,
            ],
            [
                'id_number' => 'x42010220110307991',
                'result'    => false,
            ],
            [
                'id_number' => '92010220110307991',
                'result'    => false,
            ],
            [
                'id_number' => '42010220110307991',
                'result'    => false,
            ],
            [
                'id_number' => '92010220110307991a',
                'result'    => false,
            ],
            [
                'id_number' => '42010220110307991=',
                'result'    => false,
            ],
            [
                'id_number' => '3210021926 08174373',
                'result'    => false,
            ],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'id_number' => 'id_number',
            ]);

            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

    /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testLimitDecimal(Factory $factory)
    {
        $factory->extend('limit_decimal', [new ExtendValidator(), 'limitDecimal']);

        $values = [
            [
                'number'  => 12,
                'decimal' => 1,
                'result'  => true,
            ],
            [
                'number'  => 0,
                'decimal' => 1,
                'result'  => true,
            ],
            [
                'number'  => 09.10,
                'decimal' => 2,
                'result'  => true,
            ],
            [
                'number'  => '8.25',
                'decimal' => 2,
                'result'  => true,
            ],
            [
                'number'  => 12.,
                'decimal' => 1,
                'result'  => true,
            ],
            [
                'number'  => 0x539,
                'decimal' => 2,
                'result'  => true,
            ],
            [
                'number'  => '0x539',
                'decimal' => 2,
                'result'  => false,
            ],
            [
                'number'  => 2.022,
                'decimal' => 1,
                'result'  => false,
            ],
            [
                'number'  => '10.a',
                'decimal' => 2,
                'result'  => false,
            ],
            [
                'number'  => '20. 23',
                'decimal' => 1,
                'result'  => false,
            ],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'number' => 'limit_decimal:' . $value['decimal'],
            ]);

            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

    /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testMathPeTime(Factory $factory)
    {
        $factory->extend('math_pe_time', [new ExtendValidator(), 'mathPeTime']);

        $values = [
            [
                'time'   => '0',
                'result' => true,
            ],
            [
                'time'   => 9.5,
                'result' => true,
            ],
            [
                'time'   => 09.5,
                'result' => true,
            ],
            [
                'time'   => 9.59,
                'result' => true,
            ],
            [
                'time'   => 09.48,
                'result' => true,
            ],
            [
                'time'   => '00.01',
                'result' => true,
            ],
            [
                'time'   => true,
                'result' => false,
            ],
            [
                'time'   => null,
                'result' => false,
            ],
            [
                'time'   => '61',
                'result' => false,
            ],
            [
                'time'   => '59.60',
                'result' => false,
            ],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'time' => 'math_pe_time',
            ]);

            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

     /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testValidateHtmlContentLength(Factory $factory)
    {
        $factory->extend('html_content_length', [new ExtendValidator(), 'validateHtmlContentLength']);
        $factory->replacer('html_content_length', [new ExtendValidator(), 'htmlContentLengthMsg']);

        $values = [
            [
                'content' => '1234213阿达a-i9(*',
                'max'     => 15,
                'result'  => true,
            ],
            [
                'content' => '<p>测试</p>',
                'max'     => 3,
                'result'  => true,
            ],
            [
                'content' => '<p>测试</p><br/>',
                'max'     => 3,
                'result'  => true,
            ],
            [
                'content' => '<p>测试',
                'max'     => 3,
                'result'  => true,
            ],
            [
                'content' => '1234',
                'max'     => 3,
                'result'  => false,
            ],
            [
                'content' => '字数超过了',
                'max'     => 3,
                'result'  => false,
            ],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'content' => 'html_content_length:' . $value['max'],
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

     /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testAlphabetNum(Factory $factory)
    {
        $factory->extend('alphabet_num', [new ExtendValidator(), 'alphabetNum']);

        $values = [
            ['data' => 'test', 'result' => true],
            ['data' => 'TEST', 'result' => true],
            ['data' => 1235, 'result' => true],
            ['data' => '2023', 'result' => true],
            ['data' => 0x539, 'result' => true],
            ['data' => 'Test', 'result' => true],
            ['data' => 'QT123', 'result' => true],
            ['data' => 'root123', 'result' => true],
            ['data' => 'stAr825',  'result' => true],
            ['data' => 'Abc!', 'result' => false],
            ['data' => '$', 'result' => false],
            ['data' => 'm J', 'result' => false],
            ['data' => '！', 'result' => false],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'data' => 'alphabet_num',
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

     /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testAlphabetDash(Factory $factory)
    {
        $factory->extend('alphabet_dash', [new ExtendValidator(), 'alphabetDash']);

        $values = [
            ['data' => 'test', 'result' => true],
            ['data' => 'TEST', 'result' => true],
            ['data' => 1235, 'result' => true],
            ['data' => '2023', 'result' => true],
            ['data' => 0x539, 'result' => true],
            ['data' => 'Test', 'result' => true],
            ['data' => 'QT123', 'result' => true],
            ['data' => 'root123', 'result' => true],
            ['data' => 'stAr825',  'result' => true],
            ['data' => 'stAr825/',  'result' => true],
            ['data' => '测试stAr825/',  'result' => true],
            ['data' => '测试のstAr825/',  'result' => true],
            ['data' => '测试の테스트stAr825/',  'result' => true],
            ['data' => 'Abc!', 'result' => false],
            ['data' => '$', 'result' => false],
            ['data' => 'm J', 'result' => false],
            ['data' => '！', 'result' => false],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'data' => 'alphabet_dash',
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

     /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testValidateAlphaDashAndMiddleDot(Factory $factory)
    {
        $factory->extend('alpha_dash_dot', [new ExtendValidator(), 'validateAlphaDashAndMiddleDot']);

        $values = [
            ['data' => 'test', 'result' => true],
            ['data' => 'TEST', 'result' => true],
            ['data' => 1235, 'result' => true],
            ['data' => '2023', 'result' => true],
            ['data' => 0x539, 'result' => true],
            ['data' => 'Test', 'result' => true],
            ['data' => 'QT123', 'result' => true],
            ['data' => 'root123', 'result' => true],
            ['data' => 'stAr825',  'result' => true],
            ['data' => '测试·stAr825',  'result' => true],
            ['data' => 'stAr825/',  'result' => false],
            ['data' => '测试のstAr825',  'result' => false],
            ['data' => '测试の테스트stAr825',  'result' => false],
            ['data' => 'Abc!', 'result' => false],
            ['data' => '$', 'result' => false],
            ['data' => 'm J', 'result' => false],
            ['data' => '！', 'result' => false],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'data' => 'alpha_dash_dot',
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

     /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testValidateAlphaNull(Factory $factory)
    {
        $factory->extend('alpha_null', [new ExtendValidator(), 'validateAlphaNull']);

        $values = [
            ['data' => 'test', 'result' => true],
            ['data' => 'TEST', 'result' => true],
            ['data' => 1235, 'result' => true],
            ['data' => '2023', 'result' => true],
            ['data' => 0x539, 'result' => true],
            ['data' => 'Test', 'result' => true],
            ['data' => 'QT123', 'result' => true],
            ['data' => 'root123', 'result' => true],
            ['data' => 'stAr825',  'result' => true],
            ['data' => null,  'result' => true],
            ['data' => '测试stAr825/',  'result' => false],
            ['data' => '测试のstAr825',  'result' => true],
            ['data' => '测试の테스트stAr825',  'result' => true],
            ['data' => 'Abc!', 'result' => false],
            ['data' => '$', 'result' => false],
            ['data' => 'm J', 'result' => false],
            ['data' => '！', 'result' => false],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'data' => 'alpha_null',
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

     /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testIsChinese(Factory $factory)
    {
        $factory->extend('is_chinese', [new ExtendValidator(), 'isChinese']);

        $values = [
            ['data' => '测试', 'result' => true],
            ['data' => 'test', 'result' => false],
            ['data' => 1235, 'result' => false],
            ['data' => '2023', 'result' => false],
            ['data' => 0x539, 'result' => false],
            ['data' => 'Test', 'result' => false],
            ['data' => 'QT123', 'result' => false],
            ['data' => 'root123', 'result' => false],
            ['data' => 'stAr825',  'result' => false],
            ['data' => '测试stAr825/',  'result' => false],
            ['data' => '测试のstAr825',  'result' => false],
            ['data' => '测试の테스트stAr825',  'result' => false],
            ['data' => 'Abc!', 'result' => false],
            ['data' => '$', 'result' => false],
            ['data' => 'm J', 'result' => false],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'data' => 'is_chinese',
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

    /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testPassword(Factory $factory)
    {
        $factory->extend('password', [new ExtendValidator(), 'password']);

        $values = [
            ['data' => 'php', 'result' => true],
            ['data' => 'GO', 'result' => true],
            ['data' => '9102', 'result' => true],
            ['data' => 'Qt123', 'result' => true],
            ['data' => 'Qt123!$%', 'result' => true],
            ['data' => 'Qt123!$%测试', 'result' => false],
            ['data' => 'Qt123!$%?', 'result' => false],
            ['data' => 'Qt123!$%！', 'result' => false],
            ['data' => 'Qt123!$%)', 'result' => false],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'data' => 'password',
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

    /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testStrictPassword(Factory $factory)
    {
        $factory->extend('strict_password', [new ExtendValidator(), 'strictPassword']);

        $values = [
            ['data' => 'Qt123123', 'result' => true],
            ['data' => 't123!678', 'result' => true],
            ['data' => 'yH!uA$g.', 'result' => true],
            ['data' => 'Qt123!$%', 'result' => true],
            ['data' => 'Qt123!$%?', 'result' => true],
            ['data' => 'php', 'result' => false],
            ['data' => 'GO', 'result' => false],
            ['data' => '9102', 'result' => false],
            ['data' => 'Qt123', 'result' => false],
            ['data' => 'Qt123!$%测试', 'result' => false],
            ['data' => 'Qt123!$%！', 'result' => false],
            ['data' => 'Qt12312', 'result' => false],
            ['data' => 't123!67', 'result' => false],
            ['data' => 'yH!uA$g', 'result' => false],
            ['data' => 'Qt123!', 'result' => false],
            ['data' => 'Qt123!JOU#0590()02340l&)HeNe,ILE@', 'result' => false],
            ['data' => '12345678901011121314151617181920', 'result' => false],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'data' => 'strict_password',
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

    /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testValidateStudentNo(Factory $factory)
    {
        $factory->extend('student_no', [new ExtendValidator(), 'validateStudentNo']);

        $values = [
            ['data' => 'G321002192608174373', 'result' => true],
            ['data' => 'L321002192608174373', 'result' => true],
            ['data' => 'L32100219260817437X', 'result' => true],
            ['data' => 'L32100219260817437a', 'result' => true],
            ['data' => 'LG321002192608174373', 'result' => false],
            ['data' => '321002192608174323', 'result' => false],
            ['data' => '32100219260817432Xa', 'result' => false],
            ['data' => '3210021926081743xd?', 'result' => false],
            ['data' => '32100219260817一3xd?', 'result' => false],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'data' => 'student_no',
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

    /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testValidatePreschoolStudentNo(Factory $factory)
    {
        $factory->extend('preschool_student_no', [new ExtendValidator(), 'validatePreschoolStudentNo']);

        $values = [
            ['data' => 'LG321002192608174373', 'result' => true],
            ['data' => 'LG32100219260817437X', 'result' => true],
            ['data' => 'L321002192608174373', 'result' => false],
            ['data' => 'G321002192608174373', 'result' => false],
            ['data' => '321002192608174323', 'result' => false],
            ['data' => '32100219260817432Xa', 'result' => false],
            ['data' => '3210021926081743xd?', 'result' => false],
            ['data' => '32100219260817一3xd?', 'result' => false],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'data' => 'preschool_student_no',
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }

     /**
     * @depends testValidationFactory
     *
     * @param Factory $factory
     * @return void
     */
    public function testValidateISBN(Factory $factory)
    {
        $factory->extend('isbn', [new ExtendValidator(), 'validateISBN']);

        $values = [
            ['data' => '9787532736539', 'result' => true],
            ['data' => '9787108041531', 'result' => true],
            ['data' => '978710804153X', 'result' => true],
            ['data' => '978710804153x', 'result' => false],
            ['data' => '测试', 'result' => false],
            ['data' => 'a978753273653', 'result' => false],
            ['data' => '978753273653', 'result' => false],
            ['data' => '97875327365!', 'result' => false],
        ];

        foreach ($values as $value) {
            $validator = $factory->make($value, [
                'data' => 'isbn',
            ]);
            $this->assertTrue($validator->fails() !== $value['result']);
        }
    }
}

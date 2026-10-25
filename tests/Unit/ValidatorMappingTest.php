<?php

declare(strict_types=1);

use Strukt\Framework\Injectable\Validator;
use Strukt\Rules\Rule;

/**
 * One property per built-in rule, annotated with the canonical rule name.
 */
final class AllRulesHolder
{
    /** @Required() */
    protected string $required = '';
    /** @Nullable() */
    protected ?string $nullable = null;
    /** @Filled() */
    protected string $filled = '';
    /** @String() */
    protected string $stringVal = '';
    /** @Integer() */
    protected string $integerVal = '';
    /** @Numeric() */
    protected string $numericVal = '';
    /** @Boolean() */
    protected string $booleanVal = '';
    /** @Array() */
    protected array $arrayVal = [];
    /** @Email() */
    protected string $email = '';
    /** @Url() */
    protected string $url = '';
    /** @Uuid() */
    protected string $uuid = '';
    /** @Ip() */
    protected string $ip = '';
    /** @Alpha() */
    protected string $alpha = '';
    /** @AlphaNum() */
    protected string $alphaNum = '';
    /** @Min(5) */
    protected string $min = '';
    /** @Max(10) */
    protected string $max = '';
    /** @Between(1, 10) */
    protected string $between = '';
    /** @Length(8) */
    protected string $length = '';
    /** @MinLength(8) */
    protected string $minLength = '';
    /** @MaxLength(8) */
    protected string $maxLength = '';
    /** @MinCount(2) */
    protected array $minCount = [];
    /** @MaxCount(5) */
    protected array $maxCount = [];
    /** @In(draft, published) */
    protected string $inList = '';
    /** @NotIn(a, b) */
    protected string $notInList = '';
    /** @Same(.password) */
    protected string $same = '';
    /** @Different(.password) */
    protected string $different = '';
    /** @Regex(/^\d+$/) */
    protected string $regex = '';
    /** @Date(Y-m-d) */
    protected string $date = '';

    protected string $untouched = '';
}

it('maps every built-in rule through its canonical annotation name', function () {
    $rules = (new Validator(new ReflectionClass(AllRulesHolder::class)))->getRules();

    expect($rules)->toBe([
        'required' => 'required',
        'nullable' => 'nullable',
        'filled' => 'filled',
        'stringVal' => 'string',
        'integerVal' => 'integer',
        'numericVal' => 'numeric',
        'booleanVal' => 'boolean',
        'arrayVal' => 'array',
        'email' => 'email',
        'url' => 'url',
        'uuid' => 'uuid',
        'ip' => 'ip',
        'alpha' => 'alpha',
        'alphaNum' => 'alpha_num',
        'min' => 'min:5',
        'max' => 'max:10',
        'between' => 'between:1,10',
        'length' => 'length:8',
        'minLength' => 'min_length:8',
        'maxLength' => 'max_length:8',
        'minCount' => 'min_count:2',
        'maxCount' => 'max_count:5',
        'inList' => 'in:draft,published',
        'notInList' => 'not_in:a,b',
        'same' => 'same:password',
        'different' => 'different:password',
        'regex' => 'regex:/^\d+$/',
        'date' => 'date:Y-m-d',
    ]);
});

it('covers the whole rule registry', function () {
    $mapped = [];
    foreach ((new Validator(new ReflectionClass(AllRulesHolder::class)))->getRules() as $definition) {
        foreach (explode('|', $definition) as $token) {
            $mapped[] = explode(':', $token, 2)[0];
        }
    }

    expect(array_values(array_unique($mapped)))->toEqualCanonicalizing(Rule::names());
});

it('keeps the legacy friendly aliases working', function () {
    $holder = new class {
        /**
         * @IsNotEmpty()
         * @IsAlpha()
         * @IsAlphanum()
         */
        protected string $a = '';
        /**
         * @IsEmail()
         * @IsNum()
         */
        protected string $b = '';
        /**
         * @IsLen(5)
         * @IsDate()
         */
        protected string $c = '';
        /**
         * @EqualTo(.password)
         * @IsIn(a, b)
         */
        protected string $d = '';
    };

    $rules = (new Validator(new ReflectionClass($holder)))->getRules();

    expect($rules)->toBe([
        'a' => 'required|alpha|alpha_num',
        'b' => 'email|numeric',
        'c' => 'length:5|date',
        'd' => 'same:password|in:a,b',
    ]);
});

it('ignores annotations that are not rules', function () {
    $holder = new class {
        /**
         * @Column()
         * @Type(full)
         * @IsNotEmpty()
         */
        protected string $name = '';
    };

    expect((new Validator(new ReflectionClass($holder)))->getRules())->toBe([
        'name' => 'required',
    ]);
});

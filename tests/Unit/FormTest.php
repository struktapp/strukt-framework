<?php

declare(strict_types=1);

use Strukt\Framework\Contract\Form;

final class LoginForm extends Form
{
    /**
     * @IsNotEmpty()
     * @IsAlpha()
     */
    protected string $username = '';

    /**
     * @IsNotEmpty()
     */
    protected string $password = '';

    protected string $note = '';
}

it('compiles property annotations into validation rules', function () {
    $form = new LoginForm();

    expect($form->rules())->toMatchArray([
        'username' => 'required|alpha',
        'password' => 'required',
    ]);
});

it('validates through the strukt/rules engine', function () {
    expect((new LoginForm())->validateWith(['username' => 'ada', 'password' => 'secret']))->toBeTrue();
});

it('collects failures per field', function () {
    $form = (new LoginForm())->submit(['username' => 'ada123', 'password' => '']);

    expect($form->validate())->toBeFalse();
    expect($form->hasErrors())->toBeTrue();
    expect($form->errorsFor('username'))->toContain('Username may only contain letters.');
    expect($form->errorsFor('password'))->toContain('Password is required.');
});

it('satisfies the form package and router contracts', function () {
    $form = new LoginForm();

    expect($form)->toBeInstanceOf(\Strukt\Form\Form::class);
    expect($form)->toBeInstanceOf(\Strukt\Contract\FormInterface::class);
});
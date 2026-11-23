<?php
declare(strict_types=1);

namespace Tests\Unit;

use KD\Http\Response;
use KD\Http\Router;
use KD\Http\HttpException;
use KD\Support\Csrf;
use KD\Support\Money;
use KD\Support\Password;
use KD\Support\Validator;
use Tests\TestCase;

final class CsrfTest extends TestCase
{
    public function testTokenIsStableWithinSession(): void
    {
        $a = Csrf::token();
        $this->assertSame($a, Csrf::token());
        $this->assertSame(64, strlen($a));
    }

    public function testValidTokenVerifies(): void
    {
        $this->assertTrue(Csrf::verify(Csrf::token()));
    }

    public function testMissingOrWrongTokenFails(): void
    {
        Csrf::token();
        $this->assertFalse(Csrf::verify(null));
        $this->assertFalse(Csrf::verify(''));
        $this->assertFalse(Csrf::verify(str_repeat('a', 64)));
    }

    public function testNoTokenInSessionNeverVerifies(): void
    {
        $this->assertFalse(Csrf::verify(''), 'empty token must not match an empty session');
    }

    public function testRotateChangesToken(): void
    {
        $a = Csrf::token();
        Csrf::rotate();
        $this->assertNotSame($a, Csrf::token());
    }

    public function testFieldIsEscapedHiddenInput(): void
    {
        $html = Csrf::field();
        $this->assertStringContains('type="hidden"', $html);
        $this->assertStringContains('name="_csrf"', $html);
    }
}

final class PasswordTest extends TestCase
{
    public function testHashIsNotPlainTextAndVerifies(): void
    {
        $hash = Password::hash('Correct-horse-9');
        $this->assertNotSame('Correct-horse-9', $hash);
        $this->assertTrue(Password::verify('Correct-horse-9', $hash));
        $this->assertFalse(Password::verify('correct-horse-9', $hash));
    }

    public function testSameInputGivesDifferentHashes(): void
    {
        $this->assertNotSame(Password::hash('Correct-horse-9'), Password::hash('Correct-horse-9'));
    }

    public function testPolicyRejectsShortCommonAndEmailPasswords(): void
    {
        $this->assertNotNull(Password::policyError('short1A'));
        $this->assertNotNull(Password::policyError('password123'));
        $this->assertNotNull(Password::policyError('aaaaaaaaaaaa'), 'needs letters and digits');
        $this->assertNotNull(Password::policyError('girish2026x', 'girish2026x@example.com'));
        $this->assertNotNull(Password::policyError(str_repeat('ab1', 30)), 'over 72 bytes is rejected');
    }

    public function testPolicyAcceptsReasonablePassword(): void
    {
        $this->assertNull(Password::policyError('Monsoon-Train-42'));
    }

    public function testVerifyAgainstGarbageHashIsFalse(): void
    {
        $this->assertFalse(Password::verify('anything', '123456'));
    }
}

final class ValidatorTest extends TestCase
{
    public function testRequiredAndEmail(): void
    {
        $errors = Validator::validate(['email' => 'nope', 'name' => '  '], [
            'email' => 'required|email',
            'name'  => 'required',
        ]);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testValidDataHasNoErrors(): void
    {
        $errors = Validator::validate(
            ['email' => 'a@b.co', 'qty' => '3', 'size' => 'M', 'phone' => '+91 98765 43210'],
            ['email' => 'required|email|max:190', 'qty' => 'required|int|between:1,10', 'size' => 'in:S,M,L,XL', 'phone' => 'phone']
        );
        $this->assertSame([], $errors);
    }

    public function testIntRejectsInjectionStrings(): void
    {
        $errors = Validator::validate(['id' => "1' OR '1'='1"], ['id' => 'required|int']);
        $this->assertArrayHasKey('id', $errors);
    }

    public function testBetweenAndInBounds(): void
    {
        $this->assertArrayHasKey('qty', Validator::validate(['qty' => '11'], ['qty' => 'int|between:1,10']));
        $this->assertArrayHasKey('qty', Validator::validate(['qty' => '0'], ['qty' => 'int|between:1,10']));
        $this->assertArrayHasKey('size', Validator::validate(['size' => 'XXL'], ['size' => 'in:S,M,L,XL']));
    }

    public function testUrlOnlyAllowsHttpAndRelative(): void
    {
        $this->assertArrayHasKey('u', Validator::validate(['u' => 'javascript:alert(1)'], ['u' => 'url']));
        $this->assertArrayHasKey('u', Validator::validate(['u' => '//evil.example'], ['u' => 'url']));
        $this->assertSame([], Validator::validate(['u' => '/shop?cat=2'], ['u' => 'url']));
        $this->assertSame([], Validator::validate(['u' => 'https://example.com/x'], ['u' => 'url']));
    }

    public function testOptionalEmptyFieldSkipsOtherRules(): void
    {
        $this->assertSame([], Validator::validate(['phone' => ''], ['phone' => 'phone|max:20']));
    }

    public function testMaxCountsCharactersNotBytes(): void
    {
        $this->assertSame([], Validator::validate(['n' => 'गिरीश'], ['n' => 'max:5']));
    }

    public function testDateAndMoney(): void
    {
        $this->assertArrayHasKey('d', Validator::validate(['d' => '2026-02-30'], ['d' => 'date']));
        $this->assertSame([], Validator::validate(['d' => '2026-02-28'], ['d' => 'date']));
        $this->assertArrayHasKey('m', Validator::validate(['m' => '-5'], ['m' => 'money']));
        $this->assertSame([], Validator::validate(['m' => '1499.50'], ['m' => 'money']));
    }
}

final class MoneyTest extends TestCase
{
    public function testFormat(): void
    {
        $this->assertSame('₹1,499.50', Money::format(149950));
        $this->assertSame('₹0.00', Money::format(0));
    }

    public function testParse(): void
    {
        $this->assertSame(149950, Money::parse('1499.50'));
        $this->assertSame(150000, Money::parse('1500'));
        $this->assertSame(1050, Money::parse('10.5'));
        $this->assertNull(Money::parse('abc'));
        $this->assertNull(Money::parse('-1'));
        $this->assertNull(Money::parse('1.234'));
    }
}

final class RouterTest extends TestCase
{
    public function testMatchesStaticAndParamRoutes(): void
    {
        $r = new Router();
        $r->get('/', 'home');
        $r->get('/product/{id}', 'product');
        [$h, $p] = $r->match('GET', '/');
        $this->assertSame('home', $h);
        [$h, $p] = $r->match('GET', '/product/42');
        $this->assertSame('product', $h);
        $this->assertSame(42, $p['id']);
    }

    public function testParamMustBeNumeric(): void
    {
        $r = new Router();
        $r->get('/product/{id}', 'product');
        $e = $this->assertThrows(HttpException::class, fn () => $r->match('GET', "/product/1'or'1"));
        $this->assertSame(404, $e->getCode());
    }

    public function testWrongMethodIs405(): void
    {
        $r = new Router();
        $r->post('/cart/add', 'add');
        $e = $this->assertThrows(HttpException::class, fn () => $r->match('GET', '/cart/add'));
        $this->assertSame(405, $e->getCode());
    }

    public function testTrailingSlashIsIgnored(): void
    {
        $r = new Router();
        $r->get('/shop', 'shop');
        [$h] = $r->match('GET', '/shop/');
        $this->assertSame('shop', $h);
    }
}

final class RedirectTest extends TestCase
{
    public function testOnlyLocalPathsAreAllowed(): void
    {
        $this->assertSame('/account', Response::safeTarget('/account', '/'));
        $this->assertSame('/', Response::safeTarget('https://evil.example', '/'));
        $this->assertSame('/', Response::safeTarget('//evil.example', '/'));
        $this->assertSame('/', Response::safeTarget('/\\evil.example', '/'));
        $this->assertSame('/', Response::safeTarget("/ok\r\nSet-Cookie: x", '/'));
        $this->assertSame('/', Response::safeTarget(null, '/'));
    }
}

final class EscapeTest extends TestCase
{
    public function testEscapesHtmlAndQuotes(): void
    {
        $this->assertSame('&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;', e("<script>alert('x')</script>"));
        $this->assertSame('&quot; onmouseover=&quot;x', e('" onmouseover="x'));
        $this->assertSame('', e(null));
        $this->assertSame('5', e(5));
    }
}

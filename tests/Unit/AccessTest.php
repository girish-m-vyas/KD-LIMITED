<?php
declare(strict_types=1);

namespace Tests\Unit;

use KD\Auth\Rbac;
use KD\Services\Cart;
use KD\Support\Upload;
use KD\Support\UploadException;
use Tests\TestCase;

final class RbacTest extends TestCase
{
    public function testSuperAdminCanDoEverything(): void
    {
        foreach (Rbac::PERMISSIONS as $perm => $_label) {
            $this->assertTrue(Rbac::can('super_admin', $perm), "super_admin should have {$perm}");
        }
    }

    public function testCatalogManagerIsLimited(): void
    {
        $this->assertTrue(Rbac::can('catalog_manager', 'catalog.manage'));
        $this->assertTrue(Rbac::can('catalog_manager', 'content.manage'));
        $this->assertFalse(Rbac::can('catalog_manager', 'orders.manage'));
        $this->assertFalse(Rbac::can('catalog_manager', 'users.manage'));
        $this->assertFalse(Rbac::can('catalog_manager', 'customers.manage'));
    }

    public function testOrderManagerIsLimited(): void
    {
        $this->assertTrue(Rbac::can('order_manager', 'orders.manage'));
        $this->assertTrue(Rbac::can('order_manager', 'payments.view'));
        $this->assertFalse(Rbac::can('order_manager', 'catalog.manage'));
        $this->assertFalse(Rbac::can('order_manager', 'users.manage'));
        $this->assertFalse(Rbac::can('order_manager', 'audit.view'));
    }

    public function testUnknownRoleOrPermissionIsDenied(): void
    {
        $this->assertFalse(Rbac::can('hacker', 'catalog.manage'));
        $this->assertFalse(Rbac::can('super_admin', 'does.not.exist'));
        $this->assertFalse(Rbac::can('', ''));
    }
}

final class CartTest extends TestCase
{
    public function testAddMergesSameProductAndSize(): void
    {
        Cart::add(1, 2, 'M');
        Cart::add(1, 3, 'M');
        Cart::add(1, 1, 'L');
        $items = Cart::items();
        $this->assertCount(2, $items);
        $this->assertSame(5, $items['1|M']['qty']);
        $this->assertSame(6, Cart::count());
    }

    public function testQuantityIsCappedAndValidated(): void
    {
        Cart::add(1, 9, 'M');
        Cart::add(1, 9, 'M');
        $this->assertSame(Cart::MAX_QTY, Cart::items()['1|M']['qty']);
        $this->assertThrows(\InvalidArgumentException::class, fn () => Cart::add(1, 0, 'M'));
        $this->assertThrows(\InvalidArgumentException::class, fn () => Cart::add(1, -3, 'M'));
        $this->assertThrows(\InvalidArgumentException::class, fn () => Cart::add(1, 1, 'XXL<script>'));
        $this->assertThrows(\InvalidArgumentException::class, fn () => Cart::add(0, 1, 'M'));
    }

    public function testUpdateZeroRemoves(): void
    {
        Cart::add(2, 2, 'S');
        Cart::update('2|S', 0);
        $this->assertSame([], Cart::items());
    }

    public function testUnknownKeysAreIgnored(): void
    {
        Cart::add(2, 2, 'S');
        Cart::update('../../etc', 5);
        Cart::remove('nope');
        $this->assertCount(1, Cart::items());
    }

    public function testCartStoresNoPrices(): void
    {
        Cart::add(3, 1, 'XL');
        $this->assertSame(['product_id' => 3, 'qty' => 1, 'size' => 'XL'], Cart::items()['3|XL']);
    }
}

final class UploadTest extends TestCase
{
    private string $dir;

    public function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/kd-upload-test-' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/products', 0777, true);
    }

    public function tearDown(): void
    {
        foreach (glob($this->dir . '/products/*') ?: [] as $f) {
            unlink($f);
        }
        @rmdir($this->dir . '/products');
        @rmdir($this->dir);
    }

    private function uploader(): Upload
    {
        // Test doubles: treat any existing file as uploaded and copy instead of move.
        return new Upload($this->dir, fn ($from, $to) => copy($from, $to), fn ($p) => is_file($p));
    }

    private function fakeFile(string $contents, string $name = 'photo.jpg'): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'kdup');
        file_put_contents($tmp, $contents);
        return ['name' => $name, 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($contents)];
    }

    private function realPng(): string
    {
        $img = imagecreatetruecolor(20, 20);
        ob_start();
        imagepng($img);
        return (string) ob_get_clean();
    }

    public function testValidImageGetsRandomNameAndIsReEncoded(): void
    {
        $name = $this->uploader()->storeImage($this->fakeFile($this->realPng(), '../../evil name.php.png'), 'products');
        $this->assertTrue((bool) preg_match('/^[a-f0-9]{32}\.png$/', $name), "bad name {$name}");
        $this->assertTrue(is_file($this->dir . '/products/' . $name));
    }

    public function testPhpFileDisguisedAsJpgIsRejected(): void
    {
        $e = $this->assertThrows(UploadException::class, fn () => $this->uploader()->storeImage(
            $this->fakeFile('<?php system($_GET["c"]); ?>', 'shell.jpg'),
            'products'
        ));
        $this->assertStringContains('image', strtolower($e->getMessage()));
    }

    public function testPolyglotImageLosesTrailingPhp(): void
    {
        $payload = $this->realPng() . '<?php echo "pwned"; ?>';
        $name = $this->uploader()->storeImage($this->fakeFile($payload, 'x.png'), 'products');
        $stored = (string) file_get_contents($this->dir . '/products/' . $name);
        $this->assertStringNotContains('<?php', $stored, 're-encoding must strip appended code');
    }

    public function testOversizeAndErrorsAreRejected(): void
    {
        $up = new Upload($this->dir, fn ($a, $b) => copy($a, $b), fn ($p) => true, maxBytes: 10);
        $this->assertThrows(UploadException::class, fn () => $up->storeImage($this->fakeFile($this->realPng()), 'products'));

        $file = $this->fakeFile($this->realPng());
        $file['error'] = UPLOAD_ERR_PARTIAL;
        $this->assertThrows(UploadException::class, fn () => $this->uploader()->storeImage($file, 'products'));
    }

    public function testFolderMustBeAllowListed(): void
    {
        $this->assertThrows(\InvalidArgumentException::class, fn () => $this->uploader()->storeImage($this->fakeFile($this->realPng()), '../public'));
    }

    public function testNotUploadedFileIsRejected(): void
    {
        $up = new Upload($this->dir, fn ($a, $b) => copy($a, $b), fn ($p) => false);
        $this->assertThrows(UploadException::class, fn () => $up->storeImage($this->fakeFile($this->realPng()), 'products'));
    }

    public function testNoFileReturnsNullWhenOptional(): void
    {
        $this->assertNull($this->uploader()->storeOptionalImage(['error' => UPLOAD_ERR_NO_FILE], 'products'));
        $this->assertNull($this->uploader()->storeOptionalImage(null, 'products'));
    }
}

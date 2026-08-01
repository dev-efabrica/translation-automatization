<?php

namespace Efabrica\TranslationsAutomatization\Tests\Command\CheckTranslations;

use Efabrica\TranslationsAutomatization\Command\CheckTranslations\LegacyClassMethodArgVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class LegacyReceiverArgposTest extends TestCase
{
    public function testItemActionModalUsesLabelAtPositionOne(): void
    {
        $keys = $this->analyze('SomeGrid.php', <<<'PHP'
<?php
class SomeGrid
{
    protected function itemActions(RowActionCollection $actions): void
    {
        $actions->modal('create_config', 'dictionary.configs.create', 'public');
    }
}
PHP);

        $this->assertCount(1, $keys);
        $this->assertSame('dictionary.configs.create', $keys[0]['key']);
        $this->assertSame('modal', $keys[0]['call']);
    }

    public function testHeaderActionModalUsesLabelAtPositionTwo(): void
    {
        $keys = $this->analyze('SomeGrid.php', <<<'PHP'
<?php
class SomeGrid
{
    protected function headerActions(HeaderActionCollection $actions): void
    {
        $actions->modal($this->modalFactory, 'export', 'dictionary.actions.export', 'download');
    }
}
PHP);

        $this->assertCount(1, $keys);
        $this->assertSame('dictionary.actions.export', $keys[0]['key']);
    }

    public function testModalWithUnknownReceiverProducesCandidateKeys(): void
    {
        $keys = $this->analyze('SomeGrid.php', <<<'PHP'
<?php
class SomeGrid
{
    protected function itemActions($actions): void
    {
        $actions->modal('create_config', 'dictionary.configs.create', 'public');
    }
}
PHP);

        $this->assertCount(1, $keys);
        $this->assertNull($keys[0]['key']);
        $this->assertSame(['dictionary.configs.create', 'public'], $keys[0]['keyCandidates']);
    }

    public function testModalOnUnrelatedTypedReceiverIsSkipped(): void
    {
        $keys = $this->analyze('SomeGrid.php', <<<'PHP'
<?php
class SomeGrid
{
    protected function itemActions(PdfBuilder $builder): void
    {
        $builder->modal('a', 'b', 'c');
    }
}
PHP);

        $this->assertCount(0, $keys);
    }

    public function testClosureInheritsReceiverTypeFromUse(): void
    {
        $keys = $this->analyze('SomeGrid.php', <<<'PHP'
<?php
class SomeGrid
{
    protected function itemActions(RowActionCollection $actions): void
    {
        $callback = function () use ($actions): void {
            $actions->modal('create_config', 'dictionary.configs.create', 'public');
        };
    }
}
PHP);

        $this->assertCount(1, $keys);
        $this->assertSame('dictionary.configs.create', $keys[0]['key']);
    }

    public function testCustomColumnWithEmptyLabelIsAllowed(): void
    {
        $keys = $this->analyze('SomeGrid.php', <<<'PHP'
<?php
class SomeGrid
{
    protected function columns(RowColumnCollection $columns): void
    {
        $columns->custom('name', '', 'renderer');
    }
}
PHP);

        $this->assertCount(0, $keys);
    }

    public function testCustomColumnWithLabelIsStillChecked(): void
    {
        $keys = $this->analyze('SomeGrid.php', <<<'PHP'
<?php
class SomeGrid
{
    protected function columns(RowColumnCollection $columns): void
    {
        $columns->custom('environments_count', 'Environments', 'renderer');
    }
}
PHP);

        $this->assertCount(1, $keys);
        $this->assertSame('Environments', $keys[0]['key']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function analyze(string $fileName, string $code): array
    {
        $config = require __DIR__ . '/../../../src/Command/CheckTranslations/Config.php';
        $keys = [];
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new LegacyClassMethodArgVisitor($keys, $fileName, $config));
        $ast = $parser->parse($code);
        $this->assertNotNull($ast);
        $traverser->traverse($ast);

        return $keys;
    }
}

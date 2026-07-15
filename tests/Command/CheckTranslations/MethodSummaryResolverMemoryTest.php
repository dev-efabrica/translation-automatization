<?php

namespace Efabrica\TranslationsAutomatization\Tests\Command\CheckTranslations;

use Efabrica\TranslationsAutomatization\Command\CheckTranslations\ExpressionSubstitutor;
use Efabrica\TranslationsAutomatization\Command\CheckTranslations\MethodBodyCache;
use Efabrica\TranslationsAutomatization\Command\CheckTranslations\MethodSummaryResolver;
use Efabrica\TranslationsAutomatization\Command\CheckTranslations\ProjectClassIndex;
use Efabrica\TranslationsAutomatization\Command\CheckTranslations\TranslationKeyExpressionResolver;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class MethodSummaryResolverMemoryTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->temporaryFiles = [];
    }

    public function testCandidatesPerMethodAreCapped(): void
    {
        // Chain of methods where each level calls the next one twice: without the cap the
        // summary of level1 would contain 2^10 = 1024 candidates and grow exponentially.
        $levels = 10;
        $methods = '';
        for ($i = 1; $i <= $levels; $i++) {
            $next = $i + 1;
            $body = $i < $levels
                ? "return \$this->level$next(\$key . '.a') . \$this->level$next(\$key . '.b');"
                : "return \$this->translator->translate(\$key . '.leaf1') . \$this->translator->translate(\$key . '.leaf2');";
            $methods .= "    public function level$i(string \$key): string { $body }\n";
        }

        $resolver = $this->createResolver("<?php\nclass ChainService\n{\n$methods}\n");

        $candidates = $resolver->resolveMethod('ChainService', 'level1');

        $this->assertCount(MethodSummaryResolver::MAX_CANDIDATES_PER_METHOD, $candidates);
    }

    public function testSmallMethodSummaryIsNotTruncated(): void
    {
        $resolver = $this->createResolver(<<<'PHP'
<?php
class SimpleService
{
    public function label(): string
    {
        return $this->translator->translate('simple.one') . $this->translator->translate('simple.two');
    }
}
PHP);

        $this->assertCount(2, $resolver->resolveMethod('SimpleService', 'label'));
    }

    public function testSelfReferentialAssignmentWithTernaryDoesNotLoopForever(): void
    {
        // Regression: `$text = $cond ? ltrim($text) : rtrim($text);` used to put a
        // self-referential expression into the substitution map, and every ternary
        // recursion level inlined it again until memory ran out.
        $resolver = $this->createResolver(<<<'PHP'
<?php
class TrimLikeService
{
    public function normalize(string $text, ?string $mask): string
    {
        $text = $mask === null ? ltrim($text) : ltrim($text, $mask);
        $text = $mask === null ? rtrim($text) : rtrim($text, $mask);
        return $this->translator->translate($text);
    }
}
PHP);

        $candidates = $resolver->resolveMethod('TrimLikeService', 'normalize');

        $this->assertCount(1, $candidates);
    }

    public function testSubstitutorReturnsOriginalWhenNothingToSubstitute(): void
    {
        $substitutor = new ExpressionSubstitutor();
        $expression = new String_('static.key');

        $this->assertSame($expression, $substitutor->substitute($expression, []));
        $this->assertSame($expression, $substitutor->substitute($expression, ['key' => new String_('other')]));
    }

    public function testSubstitutorStillSubstitutesMatchingVariables(): void
    {
        $substitutor = new ExpressionSubstitutor();
        $expression = new Expr\BinaryOp\Concat(new String_('prefix.'), new Expr\Variable('key'));

        $substituted = $substitutor->substitute($expression, ['key' => new String_('suffix')]);

        $this->assertNotSame($expression, $substituted);
        $this->assertInstanceOf(Expr\BinaryOp\Concat::class, $substituted);
        $this->assertInstanceOf(String_::class, $substituted->right);
        $this->assertSame('suffix', $substituted->right->value);
        $this->assertInstanceOf(Expr\Variable::class, $expression->right);
    }

    private function createResolver(string $code): MethodSummaryResolver
    {
        $file = tempnam(sys_get_temp_dir(), 'resolver-test-') . '.php';
        file_put_contents($file, $code);
        $this->temporaryFiles[] = $file;

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $this->assertNotNull($ast);

        $classIndex = new ProjectClassIndex();
        $classIndex->setBodyCache(new MethodBodyCache($parser));
        $classIndex->collectFromAst($ast, $file);
        $classIndex->resolveAllConstants(new TranslationKeyExpressionResolver());

        return new MethodSummaryResolver($classIndex);
    }
}

<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckFormKeys;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

class ClassMethodArgVisitor extends NodeVisitorAbstract
{
    // class_part => lang_key position => methodName
    private const CLASS_ARGPOS_METHODS = [
        'ALL' => [
            0 => [
                'translate', // ITranslator
            ],
        ],
        'Grid' => [
            0 => [
                'trans',
                'global' // filter
            ],
            1 => [
                'dateTime', // columns
                'text', // columns
                'add', // actions
                'range', // filter
                'dateRange', // filter
                'comparator', // filter
                'number', // columns
                'link', // columns
                'customInfo', // columns
                'ajaxModal' // action
            ],
            2 => [
                'select', // filter
                'multiselect', // filter
                'choozer', // filter
                'ajaxSelect', // filter
                'checkboxList', // filter
                'multiValueComparator', // filter
                'published', // filter
                'modal', // action
                'createModal', // action
                'create', // headerActions
                'delete', // groupAction
                'deleteFromRepo', // groupAction
                'addInfo', // columns
            ]
        ],
        'Form' => [
            0 => [
                'setRequired',
            ],
            1 => [
                'addText',
                'addTextArea',
                'addEmail',
                'addInteger',
                'addFloat',
                'addDate',
                'addTime',
                'addDateTime',
                'addUpload',
                'addMultiUpload',
                'addCheckbox',
                'addRadioList',
                'addCheckboxList',
                'addSelect',
                'addColor',
                'addSubmit',
                'addButton',
                'addAjaxTags',
                'addDateTimePicker',
                'custom',
                'addRule',
            ],
            2 => [
                'addChooze',
                'infoBadge',
            ],
        ],
        'Module' => [
            2 => [
                'addResource'
            ],
        ]
    ];
    private const ALLOW_EMPTY_TRANSLATION = [
        'Form' => [
            1 => [
                'addSelect',
                'addTextArea'
            ]
        ],
    ];
    private const ARGPOS_CLASSES = [
        0 => [
            'Efabrica\WebComponent\Core\Menu\MenuItem',
        ],
    ];

    private $keys = [];

    private $filePath;

    private $className;

    private $classArgposClassesMap = [];

    public function __construct(array &$keys, string $filePath)
    {
        $this->keys = &$keys;
        $this->filePath = $filePath;
        $this->className = (string)pathinfo($filePath, PATHINFO_FILENAME);
    }

    public function enterNode(Node $node)
    {
        $this->prepareUseClasses($node);
        // find new Class(arg1, arg2, arg3)
        if ($node instanceof New_ && isset($node->class) && in_array($node->class->name ?? null, $this->classArgposClassesMap, true)) {
            $className = $node->class->name;
            $argIndex = array_search($className, $this->classArgposClassesMap);
            $args = $node->args;
            if (isset($args[$argIndex]) && $args[$argIndex]->value instanceof String_) {
                $key = $args[$argIndex]->value->value;
                $this->addKey($args[$argIndex]->getStartLine(), $className, $key);
            }
        }
        // find in Class ->method(arg1, arg2, arg3)
        if ($node instanceof MethodCall &&
            $node->name instanceof Identifier) {
            $methodName = $node->name->name;
            foreach (self::CLASS_ARGPOS_METHODS as $classNamePart => $argposMethods) {
                if ($classNamePart !== 'ALL' && strpos($this->className, $classNamePart) === false) {
                    continue;
                }
                foreach ($argposMethods as $argIndex => $methods) {
                    if (in_array($methodName, $methods, true)) {
                        $this->extractKeyFromArgument($node, $argIndex, $classNamePart);
                    }
                }
            }
        }
    }

    private function prepareUseClasses(Node $node)
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $useName = $use->name->name;
                foreach (self::ARGPOS_CLASSES as $argIndex => $classes) {
                    if (in_array($useName, $classes)) {
                        $shortName = basename(str_replace('\\', '/', $useName));
                        $this->classArgposClassesMap[$argIndex] = $shortName;
                    }
                }
            }
        }
    }

    private function extractKeyFromArgument(MethodCall $node, int $argIndex, string $classNamePart): void
    {
        $args = $node->args;
        if (isset($args[$argIndex]) && $args[$argIndex]->value instanceof String_) {
            $method = $node->name->name;
            $arg = null;
            if ($method === 'translate' && isset($args[$argIndex + 1]) && $args[$argIndex + 1]->value instanceof Node\Expr\Array_) {
                $arg = $args[$argIndex + 1]->value->items[0]->key->value;
            }

            $key = $args[$argIndex]->value->value;
            if (
                array_key_exists($classNamePart, self::ALLOW_EMPTY_TRANSLATION) &&
                array_key_exists($argIndex, self::ALLOW_EMPTY_TRANSLATION[$classNamePart]) &&
                $key === '' &&
                (in_array($method, self::ALLOW_EMPTY_TRANSLATION[$classNamePart][$argIndex], true))
            ) {
                return;
            }
            $this->addKey($args[$argIndex]->getStartLine(), $method, $key, $arg);
        }
    }

    private function addKey(int $line, string $call, string $key, string $arg = null): void
    {
        $this->keys[] = [
            'file' => $this->filePath,
            'line' => $line,
            'call' => $call,
            'key' => $key,
            'arg' => $arg,
        ];
    }
}

<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckFormKeys;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
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
                'create', // action
                'modal', // action
                'number', // columns
                'dateTime', // columns
                'link', // columns
                'customInfo', // columns
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
                'delete', // groupAction
                'deleteFromRepo', // groupAction
                'addInfo', // columns
            ]
        ],
        'Form' => [
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
            ],
            2 => [
                'addChooze',
            ],
        ],
    ];

    private $keys = [];

    private $filePath;

    private $className;

    public function __construct(array &$keys, string $filePath)
    {
        $this->keys = &$keys;
        $this->filePath = $filePath;
        $this->className = (string)pathinfo($filePath, PATHINFO_FILENAME);
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof MethodCall &&
            $node->name instanceof Node\Identifier) {
            $methodName = $node->name->name;
            foreach (self::CLASS_ARGPOS_METHODS as $classNamePart => $argposMethods) {
                if ($classNamePart !== 'ALL' && strpos($this->className, $classNamePart) === false) {
                    continue;
                }
                foreach ($argposMethods as $argIndex => $methods) {
                    if (in_array($methodName, $methods, true)) {
                        $this->extractKeyFromArgument($node, $argIndex);
                    }
                }
            }
        }
    }

    private function extractKeyFromArgument(MethodCall $node, int $argIndex): void
    {
        $args = $node->args;
        if (isset($args[$argIndex]) && $args[$argIndex]->value instanceof String_) {
            $method = $node->name->name;
            $arg = null;
            if ($method === 'translate' && isset($args[$argIndex + 1]) && $args[$argIndex + 1]->value instanceof Node\Expr\Array_) {
                $arg = $args[$argIndex + 1]->value->items[0]->key->value;
            }
            $this->keys[] = [
                'file' => $this->filePath,
                'line' => $node->getStartLine(),
                'method' => $method,
                'key' => $args[$argIndex]->value->value,
                'arg' => $arg,
            ];
        }
    }
}

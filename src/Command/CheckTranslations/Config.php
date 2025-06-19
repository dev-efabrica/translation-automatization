<?php
// class_part => lang_key position => methodName
return [
    'CLASS_ARGPOS_METHODS' => [
        'ALL' => [
            0 => [
                'translate', // ITranslator
                'flashMessage', // from Presenters
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
                'column' // sort
            ]
        ],
        'Form' => [
            0 => [
                'setRequired'
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
            ]
        ]
    ],
    'ALLOW_EMPTY_TRANSLATION' => [
        'Form' => [
            1 => [
                'addSelect',
                'addTextArea'
            ]
        ],
    ],
    'ARGPOS_CLASSES' => [
        0 => [
            'Efabrica\WebComponent\Core\Menu\MenuItem'
        ]
    ]
];


<?php
// class_part => lang_key position => methodName
$formArgposMethods = [
    0 => [
        'setRequired',
        'addGroup',
        'addRemoveButton', // multiplier
        'addCreateButton', // multiplier
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
        'addMultiSelect',
        'addColor',
        'addSubmit',
        'addButton',
        'addAjaxTags',
        'addDateTimePicker',
        'addPassword',
        'addToggleSwitch',
        'addRte',
        'custom',
        'addRule',
        'addChoozeAbTestingCondition',
        'addChoozeCatalogue',
        'addChoozeCategory',
        'addChoozeChannel',
        'addChoozeChannelEpg',
        'addChoozeChannelList',
        'addChoozeCompetition',
        'addChoozeContent',
        'addChoozeContentAll',
        'addChoozeContentGroups',
        'addChoozeEpgItem',
        'addChoozeEpisode',
        'addChoozeFaqGroup',
        'addChoozeGame',
        'addChoozeGenre',
        'addChoozeImage',
        'addChoozeMenu',
        'addChoozeMenuItem',
        'addChoozeMenuLevel',
        'addChoozePage',
        'addChoozePersona',
        'addChoozePlayoffStatsTypesCl',
        'addChoozeSeasons',
        'addChoozeShowMovieCollection',
        'addChoozeShowMovieCompetition',
        'addChoozeTeamGameStatsTypesCl',
        'addChoozeTeamStatsTypesCl',
        'addChoozeTeamStatsTypesOrderingCl',
        'addChoozeTemplateType',
    ],
    2 => [
        'addChooze',
        'infoBadge',
        'addImages',
    ],
    3 => [
        'addTagList',
    ],
    4 => [
        'addTagList',
    ],
];

$formAllowEmptyTranslation = [
    1 => [
        'addSelect',
        'addTextArea',
    ],
];

$gridArgposMethods = [
    0 => array_merge([
        'trans',
        'global', // filter
        'addExportCsv', // ublaboo
        'addExportCsvFiltered', // ublaboo
    ], $formArgposMethods[0]),
    1 => array_merge([
        'dateTime', // columns
        'text', // columns
        'add', // actions
        'range', // filter
        'dateRange', // filter
        'comparator', // filter
        'number', // columns
        'link', // columns
        'customInfo', // columns
        'ajaxModal', // action
        // ublaboo datagrid (ebox-crm)
        'addColumnText',
        'addColumnDateTime',
        'addColumnNumber',
        'addColumnBoolean',
        'addFilterText',
        'addFilterSelect',
        'addFilterDateRange',
        'addFilterRange',
        'addFilterBoolean',
        'addAction',
        'addActionCallback',
    ], $formArgposMethods[1]),
    2 => array_merge([
        'select', // filter
        'multiselect', // filter
        'choozer', // filter
        'ajaxSelect', // filter
        'checkboxList', // filter
        'multiValueComparator', // filter
        'published', // filter
        'modal', // action title key, arg 1 is often an internal action slug like "export"
        'createModal', // action
        'create', // headerActions
        'delete', // groupAction
        'deleteFromRepo', // groupAction
        'addInfo', // columns
        'column', // sort
    ], $formArgposMethods[2]),
    3 => $formArgposMethods[3],
    4 => $formArgposMethods[4],
];

$gridAllowEmptyTranslation = [
    1 => array_merge([
        'addAction', // ublaboo, icon-only actions
        'addActionCallback', // ublaboo, icon-only actions
    ], $formAllowEmptyTranslation[1]),
];

return [
    'CLASS_ARGPOS_METHODS' => [
        'ALL' => [
            0 => [
                'translate', // ITranslator
                'flashMessage', // from Presenters
            ],
        ],
        'Grid' => $gridArgposMethods,
        'GridFactory' => $gridArgposMethods,
        'TableFactory' => $gridArgposMethods,
        'Form' => $formArgposMethods,
        'FormFactory' => $formArgposMethods,
        'Presenter' => $formArgposMethods,
        'Helper' => $formArgposMethods,
        'DataProvider' => $formArgposMethods,
        'Criteria' => $formArgposMethods,
        'Behavior' => $formArgposMethods,
        'Change' => $formArgposMethods,
        'Action' => $formArgposMethods,
        'MixedContent' => $formArgposMethods,
        'Control' => $formArgposMethods,
        'Controls' => $formArgposMethods,
        'Widget' => $formArgposMethods,
        'Trait' => $formArgposMethods,
        'ExternalLogin' => $formArgposMethods,
        'Module' => [
            2 => [
                'addResource',
            ],
        ],
        'Plugin' => [
            1 => [
                'dropdown',
                'multiDropdown',
                'string',
                'number',
                'choozer',
                'checkbox',
                'multi',
                'dateTime',
                'text',
                'StringConfigItem',
                'DateTimeConfigItem',
                'NumberConfigItem',
                'ChoozerConfigItem',
            ],
            2 => [
                'dropdown',
            ],
            3 => [
                'dropdown',
            ],
        ],
    ],
    'ALLOW_EMPTY_TRANSLATION' => [
        'Grid' => $gridAllowEmptyTranslation,
        'GridFactory' => $gridAllowEmptyTranslation,
        'TableFactory' => $gridAllowEmptyTranslation,
        'Form' => $formAllowEmptyTranslation,
        'FormFactory' => $formAllowEmptyTranslation,
        'Presenter' => $formAllowEmptyTranslation,
        'Helper' => $formAllowEmptyTranslation,
        'DataProvider' => $formAllowEmptyTranslation,
        'Criteria' => $formAllowEmptyTranslation,
        'Behavior' => $formAllowEmptyTranslation,
        'Change' => $formAllowEmptyTranslation,
        'Action' => $formAllowEmptyTranslation,
        'MixedContent' => $formAllowEmptyTranslation,
        'Control' => $formAllowEmptyTranslation,
        'Controls' => $formAllowEmptyTranslation,
        'Widget' => $formAllowEmptyTranslation,
        'Trait' => $formAllowEmptyTranslation,
        'ExternalLogin' => $formAllowEmptyTranslation,
        'Plugin' => [
            3 => [
                'dropdown',
            ],
        ],
    ],
    'ARGPOS_CLASSES' => [
        0 => [
            'Efabrica\WebComponent\Core\Menu\MenuItem',
        ],
    ],
];

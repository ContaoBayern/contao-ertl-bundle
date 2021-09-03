<?php

$GLOBALS['TL_DCA']['tl_member_token'] = [
    'config' => [
        'dataContainer'    => 'Table',
        // 'closed'           => true,
        // 'notEditable'      => true,
        // 'notCopyable'      => true,
        'enableVersioning' => true,
        'ptable'           => 'tl_member',
        'sql'              => [
            'keys' => [
                'id'           => 'primary',
                'token,domain' => 'unique',
            ],
        ],
    ],

    // 'list' and 'palettes' are only needed if we decide to show this table in Contao's backend

    'list' => [
        'sorting'           => [
            'mode'        => 2, // records are sorted by a switchable field
            'flag'        => 1, // sort by initial letter ascending
            'fields'      => ['token', 'tstamp', 'validuntil', 'domain'],
            'panelLayout' => 'sort,filter;search,limit',
        ],
        'label'             => [
            'fields' => ['token', 'domain', 'pid'],
            'format' => '%s <span class="tl_gray">%s</span> <span class="tl_gray">[Member-ID %s]</span>',
            // TODO (?) Callback um z.B. zur Member-ID die E-Mail-Adresse anzeigen zu können
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                //'label' => &$GLOBALS['TL_LANG']['tl_member_token']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'delete' => [
                //'label' => &$GLOBALS['TL_LANG']['tl_member_token']['edit'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
            ],
            'show'   => [
                //'label' => &$GLOBALS['TL_LANG']['tl_member_token']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    'palettes' => [
        'default' => '{settings_legend},token,domain,validuntil,pid',
    ],

    'fields' => [
        'id'         => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp'     => [
            'sorting' => true,
            'flag'    => 5, // sort by day ascending
            'sql'     => "int(10) unsigned NOT NULL default '0'",
        ],
        'token'      => [
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 64, 'tl_class' => 'w50'],
            'search'    => true,
            'filter'    => false,
            'sorting'   => false,
            'flag'      => 11, // sort ascending
            'exclude'   => true,
            'sql'       => "VARCHAR(64) NOT NULL default ''",
        ],
        'domain'     => [
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 64, 'tl_class' => 'w50'],
            'search'    => false,
            'filter'    => true,
            'sorting'   => true,
            'flag'      => 11, // sort ascending
            'exclude'   => true,
            'sql'       => "VARCHAR(64) NOT NULL default ''",
        ],
        'pid'        => [
            // the associated member's ID
            'inputType'  => 'select',
            'eval'       => ['mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'search'     => true,
            'filter'     => false,
            'sorting'    => true,
            'flag'       => 11, // sort ascending
            'exclude'    => true,
            'foreignKey' => 'tl_member.username',
            'sql'        => "int(10) unsigned NOT NULL default '0'",
        ],
        'validuntil' => [
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'tl_class' => 'w50 wizard', 'rgxp' => 'datim', 'datepicker' => true],

            'search'  => false,
            'filter'  => false,
            'sorting' => true,
            'flag'    => 5, // sort by day ascending
            'exclude' => true,
            'sql'     => "int(10) unsigned NOT NULL default '0'",
        ],
    ],

];

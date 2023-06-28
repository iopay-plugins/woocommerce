<?php
return array(
    'target_php_version' => '8.1',
    'minimum_target_php_version' => '7.4',
    'backward_compatibility_checks' => true,
    'color_issue_messages_if_supported' => true,
    'scalar_implicit_cast' => true,
    'disable_suggestions' => true,
    'read_type_annotations' => false,
    'analyzed_file_extensions' => array('php'),
    'suppress_issue_types' => array(),

    'directory_list' => array(
        'includes/',
        '.phan/stubs/',
    ),

    'exclude_analysis_directory_list' => array(
        'vendor/',
        '.phan/stubs/',
    ),

    'exclude_file_list' => array(
        '.phan/stubs/',
    ),
);

<?php

return [
    'rootLogger' => [
        'appenders' => ['EHA'],
    ],
    'appenders' => [
        'EHA' =>  [
            'class' => 'LoggerAppenderRollingFile',
            'layout' => array(
                'class' => 'LoggerLayoutSimple',
            ),
            'params' => [
                'file' => getenv("DEFAULT_LOGS_PATH").DIRECTORY_SEPARATOR."EHA-APP.log",
                'maxFileSize' => (int)getenv("LOGS_FILE_SIZE"), // exple: 50MB
                'maxBackupIndex' => (int)getenv("MAX_BACKUP_INDEX"),
            ],
        ]
    ]
];

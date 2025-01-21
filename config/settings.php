<?php
return [
  "app" => [
    "encoding" => "utf-8",
    "language" => "en-US",
    "file-path" => "/downloads",
    "allowed-extensions" => [
      'json'
    ],
    "log-location" => __DIR__ . "/../logs/downloadphp.log",
    "log-level" => Monolog\Logger::DEBUG
  ],
  "database" => [ 
    "dsn" => "sqlite:" . __DIR__ . "/../resources/data/dl.db"
  ]
];
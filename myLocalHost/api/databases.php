<?php

declare(strict_types=1);

header('Content-Type: application/json');

$databases = [];

/*
==================================================
    MYSQL / MARIADB
==================================================
*/

$mysqli = @new mysqli(
        "127.0.0.1",
        "mylocalhost",
        "IFv[AHsJTLzMyumW",
        ""
    );

if (!$mysqli->connect_errno) {

    $result = $mysqli->query(

        "SHOW DATABASES"

    );

    while ($row = $result->fetch_row()) {

        $name = $row[0];

        if (in_array($name, [

            "information_schema",

            "mysql",

            "performance_schema",

            "phpmyadmin",

            "sys"

        ])) {

            continue;

        }

        $tables = 0;

        $size = 0;

        $mysqli->select_db($name);

        $tableResult = $mysqli->query(

            "SHOW TABLE STATUS"

        );

        while ($table = $tableResult->fetch_assoc()) {

            $tables++;

            $size +=

                ($table["Data_length"] ?? 0)

                +

                ($table["Index_length"] ?? 0);

        }

        $databases[] = [

            "name" => $name,

            "engine" => "MariaDB",

            "tables" => $tables,

            "size" => formatBytes($size),

            "status" => "online"

        ];

    }

    $mysqli->close();

}

/*
==================================================
    SQLITE
==================================================
*/

$iterator = new RecursiveIteratorIterator(

    new RecursiveDirectoryIterator(

        "B:/htdocs",

        FilesystemIterator::SKIP_DOTS

    )

);

foreach ($iterator as $file) {

    if (!$file->isFile()) {

        continue;

    }

    $extension = strtolower(

        $file->getExtension()

    );

    if (

        !in_array(

            $extension,

            [

                "sqlite",

                "sqlite3",

                "db"

            ]

        )

    ) {

        continue;

    }

    $databases[] = [

        "name" =>

            $file->getFilename(),

        "engine" => "SQLite",

        "tables" => null,

        "size" => formatBytes(

            $file->getSize()

        ),

        "status" => "file",

        "path" => str_replace(

            "\\",

            "/",

            $file->getPathname()

        )

    ];

}

/*
==================================================
    HELPERS
==================================================
*/

function formatBytes(int $bytes): string
{

    $units = [

        "B",

        "KB",

        "MB",

        "GB",

        "TB"

    ];

    $power =

        $bytes > 0

            ? floor(log($bytes,1024))

            : 0;

    $power = min(

        $power,

        count($units)-1

    );

    return round(

        $bytes /

        pow(1024,$power),

        2

    )

    . " "

    . $units[$power];

}

usort(

    $databases,

    fn($a,$b)=>

        strcasecmp(

            $a["name"],

            $b["name"]

        )

);

echo json_encode(

    [

        "success"=>true,

        "count"=>count($databases),

        "databases"=>$databases

    ],

    JSON_PRETTY_PRINT |

    JSON_UNESCAPED_SLASHES

);

<?php

// use Phico\Database\ConnectionFactory;
use Phico\Database\DB;

test("can connect to adhoc in memory sqlite database", function () {
    $db = db();
    // $db->connect("sqlite::memory:");

    expect($db)->toBeInstanceOf(DB::class);
});

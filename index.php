<?php

require 'flight/Flight.php';

require 'libs/jsondb.php';

// Flight::loader()::addDirectory(__DIR__ . '/libs');

Flight::register('jsondb', 'JsonDB', ['database.json']);

// var_dump(Flight::$loader);

Flight::route('/save-data', function(){
    $data = ['name' => 'John', 'age' => 30];
    Flight::jsondb()->saveData($data);
    echo 'Data saved successfully!';
});

Flight::route('/load-data', function(){
    $data = Flight::jsondb()->loadData();
    Flight::json($data);
});


Flight::route('/', function () {
    echo 'hello world!';
});



Flight::start();
